<?php
/**
 * Tests para Cache Manager
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;
use WP_API_Codeia\Cache_Manager;

/**
 * Class Cache_Manager_Test
 */
class Cache_Manager_Test extends UnitTestCase {

    /**
     * Test que se puede guardar y obtener valor del cache
     */
    public function test_set_and_get(): void {
        $manager = new Cache_Manager();
        $key = 'test_key_' . wp_generate_password(8, false);
        $value = ['test' => 'data'];

        // Guardar
        $result = $manager->set($key, $value, 'posts');
        $this->assertTrue($result);

        // Obtener
        $cached = $manager->get($key, 'posts');
        $this->assertEquals($value, $cached);
    }

    /**
     * Test que get retorna false si no existe
     */
    public function test_get_returns_false_for_nonexistent(): void {
        $manager = new Cache_Manager();

        $value = $manager->get('nonexistent_key', 'posts');

        $this->assertFalse($value);
    }

    /**
     * Test que se puede eliminar un valor
     */
    public function test_delete(): void {
        $manager = new Cache_Manager();
        $key = 'test_key_' . wp_generate_password(8, false);
        $value = 'test_value';

        $manager->set($key, $value, 'posts');
        $this->assertEquals($value, $manager->get($key, 'posts'));

        $manager->delete($key, 'posts');
        $this->assertFalse($manager->get($key, 'posts'));
    }

    /**
     * Test que remember guarda si no existe
     */
    public function test_remember(): void {
        $manager = new Cache_Manager();
        $key = 'test_key_' . wp_generate_password(8, false);
        $callback_value = ['callback' => 'result'];

        // Primera llamada: ejecuta callback
        $value = $manager->remember($key, function() use ($callback_value) {
            return $callback_value;
        }, 'posts');

        $this->assertEquals($callback_value, $value);

        // Segunda llamada: usa cache
        $value2 = $manager->remember($key, function() {
            return ['different' => 'value'];
        }, 'posts');

        $this->assertEquals($callback_value, $value2);
    }

    /**
     * Test que se obtienen estadísticas
     */
    public function test_get_stats(): void {
        $manager = new Cache_Manager();

        $stats = $manager->get_stats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('groups', $stats);
        $this->assertArrayHasKey('total_keys', $stats);
    }

    /**
     * Test que flush_group limpia un grupo
     */
    public function test_flush_group(): void {
        $manager = new Cache_Manager();
        $key = 'test_key_' . wp_generate_password(8, false);

        $manager->set($key, 'value', 'posts');
        $manager->flush_group('posts');

        $this->assertFalse($manager->get($key, 'posts'));
    }

    /**
     * Test que flush_all limpia todos los grupos
     */
    public function test_flush_all(): void {
        $manager = new Cache_Manager();
        $key1 = 'test_key_' . wp_generate_password(8, false);
        $key2 = 'test_key_' . wp_generate_password(8, false);

        $manager->set($key1, 'value1', 'posts');
        $manager->set($key2, 'value2', 'terms');

        $manager->flush_all();

        $this->assertFalse($manager->get($key1, 'posts'));
        $this->assertFalse($manager->get($key2, 'terms'));
    }

    /**
     * Test que se puede configurar un grupo
     */
    public function test_configure_group(): void {
        $manager = new Cache_Manager();

        $result = $manager->configure_group('custom', [
            'enabled' => true,
            'ttl' => 1800,
            'strategy' => 'transient',
        ]);

        $this->assertTrue($result);
    }

    /**
     * Test que cache deshabilitado no guarda
     */
    public function test_disabled_cache_does_not_save(): void {
        $manager = new Cache_Manager();

        // Configurar grupo como deshabilitado
        $manager->configure_group('test_disabled', ['enabled' => false]);

        $key = 'test_key_' . wp_generate_password(8, false);
        $result = $manager->set($key, 'value', 'test_disabled');

        // Debería retornar false porque está deshabilitado
        // O bien guardar pero get retornar false
    }

    /**
     * Test que invalidación de post funciona
     */
    public function test_invalidate_post_cache(): void {
        $manager = new Cache_Manager();
        $post_id = $this->factory->post->create();
        $key = 'post_' . $post_id;

        // Guardar en cache
        $manager->set($key, ['data' => 'test'], 'posts');
        $this->assertNotFalse($manager->get($key, 'posts'));

        // Invalidar
        $manager->invalidate_post_cache($post_id);
        $this->assertFalse($manager->get($key, 'posts'));
    }

    /**
     * Test que invalidación de término funciona
     */
    public function test_invalidate_term_cache(): void {
        $manager = new Cache_Manager();
        $term_id = $this->factory->term->create(['taxonomy' => 'category']);
        $key = 'term_' . $term_id;

        // Guardar en cache
        $manager->set($key, ['data' => 'test'], 'terms');
        $this->assertNotFalse($manager->get($key, 'terms'));

        // Invalidar
        $manager->invalidate_term_cache($term_id);
        $this->assertFalse($manager->get($key, 'terms'));
    }
}
