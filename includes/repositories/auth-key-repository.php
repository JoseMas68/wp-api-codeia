<?php
/**
 * Auth Key Repository
 *
 * Repositorio para gestionar API Keys.
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
     * Crear API key por defecto para un usuario
     *
     * @param int $user_id ID del usuario
     * @return bool|string API key o false si falló
     */
    public static function create_default_key(int $user_id) {
        // TODO: Implementar creación de API key
        return true;
    }
}
