<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPI_DB {
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'inquiry_leads';
    }

    public static function create_tables() {
        global $wpdb;
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL DEFAULT '',
            customer_name varchar(100) DEFAULT '',
            email varchar(100) NOT NULL DEFAULT '',
            phone varchar(60) DEFAULT '',
            source_url text DEFAULT '',
            payload_json longtext DEFAULT '',
            intent_grade varchar(10) DEFAULT 'A',
            status varchar(20) DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY email (email),
            KEY intent_grade (intent_grade),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Migrate legacy columns if upgrading from earlier version
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $cols = $wpdb->get_col("DESC $table_name", 0);
            if (in_array('product_url', $cols, true) && !in_array('source_url', $cols, true)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN source_url text DEFAULT '' AFTER phone");
                $wpdb->query("UPDATE $table_name SET source_url = product_url WHERE source_url = '' OR source_url IS NULL");
            }
            if (in_array('form_data', $cols, true) && !in_array('payload_json', $cols, true)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN payload_json longtext DEFAULT '' AFTER source_url");
            }
            if (in_array('contact', $cols, true)) {
                $wpdb->query("ALTER TABLE $table_name MODIFY COLUMN contact varchar(150) NULL DEFAULT ''");
            }
        }
    }
}
