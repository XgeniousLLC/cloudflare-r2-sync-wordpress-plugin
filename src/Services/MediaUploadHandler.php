<?php
namespace Xgenious\CloudflareR2Sync\Services;

use Xgenious\CloudflareR2Sync\Api\CloudflareR2Api;

class MediaUploadHandler {
    private $cloudflareR2Api;
    private $syncService;
    private $isEnabled;
    private $baseDir;

    public function __construct() {

        $this->cloudflareR2Api = new CloudflareR2Api();
        $this->syncService = new SyncService();
        $this->isEnabled = cloudflare_r2_get_option('enabled', false);
        $uploadDir = wp_upload_dir();
        $this->baseDir = trailingslashit($uploadDir['basedir']);
    }

    public function upload_attachment_to_cloudflare_r2($metadata, $attachment_id) {
        if (!$this->isEnabled) {
            return $metadata;
        }

        $this->uploadOriginalFile($attachment_id);

        if (wp_attachment_is_image($attachment_id)) {
            $this->uploadImageSizeVariants($metadata, $attachment_id);
        }

        return $metadata;
    }

    private function uploadOriginalFile($attachment_id) {
        $filePath = get_attached_file($attachment_id);
        $relativePath = $this->getRelativePath($filePath);

        $r2Url = $this->cloudflareR2Api->upload_file($filePath, $relativePath, $attachment_id);

        if ($r2Url) {
            update_post_meta($attachment_id, 'cloudflare_r2_url', $r2Url);
        }
    }

    private function uploadImageSizeVariants($metadata, $attachment_id) {
        if (!isset($metadata['sizes']) || !is_array($metadata['sizes'])) {
            return;
        }

        $filePath = get_attached_file($attachment_id);
        $fileDir = trailingslashit(dirname($filePath));

        foreach ($metadata['sizes'] as $size => $sizeinfo) {
            $sizeFilePath = $fileDir . $sizeinfo['file'];
            $sizeRelativePath = $this->getRelativePath($sizeFilePath);

            $sizeR2Url = $this->cloudflareR2Api->upload_file($sizeFilePath, $sizeRelativePath, $attachment_id);
            //replace object url with cloudflare r2 domain
//            $sizeR2Url
            if ($sizeR2Url) {
                update_post_meta($attachment_id, "cloudflare_r2_url_{$size}", $sizeR2Url);
            }
        }
    }

    private function getRelativePath($filePath) {
        return str_replace($this->baseDir, '', $filePath);
    }

    public function delete_attachment_from_cloudflare_r2($post_id) {
        // Check if the post is an attachment
        if (get_post_type($post_id) !== 'attachment') {
            return;
        }

        // Get the file path
        $file = get_attached_file($post_id);
        if (!$file) {
            return;
        }

        // Get the file name (relative path)
        $upload_dir = wp_upload_dir();
        $file_name = str_replace($upload_dir['basedir'] . '/', '', $file);

        // Delete the file from Cloudflare R2
        $result = $this->cloudflareR2Api->delete_file($file_name);

        if ($result) {
            $this->syncService->log_sync_status($post_id, 'success', 'File deleted from Cloudflare R2');
        } else {
            $this->syncService->log_sync_status($post_id, 'error', 'Failed to delete file from Cloudflare R2');
        }

        // Remove the R2 URL from post meta
        delete_post_meta($post_id, '_cloudflare_r2_url');
    }
}