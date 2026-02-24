<?php
/**
 * Tests para Config Manager
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;

/**
 * Class Config_Manager_Test
 */
class Config_Manager_Test extends UnitTestCase {

    /**
     * Test que se puede obtener la configuración
     */
    public function test_get_config_returns_array(): void {
        $config = wp_api_codeia_config()->get_config();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('global_settings', $config);
    }

    /**
     * Test que se puede obtener un valor específico
     */
    public function test_get_specific_value(): void {
        $namespace = wp_api_codeia_config()->get('global_settings.namespace');

        $this->assertEquals('wp-api-codeia', $namespace);
    }

    /**
     * Test que se puede obtener versión por defecto
     */
    public function test_get_default_version(): void {
        $version = wp_api_codeia_config()->get_default_version();

        $this->assertEquals('v1', $version);
    }

    /**
     * Test que se obtienen endpoints por defecto
     */
    public function test_get_endpoints_returns_default_endpoints(): void {
        $endpoints = wp_api_codeia_config()->get_endpoints();

        $this->assertIsArray($endpoints);
        $this->assertArrayHasKey('posts', $endpoints);
        $this->assertArrayHasKey('pages', $endpoints);
    }

    /**
     * Test que se puede obtener configuración de un endpoint específico
     */
    public function test_get_endpoint_config_for_posts(): void {
        $config = wp_api_codeia_config()->get_endpoint_config('posts', 'v1');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('post_types', $config);
        $this->assertContains('post', $config['post_types']);
        $this->assertEquals('api_key', $config['auth']);
    }

    /**
     * Test que retorna null para endpoint inexistente
     */
    public function test_get_endpoint_config_returns_null_for_invalid_endpoint(): void {
        $config = wp_api_codeia_config()->get_endpoint_config('invalid', 'v1');

        $this->assertNull($config);
    }

    /**
     * Test que se pueden obtener permisos
     */
    public function test_get_permissions_returns_array(): void {
        $permissions = wp_api_codeia_config()->get_permissions();

        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('roles', $permissions);
    }

    /**
     * Test que se obtienen permisos de rol específico
     */
    public function test_get_role_permissions_for_administrator(): void {
        $permissions = wp_api_codeia_config()->get_role_permissions('administrator');

        $this->assertIsArray($permissions);
        $this->assertContains('*', $permissions['endpoints']);
        $this->assertContains('*', $permissions['methods']);
    }

    /**
     * Test que logging está habilitado por defecto
     */
    public function test_logging_enabled_by_default(): void {
        $enabled = wp_api_codeia_config()->is_logging_enabled();

        $this->assertTrue($enabled);
    }
}
