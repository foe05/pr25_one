<?php
/**
 * Migration 002: Migrate existing data from v1 to v2 schema
 *
 * Migrates all existing data from the old schema (field1-6) to the new normalized schema.
 * - Wildarten: ahgmh_species option → hgmh_wildarten table
 * - Hierarchie: ahgmh_jagdbezirke → hgmh_eigenjagdbezirke + hgmh_meldegruppen
 * - Submissions: ahgmh_submissions → hgmh_submissions_v2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class HGMH_Migration_002
 *
 * Handles the migration of existing data from v1 to v2 schema
 */
class HGMH_Migration_002 {

    /**
     * Run the migration
     *
     * @return bool True on success, false on failure
     */
    public function up() {
        global $wpdb;

        // Skip if old table doesn't exist
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ahgmh_submissions'");
        if (!$exists) {
            return true;
        }

        // Get count of old submissions before migration
        $old_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ahgmh_submissions");

        $this->migrate_wildarten();
        $this->migrate_hierarchie();
        $migrated_count = $this->migrate_submissions();

        // Validate migration: new count should equal old count
        $new_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hgmh_submissions_v2");

        if ($new_count !== $old_count) {
            $skipped_count = $old_count - $migrated_count;
            error_log(
                sprintf(
                    'HGMH Migration 002 FAILED: Count mismatch! Old: %d, New: %d, Migrated: %d, Skipped: %d',
                    $old_count,
                    $new_count,
                    $migrated_count,
                    $skipped_count
                )
            );
            return false;
        }

        error_log(
            sprintf(
                'HGMH Migration 002 SUCCESS: Migrated %d submissions from v1 to v2 schema',
                $new_count
            )
        );

        return true;
    }

    /**
     * Migrate Wildarten from options to normalized table
     *
     * Migrates species from the ahgmh_species option array to the hgmh_wildarten table
     */
    private function migrate_wildarten() {
        global $wpdb;

        $species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));

        foreach ($species as $index => $name) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}hgmh_wildarten WHERE name = %s",
                $name
            ));

            if (!$exists) {
                $wpdb->insert(
                    $wpdb->prefix . 'hgmh_wildarten',
                    array(
                        'name' => $name,
                        'display_order' => $index,
                        'is_active' => 1
                    )
                );
            }
        }
    }

    /**
     * Migrate Jagdbezirke hierarchy
     *
     * Migrates from ahgmh_jagdbezirke to normalized structure:
     * - Creates Meldegruppen entries
     * - Creates Eigenjagdbezirke entries with proper foreign keys
     */
    private function migrate_hierarchie() {
        global $wpdb;

        $jagdbezirke = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ahgmh_jagdbezirke WHERE active = 1"
        );

        $meldegruppen_map = array();

        foreach ($jagdbezirke as $jb) {
            $wildart_name = isset($jb->wildart) ? $jb->wildart : 'Rotwild';
            $meldegruppe_name = isset($jb->meldegruppe) ? $jb->meldegruppe : 'Gruppe_A';

            // Get/Create Wildart
            $wildart_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}hgmh_wildarten WHERE name = %s",
                $wildart_name
            ));

            if (!$wildart_id) {
                continue;
            }

            // Get/Create Meldegruppe
            $map_key = $wildart_id . '_' . $meldegruppe_name;
            if (!isset($meldegruppen_map[$map_key])) {
                $mg_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}hgmh_meldegruppen
                     WHERE wildart_id = %d AND name = %s",
                    $wildart_id, $meldegruppe_name
                ));

                if (!$mg_id) {
                    $wpdb->insert(
                        $wpdb->prefix . 'hgmh_meldegruppen',
                        array(
                            'wildart_id' => $wildart_id,
                            'name' => $meldegruppe_name,
                            'is_active' => 1
                        )
                    );
                    $mg_id = $wpdb->insert_id;
                }

                $meldegruppen_map[$map_key] = $mg_id;
            }

            // Create Eigenjagdbezirk
            $wpdb->insert(
                $wpdb->prefix . 'hgmh_eigenjagdbezirke',
                array(
                    'meldegruppe_id' => $meldegruppen_map[$map_key],
                    'name' => $jb->jagdbezirk,
                    'description' => isset($jb->bemerkung) ? $jb->bemerkung : '',
                    'is_active' => 1
                )
            );
        }
    }

    /**
     * Migrate submissions from old to new schema
     *
     * Field mapping:
     * - field1 → harvest_date
     * - field2 → category
     * - field3 → wus_number
     * - field5 → eigenjagdbezirk_id (via lookup)
     * - field6 → internal_note
     *
     * All migrated submissions receive status 'approved'
     *
     * @return int Number of successfully migrated submissions
     */
    private function migrate_submissions() {
        global $wpdb;

        $submissions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ahgmh_submissions"
        );

        $migrated_count = 0;
        $skipped_count = 0;

        foreach ($submissions as $old) {
            // Resolve Wildart ID
            $wildart_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}hgmh_wildarten WHERE name = %s",
                $old->game_species
            ));

            if (!$wildart_id) {
                $skipped_count++;
                error_log(
                    sprintf(
                        'HGMH Migration 002: Skipping submission ID %d - Wildart "%s" not found',
                        $old->id,
                        $old->game_species
                    )
                );
                continue;
            }

            // Resolve Eigenjagdbezirk ID
            $eigenjagdbezirk_id = $wpdb->get_var($wpdb->prepare("
                SELECT e.id
                FROM {$wpdb->prefix}hgmh_eigenjagdbezirke e
                JOIN {$wpdb->prefix}hgmh_meldegruppen m ON e.meldegruppe_id = m.id
                WHERE m.wildart_id = %d AND e.name = %s
            ", $wildart_id, $old->field5));

            if (!$eigenjagdbezirk_id) {
                $skipped_count++;
                error_log(
                    sprintf(
                        'HGMH Migration 002: Skipping submission ID %d - Eigenjagdbezirk "%s" not found for Wildart ID %d',
                        $old->id,
                        $old->field5,
                        $wildart_id
                    )
                );
                continue;
            }

            // Insert into new schema
            $result = $wpdb->insert(
                $wpdb->prefix . 'hgmh_submissions_v2',
                array(
                    'wildart_id' => $wildart_id,
                    'eigenjagdbezirk_id' => $eigenjagdbezirk_id,
                    'category' => $old->field2,
                    'harvest_date' => $old->field1,
                    'wus_number' => $old->field3,
                    'internal_note' => isset($old->field6) ? $old->field6 : '',
                    'submitted_by_user_id' => $old->user_id,
                    'submitted_at' => $old->created_at,
                    'status' => 'approved',
                    'approved_by_user_id' => $old->user_id,
                    'approved_at' => $old->created_at
                )
            );

            if ($result !== false) {
                $migrated_count++;
            }
        }

        if ($skipped_count > 0) {
            error_log(
                sprintf(
                    'HGMH Migration 002: Skipped %d submissions due to missing references',
                    $skipped_count
                )
            );
        }

        return $migrated_count;
    }

    /**
     * Rollback the migration
     *
     * Truncates all v2 tables to allow re-running the migration
     *
     * @return bool True on success, false on failure
     */
    public function down() {
        global $wpdb;

        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}hgmh_submissions_v2");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}hgmh_eigenjagdbezirke");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}hgmh_meldegruppen");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}hgmh_wildarten");

        return true;
    }
}
