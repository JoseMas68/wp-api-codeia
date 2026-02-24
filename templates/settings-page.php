<?php
/**
 * Template: Settings Page
 *
 * @package WP_API_Codeia
 */

$config = wp_api_codeia_config()->get_config();
$cors_config = wp_api_codeia_cors()->get_config();
?>

<div class="wrap wp-api-codeia-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php" id="wp-api-codeia-settings-form">
        <?php settings_fields('wp-api-codeia'); ?>
        <?php do_settings_sections('wp-api-codeia'); ?>

        <p class="submit">
            <button type="submit" class="button button-primary">
                Save Settings
            </button>
        </p>
    </form>

    <hr>

    <h2>🔄 Import / Export Configuration</h2>

    <div class="import-export-section">
        <div class="export-box">
            <h3>Export Configuration</h3>
            <p>Download your current configuration as JSON:</p>
            <button type="button" class="button" id="export-config-btn">
                Export Configuration
            </button>
        </div>

        <div class="import-box">
            <h3>Import Configuration</h3>
            <p>Import configuration from JSON file:</p>
            <input type="file" id="import-config-file" accept=".json">
            <button type="button" class="button" id="import-config-btn" disabled>
                Import Configuration
            </button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Export config
        $('#export-config-btn').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_api_codeia_export_config',
                    nonce: wpApiCodeia.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data, null, 2));
                        const downloadAnchorNode = document.createElement('a');
                        downloadAnchorNode.setAttribute("href", dataStr);
                        downloadAnchorNode.setAttribute("download", "wp-api-codeia-config-" + new Date().toISOString().slice(0,10) + ".json");
                        document.body.appendChild(downloadAnchorNode);
                        downloadAnchorNode.click();
                        downloadAnchorNode.remove();
                    }
                }
            });
        });

        // Import config
        $('#import-config-file').on('change', function() {
            $('#import-config-btn').prop('disabled', false);
        });

        $('#import-config-btn').on('click', function() {
            const fileInput = document.getElementById('import-config-file');
            const file = fileInput.files[0];

            if (!file) {
                alert('Please select a file');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_api_codeia_import_config',
                        nonce: wpApiCodeia.nonce,
                        config: e.target.result
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Configuration imported successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error importing configuration');
                    }
                });
            };
            reader.readAsText(file);
        });
    });
    </script>

    <style>
    .import-export-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }

    .export-box, .import-box {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 5px;
    }
    </style>
</div>

<?php
// Registrar settings de WordPress
function wp_api_codeia_register_settings() {
    // Sección: General
    add_settings_section(
        'wp_api_codeia_general',
        __('General Settings', 'wp-api-codeia'),
        null,
        'wp-api-codeia'
    );

    // Campo: Default version
    add_settings_field(
        'wp_api_codeia_default_version',
        __('Default API Version', 'wp-api-codeia'),
        'wp_api_codeia_default_version_field',
        'wp-api-codeia'
    );

    // Sección: Rate Limiting
    add_settings_section(
        'wp_api_codeia_rate_limiting',
        __('Rate Limiting', 'wp-api-codeia'),
        null,
        'wp-api-codeia'
    );

    register_setting('wp-api-codeia', 'wp_api_codeia_default_version', [
        'type' => 'string',
        'default' => 'v1',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}
add_action('admin_init', 'wp_api_codeia_register_settings');

// Campo: Default version
function wp_api_codeia_default_version_field() {
    $version = get_option('wp_api_codeia_default_version', 'v1');
    ?>
    <select name="wp_api_codeia_default_version">
        <option value="v1" <?php selected($version, 'v1'); ?>>v1</option>
    </select>
    <?php
}
