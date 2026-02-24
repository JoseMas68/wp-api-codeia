<?php
/**
 * JetEngine Detector
 *
 * Detecta campos y meta boxes de JetEngine.
 *
 * @package WP_API_Codeia\Detectors
 */

namespace WP_API_Codeia\Detectors;

/**
 * Class JetEngine_Detector
 *
 * @package WP_API_Codeia\Detectors
 */
class JetEngine_Detector {

    /**
     * Cache key para meta boxes de JetEngine
     *
     * @var string
     */
    private string $cache_key = 'wp_api_codeia_jetengine_fields';

    /**
     * Detectar meta boxes y campos de JetEngine
     *
     * @return array Meta boxes y campos detectados
     */
    public function detect(): array {
        // Verificar si JetEngine está activo
        if (!$this->is_jetengine_active()) {
            return [];
        }

        // Intentar obtener de cache
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $meta_boxes = $this->get_meta_boxes();
        $detected = [];

        foreach ($meta_boxes as $meta_box) {
            $detected[$meta_box['id']] = [
                'id' => $meta_box['id'],
                'name' => $meta_box['name'] ?? $meta_box['title'] ?? '',
                'title' => $meta_box['title'] ?? '',
                'type' => $meta_box['type'] ?? 'post_type', // post_type, taxonomy, options_page
                'object_type' => $meta_box['object_type'] ?? [],
                'fields' => $this->parse_fields($meta_box['meta_fields'] ?? []),
                'position' => $meta_box['position'] ?? 'normal',
                'priority' => $meta_box['priority'] ?? 'high',
            ];
        }

        // Guardar en cache por 6 horas
        set_transient($this->cache_key, $detected, 6 * HOUR_IN_SECONDS);

        return $detected;
    }

    /**
     * Obtener meta boxes de JetEngine
     *
     * @return array Meta boxes
     */
    private function get_meta_boxes(): array {
        if (!function_exists('jet_engine')) {
            return [];
        }

        $jet_engine = \jet_engine();
        if (!isset($jet_engine->meta_boxes) || !method_exists($jet_engine->meta_boxes, 'get_items')) {
            return [];
        }

        $meta_boxes = $jet_engine->meta_boxes->get_items();

        // Filtrar solo meta boxes activos
        return array_filter($meta_boxes, function($box) {
            return isset($box['args']['enabled']) && $box['args']['enabled'] === true;
        });
    }

    /**
     * Parsear campos de un meta box
     *
     * @param array $fields Campos de JetEngine
     * @return array Campos parseados
     */
    private function parse_fields(array $fields): array {
        $parsed = [];

        foreach ($fields as $field) {
            $field_data = $field['__options'] ?? $field;
            $field_name = $field_data['name'] ?? $field_data['key'] ?? '';

            if (empty($field_name)) {
                continue;
            }

            $parsed[$field_name] = [
                'name' => $field_name,
                'label' => $field_data['title'] ?? $field_data['label'] ?? $field_name,
                'type' => $field_data['type'] ?? 'text',
                'required' => $field_data['required'] ?? false,
                'default_value' => $field_data['default'] ?? $field_data['default_value'] ?? '',
                'placeholder' => $field_data['placeholder'] ?? '',
                'description' => $field_data['description'] ?? '',
                'width' => $field_data['width'] ?? '100%',
                'class' => $field_data['class'] ?? '',
            ];

            // Campos específicos por tipo
            $field_type = $parsed[$field_name]['type'];

            if (in_array($field_type, ['select', 'radio', 'checkbox'])) {
                $parsed[$field_name]['options'] = $this->parse_options($field_data['options'] ?? []);
                $parsed[$field_name]['multiple'] = $field_data['is_multiple'] ?? false;
            }

            if ($field_type === 'switcher' || $field_type === 'checkbox') {
                $parsed[$field_name]['default_checked'] = $field_data['default_checked'] ?? false;
            }

            if ($field_type === 'number' || $field_type === 'slider') {
                $parsed[$field_name]['min'] = $field_data['min'] ?? '';
                $parsed[$field_name]['max'] = $field_data['max'] ?? '';
                $parsed[$field_name]['step'] = $field_data['step'] ?? 1;
            }

            if ($field_type === 'date') {
                $parsed[$field_name]['format'] = $field_data['format'] ?? 'Y-m-d';
                $parsed[$field_name]['range'] = $field_data['is_range'] ?? false;
            }

            if ($field_type === 'time') {
                $parsed[$field_name]['format'] = $field_data['format'] ?? 'H:i';
            }

            if ($field_type === 'iconpicker' || $field_type === 'icon-link') {
                $parsed[$field_name]['icons_set'] = $field_data['icons_set'] ?? 'font';
            }

            if ($field_type === 'posts') {
                $parsed[$field_name]['post_type'] = $field_data['post_type'] ?? ['post'];
                $parsed[$field_name]['multiple'] = $field_data['is_multiple'] ?? false;
                $parsed[$field_name]['query'] = $field_data['query'] ?? [];
            }

            if ($field_type === 'posts') {
                $parsed[$field_name]['taxonomy'] = $field_data['taxonomy'] ?? 'category';
                $parsed[$field_name]['multiple'] = $field_data['multiple'] ?? false;
                $parsed[$field_name]['query'] = $field_data['query'] ?? [];
            }

            if ($field_type === 'repeater') {
                $parsed[$field_name]['sub_fields'] = $this->parse_fields($field_data['item_fields'] ?? []);
                $parsed[$field_name]['min_rows'] = $field_data['min_rows'] ?? 0;
                $parsed[$field_name]['max_rows'] = $field_data['max_rows'] ?? 0;
            }

            if ($field_type === 'media') {
                $parsed[$field_name]['type_format'] = $field_data['type_format'] ?? 'url';
                $parsed[$field_name]['multiple'] = $field_data['multiple'] ?? false;
            }

            if ($field_type === 'wysiwyg') {
                $parsed[$field_name]['editor_height'] = $field_data['editor_height'] ?? 300;
                $parsed[$field_name]['media_buttons'] = $field_data['media_buttons'] ?? true;
            }

            if ($field_type === 'color') {
                $parsed[$field_name]['format'] = $field_data['format'] ?? 'hex';
                $parsed[$field_name]['alpha'] = $field_data['with_alpha'] ?? false;
            }

            if ($field_type === 'hidden') {
                $parsed[$field_name]['value'] = $field_data['default'] ?? '';
            }

            if ($field_type === 'html') {
                $parsed[$field_name]['content'] = $field_data['content'] ?? '';
            }

            if ($field_type === 'posts') {
                $parsed[$field_name]['post_type'] = $field_data['post_type'] ?? 'post';
                $parsed[$field_name]['multiple'] = $field_data['multiple'] ?? false;
            }

            if ($field_type === 'terms') {
                $parsed[$field_name]['taxonomy'] = $field_data['taxonomy'] ?? 'category';
                $parsed[$field_name]['multiple'] = $field_data['multiple'] ?? false;
            }

            if ($field_type === 'google-map') {
                $parsed[$field_name]['api_key'] = $field_data['api_key'] ?? '';
                $parsed[$field_name]['zoom'] = $field_data['zoom'] ?? 10;
            }

            if ($field_type === 'location') {
                $parsed[$field_name]['default_lat'] = $field_data['default_lat'] ?? '';
                $parsed[$field_name]['default_lng'] = $field_data['default_lng'] ?? '';
            }

            if ($field_type === 'meta-fields') {
                $parsed[$field_name]['sub_fields'] = $this->parse_fields($field_data['meta_fields'] ?? []);
            }

            if ($field_type === 'plain-html') {
                $parsed[$field_name]['html_content'] = $field_data['html_content'] ?? '';
            }
        }

        return $parsed;
    }

    /**
     * Parsear opciones de select/radio/checkbox
     *
     * @param array|object $options Opciones de JetEngine
     * @return array Opciones parseadas
     */
    private function parse_options($options): array {
        $parsed = [];

        if (is_array($options)) {
            foreach ($options as $option) {
                if (is_array($option)) {
                    $key = $option['key'] ?? $option['value'] ?? $option['id'] ?? '';
                    $label = $option['value'] ?? $option['label'] ?? $key;
                    if ($key) {
                        $parsed[$key] = $label;
                    }
                } elseif (is_string($option)) {
                    $parsed[$option] = $option;
                }
            }
        }

        return $parsed;
    }

    /**
     * Verificar si JetEngine está activo
     *
     * @return bool True si JetEngine está activo
     */
    public function is_jetengine_active(): bool {
        return wp_api_codeia_is_plugin_active('jet-engine/jet-engine.php') ||
               defined('JET_ENGINE_VERSION');
    }

    /**
     * Invalidar cache de JetEngine
     *
     * @return void
     */
    public function invalidate_cache(): void {
        delete_transient($this->cache_key);
    }

    /**
     * Obtener campos JetEngine para un post type específico
     *
     * @param string $post_type Post type
     * @return array Campos para el post type
     */
    public function get_fields_for_post_type(string $post_type): array {
        $all_fields = $this->detect();
        $fields_for_type = [];

        foreach ($all_fields as $meta_box) {
            // Verificar si el meta box aplica a este post type
            if (in_array($post_type, $meta_box['object_type'], true)) {
                $fields_for_type = array_merge($fields_for_type, $meta_box['fields']);
            }
        }

        return $fields_for_type;
    }

    /**
     * Obtener meta values de JetEngine para un post
     *
     * @param int         $post_id ID del post
     * @param string|null $field_name Nombre del campo (opcional)
     * @return mixed Valor del campo o todos los campos
     */
    public function get_field_value(int $post_id, ?string $field_name = null) {
        if (!$this->is_jetengine_active()) {
            return $field_name ? null : [];
        }

        // JetEngine usa get_post_meta nativamente
        if ($field_name) {
            return get_post_meta($post_id, $field_name, true);
        }

        // Obtener todos los campos de JetEngine para este post
        $post_type = get_post_type($post_id);
        $fields = $this->get_fields_for_post_type($post_type);
        $values = [];

        foreach ($fields as $field_key => $field) {
            $value = get_post_meta($post_id, $field_key, true);
            $values[$field_key] = $value;
        }

        return $values;
    }

    /**
     * Mapear tipo de campo JetEngine a tipo OpenAPI
     *
     * @param string $jetengine_type Tipo de campo JetEngine
     * @return array Tipo OpenAPI
     */
    public function map_jetengine_type_to_openapi(string $jetengine_type): array {
        $type_map = [
            'text' => ['type' => 'string'],
            'textarea' => ['type' => 'string'],
            'number' => ['type' => 'number'],
            'slider' => ['type' => 'number'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'url' => ['type' => 'string', 'format' => 'uri'],
            'password' => ['type' => 'string', 'format' => 'password'],
            'wysiwyg' => ['type' => 'string'],
            'image' => ['type' => 'object'],
            'media' => ['type' => 'object'],
            'select' => ['type' => 'string', 'enum' => []],
            'checkbox' => ['type' => 'boolean'],
            'switcher' => ['type' => 'boolean'],
            'radio' => ['type' => 'string'],
            'posts' => ['type' => 'array'],
            'post' => ['type' => 'integer', 'description' => 'Post ID'],
            'terms' => ['type' => 'array'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'time' => ['type' => 'string', 'format' => 'duration'],
            'datetime' => ['type' => 'string', 'format' => 'date-time'],
            'color' => ['type' => 'string', 'format' => 'hex'],
            'iconpicker' => ['type' => 'string'],
            'icon-link' => ['type' => 'string', 'format' => 'uri'],
            'repeater' => ['type' => 'array'],
            'google-map' => ['type' => 'object'],
            'location' => ['type' => 'object'],
            'hidden' => ['type' => 'string'],
            'html' => ['type' => 'string'],
            'plain-html' => ['type' => 'string'],
            'meta-fields' => ['type' => 'object'],
        ];

        return $type_map[$jetengine_type] ?? ['type' => 'string'];
    }

    /**
     * Obtener todos los post types relacionados con meta boxes
     *
     * @return array Post types con meta boxes
     */
    public function get_supported_post_types(): array {
        $all_fields = $this->detect();
        $post_types = [];

        foreach ($all_fields as $meta_box) {
            foreach ($meta_box['object_type'] as $type) {
                $post_types[$type] = true;
            }
        }

        return array_keys($post_types);
    }
}
