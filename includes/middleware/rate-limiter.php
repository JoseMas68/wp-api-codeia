<?php
/**
 * Rate Limiter
 *
 * Gestiona el límite de solicitudes para la API.
 *
 * @package WP_API_Codeia\Middleware
 */

namespace WP_API_Codeia\Middleware;

/**
 * Class Rate_Limiter
 *
 * @package WP_API_Codeia\Middleware
 */
class Rate_Limiter {

    /**
     * Tabla personalizada para rate limiting
     *
     * @var string
     */
    private string $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'api_codeia_rate_limits';
    }

    /**
     * Verificar si una solicitud debe ser limitada
     *
     * @param string $identifier Identificador único (API Key, IP, User ID)
     * @param string $endpoint Endpoint solicitado
     * @param int    $limit Límite de solicitudes
     * @param int    $window Ventana de tiempo en segundos
     * @return array Array con 'allowed' (bool) yremaining', 'reset')
     */
    public function check_rate_limit(string $identifier, string $endpoint, int $limit = 100, int $window = 3600): array {
        global $wpdb;

        $now = current_time('timestamp', true);
        $window_start = $now - $window;

        // Limpiar registros viejos
        $this->cleanup_old_records($window_start);

        // Contar solicitudes en la ventana actual
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
            WHERE identifier = %s
            AND endpoint = %s
            AND requested_at > %d",
            $identifier,
            $endpoint,
            $window_start
        ));

        $remaining = max(0, $limit - $count);
        $reset = $now + $window;
        $allowed = $count < $limit;

        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'remaining' => $remaining,
            'reset' => $reset,
            'count' => $count,
        ];
    }

    /**
     * Registrar una solicitud
     *
     * @param string $identifier Identificador único
     * @param string $endpoint Endpoint solicitado
     * @param string $method Método HTTP
     * @return bool True si se registró correctamente
     */
    public function log_request(string $identifier, string $endpoint, string $method = 'GET'): bool {
        global $wpdb;

        $inserted = $wpdb->insert(
            $this->table_name,
            [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'method' => $method,
                'requested_at' => current_time('timestamp', true),
                'ip_address' => $this->get_client_ip(),
            ],
            ['%s', '%s', '%s', '%d', '%s']
        );

        return $inserted !== false;
    }

    /**
     * Obtener estadísticas de uso
     *
     * @param string $identifier Identificador único
     * @param int    $days Días de historial a obtener
     * @return array Estadísticas
     */
    public function get_usage_stats(string $identifier, int $days = 30): array {
        global $wpdb;

        $since = current_time('timestamp', true) - ($days * DAY_IN_SECONDS);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT endpoint, method, COUNT(*) as request_count
            FROM {$this->table_name}
            WHERE identifier = %s
            AND requested_at > %d
            GROUP BY endpoint, method
            ORDER BY request_count DESC",
            $identifier,
            $since
        ));

        $stats = [
            'total_requests' => 0,
            'endpoints' => [],
            'methods' => [],
            'daily_breakdown' => $this->get_daily_breakdown($identifier, $days),
        ];

        foreach ($results as $row) {
            $stats['total_requests'] += (int) $row->request_count;

            $endpoint_key = $row->endpoint;
            if (!isset($stats['endpoints'][$endpoint_key])) {
                $stats['endpoints'][$endpoint_key] = 0;
            }
            $stats['endpoints'][$endpoint_key] += (int) $row->request_count;

            $method_key = $row->method;
            if (!isset($stats['methods'][$method_key])) {
                $stats['methods'][$method_key] = 0;
            }
            $stats['methods'][$method_key] += (int) $row->request_count;
        }

        return $stats;
    }

    /**
     * Obtener desglose diario de uso
     *
     * @param string $identifier Identificador único
     * @param int    $days Días a obtener
     * @return array Desglose diario
     */
    private function get_daily_breakdown(string $identifier, int $days): array {
        global $wpdb;

        $since = current_time('timestamp', true) - ($days * DAY_IN_SECONDS);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(FROM_UNIXTIME(requested_at)) as request_date, COUNT(*) as request_count
            FROM {$this->table_name}
            WHERE identifier = %s
            AND requested_at > %d
            GROUP BY DATE(FROM_UNIXTIME(requested_at))
            ORDER BY request_date ASC",
            $identifier,
            $since
        ));

        $breakdown = [];

        foreach ($results as $row) {
            $breakdown[$row->request_date] = (int) $row->request_count;
        }

        return $breakdown;
    }

    /**
     * Obtener límites configurados para un endpoint
     *
     * @param string $endpoint Endpoint
     * @param string $scope Scope de autenticación
     * @return array Límites configurados
     */
    public function get_endpoint_limits(string $endpoint, string $scope): array {
        $config = wp_api_codeia_config()->get_config();
        $rate_limits = $config['rate_limits'] ?? [];

        // Buscar límites específicos del endpoint
        if (isset($rate_limits['endpoints'][$endpoint])) {
            $endpoint_limits = $rate_limits['endpoints'][$endpoint];

            if (isset($endpoint_limits[$scope])) {
                return $endpoint_limits[$scope];
            }

            if (isset($endpoint_limits['default'])) {
                return $endpoint_limits['default'];
            }
        }

        // Buscar límites por scope
        if (isset($rate_limits['by_scope'][$scope])) {
            return $rate_limits['by_scope'][$scope];
        }

        // Retornar límites por defecto
        return $rate_limits['default'] ?? [
            'limit' => 100,
            'window' => 3600, // 1 hora
        ];
    }

    /**
     * Limpiar registros antiguos
     *
     * @param int $before_timestamp Timestamp límite
     * @return int Cantidad de registros eliminados
     */
    private function cleanup_old_records(int $before_timestamp): int {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            ['requested_at' => $before_timestamp],
            ['<'],
            ['%d']
        );
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private function get_client_ip(): string {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }

    /**
     * Resetear límites para un identificador
     *
     * @param string $identifier Identificador único
     * @return int Cantidad de registros eliminados
     */
    public function reset_limits(string $identifier): int {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            ['identifier' => $identifier],
            ['%s']
        );
    }

    /**
     * Obtener todos los identificadores con límites activos
     *
     * @param int $limit Límite de resultados
     * @return array Identificadores
     */
    public function get_active_identifiers(int $limit = 100): array {
        global $wpdb;

        $window_start = current_time('timestamp', true) - HOUR_IN_SECONDS;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT identifier, COUNT(*) as request_count
            FROM {$this->table_name}
            WHERE requested_at > %d
            GROUP BY identifier
            ORDER BY request_count DESC
            LIMIT %d",
            $window_start,
            $limit
        ));

        return $results;
    }

    /**
     * Generar cabeceras de rate limit para respuesta HTTP
     *
     * @param array $rate_limit_info Información de rate limit
     * @return array Cabeceras
     */
    public function generate_rate_limit_headers(array $rate_limit_info): array {
        return [
            'X-RateLimit-Limit' => $rate_limit_info['limit'],
            'X-RateLimit-Remaining' => $rate_limit_info['remaining'],
            'X-RateLimit-Reset' => $rate_limit_info['reset'],
        ];
    }

    /**
     * Verificar si una IP está en lista negra
     *
     * @param string $ip Dirección IP
     * @return bool True si está bloqueada
     */
    public function is_ip_blocked(string $ip): bool {
        $blocked_ips = get_option('wp_api_codeia_blocked_ips', []);
        return in_array($ip, $blocked_ips, true);
    }

    /**
     * Bloquear una dirección IP
     *
     * @param string $ip Dirección IP
     * @return bool True si se bloqueó correctamente
     */
    public function block_ip(string $ip): bool {
        $blocked_ips = get_option('wp_api_codeia_blocked_ips', []);

        if (!in_array($ip, $blocked_ips, true)) {
            $blocked_ips[] = $ip;
            return update_option('wp_api_codeia_blocked_ips', $blocked_ips);
        }

        return true;
    }

    /**
     * Desbloquear una dirección IP
     *
     * @param string $ip Dirección IP
     * @return bool True si se desbloqueó correctamente
     */
    public function unblock_ip(string $ip): bool {
        $blocked_ips = get_option('wp_api_codeia_blocked_ips', []);
        $index = array_search($ip, $blocked_ips, true);

        if ($index !== false) {
            unset($blocked_ips[$index]);
            return update_option('wp_api_codeia_blocked_ips', array_values($blocked_ips));
        }

        return false;
    }
}
