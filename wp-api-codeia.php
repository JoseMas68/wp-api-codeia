<?php
/**
 * Plugin Name: WP API Codeia
 * Plugin URI: https://github.com/wp-api-codeia/wp-api-codeia
 * Description: Transforma WordPress en una API headless configurable, versionada y gobernable desde el admin.
 * Version: 1.0.0
 * Author: WP API Codeia Team
 * Author URI: https://wp-api-codeia.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-api-codeia
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.2
 *
 * @package WP_API_Codeia
 * @version 1.0.0
 */

// Si se accede directamente a este archivo, abortar.
if (!defined('WPINC')) {
    die;
}

// Definir constantes del plugin
define('WP_API_CODEIA_VERSION', '1.0.0');
define('WP_API_CODEIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_API_CODEIA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_API_CODEIA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WP_API_CODEIA_NAMESPACE', 'wp-api-codeia');
define('WP_API_CODEIA_BASE_PATH', '/api');

// Rutas de carpetas principales
define('WP_API_CODEIA_INCLUDES_DIR', WP_API_CODEIA_PLUGIN_DIR . 'includes/');
define('WP_API_CODEIA_ADMIN_DIR', WP_API_CODEIA_PLUGIN_DIR . 'admin/');
define('WP_API_CODEIA_TEMPLATES_DIR', WP_API_CODEIA_PLUGIN_DIR . 'templates/');
define('WP_API_CODEIA_ASSETS_URL', WP_API_CODEIA_PLUGIN_URL . 'assets/');

/**
 * Autoloader de clases del plugin
 *
 * @param string $class Nombre de la clase a cargar
 * @return void
 */
function wp_api_codeia_autoloader($class) {
    // Prefijo del namespace del plugin
    $prefix = 'WP_API_Codeia\\';

    // Verificar si la clase usa nuestro namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);

    // Mapeo de namespaces a subcarpetas específicas
    $namespace_map = [
        'Auth\\' => 'auth/',
        'Repositories\\' => 'repositories/',
        'Detectors\\' => 'detectors/',
        'Endpoints\\' => 'endpoints/',
        'Permissions\\' => 'permissions/',
        'Middleware\\' => 'middleware/',
        'Utils\\' => 'utils/',
        'Performance\\' => 'performance/',
    ];

    // Convertir namespace a ruta de archivo
    $file_path = str_replace('\\', '/', $relative_class);
    $file_path_lower = strtolower($file_path);

    // Verificar si coincide con algún namespace específico
    foreach ($namespace_map as $namespace => $subfolder) {
        $namespace_prefix = str_replace('\\', '/', $namespace);
        if (strpos($file_path_lower, $namespace_prefix) === 0) {
            // Extraer el nombre de la clase
            $class_name = substr($file_path, strlen($namespace_prefix));
            // Convertir a formato archivo (Class_Name -> class-name)
            $class_file = strtolower(str_replace('_', '-', $class_name));
            $file = WP_API_CODEIA_INCLUDES_DIR . $subfolder . $class_file . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    // Fallback al método original (para clases en raíz de includes/)
    $file = WP_API_CODEIA_INCLUDES_DIR . $file_path_lower . '.php';

    // Si el archivo existe, requerirlo
    if (file_exists($file)) {
        require_once $file;
    }
}

// Registrar autoloader
spl_autoload_register('wp_api_codeia_autoloader');

/**
 * Cargar el plugin
 *
 * @return void
 */
function wp_api_codeia_load() {
    // Cargar funciones helper
    require_once WP_API_CODEIA_INCLUDES_DIR . 'utils/helper-functions.php';

    // Inicializar el plugin principal
    $plugin = \WP_API_Codeia\Bootstrap::get_instance();
    $plugin->init();
}

// Iniciar el plugin
wp_api_codeia_load();

/**
 * Activación del plugin
 *
 * @return void
 */
function wp_api_codeia_activate() {
    // Crear tablas personalizadas
    \WP_API_Codeia\Install::create_tables();

    // Establecer configuración por defecto
    \WP_API_Codeia\Install::set_default_options();

    // Limpiar rewrite rules
    flush_rewrite_rules();

    // Crear API key por defecto para administradores
    $admin_users = get_users(['role' => 'administrator']);
    if (!empty($admin_users)) {
        foreach ($admin_users as $admin) {
            \WP_API_Codeia\Repositories\Auth_Key_Repository::create_default_key($admin->ID);
        }
    }
}
register_activation_hook(__FILE__, 'wp_api_codeia_activate');

/**
 * Desactivación del plugin
 *
 * @return void
 */
function wp_api_codeia_deactivate() {
    // Limpiar rewrite rules
    flush_rewrite_rules();

    // Limpiar transients del plugin
    wp_api_codeia_clear_all_transients();
}
register_deactivation_hook(__FILE__, 'wp_api_codeia_deactivate');

/**
 * Desinstalación del plugin
 *
 * @return void
 */
function wp_api_codeia_uninstall() {
    // Eliminar tablas personalizadas
    \WP_API_Codeia\Install::drop_tables();

    // Eliminar opciones del plugin
    \WP_API_Codeia\Install::delete_options();
}
register_uninstall_hook(__FILE__, 'wp_api_codeia_uninstall');
