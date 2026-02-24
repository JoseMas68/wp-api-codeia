<?php
/**
 * Tests para JWT Handler
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;
use WP_API_Codeia\Auth\JWT_Handler;

/**
 * Class JWT_Handler_Test
 */
class JWT_Handler_Test extends UnitTestCase {

    /**
     * Test que se puede generar un access token
     */
    public function test_generate_access_token(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $jwt_handler = new JWT_Handler();

        $token = $jwt_handler->generate_access_token($user_id);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Verificar formato JWT (3 partes separadas por puntos)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test que se puede decodificar un token válido
     */
    public function test_decode_valid_token(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $jwt_handler = new JWT_Handler();

        $token = $jwt_handler->generate_access_token($user_id);
        $payload = $jwt_handler->decode($token);

        $this->assertIsArray($payload);
        $this->assertEquals($user_id, $payload['sub']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('jti', $payload);
    }

    /**
     * Test que token inválido retorna false
     */
    public function test_decode_invalid_token(): void {
        $jwt_handler = new JWT_Handler();

        $payload = $jwt_handler->decode('invalid_token_12345');

        $this->assertFalse($payload);
    }

    /**
     * Test que se puede generar refresh token
     */
    public function test_generate_refresh_token(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $jwt_handler = new JWT_Handler();

        $refresh_token = $jwt_handler->generate_refresh_token($user_id);

        $this->assertIsString($refresh_token);
        $this->assertNotEmpty($refresh_token);

        // Verificar que tiene tipo 'refresh'
        $payload = $jwt_handler->decode($refresh_token);
        $this->assertEquals('refresh', $payload['type']);
    }

    /**
     * Test que se puede verificar refresh token
     */
    public function test_verify_refresh_token(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $jwt_handler = new JWT_Handler();

        $refresh_token = $jwt_handler->generate_refresh_token($user_id);
        $verified_id = $jwt_handler->verify_refresh_token($refresh_token);

        $this->assertEquals($user_id, $verified_id);
    }

    /**
     * Test que refresh token inválido retorna false
     */
    public function test_verify_invalid_refresh_token(): void {
        $jwt_handler = new JWT_Handler();

        $result = $jwt_handler->verify_refresh_token('invalid_refresh_token');

        $this->assertFalse($result);
    }

    /**
     * Test que se puede invalidar un token
     */
    public function test_invalidate_token(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $jwt_handler = new JWT_Handler();

        $token = $jwt_handler->generate_access_token($user_id);
        $result = $jwt_handler->invalidate_token($token);

        $this->assertTrue($result);

        // Verificar que el token está en blacklist
        $payload = $jwt_handler->decode($token);
        $this->assertTrue($jwt_handler->is_token_blacklisted($payload));
    }

    /**
     * Test que token expirado no puede decodificarse
     */
    public function test_expired_token_returns_false(): void {
        $jwt_handler = new JWT_Handler();

        // Generar token con expiración en el pasado
        $payload = [
            'sub' => 1,
            'exp' => time() - 3600, // Expiró hace 1 hora
        ];

        $token = $jwt_handler->encode($payload);
        $decoded = $jwt_handler->decode($token);

        $this->assertFalse($decoded);
    }

    /**
     * Test que se pueden establecer tiempos de vida
     */
    public function test_set_token_lifetime(): void {
        $jwt_handler = new JWT_Handler();

        $jwt_handler->set_token_lifetime(7200); // 2 horas
        $this->assertEquals(7200, $jwt_handler->get_token_lifetime());

        $jwt_handler->set_refresh_lifetime(3600 * 24 * 60); // 60 días
        $this->assertEquals(3600 * 24 * 60, $jwt_handler->get_refresh_lifetime());
    }

    /**
     * Test que tiempo mínimo es respetado
     */
    public function test_minimum_lifetime_enforced(): void {
        $jwt_handler = new JWT_Handler();

        $jwt_handler->set_token_lifetime(30); // Menos al mínimo
        $this->assertEquals(60, $jwt_handler->get_token_lifetime()); // Mínimo 1 minuto

        $jwt_handler->set_refresh_lifetime(1800); // Menos al mínimo
        $this->assertEquals(3600, $jwt_handler->get_refresh_lifetime()); // Mínimo 1 hora
    }

    /**
     * Test que incluye datos extra en el token
     */
    public function test_include_extra_data_in_token(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $jwt_handler = new JWT_Handler();

        $extra_data = [
            'custom_claim' => 'custom_value',
            'user_role' => 'administrator',
        ];

        $token = $jwt_handler->generate_access_token($user_id, $extra_data);
        $payload = $jwt_handler->decode($token);

        $this->assertEquals('custom_value', $payload['custom_claim']);
        $this->assertEquals('administrator', $payload['user_role']);
    }
}
