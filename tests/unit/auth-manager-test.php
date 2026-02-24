<?php
/**
 * Tests para Auth Manager
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;

/**
 * Class Auth_Manager_Test
 */
class Auth_Manager_Test extends UnitTestCase {

    /**
     * Test que auth manager está disponible
     */
    public function test_auth_manager_is_available(): void {
        $this->assertTrue(function_exists('wp_api_codeia_auth'));
        $auth_manager = wp_api_codeia_auth();
        $this->assertInstanceOf('WP_API_Codeia\Auth_Manager', $auth_manager);
    }

    /**
     * Test autenticación con API Key válida
     */
    public function test_authenticate_with_valid_api_key(): void {
        $user_data = $this->create_user_with_api_key();

        // Simular header Authorization
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        $user = wp_api_codeia_auth()->authenticate('posts', 'v1');

        $this->assertNotNull($user);
        $this->assertEquals($user_data['user_id'], $user->ID);
    }

    /**
     * Test que falla autenticación sin API Key
     */
    public function test_authenticate_fails_without_api_key(): void {
        // Limpiar headers
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $user = wp_api_codeia_auth()->authenticate('posts', 'v1');

        $this->assertNull($user);
    }

    /**
     * Test que falla autenticación con API Key inválida
     */
    public function test_authenticate_fails_with_invalid_api_key(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid_key_12345';

        $user = wp_api_codeia_auth()->authenticate('posts', 'v1');

        $this->assertNull($user);
    }

    /**
     * Test que autenticación retorna datos correctos
     */
    public function test_authenticate_returns_auth_data(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        wp_api_codeia_auth()->authenticate('posts', 'v1');
        $auth_data = wp_api_codeia_auth()->get_auth_data();

        $this->assertIsArray($auth_data);
        $this->assertEquals('api_key', $auth_data['method']);
        $this->assertEquals('read_write', $auth_data['scope']);
        $this->assertArrayHasKey('key_id', $auth_data);
    }

    /**
     * Test current_user_can con scope read_write
     */
    public function test_current_user_can_with_read_write_scope(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];
        wp_api_codeia_auth()->authenticate('posts', 'v1');

        $this->assertTrue(wp_api_codeia_auth()->current_user_can('read'));
        $this->assertTrue(wp_api_codeia_auth()->current_user_can('write'));
        $this->assertFalse(wp_api_codeia_auth()->current_user_can('admin'));
    }

    /**
     * Test clear_auth limpia autenticación
     */
    public function test_clear_auth_clears_authentication(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];
        wp_api_codeia_auth()->authenticate('posts', 'v1');

        $this->assertNotNull(wp_api_codeia_auth()->get_current_user());

        wp_api_codeia_auth()->clear_auth();

        $this->assertNull(wp_api_codeia_auth()->get_current_user());
        $this->assertNull(wp_api_codeia_auth()->get_auth_data());
    }
}
