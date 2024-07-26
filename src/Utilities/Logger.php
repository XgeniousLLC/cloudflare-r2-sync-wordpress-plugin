<?php

namespace Xgenious\CloudflareR2Sync\Utilities;

class Logger
{
    private $log_file;

    public function __construct()
    {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/cloudflare_r2_sync_log.txt';
    }
    public static function inlineLog($message, $level = 'info', $file = '', $line = '')
    {
        $timestamp = current_time('mysql');

        // Get the calling file and line if not provided
        if (empty($file) || empty($line)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1];
            $file = $caller['file'] ?? 'unknown';
            $line = $caller['line'] ?? 'unknown';
        }

        // Extract just the filename from the full path
        $file = basename($file);

        $formatted_message = sprintf(
            "[%s] [%s] [%s:%s]: %s\n",
            $timestamp,
            strtoupper($level),
            $file,
            $line,
            $message
        );

        file_put_contents((new self)->log_file, $formatted_message, FILE_APPEND);
    }
    public function log($message, $level = 'info', $file = '', $line = '')
    {
        $timestamp = current_time('mysql');

        // Get the calling file and line if not provided
        if (empty($file) || empty($line)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1];
            $file = $caller['file'] ?? 'unknown';
            $line = $caller['line'] ?? 'unknown';
        }

        // Extract just the filename from the full path
        $file = basename($file);

        $formatted_message = sprintf(
            "[%s] [%s] [%s:%s]: %s\n",
            $timestamp,
            strtoupper($level),
            $file,
            $line,
            $message
        );

        file_put_contents($this->log_file, $formatted_message, FILE_APPEND);
    }

    public function get_logs($lines = 100)
    {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$logs) {
            return [];
        }

        return array_slice($logs, -$lines);
    }

    public function clear_logs()
    {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }
}