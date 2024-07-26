<?php

namespace Xgenious\CloudflareR2Sync\Sync;
use WP_Background_Process;
use Xgenious\CloudflareR2Sync\Api\CloudflareR2Api;
use Xgenious\CloudflareR2Sync\Services\SyncService;

class BackgroundSync extends WP_Background_Process
{
    protected $action = 'cloudflare_r2_sync';

    /**
     * Get the cron event name.
     *
     * @return string
     */
    protected function get_event_name()
    {
        return $this->identifier . '_cron';
    }
    public function get_identifier()
    {
        return $this->identifier;
    }
    protected function task($item)
    {

        $synchronizer = new Synchronizer();
        $result = $synchronizer->syncFile($item);
        $syncService = new SyncService();

        if ($result === true) {
            // Increment the processed count
            $processed = get_option('cloudflare_r2_sync_processed_count', 0);
            update_option('cloudflare_r2_sync_processed_count', $processed + 1);
            $syncService->log_sync_status($item, 'info', 'Sync process completed successfully');


            return false; // Remove from queue if successful
        } elseif ($result === 'skip') {
            // Increment the processed count even for skipped items
            $processed = get_option('cloudflare_r2_sync_processed_count', 0);
            update_option('cloudflare_r2_sync_processed_count', $processed + 1);
            $syncService->log_sync_status($item, 'error', 'Sync process Skipped, will retry');

            return false; // Remove from queue if we're skipping this item
        } else {
            $syncService->log_sync_status($item, 'error', 'Sync process failed, will retry');
            return $item; // Keep in queue for retry if failed
        }
    }



    protected function complete()
    {
        parent::complete();

        // Reset the processed count and total jobs count when all tasks are complete
        update_option('cloudflare_r2_sync_processed_count', 0);
        update_option('cloudflare_r2_sync_total_jobs', 0);

        // You can add any actions you want to perform when all items have been processed
        do_action('cloudflare_r2_sync_complete');
    }

    /**
     * Delete all batches.
     *
     * @return $this
     */
    public function delete_all_batches()
    {
        global $wpdb;

        $table  = $wpdb->options;
        $column = 'option_name';

        if ( is_multisite() ) {
            $table  = $wpdb->sitemeta;
            $column = 'meta_key';
        }

        $key = $this->identifier . '_batch_%';

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) );

        return $this;
    }

    // Add this method to allow canceling the process
    public function cancel_process() {
        $this->delete_all_batches();
        wp_clear_scheduled_hook($this->get_event_name());

        // Reset the processed count
        update_option('cloudflare_r2_sync_processed_count', 0);
    }
}