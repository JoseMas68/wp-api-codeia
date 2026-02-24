<?php
/**
 * Tests para Rate Limiter
 *
 * @package WP_API_Codeia\Tests\Unit
 */

namespace WP_API_Codeia\Tests\Unit;

use WP_API_Codeia\Tests\UnitTestCase;
use WP_API_Codeia\Middleware\Rate_Limiter;

/**
 * Class Rate_Limiter_Test
 */
class Rate_Limiter_Test extends UnitTestCase {

    /**
     * Test que primera solicitud siempre está permitida
     */
    public function test_first_request_allowed(): void {
        $limiter = new Rate_Limiter();
        $identifier = 'test_' . wp_generate_password(8, false);

        $result = $limiter->check_rate_limit($identifier, 'posts', 10, 3600);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(9, $result['remaining']);
    }

    /**
     * Test que se pueden registrar solicitudes
     */
    public function test_log_request(): void {
        $limiter = new Rate_Limiter();
        $identifier = 'test_' . wp_generate_password(8, false);

        $result = $limiter->log_request($identifier, 'posts', 'GET');

        $this->assertTrue($result);
    }

    /**
     * Test que contador incrementa con cada solicitud
     */
    public function test_counter_increments(): void {
        $limiter = new Rate_Limiter();
        $identifier = 'test_' . wp_generate_password(8, false);
        $endpoint = 'test_endpoint';
        $limit = 5;

        // Hacer 3 solicitudes
        for ($i = 0; $i < 3; $i++) {
            $limiter->log_request($identifier, $endpoint, 'GET');
        }

        $result = $limiter->check_rate_limit($identifier, $endpoint, $limit, 3600);

        $this->assertEquals(3, $result['count']);
        $this->assertEquals(2, $result['remaining']);
        $this->assertTrue($result['allowed']);
    }

    /**
     * Test que se respeta el límite
     */
    public function test_limit_enforced(): void {
        $limiter = new Rate_Limiter();
        $identifier = 'test_' . wp_generate_password(8, false);
        $endpoint = 'test_endpoint';
        $limit = 3;

        // Exceder límite
        for ($i = 0; $i < $limit + 1; $i++) {
            $limiter->log_request($identifier, $endpoint, 'GET');
        }

        $result = $limiter->check_rate_limit($identifier, $endpoint, $limit, 3600);

        $this->assertEquals($limit + 1, $result['count']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertFalse($result['allowed']);
    }

    /**
     * Test que se resetean límites
     */
    public function test_reset_limits(): void {
        $limiter = new Rate_Limiter();
        $identifier = 'test_' . wp_generate_password(8, false);
        $endpoint = 'test_endpoint';

        // Registrar algunas solicitudes
        for ($i = 0; $i < 5; $i++) {
            $limiter->log_request($identifier, $endpoint, 'GET');
        }

        // Resetear
        $deleted = $limiter->reset_limits($identifier);

        $this->assertGreaterThan(0, $deleted);

        // Verificar que empezó de cero
        $result = $limiter->check_rate_limit($identifier, $endpoint, 10, 3600);
        $this->assertEquals(0, $result['count']);
    }

    /**
     * Test que se obtienen estadísticas de uso
     */
    public function test_get_usage_stats(): void {
        $limiter = new Rate_Limiter();
        $identifier = 'test_' . wp_generate_password(8, false);
        $endpoint = 'test_endpoint';

        // Registrar solicitudes
        $limiter->log_request($identifier, $endpoint, 'GET');
        $limiter->log_request($identifier, $endpoint, 'GET');
        $limiter->log_request($identifier, $endpoint, 'POST');

        $stats = $limiter->get_usage_stats($identifier, 30);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('endpoints', $stats);
        $this->assertArrayHasKey('methods', $stats);
        $this->assertEquals(3, $stats['total_requests']);
    }

    /**
     * Test que se obtienen identificadores activos
     */
    public function test_get_active_identifiers(): void {
        $limiter = new Rate_Limiter();
        $identifier = 'test_' . wp_generate_password(8, false);

        $limiter->log_request($identifier, 'posts', 'GET');

        $active = $limiter->get_active_identifiers(100);

        $this->assertIsArray($active);
    }

    /**
     * Test que se generan headers de rate limit
     */
    public function test_generate_rate_limit_headers(): void {
        $limiter = new Rate_Limiter();

        $rate_info = [
            'limit' => 100,
            'remaining' => 50,
            'reset' => time() + 3600,
        ];

        $headers = $limiter->generate_rate_limit_headers($rate_info);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);

        $this->assertEquals(100, $headers['X-RateLimit-Limit']);
        $this->assertEquals(50, $headers['X-RateLimit-Remaining']);
    }

    /**
     * Test bloqueo de IP
     */
    public function test_ip_blocking(): void {
        $limiter = new Rate_Limiter();
        $test_ip = '192.168.1.100';

        // Verificar que no está bloqueada
        $this->assertFalse($limiter->is_ip_blocked($test_ip));

        // Bloquear
        $result = $limiter->block_ip($test_ip);
        $this->assertTrue($result);

        // Verificar que está bloqueada
        $this->assertTrue($limiter->is_ip_blocked($test_ip));

        // Desbloquear
        $result = $limiter->unblock_ip($test_ip);
        $this->assertTrue($result);

        // Verificar que ya no está bloqueada
        $this->assertFalse($limiter->is_ip_blocked($test_ip));
    }

    /**
     * Test que se obtienen límites de endpoint
     */
    public function test_get_endpoint_limits(): void {
        $limiter = new Rate_Limiter();

        $limits = $limiter->get_endpoint_limits('posts', 'read_write');

        $this->assertIsArray($limits);
        $this->assertArrayHasKey('limit', $limits);
        $this->assertArrayHasKey('window', $limits);
    }
}
