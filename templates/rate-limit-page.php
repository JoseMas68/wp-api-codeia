<?php
/**
 * Template: Rate Limit Page
 *
 * @package WP_API_Codeia
 */

$limiter = wp_api_codeia_rate_limiter();
$active_ids = $limiter->get_active_identifiers(50);
?>

<div class="wrap wp-api-codeia-rate-limit">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wp-api-codeia-rate-limit-section">
        <div class="section-card">
            <h2>📊 Active Rate Limits (Last Hour)</h2>

            <?php if (!empty($active_ids)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Identifier</th>
                        <th>Type</th>
                        <th>Requests (Last Hour)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_ids as $id): ?>
                    <tr>
                        <td>
                            <?php
                            if (strpos($id->identifier, 'user_') === 0) {
                                $user_id = substr($id->identifier, 5);
                                $user = get_userdata($user_id);
                                echo esc_html($user->display_name ?? 'Unknown User');
                            } elseif (strpos($id->identifier, 'key_') === 0) {
                                echo 'API Key #' . esc_html(substr($id->identifier, 4));
                            } else {
                                echo esc_html($id->identifier);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (strpos($id->identifier, 'ip_') === 0) {
                                echo 'IP Address';
                            } else {
                                echo 'User/API Key';
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($id->request_count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No activity in the last hour.</p>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <h2>⚙️ Default Rate Limits</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Scope</th>
                        <th>Limit</th>
                        <th>Window</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>read</code></td>
                        <td>100 requests/hour</td>
                        <td>3600 seconds</td>
                    </tr>
                    <tr>
                        <td><code>write</code></td>
                        <td>50 requests/hour</td>
                        <td>3600 seconds</td>
                    </tr>
                    <tr>
                        <td><code>read_write</code></td>
                        <td>100 requests/hour</td>
                        <td>3600 seconds</td>
                    </tr>
                    <tr>
                        <td><code>admin</code></td>
                        <td>1000 requests/hour</td>
                        <td>3600 seconds</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-card">
            <h2>🚫 Blocked IPs</h2>
            <p>IP addresses that have been blocked from accessing the API.</p>

            <?php
            $blocked_ips = get_option('wp_api_codeia_blocked_ips', []);
            if (!empty($blocked_ips)):
            ?>
            <ul>
                <?php foreach ($blocked_ips as $ip): ?>
                <li>
                    <code><?php echo esc_html($ip); ?></code>
                    <button type="button" class="button button-small unblock-ip" data-ip="<?php echo esc_attr($ip); ?>" style="margin-left: 10px;">
                        Unblock
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No IPs are currently blocked.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.unblock-ip').on('click', function() {
            const ip = $(this).data('ip');

            if (!confirm('Unblock IP: ' + ip + '?')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_api_codeia_unblock_ip',
                    nonce: wpApiCodeia.nonce,
                    ip: ip
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('Error unblocking IP');
                }
            });
        });
    });
    </script>
</div>
