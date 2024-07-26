<?php
namespace Xgenious\CloudflareR2Sync\Sync;

use Xgenious\CloudflareR2Sync\Api\CloudflareR2Api;

class SyncManager
{
    private $backgroundSync;

    public function __construct()
    {
        $this->backgroundSync = new BackgroundSync();

        add_action('add_attachment', [$this, 'queueNewAttachment']);
        add_action('edit_attachment', [$this, 'queueUpdatedAttachment']);
    }

    public function queueNewAttachment($attachmentId)
    {
        $this->backgroundSync->push_to_queue($attachmentId);
        $this->backgroundSync->save()->dispatch();
    }

    public function queueUpdatedAttachment($attachmentId)
    {
        $synced = get_post_meta($attachmentId, '_cloudflare_r2_synced', true);
        if (!$synced) {
            $this->queueNewAttachment($attachmentId);
        }
    }

    public  function startSyncMediaAssets()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Reset the processed count and total jobs count
        update_option('cloudflare_r2_sync_processed_count', 0);
        update_option('cloudflare_r2_sync_total_jobs', 0);
        $this->syncAllAttachments();

        wp_send_json_success('Sync process started');
    }

    public function syncAllAttachments()
    {
        $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        );

        $attachments = get_posts($args);
        $total_jobs = count($attachments);

        // Store the total number of jobs
        update_option('cloudflare_r2_sync_total_jobs', $total_jobs);

        foreach ($attachments as $attachmentId) {
            $this->backgroundSync->push_to_queue($attachmentId);
        }

        $this->backgroundSync->save()->dispatch();
    }



    public function get_job_stats()
    {
        $total_jobs = get_option('cloudflare_r2_sync_total_jobs', 0);
        $processed = get_option('cloudflare_r2_sync_processed_count', 0);
        $pending = max(0, $total_jobs - $processed);

        wp_send_json_success([
            'pending' => $pending,
            'processed' => $processed,
            'total' => $total_jobs
        ]);
    }

    public function cancel_sync_jobs() {
        $this->backgroundSync->cancel_process();

        // Reset the processed count
        update_option('cloudflare_r2_sync_processed_count', 0);
        update_option('cloudflare_r2_sync_total_jobs', 0);
        wp_send_json(['success' => true, 'msg' => 'Background jobs cancelled successfully.'],200);
    }

    // Add this method to increment the processed count
    public function increment_processed_count() {
        $processed = get_option('cloudflare_r2_sync_processed_count', 0);
        update_option('cloudflare_r2_sync_processed_count', $processed + 1);
    }

    public function clear_sync_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xg_cloudflare_r2_sync_log';

        $result = $wpdb->query("TRUNCATE TABLE $table_name");

        if ($result !== false) {
            wp_send_json(['success' => true, 'msg' => 'All sync logs cleared successfully.'],200);
        } else {
            wp_send_json(['success' => false, 'msg' => 'Failed to clear sync logs.'],200);
        }
    }


    public function remove_all_r2_files()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }


        try {
            $r2Api = new CloudflareR2Api();
            $result = $r2Api->delete_all_objects();
            if ($result) {
                // Reset all synced flags in the database
                global $wpdb;
                $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_cloudflare_r2_synced'");
                $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_cloudflare_r2_url'");

                wp_send_json_success('All files removed from Cloudflare R2 successfully.');
            } else {
                wp_send_json_error('Failed to remove files from Cloudflare R2.');
            }
        } catch (\Exception $e) {
            wp_send_json_error('Error removing files from Cloudflare R2: ' . $e->getMessage());
        }
    }

}