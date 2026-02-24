<?php
/**
 * Config Manager
 *
 * Gestiona la configuración del plugin.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

/**
 * Class Config_Manager
 *
 * @package WP_API_Codeia
 */
class Config_Manager {

    /**
     * Configuración del plugin
     *
     * @var array|null
     */
    private ?array $config = null;

    /**
     * Opción de WordPress donde se guarda la config
     *
     * @var string
     */
    private string $option_name = 'wp_api_codeia_config';

    /**
     * Inicializar el config manager
     *
     * @return void
     */
    public function init(): void {
        // Cargar configuración
        $this->load_config();

        // Hook para actualizar configuración
        add_action('wp_api_codeia_config_updated', [$this, 'on_config_updated']);
    }

    /**
     * Cargar configuración desde la base de datos
     *
     * @return void
     */
    private function load_config(): void {
        $saved_config = get_option($this->option_name);

        if ($saved_config === false) {
            // Usar configuración por defecto
            $this->config = Install::get_default_config();
        } else {
            $this->config = $saved_config;
        }
    }

    /**
     * Obtener toda la configuración
     *
     * @return array Configuración completa
     */
    public function get_config(): array {
        return $this->config;
    }

    /**
     * Obtener un valor de configuración por ruta
     *
     * @param string $path Ruta del valor (ej: 'global_settings.namespace')
     * @param mixed  $default Valor por defecto
     * @return mixed Valor encontrado o default
     */
    public function get(string $path, $default = null) {
        $keys = explode('.', $path);
        $value = $this->config;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Establecer un valor de configuración
     *
     * @param string $path  Ruta del valor
     * @param mixed  $value Valor a establecer
     * @return bool True si se guardó correctamente
     */
    public function set(string $path, $value): bool {
        $keys = explode('.', $path);
        $config = &$this->config;

        foreach ($keys as $key) {
            if (!isset($config[$key]) || !is_array($config[$key])) {
                $config[$key] = [];
            }
            $config = &$config[$key];
        }

        $config = $value;

        return $this->save_config();
    }

    /**
     * Guardar configuración en la base de datos
     *
     * @return bool True si se guardó correctamente
     */
    private function save_config(): bool {
        $updated = update_option($this->option_name, $this->config);

        if ($updated) {
            // Actualizar hash de cache
            $this->update_cache_hash();

            // Disparar hook de configuración actualizada
            do_action('wp_api_codeia_config_updated', $this->config);
        }

        return $updated;
    }

    /**
     * Obtener endpoints configurados
     *
     * @return array Endpoints
     */
    public function get_endpoints(): array {
        return $this->get('endpoints', []);
    }

    /**
     * Obtener configuración de un endpoint específico
     *
     * @param string $endpoint_slug Slug del endpoint
     * @param string $version       Versión (default: 'v1')
     * @return array|null Configuración del endpoint o null
     */
    public function get_endpoint_config(string $endpoint_slug, string $version = 'v1'): ?array {
        $endpoints = $this->get_endpoints();

        if (!isset($endpoints[$endpoint_slug])) {
            return null;
        }

        $endpoint = $endpoints[$endpoint_slug];

        if (!isset($endpoint['versions'][$version])) {
            return null;
        }

        return $endpoint['versions'][$version];
    }

    /**
     * Guardar configuración de un endpoint
     *
     * @param string $endpoint_slug Slug del endpoint
     * @param array  $config        Configuración del endpoint
     * @return bool True si se guardó correctamente
     */
    public function save_endpoint_config(string $endpoint_slug, array $config): bool {
        $endpoints = $this->get_endpoints();

        if (!isset($endpoints[$endpoint_slug])) {
            $endpoints[$endpoint_slug] = [
                'slug' => $endpoint_slug,
                'name' => $config['name'] ?? ucfirst($endpoint_slug),
                'description' => $config['description'] ?? '',
                'versions' => [],
            ];
        }

        $version = $config['version'] ?? 'v1';
        $endpoints[$endpoint_slug]['versions'][$version] = $config;

        return $this->set('endpoints', $endpoints);
    }

    /**
     * Eliminar un endpoint
     *
     * @param string $endpoint_slug Slug del endpoint
     * @return bool True si se eliminó correctamente
     */
    public function delete_endpoint(string $endpoint_slug): bool {
        $endpoints = $this->get_endpoints();

        if (!isset($endpoints[$endpoint_slug])) {
            return false;
        }

        unset($endpoints[$endpoint_slug]);

        return $this->set('endpoints', $endpoints);
    }

    /**
     * Obtener configuración de autenticación
     *
     * @param string $method Método de autenticación (api_keys, jwt, etc.)
     * @return array|null Configuración del método o null
     */
    public function get_auth_config(string $method): ?array {
        return $this->get('auth.' . $method);
    }

    /**
     * Obtener permisos configurados
     *
     * @return array Permisos
     */
    public function get_permissions(): array {
        return $this->get('permissions', []);
    }

    /**
     * Obtener permisos por rol
     *
     * @param string $role Slug del rol
     * @return array|null Permisos del rol o null
     */
    public function get_role_permissions(string $role): ?array {
        $roles = $this->get('permissions.roles', []);

        return $roles[$role] ?? null;
    }

    /**
     * Obtener configuración global
     *
     * @return array Configuración global
     */
    public function get_global_settings(): array {
        return $this->get('global_settings', []);
    }

    /**
     * Obtener namespace de la API
     *
     * @return string Namespace
     */
    public function get_namespace(): string {
        return $this->get('global_settings.namespace', WP_API_CODEIA_NAMESPACE);
    }

    /**
     * Obtener versión por defecto de la API
     *
     * @return string Versión (ej: 'v1')
     */
    public function get_default_version(): string {
        return $this->get('global_settings.default_version', 'v1');
    }

    /**
     * Obtener versiones soportadas
     *
     * @return array Lista de versiones
     */
    public function get_supported_versions(): array {
        return $this->get('global_settings.supported_versions', ['v1']);
    }

    /**
     * Verificar si los logs están habilitados
     *
     * @return bool True si están habilitados
     */
    public function is_logging_enabled(): bool {
        return $this->get('global_settings.enable_logs', true);
    }

    /**
     * Actualizar hash de cache
     *
     * @return void
     */
    private function update_cache_hash(): void {
        update_option('wp_api_codeia_cache_hash', wp_generate_password(32, false));
    }

    /**
     * Obtener hash actual de cache
     *
     * @return string Hash de cache
     */
    public function get_cache_hash(): string {
        return get_option('wp_api_codeia_cache_hash', '');
    }

    /**
     * Validar configuración contra esquema JSON
     *
     * @param array $config Configuración a validar
     * @return bool|WP_Error True si válida, WP_Error con detalles si no
     */
    public function validate_config(array $config) {
        // TODO: Implementar validación con JSON Schema
        // Por ahora, validación básica
        $required_keys = ['version', 'global_settings', 'endpoints', 'auth', 'permissions'];

        foreach ($required_keys as $key) {
            if (!isset($config[$key])) {
                return new \WP_Error(
                    'missing_key',
                    sprintf('Falta la clave requerida: %s', $key),
                    ['key' => $key]
                );
            }
        }

        return true;
    }

    /**
     * Importar configuración desde JSON
     *
     * @param string $json JSON con la configuración
     * @return bool|WP_Error True si se importó correctamente
     */
    public function import_config(string $json) {
        $config = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(
                'invalid_json',
                'JSON inválido: ' . json_last_error_msg()
            );
        }

        $validation = $this->validate_config($config);

        if (is_wp_error($validation)) {
            return $validation;
        }

        $this->config = $config;

        return $this->save_config();
    }

    /**
     * Exportar configuración a JSON
     *
     * @return string JSON con la configuración
     */
    public function export_config(): string {
        return wp_json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Al actualizar la configuración
     *
     * @param array $config Nueva configuración
     * @return void
     */
    public function on_config_updated(array $config): void {
        // Limpiar cache
        wp_api_codeia_clear_all_transients();

        // Regenerar rewrite rules
        flush_rewrite_rules();

        // Actualizar documentación
        if ($this->get('documentation.auto_update', true)) {
            wp_api_codeia_docs()->generate();
        }
    }
}
