<?php
/**
 * Template: API Keys Page
 *
 * @package WP_API_Codeia
 */

$user_id = get_current_user_id();
$repository = new \WP_API_Codeia\Repositories\Auth_Key_Repository();
$api_keys = $repository->get_user_keys($user_id);
?>

<div class="wrap wp-api-codeia-api-keys">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wp-api-codeia-keys-section">
        <div class="section-card">
            <h2>➕ Generate New API Key</h2>

            <form method="post" id="wp-api-codeia-create-key-form">
                <?php wp_nonce_field('wp_api_codeia_create_key', 'create_key_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="key_name">Key Name</label></th>
                        <td>
                            <input type="text" name="key_name" id="key_name" class="regular-text" required>
                            <p class="description">A descriptive name for this API key (e.g., "Mobile App", "Integration X")</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="key_scope">Permissions</label></th>
                        <td>
                            <select name="key_scope" id="key_scope">
                                <option value="read">Read Only</option>
                                <option value="write">Write Only</option>
                                <option value="read_write" selected>Read & Write</option>
                                <option value="admin">Admin (Full Access)</option>
                            </select>
                            <p class="description">
                                <strong>Read:</strong> GET requests only<br>
                                <strong>Write:</strong> POST/PUT/DELETE requests<br>
                                <strong>Admin:</strong> Full access including settings
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="key_expires">Expiration</label></th>
                        <td>
                            <select name="key_expires" id="key_expires">
                                <option value="0" selected>Never</option>
                                <option value="30">30 Days</option>
                                <option value="90">90 Days</option>
                                <option value="180">6 Months</option>
                                <option value="365">1 Year</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        Generate API Key
                    </button>
                </p>
            </form>
        </div>

        <div class="section-card">
            <h2>🔑 Your API Keys</h2>

            <?php if (!empty($api_keys)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Scope</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($api_keys as $key): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($key['name']); ?></strong>
                            <?php if ($key['is_default']): ?>
                                <span class="badge">Default</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="api-key-masked" data-full-key="<?php echo esc_attr($key['last_chars'] ?? ''); ?>">
                                <?php echo esc_html($key['truncated_key'] ?? '•••••••••••••'); ?>
                            </code>
                            <button type="button" class="button button-small copy-key" data-key="<?php echo esc_attr($key['raw_key'] ?? ''); ?>" style="margin-left: 8px;">
                                📋
                            </button>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo esc_attr($key['scope']); ?>">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $key['scope']))); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $created = strtotime($key['created_at']);
                            echo esc_html(human_time_diff($created) . ' ago');
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($key['last_used'])) {
                                $last_used = strtotime($key['last_used']);
                                echo esc_html(human_time_diff($last_used) . ' ago');
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($key['is_revoked']): ?>
                                <span class="badge badge-revoked">Revoked</span>
                            <?php else: ?>
                                <span class="badge badge-active">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$key['is_default']): ?>
                                <?php if (!$key['is_revoked']): ?>
                                    <button type="button" class="button button-small revoke-key" data-id="<?php echo esc_attr($key['id']); ?>">
                                        Revoke
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="button button-small delete-key" data-id="<?php echo esc_attr($key['id']); ?>">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <style>
            .badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
            }

            .badge-read { background: #e3f2fd; color: #1976d2; }
            .badge-write { background: #fff3e0; color: #f57c00; }
            .badge-read_write { background: #e8f5e9; color: #388e3c; }
            .badge-admin { background: #fce4ec; color: #c2185b; }
            .badge-active { background: #e8f5e9; color: #388e3c; }
            .badge-revoked { background: #ffebee; color: #c62828; }
            </style>
            <?php else: ?>
            <p>You don't have any API keys yet. Generate your first key above!</p>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <h2>📖 How to Use Your API Key</h2>

            <h3>Authentication Header</h3>
            <pre><code>Authorization: Bearer YOUR_API_KEY_HERE</code></pre>

            <h3>Example with cURL</h3>
            <pre><code>curl -X GET "<?php echo esc_html(rest_url(WP_API_CODEIA_BASE_PATH . '/v1/posts')); ?>" \
     -H "Authorization: Bearer YOUR_API_KEY_HERE"</code></pre>

            <h3>Example with JavaScript</h3>
            <pre><code>fetch('<?php echo esc_html(rest_url(WP_API_CODEIA_BASE_PATH . '/v1/posts')); ?>', {
    headers: {
        'Authorization': 'Bearer YOUR_API_KEY_HERE'
    }
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
        </div>

        <div class="section-card">
            <h2>⚠️ Security Best Practices</h2>
            <ul>
                <li>🔒 Never share your API key publicly or commit it to version control</li>
                <li>🔄 Rotate your API keys periodically</li>
                <li>👤 Use the minimum required scope for each key</li>
                <li>📅 Set expiration dates for temporary access</li>
                <li>🚫 Revoke keys that are no longer needed</li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy key to clipboard
    $('.copy-key').on('click', function() {
        const key = $(this).data('key');
        navigator.clipboard.writeText(key).then(function() {
            alert('API Key copied to clipboard!');
        });
    });

    // Revoke key
    $('.revoke-key').on('click', function() {
        if (!confirm('Are you sure you want to revoke this API key?')) {
            return;
        }

        const keyId = $(this).data('id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_api_codeia_revoke_key',
                nonce: wpApiCodeia.nonce,
                key_id: keyId
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Error revoking API key.');
            }
        });
    });

    // Delete key
    $('.delete-key').on('click', function() {
        if (!confirm('Are you sure you want to delete this API key? This cannot be undone.')) {
            return;
        }

        const keyId = $(this).data('id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_api_codeia_delete_key',
                nonce: wpApiCodeia.nonce,
                key_id: keyId
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Error deleting API key.');
            }
        });
    });

    // Create key form
    $('#wp-api-codeia-create-key-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=wp_api_codeia_create_key&nonce=' + wpApiCodeia.nonce,
            success: function(response) {
                if (response.success) {
                    $('#new-key-display').html(
                        '<h3>🎉 API Key Generated!</h3>' +
                        '<p><strong>Copy this key now. You won\'t be able to see it again!</strong></p>' +
                        '<pre><code>' + response.data.key + '</code></pre>' +
                        '<button type="button" class="button button-primary" onclick="navigator.clipboard.writeText(\'' + response.data.key + '\'); alert(\'Copied!\');">Copy to Clipboard</button>'
                    );
                    $('#new-key-modal').show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error creating API key.');
            }
        });
    });
});
</script>

<!-- Modal for displaying new key -->
<div id="new-key-modal" style="display:none;" class="wp-api-codeia-modal">
    <div class="modal-content">
        <div id="new-key-display"></div>
        <button type="button" class="button" onclick="jQuery('#new-key-modal').hide();">Close</button>
    </div>
</div>

<style>
.wp-api-codeia-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100000;
}

.modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
}

.wp-api-codeia-api-keys code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
