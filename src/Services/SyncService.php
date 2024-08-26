<?php
namespace Xgenious\CloudflareR2Sync\Services;

use Xgenious\CloudflareR2Sync\Api\CloudflareR2Api;

class SyncService {

    public function log_sync_status($file_id, $status, $message = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xg_cloudflare_r2_sync_log';


        $wpdb->insert(
            $table_name,
            [
                'file_id' => $file_id,
                'status' => $status,
                'message' => $message,
                'timestamp' => current_time('mysql')
            ]
        );
    }

    public function get_sync_log() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xg_cloudflare_r2_sync_log';

        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
    }
}