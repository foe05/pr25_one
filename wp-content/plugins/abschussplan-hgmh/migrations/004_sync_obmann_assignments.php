<?php
/**
 * Migration 004: Synchronize Obmann Assignments
 *
 * This migration ensures that Obmann assignments are properly synchronized
 * between user meta (primary storage) and the database table (hgmh_meldegruppen.obmann_user_id).
 *
 * It also migrates any legacy assignments from wp_options to user meta.
 *
 * Background:
 * - The system previously had two separate storage systems for Obmann assignments
 * - System A: User Meta (ahgmh_assigned_meldegruppe_{wildart})
 * - System B: WordPress Options (ahgmh_meldegruppe_obmann)
 * - The database table (hgmh_meldegruppen.obmann_user_id) was not being updated
 *
 * This migration:
 * 1. Migrates legacy wp_options assignments to user meta
 * 2. Syncs user meta assignments to the database table
 */

if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Migration_004 {

    /**
     * User meta key prefix for meldegruppe assignments
     */
    private $user_meta_prefix = 'ahgmh_assigned_meldegruppe_';

    /**
     * Legacy option key for Obmann assignments
     */
    private $legacy_option_key = 'ahgmh_meldegruppe_obmann';

    /**
     * Run the migration
     *
     * @return bool True on success
     */
    public function up() {
        global $wpdb;

        error_log('AHGMH Migration 004: Starting Obmann assignment synchronization');

        // Step 1: Migrate legacy wp_options to user meta
        $legacy_migrated = $this->migrate_legacy_options();
        error_log(sprintf('AHGMH Migration 004: Migrated %d legacy assignments from wp_options', $legacy_migrated));

        // Step 2: Sync user meta to database table
        $db_synced = $this->sync_to_database();
        error_log(sprintf('AHGMH Migration 004: Synced %d assignments to database table', $db_synced));

        // Mark migration as complete
        update_option('ahgmh_migration_004_completed', true);
        update_option('ahgmh_migration_004_timestamp', current_time('mysql'));

        error_log('AHGMH Migration 004: Completed successfully');
        return true;
    }

    /**
     * Migrate legacy wp_options assignments to user meta
     *
     * @return int Number of assignments migrated
     */
    private function migrate_legacy_options() {
        $legacy_assignments = get_option($this->legacy_option_key, []);
        $migrated_count = 0;

        if (!is_array($legacy_assignments) || empty($legacy_assignments)) {
            return 0;
        }

        foreach ($legacy_assignments as $key => $assignment) {
            if (!isset($assignment['wildart']) || !isset($assignment['meldegruppe']) || !isset($assignment['user_id'])) {
                continue;
            }

            $wildart = sanitize_text_field($assignment['wildart']);
            $meldegruppe = sanitize_text_field($assignment['meldegruppe']);
            $user_id = absint($assignment['user_id']);

            if ($user_id <= 0 || empty($wildart) || empty($meldegruppe)) {
                continue;
            }

            // Check if user exists
            $user = get_userdata($user_id);
            if (!$user) {
                error_log(sprintf('AHGMH Migration 004: Skipping assignment - user %d not found', $user_id));
                continue;
            }

            // Create user meta key
            $meta_key = $this->user_meta_prefix . sanitize_key($wildart);

            // Check if assignment already exists in user meta
            $existing = get_user_meta($user_id, $meta_key, true);

            if (empty($existing)) {
                // Add the assignment
                add_user_meta($user_id, $meta_key, $meldegruppe, true);
                $migrated_count++;
                error_log(sprintf(
                    'AHGMH Migration 004: Migrated assignment - User %d (%s) -> %s/%s',
                    $user_id,
                    $user->display_name,
                    $wildart,
                    $meldegruppe
                ));
            } else {
                error_log(sprintf(
                    'AHGMH Migration 004: Skipping - User %d already has assignment for %s',
                    $user_id,
                    $wildart
                ));
            }
        }

        // Clean up legacy option after successful migration
        if ($migrated_count > 0) {
            delete_option($this->legacy_option_key);
            error_log('AHGMH Migration 004: Deleted legacy wp_options entry');
        }

        return $migrated_count;
    }

    /**
     * Sync user meta assignments to database table
     *
     * @return int Number of assignments synced
     */
    private function sync_to_database() {
        global $wpdb;

        $synced_count = 0;

        // Check if required tables exist
        $meldegruppen_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hgmh_meldegruppen'");
        $wildarten_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hgmh_wildarten'");

        if (!$meldegruppen_exists || !$wildarten_exists) {
            error_log('AHGMH Migration 004: Required tables do not exist, skipping DB sync');
            return 0;
        }

        // Get all user meta entries with our prefix
        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, meta_key, meta_value as meldegruppe
             FROM {$wpdb->usermeta}
             WHERE meta_key LIKE %s
             AND meta_value != ''",
            $this->user_meta_prefix . '%'
        ));

        if (empty($assignments)) {
            error_log('AHGMH Migration 004: No user meta assignments found');
            return 0;
        }

        error_log(sprintf('AHGMH Migration 004: Found %d assignments to sync', count($assignments)));

        foreach ($assignments as $assignment) {
            // Extract wildart from meta key
            $wildart = substr($assignment->meta_key, strlen($this->user_meta_prefix));
            $meldegruppe = $assignment->meldegruppe;
            $user_id = absint($assignment->user_id);

            // Find meldegruppe ID in database
            $meldegruppe_id = $wpdb->get_var($wpdb->prepare(
                "SELECT m.id FROM {$wpdb->prefix}hgmh_meldegruppen m
                 INNER JOIN {$wpdb->prefix}hgmh_wildarten w ON m.wildart_id = w.id
                 WHERE LOWER(w.name) = LOWER(%s) AND m.name = %s",
                $wildart,
                $meldegruppe
            ));

            if (!$meldegruppe_id) {
                // Try with sanitized key format
                $wildart_clean = str_replace('_', '', $wildart);
                $wildart_clean = ucfirst($wildart_clean);

                $meldegruppe_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT m.id FROM {$wpdb->prefix}hgmh_meldegruppen m
                     INNER JOIN {$wpdb->prefix}hgmh_wildarten w ON m.wildart_id = w.id
                     WHERE LOWER(w.name) = LOWER(%s) AND m.name = %s",
                    $wildart_clean,
                    $meldegruppe
                ));
            }

            if ($meldegruppe_id) {
                // Update database table
                $result = $wpdb->update(
                    $wpdb->prefix . 'hgmh_meldegruppen',
                    ['obmann_user_id' => $user_id],
                    ['id' => $meldegruppe_id],
                    ['%d'],
                    ['%d']
                );

                if ($result !== false) {
                    $synced_count++;
                    error_log(sprintf(
                        'AHGMH Migration 004: Synced to DB - Meldegruppe ID %d -> User %d',
                        $meldegruppe_id,
                        $user_id
                    ));
                }
            } else {
                error_log(sprintf(
                    'AHGMH Migration 004: Could not find meldegruppe in DB - %s/%s',
                    $wildart,
                    $meldegruppe
                ));
            }
        }

        return $synced_count;
    }

    /**
     * Rollback the migration
     *
     * Note: This only removes the migration flag, it does not remove the synced data
     * as this could cause data loss.
     *
     * @return bool True on success
     */
    public function down() {
        delete_option('ahgmh_migration_004_completed');
        delete_option('ahgmh_migration_004_timestamp');

        error_log('AHGMH Migration 004: Rollback completed (migration flag removed)');
        return true;
    }
}
