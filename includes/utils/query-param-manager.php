<?php
/**
 * Query Param Manager
 *
 * Gestiona parámetros de consulta customizables para la API.
 *
 * @package WP_API_Codeia\Utils
 */

namespace WP_API_Codeia\Utils;

/**
 * Class Query_Param_Manager
 *
 * @package WP_API_Codeia\Utils
 */
class Query_Param_Manager {

    /**
     * Parámetros registrados
     *
     * @var array
     */
    private array $registered_params = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->register_default_params();
    }

    /**
     * Registrar parámetros por defecto
     *
     * @return void
     */
    private function register_default_params(): void {
        // Parámetros de paginación
        $this->register_param('page', [
            'type' => 'integer',
            'default' => 1,
            'minimum' => 1,
            'description' => 'Página de resultados a obtener',
        ]);

        $this->register_param('per_page', [
            'type' => 'integer',
            'default' => 10,
            'minimum' => 1,
            'maximum' => 100,
            'description' => 'Cantidad de resultados por página',
        ]);

        $this->register_param('offset', [
            'type' => 'integer',
            'default' => 0,
            'minimum' => 0,
            'description' => 'Cantidad de resultados a omitir',
        ]);

        // Parámetros de ordenamiento
        $this->register_param('order', [
            'type' => 'string',
            'default' => 'DESC',
            'enum' => ['ASC', 'DESC'],
            'description' => 'Orden de los resultados',
        ]);

        $this->register_param('orderby', [
            'type' => 'string',
            'default' => 'date',
            'enum' => ['date', 'title', 'name', 'modified', 'author', 'id', 'rand', 'comment_count', 'relevance'],
            'description' => 'Campo por el cual ordenar',
        ]);

        // Parámetros de filtrado
        $this->register_param('search', [
            'type' => 'string',
            'description' => 'Término de búsqueda',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        $this->register_param('status', [
            'type' => 'string',
            'default' => 'publish',
            'enum' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
            'description' => 'Estado del post',
        ]);

        $this->register_param('author', [
            'type' => 'integer',
            'description' => 'ID del autor',
        ]);

        $this->register_param('author_exclude', [
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'description' => 'IDs de autores a excluir',
        ]);

        $this->register_param('before', [
            'type' => 'string',
            'format' => 'date-time',
            'description' => 'Fecha límite superior (ISO 8601)',
        ]);

        $this->register_param('after', [
            'type' => 'string',
            'format' => 'date-time',
            'description' => 'Fecha límite inferior (ISO 8601)',
        ]);

        $this->register_param('exclude', [
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'description' => 'IDs de posts a excluir',
        ]);

        $this->register_param('include', [
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'description' => 'IDs de posts a incluir',
        ]);

        $this->register_param('parent', [
            'type' => ['integer', 'array'],
            'description' => 'ID del post padre o array de IDs',
        ]);

        // Parámetros de taxonomía
        $this->register_param('tax_relation', [
            'type' => 'string',
            'enum' => ['AND', 'OR'],
            'description' => 'Relación entre múltiples tax_query',
        ]);

        $this->register_param('categories', [
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'description' => 'IDs de categorías',
        ]);

        $this->register_param('tags', [
            'type' => 'array',
            'items' => ['type' => 'integer'],
            'description' => 'IDs de etiquetas',
        ]);

        // Parámetros de campos
        $this->register_param('fields', [
            'type' => 'string',
            'default' => 'all',
            'enum' => ['all', 'ids', 'list'],
            'description' => 'Campos a retornar',
        ]);

        $this->register_param('_fields', [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'description' => 'Campos específicos a incluir en la respuesta',
        ]);

        // Parámetros de meta
        $this->register_param('meta_key', [
            'type' => 'string',
            'description' => 'Clave de campo personalizado',
        ]);

        $this->register_param('meta_value', [
            'type' => 'string',
            'description' => 'Valor de campo personalizado',
        ]);

        $this->register_param('meta_compare', [
            'type' => 'string',
            'default' => '=',
            'enum' => ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS'],
            'description' => 'Operador de comparación para meta',
        ]);

        // Parámetros de password
        $this->register_param('password', [
            'type' => 'string',
            'description' => 'Contraseña del post para contenido protegido',
        ]);
    }

    /**
     * Registrar un parámetro customizado
     *
     * @param string $name Nombre del parámetro
     * @param array  $args Argumentos del parámetro
     * @return bool True si se registró correctamente
     */
    public function register_param(string $name, array $args): bool {
        $defaults = [
            'type' => 'string',
            'default' => null,
            'required' => false,
            'enum' => null,
            'description' => '',
            'sanitize_callback' => null,
            'validate_callback' => null,
            'minimum' => null,
            'maximum' => null,
            'items' => null,
            'format' => null,
        ];

        $this->registered_params[$name] = array_merge($defaults, $args);

        return true;
    }

    /**
     * Remover un parámetro
     *
     * @param string $name Nombre del parámetro
     * @return bool True si se removió correctamente
     */
    public function unregister_param(string $name): bool {
        if (isset($this->registered_params[$name])) {
            unset($this->registered_params[$name]);
            return true;
        }

        return false;
    }

    /**
     * Obtener todos los parámetros registrados
     *
     * @return array Parámetros registrados
     */
    public function get_registered_params(): array {
        return $this->registered_params;
    }

    /**
     * Obtener un parámetro específico
     *
     * @param string $name Nombre del parámetro
     * @return array|null Configuración del parámetro o null
     */
    public function get_param(string $name): ?array {
        return $this->registered_params[$name] ?? null;
    }

    /**
     * Validar y sanitizar parámetros de consulta
     *
     * @param array $params Parámetros a validar
     * @return array|WP_Error Parámetros validados o error
     */
    public function validate_and_sanitize_params(array $params) {
        $validated = [];
        $errors = [];

        foreach ($params as $key => $value) {
            $param_config = $this->get_param($key);

            if (!$param_config) {
                // Parámetro no registrado, ignorar
                continue;
            }

            // Validar
            $validation = $this->validate_param($key, $value, $param_config);

            if (is_wp_error($validation)) {
                $errors[$key] = $validation->get_error_message();
                continue;
            }

            // Sanitizar
            $sanitized = $this->sanitize_param($key, $value, $param_config);
            $validated[$key] = $sanitized;
        }

        // Aplicar defaults
        foreach ($this->registered_params as $key => $config) {
            if (!isset($validated[$key]) && isset($config['default'])) {
                $validated[$key] = $config['default'];
            }
        }

        if (!empty($errors)) {
            return new \WP_Error('invalid_params', 'Invalid parameters', ['errors' => $errors]);
        }

        return $validated;
    }

    /**
     * Validar un parámetro
     *
     * @param string $key Nombre del parámetro
     * @param mixed  $value Valor a validar
     * @param array  $config Configuración del parámetro
     * @return true|WP_Error True si es válido o error
     */
    private function validate_param(string $key, $value, array $config) {
        // Validar callback custom
        if (isset($config['validate_callback']) && is_callable($config['validate_callback'])) {
            $result = call_user_func($config['validate_callback'], $value, $key);

            if (is_wp_error($result)) {
                return $result;
            }
        }

        // Validar required
        if ($config['required'] && $value === null) {
            return new \WP_Error('missing_param', sprintf('Parameter %s is required', $key));
        }

        // Validar tipo
        if ($value !== null) {
            $type_validation = $this->validate_type($value, $config);
            if (is_wp_error($type_validation)) {
                return $type_validation;
            }
        }

        // Validar enum
        if (isset($config['enum']) && $value !== null) {
            $values = is_array($value) ? $value : [$value];
            foreach ($values as $v) {
                if (!in_array($v, $config['enum'], true)) {
                    return new \WP_Error(
                        'invalid_enum',
                        sprintf('Parameter %s must be one of: %s', $key, implode(', ', $config['enum']))
                    );
                }
            }
        }

        // Validar mínimo/máximo para números
        if (isset($config['minimum']) && is_numeric($value) && $value < $config['minimum']) {
            return new \WP_Error(
                'value_too_small',
                sprintf('Parameter %s must be at least %s', $key, $config['minimum'])
            );
        }

        if (isset($config['maximum']) && is_numeric($value) && $value > $config['maximum']) {
            return new \WP_Error(
                'value_too_large',
                sprintf('Parameter %s must be at most %s', $key, $config['maximum'])
            );
        }

        return true;
    }

    /**
     * Validar tipo de dato
     *
     * @param mixed $value Valor a validar
     * @param array $config Configuración
     * @return true|WP_Error True si es válido o error
     */
    private function validate_type($value, array $config) {
        $type = $config['type'];

        // Tipo múltiple
        if (is_array($type)) {
            $valid = false;
            foreach ($type as $t) {
                $config['type'] = $t;
                if ($this->validate_type($value, $config) === true) {
                    $valid = true;
                    break;
                }
            }
            $config['type'] = $type; // Restaurar

            return $valid ? true : new \WP_Error('invalid_type', 'Invalid data type');
        }

        switch ($type) {
            case 'integer':
                if (!is_numeric($value) || (int) $value != $value) {
                    return new \WP_Error('invalid_integer', 'Value must be an integer');
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    return new \WP_Error('invalid_number', 'Value must be a number');
                }
                break;

            case 'boolean':
                if (!is_bool($value) && !in_array(strtolower($value), ['true', 'false', '1', '0'], true)) {
                    return new \WP_Error('invalid_boolean', 'Value must be a boolean');
                }
                break;

            case 'array':
                if (!is_array($value)) {
                    return new \WP_Error('invalid_array', 'Value must be an array');
                }

                // Validar items
                if (isset($config['items'])) {
                    foreach ($value as $item) {
                        $item_validation = $this->validate_type($item, $config['items']);
                        if (is_wp_error($item_validation)) {
                            return $item_validation;
                        }
                    }
                }
                break;

            case 'string':
                if (!is_string($value) && !is_numeric($value)) {
                    return new \WP_Error('invalid_string', 'Value must be a string');
                }
                break;
        }

        return true;
    }

    /**
     * Sanitizar un parámetro
     *
     * @param string $key Nombre del parámetro
     * @param mixed  $value Valor a sanitizar
     * @param array  $config Configuración del parámetro
     * @return mixed Valor sanitizado
     */
    private function sanitize_param(string $key, $value, array $config) {
        if ($value === null) {
            return null;
        }

        // Sanitize callback custom
        if (isset($config['sanitize_callback']) && is_callable($config['sanitize_callback'])) {
            return call_user_func($config['sanitize_callback'], $value, $key);
        }

        // Sanitize por tipo
        $type = is_array($config['type']) ? $config['type'][0] : $config['type'];

        switch ($type) {
            case 'integer':
                return (int) $value;

            case 'number':
                return is_numeric($value) ? floatval($value) : 0;

            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'array':
                if (!is_array($value)) {
                    $value = explode(',', $value);
                }
                return array_map('sanitize_text_field', $value);

            case 'string':
                return sanitize_text_field($value);

            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Construir tax_query desde parámetros
     *
     * @param array $params Parámetros validados
     * @return array Tax query
     */
    public function build_tax_query(array $params): array {
        $tax_query = ['relation' => $params['tax_relation'] ?? 'AND'];

        if (isset($params['categories']) && !empty($params['categories'])) {
            $tax_query[] = [
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $params['categories'],
            ];
        }

        if (isset($params['tags']) && !empty($params['tags'])) {
            $tax_query[] = [
                'taxonomy' => 'post_tag',
                'field' => 'term_id',
                'terms' => $params['tags'],
            ];
        }

        // Buscar parámetros de taxonomía custom
        foreach ($params as $key => $value) {
            if (strpos($key, 'tax_') === 0 && !empty($value)) {
                $taxonomy = substr($key, 4);
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => is_array($value) ? $value : [$value],
                ];
            }
        }

        return count($tax_query) > 1 ? $tax_query : [];
    }

    /**
     * Construir meta_query desde parámetros
     *
     * @param array $params Parámetros validados
     * @return array Meta query
     */
    public function build_meta_query(array $params): array {
        $meta_query = [];

        if (isset($params['meta_key'])) {
            $meta_query = [
                'key' => $params['meta_key'],
                'compare' => $params['meta_compare'] ?? '=',
            ];

            if (isset($params['meta_value'])) {
                $meta_query['value'] = $params['meta_value'];
            }

            if (isset($params['meta_type'])) {
                $meta_query['type'] = $params['meta_type'];
            }
        }

        // Buscar parámetros de meta custom
        foreach ($params as $key => $value) {
            if (strpos($key, 'meta_') === 0 && !in_array($key, ['meta_key', 'meta_value', 'meta_compare', 'meta_type'])) {
                $meta_key = substr($key, 5);
                $meta_query[] = [
                    'key' => $meta_key,
                    'value' => $value,
                    'compare' => '=',
                ];
            }
        }

        return $meta_query;
    }

    /**
     * Construir argumentos de WP_Query desde parámetros
     *
     * @param array $params Parámetros validados
     * @return array Argumentos de WP_Query
     */
    public function build_wp_query_args(array $params): array {
        $args = [
            'post_type' => $params['post_type'] ?? 'post',
            'post_status' => $params['status'] ?? 'publish',
            'paged' => $params['page'] ?? 1,
            'posts_per_page' => $params['per_page'] ?? 10,
            'offset' => $params['offset'] ?? 0,
            'order' => $params['order'] ?? 'DESC',
            'orderby' => $params['orderby'] ?? 'date',
        ];

        // Búsqueda
        if (isset($params['search'])) {
            $args['s'] = $params['search'];
        }

        // Autor
        if (isset($params['author'])) {
            $args['author'] = $params['author'];
        }

        if (isset($params['author_exclude'])) {
            $args['author__not_in'] = $params['author_exclude'];
        }

        // Fechas
        if (isset($params['before'])) {
            $args['date_query'][] = [
                'before' => $params['before'],
                'inclusive' => true,
            ];
        }

        if (isset($params['after'])) {
            $args['date_query'][] = [
                'after' => $params['after'],
                'inclusive' => true,
            ];
        }

        // Include/Exclude
        if (isset($params['exclude'])) {
            $args['post__not_in'] = $params['exclude'];
        }

        if (isset($params['include'])) {
            $args['post__in'] = $params['include'];
        }

        // Parent
        if (isset($params['parent'])) {
            $args['post_parent'] = $params['parent'];
        }

        // Tax query
        $tax_query = $this->build_tax_query($params);
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Meta query
        $meta_query = $this->build_meta_query($params);
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }
}
