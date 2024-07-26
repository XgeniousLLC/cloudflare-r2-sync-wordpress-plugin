<?php
namespace Xgenious\CloudflareR2Sync\API;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Xgenious\CloudflareR2Sync\Services\SyncService;

class CloudflareR2Api {

    private $s3Client;

    public function __construct() {
        $this->initializeS3Client();
    }

    private function initializeS3Client() {
        $access_key_id = get_option('cloudflare_r2_access_key_id');
        $secret_access_key = get_option('cloudflare_r2_secret_access_key');
        $endpoint = get_option('cloudflare_r2_endpoint');
        $bucket = get_option('cloudflare_r2_bucket');

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => $endpoint,
//            'Bucket' => $bucket,
            'credentials' => [
                'key' => $access_key_id,
                'secret' => $secret_access_key,
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    public function upload_file($file_path, $file_name,$attachment_id) {
        $bucket = get_option('cloudflare_r2_bucket');
        $sync_service = new SyncService();
        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $bucket,
                'Key'    => $file_name,
                'Body'   => fopen($file_path, 'r'),
                'ACL'    => 'public-read',
            ]);

            $sync_service->log_sync_status($attachment_id, 'success', 'File uploaded to Cloudflare R2');
            return $result['ObjectURL'];
        } catch (AwsException $e) {
            error_log('Cloudflare R2 upload error: ' . $e->getMessage());
            $sync_service->log_sync_status($attachment_id, 'error', 'Failed to upload file to Cloudflare R2');
            return false;
        }
    }
}