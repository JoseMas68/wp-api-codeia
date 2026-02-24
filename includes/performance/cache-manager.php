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
     * Prefijo para las keys de cache
     *
     * @var string
     */
    private string $prefix = 'wp_api_codeia_';

    /**
     * Grupos de cache configurados
     *
     * @var array
     */
    private array $cache_groups = [];

    /**
     * Inicializar el cache manager
     *
     * @return void
     */
    public function init(): void {
        // Hooks de cache
        add_action('save_post', [$this, 'invalidate_post_cache']);
        add_action('delete_post', [$this, 'invalidate_post_cache']);
        add_action('wp_trash_post', [$this, 'invalidate_post_cache']);

        // Hooks para taxonomías
        add_action('created_term', [$this, 'invalidate_term_cache']);
        add_action('edited_term', [$this, 'invalidate_term_cache']);
        add_action('delete_term', [$this, 'invalidate_term_cache']);

        // Hook para opciones
        add_action('updated_option', [$this, 'invalidate_option_cache']);

        // Configurar grupos de cache por defecto
        $this->setup_default_groups();
    }

    /**
     * Configurar grupos de cache por defecto
     *
     * @return void
     */
    private function setup_default_groups(): void {
        $this->cache_groups = [
            'posts' => [
                'enabled' => true,
                'ttl' => HOUR_IN_SECONDS,
                'strategy' => 'object', // object, transient, wp_cache
            ],
            'terms' => [
                'enabled' => true,
                'ttl' => HOUR_IN_SECONDS * 6,
                'strategy' => 'object',
            ],
            'users' => [
                'enabled' => true,
                'ttl' => HOUR_IN_SECONDS * 2,
                'strategy' => 'object',
            ],
            'meta' => [
                'enabled' => true,
                'ttl' => HOUR_IN_SECONDS,
                'strategy' => 'transient',
            ],
            'queries' => [
                'enabled' => true,
                'ttl' => MINUTE_IN_SECONDS * 5,
                'strategy' => 'transient',
            ],
            'endpoints' => [
                'enabled' => true,
                'ttl' => MINUTE_IN_SECONDS * 15,
                'strategy' => 'transient',
            ],
        ];
    }

    /**
     * Obtener valor del cache
     *
     * @param string $key Clave de cache
     * @param string $group Grupo de cache
     * @return mixed Valor cacheado o false si no existe
     */
    public function get(string $key, string $group = 'default') {
        $cache_key = $this->generate_key($key, $group);
        $config = $this->get_group_config($group);

        if (!$config['enabled']) {
            return false;
        }

        switch ($config['strategy']) {
            case 'transient':
                return get_transient($cache_key);

            case 'object':
                return wp_cache_get($cache_key, $this->prefix . $group);

            case 'site_transient':
                return get_site_transient($cache_key);

            default:
                return get_transient($cache_key);
        }
    }

    /**
     * Guardar valor en cache
     *
     * @param string $key Clave de cache
     * @param mixed  $value Valor a cachear
     * @param string $group Grupo de cache
     * @param int    $ttl Tiempo de vida en segundos
     * @return bool True si se guardó correctamente
     */
    public function set(string $key, $value, string $group = 'default', int $ttl = 0): bool {
        $cache_key = $this->generate_key($key, $group);
        $config = $this->get_group_config($group);

        if (!$config['enabled']) {
            return false;
        }

        if ($ttl === 0) {
            $ttl = $config['ttl'];
        }

        switch ($config['strategy']) {
            case 'transient':
                return set_transient($cache_key, $value, $ttl);

            case 'object':
                return wp_cache_set($cache_key, $value, $this->prefix . $group, $ttl);

            case 'site_transient':
                return set_site_transient($cache_key, $value, $ttl);

            default:
                return set_transient($cache_key, $value, $ttl);
        }
    }

    /**
     * Eliminar valor del cache
     *
     * @param string $key Clave de cache
     * @param string $group Grupo de cache
     * @return bool True si se eliminó correctamente
     */
    public function delete(string $key, string $group = 'default'): bool {
        $cache_key = $this->generate_key($key, $group);
        $config = $this->get_group_config($group);

        switch ($config['strategy']) {
            case 'transient':
                return delete_transient($cache_key);

            case 'object':
                return wp_cache_delete($cache_key, $this->prefix . $group);

            case 'site_transient':
                return delete_site_transient($cache_key);

            default:
                return delete_transient($cache_key);
        }
    }

    /**
     * Limpiar todo el cache de un grupo
     *
     * @param string $group Grupo de cache
     * @return bool True si se limpió correctamente
     */
    public function flush_group(string $group): bool {
        $config = $this->get_group_config($group);

        if ($config['strategy'] === 'object') {
            // wp_cache_flush_group solo está disponible en WP 6.1+
            if (function_exists('wp_cache_flush_group')) {
                return wp_cache_flush_group($this->prefix . $group);
            }
            // Fallback: limpiar todo el object cache
            wp_cache_flush();
            return true;
        }

        // Para transients, necesitamos rastrear las keys
        $keys = get_option($this->prefix . 'keys_' . $group, []);

        foreach ($keys as $key) {
            $this->delete($key, $group);
        }

        update_option($this->prefix . 'keys_' . $group, []);

        return true;
    }

    /**
     * Generar clave de cache
     *
     * @param string $key Clave original
     * @param string $group Grupo de cache
     * @return string Clave de cache completa
     */
    private function generate_key(string $key, string $group): string {
        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9_\-]/', '_', $key);

        // Agregar hash si la clave es muy larga
        if (strlen($key) > 60) {
            $key = md5($key);
        }

        return $this->prefix . $group . '_' . $key;
    }

    /**
     * Obtener configuración de un grupo
     *
     * @param string $group Nombre del grupo
     * @return array Configuración del grupo
     */
    private function get_group_config(string $group): array {
        $default_config = [
            'enabled' => true,
            'ttl' => HOUR_IN_SECONDS,
            'strategy' => 'transient',
        ];

        return $this->cache_groups[$group] ?? $default_config;
    }

    /**
     * Invalidar cache de un post
     *
     * @param int $post_id ID del post
     * @return void
     */
    public function invalidate_post_cache(int $post_id): void {
        $post_type = get_post_type($post_id);

        // Eliminar cache del post individual
        $this->delete('post_' . $post_id, 'posts');

        // Eliminar cache de listas
        $this->delete('posts_' . $post_type . '_list', 'posts');

        // Eliminar cache de queries relacionadas
        $this->flush_group('queries');
    }

    /**
     * Invalidar cache de un término
     *
     * @param int $term_id ID del término
     * @return void
     */
    public function invalidate_term_cache(int $term_id): void {
        // Eliminar cache del término
        $this->delete('term_' . $term_id, 'terms');

        // Eliminar cache de listas de términos
        $this->flush_group('terms');
    }

    /**
     * Invalidar cache de opciones
     *
     * @param string $option_name Nombre de la opción
     * @return void
     */
    public function invalidate_option_cache(string $option_name): void {
        if (strpos($option_name, $this->prefix) === 0) {
            $this->flush_group('options');
        }
    }

    /**
     * Recordar una clave de cache para poder limpiarla después
     *
     * @param string $key Clave de cache
     * @param string $group Grupo de cache
     * @return void
     */
    private function remember_key(string $key, string $group): void {
        $keys = get_option($this->prefix . 'keys_' . $group, []);

        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            update_option($this->prefix . 'keys_' . $group, $keys);
        }
    }

    /**
     * Obtener o crear valor cacheado (pattern cache-aside)
     *
     * @param string   $key Clave de cache
     * @param callable $callback Función para generar el valor
     * @param string   $group Grupo de cache
     * @param int      $ttl Tiempo de vida
     * @return mixed Valor cacheado o generado
     */
    public function remember(string $key, callable $callback, string $group = 'default', int $ttl = 0) {
        $value = $this->get($key, $group);

        if ($value !== false) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $group, $ttl);

        return $value;
    }

    /**
     * Obtener estadísticas de cache
     *
     * @return array Estadísticas
     */
    public function get_stats(): array {
        $stats = [
            'groups' => [],
            'total_keys' => 0,
        ];

        foreach ($this->cache_groups as $group => $config) {
            $keys = get_option($this->prefix . 'keys_' . $group, []);
            $stats['groups'][$group] = [
                'enabled' => $config['enabled'],
                'strategy' => $config['strategy'],
                'ttl' => $config['ttl'],
                'keys_count' => count($keys),
            ];
            $stats['total_keys'] += count($keys);
        }

        return $stats;
    }

    /**
     * Limpiar todo el cache del plugin
     *
     * @return void
     */
    public function flush_all(): void {
        foreach (array_keys($this->cache_groups) as $group) {
            $this->flush_group($group);
        }
    }

    /**
     * Configurar un grupo de cache
     *
     * @param string $group Nombre del grupo
     * @param array  $config Configuración
     * @return bool True si se configuró correctamente
     */
    public function configure_group(string $group, array $config): bool {
        $default_config = [
            'enabled' => true,
            'ttl' => HOUR_IN_SECONDS,
            'strategy' => 'transient',
        ];

        $this->cache_groups[$group] = array_merge($default_config, $config);

        return true;
    }
}
