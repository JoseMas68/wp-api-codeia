<?php
/**
 * AJAX Handlers
 *
 * Maneja las peticiones AJAX del admin.
 *
 * @package WP_API_Codeia\Admin
 */

namespace WP_API_Codeia\Admin;

use WP_API_Codeia\Repositories\Auth_Key_Repository;

/**
 * Class AJAX_Handlers
 *
 * @package WP_API_Codeia\Admin
 */
class AJAX_Handlers {

    /**
     * Inicializar handlers AJAX
     *
     * @return void
     */
    public function init(): void {
        // Crear API Key
        add_action('wp_ajax_wp_api_codeia_create_key', [$this, 'create_key']);
        add_action('wp_ajax_wp_api_codeia_revoke_key', [$this, 'revoke_key']);
        add_action('wp_ajax_wp_api_codeia_delete_key', [$this, 'delete_key']);

        // Clear cache
        add_action('wp_ajax_wp_api_codeia_clear_cache', [$this, 'clear_cache']);

        // Exportar/Importar config
        add_action('wp_ajax_wp_api_codeia_export_config', [$this, 'export_config']);
        add_action('wp_ajax_wp_api_codeia_import_config', [$this, 'import_config']);

        // Logs
        add_action('wp_ajax_wp_api_codeia_export_logs', [$this, 'export_logs']);
        add_action('wp_ajax_wp_api_codeia_clear_logs', [$this, 'clear_logs']);

        // Rate limiting
        add_action('wp_ajax_wp_api_codeia_unblock_ip', [$this, 'unblock_ip']);
    }

    /**
     * Exportar logs a CSV vía AJAX
     *
     * @return void
     */
    public function export_logs(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $logger = wp_api_codeia()->get_request_logger();
        $logs = $logger->get_logs(['limit' => 10000]);

        // Generar CSV
        $csv = "Time,Method,Endpoint,Status,Duration,User,IP\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $log['requested_at'],
                $log['method'],
                $log['endpoint'],
                $log['response_status'],
                $log['duration'],
                $log['user_id'] ?? '',
                $log['ip_address'] ?? ''
            );
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="wp-api-codeia-logs-' . date('Y-m-d') . '.csv"');
        header('Content-Length: ' . strlen($csv));

        echo $csv;
        exit;
    }

    /**
     * Limpiar logs vía AJAX
     *
     * @return void
     */
    public function clear_logs(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'api_codeia_logs';

        $wpdb->query("TRUNCATE TABLE {$table_name}");

        wp_send_json_success([
            'success' => true,
            'message' => 'Logs cleared successfully',
        ]);
    }

    /**
     * Desbloquear IP vía AJAX
     *
     * @return void
     */
    public function unblock_ip(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $ip = sanitize_text_field($_POST['ip'] ?? '');

        if (empty($ip)) {
            wp_send_json_error(['message' => 'Invalid IP address']);
        }

        $limiter = wp_api_codeia_rate_limiter();
        $result = $limiter->unblock_ip($ip);

        if ($result) {
            wp_send_json_success([
                'success' => true,
                'message' => 'IP unblocked successfully',
            ]);
        } else {
            wp_send_json_error(['message' => 'Error unblocking IP']);
        }
    }

    /**
     * Crear nueva API Key vía AJAX
     *
     * @return void
     */
    public function create_key(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $name = sanitize_text_field($_POST['key_name'] ?? '');
        $scope = sanitize_text_field($_POST['key_scope'] ?? 'read_write');
        $expires = intval($_POST['key_expires'] ?? 0);

        if (empty($name)) {
            wp_send_json_error(['message' => 'Key name is required']);
        }

        $repository = new Auth_Key_Repository();
        $user_id = get_current_user_id();

        // Calcular fecha de expiración
        $expires_at = 0;
        if ($expires > 0) {
            $expires_at = time() + ($expires * DAY_IN_SECONDS);
        }

        $raw_key = $repository->create($user_id, $name, $scope, $expires_at);

        if (!$raw_key) {
            wp_send_json_error(['message' => 'Error creating API key']);
        }

        wp_send_json_success([
            'success' => true,
            'message' => 'API Key created successfully',
            'data' => [
                'key' => $raw_key,
                'name' => $name,
                'scope' => $scope,
            ],
        ]);
    }

    /**
     * Revocar API Key vía AJAX
     *
     * @return void
     */
    public function revoke_key(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $key_id = intval($_POST['key_id'] ?? 0);

        if (!$key_id) {
            wp_send_json_error(['message' => 'Invalid key ID']);
        }

        $repository = new Auth_Key_Repository();
        $result = $repository->revoke($key_id);

        if ($result) {
            wp_send_json_success([
                'success' => true,
                'message' => 'API Key revoked successfully',
            ]);
        } else {
            wp_send_json_error(['message' => 'Error revoking API key']);
        }
    }

    /**
     * Eliminar API Key vía AJAX
     *
     * @return void
     */
    public function delete_key(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $key_id = intval($_POST['key_id'] ?? 0);

        if (!$key_id) {
            wp_send_json_error(['message' => 'Invalid key ID']);
        }

        $repository = new Auth_Key_Repository();
        $result = $repository->delete($key_id);

        if ($result) {
            wp_send_json_success([
                'success' => true,
                'message' => 'API Key deleted successfully',
            ]);
        } else {
            wp_send_json_error(['message' => 'Error deleting API key']);
        }
    }

    /**
     * Limpiar todo el cache vía AJAX
     *
     * @return void
     */
    public function clear_cache(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        // Limpiar cache del plugin
        wp_api_codeia()->get_cache_manager()->flush_all();

        // Limpiar transients
        wp_api_codeia_clear_all_transients();

        // Limpiar object cache si está disponible
        if (wp_using_ext_object_cache()) {
            wp_cache_flush();
        }

        wp_send_json_success([
            'success' => true,
            'message' => 'Cache cleared successfully',
        ]);
    }

    /**
     * Exportar configuración vía AJAX
     *
     * @return void
     */
    public function export_config(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $config = wp_api_codeia_config()->get_config();
        $cors_config = wp_api_codeia_cors()->get_config();
        $field_perms = wp_api_codeia_field_permissions()->export_config();

        $export_data = [
            'version' => WP_API_CODEIA_VERSION,
            'exported_at' => current_time('mysql'),
            'config' => $config,
            'cors' => $cors_config,
            'field_permissions' => $field_perms,
        ];

        wp_send_json_success([
            'success' => true,
            'data' => $export_data,
        ]);
    }

    /**
     * Importar configuración vía AJAX
     *
     * @return void
     */
    public function import_config(): void {
        check_ajax_referer('wp_api_codeia', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $config_data = json_decode(stripslashes($_POST['config'] ?? '{}'), true);

        if (!$config_data) {
            wp_send_json_error(['message' => 'Invalid configuration data']);
        }

        // Importar configuración principal
        if (isset($config_data['config'])) {
            wp_api_codeia_config()->save_config($config_data['config']);
        }

        // Importar configuración CORS
        if (isset($config_data['cors'])) {
            wp_api_codeia_cors()->save_config($config_data['cors']);
        }

        // Importar permisos de campo
        if (isset($config_data['field_permissions'])) {
            wp_api_codeia_field_permissions()->import_config($config_data['field_permissions']);
        }

        // Limpiar cache
        wp_api_codeia()->get_cache_manager()->flush_all();

        wp_send_json_success([
            'success' => true,
            'message' => 'Configuration imported successfully',
        ]);
    }
}
