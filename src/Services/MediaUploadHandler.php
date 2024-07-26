<?php
namespace Xgenious\CloudflareR2Sync\Services;

use Xgenious\CloudflareR2Sync\API\CloudflareR2Api;

class MediaUploadHandler {
    private $cloudflareR2Api;
    private $isEnabled;
    private $baseDir;

    public function __construct() {

        $this->cloudflareR2Api = new CloudflareR2Api();
        $this->isEnabled = get_option('cloudflare_r2_enabled', false);
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
}