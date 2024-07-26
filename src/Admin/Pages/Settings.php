<?php
namespace Xgenious\CloudflareR2Sync\Admin\Pages;

class Settings
{
    private $option_group = 'cloudflare_r2_options';
    private $option_name = 'cloudflare_r2_settings';
    private $option_page = 'r2-sync-settings';

    /**
     * Get a specific option value or all options.
     *
     * @param string|null $key The specific option key to retrieve. If null, returns all options.
     * @param mixed $default The default value to return if the option doesn't exist.
     * @return mixed The option value, or the default if the option doesn't exist.
     */
    public static function get_option($key = null, $default = null)
    {
        $options = get_option((new self)->option_name, []);

        if ($key === null) {
            return $options;
        }

        $value = isset($options[$key]) ? $options[$key] : $default;

        // If the key is 'url' and the value doesn't start with 'http://' or 'https://', prepend 'https://'
        if ($key === 'url' && $value && !preg_match('~^https?://~i', $value)) {
            $value = 'https://' . $value;
        }

        return $value;
    }
    public function display() {
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

    public function register_settings() {

        register_setting(
            $this->option_group,
            $this->option_name,
            [$this, 'sanitize_options']
        );

        add_settings_section(
            'cloudflare_r2_main_section',
            'Cloudflare R2 Settings',
            [$this, 'section_callback'],
            $this->option_page
        );

        $this->add_settings_fields();
    }
    private function add_settings_fields() {
        $fields = [
            'access_key_id' => [
                'title' => 'Access Key ID',
                'callback' => 'render_text_field',
                'description' => 'Enter your Cloudflare R2 Access Key ID. You can find this in the Cloudflare dashboard under R2 > Manage R2 API Tokens.'
            ],
            'secret_access_key' => [
                'title' => 'Secret Access Key',
                'callback' => 'render_password_field',
                'description' => 'Enter your Cloudflare R2 Secret Access Key. This is provided when you create a new R2 API Token in the Cloudflare dashboard.'
            ],
            'bucket' => [
                'title' => 'Bucket Name',
                'callback' => 'render_text_field',
                'description' => 'Enter the name of your Cloudflare R2 bucket. You can create or find existing buckets in the Cloudflare dashboard under R2 > Buckets.'
            ],
            'url' => [
                'title' => 'R2 URL',
                'callback' => 'render_text_field',
                'description' => "Enter your Cloudflare domain url, connect your domain from the R2 settings -> domain"
            ],
            'endpoint' => [
                'title' => 'R2 Endpoint',
                'callback' => 'render_text_field',
                'description' => 'Enter your Cloudflare R2 endpoint. This is typically https://<accountid>.r2.cloudflarestorage.com. You can find your account ID in the Cloudflare dashboard under Account Home > Account ID.'
            ],
            'enabled' => [
                'title' => 'Enable Cloudflare R2',
                'callback' => 'render_checkbox_field',
                'description' => 'Check this box to enable Cloudflare R2 integration. Make sure all other fields are correctly filled before enabling.'
            ]
        ];

        foreach ($fields as $field_name => $field_data) {
            add_settings_field(
                $field_name,
                $field_data['title'],
                [$this, $field_data['callback']],
                $this->option_page,
                'cloudflare_r2_main_section',
                [
                    'label_for' => $field_name,
                    'description' => $field_data['description']
                ]
            );
        }
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


    public function register_setting_group() {
        register_setting(
            $this->option_group,
            $this->option_group,
            [$this, 'sanitize_options']
        );
    }
    public function sanitize_options($options) {
        // Add any specific sanitization logic here
        return $options;
    }
    public function render_text_field($args) {
        $option_name = $args['label_for'];
        $options = get_option($this->option_name);
        $value = isset($options[$option_name]) ? $options[$option_name] : '';
        echo "<input type='text' id='" . esc_attr($option_name) . "' name='" . esc_attr($this->option_name) . "[" . esc_attr($option_name) . "]' value='" . esc_attr($value) . "' class='regular-text'>";
        if (!empty($args['description'])) {
            echo "<p class='description'>" . esc_html($args['description']) . "</p>";
        }
    }

    public function render_password_field($args) {
        $option_name = $args['label_for'];
        $options = get_option($this->option_name);
        $value = isset($options[$option_name]) ? $options[$option_name] : '';
        echo "<input type='password' id='" . esc_attr($option_name) . "' name='" . esc_attr($this->option_name) . "[" . esc_attr($option_name) . "]' value='" . esc_attr($value) . "' class='regular-text'>";
        if (!empty($args['description'])) {
            echo "<p class='description'>" . esc_html($args['description']) . "</p>";
        }
    }

    public function render_checkbox_field($args) {
        $option_name = $args['label_for'];
        $options = get_option($this->option_name);
        $value = isset($options[$option_name]) ? $options[$option_name] : false;
        echo "<input type='checkbox' id='" . esc_attr($option_name) . "' name='" . esc_attr($this->option_name) . "[" . esc_attr($option_name) . "]' value='1' " . checked(1, $value, false) . ">";
        if (!empty($args['description'])) {
            echo "<p class='description'>" . esc_html($args['description']) . "</p>";
        }
    }
    public static function render_page() {
        $settings = new self();
        $settings->register_setting_group();
        $settings->display();
    }
}