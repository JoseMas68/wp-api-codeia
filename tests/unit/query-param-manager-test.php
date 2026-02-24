<?php
/**
 * Tests para Query Param Manager
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;
use WP_API_Codeia\Utils\Query_Param_Manager;

/**
 * Class Query_Param_Manager_Test
 */
class Query_Param_Manager_Test extends UnitTestCase {

    /**
     * Test que se obtienen parámetros registrados
     */
    public function test_get_registered_params(): void {
        $manager = new Query_Param_Manager();
        $params = $manager->get_registered_params();

        $this->assertIsArray($params);
        $this->assertArrayHasKey('page', $params);
        $this->assertArrayHasKey('per_page', $params);
        $this->assertArrayHasKey('search', $params);
    }

    /**
     * Test que se obtiene un parámetro específico
     */
    public function test_get_param(): void {
        $manager = new Query_Param_Manager();
        $param = $manager->get_param('page');

        $this->assertIsArray($param);
        $this->assertEquals('integer', $param['type']);
        $this->assertEquals(1, $param['default']);
        $this->assertEquals(1, $param['minimum']);
    }

    /**
     * Test que se puede registrar parámetro custom
     */
    public function test_register_param(): void {
        $manager = new Query_Param_Manager();

        $result = $manager->register_param('custom_param', [
            'type' => 'string',
            'default' => 'default_value',
            'description' => 'Custom parameter',
        ]);

        $this->assertTrue($result);

        $param = $manager->get_param('custom_param');
        $this->assertIsArray($param);
        $this->assertEquals('string', $param['type']);
    }

    /**
     * Test que se puede remover parámetro
     */
    public function test_unregister_param(): void {
        $manager = new Query_Param_Manager();

        // Registrar
        $manager->register_param('temp_param', ['type' => 'string']);
        $this->assertNotNull($manager->get_param('temp_param'));

        // Remover
        $manager->unregister_param('temp_param');
        $this->assertNull($manager->get_param('temp_param'));
    }

    /**
     * Test que valida y sanitiza parámetros
     */
    public function test_validate_and_sanitize_params(): void {
        $manager = new Query_Param_Manager();

        $params = [
            'page' => '2',
            'per_page' => '20',
            'search' => 'test query',
        ];

        $validated = $manager->validate_and_sanitize_params($params);

        $this->assertNotWPError($validated);
        $this->assertEquals(2, $validated['page']);
        $this->assertEquals(20, $validated['per_page']);
        $this->assertEquals('test query', $validated['search']);
    }

    /**
     * Test que valida enteros
     */
    public function test_validates_integer(): void {
        $manager = new Query_Param_Manager();

        $params = ['page' => 'not_an_number'];
        $validated = $manager->validate_and_sanitize_params($params);

        $this->assertWPError($validated);
    }

    /**
     * Test que valida enums
     */
    public function test_validates_enum(): void {
        $manager = new Query_Param_Manager();

        $params = ['order' => 'INVALID'];
        $validated = $manager->validate_and_sanitize_params($params);

        $this->assertWPError($validated);
    }

    /**
     * Test que valida mínimos y máximos
     */
    public function test_validates_min_max(): void {
        $manager = new Query_Param_Manager();

        // per_page tiene máximo 100
        $params = ['per_page' => '150'];
        $validated = $manager->validate_and_sanitize_params($params);

        $this->assertWPError($validated);
    }

    /**
     * Test que aplica defaults
     */
    public function test_applies_defaults(): void {
        $manager = new Query_Param_Manager();

        $params = []; // Sin parámetros
        $validated = $manager->validate_and_sanitize_params($params);

        $this->assertNotWPError($validated);
        $this->assertEquals(1, $validated['page']);
        $this->assertEquals(10, $validated['per_page']);
    }

    /**
     * Test que construye tax_query
     */
    public function test_build_tax_query(): void {
        $manager = new Query_Param_Manager();

        $params = [
            'categories' => [1, 2, 3],
            'tags' => [5, 6],
        ];

        $tax_query = $manager->build_tax_query($params);

        $this->assertIsArray($tax_query);
        $this->assertCount(3, $tax_query); // relation + 2 tax queries
    }

    /**
     * Test que construye meta_query
     */
    public function test_build_meta_query(): void {
        $manager = new Query_Param_Manager();

        $params = [
            'meta_key' => 'custom_field',
            'meta_value' => 'value123',
        ];

        $meta_query = $manager->build_meta_query($params);

        $this->assertIsArray($meta_query);
        $this->assertEquals('custom_field', $meta_query['key']);
        $this->assertEquals('value123', $meta_query['value']);
    }

    /**
     * Test que construye WP_Query args
     */
    public function test_build_wp_query_args(): void {
        $manager = new Query_Param_Manager();

        $params = [
            'post_type' => 'post',
            'page' => 2,
            'per_page' => 20,
            'order' => 'ASC',
            'orderby' => 'title',
        ];

        $args = $manager->build_wp_query_args($params);

        $this->assertEquals('post', $args['post_type']);
        $this->assertEquals(2, $args['paged']);
        $this->assertEquals(20, $args['posts_per_page']);
        $this->assertEquals('ASC', $args['order']);
        $this->assertEquals('title', $args['orderby']);
    }

    /**
     * Test que sanitiza strings
     */
    public function test_sanitizes_strings(): void {
        $manager = new Query_Param_Manager();

        $params = ['search' => '<script>alert("xss")</script>test'];
        $validated = $manager->validate_and_sanitize_params($params);

        $this->assertNotWPError($validated);
        $this->assertStringNotContainsString('<script>', $validated['search']);
    }

    /**
     * Test que sanitiza arrays
     */
    public function test_sanitizes_arrays(): void {
        $manager = new Query_Param_Manager();

        $params = ['include' => ['1', '2', '3']];
        $validated = $manager->validate_and_sanitize_params($params);

        $this->assertNotWPError($validated);
        $this->assertIsArray($validated['include']);
    }
}
