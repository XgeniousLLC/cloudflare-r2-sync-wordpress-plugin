<?php
namespace Xgenious\CloudflareR2Sync\Core;

class Activator {
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xg_cloudflare_r2_sync_log';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            file_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}