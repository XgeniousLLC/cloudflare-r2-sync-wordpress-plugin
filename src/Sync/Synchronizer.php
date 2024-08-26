<?php

namespace Xgenious\CloudflareR2Sync\Sync;

use Xgenious\CloudflareR2Sync\Api\CloudflareR2Api;
use Xgenious\CloudflareR2Sync\Services\SyncService;
use Xgenious\CloudflareR2Sync\Utilities\Logger;

class Synchronizer
{
    private $r2Api;
    private $syncService;
    private $logger;

    public function __construct()
    {
        $this->r2Api = new CloudflareR2Api();
        $this->syncService = new SyncService();
        $this->logger = new Logger();
    }

    public function syncFile($attachmentId, $start_time, $time_limit)
    {
        $this->logger->log("Syncing file for attachment ID: $attachmentId", 'info');
        $this->syncService->log_sync_status($attachmentId,'info',"Syncing file for attachment ID: $attachmentId" );

        $file = get_attached_file($attachmentId);
        $attachment_url = wp_get_attachment_url($attachmentId);
        $uploaded_from_url = false;

        if (!$file || !file_exists($file)) {
            $this->logger->log("File not found locally for attachment ID: $attachmentId, attempting to download from URL: $attachment_url", 'info');
            $this->syncService->log_sync_status($attachmentId,'info',"File not found locally for attachment ID: $attachmentId, attempting to download from URL: $attachment_url");

            $file = $this->downloadFile($attachmentId, $attachment_url, $start_time, $time_limit);
            if ($file === 'retry') {
                return 'retry';
            } elseif (!$file) {
                $this->logger->log("Failed to download file from URL for attachment ID: $attachmentId. Skipping this file.", 'error');
                $this->syncService->log_sync_status($attachmentId,'error',"Failed to download file from URL for attachment ID: $attachmentId. Skipping this file.");

                return 'skip';
            }
            $uploaded_from_url = true;
        }

        if (time() - $start_time > $time_limit) {
            $this->logger->log("Time limit reached while processing attachment ID: $attachmentId. Will retry.", 'info');
            $this->syncService->log_sync_status($attachmentId,'info',"Time limit reached while processing attachment ID: $attachmentId. Will retry.");

            return 'retry';
        }

        $uploadDir = wp_upload_dir();
        $remotePath = str_replace($uploadDir['basedir'] . '/', '', $file);

        try {
            $result = $this->r2Api->upload_file($file, $remotePath, $attachmentId);
            if ($result) {
                update_post_meta($attachmentId, '_cloudflare_r2_synced', true);
                update_post_meta($attachmentId, '_cloudflare_r2_url', $result);

                if ($uploaded_from_url) {
                    $this->logger->log("File uploaded from URL and synced to Cloudflare R2 for attachment ID: $attachmentId", 'success');
                    $this->syncService->log_sync_status($attachmentId,'success',"File uploaded from URL and synced to Cloudflare R2 for attachment ID: $attachmentId");

                } else {
                    $this->logger->log("File synced to Cloudflare R2 for attachment ID: $attachmentId", 'success');
                    $this->syncService->log_sync_status($attachmentId,'success',"File synced to Cloudflare R2 for attachment ID: $attachmentId");

                }
                
                update_post_meta($attachmentId, 'cloudflare_r2_url', $result);
                
                $this->logger->log("File synced to Cloudflare R2 for attachment ID: $attachmentId url: $result", 'success');

                return true;
            } else {
                $this->logger->log("Failed to sync file to Cloudflare R2 for attachment ID: $attachmentId", 'error');
                $this->syncService->log_sync_status($attachmentId,'error',"Failed to sync file to Cloudflare R2 for attachment ID: $attachmentId");

            }
        } catch (\Exception $e) {
            $this->logger->log("Exception while syncing file for attachment ID: $attachmentId. Error: " . $e->getMessage(), 'error');
            $this->syncService->log_sync_status($attachmentId,'error',"Exception while syncing file for attachment ID: $attachmentId. Error: " . $e->getMessage());

        }

        return false;
    }

    private function downloadFile($attachmentId, $url, $start_time, $time_limit)
    {
        $uploadDir = wp_upload_dir();
        $filePath = get_attached_file($attachmentId);

        if (empty($filePath)) {
            $fileName = basename($url);
            $filePath = $uploadDir['path'] . '/' . wp_unique_filename($uploadDir['path'], $fileName);
        }

        wp_mkdir_p(dirname($filePath));

        $args = array(
            'timeout'     => min(60, $time_limit - (time() - $start_time)),
            'redirection' => 5,
            'sslverify'   => false,
        );

        $this->logger->log("Attempting to download file from URL: $url", 'info');
        $this->syncService->log_sync_status($attachmentId,'info',"Attempting to download file from URL: $url");

        $response = wp_safe_remote_get($url, $args);

        if (is_wp_error($response)) {
            $this->logger->log("Failed to download file: " . $response->get_error_message(), 'error');
            $this->syncService->log_sync_status($attachmentId,'error',"Failed to download file: " . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            $this->logger->log("Downloaded file is empty", 'error');
            $this->syncService->log_sync_status($attachmentId,'error',"Downloaded file is empty");

            return false;
        }

        if (time() - $start_time > $time_limit) {
            $this->logger->log("Time limit reached while downloading file. Will retry.", 'info');
            $this->syncService->log_sync_status($attachmentId,'error',"Time limit reached while downloading file. Will retry.");
            return 'retry';
        }

        if (file_put_contents($filePath, $body) === false) {
            $this->logger->log("Failed to save downloaded file", 'error');
            $this->syncService->log_sync_status($attachmentId,'error',"Failed to save downloaded file");

            return false;
        }

        wp_update_attachment_metadata($attachmentId, wp_generate_attachment_metadata($attachmentId, $filePath));

        $this->logger->log("File successfully downloaded and saved: $filePath", 'info');
        $this->syncService->log_sync_status($attachmentId,'error',"File successfully downloaded and saved: $filePath");
        
        
        return $filePath;
    }
}