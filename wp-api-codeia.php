<?php
/**
 * Plugin Name: WP API Codeia
 * Plugin URI: https://github.com/tu-usuario/wp-api-codeia
 * Description: Un plugin de WordPress para [descripción breve].
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tu-sitio-web.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-api-codeia
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Si se accede directamente a este archivo, abortar.
if (!defined('WPINC')) {
    die;
}

// Definir la versión del plugin
define('WP_API_CODEIA_VERSION', '1.0.0');

// Definir la ruta del plugin
define('WP_API_CODEIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_API_CODEIA_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Código principal del plugin
 */

// Activación del plugin
register_activation_hook(__FILE__, 'wp_api_codeia_activate');
function wp_api_codeia_activate() {
    // Acciones de activación
    // Por ejemplo: crear tablas en la base de datos, configurar opciones, etc.
}

// Desactivación del plugin
register_deactivation_hook(__FILE__, 'wp_api_codeia_deactivate');
function wp_api_codeia_deactivate() {
    // Acciones de desactivación
    // Por ejemplo: limpiar datos temporales, etc.
}

// Inicialización del plugin
add_action('plugins_loaded', 'wp_api_codeia_init');
function wp_api_codeia_init() {
    // Inicializar componentes del plugin
}
