<?php
/*
Plugin Name: Cloudflare R2 Sync
Description: Sync WordPress media assets with Cloudflare R2 Storage
Version: 1.2.0
Author: wpHex
Author URI: https://wphex.com
*/

if (!defined('ABSPATH')) {
    exit;
}
define('CLOUDFLARE_R2_SYNC_PATH', plugin_dir_path(__FILE__));
define('CLOUDFLARE_R2_SYNC_URL', plugin_dir_url(__FILE__));
define('CLOUDFLARE_R2_VERSION','1.2.0');

// Check if Composer's autoloader exists
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}



function cloudflare_r2_sync_init() {
    $plugin = new \Xgenious\CloudflareR2Sync\Core\Plugin();
    $plugin->run();
}

add_action('plugins_loaded', 'cloudflare_r2_sync_init');

function cloudflare_r2_get_option($key = null, $default = null) {
    return \Xgenious\CloudflareR2Sync\Admin\Pages\Settings::get_option($key, $default);
}

// Register activation hook
register_activation_hook(__FILE__, ['\Xgenious\CloudflareR2Sync\Core\Activator', 'activate']);