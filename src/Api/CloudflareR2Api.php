<?php
namespace Xgenious\CloudflareR2Sync\Api;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Xgenious\CloudflareR2Sync\Services\SyncService;

class CloudflareR2Api {

    private $s3Client;

    public function __construct() {
        $this->initializeS3Client();
    }

    private function initializeS3Client() {
        $access_key_id = cloudflare_r2_get_option('access_key_id');
        $secret_access_key = cloudflare_r2_get_option('secret_access_key');
        $endpoint = cloudflare_r2_get_option('endpoint');
        $bucket = cloudflare_r2_get_option('bucket');

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
        $bucket = cloudflare_r2_get_option('bucket');
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

    public function delete_file($file_name) {
        $bucket = cloudflare_r2_get_option('bucket');
        try {
            $result = $this->s3Client->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $file_name,
            ]);
            return true;
        } catch (AwsException $e) {
            error_log('Cloudflare R2 delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_all_objects()
    {
        $bucket = cloudflare_r2_get_option('bucket');
        try {
            $objects = $this->s3Client->listObjects([
                'Bucket' => $bucket,
            ]);

            if (!empty($objects['Contents'])) {
                $deleteObjects = [];
                foreach ($objects['Contents'] as $object) {
                    $deleteObjects[] = ['Key' => $object['Key']];
                }

                $result = $this->s3Client->deleteObjects([
                    'Bucket' => $bucket,
                    'Delete' => [
                        'Objects' => $deleteObjects,
                    ],
                ]);

                return true;
            }

            return true; // Return true if bucket was already empty
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            error_log('Error deleting objects from R2: ' . $e->getMessage());
            return false;
        }
    }
}