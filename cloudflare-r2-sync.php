<?php
/*
Plugin Name: Cloudflare R2 Sync
Description: Sync WordPress media assets with Cloudflare R2 Storage
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit;
}

// Check if Composer's autoloader exists
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}


function cloudflare_r2_sync_init() {
    $plugin = new \Xgenious\CloudflareR2Sync\Core\Plugin();
    $plugin->run();
}

add_action('plugins_loaded', 'cloudflare_r2_sync_init');

// Register activation hook
register_activation_hook(__FILE__, ['\Xgenious\CloudflareR2Sync\Core\Activator', 'activate']);