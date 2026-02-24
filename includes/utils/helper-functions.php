<?php
/**
 * Helper Functions
 *
 * Funciones de utilidad para uso en todo el plugin.
 *
 * @package WP_API_Codeia
 */

use WP_API_Codeia\Bootstrap;

/**
 * Obtener la instancia principal del plugin
 *
 * @return Bootstrap
 */
function wp_api_codeia(): Bootstrap {
    return Bootstrap::get_instance();
}

/**
 * Obtener el Configuration Manager
 *
 * @return \WP_API_Codeia\Config_Manager
 */
function wp_api_codeia_config(): \WP_API_Codeia\Config_Manager {
    return wp_api_codeia()->get_config_manager();
}

/**
 * Obtener el Detector Manager
 *
 * @return \WP_API_Codeia\Detector_Manager
 */
function wp_api_codeia_detectors(): \WP_API_Codeia\Detector_Manager {
    return wp_api_codeia()->get_detector_manager();
}

/**
 * Obtener el Endpoint Manager
 *
 * @return \WP_API_Codeia\Endpoint_Manager
 */
function wp_api_codeia_endpoints(): \WP_API_Codeia\Endpoint_Manager {
    return wp_api_codeia()->get_endpoint_manager();
}

/**
 * Obtener el Auth Manager
 *
 * @return \WP_API_Codeia\Auth_Manager
 */
function wp_api_codeia_auth(): \WP_API_Codeia\Auth_Manager {
    return wp_api_codeia()->get_auth_manager();
}

/**
 * Obtener el Permission Manager
 *
 * @return \WP_API_Codeia\Permission_Manager
 */
function wp_api_codeia_permissions(): \WP_API_Codeia\Permission_Manager {
    return wp_api_codeia()->get_permission_manager();
}

/**
 * Obtener el Documentation Generator
 *
 * @return \WP_API_Codeia\Documentation_Generator
 */
function wp_api_codeia_docs(): \WP_API_Codeia\Documentation_Generator {
    return wp_api_codeia()->get_docs_generator();
}

/**
 * Obtener el Cache Manager
 *
 * @return \WP_API_Codeia\Cache_Manager
 */
function wp_api_codeia_cache(): \WP_API_Codeia\Cache_Manager {
    return wp_api_codeia()->get_cache_manager();
}

/**
 * Obtener el Request Logger
 *
 * @return \WP_API_Codeia\Request_Logger
 */
function wp_api_codeia_logger(): \WP_API_Codeia\Request_Logger {
    return wp_api_codeia()->get_request_logger();
}

/**
 * Limpiar todos los transients del plugin
 *
 * @return void
 */
function wp_api_codeia_clear_all_transients(): void {
    global $wpdb;

    $prefix = 'wp_api_codeia_';

    // Obtener todos los transients del plugin
    $transients = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $prefix . '%'
        )
    );

    // Eliminar cada transient
    foreach ($transients as $transient) {
        $name = str_replace('_transient_', '', $transient);
        delete_transient($name);
    }
}

/**
 * Obtener la URL de la API
 *
 * @param string $version   Versión de la API
 * @param string $endpoint  Slug del endpoint
 * @param string $item_id   ID del item (opcional)
 * @return string URL completa
 */
function wp_api_codeia_get_api_url(string $version = 'v1', string $endpoint = '', string $item_id = ''): string {
    $url = home_url(WP_API_CODEIA_BASE_PATH . '/' . $version);

    if (!empty($endpoint)) {
        $url .= '/' . $endpoint;

        if (!empty($item_id)) {
            $url .= '/' . $item_id;
        }
    }

    return $url;
}

/**
 * Sanitizar string para usar como slug
 *
 * @param string $string String a sanitizar
 * @return string Slug sanitizado
 */
function wp_api_codeia_sanitize_slug(string $string): string {
    return sanitize_title($string);
}

/**
 * Validar versión de la API
 *
 * @param string $version Versión a validar
 * @return bool True si es válida
 */
function wp_api_codeia_is_valid_version(string $version): bool {
    return preg_match('/^v\d+$/', $version) === 1;
}

/**
 * Obtener versiones soportadas de la API
 *
 * @return array Lista de versiones
 */
function wp_api_codeia_get_supported_versions(): array {
    $config = wp_api_codeia_config()->get_config();
    return $config['global_settings']['supported_versions'] ?? ['v1'];
}

/**
 * Formatear respuesta JSON
 *
 * @param mixed  $data      Datos a formatear
 * @param int    $status    Código HTTP
 * @param array  $headers   Headers adicionales
 * @return void
 */
function wp_api_codeia_send_json($data, int $status = 200, array $headers = []): void {
    status_header($status);

    // Headers por defecto
    $default_headers = [
        'Content-Type' => 'application/json; charset=' . get_option('blog_charset'),
    ];

    // Combinar headers
    $all_headers = array_merge($default_headers, $headers);

    // Enviar headers
    foreach ($all_headers as $name => $value) {
        header($name . ': ' . $value);
    }

    // Enviar respuesta
    echo wp_json_encode($data);
    exit;
}

/**
 * Obtener la IP del cliente
 *
 * @return string IP del cliente
 */
function wp_api_codeia_get_client_ip(): string {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return sanitize_text_field($ip);
}

/**
 * Generar un string aleatorio
 *
 * @param int $length Longitud del string
 * @return string String aleatorio
 */
function wp_api_codeia_random_string(int $length = 32): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $string = '';

    for ($i = 0; $i < $length; $i++) {
        $string .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $string;
}

/**
 * Verificar si estamos en un entorno de desarrollo
 *
 * @return bool True si es desarrollo
 */
function wp_api_codeia_is_dev(): bool {
    return defined('WP_DEBUG') && WP_DEBUG === true;
}

/**
 * Log para debugging (solo en desarrollo)
 *
 * @param mixed $data Datos a logear
 * @return void
 */
function wp_api_codeia_debug_log($data): void {
    if (!wp_api_codeia_is_dev()) {
        return;
    }

    if (is_scalar($data)) {
        error_log('[WP API Codeia] ' . $data);
    } else {
        error_log('[WP API Codeia] ' . wp_json_encode($data));
    }
}

/**
 * Obtener todos los roles de WordPress
 *
 * @return array Roles disponibles
 */
function wp_api_codeia_get_roles(): array {
    $roles = wp_roles()->roles;
    $formatted = [];

    foreach ($roles as $role_slug => $role_data) {
        $formatted[$role_slug] = [
            'name' => $role_data['name'],
            'capabilities' => $role_data['capabilities'],
        ];
    }

    return $formatted;
}

/**
 * Verificar si un plugin está activo
 *
 * @param string $plugin_path Ruta del plugin (ej: 'advanced-custom-fields/acf.php')
 * @return bool True si está activo
 */
function wp_api_codeia_is_plugin_active(string $plugin_path): bool {
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active($plugin_path);
}

/**
 * Obtener información de depuración
 *
 * @return array Información de depuración
 */
function wp_api_codeia_get_debug_info(): array {
    global $wpdb;

    return [
        'plugin_version' => WP_API_CODEIA_VERSION,
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'active_plugins' => get_option('active_plugins', []),
        'active_theme' => get_option('stylesheet'),
        'db_version' => $wpdb->db_version(),
        'memory_limit' => WP_MEMORY_LIMIT,
        'max_execution_time' => ini_get('max_execution_time'),
        'debug_mode' => wp_api_codeia_is_dev(),
        'multisite' => is_multisite(),
        'locale' => get_locale(),
    ];
}
