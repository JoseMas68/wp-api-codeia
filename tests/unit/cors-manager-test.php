<?php
/**
 * Tests para CORS Manager
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;
use WP_API_Codeia\Middleware\CORS_Manager;

/**
 * Class CORS_Manager_Test
 */
class CORS_Manager_Test extends UnitTestCase {

    /**
     * Test que se obtiene configuración por defecto
     */
    public function test_get_default_config(): void {
        $manager = new CORS_Manager();
        $config = $manager->get_config();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('allow_origins', $config);
        $this->assertArrayHasKey('allow_methods', $config);
        $this->assertArrayHasKey('allow_headers', $config);
        $this->assertTrue($config['enabled']);
        $this->assertContains('*', $config['allow_origins']);
    }

    /**
     * Test que se puede guardar configuración
     */
    public function test_save_config(): void {
        $manager = new CORS_Manager();

        $config = [
            'enabled' => true,
            'allow_origins' => ['https://example.com'],
            'allow_methods' => ['GET', 'POST'],
            'allow_headers' => ['Authorization', 'Content-Type'],
            'expose_headers' => ['X-WP-Total'],
            'max_age' => 3600,
            'allow_credentials' => true,
        ];

        $result = $manager->save_config($config);

        $this->assertTrue($result);

        $saved = $manager->get_config();
        $this->assertEquals(['https://example.com'], $saved['allow_origins']);
    }

    /**
     * Test que se puede agregar origen permitido
     */
    public function test_add_allowed_origin(): void {
        $manager = new CORS_Manager();

        // Limpiar configuración primero
        $manager->save_config([
            'enabled' => true,
            'allow_origins' => [],
            'allow_methods' => ['GET'],
            'allow_headers' => [],
            'expose_headers' => [],
            'max_age' => 0,
            'allow_credentials' => false,
        ]);

        $result = $manager->add_allowed_origin('https://test.com');

        $this->assertTrue($result);

        $config = $manager->get_config();
        $this->assertContains('https://test.com', $config['allow_origins']);
    }

    /**
     * Test que se puede remover origen permitido
     */
    public function test_remove_allowed_origin(): void {
        $manager = new CORS_Manager();

        $manager->save_config([
            'enabled' => true,
            'allow_origins' => ['https://test.com'],
            'allow_methods' => ['GET'],
            'allow_headers' => [],
            'expose_headers' => [],
            'max_age' => 0,
            'allow_credentials' => false,
        ]);

        $result = $manager->remove_allowed_origin('https://test.com');

        $this->assertTrue($result);

        $config = $manager->get_config();
        $this->assertNotContains('https://test.com', $config['allow_origins']);
    }

    /**
     * Test que wildcard permite todo
     */
    public function test_wildcard_allows_all(): void {
        $manager = new CORS_Manager();

        // Configurar con wildcard
        $manager->save_config([
            'enabled' => true,
            'allow_origins' => ['*'],
            'allow_methods' => ['GET'],
            'allow_headers' => [],
            'expose_headers' => [],
            'max_age' => 0,
            'allow_credentials' => false,
        ]);

        $config = $manager->get_config();

        // Verificar que '*' está en la lista
        $this->assertContains('*', $config['allow_origins']);
    }

    /**
     * Test que se obtiene configuración de desarrollo
     */
    public function test_get_dev_config(): void {
        $manager = new CORS_Manager();
        $dev_config = $manager->get_dev_config();

        $this->assertIsArray($dev_config);
        $this->assertArrayHasKey('allow_origins', $dev_config);
        $this->assertContains('http://localhost:3000', $dev_config['allow_origins']);
        $this->assertTrue($dev_config['allow_credentials']);
    }

    /**
     * Test que validate_request_headers retorna array válido
     */
    public function test_validate_request_headers(): void {
        $manager = new CORS_Manager();

        $result = $manager->validate_request_headers();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Test que se pueden configurar múltiples orígenes
     */
    public function test_multiple_origins(): void {
        $manager = new CORS_Manager();

        $config = [
            'enabled' => true,
            'allow_origins' => [
                'https://example.com',
                'https://test.com',
                'https://another.com',
            ],
            'allow_methods' => ['GET'],
            'allow_headers' => [],
            'expose_headers' => [],
            'max_age' => 0,
            'allow_credentials' => false,
        ];

        $manager->save_config($config);
        $saved = $manager->get_config();

        $this->assertCount(3, $saved['allow_origins']);
    }
}
