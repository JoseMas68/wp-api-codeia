<?php
/**
 * Middleware Pipeline
 *
 * Sistema de middleware para procesar requests y responses.
 *
 * @package WP_API_Codeia\Middleware
 */

namespace WP_API_Codeia\Middleware;

use WP_API_Codeia\API_Request;
use WP_API_Codeia\API_Response;

/**
 * Class Middleware_Pipeline
 *
 * @package WP_API_Codeia\Middleware
 */
class Middleware_Pipeline {

    /**
     * Middleware registrados
     *
     * @var array
     */
    private array $middlewares = [];

    /**
     * Índice actual de middleware
     *
     * @var int
     */
    private int $current_index = 0;

    /**
     * Request actual
     *
     * @var API_Request|null
     */
    private ?API_Request $request = null;

    /**
     * Handler final (endpoint manager)
     *
     * @var callable|null
     */
    private ?callable $final_handler = null;

    /**
     * Registrar un middleware
     *
     * @param string   $name Nombre del middleware
     * @param callable $handler Función handler
     * @param int      $priority Prioridad (menor = antes)
     * @return void
     */
    public function register(string $name, callable $handler, int $priority = 10): void {
        $this->middlewares[] = [
            'name' => $name,
            'handler' => $handler,
            'priority' => $priority,
        ];

        // Ordenar por prioridad
        usort($this->middlewares, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * Ejecutar el pipeline de request
     *
     * @param API_Request $request       Request a procesar
     * @param callable     $final_handler Handler final (endpoint manager)
     * @return API_Response Response procesada
     */
    public function process_request(API_Request $request, callable $final_handler): API_Response {
        $this->request = $request;
        $this->current_index = 0;
        $this->final_handler = $final_handler;

        return $this->run_next();
    }

    /**
     * Ejecutar el siguiente middleware en el pipeline
     *
     * @return API_Response
     */
    private function run_next(): API_Response {
        // Si no hay más middleware, ejecutar handler final
        if ($this->current_index >= count($this->middlewares)) {
            if ($this->final_handler) {
                return call_user_func($this->final_handler, $this->request);
            }
            return new API_Response([]);
        }

        $middleware = $this->middlewares[$this->current_index];
        $this->current_index++;

        try {
            // Ejecutar middleware
            $result = call_user_func(
                $middleware['handler'],
                $this->request,
                $this->run_next(...)
            );

            if ($result instanceof API_Response) {
                return $result;
            }

            return new API_Response($result ?? []);

        } catch (\Exception $e) {
            return new API_Response([
                'success' => false,
                'code' => 'middleware_error',
                'message' => 'Error in middleware: ' . $middleware['name'],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesar response a través de middleware de response
     *
     * @param API_Response $response Response a procesar
     * @return API_Response Response procesada
     */
    public function process_response(API_Response $response): API_Response {
        // Aplicar transforms de response
        // Por ahora, retornamos la response tal cual
        // Aquí se pueden agregar middlewares que modifiquen la response

        return $response;
    }

    /**
     * Obtener middlewares registrados
     *
     * @return array
     */
    public function get_middlewares(): array {
        return $this->middlewares;
    }

    /**
     * Remover un middleware
     *
     * @param string $name Nombre del middleware
     * @return bool True si se removió
     */
    public function remove(string $name): bool {
        foreach ($this->middlewares as $index => $middleware) {
            if ($middleware['name'] === $name) {
                unset($this->middlewares[$index]);
                $this->middlewares = array_values($this->middlewares);
                return true;
            }
        }

        return false;
    }

    /**
     * Limpiar todos los middlewares
     *
     * @return void
     */
    public function clear(): void {
        $this->middlewares = [];
        $this->current_index = 0;
    }

    /**
     * Crear pipeline con middlewares por defecto
     *
     * @return self
     */
    public static function create_default(): self {
        $pipeline = new self();

        // Middleware de Rate Limiting (prioridad 1 - primero)
        $pipeline->register('rate_limit', function($request, $next) {
            $limiter = wp_api_codeia_rate_limiter();
            $config = wp_api_codeia_config()->get_endpoint_config($request->get_endpoint(), $request->get_version());

            if ($config && isset($config['auth'])) {
                $scope = $config['auth'];
                $limits = $limiter->get_endpoint_limits($request->get_endpoint(), $scope);

                $check = $limiter->check_rate_limit(
                    $request->get_identifier(),
                    $request->get_endpoint(),
                    $limits['limit'],
                    $limits['window']
                );

                if (!$check['allowed']) {
                    return new API_Response([
                        'success' => false,
                        'code' => 'rate_limit_exceeded',
                        'message' => 'Rate limit exceeded',
                        'data' => [
                            'limit' => $check['limit'],
                            'reset' => $check['reset'],
                        ],
                    ], 429);
                }

                // Log request
                $limiter->log_request(
                    $request->get_identifier(),
                    $request->get_endpoint(),
                    $request->get_method()
                );
            }

            return $next();
        }, 1);

        // Middleware de CORS (prioridad 2)
        $pipeline->register('cors', function($request, $next) {
            $cors = wp_api_codeia_cors();

            // CORS headers se aplican en el hook wp_api_codeia_send_response
            // Aquí solo verificamos si la request es válida
            $validation = $cors->validate_request_headers();

            if (!$validation['valid']) {
                return new API_Response([
                    'success' => false,
                    'code' => 'cors_error',
                    'message' => 'CORS validation failed',
                    'errors' => $validation['errors'],
                ], 403);
            }

            return $next();
        }, 2);

        // Middleware de Autenticación (prioridad 3)
        $pipeline->register('auth', function($request, $next) {
            $auth = wp_api_codeia_auth();

            // Autenticar
            $user = $auth->authenticate(
                $request->get_endpoint(),
                $request->get_version()
            );

            if ($user === null) {
                $config = wp_api_codeia_config()->get_endpoint_config($request->get_endpoint(), $request->get_version());

                // Verificar si el endpoint requiere autenticación
                if ($config && ($config['auth'] ?? 'api_key') !== 'none') {
                    return new API_Response([
                        'success' => false,
                        'code' => 'unauthorized',
                        'message' => 'Authentication required',
                    ], 401);
                }
            }

            return $next();
        }, 3);

        // Middleware de Permissions (prioridad 4)
        $pipeline->register('permissions', function($request, $next) {
            $auth = wp_api_codeia_auth();
            $user = $auth->get_current_user();

            if ($user) {
                $auth_data = $auth->get_auth_data();
                $scope = $auth_data['scope'] ?? 'read';
                $role = $user->roles[0] ?? '';

                // Verificar permiso para el método
                $method = $request->get_method();

                if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
                    if (!$auth->current_user_can('write')) {
                        return new API_Response([
                            'success' => false,
                            'code' => 'forbidden',
                            'message' => 'Insufficient permissions',
                        ], 403);
                    }
                }
            }

            return $next();
        }, 4);

        // Middleware de Cache (prioridad 5 - para GET)
        $pipeline->register('cache', function($request, $next) {
            $cache = wp_api_codeia()->get_cache_manager();

            // Solo cache para GET
            if ($request->get_method() === 'GET') {
                $cache_key = $request->get_cache_key();

                // Intentar obtener de cache
                $cached = $cache->get($cache_key, 'endpoints');

                if ($cached !== false) {
                    return new API_Response($cached);
                }

                // Ejecutar next y cachear resultado
                $response = $next();

                if ($response->get_status() < 400) {
                    $cache->set($cache_key, $response->get_data(), 'endpoints', 900); // 15 min
                }

                return $response;
            }

            return $next();
        }, 5);

        // Middleware de Field Permissions (prioridad 6 - último)
        $pipeline->register('field_permissions', function($request, $next) {
            $response = $next();

            // Solo para GET requests exitosos
            if ($request->get_method() === 'GET' && $response->get_status() < 400) {
                $auth = wp_api_codeia_auth();
                $user = $auth->get_current_user();

                if ($user) {
                    $auth_data = $auth->get_auth_data();
                    $scope = $auth_data['scope'] ?? 'read';
                    $role = $user->roles[0] ?? '';
                    $post_type = $request->get_param('post_type') ?? 'post';

                    // Filtrar campos
                    $field_perms = wp_api_codeia_field_permissions();
                    $data = $response->get_data();

                    if (is_array($data)) {
                        if (isset($data['data']) && is_array($data['data'])) {
                            // Response con estructura de lista
                            foreach ($data['data'] as &$item) {
                                $item = $field_perms->filter_fields($item, $post_type, $role, $scope);
                            }
                        } else {
                            // Response simple
                            $data = $field_perms->filter_fields($data, $post_type, $role, $scope);
                        }

                        $response->set_data($data);
                    }
                }
            }

            return $response;
        }, 6);

        return $pipeline;
    }
}
