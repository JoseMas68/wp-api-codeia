<?php
/**
 * Script simple para correr tests sin WordPress test suite
 *
 * Este script permite verificar que el código no tiene errores fatales
 * y que las clases se pueden instanciar correctamente.
 */

// Cargar el plugin
require_once __DIR__ . '/../wp-api-codeia.php';

echo "=== WP API Codeia - Tests Básicos ===\n\n";

$tests_passed = 0;
$tests_failed = 0;

/**
 * Función para ejecutar un test
 */
function run_test($test_name, $test_function) {
    global $tests_passed, $tests_failed;

    try {
        $test_function();
        echo "✓ $test_name\n";
        $tests_passed++;
    } catch (\Exception $e) {
        echo "✗ $test_name: " . $e->getMessage() . "\n";
        $tests_failed++;
    } catch (\Error $e) {
        echo "✗ $test_name: " . $e->getMessage() . "\n";
        $tests_failed++;
    }
}

// Tests de carga de clases
run_test('El plugin se carga correctamente', function() {
    if (!defined('WP_API_CODEIA_VERSION')) {
        throw new Exception('WP_API_CODEIA_VERSION no está definida');
    }
});

run_test('Autoloader está registrado', function() {
    if (!function_exists('spl_autoload_functions')) {
        throw new Exception('spl_autoload_functions no disponible');
    }
    $autoloaders = spl_autoload_functions();
    if (empty($autoloaders)) {
        throw new Exception('No hay autoloaders registrados');
    }
});

// Tests de constantes
run_test('Constants están definidas', function() {
    $required_constants = [
        'WP_API_CODEIA_VERSION',
        'WP_API_CODEIA_PLUGIN_DIR',
        'WP_API_CODEIA_PLUGIN_URL',
        'WP_API_CODEIA_NAMESPACE',
        'WP_API_CODEIA_BASE_PATH',
    ];

    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            throw new Exception("Constante $constant no está definida");
        }
    }
});

// Tests de funciones helper
run_test('Funciones helper existen', function() {
    $required_functions = [
        'wp_api_codeia',
        'wp_api_codeia_config',
        'wp_api_codeia_auth',
        'wp_api_codeia_clear_all_transients',
        'wp_api_codeia_random_string',
        'wp_api_codeia_is_dev',
    ];

    foreach ($required_functions as $function) {
        if (!function_exists($function)) {
            throw new Exception("Función $function no existe");
        }
    }
});

// Tests de clases principales
run_test('Clases principales pueden instanciarse', function() {
    $classes = [
        'WP_API_Codeia\\Bootstrap',
        'WP_API_Codeia\\Config_Manager',
        'WP_API_Codeia\\Detector_Manager',
        'WP_API_Codeia\\Auth_Manager',
        'WP_API_Codeia\\Permission_Manager',
        'WP_API_Codeia\\Endpoint_Manager',
        'WP_API_Codeia\\Documentation_Generator',
        'WP_API_Codeia\\Cache_Manager',
        'WP_API_Codeia\\Request_Logger',
    ];

    foreach ($classes as $class) {
        if (!class_exists($class)) {
            throw new Exception("Clase $class no existe");
        }
    }
});

// Tests de repositorios
run_test('Repositorios pueden instanciarse', function() {
    $repositories = [
        'WP_API_Codeia\\Repositories\\Auth_Key_Repository',
    ];

    foreach ($repositories as $repository) {
        if (!class_exists($repository)) {
            throw new Exception("Repositorio $repository no existe");
        }
    }
});

// Tests de configuración
run_test('Config Manager funciona', function() {
    $config = wp_api_codeia_config();
    if (!method_exists($config, 'get_config')) {
        throw new Exception('Método get_config no existe');
    }

    $version = $config->get('version');
    if (empty($version)) {
        throw new Exception('Versión de configuración está vacía');
    }
});

// Tests de detectores
run_test('Detector Manager funciona', function() {
    $detector = wp_api_codeia_detectors();

    if (!method_exists($detector, 'detect_cpts')) {
        throw new Exception('Método detect_cpts no existe');
    }
});

// Tests de API Request/Response
run_test('API Request y Response existen', function() {
    if (!class_exists('WP_API_Codeia\\API_Request')) {
        throw new Exception('Clase API_Request no existe');
    }
    if (!class_exists('WP_API_Codeia\\API_Response')) {
        throw new Exception('Clase API_Response no existe');
    }

    $request = new \WP_API_Codeia\API_Request('v1', 'test');
    if ($request->get_version() !== 'v1') {
        throw new Exception('API_Request no funciona correctamente');
    }
});

// Tests de seguridad
run_test('API Key hashing funciona', function() {
    $test_key = 'test_key_123';
    $hashed = password_hash($test_key, PASSWORD_DEFAULT);

    if (!password_verify($test_key, $hashed)) {
        throw new Exception('Hash de API Key no funciona');
    }
});

run_test('Random string genera longitudes correctas', function() {
    $length = 32;
    $string = wp_api_codeia_random_string($length);

    if (strlen($string) !== $length) {
        throw new Exception("Random string genera longitud incorrecta: " . strlen($string));
    }
});

// Tests de estructura
run_test('Estructura de carpetas existe', function() {
    $required_dirs = [
        'includes',
        'includes/detectors',
        'includes/endpoints',
        'includes/auth',
        'includes/permissions',
        'admin',
        'tests',
    ];

    foreach ($required_dirs as $dir) {
        if (!is_dir(__DIR__ . '/../' . $dir)) {
            throw new Exception("Directorio $dir no existe");
        }
    }
});

run_test('Archivos principales existen', function() {
    $required_files = [
        'wp-api-codeia.php',
        'includes/bootstrap.php',
        'includes/install.php',
        'includes/utils/config-manager.php',
        'includes/utils/helper-functions.php',
    ];

    foreach ($required_files as $file) {
        if (!file_exists(__DIR__ . '/../' . $file)) {
            throw new Exception("Archivo $file no existe");
        }
    }
});

// Tests de configuración JSON
run_test('Configuración tiene estructura válida', function() {
    $config = wp_api_codeia_config()->get_config();

    $required_keys = ['version', 'global_settings', 'endpoints', 'auth', 'permissions'];

    foreach ($required_keys as $key) {
        if (!isset($config[$key])) {
            throw new Exception("Clave '$key' no existe en configuración");
        }
    }
});

run_test('Endpoints por defecto están configurados', function() {
    $endpoints = wp_api_codeia_config()->get_endpoints();

    if (!isset($endpoints['posts'])) {
        throw new Exception('Endpoint "posts" no está configurado');
    }
    if (!isset($endpoints['pages'])) {
        throw new Exception('Endpoint "pages" no está configurado');
    }

    $posts_config = wp_api_codeia_config()->get_endpoint_config('posts', 'v1');
    if ($posts_config === null) {
        throw new Exception('Configuración de endpoint posts/v1 no existe');
    }
});

// Tests de compatibilidad
run_test('Versión de PHP es compatible', function() {
    if (version_compare(PHP_VERSION, '8.2', '<')) {
        throw new Exception('PHP ' . PHP_VERSION . ' no es compatible, requiere 8.2+');
    }
});

run_test('Extensiones requeridas están disponibles', function() {
    $required_extensions = [
        'json',
        'mysqli',
        'pdo',
    ];

    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            throw new Exception("Extensión $ext no está cargada");
        }
    }
});

echo "\n=== Resumen ===\n";
echo "Tests pasados: $tests_passed\n";
echo "Tests fallidos: $tests_failed\n";
echo "Total tests: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed > 0) {
    echo "\n❌ Algunos tests fallaron\n";
    exit(1);
} else {
    echo "\n✅ Todos los tests pasaron\n";
    exit(0);
}
