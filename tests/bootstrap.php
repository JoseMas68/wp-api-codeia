<?php
/**
 * Bootstrap para tests unitarios
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests;

// Cargar composer autoloader si existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Cargar funciones de testing de WordPress
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Cargar archivos de test de WordPress
require_once $_tests_dir . '/includes/functions.php';

require_once $_tests_dir . '/includes/mock-mailer.php';

require_once $_tests_dir . '/includes/testcase.php';

// Activar el plugin para tests
\WP_UnitTest_Factory::plugin('wp-api-codeia/wp-api-codeia.php');

/**
 * Clase base para tests unitarios
 */
abstract class UnitTestCase extends \WP_UnitTestCase {

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();

        // Asegurar que el plugin esté cargado
        if (!function_exists('wp_api_codeia')) {
            include_once WP_PLUGIN_DIR . '/wp-api-codeia/wp-api-codeia.php';
        }
    }

    /**
     * Cleanup después de cada test
     */
    protected function tearDown(): void {
        parent::tearDown();

        // Limpiar datos de prueba
        wp_api_codeia_clear_all_transients();
    }

    /**
     * Crear un usuario de prueba con API Key
     *
     * @param string $role Rol del usuario
     * @return array Usuario y API Key
     */
    protected function create_user_with_api_key(string $role = 'administrator'): array {
        $user_id = $this->factory->user->create(['role' => $role]);

        // Crear API Key para el usuario
        $repository = new \WP_API_Codeia\Repositories\Auth_Key_Repository();
        $api_key = $repository->create(
            $user_id,
            'Test API Key',
            'read_write',
            0 // No expira
        );

        return [
            'user_id' => $user_id,
            'api_key' => $api_key,
            'user' => get_user_by('id', $user_id),
        ];
    }
}
