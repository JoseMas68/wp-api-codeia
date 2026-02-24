<?php
/**
 * Tests para Auth Key Repository
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;
use WP_API_Codeia\Repositories\Auth_Key_Repository;

/**
 * Class Auth_Key_Repository_Test
 */
class Auth_Key_Repository_Test extends UnitTestCase {

    /**
     * Test que se puede crear una API Key
     */
    public function test_create_api_key(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $repository = new Auth_Key_Repository();

        $api_key = $repository->create(
            $user_id,
            'Test Key',
            'read_write',
            0
        );

        $this->assertIsString($api_key);
        $this->assertStringStartsWith('wpck_', $api_key);
        $this->assertGreaterThan(40, strlen($api_key));
    }

    /**
     * Test que se puede validar una API Key
     */
    public function test_validate_api_key(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $repository = new Auth_Key_Repository();

        $api_key = $repository->create($user_id, 'Test Key', 'read_write');
        $result = $repository->validate($api_key);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertEquals($user_id, $result['user_id']);
        $this->assertEquals('read_write', $result['scope']);
    }

    /**
     * Test que falla validar API Key inválida
     */
    public function test_validate_invalid_api_key_returns_false(): void {
        $repository = new Auth_Key_Repository();
        $result = $repository->validate('invalid_key_12345');

        $this->assertFalse($result);
    }

    /**
     * Test que se obtienen las keys de un usuario
     */
    public function test_get_user_keys(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $repository = new Auth_Key_Repository();

        $repository->create($user_id, 'Key 1', 'read');
        $repository->create($user_id, 'Key 2', 'write');

        $keys = $repository->get_user_keys($user_id);

        $this->assertCount(2, $keys);
        $this->assertEquals('Key 1', $keys[0]['name']);
        $this->assertEquals('Key 2', $keys[1]['name']);
    }

    /**
     * Test que se puede revocar una API Key
     */
    public function test_revoke_api_key(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $repository = new Auth_Key_Repository();

        $api_key = $repository->create($user_id, 'Test Key', 'read_write');
        $key_data = $repository->validate($api_key);

        $this->assertTrue($repository->revoke($key_data['id']));

        // Después de revocar, ya no debería validar
        $result = $repository->validate($api_key);
        $this->assertFalse($result);
    }

    /**
     * Test que scope es sanitizado correctamente
     */
    public function test_scope_is_sanitized(): void {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        $repository = new Auth_Key_Repository();

        // Intentar crear con scope inválido
        $api_key = $repository->create($user_id, 'Test Key', 'invalid_scope');
        $result = $repository->validate($api_key);

        // Debería haber sido sanitizado a 'read_write'
        $this->assertEquals('read_write', $result['scope']);
    }

    /**
     * Test que se obtiene array vacío para usuario sin keys
     */
    public function test_get_user_keys_returns_empty_for_new_user(): void {
        $user_id = $this->factory->user->create();
        $repository = new Auth_Key_Repository();

        $keys = $repository->get_user_keys($user_id);

        $this->assertIsArray($keys);
        $this->assertEmpty($keys);
    }
}
