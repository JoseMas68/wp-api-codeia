<?php
/**
 * JWT Endpoint
 *
 * Gestiona endpoints para autenticación JWT (login, refresh, logout).
 *
 * @package WP_API_Codeia\Endpoints
 */

namespace WP_API_Codeia\Endpoints;

use WP_API_Codeia\Auth\Auth_Manager;
use WP_API_Codeia\API_Request;
use WP_API_Codeia\API_Response;

/**
 * Class JWT_Endpoint
 *
 * @package WP_API_Codeia\Endpoints
 */
class JWT_Endpoint {

    /**
     * Auth Manager
     *
     * @var Auth_Manager
     */
    private Auth_Manager $auth_manager;

    /**
     * Constructor
     *
     * @param Auth_Manager $auth_manager Auth Manager
     */
    public function __construct(Auth_Manager $auth_manager) {
        $this->auth_manager = $auth_manager;
    }

    /**
     * Manejar petición de login (obtener tokens)
     *
     * @param API_Request $request Petición
     * @return API_Response Respuesta
     */
    public function login(API_Request $request): API_Response {
        $username = $request->get_param('username');
        $password = $request->get_param('password');

        if (empty($username) || empty($password)) {
            return new API_Response([
                'success' => false,
                'message' => 'Usuario y contraseña son requeridos',
                'code' => 'missing_credentials',
            ], 400);
        }

        // Autenticar usuario
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new API_Response([
                'success' => false,
                'message' => 'Credenciales inválidas',
                'code' => 'invalid_credentials',
            ], 401);
        }

        // Verificar que el usuario puede usar JWT
        if (!$this->can_user_use_jwt($user)) {
            return new API_Response([
                'success' => false,
                'message' => 'Usuario no autorizado para usar JWT',
                'code' => 'unauthorized',
            ], 403);
        }

        // Generar tokens
        $tokens = $this->auth_manager->generate_jwt_tokens($user->ID, [
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_role' => $user->roles[0] ?? '',
        ]);

        return new API_Response([
            'success' => true,
            'data' => array_merge($tokens, [
                'user' => [
                    'id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'nicename' => $user->user_nicename,
                    'role' => $user->roles[0] ?? '',
                ],
            ]),
        ]);
    }

    /**
     * Manejar petición de refresh
     *
     * @param API_Request $request Petición
     * @return API_Response Respuesta
     */
    public function refresh(API_Request $request): API_Response {
        $refresh_token = $request->get_param('refresh_token');

        if (empty($refresh_token)) {
            return new API_Response([
                'success' => false,
                'message' => 'Refresh token es requerido',
                'code' => 'missing_refresh_token',
            ], 400);
        }

        // Refrescar token
        $new_tokens = $this->auth_manager->refresh_jwt_token($refresh_token);

        if ($new_tokens === false) {
            return new API_Response([
                'success' => false,
                'message' => 'Refresh token inválido o expirado',
                'code' => 'invalid_refresh_token',
            ], 401);
        }

        return new API_Response([
            'success' => true,
            'data' => $new_tokens,
        ]);
    }

    /**
     * Manejar petición de logout (invalidar token)
     *
     * @param API_Request $request Petición
     * @return API_Response Respuesta
     */
    public function logout(API_Request $request): API_Response {
        // Obtener token actual
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';

        if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
            $token = trim($matches[1]);
        }

        if (empty($token)) {
            return new API_Response([
                'success' => false,
                'message' => 'Token no proporcionado',
                'code' => 'missing_token',
            ], 400);
        }

        // Invalidar token
        $result = $this->auth_manager->logout_jwt($token);

        if (!$result) {
            return new API_Response([
                'success' => false,
                'message' => 'Error al invalidar token',
                'code' => 'invalidation_failed',
            ], 500);
        }

        return new API_Response([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    /**
     * Verificar token (para validar sin renovar)
     *
     * @param API_Request $request Petición
     * @return API_Response Respuesta
     */
    public function verify(API_Request $request): API_Response {
        // Obtener token actual
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';

        if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
            $token = trim($matches[1]);
        }

        if (empty($token)) {
            return new API_Response([
                'success' => false,
                'message' => 'Token no proporcionado',
                'code' => 'missing_token',
            ], 400);
        }

        // Verificar token
        $payload = $this->auth_manager->verify_jwt($token);

        if ($payload === false) {
            return new API_Response([
                'success' => false,
                'message' => 'Token inválido o expirado',
                'code' => 'invalid_token',
                'data' => [
                    'valid' => false,
                    'expired' => true,
                ],
            ], 401);
        }

        // Obtener usuario
        $user_id = $payload['sub'] ?? null;
        $user = $user_id ? get_userdata($user_id) : null;

        return new API_Response([
            'success' => true,
            'data' => [
                'valid' => true,
                'expired' => false,
                'expires_at' => $payload['exp'] ?? 0,
                'user' => $user ? [
                    'id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'role' => $user->roles[0] ?? '',
                ] : null,
            ],
        ]);
    }

    /**
     * Verificar si un usuario puede usar autenticación JWT
     *
     * @param \WP_User $user Usuario
     * @return bool True si puede usar JWT
     */
    private function can_user_use_jwt(\WP_User $user): bool {
        // Por defecto, solo usuarios que pueden editar posts pueden usar JWT
        return user_can($user, 'edit_posts');
    }
}
