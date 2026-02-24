<?php
/**
 * Bootstrap
 *
 * Clase principal que inicializa todos los componentes del plugin.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

use WP_API_Codeia\Middleware\Middleware_Pipeline;
use WP_API_Codeia\Middleware\CORS_Manager;

/**
 * Class Bootstrap
 *
 * @package WP_API_Codeia
 */
class Bootstrap {

    /**
     * Instancia única de la clase (Singleton)
     *
     * @var Bootstrap|null
     */
    private static ?Bootstrap $instance = null;

    /**
     * Instancia del Configuration Manager
     *
     * @var Config_Manager|null
     */
    private ?Config_Manager $config_manager = null;

    /**
     * Instancia del Detector Manager
     *
     * @var Detector_Manager|null
     */
    private ?Detector_Manager $detector_manager = null;

    /**
     * Instancia del Endpoint Manager
     *
     * @var Endpoint_Manager|null
     */
    private ?Endpoint_Manager $endpoint_manager = null;

    /**
     * Instancia del Auth Manager
     *
     * @var Auth_Manager|null
     */
    private ?Auth_Manager $auth_manager = null;

    /**
     * Instancia del Permission Manager
     *
     * @var Permission_Manager|null
     */
    private ?Permission_Manager $permission_manager = null;

    /**
     * Instancia del Documentation Generator
     *
     * @var Documentation_Generator|null
     */
    private ?Documentation_Generator $docs_generator = null;

    /**
     * Instancia del Cache Manager
     *
     * @var Cache_Manager|null
     */
    private ?Cache_Manager $cache_manager = null;

    /**
     * Instancia del Request Logger
     *
     * @var Request_Logger|null
     */
    private ?Request_Logger $request_logger = null;

    /**
     * Instancia del Middleware Pipeline
     *
     * @var Middleware_Pipeline|null
     */
    private ?Middleware_Pipeline $middleware_pipeline = null;

    /**
     * Instancia del CORS Manager
     *
     * @var CORS_Manager|null
     */
    private ?CORS_Manager $cors_manager = null;

    /**
     * Constructor privado (Singleton pattern)
     */
    private function __construct() {
        // Privado para prevenir instanciación directa
    }

    /**
     * Obtener la instancia única de la clase
     *
     * @return Bootstrap
     */
    public static function get_instance(): Bootstrap {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Inicializar todos los componentes del plugin
     *
     * @return void
     */
    public function init(): void {
        // Cargar text domain para internacionalización
        $this->load_textdomain();

        // Inicializar Configuration Manager primero
        $this->init_config_manager();

        // Inicializar Cache Manager
        $this->init_cache_manager();

        // Inicializar Detector Manager
        $this->init_detector_manager();

        // Inicializar Auth Manager
        $this->init_auth_manager();

        // Inicializar Permission Manager
        $this->init_permission_manager();

        // Inicializar Endpoint Manager
        $this->init_endpoint_manager();

        // Inicializar Documentation Generator
        $this->init_documentation_generator();

        // Inicializar Request Logger
        $this->init_request_logger();

        // Inicializar Middleware Pipeline
        $this->init_middleware_pipeline();

        // Inicializar CORS Manager
        $this->init_cors_manager();

        // Inicializar Admin Dashboard
        if (is_admin()) {
            $this->init_admin();
        }

        // Registrar hooks principales
        $this->register_hooks();
    }

    /**
     * Cargar el text domain del plugin
     *
     * @return void
     */
    private function load_textdomain(): void {
        load_plugin_textdomain(
            'wp-api-codeia',
            false,
            dirname(WP_API_CODEIA_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Inicializar el Configuration Manager
     *
     * @return void
     */
    private function init_config_manager(): void {
        $this->config_manager = new Config_Manager();
        $this->config_manager->init();
    }

    /**
     * Inicializar el Cache Manager
     *
     * @return void
     */
    private function init_cache_manager(): void {
        $this->cache_manager = new Cache_Manager();
        $this->cache_manager->init();
    }

    /**
     * Inicializar el Detector Manager
     *
     * @return void
     */
    private function init_detector_manager(): void {
        $this->detector_manager = new Detector_Manager();
        $this->detector_manager->init();
    }

    /**
     * Inicializar el Auth Manager
     *
     * @return void
     */
    private function init_auth_manager(): void {
        $this->auth_manager = new Auth_Manager();
        $this->auth_manager->init();
    }

    /**
     * Inicializar el Permission Manager
     *
     * @return void
     */
    private function init_permission_manager(): void {
        $this->permission_manager = new Permission_Manager();
        $this->permission_manager->init();
    }

    /**
     * Inicializar el Endpoint Manager
     *
     * @return void
     */
    private function init_endpoint_manager(): void {
        $this->endpoint_manager = new Endpoint_Manager();
        $this->endpoint_manager->init();
    }

    /**
     * Inicializar el Documentation Generator
     *
     * @return void
     */
    private function init_documentation_generator(): void {
        $this->docs_generator = new Documentation_Generator();
        $this->docs_generator->init();
    }

    /**
     * Inicializar el Request Logger
     *
     * @return void
     */
    private function init_request_logger(): void {
        $this->request_logger = new Request_Logger();
        $this->request_logger->init();
    }

    /**
     * Inicializar el Middleware Pipeline
     *
     * @return void
     */
    private function init_middleware_pipeline(): void {
        $this->middleware_pipeline = Middleware_Pipeline::create_default();
    }

    /**
     * Inicializar el CORS Manager
     *
     * @return void
     */
    private function init_cors_manager(): void {
        $this->cors_manager = new CORS_Manager();
        $this->cors_manager->init();

        // Agregar action para enviar headers CORS
        add_action('wp_api_codeia_send_response', [$this->cors_manager, 'add_cors_headers']);
    }

    /**
     * Inicializar el Admin Dashboard
     *
     * @return void
     */
    private function init_admin(): void {
        $admin = new Admin\Dashboard();
        $admin->init();
    }

    /**
     * Registrar hooks principales del plugin
     *
     * @return void
     */
    private function register_hooks(): void {
        // Registrar query vars para routing
        add_filter('query_vars', [$this, 'register_query_vars']);

        // Registrar rewrite rules
        add_filter('rewrite_rules_array', [$this, 'register_rewrite_rules']);

        // Manejar requests a la API
        add_action('parse_request', [$this, 'handle_api_request']);

        // Invalidar cache al guardar posts
        add_action('save_post', [$this->cache_manager, 'invalidate_post_cache']);
        add_action('delete_post', [$this->cache_manager, 'invalidate_post_cache']);

        // Invalidar cache de campos al cambiar ACF
        if (class_exists('ACF')) {
            add_action('acf/update_value', [$this->cache_manager, 'invalidate_field_cache'], 10, 3);
        }
    }

    /**
     * Registrar query vars personalizados
     *
     * @param array $query_vars Query vars existentes
     * @return array Query vars modificados
     */
    public function register_query_vars(array $query_vars): array {
        $query_vars[] = 'wp_api_codeia';
        $query_vars[] = 'wp_api_codeia_version';
        $query_vars[] = 'wp_api_codeia_endpoint';
        $query_vars[] = 'wp_api_codeia_id';

        return $query_vars;
    }

    /**
     * Registrar rewrite rules para la API
     *
     * @param array $rules Reglas existentes
     * @return array Reglas modificadas
     */
    public function register_rewrite_rules(array $rules): array {
        $new_rules = [];
        $endpoints = $this->config_manager->get_endpoints();

        foreach ($endpoints as $endpoint_slug => $endpoint_config) {
            $versions = array_keys($endpoint_config['versions'] ?? ['v1' => []]);

            foreach ($versions as $version) {
                $base = WP_API_CODEIA_BASE_PATH . '/' . $version;

                // Regla para listar
                $new_rules[$base . '/' . $endpoint_slug . '/?$'] =
                    'index.php?wp_api_codeia=1&wp_api_codeia_version=' . $version .
                    '&wp_api_codeia_endpoint=' . $endpoint_slug;

                // Regla para item individual
                $new_rules[$base . '/' . $endpoint_slug . '/([^/]+)/?$'] =
                    'index.php?wp_api_codeia=1&wp_api_codeia_version=' . $version .
                    '&wp_api_codeia_endpoint=' . $endpoint_slug .
                    '&wp_api_codeia_id=$matches[1]';
            }
        }

        // Agregar reglas al inicio del array (mayor prioridad)
        return $new_rules + $rules;
    }

    /**
     * Manejar requests a la API
     *
     * @param \WP $wp Instancia de WP
     * @return void
     */
    public function handle_api_request(\WP $wp): void {
        // Verificar si es una request a nuestra API
        if (!isset($wp->query_vars['wp_api_codeia']) || $wp->query_vars['wp_api_codeia'] != '1') {
            return;
        }

        $version = $wp->query_vars['wp_api_codeia_version'] ?? 'v1';
        $endpoint_slug = $wp->query_vars['wp_api_codeia_endpoint'] ?? '';
        $item_id = $wp->query_vars['wp_api_codeia_id'] ?? null;

        if (empty($endpoint_slug)) {
            $this->send_error('Endpoint no especificado', 404);
        }

        try {
            // Crear la request
            $request = new API_Request($version, $endpoint_slug, $item_id);

            // Procesar a través del middleware pipeline
            // El endpoint manager es el handler final
            $response = $this->middleware_pipeline->process_request(
                $request,
                [$this->endpoint_manager, 'process_request']
            );

            // Aplicar headers CORS a la response
            do_action('wp_api_codeia_send_response');

            // Enviar respuesta
            $response->send();

        } catch (\Exception $e) {
            $this->send_error($e->getMessage(), $e->getCode() ?: 500);
        }

        // Prevenir que WordPress continúe procesando
        exit;
    }

    /**
     * Enviar error como JSON
     *
     * @param string $message Mensaje de error
     * @param int    $status  Código HTTP
     * @return void
     */
    private function send_error(string $message, int $status = 400): void {
        status_header($status);
        header('Content-Type: application/json');
        echo wp_json_encode([
            'error' => true,
            'message' => $message,
            'status' => $status,
        ]);
        exit;
    }

    /**
     * Obtener el Configuration Manager
     *
     * @return Config_Manager
     */
    public function get_config_manager(): Config_Manager {
        return $this->config_manager;
    }

    /**
     * Obtener el Detector Manager
     *
     * @return Detector_Manager
     */
    public function get_detector_manager(): Detector_Manager {
        return $this->detector_manager;
    }

    /**
     * Obtener el Endpoint Manager
     *
     * @return Endpoint_Manager
     */
    public function get_endpoint_manager(): Endpoint_Manager {
        return $this->endpoint_manager;
    }

    /**
     * Obtener el Auth Manager
     *
     * @return Auth_Manager
     */
    public function get_auth_manager(): Auth_Manager {
        return $this->auth_manager;
    }

    /**
     * Obtener el Permission Manager
     *
     * @return Permission_Manager
     */
    public function get_permission_manager(): Permission_Manager {
        return $this->permission_manager;
    }

    /**
     * Obtener el Documentation Generator
     *
     * @return Documentation_Generator
     */
    public function get_docs_generator(): Documentation_Generator {
        return $this->docs_generator;
    }

    /**
     * Obtener el Cache Manager
     *
     * @return Cache_Manager
     */
    public function get_cache_manager(): Cache_Manager {
        return $this->cache_manager;
    }

    /**
     * Obtener el Request Logger
     *
     * @return Request_Logger
     */
    public function get_request_logger(): Request_Logger {
        return $this->request_logger;
    }

    /**
     * Obtener el Middleware Pipeline
     *
     * @return Middleware_Pipeline
     */
    public function get_middleware_pipeline(): Middleware_Pipeline {
        return $this->middleware_pipeline ?? Middleware_Pipeline::create_default();
    }

    /**
     * Obtener el CORS Manager
     *
     * @return CORS_Manager
     */
    public function get_cors_manager(): CORS_Manager {
        return $this->cors_manager ?? new CORS_Manager();
    }
}
