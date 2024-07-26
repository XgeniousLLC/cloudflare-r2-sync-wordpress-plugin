<?php

namespace Xgenious\CloudflareR2Sync\Admin;

use Xgenious\CloudflareR2Sync\Admin\Pages\Dashboard;
use Xgenious\CloudflareR2Sync\Admin\Pages\Settings as SettingsPage;

class Settings {
    private $option_group = 'cloudflare_r2_sync_settings';
    private $option_page = 'cloudflare-r2-sync-settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    public function r2_sync_dashboard_page()
    {
        return Dashboard::render_page();
    }

    public function r2_sync_settings_page()
    {
        return SettingsPage::render_page();
    }
    public function add_settings_page() {
//        add_options_page(
//            'Cloudflare R2 Sync Settings',
//            'Cloudflare R2 Sync',
//            'manage_options',
//            $this->option_page,
//            [$this, 'render_settings_page']
//        );

            add_menu_page(
                'R2 Sync', // Page title
                'R2 Sync', // Menu title
                'manage_options', // Capability
                'r2-sync', // Menu slug
                [$this,'r2_sync_dashboard_page'], // Function to display the dashboard
                'dashicons-cloud-upload', // Icon (optional)
                80 // Position
            );

            add_submenu_page(
                'r2-sync', // Parent slug
                'Dashboard', // Page title
                'Dashboard', // Menu title
                'manage_options', // Capability
                'r2-sync', // Menu slug (same as parent for main submenu item)
                [$this,'r2_sync_dashboard_page'] // Function to display the dashboard
            );

            add_submenu_page(
                'r2-sync', // Parent slug
                'Settings', // Page title
                'Settings', // Menu title
                'manage_options', // Capability
                'r2-sync-settings', // Menu slug
                [$this,'r2_sync_settings_page'] // Function to display the settings
            );

            add_submenu_page(
                'r2-sync', // Parent slug
                'Sync Logs', // Page title
                'Sync Logs', // Menu title
                'manage_options', // Capability
                'r2-sync-logs', // Menu slug
                'r2_sync_logs_page' // Function to display the logs
            );
    }

    public function register_settings() {
        register_setting($this->option_group, 'cloudflare_r2_access_key_id');
        register_setting($this->option_group, 'cloudflare_r2_secret_access_key');
        register_setting($this->option_group, 'cloudflare_r2_bucket');
        register_setting($this->option_group, 'cloudflare_r2_url');
        register_setting($this->option_group, 'cloudflare_r2_endpoint');
        register_setting($this->option_group, 'cloudflare_r2_enabled', [
            'type' => 'boolean',
            'default' => false,
        ]);

        add_settings_section(
            'cloudflare_r2_main_section',
            'Cloudflare R2 Settings',
            [$this, 'section_callback'],
           'r2-sync-settings' //settings page slug
        );

        add_settings_field(
            'cloudflare_r2_access_key_id',
            'Access Key ID',
            [$this, 'render_text_field'],
            'r2-sync-settings',
            'cloudflare_r2_main_section',
            [
                'label_for' => 'cloudflare_r2_access_key_id',
                'description' => 'Enter your Cloudflare R2 Access Key ID. You can find this in the Cloudflare dashboard under R2 > Manage R2 API Tokens.'
            ]
        );

        add_settings_field(
            'cloudflare_r2_secret_access_key',
            'Secret Access Key',
            [$this, 'render_password_field'],
            $this->option_page,
            'cloudflare_r2_main_section',
            [
                    'label_for' => 'cloudflare_r2_secret_access_key',
                    'description' => 'Enter your Cloudflare R2 Secret Access Key. This is provided when you create a new R2 API Token in the Cloudflare dashboard.'
            ]
        );

        add_settings_field(
            'cloudflare_r2_bucket',
            'Bucket Name',
            [$this, 'render_text_field'],
            $this->option_page,
            'cloudflare_r2_main_section',
            [
                 'label_for' => 'cloudflare_r2_bucket',
                'description' => 'Enter the name of your Cloudflare R2 bucket. You can create or find existing buckets in the Cloudflare dashboard under R2 > Buckets.'

            ]
        );

        add_settings_field(
            'cloudflare_r2_url',
            'R2 URL',
            [$this, 'render_text_field'],
            $this->option_page,
            'cloudflare_r2_main_section',
            [
                'label_for' => 'cloudflare_r2_url',
                'description' => "Enter your Cloudflare domain url"
            ]
        );

        add_settings_field(
            'cloudflare_r2_endpoint',
            'R2 Endpoint',
            [$this, 'render_text_field'],
            $this->option_page,
            'cloudflare_r2_main_section',
            [
                'label_for' => 'cloudflare_r2_endpoint',
                'description' => 'Enter your Cloudflare R2 endpoint. This is typically https://<accountid>.r2.cloudflarestorage.com. You can find your account ID in the Cloudflare dashboard under Account Home > Account ID.'
            ]
        );

        add_settings_field(
            'cloudflare_r2_enabled',
            'Enable Cloudflare R2',
            [$this, 'render_checkbox_field'],
            $this->option_page,
            'cloudflare_r2_main_section',
            [
                 'label_for' => 'cloudflare_r2_enabled',
                'description' => 'Check this box to enable Cloudflare R2 integration. Make sure all other fields are correctly filled before enabling.'
            ]
        );
    }

    public function section_callback($args) {
        echo '<p>To set up Cloudflare R2 integration, you\'ll need to create an R2 bucket and API token in your Cloudflare account. Follow these steps:</p>';
        echo '<ol>';
        echo '<li>Log in to your Cloudflare dashboard.</li>';
        echo '<li>Navigate to R2 under the "Storage" section.</li>';
        echo '<li>Create a new bucket or select an existing one.</li>';
        echo '<li>Go to "Manage R2 API Tokens" and create a new token with appropriate permissions.</li>';
        echo '<li>Copy the Access Key ID and Secret Access Key provided.</li>';
        echo '<li>Fill in the fields below with the information from your Cloudflare R2 account.</li>';
        echo '</ol>';
    }

    public function render_text_field($args) {
        $option = $args['label_for'];
        $value = get_option($option);
        $description = isset($args['description']) ? $args['description'] : '';
        echo "<input type='text' id='" . esc_attr($option) . "' name='" . esc_attr($option) . "' value='" . esc_attr($value) . "' class='regular-text'>";
        if (!empty($description)) {
            echo "<p class='description'>" . esc_html($description) . "</p>";
        }
    }

    public function render_password_field($args) {
        $option = $args['label_for'];
        $value = get_option($option);
        $description = isset($args['description']) ? $args['description'] : '';

        echo "<input type='password' id='" . esc_attr($option) . "' name='" . esc_attr($option) . "' value='" . esc_attr($value) . "' class='regular-text'>";
        if (!empty($description)) {
            echo "<p class='description'>" . esc_html($description) . "</p>";
        }
    }

    public function render_checkbox_field($args) {
        $option = $args['label_for'];
        $value = get_option($option);
        $description = isset($args['description']) ? $args['description'] : '';
        echo "<input type='checkbox' id='" . esc_attr($option) . "' name='" . esc_attr($option) . "' value='1' " . checked(1, $value, false) . ">";
        if (!empty($description)) {
            echo "<p class='description'>" . esc_html($description) . "</p>";
        }
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->option_page);
                submit_button('Save Settings');
                ?>
            </form>
            <hr>
            <h2>Connection Test</h2>
            <p>Click the button below to test your Cloudflare R2 connection.</p>
            <button id="test-cloudflare-r2-connection" class="button button-secondary">
                Test Connection
            </button>
            <span id="connection-test-result"></span>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#test-cloudflare-r2-connection').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $result = $('#connection-test-result');

                $button.prop('disabled', true);
                $result.text('Testing connection...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_cloudflare_r2_connection'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.text('Connection successful!').css('color', 'green');
                        } else {
                            $result.text('Connection failed: ' + response.data).css('color', 'red');
                        }
                    },
                    error: function() {
                        $result.text('An error occurred while testing the connection.').css('color', 'red');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
}