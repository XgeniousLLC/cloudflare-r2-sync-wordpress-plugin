<?php

namespace Xgenious\CloudflareR2Sync\Sync;
use WP_Background_Process;
use Xgenious\CloudflareR2Sync\Api\CloudflareR2Api;
use Xgenious\CloudflareR2Sync\Services\SyncService;
use Xgenious\CloudflareR2Sync\Utilities\Logger;

class BackgroundSync extends WP_Background_Process
{
    protected $action = 'cloudflare_r2_sync';
    private $logger;
    public function __construct()
    {
        parent::__construct();
        $this->logger = new Logger();
    }
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

        $this->logger->log("Starting sync process for attachment ID: $item", 'info');

        // Set a time limit for this task
        $start_time = time();
        $time_limit = 60; // 1 minute

        $synchronizer = new Synchronizer();
        $result = $synchronizer->syncFile($item, $start_time, $time_limit);

        if ($result === true) {
            $this->logger->log("Sync process completed successfully for attachment ID: $item", 'success');
            $processed = get_option('cloudflare_r2_sync_processed_count', 0);
            update_option('cloudflare_r2_sync_processed_count', $processed + 1);
            return false;
        } elseif ($result === 'skip') {
            $this->logger->log("Sync process skipped for attachment ID: $item", 'info');
            $processed = get_option('cloudflare_r2_sync_processed_count', 0);
            update_option('cloudflare_r2_sync_processed_count', $processed + 1);
            return false;
        } elseif ($result === 'retry') {
            $this->logger->log("Sync process timed out for attachment ID: $item, will retry", 'info');
            return $item;
        } else {
            $this->logger->log("Sync process failed for attachment ID: $item, will retry", 'error');
            return $item;
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