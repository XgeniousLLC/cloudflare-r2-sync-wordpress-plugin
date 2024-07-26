<?php
namespace Xgenious\CloudflareR2Sync\Core;

use Xgenious\CloudflareR2Sync\Admin\Menus;
use Xgenious\CloudflareR2Sync\Admin\Pages\Settings;
use Xgenious\CloudflareR2Sync\Admin\Pages\SyncPage;
use Xgenious\CloudflareR2Sync\Api\CloudflareR2Api;
use Xgenious\CloudflareR2Sync\Services\MediaUploadHandler;
use Xgenious\CloudflareR2Sync\Services\SyncService;
use Xgenious\CloudflareR2Sync\Services\UrlRewriter;
use Xgenious\CloudflareR2Sync\Sync\SyncManager;

class Plugin {
    public function run() {
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_dependency_classes();
        $this->load_admin_css_files();
        $this->load_admin_js_files();
    }

    private function define_admin_hooks() {
        $menus = new Menus();
        $mediaUploadHandler = new MediaUploadHandler();
        $SyncManager = new SyncManager();
        add_action('admin_menu', [$menus, 'admin_menu_page_register']);
        add_action('admin_init', [new Settings(), 'register_settings']);
        add_filter('wp_generate_attachment_metadata', [$mediaUploadHandler, 'upload_attachment_to_cloudflare_r2'], 10, 2);
        add_action('delete_attachment', [$mediaUploadHandler, 'delete_attachment_from_cloudflare_r2']);

        add_action('wp_ajax_sync_existing_files', [$SyncManager,'startSyncMediaAssets']);

        add_action('wp_ajax_get_job_stats',  [$SyncManager,'get_job_stats']);
        add_action('wp_ajax_cancel_sync_jobs',  [$SyncManager,'cancel_sync_jobs']);
        add_action('wp_ajax_clear_sync_logs',  [$SyncManager,'clear_sync_logs']);
        add_action('wp_ajax_remove_all_r2_files', [$SyncManager, 'remove_all_r2_files']);
        add_action('wp_ajax_check_sync_status', [$SyncManager, 'check_sync_status']);



//        add_action('wp_ajax_sync_existing_files', function() {
//            if (!current_user_can('manage_options')) {
//                wp_send_json_error('Unauthorized');
//            }
//
//            $syncManager = new SyncManager();
//            $syncManager->syncAllAttachments();
//
//            wp_send_json_success('Sync process started');
//        });


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
        new SyncManager();
    }

    private function load_admin_css_files()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    private function load_admin_js_files()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }


    public function enqueue_admin_styles($hook)
    {
        if ($this->is_plugin_page($hook)) {
            wp_enqueue_style('cloudflare-r2-sync-admin', CLOUDFLARE_R2_SYNC_URL . 'assets/admin/css/admin-style.css', [], CLOUDFLARE_R2_VERSION);
        }
    }

    public function enqueue_admin_scripts($hook)
    {
        if ($this->is_plugin_page($hook)) {
            wp_enqueue_script('cloudflare-r2-sync-admin', CLOUDFLARE_R2_SYNC_URL. 'assets/admin/js/admin-script.js', ['jquery'], CLOUDFLARE_R2_VERSION, true);
        }
    }

    private function is_plugin_page($hook)
    {
        $plugin_pages = [
            'toplevel_page_r2-sync',
            'r2-sync_page_r2-sync-logs'
        ];

        return in_array($hook, $plugin_pages);
    }
}