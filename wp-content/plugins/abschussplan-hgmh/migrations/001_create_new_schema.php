<?php
/**
 * Migration 001: Create New Database Schema
 */

if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Migration_001 {

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Wildarten (Game Species)
        $sql_wildarten = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_wildarten (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            display_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name),
            KEY is_active (is_active)
        ) $charset_collate;";

        // 2. Meldegruppen
        $sql_meldegruppen = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_meldegruppen (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wildart_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            obmann_user_id bigint(20) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wildart_name (wildart_id, name),
            KEY wildart_id (wildart_id),
            KEY obmann_user_id (obmann_user_id)
        ) $charset_collate;";

        // 3. Eigenjagdbezirke
        $sql_eigenjagdbezirke = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_eigenjagdbezirke (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            meldegruppe_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY meldegruppe_name (meldegruppe_id, name),
            KEY meldegruppe_id (meldegruppe_id)
        ) $charset_collate;";

        // 4. Submissions V2 (with workflow)
        $sql_submissions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_submissions_v2 (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wildart_id bigint(20) NOT NULL,
            eigenjagdbezirk_id bigint(20) NOT NULL,
            category varchar(100) NOT NULL,
            harvest_date datetime NOT NULL,
            wus_number varchar(50) DEFAULT NULL,
            internal_note text,
            submitted_by_user_id bigint(20) DEFAULT NULL,
            submitted_by_email varchar(255) DEFAULT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'pending_email',
            verification_token varchar(64) DEFAULT NULL,
            verified_at datetime DEFAULT NULL,
            approved_by_user_id bigint(20) DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            approval_comment text,
            time_to_email_verify int(11) DEFAULT NULL,
            time_to_approval int(11) DEFAULT NULL,
            total_processing_time int(11) DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wildart_id (wildart_id),
            KEY eigenjagdbezirk_id (eigenjagdbezirk_id),
            KEY status (status),
            KEY harvest_date (harvest_date)
        ) $charset_collate;";

        // 5. Moderation History
        $sql_moderation = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_moderation_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            performed_by_user_id bigint(20) DEFAULT NULL,
            performed_by_email varchar(255) DEFAULT NULL,
            old_status varchar(50) DEFAULT NULL,
            new_status varchar(50) DEFAULT NULL,
            comment text,
            performed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY performed_by_user_id (performed_by_user_id),
            KEY action (action)
        ) $charset_collate;";

        // 6. Activity Log
        $sql_activity = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_activity_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            user_email varchar(255) DEFAULT NULL,
            ip_address_hash varchar(64),
            action varchar(100) NOT NULL,
            entity_type varchar(50) DEFAULT NULL,
            entity_id bigint(20) DEFAULT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Execute migrations
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_wildarten);
        dbDelta($sql_meldegruppen);
        dbDelta($sql_eigenjagdbezirke);
        dbDelta($sql_submissions);
        dbDelta($sql_moderation);
        dbDelta($sql_activity);

        return true;
    }

    public function down() {
        global $wpdb;

        // Drop in reverse order
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_activity_log");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_moderation_history");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_submissions_v2");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_eigenjagdbezirke");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_meldegruppen");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_wildarten");

        return true;
    }
}
