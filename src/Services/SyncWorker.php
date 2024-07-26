<?php
namespace Xgenious\CloudflareR2Sync\Services;

use Xgenious\CloudflareR2Sync\API\CloudflareR2Api;

class SyncWorker {
    public function start_sync() {
        $batch_size = 10;
        $offset = 0;

        while (true) {
            $attachments = get_posts([
                'post_type' => 'attachment',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
            ]);

            if (empty($attachments)) {
                break;
            }

            foreach ($attachments as $attachment) {
                $this->sync_single_attachment($attachment->ID);
                sleep(1); // Add a small delay to prevent overwhelming the server
            }

            $offset += $batch_size;
        }
    }

    private function sync_single_attachment($attachment_id) {
        $file = get_attached_file($attachment_id);
        $cloudflare_r2_api = new CloudflareR2Api();
        $result = $cloudflare_r2_api->upload_file($file);

        $sync_service = new SyncService();
        if ($result) {
            $sync_service->log_sync_status($attachment_id, 'success', 'File synced to Cloudflare R2');
        } else {
            $sync_service->log_sync_status($attachment_id, 'error', 'Failed to sync file to Cloudflare R2');
        }
    }
}