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

    public function syncFile($attachmentId)
    {
        $file = get_attached_file($attachmentId);
        $attachment_url = wp_get_attachment_url($attachmentId);
        $uploaded_from_url = false;

        if (!$file || !file_exists($file)) {
            $this->syncService->log_sync_status($attachmentId, 'info', 'File not found locally, skipping to upload to cloudflare');
            return 'skip';
            $this->logger->log( 'File not found locally, attempting to download from URL '.$attachmentId,'info');
            $file = $this->downloadFile($attachmentId, $attachment_url);
            if (!$file) {
                $this->syncService->log_sync_status($attachmentId, 'error', 'Failed to download file from URL. Skipping this file.');
                return 'skip'; // Return 'skip' to indicate this item should be removed from the queue
            }
            $uploaded_from_url = true;
        }

        $uploadDir = wp_upload_dir();
        $remotePath = str_replace($uploadDir['basedir'] . '/', '', $file);

        try {
            $result = $this->r2Api->upload_file($file, $remotePath, $attachmentId);
            if ($result) {
                update_post_meta($attachmentId, '_cloudflare_r2_synced', true);
                update_post_meta($attachmentId, '_cloudflare_r2_url', $result);

                if ($uploaded_from_url) {
                    $this->syncService->log_sync_status($attachmentId, 'success', 'File uploaded from URL and synced to Cloudflare R2');
                    $this->logger->log($attachmentId. ' File uploaded from URL and synced to Cloudflare R2', 'success', );
                } else {
                    $this->syncService->log_sync_status($attachmentId, 'success', 'File synced to Cloudflare R2');
                    $this->logger->log($attachmentId.' File synced to Cloudflare R2', 'success', );
                }

                return true;
            } else {
                $this->syncService->log_sync_status($attachmentId, 'error', 'Failed to sync file to Cloudflare R2');
                $this->logger->log($attachmentId.' Failed to sync file to Cloudflare R2', 'error');
            }
        } catch (\Exception $e) {
            $this->syncService->log_sync_status($attachmentId, 'error', 'Exception while syncing file: ' . $e->getMessage());
            $this->logger->log($attachmentId. 'Exception while syncing file: ' . $e->getMessage(), 'error');
        }

        return false;
    }

    public function downloadFile($attachmentId, $url)
    {
        $this->logger->log($attachmentId.' From download file: method' . $attachmentId. $url, 'info',__FILE__,'71' );
        $uploadDir = wp_upload_dir();
        $filePath = get_attached_file($attachmentId);


        if (empty($filePath)) {
            $fileName = basename($url);

            $filePath = $uploadDir['path'] . '/' . wp_unique_filename($uploadDir['path'], $fileName);

            $this->logger->log($attachmentId.' $filePath ' .$filePath, 'info',__FILE__,'82' );

//            if(!file_exists($uploadDir['path'] . '/') && !is_dir($uploadDir['path'] . '/')){
//                wp_mkdir_p(dirname($filePath));
//            }
        }

        $this->logger->log($attachmentId.' requesting through download_url ' , 'info',__FILE__,'94' );

        /** @var array|WP_Error $response */

        $tmp_file = download_url( $url ,100);
//
        $this->logger->log($attachmentId.' requesting through download_url finish' , 'info',__FILE__,'102' );

// Copies the file to the final destination and deletes temporary file.
        copy( $tmp_file, $filePath );
        @unlink( $tmp_file );


        if (is_wp_error($response)) {
            $this->syncService->log_sync_status($attachmentId, 'error', 'Failed to download file: ' . $response->get_error_message());
            $this->logger->log($attachmentId.'Failed to download file: ' . $response->get_error_message(), 'error',__FILE__,'100');
            return false;
        }

        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            $this->syncService->log_sync_status($attachmentId, 'error', 'Downloaded file is empty');
            $this->logger->log($attachmentId.'Downloaded file is empty', 'error',__FILE__,'108');
            return false;
        }
        $put_fileinto_folder = file_put_contents($filePath, $body);

        if ($put_fileinto_folder  === false) {
            $this->syncService->log_sync_status($attachmentId, 'error', 'Failed to save downloaded file');
            $this->logger->log($attachmentId.'Failed to save downloaded file', 'error',__FILE__,'115');
            return false;
        }
        $this->syncService->log_sync_status($attachmentId, 'info', 'Success to save downloaded file');
        $this->logger->log($attachmentId.'Success to save downloaded file', 'info',__FILE__,'119');

        update_attached_file($attachmentId, $filePath);
        wp_update_attachment_metadata($attachmentId, wp_generate_attachment_metadata($attachmentId, $filePath));

        return $filePath;
    }
}