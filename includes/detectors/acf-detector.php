<?php
/**
 * ACF Detector
 *
 * Detecta campos y field groups de ACF (Advanced Custom Fields).
 *
 * @package WP_API_Codeia\Detectors
 */

namespace WP_API_Codeia\Detectors;

/**
 * Class ACF_Detector
 *
 * @package WP_API_Codeia\Detectors
 */
class ACF_Detector {

    /**
     * Cache key para field groups de ACF
     *
     * @var string
     */
    private string $cache_key = 'wp_api_codeia_acf_fields';

    /**
     * Detectar field groups y campos de ACF
     *
     * @return array Field groups y campos detectados
     */
    public function detect(): array {
        // Verificar si ACF está activo
        if (!$this->is_acf_active()) {
            return [];
        }

        // Intentar obtener de cache
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $field_groups = $this->get_field_groups();
        $detected = [];

        foreach ($field_groups as $field_group) {
            $detected[$field_group['key']] = [
                'key' => $field_group['key'],
                'title' => $field_group['title'],
                'description' => $field_group['description'] ?? '',
                'location' => $this->parse_location($field_group['location']),
                'fields' => $this->parse_fields($field_group['fields']),
                'position' => $field_group['position'] ?? 0,
                'style' => $field_group['style'] ?? 'default',
                'label_placement' => $field_group['label_placement'] ?? 'top',
                'instruction_placement' => $field_group['instruction_placement'] ?? 'label',
                'hide_on_screen' => $field_group['hide_on_screen'] ?? [],
                'active' => $field_group['active'] ?? true,
            ];
        }

        // Guardar en cache por 6 horas
        set_transient($this->cache_key, $detected, 6 * HOUR_IN_SECONDS);

        return $detected;
    }

    /**
     * Obtener field groups de ACF
     *
     * @return array Field groups
     */
    private function get_field_groups(): array {
        if (!function_exists('acf_get_field_groups')) {
            return [];
        }

        $field_groups = acf_get_field_groups();

        // Filtrar solo field groups activos
        return array_filter($field_groups, function($group) {
            return $group['active'] ?? true;
        });
    }

    /**
     * Parsear campos de un field group
     *
     * @param array $fields Campos de ACF
     * @return array Campos parseados
     */
    private function parse_fields(array $fields): array {
        $parsed = [];

        foreach ($fields as $field) {
            $parsed[$field['name']] = [
                'key' => $field['key'],
                'label' => $field['label'],
                'name' => $field['name'],
                'type' => $field['type'],
                'required' => $field['required'] ?? false,
                'default_value' => $field['default_value'] ?? '',
                'placeholder' => $field['placeholder'] ?? '',
                'prefix' => $field['prefix'] ?? '',
                'instructions' => $field['instructions'] ?? '',
                'wrapper' => $field['wrapper'] ?? [],
                'width' => $field['width'] ?? '',
                'class' => $field['class'] ?? '',
                'conditional_logic' => $field['conditional_logic'] ?? [],
                'choices' => $field['choices'] ?? [],
                'allow_null' => $field['allow_null'] ?? false,
                'multiple' => $field['multiple'] ?? false,
                'return_format' => $field['return_format'] ?? 'value',
                'min' => $field['min'] ?? '',
                'max' => $field['max'] ?? '',
                'step' => $field['step'] ?? '',
            ];

            // Campos específicos por tipo
            if ($field['type'] === 'image') {
                $parsed[$field['name']]['preview_size'] = $field['preview_size'] ?? 'medium';
                $parsed[$field['name']]['library'] = $field['library'] ?? 'all';
            }

            if ($field['type'] === 'wysiwyg') {
                $parsed[$field['name']]['tabs'] = $field['tabs'] ?? 'all';
                $parsed[$field['name']]['toolbar'] = $field['toolbar'] ?? 'full';
                $parsed[$field['name']]['media_upload'] = $field['media_upload'] ?? 1;
            }

            if ($field['type'] === 'relationship') {
                $parsed[$field['name']]['post_type'] = $field['post_type'] ?? [];
                $parsed[$field['name']]['taxonomy'] = $field['taxonomy'] ?? '';
                $parsed[$field['name']]['filters'] = $field['filters'] ?? [];
                $parsed[$field['name']]['elements'] = $field['elements'] ?? [];
                $parsed[$field['name']]['min'] = $field['min'] ?? 0;
                $parsed[$field['name']]['max'] = $field['max'] ?? '';
                $parsed[$field['name']]['return_format'] = $field['return_format'] ?? 'object';
            }

            if ($field['type'] === 'repeater') {
                $parsed[$field['name']]['sub_fields'] = $this->parse_fields($field['sub_fields'] ?? []);
                $parsed[$field['name']]['layout'] = $field['layout'] ?? 'table';
                $parsed[$field['name']]['min'] = $field['min'] ?? 0;
                $parsed[$field['name']]['max'] = $field['max'] ?? 0;
            }

            if ($field['type'] === 'flexible_content') {
                $parsed[$field['name']]['layouts'] = $this->parse_layouts($field['layouts'] ?? []);
            }

            if ($field['type'] === 'group') {
                $parsed[$field['name']]['sub_fields'] = $this->parse_fields($field['sub_fields'] ?? []);
                $parsed[$field['name']]['layout'] = $field['layout'] ?? 'block';
            }
        }

        return $parsed;
    }

    /**
     * Parsear layouts de flexible content
     *
     * @param array $layouts Layouts de ACF
     * @return array Layouts parseados
     */
    private function parse_layouts(array $layouts): array {
        $parsed = [];

        foreach ($layouts as $layout) {
            $parsed[$layout['name']] = [
                'key' => $layout['key'],
                'name' => $layout['name'],
                'label' => $layout['label'],
                'display' => $layout['display'] ?? 'block',
                'min' => $layout['min'] ?? '',
                'max' => $layout['max'] ?? '',
                'sub_fields' => $this->parse_fields($layout['sub_fields'] ?? []),
            ];
        }

        return $parsed;
    }

    /**
     * Parsear regla de ubicación
     *
     * @param array $location Regla de ubicación de ACF
     * @return array Ubicación parseada
     */
    private function parse_location(array $location): array {
        $parsed = [
            'rule_groups' => [],
            'post_types' => [],
            'taxonomies' => [],
            'user_roles' => [],
            'user_form' => false,
            'options_pages' => [],
        ];

        if (empty($location)) {
            return $parsed;
        }

        foreach ($location as $group_key => $or_group) {
            foreach ($or_group as $and_group) {
                foreach ($and_group as $rule) {
                    $param = $rule['param'] ?? '';
                    $operator = $rule['operator'] ?? '==';
                    $value = $rule['value'] ?? '';

                    if ($param === 'post_type') {
                        $parsed['post_types'][] = $value;
                    } elseif ($param === 'post_taxonomy') {
                        $parsed['taxonomies'][] = $value;
                    } elseif ($param === 'user_role') {
                        $parsed['user_roles'][] = $value;
                    } elseif ($param === 'options_page') {
                        $parsed['options_pages'][] = $value;
                    } elseif ($param === 'user_form') {
                        $parsed['user_form'] = $value == 'true';
                    }
                }
            }
        }

        // Eliminar duplicados
        $parsed['post_types'] = array_unique($parsed['post_types']);
        $parsed['taxonomies'] = array_unique($parsed['taxonomies']);
        $parsed['user_roles'] = array_unique($parsed['user_roles']);
        $parsed['options_pages'] = array_unique($parsed['options_pages']);

        return $parsed;
    }

    /**
     * Verificar si ACF está activo
     *
     * @return bool True si ACF está activo
     */
    public function is_acf_active(): bool {
        return wp_api_codeia_is_plugin_active('advanced-custom-fields/acf.php') ||
               class_exists('ACF');
    }

    /**
     * Invalidar cache de ACF
     *
     * @return void
     */
    public function invalidate_cache(): void {
        delete_transient($this->cache_key);
    }

    /**
     * Obtener campos ACF para un post type específico
     *
     * @param string $post_type Post type
     * @return array Campos para el post type
     */
    public function get_fields_for_post_type(string $post_type): array {
        $all_fields = $this->detect();
        $fields_for_type = [];

        foreach ($all_fields as $field_group) {
            // Verificar si el field group aplica a este post type
            if (in_array($post_type, $field_group['location']['post_types'], true)) {
                $fields_for_type = array_merge($fields_for_type, $field_group['fields']);
            }
        }

        return $fields_for_type;
    }

    /**
     * Obtener meta values de ACF para un post
     *
     * @param int    $post_id ID del post
     * @param string $field_name Nombre del campo (opcional)
     * @return mixed Valor del campo o todos los campos
     */
    public function get_field_value(int $post_id, ?string $field_name = null) {
        if (!$this->is_acf_active()) {
            return $field_name ? null : [];
        }

        if ($field_name) {
            return get_field($field_name, $post_id);
        }

        // Obtener todos los campos de ACF para este post
        $post_type = get_post_type($post_id);
        $fields = $this->get_fields_for_post_type($post_type);
        $values = [];

        foreach ($fields as $field_key => $field) {
            $value = get_field($field['name'], $post_id);
            $values[$field_key] = $value;
        }

        return $values;
    }

    /**
     * Mapear tipo de campo ACF a tipo OpenAPI
     *
     * @param string $acf_type Tipo de campo ACF
     * @return array Tipo OpenAPI
     */
    public function map_acf_type_to_openapi(string $acf_type): array {
        $type_map = [
            'text' => ['type' => 'string'],
            'textarea' => ['type' => 'string'],
            'number' => ['type' => 'number'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'url' => ['type' => 'string', 'format' => 'uri'],
            'password' => ['type' => 'string', 'format' => 'password'],
            'wysiwyg' => ['type' => 'string'],
            'image' => ['type' => 'object'],
            'file' => ['type' => 'object'],
            'select' => ['type' => 'string', 'enum' => []],
            'checkbox' => ['type' => 'boolean'],
            'true_false' => ['type' => 'boolean'],
            'radio' => ['type' => 'string'],
            'button_group' => ['type' => 'string', 'enum' => []],
            'post_object' => ['type' => 'integer', 'description' => 'Post ID'],
            'page_link' => ['type' => 'string', 'format' => 'uri'],
            'relationship' => ['type' => 'array'],
            'repeater' => ['type' => 'array'],
            'flexible_content' => ['type' => 'array'],
            'gallery' => ['type' => 'array'],
            'date_picker' => ['type' => 'string', 'format' => 'date-time'],
            'date_time_picker' => ['type' => 'string', 'format' => 'date-time'],
            'time_picker' => ['type' => 'string', 'format' => 'duration'],
            'color_picker' => ['type' => 'string', 'format' => 'hex'],
            'google_map' => ['type' => 'object'],
            'oembed' => ['type' => 'object'],
        ];

        return $type_map[$acf_type] ?? ['type' => 'string'];
    }
}
