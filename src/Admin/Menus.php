<?php

namespace Xgenious\CloudflareR2Sync\Admin;

use Xgenious\CloudflareR2Sync\Admin\Pages\Dashboard;
use Xgenious\CloudflareR2Sync\Admin\Pages\Settings;

class Menus {

    public function r2_sync_dashboard_page()
    {
        return Dashboard::render_page();
    }

    public function admin_menu_page_register() {
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
                [\Xgenious\CloudflareR2Sync\Admin\Pages\Settings::class, 'render_page'] // Function to display the dashboard
            );
            add_submenu_page(
                'r2-sync', // Parent slug
                'Sync Logs', // Page title
                'Sync Logs', // Menu title
                'manage_options', // Capability
                'r2-sync-logs', // Menu slug
                [\Xgenious\CloudflareR2Sync\Admin\Pages\SyncPage::class, 'render_page']
            );
    }



}