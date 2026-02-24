<?php
/**
 * Admin Page
 *
 * Página principal de administración del plugin.
 *
 * @package WP_API_Codeia\Admin
 */

namespace WP_API_Codeia\Admin;

/**
 * Class Admin_Page
 *
 * @package WP_API_Codeia\Admin
 */
class Admin_Page {

    /**
     * Inicializar la página de admin
     *
     * @return void
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Agregar menú de administración
     *
     * @return void
     */
    public function add_menu(): void {
        // Menú principal
        add_menu_page(
            'WP API Codeia',
            'WP API Codeia',
            'manage_options',
            'wp-api-codeia',
            [$this, 'render_dashboard'],
            'dashicons-rest-api',
            30
        );

        // Submenú: Dashboard
        add_submenu_page(
            'wp-api-codeia',
            __('Dashboard', 'wp-api-codeia'),
            __('Dashboard', 'wp-api-codeia'),
            'manage_options',
            'wp-api-codeia',
            [$this, 'render_dashboard']
        );

        // Submenú: Endpoints
        add_submenu_page(
            'wp-api-codeia',
            __('Endpoints', 'wp-api-codeia'),
            __('Endpoints', 'wp-api-codeia'),
            'manage_options',
            'wp-api-codeia-endpoints',
            [$this, 'render_endpoints']
        );

        // Submenú: API Keys
        add_submenu_page(
            'wp-api-codeia',
            __('API Keys', 'wp-api-codeia'),
            __('API Keys', 'wp-api-codeia'),
            'manage_options',
            'wp-api-codeia-keys',
            [$this, 'render_api_keys']
        );

        // Submenú: Rate Limiting
        add_submenu_page(
            'wp-api-codeia',
            __('Rate Limiting', 'wp-api-codeia'),
            __('Rate Limiting', 'wp-api-codeia'),
            'manage_options',
            'wp-api-codeia-rate-limit',
            [$this, 'render_rate_limit']
        );

        // Submenú: Logs
        add_submenu_page(
            'wp-api-codeia',
            __('Logs', 'wp-api-codeia'),
            __('Logs', 'wp-api-codeia'),
            'manage_options',
            'wp-api-codeia-logs',
            [$this, 'render_logs']
        );

        // Submenú: Settings
        add_submenu_page(
            'wp-api-codeia',
            __('Settings', 'wp-api-codeia'),
            __('Settings', 'wp-api-codeia'),
            'manage_options',
            'wp-api-codeia-settings',
            [$this, 'render_settings']
        );
    }

    /**
     * Enqueue scripts y styles
     *
     * @param string $hook_suffix Hook suffix de la página actual
     * @return void
     */
    public function enqueue_scripts(string $hook_suffix): void {
        // Solo cargar en páginas de nuestro plugin
        if (strpos($hook_suffix, 'wp-api-codeia') === false) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'wp-api-codeia-admin',
            WP_API_CODEIA_ASSETS_URL . 'css/admin.css',
            [],
            WP_API_CODEIA_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'wp-api-codeia-admin',
            WP_API_CODEIA_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            WP_API_CODEIA_VERSION,
            true
        );

        // Localizar script
        wp_localize_script('wp-api-codeia-admin', 'wpApiCodeia', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-api-codeia'),
            'restUrl' => rest_url('wp-api-codeia/v1/'),
        ]);
    }

    /**
     * Renderizar Dashboard
     *
     * @return void
     */
    public function render_dashboard(): void {
        $stats = $this->get_dashboard_stats();

        include WP_API_CODEIA_PLUGIN_DIR . 'templates/dashboard-page.php';
    }

    /**
     * Renderizar página de Endpoints
     *
     * @return void
     */
    public function render_endpoints(): void {
        $endpoints = wp_api_codeia_config()->get_endpoints();

        include WP_API_CODEIA_PLUGIN_DIR . 'templates/endpoints-page.php';
    }

    /**
     * Renderizar página de API Keys
     *
     * @return void
     */
    public function render_api_keys(): void {
        $user_id = get_current_user_id();
        $repository = new \WP_API_Codeia\Repositories\Auth_Key_Repository();
        $api_keys = $repository->get_user_keys($user_id);

        include WP_API_CODEIA_PLUGIN_DIR . 'templates/api-keys-page.php';
    }

    /**
     * Renderizar página de Rate Limiting
     *
     * @return void
     */
    public function render_rate_limit(): void {
        $limiter = wp_api_codeia_rate_limiter();
        $active_ids = $limiter->get_active_identifiers(100);

        include WP_API_CODEIA_PLUGIN_DIR . 'templates/rate-limit-page.php';
    }

    /**
     * Renderizar página de Logs
     *
     * @return void
     */
    public function render_logs(): void {
        $logger = wp_api_codeia()->get_request_logger();
        $logs = $logger->get_logs(['limit' => 100]);

        include WP_API_CODEIA_PLUGIN_DIR . 'templates/logs-page.php';
    }

    /**
     * Renderizar página de Settings
     *
     * @return void
     */
    public function render_settings(): void {
        $config = wp_api_codeia_config()->get_config();
        $cors_config = wp_api_codeia_cors()->get_config();

        include WP_API_CODEIA_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Obtener estadísticas del dashboard
     *
     * @return array
     */
    private function get_dashboard_stats(): array {
        global $wpdb;

        // Stats de requests
        $table_name = $wpdb->prefix . 'api_codeia_logs';
        $total_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $requests_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE requested_at > %s",
            date('Y-m-d 00:00:00')
        ));

        // Stats de API keys
        $table_keys = $wpdb->prefix . 'api_codeia_auth_keys';
        $active_keys = $wpdb->get_var("SELECT COUNT(*) FROM {$table_keys} WHERE is_revoked = 0");

        // Stats de endpoints
        $endpoints = wp_api_codeia_config()->get_endpoints();
        $endpoint_count = count($endpoints);

        // Stats de rate limiting
        $table_rate = $wpdb->prefix . 'api_codeia_rate_limits';
        $rate_hits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_rate} WHERE requested_at > %s",
            date('Y-m-d 00:00:00')
        ));

        // Top endpoints
        $top_endpoints = $wpdb->get_results($wpdb->prepare(
            "SELECT endpoint, COUNT(*) as request_count
            FROM {$table_name}
            WHERE requested_at > %s
            GROUP BY endpoint
            ORDER BY request_count DESC
            LIMIT 5",
            date('Y-m-d 00:00:00', strtotime('-7 days'))
        ));

        return [
            'total_requests' => (int) $total_requests,
            'requests_today' => (int) $requests_today,
            'active_keys' => (int) $active_keys,
            'endpoint_count' => $endpoint_count,
            'rate_hits_today' => (int) $rate_hits,
            'top_endpoints' => $top_endpoints,
        ];
    }
}
