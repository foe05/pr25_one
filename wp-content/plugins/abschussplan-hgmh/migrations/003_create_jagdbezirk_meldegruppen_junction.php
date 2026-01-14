<?php
/**
 * Migration 003: Create Junction Table for Jagdbezirk-Meldegruppen Many-to-Many Relationship
 *
 * This migration creates a junction table to support assigning multiple Meldegruppen
 * to each Jagdbezirk (Eigenjagdbezirk), enabling a more flexible data model.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Migration_003 {

    /**
     * Run the migration
     *
     * @return bool True on success
     */
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Junction table for Jagdbezirk <-> Meldegruppen many-to-many relationship
        $sql_junction = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_jagdbezirk_meldegruppen (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            jagdbezirk_id bigint(20) NOT NULL,
            meldegruppe_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY jagdbezirk_meldegruppe (jagdbezirk_id, meldegruppe_id),
            KEY jagdbezirk_id (jagdbezirk_id),
            KEY meldegruppe_id (meldegruppe_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_junction);

        // Migrate existing single meldegruppe_id assignments to the junction table
        $this->migrate_existing_assignments();

        return true;
    }

    /**
     * Migrate existing single meldegruppe_id assignments from eigenjagdbezirke table
     * to the new junction table
     */
    private function migrate_existing_assignments() {
        global $wpdb;

        $eigenjagdbezirke_table = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $junction_table = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';

        // Check if eigenjagdbezirke table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$eigenjagdbezirke_table}'");
        if (!$table_exists) {
            error_log('AHGMH Migration 003: Eigenjagdbezirke table does not exist, skipping assignment migration');
            return;
        }

        // Check if meldegruppe_id column exists
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$eigenjagdbezirke_table}");
        $has_meldegruppe_id = false;
        foreach ($columns as $col) {
            if ($col->Field === 'meldegruppe_id') {
                $has_meldegruppe_id = true;
                break;
            }
        }

        if (!$has_meldegruppe_id) {
            error_log('AHGMH Migration 003: meldegruppe_id column does not exist, skipping assignment migration');
            return;
        }

        // Get all existing Jagdbezirke with their meldegruppe_id
        $jagdbezirke = $wpdb->get_results(
            "SELECT id, meldegruppe_id FROM {$eigenjagdbezirke_table} WHERE meldegruppe_id > 0",
            ARRAY_A
        );

        if (empty($jagdbezirke)) {
            error_log('AHGMH Migration 003: No jagdbezirke with meldegruppe_id found, nothing to migrate');
            return;
        }

        error_log(sprintf('AHGMH Migration 003: Found %d jagdbezirke to migrate', count($jagdbezirke)));

        foreach ($jagdbezirke as $jb) {
            // Check if this assignment already exists in junction table
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$junction_table}
                 WHERE jagdbezirk_id = %d AND meldegruppe_id = %d",
                $jb['id'],
                $jb['meldegruppe_id']
            ));

            if (!$exists) {
                $wpdb->insert(
                    $junction_table,
                    [
                        'jagdbezirk_id' => $jb['id'],
                        'meldegruppe_id' => $jb['meldegruppe_id'],
                        'created_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%s']
                );
            }
        }
    }

    /**
     * Rollback the migration
     *
     * @return bool True on success
     */
    public function down() {
        global $wpdb;

        // Note: We do NOT delete data from the eigenjagdbezirke.meldegruppe_id column
        // as that would cause data loss. The column remains for backward compatibility.

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_jagdbezirk_meldegruppen");

        return true;
    }
}
