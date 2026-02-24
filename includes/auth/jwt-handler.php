<?php
/**
 * JWT Handler
 *
 * Maneja la codificación y decodificación de tokens JWT.
 *
 * @package WP_API_Codeia\Auth
 */

namespace WP_API_Codeia\Auth;

/**
 * Class JWT_Handler
 *
 * @package WP_API_Codeia\Auth
 */
class JWT_Handler {

    /**
     * Algoritmo de firma por defecto
     *
     * @var string
     */
    private string $algorithm = 'HS256';

    /**
     * Tiempo de vida del token en segundos (defecto: 1 hora)
     *
     * @var int
     */
    private int $token_lifetime = 3600;

    /**
     * Tiempo de vida del refresh token en segundos (defecto: 30 días)
     *
     * @var int
     */
    private int $refresh_lifetime = 2592000;

    /**
     * Obtener la clave secreta para firmar tokens
     *
     * @return string
     */
    private function get_secret_key(): string {
        $secret_key = get_option('wp_api_codeia_jwt_secret');

        if (empty($secret_key)) {
            // Generar clave secreta si no existe
            $secret_key = wp_generate_password(64, true, true);
            update_option('wp_api_codeia_jwt_secret', $secret_key);
        }

        return $secret_key;
    }

    /**
     * Codificar un token JWT
     *
     * @param array $payload Payload del token
     * @param int   $lifetime Tiempo de vida en segundos
     * @return string Token JWT
     * @throws \Exception Si hay error en la codificación
     */
    public function encode(array $payload, int $lifetime = 0): string {
        if ($lifetime === 0) {
            $lifetime = $this->token_lifetime;
        }

        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm,
        ];

        // Agregar timestamps al payload
        $payload['iat'] = time();
        $payload['exp'] = time() + $lifetime;
        $payload['nbf'] = time();

        // Agregar identificador único (jti)
        $payload['jti'] = $this->generate_jti();

        // Codificar header y payload
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));

        // Crear firma
        $signature = $this->sign($header_encoded . '.' . $payload_encoded);

        return $header_encoded . '.' . $payload_encoded . '.' . $signature;
    }

    /**
     * Decodificar un token JWT
     *
     * @param string $token Token JWT
     * @return array|false Payload decodificado o false si es inválido
     */
    public function decode(string $token) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($header_encoded, $payload_encoded, $signature) = $parts;

        // Verificar firma
        if (!$this->verify($header_encoded . '.' . $payload_encoded, $signature)) {
            return false;
        }

        // Decodificar payload
        $payload = json_decode($this->base64url_decode($payload_encoded), true);

        if (!is_array($payload)) {
            return false;
        }

        // Verificar expiración
        if (isset($payload['exp']) && time() > $payload['exp']) {
            return false;
        }

        // Verificar not before
        if (isset($payload['nbf']) && time() < $payload['nbf']) {
            return false;
        }

        return $payload;
    }

    /**
     * Generar un token de acceso para un usuario
     *
     * @param int   $user_id ID del usuario
     * @param array $extra_data Datos adicionales a incluir
     * @return string Token JWT
     */
    public function generate_access_token(int $user_id, array $extra_data = []): string {
        $user = get_userdata($user_id);

        if (!$user) {
            throw new \Exception('Usuario no encontrado');
        }

        $payload = array_merge([
            'sub' => $user_id,
            'iss' => get_bloginfo('url'),
            'aud' => get_bloginfo('url'),
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_role' => $user->roles[0] ?? '',
            'scope' => 'read_write',
        ], $extra_data);

        return $this->encode($payload);
    }

    /**
     * Generar un refresh token
     *
     * @param int $user_id ID del usuario
     * @return string Refresh token
     */
    public function generate_refresh_token(int $user_id): string {
        $payload = [
            'sub' => $user_id,
            'iss' => get_bloginfo('url'),
            'aud' => get_bloginfo('url'),
            'type' => 'refresh',
        ];

        return $this->encode($payload, $this->refresh_lifetime);
    }

    /**
     * Verificar un refresh token y obtener el user_id
     *
     * @param string $refresh_token Refresh token
     * @return int|false User ID o false si es inválido
     */
    public function verify_refresh_token(string $refresh_token) {
        $payload = $this->decode($refresh_token);

        if (!$payload || !isset($payload['type']) || $payload['type'] !== 'refresh') {
            return false;
        }

        return $payload['sub'] ?? false;
    }

    /**
     * Firmar datos
     *
     * @param string $data Datos a firmar
     * @return string Firma codificada
     */
    private function sign(string $data): string {
        $secret = $this->get_secret_key();
        $signature = hash_hmac('sha256', $data, $secret, true);
        return $this->base64url_encode($signature);
    }

    /**
     * Verificar firma
     *
     * @param string $data Datos firmados
     * @param string $signature Firma a verificar
     * @return bool True si la firma es válida
     */
    private function verify(string $data, string $signature): bool {
        $secret = $this->get_secret_key();
        $expected_signature = hash_hmac('sha256', $data, $secret, true);
        return hash_equals($this->base64url_encode($expected_signature), $signature);
    }

    /**
     * Codificar en base64 URL-safe
     *
     * @param string $data Datos a codificar
     * @return string Datos codificados
     */
    private function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodificar desde base64 URL-safe
     *
     * @param string $data Datos a decodificar
     * @return string Datos decodificados
     */
    private function base64url_decode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generar identificador único para el token (JTI)
     *
     * @return string
     */
    private function generate_jti(): string {
        return wp_generate_password(16, false);
    }

    /**
     * Obtener el tiempo de vida del token
     *
     * @return int Tiempo en segundos
     */
    public function get_token_lifetime(): int {
        return $this->token_lifetime;
    }

    /**
     * Establecer el tiempo de vida del token
     *
     * @param int $lifetime Tiempo en segundos
     * @return void
     */
    public function set_token_lifetime(int $lifetime): void {
        $this->token_lifetime = max(60, $lifetime); // Mínimo 1 minuto
    }

    /**
     * Obtener el tiempo de vida del refresh token
     *
     * @return int Tiempo en segundos
     */
    public function get_refresh_lifetime(): int {
        return $this->refresh_lifetime;
    }

    /**
     * Establecer el tiempo de vida del refresh token
     *
     * @param int $lifetime Tiempo en segundos
     * @return void
     */
    public function set_refresh_lifetime(int $lifetime): void {
        $this->refresh_lifetime = max(3600, $lifetime); // Mínimo 1 hora
    }

    /**
     * Invalidar un token (agregar a blacklist)
     *
     * @param string $token Token a invalidar
     * @return bool True si se invalidó correctamente
     */
    public function invalidate_token(string $token): bool {
        $payload = $this->decode($token);

        if (!$payload || !isset($payload['exp']) || !isset($payload['jti'])) {
            return false;
        }

        $blacklist = get_option('wp_api_codeia_jwt_blacklist', []);

        // Agregar a la blacklist con su tiempo de expiración
        $blacklist[$payload['jti']] = $payload['exp'];

        update_option('wp_api_codeia_jwt_blacklist', $blacklist);

        return true;
    }

    /**
     * Verificar si un token está en blacklist
     *
     * @param array $payload Payload del token
     * @return bool True si está en blacklist
     */
    public function is_token_blacklisted(array $payload): bool {
        if (!isset($payload['jti'])) {
            return false;
        }

        $blacklist = get_option('wp_api_codeia_jwt_blacklist', []);

        if (!isset($blacklist[$payload['jti']])) {
            return false;
        }

        // Limpiar tokens expirados de la blacklist
        if (time() > $blacklist[$payload['jti']]) {
            unset($blacklist[$payload['jti']]);
            update_option('wp_api_codeia_jwt_blacklist', $blacklist);
            return false;
        }

        return true;
    }

    /**
     * Limpiar tokens expirados de la blacklist
     *
     * @return int Cantidad de tokens eliminados
     */
    public function cleanup_blacklist(): int {
        $blacklist = get_option('wp_api_codeia_jwt_blacklist', []);
        $cleaned = 0;
        $now = time();

        foreach ($blacklist as $jti => $exp) {
            if ($now > $exp) {
                unset($blacklist[$jti]);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            update_option('wp_api_codeia_jwt_blacklist', $blacklist);
        }

        return $cleaned;
    }
}
