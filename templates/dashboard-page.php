<?php
/**
 * Template: Dashboard Page
 *
 * @package WP_API_Codeia
 */

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Values are escaped in the template
?>

<div class="wrap wp-api-codeia-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wp-api-codeia-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_requests']); ?></h3>
                <p>Total Requests</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['requests_today']); ?></h3>
                <p>Requests Today</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔑</div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['active_keys']); ?></h3>
                <p>Active API Keys</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔌</div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['endpoint_count']); ?></h3>
                <p>Endpoints</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['rate_hits_today']); ?></h3>
                <p>Rate Limit Hits Today</p>
            </div>
        </div>
    </div>

    <div class="wp-api-codeia-dashboard-sections">
        <div class="section-card">
            <h2>🔗 API Endpoints</h2>
            <p>Base URL: <code><?php echo esc_html(rest_url(WP_API_CODEIA_BASE_PATH . '/v1/')); ?></code></p>

            <h3>Available Endpoints</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Methods</th>
                        <th>Auth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (wp_api_codeia_config()->get_endpoints() as $slug => $config): ?>
                    <tr>
                        <td><code>/<?php echo esc_html($slug); ?></code></td>
                        <td>GET, POST, PUT, DELETE</td>
                        <td><?php echo esc_html($config['auth'] ?? 'api_key'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section-card">
            <h2>📊 Top Endpoints (Last 7 Days)</h2>
            <?php if (!empty($stats['top_endpoints'])): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Requests</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['top_endpoints'] as $endpoint): ?>
                    <tr>
                        <td><code><?php echo esc_html($endpoint->endpoint); ?></code></td>
                        <td><?php echo number_format($endpoint->request_count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No data available yet.</p>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <h2>⚙️ Quick Actions</h2>
            <div class="quick-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-api-codeia-keys'); ?>" class="button button-primary">
                    + Generate API Key
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-api-codeia-logs'); ?>" class="button">
                    View Logs
                </a>
                <a href="<?php echo esc_url(wp_api_codeia_config()->get_openapi_url()); ?>" class="button" target="_blank">
                    OpenAPI Spec
                </a>
                <button type="button" class="button" id="wp-api-codeia-clear-cache">
                    Clear Cache
                </button>
            </div>
        </div>

        <div class="section-card">
            <h2>ℹ️ System Status</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th>Plugin Version</th>
                        <td><?php echo esc_html(WP_API_CODEIA_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th>WordPress Version</th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th>PHP Version</th>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th>Cache Status</th>
                        <td>
                            <?php
                            $cache_stats = wp_api_codeia()->get_cache_manager()->get_stats();
                            echo esc_html($cache_stats['total_keys']) . ' keys cached';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>ACF</th>
                        <td>
                            <?php echo wp_api_codeia_is_acf_active() ? '✅ Active' : '❌ Not Active'; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>JetEngine</th>
                        <td>
                            <?php echo wp_api_codeia_is_jetengine_active() ? '✅ Active' : '❌ Not Active'; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wp-api-codeia-clear-cache').on('click', function() {
        if (!confirm('Are you sure you want to clear all cache?')) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_api_codeia_clear_cache',
                nonce: wpApiCodeia.nonce
            },
            success: function() {
                alert('Cache cleared successfully!');
                location.reload();
            },
            error: function() {
                alert('Error clearing cache.');
            }
        });
    });
});
</script>

<style>
.wp-api-codeia-dashboard {
    margin-top: 20px;
}

.wp-api-codeia-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 2em;
}

.stat-content h3 {
    margin: 0;
    font-size: 1.8em;
    font-weight: 600;
}

.stat-content p {
    margin: 0;
    color: #666;
}

.wp-api-codeia-dashboard-sections {
    display: grid;
    gap: 20px;
}

.section-card {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.section-card h2 {
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>
