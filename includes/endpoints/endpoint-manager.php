<?php
/**
 * Endpoint Manager
 *
 * Gestiona los endpoints de la API.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

use WP_API_Codeia\Middleware\Middleware_Pipeline;
use WP_API_Codeia\Utils\Query_Param_Manager;

/**
 * Class Endpoint_Manager
 *
 * @package WP_API_Codeia
 */
class Endpoint_Manager {

    /**
     * Inicializar el endpoint manager
     *
     * @return void
     */
    public function init(): void {
        // Hooks de endpoints
    }

    /**
     * Procesar una request
     *
     * @param API_Request $request Request a procesar
     * @return API_Response Respuesta generada
     */
    public function process_request(API_Request $request): API_Response {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        try {
            // 1. Autenticar
            $auth_result = wp_api_codeia_auth()->authenticate(
                $request->get_endpoint(),
                $request->get_version()
            );

            if ($auth_result === null && $this->requires_auth($request)) {
                return $this->error('Autenticación requerida', 401);
            }

            // 2. Verificar permisos
            if (!$this->check_permissions($request, $auth_result)) {
                return $this->error('Permisos insuficientes', 403);
            }

            // 3. Obtener configuración del endpoint
            $endpoint_config = wp_api_codeia_config()->get_endpoint_config(
                $request->get_endpoint(),
                $request->get_version()
            );

            if ($endpoint_config === null) {
                return $this->error('Endpoint no encontrado', 404);
            }

            // 4. Procesar según método HTTP
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            switch ($method) {
                case 'GET':
                    $response = $this->handle_get($request, $endpoint_config);
                    break;

                case 'POST':
                    $response = $this->handle_post($request, $endpoint_config);
                    break;

                case 'PUT':
                    $response = $this->handle_put($request, $endpoint_config);
                    break;

                case 'DELETE':
                    $response = $this->handle_delete($request, $endpoint_config);
                    break;

                default:
                    $response = $this->error('Método no permitido', 405);
            }

            // 5. Loggear request
            $this->log_request($request, $response, $start_time, $start_memory);

            return $response;

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Manejar request GET
     *
     * @param API_Request $request          Request
     * @param array       $endpoint_config  Configuración del endpoint
     * @return API_Response Respuesta
     */
    private function handle_get(API_Request $request, array $endpoint_config): API_Response {
        $post_types = $endpoint_config['post_types'] ?? [];
        $fields = $endpoint_config['fields']['include'] ?? [];

        if ($request->get_item_id()) {
            // Obtener item individual
            return $this->get_single_item($request, $post_types, $fields);
        }

        // Obtener lista
        return $this->get_items($request, $post_types, $fields);
    }

    /**
     * Obtener item individual
     *
     * @param API_Request $request     Request
     * @param array       $post_types  Post types permitidos
     * @param array       $fields      Campos a incluir
     * @return API_Response Respuesta
     */
    private function get_single_item(API_Request $request, array $post_types, array $fields): API_Response {
        $item_id = $request->get_item_id();

        $post = get_post($item_id);

        if ($post === null || !in_array($post->post_type, $post_types, true)) {
            return $this->error('Item no encontrado', 404);
        }

        return new API_Response($this->format_post($post, $fields));
    }

    /**
     * Obtener lista de items
     *
     * @param API_Request $request     Request
     * @param array       $post_types  Post types permitidos
     * @param array       $fields      Campos a incluir
     * @return API_Response Respuesta
     */
    private function get_items(API_Request $request, array $post_types, array $fields): API_Response {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 10;

        $args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        ];

        $query = new \WP_Query($args);

        $items = [];
        foreach ($query->posts as $post) {
            $items[] = $this->format_post($post, $fields);
        }

        return new API_Response([
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $query->found_posts,
                'total_pages' => $query->max_num_pages,
            ],
        ]);
    }

    /**
     * Formatear post para respuesta
     *
     * @param \WP_Post $post   Post a formatear
     * @param array    $fields Campos a incluir
     * @return array Post formateado
     */
    private function format_post(\WP_Post $post, array $fields): array {
        $formatted = [];

        // Campos nativos básicos
        $native_fields = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'content' => apply_filters('the_content', $post->post_content),
            'excerpt' => get_the_excerpt($post),
            'date' => get_the_date('c', $post),
            'modified' => get_the_modified_date('c', $post),
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'link' => get_permalink($post),
        ];

        // Filtrar campos solicitados
        if (empty($fields) || in_array('*', $fields, true)) {
            $formatted = $native_fields;
        } else {
            foreach ($fields as $field) {
                if (isset($native_fields[$field])) {
                    $formatted[$field] = $native_fields[$field];
                }
            }
        }

        // Agregar author si solicitado
        if (in_array('author', $fields, true) || in_array('*', $fields, true)) {
            $formatted['author'] = [
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author),
            ];
        }

        return $formatted;
    }

    /**
     * Manejar request POST
     *
     * @param API_Request $request          Request
     * @param array       $endpoint_config  Configuración del endpoint
     * @return API_Response Respuesta
     */
    private function handle_post(API_Request $request, array $endpoint_config): API_Response {
        // Obtener datos del body
        $body = $request->get_body();

        if (empty($body)) {
            return $this->error('No se proporcionaron datos', 400);
        }

        $post_types = $endpoint_config['post_types'] ?? ['post'];
        $post_type = $body['post_type'] ?? $post_types[0];

        if (!in_array($post_type, $post_types, true)) {
            return $this->error('Post type no permitido', 400);
        }

        // Verificar permisos
        $auth = wp_api_codeia_auth();
        $user = $auth->get_current_user();

        if (!$user || !user_can($user, 'publish_posts')) {
            return $this->error('No tienes permiso para crear posts', 403);
        }

        // Preparar datos del post
        $post_data = [
            'post_title'   => $body['title'] ?? '',
            'post_content' => $body['content'] ?? '',
            'post_excerpt' => $body['excerpt'] ?? '',
            'post_status'  => $body['status'] ?? 'draft',
            'post_type'    => $post_type,
            'post_author'  => $user->ID,
        ];

        // Insertar post
        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $this->error($post_id->get_error_message(), 400);
        }

        // Guardar campos custom (ACF, meta, etc.)
        if (isset($body['meta']) && is_array($body['meta'])) {
            foreach ($body['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }

        // Guardar campos ACF
        if (isset($body['acf']) && is_array($body['acf']) && function_exists('update_field')) {
            foreach ($body['acf'] as $key => $value) {
                update_field($key, $value, $post_id);
            }
        }

        // Guardar términos
        if (isset($body['terms']) && is_array($body['terms'])) {
            foreach ($body['terms'] as $taxonomy => $terms) {
                wp_set_object_terms($post_id, $terms, $taxonomy);
            }
        }

        // Invalidar cache
        wp_api_codeia()->get_cache_manager()->invalidate_post_cache($post_id);

        // Retornar el post creado
        $post = get_post($post_id);
        $fields = $endpoint_config['fields']['include'] ?? [];

        return new API_Response($this->format_post($post, $fields), 201);
    }

    /**
     * Manejar request PUT
     *
     * @param API_Request $request          Request
     * @param array       $endpoint_config  Configuración del endpoint
     * @return API_Response Respuesta
     */
    private function handle_put(API_Request $request, array $endpoint_config): API_Response {
        $item_id = $request->get_item_id();

        if (!$item_id) {
            return $this->error('ID de item no proporcionado', 400);
        }

        $post = get_post($item_id);

        if ($post === null) {
            return $this->error('Post no encontrado', 404);
        }

        // Verificar permisos
        $auth = wp_api_codeia_auth();
        $user = $auth->get_current_user();

        if (!$user || !user_can($user, 'edit_post', $item_id)) {
            return $this->error('No tienes permiso para editar este post', 403);
        }

        // Obtener datos del body
        $body = $request->get_body();

        if (empty($body)) {
            return $this->error('No se proporcionaron datos', 400);
        }

        // Preparar datos del post
        $post_data = [
            'ID' => $item_id,
        ];

        if (isset($body['title'])) {
            $post_data['post_title'] = $body['title'];
        }

        if (isset($body['content'])) {
            $post_data['post_content'] = $body['content'];
        }

        if (isset($body['excerpt'])) {
            $post_data['post_excerpt'] = $body['excerpt'];
        }

        if (isset($body['status'])) {
            $post_data['post_status'] = $body['status'];
        }

        // Actualizar post
        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            return $this->error($result->get_error_message(), 400);
        }

        // Guardar campos custom (ACF, meta, etc.)
        if (isset($body['meta']) && is_array($body['meta'])) {
            foreach ($body['meta'] as $key => $value) {
                update_post_meta($item_id, $key, $value);
            }
        }

        // Guardar campos ACF
        if (isset($body['acf']) && is_array($body['acf']) && function_exists('update_field')) {
            foreach ($body['acf'] as $key => $value) {
                update_field($key, $value, $item_id);
            }
        }

        // Guardar términos
        if (isset($body['terms']) && is_array($body['terms'])) {
            foreach ($body['terms'] as $taxonomy => $terms) {
                wp_set_object_terms($item_id, $terms, $taxonomy);
            }
        }

        // Invalidar cache
        wp_api_codeia()->get_cache_manager()->invalidate_post_cache($item_id);

        // Retornar el post actualizado
        $post = get_post($item_id);
        $fields = $endpoint_config['fields']['include'] ?? [];

        return new API_Response($this->format_post($post, $fields));
    }

    /**
     * Manejar request DELETE
     *
     * @param API_Request $request          Request
     * @param array       $endpoint_config  Configuración del endpoint
     * @return API_Response Respuesta
     */
    private function handle_delete(API_Request $request, array $endpoint_config): API_Response {
        $item_id = $request->get_item_id();

        if (!$item_id) {
            return $this->error('ID de item no proporcionado', 400);
        }

        $post = get_post($item_id);

        if ($post === null) {
            return $this->error('Post no encontrado', 404);
        }

        // Verificar permisos
        $auth = wp_api_codeia_auth();
        $user = $auth->get_current_user();

        if (!$user || !user_can($user, 'delete_post', $item_id)) {
            return $this->error('No tienes permiso para eliminar este post', 403);
        }

        // Invalidar cache antes de borrar
        wp_api_codeia()->get_cache_manager()->invalidate_post_cache($item_id);

        // Eliminar post (a papelera)
        $result = wp_trash_post($item_id);

        if (!$result) {
            return $this->error('Error al eliminar el post', 500);
        }

        return new API_Response([
            'success' => true,
            'message' => 'Post movido a papelera',
            'id' => $item_id,
        ]);
    }

    /**
     * Verificar si el endpoint requiere autenticación
     *
     * @param API_Request $request Request
     * @return bool True si requiere auth
     */
    private function requires_auth(API_Request $request): bool {
        $config = wp_api_codeia_config()->get_endpoint_config(
            $request->get_endpoint(),
            $request->get_version()
        );

        return ($config['auth'] ?? 'api_key') !== 'none';
    }

    /**
     * Verificar permisos
     *
     * @param API_Request     $request       Request
     * @param array|null      $auth_result   Resultado de autenticación
     * @return bool True si tiene permisos
     */
    private function check_permissions(API_Request $request, ?array $auth_result): bool {
        // TODO: Implementar verificación completa de permisos
        return true;
    }

    /**
     * Crear respuesta de error
     *
     * @param string $message Mensaje de error
     * @param int    $status  Código HTTP
     * @return API_Response Respuesta de error
     */
    private function error(string $message, int $status): API_Response {
        return new API_Response([
            'error' => true,
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    /**
     * Loggear request
     *
     * @param API_Request  $request       Request
     * @param API_Response $response      Respuesta
     * @param float        $start_time    Tiempo de inicio
     * @param int          $start_memory  Memoria de inicio
     * @return void
     */
    private function log_request(API_Request $request, API_Response $response, float $start_time, int $start_memory): void {
        if (!wp_api_codeia_config()->is_logging_enabled()) {
            return;
        }

        // TODO: Implementar logging completo
        wp_api_codeia_debug_log(sprintf(
            'Request: %s %s/%s - Status: %d - Time: %.4fs',
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $request->get_version(),
            $request->get_endpoint(),
            $response->get_status(),
            microtime(true) - $start_time
        ));
    }
}

/**
 * Class API_Request
 *
 * @package WP_API_Codeia
 */
class API_Request {

    private string $version;
    private string $endpoint;
    private ?string $item_id;
    private ?array $body = null;
    private ?array $params = null;

    public function __construct(string $version, string $endpoint, ?string $item_id = null) {
        $this->version = $version;
        $this->endpoint = $endpoint;
        $this->item_id = $item_id;
    }

    public function get_version(): string {
        return $this->version;
    }

    public function get_endpoint(): string {
        return $this->endpoint;
    }

    public function get_item_id(): ?string {
        return $this->item_id;
    }

    /**
     * Obtener método HTTP
     *
     * @return string
     */
    public function get_method(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Obtener identificador único para rate limiting
     *
     * @return string
     */
    public function get_identifier(): string {
        $auth = wp_api_codeia_auth();
        $user = $auth->get_current_user();

        if ($user) {
            return 'user_' . $user->ID;
        }

        $auth_data = $auth->get_auth_data();
        if ($auth_data && isset($auth_data['key_id'])) {
            return 'key_' . $auth_data['key_id'];
        }

        return 'ip_' . wp_api_codeia_get_client_ip();
    }

    /**
     * Obtener clave de cache
     *
     * @return string
     */
    public function get_cache_key(): string {
        $key_parts = [
            $this->endpoint,
            $this->version,
            $this->get_method(),
        ];

        if ($this->item_id) {
            $key_parts[] = $this->item_id;
        }

        $params = $this->get_params();
        if (isset($params['page'])) {
            $key_parts[] = 'p' . $params['page'];
        }
        if (isset($params['per_page'])) {
            $key_parts[] = 'pp' . $params['per_page'];
        }

        return implode('_', $key_parts);
    }

    /**
     * Obtener parámetros de la request
     *
     * @return array
     */
    public function get_params(): array {
        if ($this->params === null) {
            $this->params = $_GET + $_POST;
        }

        return $this->params;
    }

    /**
     * Obtener un parámetro específico
     *
     * @param string     $key     Clave del parámetro
     * @param mixed|null $default Valor por defecto
     * @return mixed
     */
    public function get_param(string $key, $default = null) {
        $params = $this->get_params();

        return $params[$key] ?? $default;
    }

    /**
     * Obtener body de la request (para POST/PUT)
     *
     * @return array
     */
    public function get_body(): array {
        if ($this->body === null) {
            $raw_body = file_get_contents('php://input');
            $this->body = json_decode($raw_body, true) ?? [];

            if (empty($this->body) && !empty($_POST)) {
                $this->body = $_POST;
            }
        }

        return $this->body;
    }
}

/**
 * Class API_Response
 *
 * @package WP_API_Codeia
 */
class API_Response {

    private $data;
    private int $status;
    private array $headers;

    public function __construct($data, int $status = 200, array $headers = []) {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function send(): void {
        status_header($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        header('Content-Type: application/json');

        echo wp_json_encode($this->data);
        exit;
    }

    public function get_status(): int {
        return $this->status;
    }

    public function get_data() {
        return $this->data;
    }

    /**
     * Establecer datos de la respuesta
     *
     * @param mixed $data Nuevos datos
     * @return void
     */
    public function set_data($data): void {
        $this->data = $data;
    }
}
