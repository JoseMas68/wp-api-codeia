<?php
/**
 * Tests para Detector Manager
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;

/**
 * Class Detector_Manager_Test
 */
class Detector_Manager_Test extends UnitTestCase {

    /**
     * Test que detector manager está disponible
     */
    public function test_detector_manager_is_available(): void {
        $this->assertTrue(function_exists('wp_api_codeia_detectors'));
        $detector = wp_api_codeia_detectors();
        $this->assertInstanceOf('WP_API_Codeia\Detector_Manager', $detector);
    }

    /**
     * Test que detecta CPTs nativos
     */
    public function test_detect_cpts_returns_native_post_types(): void {
        $cpts = wp_api_codeia_detectors()->detect_cpts();

        $this->assertIsArray($cpts);
        $this->assertArrayHasKey('post', $cpts);
        $this->assertArrayHasKey('page', $cpts);
    }

    /**
     * Test que CPTs tienen la estructura correcta
     */
    public function test_cpts_have_correct_structure(): void {
        $cpts = wp_api_codeia_detectors()->detect_cpts();

        $this->assertArrayHasKey('name', $cpts['post']);
        $this->assertArrayHasKey('label', $cpts['post']);
        $this->assertArrayHasKey('public', $cpts['post']);
        $this->assertArrayHasKey('hierarchical', $cpts['post']);
        $this->assertArrayHasKey('supports', $cpts['post']);
        $this->assertArrayHasKey('taxonomies', $cpts['post']);
    }

    /**
     * Test que detecta taxonomías
     */
    public function test_detect_taxonomies_returns_taxonomies(): void {
        $taxonomies = wp_api_codeia_detectors()->detect_taxonomies();

        $this->assertIsArray($taxonomies);
        $this->assertArrayHasKey('category', $taxonomies);
        $this->assertArrayHasKey('post_tag', $taxonomies);
    }

    /**
     * Test que taxonomías tienen la estructura correcta
     */
    public function test_taxonomies_have_correct_structure(): void {
        $taxonomies = wp_api_codeia_detectors()->detect_taxonomies();

        $this->assertArrayHasKey('name', $taxonomies['category']);
        $this->assertArrayHasKey('label', $taxonomies['category']);
        $this->assertArrayHasKey('hierarchical', $taxonomies['category']);
        $this->assertTrue($taxonomies['category']['hierarchical']);
    }

    /**
     * Test que detecta meta fields
     */
    public function test_detect_meta_fields_returns_array(): void {
        $meta_fields = wp_api_codeia_detectors()->detect_meta_fields();

        $this->assertIsArray($meta_fields);
        $this->assertArrayHasKey('post', $meta_fields);
    }

    /**
     * Test que detecta CPTs custom
     */
    public function test_detect_custom_post_types(): void {
        // Registrar un CPT custom
        register_post_type('book', [
            'public' => true,
            'label' => 'Books',
        ]);

        $cpts = wp_api_codeia_detectors()->detect_cpts();

        $this->assertArrayHasKey('book', $cpts);
        $this->assertEquals('Books', $cpts['book']['label']);

        // Limpiar
        unregister_post_type('book');
    }
}
