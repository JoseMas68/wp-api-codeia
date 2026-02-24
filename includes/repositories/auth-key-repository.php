<?php
/**
 * Auth Key Repository
 *
 * Repositorio para gestionar API Keys en la base de datos.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Repositories;

/**
 * Class Auth_Key_Repository
 *
 * @package WP_API_Codeia\Repositories
 */
class Auth_Key_Repository {

    /**
     * Nombre de la tabla
     *
     * @var string
     */
    private string $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'api_codeia_auth_keys';
    }

    /**
     * Crear una nueva API Key
     *
     * @param int    $user_id ID del usuario
     * @param string $name    Nombre descriptivo de la key
     * @param string $scope    Scope de permisos (read, write, read_write, admin)
     * @param int    $expires  Timestamp de expiración (0 para no expirar)
     * @return string|false API Key creada o false si falló
     */
    public function create(int $user_id, string $name, string $scope = 'read_write', int $expires = 0) {
        global $wpdb;

        // Generar API key
        $prefix = wp_api_codeia_config()->get('auth.api_keys.key_prefix', 'wpck_');
        $raw_key = $prefix . wp_api_codeia_random_string(32);
        $hashed_key = password_hash($raw_key, PASSWORD_DEFAULT);

        // Datos de la key
        $data = [
            'api_key' => $hashed_key,
            'user_id' => $user_id,
            'name' => $name,
            'scope' => $this->sanitize_scope($scope),
            'expires_at' => $expires > 0 ? date('Y-m-d H:i:s', $expires) : null,
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($this->table_name, $data);

        if ($result === false) {
            return false;
        }

        // Retornar la key sin hashear (solo se muestra una vez)
        return $raw_key;
    }

    /**
     * Crear API key por defecto para un usuario
     *
     * @param int $user_id ID del usuario
     * @return bool|string API key o false si falló
     */
    public static function create_default_key(int $user_id) {
        $repository = new self();

        // Verificar si ya tiene una key por defecto
        $existing = $repository->get_user_keys($user_id);
        if (!empty($existing)) {
            return false;
        }

        return $repository->create(
            $user_id,
            sprintf('Default Key - %s', wp_get_current_user()->display_name),
            'read_write',
            0 // No expira
        );
    }

    /**
     * Validar una API Key
     *
     * @param string $raw_key API key a validar
     * @return array|false Datos de la key o false si inválida
     */
    public function validate(string $raw_key) {
        global $wpdb;

        // Obtener todas las keys activas
        $keys = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, api_key, user_id, name, scope, expires_at, is_revoked
                FROM {$this->table_name}
                WHERE is_revoked = 0
                AND (expires_at IS NULL OR expires_at > NOW())"
            )
        );

        // Buscar key que coincida
        foreach ($keys as $key) {
            if (password_verify($raw_key, $key->api_key)) {
                // Actualizar last_used
                $wpdb->update(
                    $this->table_name,
                    ['last_used' => current_time('mysql')],
                    ['id' => $key->id],
                    ['%s'],
                    ['%d']
                );

                return [
                    'id' => $key->id,
                    'user_id' => $key->user_id,
                    'name' => $key->name,
                    'scope' => $key->scope,
                ];
            }
        }

        return false;
    }

    /**
     * Obtener todas las keys de un usuario
     *
     * @param int $user_id ID del usuario
     * @return array Keys del usuario
     */
    public function get_user_keys(int $user_id): array {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name, scope, last_used, expires_at, is_revoked, created_at
                FROM {$this->table_name}
                WHERE user_id = %d
                ORDER BY created_at DESC",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Sanitizar scope de permisos
     *
     * @param string $scope Scope a sanitizar
     * @return string Scope sanitizado
     */
    private function sanitize_scope(string $scope): string {
        $allowed_scopes = ['read', 'write', 'read_write', 'admin'];

        if (in_array($scope, $allowed_scopes, true)) {
            return $scope;
        }

        return 'read_write';
    }
}
