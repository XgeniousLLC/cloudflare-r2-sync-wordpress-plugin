<?php
namespace Xgenious\CloudflareR2Sync\Services;

use Xgenious\CloudflareR2Sync\API\CloudflareR2Api;

class SyncService {

//    public function upload_to_cloudflare_r2($metadata, $attachment_id) {
//        if (!get_option('cloudflare_r2_enabled')) {
//            return $metadata;
//        }
//
//        $file = get_attached_file($attachment_id);
//        $cloudflare_r2_api = new CloudflareR2Api();
//        $result = $cloudflare_r2_api->upload_file($file);
//
//        if ($result) {
//            $this->log_sync_status($attachment_id, 'success', 'File uploaded to Cloudflare R2');
//        } else {
//            $this->log_sync_status($attachment_id, 'error', 'Failed to upload file to Cloudflare R2');
//        }
//
//        return $metadata;
//    }

/*

*/

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