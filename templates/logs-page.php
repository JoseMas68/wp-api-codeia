<?php
/**
 * Template: Logs Page
 *
 * @package WP_API_Codeia
 */

$logger = wp_api_codeia()->get_request_logger();
$logs = $logger->get_logs([
    'limit' => 100,
    'order' => 'DESC',
]);
?>

<div class="wrap wp-api-codeia-logs">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wp-api-codeia-logs-actions" style="margin-bottom: 20px;">
        <button type="button" class="button" id="refresh-logs">
            🔄 Refresh Logs
        </button>
        <button type="button" class="button" id="export-logs">
            📥 Export Logs (CSV)
        </button>
        <button type="button" class="button" id="clear-logs">
            🗑️ Clear All Logs
        </button>
    </div>

    <div class="wp-api-codeia-logs-section">
        <div class="section-card">
            <h2>📜 Recent Requests (Last 100)</h2>

            <?php if (!empty($logs)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Method</th>
                        <th>Endpoint</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>User/IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <?php
                            $time = strtotime($log['requested_at']);
                            echo esc_html(date('Y-m-d H:i:s', $time));
                            ?>
                        </td>
                        <td>
                            <span class="method-badge method-<?php echo esc_attr(strtolower($log['method'])); ?>">
                                <?php echo esc_html($log['method']); ?>
                            </span>
                        </td>
                        <td><code><?php echo esc_html($log['endpoint']); ?></code></td>
                        <td>
                            <?php
                            $status = $log['response_status'];
                            $class = $status < 300 ? 'success' : ($status < 500 ? 'warning' : 'error');
                            ?>
                            <span class="status-badge status-<?php echo esc_attr($class); ?>">
                                <?php echo esc_html($status); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(number_format($log['duration'] * 1000, 0)); ?>ms</td>
                        <td>
                            <?php
                            if (!empty($log['user_id'])) {
                                $user = get_userdata($log['user_id']);
                                echo esc_html($user->display_name ?? 'User ' . $log['user_id']);
                            } else {
                                echo esc_html($log['ip_address'] ?? 'Unknown');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No logs available yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Refresh logs
        $('#refresh-logs').on('click', function() {
            location.reload();
        });

        // Export logs
        $('#export-logs').on('click', function() {
            window.location.href = '<?php echo admin_url('admin-ajax.php?action=wp_api_codeia_export_logs&nonce=' . wp_create_nonce('wp_api_codeia')); ?>';
        });

        // Clear logs
        $('#clear-logs').on('click', function() {
            if (!confirm('Are you sure you want to clear all logs? This cannot be undone.')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_api_codeia_clear_logs',
                    nonce: wpApiCodeia.nonce
                },
                success: function() {
                    alert('Logs cleared successfully!');
                    location.reload();
                },
                error: function() {
                    alert('Error clearing logs');
                }
            });
        });
    });
    </script>

    <style>
    .method-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-weight: 600;
        font-size: 11px;
    }

    .method-get { background: #e3f2fd; color: #1976d2; }
    .method-post { background: #e8f5e9; color: #388e3c; }
    .method-put { background: #fff3e0; color: #f57c00; }
    .method-delete { background: #ffebee; color: #c62828; }

    .status-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-weight: 600;
        font-size: 11px;
    }

    .status-success { background: #e8f5e9; color: #388e3c; }
    .status-warning { background: #fff3e0; color: #f57c00; }
    .status-error { background: #ffebee; color: #c62828; }
    </style>
</div>
