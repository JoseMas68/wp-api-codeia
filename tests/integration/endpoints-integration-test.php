<?php
/**
 * Tests de integración para Endpoints
 *
 * @package WP_API_Codeia\Tests\Integration
 */

namespace WP_API_Codeia\Tests\Integration;

use WP_API_Codeia\Tests\UnitTestCase;

/**
 * Class Endpoints_Integration_Test
 */
class Endpoints_Integration_Test extends UnitTestCase {

    /**
     * Setup específico para tests de integración
     */
    public function setUp(): void {
        parent::setUp();

        // Crear algunos posts de prueba
        $this->factory->post->create_many(5, [
            'post_title' => 'Test Post',
            'post_status' => 'publish',
        ]);

        // Crear páginas de prueba
        $this->factory->post->create_many(3, [
            'post_type' => 'page',
            'post_title' => 'Test Page',
            'post_status' => 'publish',
        ]);
    }

    /**
     * Test que GET /api/v1/posts funciona
     */
    public function test_get_posts_endpoint(): void {
        $user_data = $this->create_user_with_api_key();

        // Simular request GET
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];
        $_GET['page'] = 1;
        $_GET['per_page'] = 10;

        $request = new \WP_API_Codeia\API_Request('v1', 'posts');
        $response = wp_api_codeia_endpoints()->process_request($request);

        $data = $response->get_data();

        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('data', $data);
        $this->assertGreaterThanOrEqual(5, count($data['data']));
    }

    /**
     * Test que GET /api/v1/posts/{id} funciona
     */
    public function test_get_single_post_endpoint(): void {
        $user_data = $this->create_user_with_api_key();
        $post_id = $this->factory->post->create();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        $request = new \WP_API_Codeia\API_Request('v1', 'posts', $post_id);
        $response = wp_api_codeia_endpoints()->process_request($request);

        $data = $response->get_data();

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals($post_id, $data['id']);
    }

    /**
     * Test que retorna 404 para post inexistente
     */
    public function test_get_single_post_returns_404_for_invalid_id(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        $request = new \WP_API_Codeia\API_Request('v1', 'posts', 99999);
        $response = wp_api_codeia_endpoints()->process_request($request);

        $this->assertEquals(404, $response->get_status());
    }

    /**
     * Test que GET /api/v1/pages funciona
     */
    public function test_get_pages_endpoint(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        $request = new \WP_API_Codeia\API_Request('v1', 'pages');
        $response = wp_api_codeia_endpoints()->process_request($request);

        $data = $response->get_data();

        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('data', $data);
        $this->assertGreaterThanOrEqual(3, count($data['data']));
    }

    /**
     * Test que paginación funciona correctamente
     */
    public function test_pagination_works(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];
        $_GET['per_page'] = 2;

        $request = new \WP_API_Codeia\API_Request('v1', 'posts');
        $response = wp_api_codeia_endpoints()->process_request($request);

        $data = $response->get_data();

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(2, $data['data']);
        $this->assertEquals(1, $data['meta']['page']);
        $this->assertEquals(2, $data['meta']['per_page']);
    }

    /**
     * Test que retorna 401 sin autenticación
     */
    public function test_endpoint_returns_401_without_auth(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = new \WP_API_Codeia\API_Request('v1', 'posts');
        $response = wp_api_codeia_endpoints()->process_request($request);

        $this->assertEquals(401, $response->get_status());
    }

    /**
     * Test que retorna 404 para endpoint inexistente
     */
    public function test_invalid_endpoint_returns_404(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        $request = new \WP_API_Codeia\API_Request('v1', 'invalid_endpoint');
        $response = wp_api_codeia_endpoints()->process_request($request);

        $this->assertEquals(404, $response->get_status());
    }

    /**
     * Test que POST retorna 501 (no implementado)
     */
    public function test_post_method_returns_501(): void {
        $user_data = $this->create_user_with_api_key();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        $request = new \WP_API_Codeia\API_Request('v1', 'posts');
        $response = wp_api_codeia_endpoints()->process_request($request);

        $this->assertEquals(501, $response->get_status());
    }

    /**
     * Test que campos se filtran correctamente
     */
    public function test_fields_are_filtered_correctly(): void {
        $user_data = $this->create_user_with_api_key();
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post Title',
            'post_content' => 'Test content',
        ]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user_data['api_key'];

        $request = new \WP_API_Codeia\API_Request('v1', 'posts', $post_id);
        $response = wp_api_codeia_endpoints()->process_request($request);

        $data = $response->get_data();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertArrayHasKey('author', $data);
    }
}
