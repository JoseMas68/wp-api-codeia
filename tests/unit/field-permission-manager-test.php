<?php
/**
 * Tests para Field Permission Manager
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;
use WP_API_Codeia\Permissions\Field_Permission_Manager;

/**
 * Class Field_Permission_Manager_Test
 */
class Field_Permission_Manager_Test extends UnitTestCase {

    /**
     * Test que campos nativos están permitidos por defecto
     */
    public function test_native_fields_allowed_by_default(): void {
        $manager = new Field_Permission_Manager();

        $native_fields = ['ID', 'post_title', 'post_content', 'post_author'];

        foreach ($native_fields as $field) {
            $this->assertTrue(
                $manager->is_field_allowed($field, 'post', 'subscriber', 'read')
            );
        }
    }

    /**
     * Test que se puede agregar regla de denegación
     */
    public function test_add_deny_rule(): void {
        $manager = new Field_Permission_Manager();

        // Agregar regla de denegación para subscriber
        $result = $manager->add_rule('post', 'custom_field', 'deny', 'role', 'subscriber');

        $this->assertTrue($result);

        // Verificar que el campo está denegado
        $this->assertFalse(
            $manager->is_field_allowed('custom_field', 'post', 'subscriber', 'read')
        );
    }

    /**
     * Test que se puede agregar regla de permiso
     */
    public function test_add_allow_rule(): void {
        $manager = new Field_Permission_Manager();

        // Agregar regla de allow para admin
        $result = $manager->add_rule('post', 'sensitive_field', 'allow', 'role', 'administrator');

        $this->assertTrue($result);

        // Verificar que el campo está permitido
        $this->assertTrue(
            $manager->is_field_allowed('sensitive_field', 'post', 'administrator', 'admin')
        );
    }

    /**
     * Test que reglas de deny tienen prioridad
     */
    public function test_deny_rules_override_allow(): void {
        $manager = new Field_Permission_Manager();

        // Agregar allow
        $manager->add_rule('post', 'test_field', 'allow', 'role', 'subscriber');

        // Agregar deny (debería tener prioridad)
        $manager->add_rule('post', 'test_field', 'deny', 'role', 'subscriber');

        $this->assertFalse(
            $manager->is_field_allowed('test_field', 'post', 'subscriber', 'read')
        );
    }

    /**
     * Test que se puede filtrar campos según permisos
     */
    public function test_filter_fields_removes_disallowed(): void {
        $manager = new Field_Permission_Manager();

        // Denegar campo 'secret' para subscriber
        $manager->add_rule('post', 'secret', 'deny', 'role', 'subscriber');

        $fields = [
            'ID' => 1,
            'post_title' => 'Test',
            'secret' => 'hidden',
        ];

        $filtered = $manager->filter_fields($fields, 'post', 'subscriber', 'read');

        $this->assertArrayHasKey('ID', $filtered);
        $this->assertArrayHasKey('post_title', $filtered);
        $this->assertArrayNotHasKey('secret', $filtered);
    }

    /**
     * Test que se obtienen campos permitidos
     */
    public function test_get_allowed_fields(): void {
        $manager = new Field_Permission_Manager();

        $allowed = $manager->get_allowed_fields('post', 'subscriber', 'read');

        $this->assertIsArray($allowed);
        $this->assertArrayHasKey('ID', $allowed);
        $this->assertArrayHasKey('post_title', $allowed);
    }

    /**
     * Test que se pueden remover reglas
     */
    public function test_remove_rule(): void {
        $manager = new Field_Permission_Manager();

        // Agregar regla
        $manager->add_rule('post', 'test_field', 'deny', 'role', 'subscriber');
        $this->assertFalse(
            $manager->is_field_allowed('test_field', 'post', 'subscriber', 'read')
        );

        // Remover regla
        $manager->remove_rule('post', 'test_field', 'deny', 'role', 'subscriber');

        // Ahora debería estar permitido (no hay denegación explícita)
        $this->assertTrue(
            $manager->is_field_allowed('test_field', 'post', 'subscriber', 'read')
        );
    }

    /**
     * Test que scope read_write permite más campos que read
     */
    public function test_scope_affects_permissions(): void {
        $manager = new Field_Permission_Manager();

        // Denegar campo para scope 'read'
        $manager->add_rule('post', 'editable_field', 'deny', 'scope', 'read');

        // Con scope read, debería estar denegado
        $this->assertFalse(
            $manager->is_field_allowed('editable_field', 'post', 'subscriber', 'read')
        );

        // Con scope read_write, debería estar permitido
        $this->assertTrue(
            $manager->is_field_allowed('editable_field', 'post', 'subscriber', 'read_write')
        );
    }

    /**
     * Test que se puede exportar configuración
     */
    public function test_export_config(): void {
        $manager = new Field_Permission_Manager();

        $config = $manager->export_config();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('global', $config);
        $this->assertArrayHasKey('post_types', $config);
    }

    /**
     * Test que se puede importar configuración
     */
    public function test_import_config(): void {
        $manager = new Field_Permission_Manager();

        $config = [
            'global' => [
                'deny' => [
                    'subscriber' => ['test_field'],
                ],
            ],
            'post_types' => [],
        ];

        $result = $manager->import_config($config);

        $this->assertTrue($result);
        $this->assertFalse(
            $manager->is_field_allowed('test_field', 'post', 'subscriber', 'read')
        );
    }

    /**
     * Test que se obtiene resumen de permisos
     */
    public function test_get_permissions_summary(): void {
        $manager = new Field_Permission_Manager();

        $manager->add_rule('post', 'secret_field', 'deny', 'role', 'subscriber');

        $summary = $manager->get_permissions_summary('subscriber', 'read');

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('global', $summary);
        $this->assertArrayHasKey('post_types', $summary);
    }
}
