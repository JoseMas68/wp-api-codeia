<?php
/**
 * Media Handler
 *
 * Gestiona la subida y manipulación de archivos multimedia vía API.
 *
 * @package WP_API_Codeia\Utils
 */

namespace WP_API_Codeia\Utils;

/**
 * Class Media_Handler
 *
 * @package WP_API_Codeia\Utils
 */
class Media_Handler {

    /**
     * Obtener configuración de subida de media
     *
     * @return array Configuración
     */
    public function get_config(): array {
        $default_config = [
            'enabled' => true,
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'],
            'max_file_size' => 5 * 1024 * 1024, // 5MB
            'max_width' => 2560,
            'max_height' => 2560,
            'auto_orient' => true,
            'generate_thumbnails' => true,
            'compress_images' => true,
            'jpeg_quality' => 85,
            'png_compression' => 6,
        ];

        $config = get_option('wp_api_codeia_media_config', []);

        return array_merge($default_config, $config);
    }

    /**
     * Guardar configuración de media
     *
     * @param array $config Configuración
     * @return bool True si se guardó correctamente
     */
    public function save_config(array $config): bool {
        return update_option('wp_api_codeia_media_config', $config);
    }

    /**
     * Subir un archivo
     *
     * @param array  $file Datos del archivo ($_FILES)
     * @param int    $user_id ID del usuario que sube
     * @param string $post_type Post type asociado
     * @param array  $metadata Metadatos adicionales
     * @return array|WP_Error Array con attachment data o error
     */
    public function upload_file(array $file, int $user_id, string $post_type = 'attachment', array $metadata = []) {
        // Validar archivo
        $validation = $this->validate_file($file);

        if (is_wp_error($validation)) {
            return $validation;
        }

        // Obtener configuración
        $config = $this->get_config();

        // Preparar datos para subir
        $upload = wp_handle_upload($file, [
            'test_form' => false,
            'mimes' => get_allowed_mime_types(),
            'test_type' => true,
        ]);

        if (isset($upload['error'])) {
            return new \WP_Error('upload_error', $upload['error'], ['status' => 400]);
        }

        // Crear attachment
        $attachment = [
            'post_mime_type' => $upload['type'],
            'guid' => $upload['url'],
            'post_title' => $metadata['title'] ?? sanitize_file_name($file['name']),
            'post_content' => $metadata['description'] ?? '',
            'post_excerpt' => $metadata['caption'] ?? '',
            'post_status' => 'inherit',
            'post_author' => $user_id,
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file']);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generar metadatos
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        // Aplicar optimizaciones si está habilitado
        if ($config['compress_images'] && $this->is_image($upload['type'])) {
            $this->optimize_image($attachment_id, $config);
        }

        // Actualizar alt text
        if (!empty($metadata['alt_text'])) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($metadata['alt_text']));
        }

        // Guardar metadatos personalizados
        if (!empty($metadata['custom'])) {
            foreach ($metadata['custom'] as $key => $value) {
                update_post_meta($attachment_id, $key, sanitize_text_field($value));
            }
        }

        return $this->format_attachment_response($attachment_id);
    }

    /**
     * Subir múltiples archivos
     *
     * @param array $files Array de archivos
     * @param int   $user_id ID del usuario
     * @param array $metadata Metadatos a aplicar a todos
     * @return array Array con resultados
     */
    public function upload_multiple(array $files, int $user_id, array $metadata = []): array {
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        // Reorganizar array de archivos
        $files_array = $this->reorganize_files_array($files);

        foreach ($files_array as $index => $file) {
            $file_metadata = $metadata[$index] ?? $metadata;
            $result = $this->upload_file($file, $user_id, 'attachment', $file_metadata);

            if (is_wp_error($result)) {
                $results['failed'][] = [
                    'file' => $file['name'],
                    'error' => $result->get_error_message(),
                ];
            } else {
                $results['successful'][] = $result;
            }
        }

        return $results;
    }

    /**
     * Validar archivo antes de subir
     *
     * @param array $file Datos del archivo
     * @return true|WP_Error True si es válido o error
     */
    private function validate_file(array $file) {
        // Verificar que haya un archivo
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new \WP_Error('no_file', 'No file uploaded', ['status' => 400]);
        }

        // Verificar tamaño
        $config = $this->get_config();
        $file_size = filesize($file['tmp_name']);

        if ($file_size > $config['max_file_size']) {
            return new \WP_Error(
                'file_too_large',
                sprintf('File size exceeds maximum allowed size of %s', size_format($config['max_file_size'])),
                ['status' => 400]
            );
        }

        // Verificar tipo MIME
        $file_type = $file['type'] ?? mime_content_type($file['tmp_name']);

        if (!in_array($file_type, $config['allowed_types'], true)) {
            return new \WP_Error(
                'invalid_file_type',
                sprintf('File type %s is not allowed', $file_type),
                ['status' => 400]
            );
        }

        // Verificar dimensión si es imagen
        if ($this->is_image($file_type)) {
            $image_info = getimagesize($file['tmp_name']);

            if ($image_info !== false) {
                list($width, $height) = $image_info;

                if ($width > $config['max_width'] || $height > $config['max_height']) {
                    return new \WP_Error(
                        'image_too_large',
                        sprintf('Image dimensions exceed maximum allowed size of %dx%d', $config['max_width'], $config['max_height']),
                        ['status' => 400]
                    );
                }
            }
        }

        return true;
    }

    /**
     * Optimizar imagen
     *
     * @param int   $attachment_id ID del attachment
     * @param array $config Configuración
     * @return bool True si se optimizó correctamente
     */
    private function optimize_image(int $attachment_id, array $config): bool {
        $file_path = get_attached_file($attachment_id);

        if (!$file_path || !file_exists($file_path)) {
            return false;
        }

        $mime_type = get_post_mime_type($attachment_id);

        // Optimizar JPEG
        if ($mime_type === 'image/jpeg') {
            $image = imagecreatefromjpeg($file_path);

            if ($image) {
                // Auto orientar si está habilitado
                if ($config['auto_orient']) {
                    // Note: Auto-orientation requires exif extension
                    if (function_exists('exif_read_data')) {
                        $exif = exif_read_data($file_path);
                        if (!empty($exif['Orientation'])) {
                            $image = $this->orient_image($image, $exif['Orientation']);
                        }
                    }
                }

                imagejpeg($image, $file_path, $config['jpeg_quality']);
                imagedestroy($image);

                return true;
            }
        }

        // Optimizar PNG
        if ($mime_type === 'image/png') {
            $image = imagecreatefrompng($file_path);

            if ($image) {
                imagepng($image, $file_path, $config['png_compression']);
                imagedestroy($image);

                return true;
            }
        }

        return false;
    }

    /**
     * Orientar imagen según datos EXIF
     *
     * @param resource $image Imagen GD
     * @param int      $orientation Orientación EXIF
     * @return resource Imagen orientada
     */
    private function orient_image($image, int $orientation) {
        switch ($orientation) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        return $image;
    }

    /**
     * Verificar si un MIME type es de imagen
     *
     * @param string $mime_type MIME type
     * @return bool True si es imagen
     */
    private function is_image(string $mime_type): bool {
        return strpos($mime_type, 'image/') === 0;
    }

    /**
     * Reorganizar array de archivos para múltiples uploads
     *
     * @param array $files Array de $_FILES
     * @return array Array reorganizado
     */
    private function reorganize_files_array(array $files): array {
        $reorganized = [];

        foreach ($files['name'] as $key => $value) {
            $reorganized[$key] = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key],
            ];
        }

        return $reorganized;
    }

    /**
     * Formatear respuesta de attachment para API
     *
     * @param int $attachment_id ID del attachment
     * @return array Datos del attachment formateados
     */
    public function format_attachment_response(int $attachment_id): array {
        $attachment = get_post($attachment_id);

        if (!$attachment) {
            return [];
        }

        $metadata = wp_get_attachment_metadata($attachment_id);

        return [
            'id' => $attachment_id,
            'title' => $attachment->post_title,
            'description' => $attachment->post_content,
            'caption' => $attachment->post_excerpt,
            'alt_text' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'mime_type' => $attachment->post_mime_type,
            'url' => wp_get_attachment_url($attachment_id),
            'sizes' => $this->get_image_sizes($attachment_id, $metadata),
            'metadata' => $metadata,
            'author' => $attachment->post_author,
            'date' => $attachment->post_date,
            'modified' => $attachment->post_modified,
        ];
    }

    /**
     * Obtener tamaños de imagen disponibles
     *
     * @param int   $attachment_id ID del attachment
     * @param array $metadata Metadatos del attachment
     * @return array Array de tamaños
     */
    private function get_image_sizes(int $attachment_id, array $metadata): array {
        $sizes = [];

        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $base_url = wp_get_attachment_url($attachment_id);
            $base_url = preg_replace('/-\d+x\d+\.(jpg|jpeg|png|gif|webp)$/', '.$1', $base_url);

            foreach ($metadata['sizes'] as $size_name => $size_data) {
                $sizes[$size_name] = [
                    'url' => dirname($base_url) . '/' . $size_data['file'],
                    'width' => $size_data['width'],
                    'height' => $size_data['height'],
                    'mime_type' => $size_data['mime-type'] ?? null,
                ];
            }
        }

        return $sizes;
    }

    /**
     * Borrar un attachment
     *
     * @param int $attachment_id ID del attachment
     * @param bool $force Borrar permanentemente (no papelera)
     * @return bool True si se borró correctamente
     */
    public function delete_attachment(int $attachment_id, bool $force = false): bool {
        $result = wp_delete_attachment($attachment_id, $force);

        return $result !== false;
    }

    /**
     * Actualizar metadatos de un attachment
     *
     * @param int   $attachment_id ID del attachment
     * @param array $metadata Metadatos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function update_attachment_metadata(int $attachment_id, array $metadata): bool {
        $post_data = [];
        $meta_data = [];

        // Campos permitidos para actualizar
        $allowed_fields = ['post_title', 'post_content', 'post_excerpt'];

        foreach ($allowed_fields as $field) {
            if (isset($metadata[$field])) {
                $post_data[$field] = $metadata[$field];
            }
        }

        // Actualizar post
        if (!empty($post_data)) {
            $post_data['ID'] = $attachment_id;
            wp_update_post($post_data);
        }

        // Actualizar alt text
        if (isset($metadata['alt_text'])) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($metadata['alt_text']));
        }

        // Actualizar metadatos personalizados
        if (isset($metadata['custom']) && is_array($metadata['custom'])) {
            foreach ($metadata['custom'] as $key => $value) {
                update_post_meta($attachment_id, $key, sanitize_text_field($value));
            }
        }

        return true;
    }

    /**
     * Obtener estadísticas de uso de media
     *
     * @param int $user_id ID del usuario (0 para todos)
     * @return array Estadísticas
     */
    public function get_media_stats(int $user_id = 0): array {
        $args = [
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        if ($user_id > 0) {
            $args['author'] = $user_id;
        }

        $query = new \WP_Query($args);
        $attachments = $query->posts;
        $total_size = 0;
        $type_counts = [];

        foreach ($attachments as $attachment_id) {
            $file_path = get_attached_file($attachment_id);
            if ($file_path && file_exists($file_path)) {
                $total_size += filesize($file_path);
            }

            $mime_type = get_post_mime_type($attachment_id);
            if ($mime_type) {
                $type_counts[$mime_type] = ($type_counts[$mime_type] ?? 0) + 1;
            }
        }

        return [
            'total_files' => count($attachments),
            'total_size' => $total_size,
            'total_size_formatted' => size_format($total_size),
            'type_counts' => $type_counts,
        ];
    }
}
