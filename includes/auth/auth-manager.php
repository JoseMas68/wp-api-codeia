<?php
/**
 * Auth Manager
 *
 * Gestiona la autenticación de la API.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

use WP_API_Codeia\Repositories\Auth_Key_Repository;

/**
 * Class Auth_Manager
 *
 * @package WP_API_Codeia
 */
class Auth_Manager {

    /**
     * Usuario autenticado actual
     *
     * @var \WP_User|null
     */
    private ?\WP_User $current_user = null;

    /**
     * Datos de autenticación actual
     *
     * @var array|null
     */
    private ?array $auth_data = null;

    /**
     * Inicializar el auth manager
     *
     * @return void
     */
    public function init(): void {
        // Hook para determinar autenticación
        add_filter('wp_api_codeia_determine_auth', [$this, 'determine_auth'], 10, 2);
    }

    /**
     * Determinar método de autenticación y autenticar
     *
     * @param string          $endpoint_slug Slug del endpoint
     * @param string          $version       Versión de la API
     * @return \WP_User|null Usuario autenticado o null
     */
    public function authenticate(string $endpoint_slug, string $version): ?\WP_User {
        // Obtener configuración del endpoint
        $endpoint_config = wp_api_codeia_config()->get_endpoint_config($endpoint_slug, $version);

        if ($endpoint_config === null) {
            return null;
        }

        $auth_method = $endpoint_config['auth'] ?? 'api_key';

        // Aplicar filtro para determinar auth
        $auth_result = apply_filters(
            'wp_api_codeia_determine_auth',
            null,
            $auth_method,
            $endpoint_config
        );

        if ($auth_result === null) {
            // Usar método por defecto
            $auth_result = $this->auth_with_method($auth_method);
        }

        if ($auth_result === null) {
            return null;
        }

        $this->current_user = $auth_result['user'];
        $this->auth_data = $auth_result;

        return $this->current_user;
    }

    /**
     * Autenticar con un método específico
     *
     * @param string $method Método de autenticación
     * @return array|null Datos de autenticación o null
     */
    private function auth_with_method(string $method): ?array {
        switch ($method) {
            case 'api_key':
                return $this->auth_with_api_key();

            case 'jwt':
                return $this->auth_with_jwt();

            case 'basic':
                return $this->auth_with_basic();

            case 'app_password':
                return $this->auth_with_app_password();

            case 'none':
                // No requiere autenticación
                return [
                    'user' => null,
                    'method' => 'none',
                    'scope' => 'public',
                ];

            default:
                return null;
        }
    }

    /**
     * Autenticar con API Key
     *
     * @return array|null Datos de autenticación o null
     */
    private function auth_with_api_key(): ?array {
        // Obtener API Key del header
        $api_key = $this->get_api_key_from_header();

        if (empty($api_key)) {
            // Intentar obtener de query param (no recomendado)
            $api_key = $_GET['api_key'] ?? '';
        }

        if (empty($api_key)) {
            return null;
        }

        // Validar API Key
        $repository = new Auth_Key_Repository();
        $key_data = $repository->validate($api_key);

        if ($key_data === false) {
            return null;
        }

        // Obtener usuario
        $user = get_user_by('id', $key_data['user_id']);

        if ($user === false) {
            return null;
        }

        return [
            'user' => $user,
            'method' => 'api_key',
            'scope' => $key_data['scope'],
            'key_id' => $key_data['id'],
            'key_name' => $key_data['name'],
        ];
    }

    /**
     * Autenticar con JWT
     *
     * @return array|null Datos de autenticación o null
     */
    private function auth_with_jwt(): ?array {
        // TODO: Implementar JWT authentication
        return null;
    }

    /**
     * Autenticar con Basic Auth
     *
     * @return array|null Datos de autenticación o null
     */
    private function auth_with_basic(): ?array {
        // Solo permitir en desarrollo
        if (!wp_api_codeia_is_dev()) {
            return null;
        }

        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return null;
        }

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        // Intentar autenticar con WordPress
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return null;
        }

        return [
            'user' => $user,
            'method' => 'basic',
            'scope' => 'admin',
        ];
    }

    /**
     * Autenticar con Application Passwords
     *
     * @return array|null Datos de autenticación o null
     */
    private function auth_with_app_password(): ?array {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return null;
        }

        $username = $_SERVER['PHP_AUTH_USER'];
        $app_password = $_SERVER['PHP_AUTH_PW'];

        // Validar con Application Passwords de WordPress
        $user = get_user_by('login', $username);

        if ($user === false) {
            return null;
        }

        // Verificar application password
        if (!wp_check_application_password($user, $app_password)) {
            return null;
        }

        return [
            'user' => $user,
            'method' => 'app_password',
            'scope' => 'read_write',
        ];
    }

    /**
     * Obtener API Key desde header Authorization
     *
     * @return string API Key o string vacío
     */
    private function get_api_key_from_header(): string {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($auth_header)) {
            $auth_header = getallheaders()['Authorization'] ?? '';
        }

        if (empty($auth_header)) {
            return '';
        }

        // Formato: "Bearer {api_key}"
        if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Verificar si el usuario actual tiene un permiso
     *
     * @param string $permission Permiso a verificar (read, write, admin)
     * @return bool True si tiene el permiso
     */
    public function current_user_can(string $permission): bool {
        if ($this->auth_data === null) {
            return false;
        }

        $scope = $this->auth_data['scope'] ?? '';

        $scopes = [
            'read' => ['read'],
            'write' => ['write'],
            'read_write' => ['read', 'write'],
            'admin' => ['read', 'write', 'admin'],
            'public' => ['read'],
        ];

        return in_array($permission, $scopes[$scope] ?? [], true);
    }

    /**
     * Obtener usuario autenticado actual
     *
     * @return \WP_User|null Usuario actual o null
     */
    public function get_current_user(): ?\WP_User {
        return $this->current_user;
    }

    /**
     * Obtener datos de autenticación actual
     *
     * @return array|null Datos de autenticación o null
     */
    public function get_auth_data(): ?array {
        return $this->auth_data;
    }

    /**
     * Limpiar autenticación actual
     *
     * @return void
     */
    public function clear_auth(): void {
        $this->current_user = null;
        $this->auth_data = null;
    }
}
