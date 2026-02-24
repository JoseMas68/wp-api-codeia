<?php
/**
 * Dashboard
 *
 * Dashboard principal del admin.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Admin;

/**
 * Class Dashboard
 *
 * @package WP_API_Codeia\Admin
 */
class Dashboard {

    /**
     * Inicializar el dashboard
     *
     * @return void
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    /**
     * Agregar menú al admin
     *
     * @return void
     */
    public function add_menu(): void {
        add_menu_page(
            'WP API Codeia',
            'WP API Codeia',
            'manage_options',
            'wp-api-codeia',
            [$this, 'render_dashboard'],
            'dashicons-rest-api',
            30
        );
    }

    /**
     * Renderizar dashboard
     *
     * @return void
     */
    public function render_dashboard(): void {
        echo '<div class="wrap">';
        echo '<h1>WP API Codeia</h1>';
        echo '<p>Bienvenido al panel de control de WP API Codeia.</p>';
        echo '<p>El plugin se está inicializando...</p>';
        echo '</div>';
    }
}
