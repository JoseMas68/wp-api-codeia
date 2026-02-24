<?php
/**
 * Detector Manager
 *
 * Gestiona la detección de CPTs, taxonomías y campos personalizados.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

/**
 * Class Detector_Manager
 *
 * @package WP_API_Codeia
 */
class Detector_Manager {

    /**
     * Inicializar el detector manager
     *
     * @return void
     */
    public function init(): void {
        // Hooks de detección
        add_action('init', [$this, 'detect_all'], 999);
        add_action('registered_post_type', [$this, 'invalidate_cpt_cache']);
        add_action('registered_taxonomy', [$this, 'invalidate_taxonomy_cache']);
    }

    /**
     * Detectar todos los elementos (CPTs, taxonomías, campos)
     *
     * @return void
     */
    public function detect_all(): void {
        $this->detect_cpts();
        $this->detect_taxonomies();
        $this->detect_meta_fields();
    }

    /**
     * Detectar Custom Post Types
     *
     * @return array CPTs detectados
     */
    public function detect_cpts(): array {
        $cache_key = 'wp_api_codeia_cpts';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $post_types = get_post_types(['public' => true], 'objects');
        $detected = [];

        foreach ($post_types as $post_type) {
            // Excluir post types nativos si se desea solo custom
            // if (in_array($post_type->name, ['post', 'page', 'attachment'])) {
            //     continue;
            // }

            $detected[$post_type->name] = [
                'name' => $post_type->name,
                'label' => $post_type->label,
                'singular_label' => $post_type->labels->singular_name,
                'description' => $post_type->description,
                'public' => $post_type->public,
                'hierarchical' => $post_type->hierarchical,
                'supports' => $post_type->supports,
                'taxonomies' => get_object_taxonomies($post_type->name),
                'has_archive' => $post_type->has_archive,
            ];
        }

        set_transient($cache_key, $detected, 12 * HOUR_IN_SECONDS);

        return $detected;
    }

    /**
     * Detectar Taxonomías
     *
     * @return array Taxonomías detectadas
     */
    public function detect_taxonomies(): array {
        $cache_key = 'wp_api_codeia_taxonomies';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $detected = [];

        foreach ($taxonomies as $taxonomy) {
            $detected[$taxonomy->name] = [
                'name' => $taxonomy->name,
                'label' => $taxonomy->label,
                'singular_label' => $taxonomy->labels->singular_name,
                'hierarchical' => $taxonomy->hierarchical,
                'object_types' => $taxonomy->object_type,
            ];
        }

        set_transient($cache_key, $detected, 12 * HOUR_IN_SECONDS);

        return $detected;
    }

    /**
     * Detectar Meta Fields
     *
     * @return array Meta fields detectados
     */
    public function detect_meta_fields(): array {
        $cache_key = 'wp_api_codeia_meta_fields';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $detected = [];
        $post_types = array_keys($this->detect_cpts());

        foreach ($post_types as $post_type) {
            $meta_keys = get_registered_meta_keys('post', $post_type);
            $detected[$post_type] = [];

            foreach ($meta_keys as $key => $meta) {
                // Excluir meta keys del plugin
                if (strpos($key, '_wp_api_codeia_') === 0) {
                    continue;
                }

                $detected[$post_type][$key] = [
                    'key' => $key,
                    'type' => $meta['type'] ?? 'string',
                    'description' => $meta['description'] ?? '',
                    'single' => $meta['single'] ?? true,
                    'show_in_rest' => $meta['show_in_rest'] ?? false,
                ];
            }
        }

        set_transient($cache_key, $detected, 12 * HOUR_IN_SECONDS);

        return $detected;
    }

    /**
     * Invalidar cache de CPTs
     *
     * @return void
     */
    public function invalidate_cpt_cache(): void {
        delete_transient('wp_api_codeia_cpts');
    }

    /**
     * Invalidar cache de taxonomías
     *
     * @return void
     */
    public function invalidate_taxonomy_cache(): void {
        delete_transient('wp_api_codeia_taxonomies');
    }
}
