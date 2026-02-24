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

/**
 * Obtener el Field Permission Manager
 *
 * @return \WP_API_Codeia\Permissions\Field_Permission_Manager
 */
function wp_api_codeia_field_permissions(): \WP_API_Codeia\Permissions\Field_Permission_Manager {
    return new \WP_API_Codeia\Permissions\Field_Permission_Manager();
}

/**
 * Obtener el Rate Limiter
 *
 * @return \WP_API_Codeia\Middleware\Rate_Limiter
 */
function wp_api_codeia_rate_limiter(): \WP_API_Codeia\Middleware\Rate_Limiter {
    return new \WP_API_Codeia\Middleware\Rate_Limiter();
}

/**
 * Obtener el CORS Manager
 *
 * @return \WP_API_Codeia\Middleware\CORS_Manager
 */
function wp_api_codeia_cors(): \WP_API_Codeia\Middleware\CORS_Manager {
    return new \WP_API_Codeia\Middleware\CORS_Manager();
}

/**
 * Obtener el Media Handler
 *
 * @return \WP_API_Codeia\Utils\Media_Handler
 */
function wp_api_codeia_media(): \WP_API_Codeia\Utils\Media_Handler {
    return new \WP_API_Codeia\Utils\Media_Handler();
}

/**
 * Obtener el Query Param Manager
 *
 * @return \WP_API_Codeia\Utils\Query_Param_Manager
 */
function wp_api_codeia_query_params(): \WP_API_Codeia\Utils\Query_Param_Manager {
    return new \WP_API_Codeia\Utils\Query_Param_Manager();
}

/**
 * Verificar si ACF está activo
 *
 * @return bool
 */
function wp_api_codeia_is_acf_active(): bool {
    return wp_api_codeia_is_plugin_active('advanced-custom-fields/acf.php') ||
           class_exists('ACF');
}

/**
 * Verificar si JetEngine está activo
 *
 * @return bool
 */
function wp_api_codeia_is_jetengine_active(): bool {
    return wp_api_codeia_is_plugin_active('jet-engine/jet-engine.php') ||
           defined('JET_ENGINE_VERSION');
}

/**
 * Generar string aleatorio
 *
 * @param int $length Longitud del string
 * @return string String aleatorio
 */
function wp_api_codeia_random_string(int $length = 16): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';

    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $random_string;
}

/**
 * Sanitizar respuesta de API
 *
 * @param mixed $data Datos a sanitizar
 * @return mixed Datos sanitizados
 */
function wp_api_codeia_sanitize_api_response($data) {
    if (is_array($data)) {
        return array_map('wp_api_codeia_sanitize_api_response', $data);
    } elseif (is_object($data)) {
        return array_map('wp_api_codeia_sanitize_api_response', get_object_vars($data));
    } elseif (is_string($data)) {
        return sanitize_text_field($data);
    }

    return $data;
}

/**
 * Formatear respuesta de error para API
 *
 * @param string|\WP_Error $error Error o mensaje
 * @param int              $status Código HTTP
 * @return array Respuesta formateada
 */
function wp_api_codeia_format_error($error, int $status = 400): array {
    if (is_wp_error($error)) {
        return [
            'success' => false,
            'code' => $error->get_error_code(),
            'message' => $error->get_error_message(),
            'data' => $error->get_error_data(),
        ];
    }

    return [
        'success' => false,
        'code' => 'error',
        'message' => $error,
        'data' => ['status' => $status],
    ];
}

/**
 * Obtener configuración de JWT
 *
 * @return array Configuración de JWT
 */
function wp_api_codeia_get_jwt_config(): array {
    return [
        'secret_key' => get_option('wp_api_codeia_jwt_secret'),
        'token_lifetime' => get_option('wp_api_codeia_jwt_token_lifetime', 3600),
        'refresh_lifetime' => get_option('wp_api_codeia_jwt_refresh_lifetime', 2592000),
    ];
}

/**
 * Verificar si un endpoint requiere autenticación
 *
 * @param string $endpoint Slug del endpoint
 * @param string $version   Versión de la API
 * @return bool True si requiere autenticación
 */
function wp_api_codeia_endpoint_requires_auth(string $endpoint, string $version = 'v1'): bool {
    $config = wp_api_codeia_config()->get_endpoint_config($endpoint, $version);

    if ($config === null) {
        return true; // Por defecto, requerir auth
    }

    return ($config['auth'] ?? 'api_key') !== 'none';
}

/**
 * Obtener scope máximo de un usuario
 *
 * @param int $user_id ID del usuario
 * @return string Scope máximo (read, write, read_write, admin)
 */
function wp_api_codeia_get_user_max_scope(int $user_id): string {
    $user = get_userdata($user_id);

    if (!$user) {
        return 'read';
    }

    // Admin tiene scope admin
    if (user_can($user, 'manage_options')) {
        return 'admin';
    }

    // Editores tienen read_write
    if (user_can($user, 'edit_posts')) {
        return 'read_write';
    }

    // Colaboradores tienen write
    if (user_can($user, 'edit_posts')) {
        return 'write';
    }

    // Suscriptores solo tienen read
    return 'read';
}

/**
 * Verificar si un request es una petición de API
 *
 * @return bool True si es petición de API
 */
function wp_api_codeia_is_api_request(): bool {
    $namespace = trim(WP_API_CODEIA_NAMESPACE, '/');

    return isset($_SERVER['REQUEST_URI']) &&
           strpos($_SERVER['REQUEST_URI'], '/' . $namespace . '/') !== false;
}

/**
 * Obtener IP del cliente
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
 * Validar formato de email
 *
 * @param string $email Email a validar
 * @return bool True si es válido
 */
function wp_api_codeia_validate_email(string $email): bool {
    return is_email($email) !== false;
}

/**
 * Truncar texto
 *
 * @param string $text   Texto a truncar
 * @param int    $length Longitud máxima
 * @param string $suffix Sufijo a agregar
 * @return string Texto truncado
 */
function wp_api_codeia_truncate(string $text, int $length, string $suffix = '...'): string {
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}
