<?php
/**
 * Template: Endpoints Page
 *
 * @package WP_API_Codeia
 */

$endpoints = wp_api_codeia_config()->get_endpoints();
?>

<div class="wrap wp-api-codeia-endpoints">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wp-api-codeia-endpoints-section">
        <div class="section-card">
            <h2>📡 Available Endpoints</h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>URL</th>
                        <th>Methods</th>
                        <th>Auth</th>
                        <th>Post Types</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($endpoints as $slug => $config): ?>
                    <tr>
                        <td><strong><?php echo esc_html($config['label'] ?? $slug); ?></strong></td>
                        <td><code><?php echo esc_html(rest_url(WP_API_CODEIA_BASE_PATH . '/v1/' . $slug . '/')); ?></code></td>
                        <td>GET, POST, PUT, DELETE</td>
                        <td><code><?php echo esc_html($config['auth'] ?? 'api_key'); ?></code></td>
                        <td><?php echo esc_html(implode(', ', $config['post_types'] ?? [])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section-card">
            <h2>🧪 Test Endpoint</h2>
            <p>Test your API authentication directly from here:</p>

            <div class="test-endpoint-form">
                <label>
                    Select Endpoint:
                    <select id="test-endpoint-select">
                        <?php foreach ($endpoints as $slug => $config): ?>
                        <option value="<?php echo esc_attr($slug); ?>">
                            <?php echo esc_html($config['label'] ?? $slug); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <button type="button" class="button button-primary" id="test-endpoint-btn">
                    Test GET Request
                </button>

                <div id="test-result" style="margin-top: 20px; display:none;">
                    <h3>Result:</h3>
                    <pre><code id="test-result-content"></code></pre>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h2>📚 OpenAPI Documentation</h2>
            <p>View the full OpenAPI specification:</p>
            <a href="<?php echo esc_url(wp_api_codeia_config()->get_openapi_url()); ?>" class="button button-primary" target="_blank">
                View OpenAPI Spec
            </a>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#test-endpoint-btn').on('click', function() {
            const endpoint = $('#test-endpoint-select').val();
            const url = wpApiCodeia.restUrl + endpoint + '/';

            $('#test-result').show();
            $('#test-result-content').text('Loading...');

            $.ajax({
                url: url,
                method: 'GET',
                beforeSend: function(xhr) {
                    // Add auth if available from session
                    const apiKey = localStorage.getItem('wp_api_codeia_test_key');
                    if (apiKey) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + apiKey);
                    }
                },
                success: function(data) {
                    $('#test-result-content').text(JSON.stringify(data, null, 2));
                },
                error: function(xhr) {
                    $('#test-result-content').text('Error: ' + xhr.status + ' ' + xhr.statusText);
                }
            });
        });
    });
    </script>

    <style>
    .test-endpoint-form {
        margin: 20px 0;
    }

    .test-endpoint-form label {
        display: block;
        margin-bottom: 10px;
    }

    #test-result {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
    }

    #test-result pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-all;
    }
    </style>
</div>
