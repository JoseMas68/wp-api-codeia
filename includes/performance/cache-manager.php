<?php
/**
 * Cache Manager
 *
 * Gestiona el caching del plugin.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

/**
 * Class Cache_Manager
 *
 * @package WP_API_Codeia
 */
class Cache_Manager {

    /**
     * Inicializar el cache manager
     *
     * @return void
     */
    public function init(): void {
        // Hooks de cache
    }

    /**
     * Invalidar cache de un post
     *
     * @param int $post_id ID del post
     * @return void
     */
    public function invalidate_post_cache(int $post_id): void {
        // TODO: Implementar invalidación
    }

    /**
     * Invalidar cache de campos
     *
     * @return void
     */
    public function invalidate_field_cache(): void {
        // TODO: Implementar invalidación
    }
}
