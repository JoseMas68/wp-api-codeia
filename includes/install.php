<?php
/**
 * Install
 *
 * Clase para manejar la instalación, desinstalación y configuración inicial.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

/**
 * Class Install
 *
 * @package WP_API_Codeia
 */
class Install {

    /**
     * Crear tablas personalizadas en la base de datos
     *
     * @return void
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tabla de API Keys
        $table_name = $wpdb->prefix . 'api_codeia_auth_keys';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            api_key varchar(255) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL,
            scope varchar(50) NOT NULL DEFAULT 'read_write',
            last_used datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            is_revoked tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY api_key (api_key),
            KEY user_id (user_id),
            KEY is_revoked (is_revoked)
        ) $charset_collate;";

        dbDelta($sql);

        // Tabla de Logs
        $table_name = $wpdb->prefix . 'api_codeia_logs';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            api_key_id bigint(20) UNSIGNED DEFAULT NULL,
            ip varchar(45) NOT NULL,
            status_code int(3) NOT NULL,
            response_time float NOT NULL DEFAULT 0,
            memory_usage bigint(20) NOT NULL DEFAULT 0,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY endpoint (endpoint),
            KEY method (method),
            KEY user_id (user_id),
            KEY status_code (status_code),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);

        // Tabla de Rate Limits
        $table_name = $wpdb->prefix . 'api_codeia_rate_limits';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            request_count int(11) NOT NULL DEFAULT 1,
            window_start datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            window_end datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_identifier (identifier, endpoint, method, window_start),
            KEY identifier (identifier),
            KEY endpoint (endpoint),
            KEY window_end (window_end)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Eliminar tablas personalizadas
     *
     * @return void
     */
    public static function drop_tables(): void {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'api_codeia_auth_keys',
            $wpdb->prefix . 'api_codeia_logs',
            $wpdb->prefix . 'api_codeia_rate_limits',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Establecer opciones por defecto
     *
     * @return void
     */
    public static function set_default_options(): void {
        $defaults = [
            'wp_api_codeia_version' => WP_API_CODEIA_VERSION,
            'wp_api_codeia_config' => self::get_default_config(),
            'wp_api_codeia_cache_hash' => wp_generate_password(32, false),
            'wp_api_codeia_jwt_secret' => wp_generate_password(64, true),
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Eliminar opciones del plugin
     *
     * @return void
     */
    public static function delete_options(): void {
        global $wpdb;

        // Eliminar opciones individuales
        $options = [
            'wp_api_codeia_version',
            'wp_api_codeia_config',
            'wp_api_codeia_cache_hash',
            'wp_api_codeia_jwt_secret',
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        // Eliminar transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_wp_api_codeia_%'
             OR option_name LIKE '_transient_timeout_wp_api_codeia_%'"
        );
    }

    /**
     * Obtener configuración por defecto
     *
     * @return array Configuración por defecto
     */
    private static function get_default_config(): array {
        return [
            'version' => '1.0.0',
            'global_settings' => [
                'namespace' => WP_API_CODEIA_NAMESPACE,
                'base_path' => WP_API_CODEIA_BASE_PATH,
                'default_version' => 'v1',
                'supported_versions' => ['v1'],
                'enable_logs' => true,
                'log_retention_days' => 30,
                'default_rate_limit' => [
                    'requests' => 1000,
                    'window' => 'hour',
                ],
                'cors' => [
                    'enabled' => true,
                    'allowed_origins' => ['*'],
                    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
                    'allowed_headers' => ['Authorization', 'Content-Type'],
                    'max_age' => 86400,
                ],
            ],
            'endpoints' => [
                'posts' => [
                    'slug' => 'posts',
                    'name' => 'Posts',
                    'description' => 'Endpoint para publicaciones de WordPress',
                    'versions' => [
                        'v1' => [
                            'post_types' => ['post'],
                            'methods' => ['GET'],
                            'auth' => 'api_key',
                            'fields' => [
                                'include' => ['id', 'title', 'content', 'excerpt', 'date', 'link', 'author'],
                                'exclude' => [],
                            ],
                            'query_params' => [
                                'allowed' => ['page', 'per_page', 'search', 'orderby', 'order'],
                                'defaults' => [
                                    'per_page' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
                'pages' => [
                    'slug' => 'pages',
                    'name' => 'Pages',
                    'description' => 'Endpoint para páginas de WordPress',
                    'versions' => [
                        'v1' => [
                            'post_types' => ['page'],
                            'methods' => ['GET'],
                            'auth' => 'api_key',
                            'fields' => [
                                'include' => ['id', 'title', 'content', 'excerpt', 'date', 'link'],
                            ],
                        ],
                    ],
                ],
            ],
            'auth' => [
                'api_keys' => [
                    'enabled' => true,
                    'storage' => 'custom_table',
                    'key_prefix' => 'wpck_',
                    'key_length' => 32,
                ],
                'jwt' => [
                    'enabled' => false,
                    'algorithm' => 'HS256',
                    'expiration' => 3600,
                    'refresh_expiration' => 604800,
                ],
                'basic_auth' => [
                    'enabled' => true,
                    'environments' => ['development'],
                ],
                'app_passwords' => [
                    'enabled' => true,
                    'integration' => 'native',
                ],
            ],
            'permissions' => [
                'roles' => [
                    'administrator' => [
                        'endpoints' => ['*'],
                        'methods' => ['*'],
                        'fields' => ['*'],
                    ],
                    'editor' => [
                        'endpoints' => ['posts', 'pages', 'media'],
                        'methods' => ['GET', 'POST', 'PUT'],
                        'fields' => ['*'],
                    ],
                    'author' => [
                        'endpoints' => ['posts'],
                        'methods' => ['GET', 'POST', 'PUT'],
                        'fields' => ['id', 'title', 'content', 'excerpt', 'date'],
                    ],
                    'subscriber' => [
                        'endpoints' => ['posts', 'pages'],
                        'methods' => ['GET'],
                        'fields' => ['id', 'title', 'excerpt', 'date'],
                    ],
                ],
                'field_level' => [
                    'sensitive_fields' => [
                        'user_email' => ['administrator'],
                        'user_meta' => ['administrator'],
                    ],
                ],
            ],
            'media' => [
                'upload_enabled' => true,
                'allowed_mime_types' => [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                    'application/pdf',
                ],
                'max_file_size' => 5 * 1024 * 1024, // 5MB
                'auto_associate' => true,
                'image_sizes' => ['thumbnail', 'medium', 'large', 'full'],
            ],
            'documentation' => [
                'enabled' => true,
                'format' => 'openapi_3_0',
                'endpoint' => '/docs',
                'auto_update' => true,
                'ui_theme' => 'default',
            ],
            'cache' => [
                'enabled' => true,
                'storage' => 'transients',
                'default_ttl' => 300,
                'by_endpoint' => [
                    'posts' => ['GET' => 300, 'POST' => 0],
                    'pages' => ['GET' => 300, 'POST' => 0],
                ],
            ],
        ];
    }
}
