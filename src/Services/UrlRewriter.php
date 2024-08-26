<?php

namespace Xgenious\CloudflareR2Sync\Services;

use Xgenious\CloudflareR2Sync\Utilities\Logger;


class UrlRewriter
{
    private $isEnabled;
    private $uploadDir;
    private $uploadUrl;
    private $cloudflareR2Bucket;
    private $cloudflareR2Endpoint;
    private $cloudflareR2Url;

    public function __construct()
    {
        $this->cloudflareR2Bucket = cloudflare_r2_get_option('bucket','');
        $this->cloudflareR2Endpoint = cloudflare_r2_get_option('endpoint','');
        $this->cloudflareR2Url = cloudflare_r2_get_option('url','');

        $this->isEnabled = cloudflare_r2_get_option('enabled', false);
        $uploadInfo = wp_upload_dir();
        $this->uploadDir = trailingslashit($uploadInfo['basedir']);
        $this->uploadUrl = trailingslashit($uploadInfo['baseurl']);
        $this->setupHooks();
    }

    private function setupHooks() {
        if ($this->isEnabled) {
            $this->debugLog('from setupHooks');
            add_filter('the_content', [$this, 'rewriteContentUrls'], 10); // Changed priority to 10
            add_filter('wp_get_attachment_url', [$this, 'rewriteAttachmentUrl'], 10, 2);
            add_filter('wp_get_attachment_image_src', [$this, 'rewriteImageSrc'], 10, 4);
            add_filter('wp_calculate_image_srcset', [$this, 'rewriteImageSrcset'], 10, 5);
            add_filter('wp_get_attachment_image_attributes', [$this, 'rewriteImageAttributes'], 10, 3);
        }
    }

  
    public function rewriteImageSrc($image, $attachment_id, $size, $icon)
    {
        if (!$image) {
            return $image;
        }

        $r2_url = get_post_meta($attachment_id, 'cloudflare_r2_url', true);
        if ($r2_url) {
            $image[0] = $this->replace_r2_bucket_url_with_domain($r2_url);
        }

        return $image;
    }

    public function rewriteImageSrcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {

        $full_size = true;
        foreach ($sources as &$source) {
            if($full_size){
                $size = $source['descriptor'] . '-' . $source['value'];
                $r2_url = get_post_meta($attachment_id, "cloudflare_r2_url_{$size}", true);
                if ($r2_url) {
                    $source['url'] = $this->replace_r2_bucket_url_with_domain($r2_url);
                }
                $full_size=false;
            }else{
                $image_width = $this->getNameByWidth($source['value'] ?? '',$image_meta);
                $r2_url = get_post_meta($attachment_id, "cloudflare_r2_url_{$image_width}", true);
                if ($r2_url) {
                    $source['url'] = $this->replace_r2_bucket_url_with_domain($r2_url);
                }
            }
        }


        return $sources;
    }

    private function getNameByWidth($width, $image_sizes) {

        $flat_array = $this->convert_to_flat_array_of_image_size($image_sizes['sizes']);
        foreach ($flat_array as $item) {
            if ($item['width'] == $width) {
                return $item['name'];
            }
        }
        return null; // Return null if no matching width is found
    }

    private function convert_to_flat_array_of_image_size ( $original_array) {
        return  array_map(function($name, $details) {
            return [
                'width' => $details['width'],
                'name' => $name
            ];
        }, array_keys($original_array), $original_array);
    }

    public function rewriteContentUrls($content) {
        if (!$this->isEnabled) {
            return $content;
        }

        $pattern = '/<img[^>]+src=([\'"])(.*?)\1[^>]*>/i';
        return preg_replace_callback($pattern, [$this, 'replaceImageCallback'], $content);
    }
    
    private function replaceImageCallback($matches) {
        $img_tag = $matches[0];
        $src = $matches[2];

        // Replace src
        $new_src = $this->getR2UrlFromLocalUrl($src);
        $img_tag = str_replace($src, $new_src, $img_tag);

        // Replace srcset if exists
        if (preg_match('/srcset=([\'"])(.*?)\1/i', $img_tag, $srcset_matches)) {
            $srcset = $srcset_matches[2];
            $new_srcset = $this->rewriteSrcSet($srcset);
            $img_tag = str_replace($srcset_matches[0], 'srcset="' . $new_srcset . '"', $img_tag);
        }

        return $img_tag;
    }

    public function rewriteImageAttributes($attr, $attachment, $size)
    {
        if (isset($attr['src'])) {
            $r2_url = get_post_meta($attachment->ID, 'cloudflare_r2_url', true);
            if ($r2_url) {
                $attr['src'] = $this->replace_r2_bucket_url_with_domain($r2_url);
            }
        }
        if (isset($attr['srcset'])) {


            $attr['srcset'] = $this->rewriteSrcSet($attr['srcset'], $attachment->ID);
        }
        return $attr;
    }
    private function sizeMatches($requested_size, $stored_size) {
        // Convert sizes to arrays of dimensions
        $requested = explode('x', $requested_size);
        $stored = explode('x', $stored_size);

        // Check if dimensions match
        return ($requested[0] == $stored[0] && $requested[1] == $stored[1]);
    }
    
    private function rewriteSrcSet($srcset) {
        $srcset_parts = explode(',', $srcset);
        $new_srcset_parts = array();

        foreach ($srcset_parts as $part) {
            $part = trim($part);
            $parts = preg_split('/\s+/', $part);
            $url = $parts[0];
            $new_url = $this->getR2UrlFromLocalUrl($url);
            $parts[0] = $new_url;
            $new_srcset_parts[] = implode(' ', $parts);
        }

        return implode(', ', $new_srcset_parts);
    }

    private function getR2UrlFromLocalUrl($url) {
        // If the URL is already a Cloudflare R2 URL, return it as is
        if (strpos($url, $this->cloudflareR2Url) === 0) {
            return $url;
        }

        $relative_path = str_replace($this->uploadUrl, '', $url);
        $local_path = $this->uploadDir . $relative_path;
        $attachment_id = $this->getAttachmentIdFromUrl($url);

        if ($attachment_id) {
            $size = $this->getSizeFromFilename($local_path);
            $r2_url = $this->getR2Url($attachment_id, $size);
            return $r2_url ? $r2_url : $url;
        }

        return $url;
    }

    private function getSizeFromFilename($filepath) {
        $info = pathinfo($filepath);
        $filename = $info['filename'];
        if (preg_match('/-(\d+)x(\d+)$/', $filename, $matches)) {
            return $matches[1] . 'x' . $matches[2];
        }
        return 'full';
    }



    private function getR2Url($attachment_id, $size = 'full') {

        if ($size === 'full') {
            $r2_url = get_post_meta($attachment_id, 'cloudflare_r2_url', true);
            return $this->replace_r2_bucket_url_with_domain($r2_url);
        } else {
            // First, try to get the URL using the exact size
            $r2_url = get_post_meta($attachment_id, "cloudflare_r2_url_{$size}", true);
            if ($r2_url) {
                return $this->replace_r2_bucket_url_with_domain($r2_url);
            }

            // If not found, try to find a matching size
            $meta = get_post_meta($attachment_id);
            foreach ($meta as $key => $value) {
                if (strpos($key, 'cloudflare_r2_url_') === 0) {
                    $stored_size = str_replace('cloudflare_r2_url_', '', $key);
                    if ($this->sizeMatches($size, $stored_size)) {
                        return $this->replace_r2_bucket_url_with_domain($value[0]); // meta values are always arrays
                    }
                }
            }
        }
        return false;
    }

    private function replace_r2_bucket_url_with_domain($r2_url)
    {
        $bucket_endpoint = $this->cloudflareR2Endpoint . '/' . $this->cloudflareR2Bucket;
        return str_replace(
            rtrim($bucket_endpoint, '/'),
            rtrim($this->cloudflareR2Url, '/'),
            $r2_url
        );
    }

    private function getAttachmentIdFromUrl($url)
    {
        global $wpdb;
        $url = str_replace($this->uploadUrl, '', $url);
        $attachment = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%%%s';", $url));
        return $attachment ? $attachment[0] : null;
    }

    private function getSizeFromUrl($url)
    {
        if (preg_match('/-(\d+)x(\d+)\.(jpg|jpeg|png|gif|webp)$/i', $url, $matches)) {
            return $matches[1] . 'x' . $matches[2];
        }
        return 'full';
    }
    
    private function debugLog($message) {
        $logger = new Logger();
        $logger->log('Cloudflare R2 Debug: ' . $message, 'info');
    }
    
    public function rewriteAttachmentUrl($url, $attachment_id) {
        $r2_url = get_post_meta($attachment_id, 'cloudflare_r2_url', true);
        
         $this->debugLog('rewriteAttachmentUrl '.$attachment_id.' '.$r2_url);
         $this->debugLog('rewriteAttachmentUrl '.$url.' '.$r2_url);
        
        return $r2_url ? $this->replace_r2_bucket_url_with_domain($r2_url) : $url;
    }
}