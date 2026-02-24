<?php
/**
 * Field Permission Manager
 *
 * Gestiona permisos a nivel de campo para la API.
 *
 * @package WP_API_Codeia\Permissions
 */

namespace WP_API_Codeia\Permissions;

/**
 * Class Field_Permission_Manager
 *
 * @package WP_API_Codeia\Permissions
 */
class Field_Permission_Manager {

    /**
     * Cache key para permisos de campo
     *
     * @var string
     */
    private string $cache_key = 'wp_api_codeia_field_permissions';

    /**
     * Obtener configuración de permisos de campo
     *
     * @return array Configuración de permisos
     */
    public function get_config(): array {
        $cached = get_transient($this->cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $config = get_option('wp_api_codeia_field_permissions', $this->get_default_config());

        // Cache por 1 hora
        set_transient($this->cache_key, $config, HOUR_IN_SECONDS);

        return $config;
    }

    /**
     * Guardar configuración de permisos de campo
     *
     * @param array $config Configuración
     * @return bool True si se guardó correctamente
     */
    public function save_config(array $config): bool {
        $result = update_option('wp_api_codeia_field_permissions', $config);

        // Invalidar cache
        $this->invalidate_cache();

        return $result;
    }

    /**
     * Verificar si un campo está permitido para un rol
     *
     * @param string $field_name Nombre del campo
     * @param string $post_type Post type
     * @param string $role Rol del usuario
     * @param string $scope Scope de la API key (read, write, read_write, admin)
     * @return bool True si está permitido
     */
    public function is_field_allowed(string $field_name, string $post_type, string $role, string $scope): bool {
        $config = $this->get_config();

        // Campos nativos siempre permitidos por defecto
        $native_fields = ['ID', 'post_author', 'post_date', 'post_content', 'post_title',
                          'post_excerpt', 'post_status', 'comment_status', 'ping_status',
                          'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified',
                          'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid',
                          'menu_order', 'post_type', 'post_mime_type', 'comment_count', 'filter'];

        if (in_array($field_name, $native_fields, true)) {
            return true;
        }

        // Verificar permisos específicos del post type
        if (isset($config['post_types'][$post_type])) {
            $post_type_config = $config['post_types'][$post_type];

            // Verificar reglas de deny (tienen prioridad)
            if (isset($post_type_config['deny'][$role]) &&
                in_array($field_name, $post_type_config['deny'][$role], true)) {
                return false;
            }

            if (isset($post_type_config['deny_by_scope'][$scope]) &&
                in_array($field_name, $post_type_config['deny_by_scope'][$scope], true)) {
                return false;
            }

            // Verificar reglas de allow
            if (isset($post_type_config['allow'][$role])) {
                return in_array($field_name, $post_type_config['allow'][$role], true);
            }

            if (isset($post_type_config['allow_by_scope'][$scope])) {
                return in_array($field_name, $post_type_config['allow_by_scope'][$scope], true);
            }
        }

        // Verificar reglas globales
        if (isset($config['global']['deny'][$role]) &&
            in_array($field_name, $config['global']['deny'][$role], true)) {
            return false;
        }

        if (isset($config['global']['deny_by_scope'][$scope]) &&
            in_array($field_name, $config['global']['deny_by_scope'][$scope], true)) {
            return false;
        }

        // Por defecto, permitir campos no restringidos
        return true;
    }

    /**
     * Filtrar campos según permisos
     *
     * @param array  $fields Campos a filtrar
     * @param string $post_type Post type
     * @param string $role Rol del usuario
     * @param string $scope Scope de la API key
     * @return array Campos filtrados
     */
    public function filter_fields(array $fields, string $post_type, string $role, string $scope): array {
        $allowed = [];

        foreach ($fields as $field_name => $field_value) {
            if ($this->is_field_allowed($field_name, $post_type, $role, $scope)) {
                $allowed[$field_name] = $field_value;
            }
        }

        return $allowed;
    }

    /**
     * Obtener campos permitidos para un rol y post type
     *
     * @param string $post_type Post type
     * @param string $role Rol del usuario
     * @param string $scope Scope de la API key
     * @return array Lista de campos permitidos
     */
    public function get_allowed_fields(string $post_type, string $role, string $scope): array {
        $config = $this->get_config();
        $detector_manager = wp_api_codeia_detectors();

        // Obtener todos los campos disponibles
        $all_fields = $detector_manager->get_all_custom_fields($post_type);
        $allowed = [];

        // Campos nativos
        $native_fields = ['ID', 'post_author', 'post_date', 'post_content', 'post_title',
                          'post_excerpt', 'post_status', 'comment_status', 'ping_status',
                          'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified',
                          'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid',
                          'menu_order', 'post_type', 'post_mime_type', 'comment_count', 'filter'];

        foreach ($native_fields as $field) {
            if ($this->is_field_allowed($field, $post_type, $role, $scope)) {
                $allowed[$field] = ['type' => 'native', 'source' => 'wordpress'];
            }
        }

        // Campos ACF
        if (isset($all_fields['acf'])) {
            foreach ($all_fields['acf'] as $field_name => $field_config) {
                if ($this->is_field_allowed($field_name, $post_type, $role, $scope)) {
                    $allowed[$field_name] = [
                        'type' => $field_config['type'] ?? 'text',
                        'source' => 'acf',
                        'label' => $field_config['label'] ?? $field_name,
                    ];
                }
            }
        }

        // Campos JetEngine
        if (isset($all_fields['jetengine'])) {
            foreach ($all_fields['jetengine'] as $field_name => $field_config) {
                if ($this->is_field_allowed($field_name, $post_type, $role, $scope)) {
                    $allowed[$field_name] = [
                        'type' => $field_config['type'] ?? 'text',
                        'source' => 'jetengine',
                        'label' => $field_config['label'] ?? $field_name,
                    ];
                }
            }
        }

        // Meta fields nativos
        if (isset($all_fields['native'])) {
            foreach ($all_fields['native'] as $field_name => $field_config) {
                if ($this->is_field_allowed($field_name, $post_type, $role, $scope)) {
                    $allowed[$field_name] = [
                        'type' => $field_config['type'] ?? 'string',
                        'source' => 'meta',
                        'label' => $field_config['description'] ?? $field_name,
                    ];
                }
            }
        }

        return $allowed;
    }

    /**
     * Agregar regla de permiso
     *
     * @param string $post_type Post type (o 'global' para todos)
     * @param string $field_name Nombre del campo
     * @param string $action 'allow' o 'deny'
     * @param string $target_type 'role' o 'scope'
     * @param string $target_value Rol o scope específico
     * @return bool True si se agregó correctamente
     */
    public function add_rule(string $post_type, string $field_name, string $action, string $target_type, string $target_value): bool {
        $config = $this->get_config();

        if ($post_type === 'global') {
            $config_key = $target_type === 'role' ? 'global.' . $action : 'global.' . $action . '_by_scope';
        } else {
            if (!isset($config['post_types'][$post_type])) {
                $config['post_types'][$post_type] = [];
            }
            $config_key = 'post_types.' . $post_type . '.' . $action;
            if ($target_type === 'scope') {
                $config_key .= '_by_scope';
            }
        }

        // Parsear la ruta del array
        $keys = explode('.', $config_key);
        $current = &$config;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        if (!in_array($field_name, $current, true)) {
            $current[] = $field_name;
        }

        return $this->save_config($config);
    }

    /**
     * Remover regla de permiso
     *
     * @param string $post_type Post type (o 'global' para todos)
     * @param string $field_name Nombre del campo
     * @param string $action 'allow' o 'deny'
     * @param string $target_type 'role' o 'scope'
     * @param string $target_value Rol o scope específico
     * @return bool True si se removió correctamente
     */
    public function remove_rule(string $post_type, string $field_name, string $action, string $target_type, string $target_value): bool {
        $config = $this->get_config();

        if ($post_type === 'global') {
            if ($target_type === 'role') {
                if (!isset($config['global'][$action][$target_value])) {
                    return false;
                }
                $index = array_search($field_name, $config['global'][$action][$target_value], true);
                if ($index !== false) {
                    unset($config['global'][$action][$target_value][$index]);
                }
            } else {
                if (!isset($config['global'][$action . '_by_scope'][$target_value])) {
                    return false;
                }
                $index = array_search($field_name, $config['global'][$action . '_by_scope'][$target_value], true);
                if ($index !== false) {
                    unset($config['global'][$action . '_by_scope'][$target_value][$index]);
                }
            }
        } else {
            if (!isset($config['post_types'][$post_type])) {
                return false;
            }

            if ($target_type === 'role') {
                if (!isset($config['post_types'][$post_type][$action][$target_value])) {
                    return false;
                }
                $index = array_search($field_name, $config['post_types'][$post_type][$action][$target_value], true);
                if ($index !== false) {
                    unset($config['post_types'][$post_type][$action][$target_value][$index]);
                }
            } else {
                if (!isset($config['post_types'][$post_type][$action . '_by_scope'][$target_value])) {
                    return false;
                }
                $index = array_search($field_name, $config['post_types'][$post_type][$action . '_by_scope'][$target_value], true);
                if ($index !== false) {
                    unset($config['post_types'][$post_type][$action . '_by_scope'][$target_value][$index]);
                }
            }
        }

        return $this->save_config($config);
    }

    /**
     * Obtener configuración por defecto
     *
     * @return array
     */
    private function get_default_config(): array {
        return [
            'global' => [
                'deny' => [
                    'subscriber' => ['user_pass', 'user_activation_key'],
                ],
                'deny_by_scope' => [
                    'read' => ['post_password', 'user_pass'],
                ],
            ],
            'post_types' => [
                'post' => [
                    'deny' => [],
                    'deny_by_scope' => [],
                    'allow' => [],
                    'allow_by_scope' => [],
                ],
                'page' => [
                    'deny' => [],
                    'deny_by_scope' => [],
                    'allow' => [],
                    'allow_by_scope' => [],
                ],
            ],
        ];
    }

    /**
     * Invalidar cache
     *
     * @return void
     */
    public function invalidate_cache(): void {
        delete_transient($this->cache_key);
    }

    /**
     * Exportar configuración de permisos
     *
     * @return array Configuración exportable
     */
    public function export_config(): array {
        return $this->get_config();
    }

    /**
     * Importar configuración de permisos
     *
     * @param array $config Configuración a importar
     * @return bool True si se importó correctamente
     */
    public function import_config(array $config): bool {
        return $this->save_config($config);
    }

    /**
     * Obtener resumen de permisos para un rol
     *
     * @param string $role Rol
     * @param string $scope Scope
     * @return array Resumen de permisos
     */
    public function get_permissions_summary(string $role, string $scope): array {
        $config = $this->get_config();
        $summary = [
            'global' => [
                'denied' => $config['global']['deny'][$role] ?? [],
                'denied_by_scope' => $config['global']['deny_by_scope'][$scope] ?? [],
            ],
            'post_types' => [],
        ];

        foreach ($config['post_types'] as $post_type => $pt_config) {
            $summary['post_types'][$post_type] = [
                'denied' => $pt_config['deny'][$role] ?? [],
                'denied_by_scope' => $pt_config['deny_by_scope'][$scope] ?? [],
                'allowed' => $pt_config['allow'][$role] ?? [],
                'allowed_by_scope' => $pt_config['allow_by_scope'][$scope] ?? [],
            ];
        }

        return $summary;
    }
}
