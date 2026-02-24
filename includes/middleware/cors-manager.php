<?php
/**
 * CORS Manager
 *
 * Gestiona los headers CORS para la API.
 *
 * @package WP_API_Codeia\Middleware
 */

namespace WP_API_Codeia\Middleware;

/**
 * Class CORS_Manager
 *
 * @package WP_API_Codeia\Middleware
 */
class CORS_Manager {

    /**
     * Inicializar el CORS manager
     *
     * @return void
     */
    public function init(): void {
        // Agregar headers CORS a las respuestas de la API
        add_action('wp_api_codeia_send_response', [$this, 'add_cors_headers']);
    }

    /**
     * Obtener configuración de CORS
     *
     * @return array Configuración de CORS
     */
    public function get_config(): array {
        $default_config = [
            'enabled' => true,
            'allow_origins' => ['*'], // * o array de dominios
            'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allow_headers' => ['Authorization', 'Content-Type', 'X-WP-Nonce'],
            'expose_headers' => ['X-WP-Total', 'X-WP-TotalPages', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
            'max_age' => 86400, // 24 horas
            'allow_credentials' => false,
            'origin_regex' => false, // Usar regex para matching de orígenes
        ];

        $config = get_option('wp_api_codeia_cors_config', []);

        return array_merge($default_config, $config);
    }

    /**
     * Guardar configuración de CORS
     *
     * @param array $config Configuración
     * @return bool True si se guardó correctamente
     */
    public function save_config(array $config): bool {
        return update_option('wp_api_codeia_cors_config', $config);
    }

    /**
     * Agregar headers CORS a la respuesta
     *
     * @return void
     */
    public function add_cors_headers(): void {
        $config = $this->get_config();

        if (!$config['enabled']) {
            return;
        }

        $origin = $this->get_request_origin();

        // Verificar si el origen está permitido
        if ($this->is_origin_allowed($origin, $config)) {
            header('Access-Control-Allow-Origin: ' . $this->format_allow_origin($origin, $config));
        }

        // Allow Credentials
        if ($config['allow_credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }

        // Allow Methods
        header('Access-Control-Allow-Methods: ' . implode(', ', $config['allow_methods']));

        // Allow Headers
        if (!empty($config['allow_headers'])) {
            header('Access-Control-Allow-Headers: ' . implode(', ', $config['allow_headers']));
        }

        // Expose Headers
        if (!empty($config['expose_headers'])) {
            header('Access-Control-Expose-Headers: ' . implode(', ', $config['expose_headers']));
        }

        // Max Age
        if ($config['max_age'] > 0) {
            header('Access-Control-Max-Age: ' . (int) $config['max_age']);
        }

        // Manejar preflight request
        if ($this->is_preflight_request()) {
            $this->handle_preflight();
        }
    }

    /**
     * Obtener el origen de la solicitud
     *
     * @return string Origen o string vacío
     */
    private function get_request_origin(): string {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (empty($origin)) {
            $origin = $_SERVER['HTTP_REFERER'] ?? '';
        }

        return sanitize_text_field($origin);
    }

    /**
     * Verificar si un origen está permitido
     *
     * @param string $origin Origen a verificar
     * @param array  $config Configuración de CORS
     * @return bool True si está permitido
     */
    private function is_origin_allowed(string $origin, array $config): bool {
        if (empty($origin)) {
            return false;
        }

        $allow_origins = $config['allow_origins'] ?? [];

        // Wildcard permite todo
        if (in_array('*', $allow_origins, true)) {
            return true;
        }

        // Verificar lista de orígenes permitidos
        foreach ($allow_origins as $allowed_origin) {
            if ($config['origin_regex']) {
                // Usar regex para matching
                $pattern = '#' . str_replace('#', '\#', $allowed_origin) . '#';
                if (preg_match($pattern, $origin)) {
                    return true;
                }
            } else {
                // Matching exacto o con wildcard simple (*.example.com)
                if ($this->match_origin($allowed_origin, $origin)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Hacer match entre patrón de origen y origen real
     *
     * @param string $pattern Patrón (puede tener *.dominio.com)
     * @param string $origin Origen real
     * @return bool True si hay match
     */
    private function match_origin(string $pattern, string $origin): bool {
        if ($pattern === '*') {
            return true;
        }

        if ($pattern === $origin) {
            return true;
        }

        // Soportar *.dominio.com
        if (strpos($pattern, '*.') === 0) {
            $domain = substr($pattern, 2);
            $origin_domain = parse_url($origin, PHP_URL_HOST);

            if ($origin_domain === $domain || $this->ends_with($origin_domain, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formatar el header Access-Control-Allow-Origin
     *
     * @param string $origin Origen solicitado
     * @param array  $config Configuración
     * @return string Valor del header
     */
    private function format_allow_origin(string $origin, array $config): string {
        // Si allow_credentials es true, NO podemos usar *
        if ($config['allow_credentials']) {
            return $origin;
        }

        $allow_origins = $config['allow_origins'] ?? [];

        // Si hay un wildcard y no se requieren credenciales, usar *
        if (in_array('*', $allow_origins, true)) {
            return '*';
        }

        return $origin;
    }

    /**
     * Verificar si es una petición preflight (OPTIONS)
     *
     * @return bool True si es preflight
     */
    private function is_preflight_request(): bool {
        return isset($_SERVER['REQUEST_METHOD']) &&
               strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS' &&
               isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
    }

    /**
     * Manejar petición preflight
     *
     * @return void
     */
    private function handle_preflight(): void {
        status_header(200);
        exit;
    }

    /**
     * Verificar si un string termina con otro
     *
     * @param string $haystack String donde buscar
     * @param string $needle String a buscar
     * @return bool True si termina con needle
     */
    private function ends_with(string $haystack, string $needle): bool {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }

    /**
     * Agregar un origen permitido
     *
     * @param string $origin Origen a agregar
     * @return bool True si se agregó correctamente
     */
    public function add_allowed_origin(string $origin): bool {
        $config = $this->get_config();

        if (!in_array($origin, $config['allow_origins'], true)) {
            $config['allow_origins'][] = $origin;
            return $this->save_config($config);
        }

        return true;
    }

    /**
     * Remover un origen permitido
     *
     * @param string $origin Origen a remover
     * @return bool True si se removió correctamente
     */
    public function remove_allowed_origin(string $origin): bool {
        $config = $this->get_config();
        $index = array_search($origin, $config['allow_origins'], true);

        if ($index !== false) {
            unset($config['allow_origins'][$index]);
            $config['allow_origins'] = array_values($config['allow_origins']);
            return $this->save_config($config);
        }

        return false;
    }

    /**
     * Validar headers CORS de una petición
     *
     * @return array Array con 'valid' (bool) y 'errors' (array)
     */
    public function validate_request_headers(): array {
        $config = $this->get_config();
        $errors = [];

        // Verificar método
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array(strtoupper($method), $config['allow_methods'], true)) {
            $errors[] = 'Method not allowed: ' . $method;
        }

        // Verificar headers requeridos
        if (!empty($config['allow_headers'])) {
            $request_headers = getallheaders();
            foreach ($config['allow_headers'] as $required_header) {
                // Authorization es opcional si no hay autenticación
                if ($required_header === 'Authorization') {
                    continue;
                }
                if (!isset($request_headers[$required_header])) {
                    // Solo es error si el cliente lo envía
                    $errors[] = 'Header not supported: ' . $required_header;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Obtener configuración por defecto para desarrollo
     *
     * @return array Configuración de desarrollo
     */
    public function get_dev_config(): array {
        return [
            'enabled' => true,
            'allow_origins' => [
                'http://localhost:3000',
                'http://localhost:8080',
                'http://127.0.0.1:3000',
                'http://127.0.0.1:8080',
            ],
            'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
            'allow_headers' => ['Authorization', 'Content-Type', 'X-WP-Nonce', 'X-Requested-With'],
            'expose_headers' => ['X-WP-Total', 'X-WP-TotalPages', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
            'max_age' => 86400,
            'allow_credentials' => true,
            'origin_regex' => false,
        ];
    }
}
