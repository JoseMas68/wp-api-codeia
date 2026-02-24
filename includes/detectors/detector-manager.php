<?php
/**
 * Detector Manager
 *
 * Gestiona la detección de CPTs, taxonomías y campos personalizados.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

use WP_API_Codeia\Detectors\ACF_Detector;
use WP_API_Codeia\Detectors\JetEngine_Detector;

/**
 * Class Detector_Manager
 *
 * @package WP_API_Codeia
 */
class Detector_Manager {

    /**
     * Instancia de ACF Detector
     *
     * @var ACF_Detector|null
     */
    private ?ACF_Detector $acf_detector = null;

    /**
     * Instancia de JetEngine Detector
     *
     * @var JetEngine_Detector|null
     */
    private ?JetEngine_Detector $jetengine_detector = null;

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

        // Inicializar detectores de plugins de terceros
        $this->init_third_party_detectors();
    }

    /**
     * Inicializar detectores de plugins de terceros
     *
     * @return void
     */
    private function init_third_party_detectors(): void {
        // ACF Detector
        if ($this->is_acf_active()) {
            $this->acf_detector = new ACF_Detector();
            add_action('acf/update_field_group', [$this, 'invalidate_acf_cache']);
        }

        // JetEngine Detector
        if ($this->is_jetengine_active()) {
            $this->jetengine_detector = new JetEngine_Detector();
        }
    }

    /**
     * Verificar si ACF está activo
     *
     * @return bool
     */
    private function is_acf_active(): bool {
        return wp_api_codeia_is_plugin_active('advanced-custom-fields/acf.php') ||
               class_exists('ACF');
    }

    /**
     * Verificar si JetEngine está activo
     *
     * @return bool
     */
    private function is_jetengine_active(): bool {
        return wp_api_codeia_is_plugin_active('jet-engine/jet-engine.php') ||
               class_exists('Jet_Engine');
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

        // Detectar campos de plugins de terceros
        if ($this->acf_detector) {
            $this->acf_detector->detect();
        }
        if ($this->jetengine_detector) {
            $this->jetengine_detector->detect();
        }
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

    /**
     * Invalidar cache de ACF
     *
     * @return void
     */
    public function invalidate_acf_cache(): void {
        if ($this->acf_detector) {
            $this->acf_detector->invalidate_cache();
        }
    }

    /**
     * Obtener detector ACF
     *
     * @return ACF_Detector|null
     */
    public function get_acf_detector(): ?ACF_Detector {
        return $this->acf_detector;
    }

    /**
     * Obtener detector JetEngine
     *
     * @return JetEngine_Detector|null
     */
    public function get_jetengine_detector(): ?JetEngine_Detector {
        return $this->jetengine_detector;
    }

    /**
     * Obtener todos los campos personalizados (incluyendo ACF y JetEngine)
     *
     * @param string $post_type Post type
     * @return array Todos los campos para el post type
     */
    public function get_all_custom_fields(string $post_type): array {
        $fields = [];

        // Meta fields nativos
        $meta_fields = $this->detect_meta_fields();
        if (isset($meta_fields[$post_type])) {
            $fields['native'] = $meta_fields[$post_type];
        }

        // Campos ACF
        if ($this->acf_detector && $this->acf_detector->is_acf_active()) {
            $fields['acf'] = $this->acf_detector->get_fields_for_post_type($post_type);
        }

        // Campos JetEngine
        if ($this->jetengine_detector && $this->jetengine_detector->is_jetengine_active()) {
            $fields['jetengine'] = $this->jetengine_detector->get_fields_for_post_type($post_type);
        }

        return $fields;
    }
}
