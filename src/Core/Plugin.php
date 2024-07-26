<?php
namespace Xgenious\CloudflareR2Sync\Core;

use Xgenious\CloudflareR2Sync\Admin\Settings;
use Xgenious\CloudflareR2Sync\Admin\SyncPage;
use Xgenious\CloudflareR2Sync\API\CloudflareR2Api;
use Xgenious\CloudflareR2Sync\Services\MediaUploadHandler;
use Xgenious\CloudflareR2Sync\Services\SyncService;
use Xgenious\CloudflareR2Sync\Services\UrlRewriter;

class Plugin {
    public function run() {
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_dependency_classes();
    }

    private function define_admin_hooks() {
        $settings = new Settings();
        $sync_page = new SyncPage();
        $mediaUploadHandler = new MediaUploadHandler();
        add_action('admin_menu', [$settings, 'add_settings_page']);
        add_action('admin_init', [$settings, 'register_settings']);
        add_action('admin_menu', [$sync_page, 'add_sync_page']);

        add_filter('wp_generate_attachment_metadata', [$mediaUploadHandler, 'upload_attachment_to_cloudflare_r2'], 10, 2);



//        $sync_service = new SyncService();
//        add_filter('wp_generate_attachment_metadata', [$sync_service, 'upload_to_cloudflare_r2'], 10, 2);


    }

    private function define_public_hooks() {
        // Add any public-facing hooks here
    }

    private function init_dependency_classes()
    {
        //load all the class need to load on plugins init hook
        new MediaUploadHandler();
        new UrlRewriter();
    }
}