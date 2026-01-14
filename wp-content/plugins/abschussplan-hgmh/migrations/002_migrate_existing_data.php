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
 * Class AHGMH_Migration_002
 *
 * Handles the migration of existing data from v1 to v2 schema
 */
class AHGMH_Migration_002 {

    /**
     * Run the migration
     *
     * @return bool True on success, false on failure
     */
    public function up() {
        global $wpdb;

        error_log('AHGMH Migration 002: Starting migration from v1 to v2 schema');

        // Skip if old tables don't exist
        $submissions_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ahgmh_submissions'");
        $jagdbezirke_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ahgmh_jagdbezirke'");

        if (!$submissions_exists) {
            error_log('AHGMH Migration 002: Old submissions table does not exist, skipping migration');
            update_option('ahgmh_migration_002_completed', true);
            return true;
        }

        if (!$jagdbezirke_exists) {
            error_log('AHGMH Migration 002: Old jagdbezirke table does not exist, skipping hierarchie migration');
            // Continue with just wildarten and submissions if possible
        }

        // Get count of old submissions before migration
        $old_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ahgmh_submissions");
        error_log(sprintf('AHGMH Migration 002: Found %d submissions to migrate', $old_count));

        // Migrate Wildarten
        $wildarten_result = $this->migrate_wildarten();
        if ($wildarten_result === false) {
            error_log('AHGMH Migration 002 FAILED: Wildarten migration failed');
            return false;
        }

        // Migrate Hierarchie
        $hierarchie_result = $this->migrate_hierarchie();
        if ($hierarchie_result === false) {
            error_log('AHGMH Migration 002 FAILED: Hierarchie migration failed');
            return false;
        }

        // Migrate Submissions
        $migrated_count = $this->migrate_submissions();
        if ($migrated_count === false) {
            error_log('AHGMH Migration 002 FAILED: Submissions migration failed');
            return false;
        }

        // Validate migration: check how many were migrated
        $new_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hgmh_submissions_v2");
        $skipped_count = $old_count - $migrated_count;

        if ($skipped_count > 0) {
            error_log(
                sprintf(
                    'AHGMH Migration 002 WARNING: %d of %d submissions were skipped (missing references)',
                    $skipped_count,
                    $old_count
                )
            );
        }

        // Migration succeeds even if some submissions were skipped
        // Only fail if NO submissions were migrated when there were some to migrate
        if ($old_count > 0 && $migrated_count === 0) {
            error_log('AHGMH Migration 002 FAILED: No submissions could be migrated');
            return false;
        }

        error_log(
            sprintf(
                'AHGMH Migration 002 SUCCESS: Migrated %d of %d submissions (skipped: %d)',
                $migrated_count,
                $old_count,
                $skipped_count
            )
        );

        update_option('ahgmh_migration_002_completed', true);
        update_option('ahgmh_migration_002_stats', [
            'old_count' => $old_count,
            'migrated' => $migrated_count,
            'skipped' => $skipped_count
        ]);
        return true;
    }

    /**
     * Migrate Wildarten from options to normalized table
     *
     * Migrates species from the ahgmh_species option array to the hgmh_wildarten table
     *
     * @return bool True on success, false on failure
     */
    private function migrate_wildarten() {
        global $wpdb;

        error_log('AHGMH Migration 002: Starting Wildarten migration');

        $species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        error_log(sprintf('AHGMH Migration 002: Found %d species to migrate', count($species)));

        $created_count = 0;
        $existing_count = 0;

        foreach ($species as $index => $name) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}hgmh_wildarten WHERE name = %s",
                $name
            ));

            if ($wpdb->last_error) {
                error_log(sprintf(
                    'AHGMH Migration 002 ERROR: Failed to check for existing Wildart "%s": %s',
                    $name,
                    $wpdb->last_error
                ));
                return false;
            }

            if (!$exists) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'hgmh_wildarten',
                    array(
                        'name' => $name,
                        'display_order' => $index,
                        'is_active' => 1
                    )
                );

                if ($result === false) {
                    error_log(sprintf(
                        'AHGMH Migration 002 ERROR: Failed to insert Wildart "%s": %s',
                        $name,
                        $wpdb->last_error
                    ));
                    return false;
                }

                $created_count++;
                error_log(sprintf('AHGMH Migration 002: Created Wildart "%s" (ID: %d)', $name, $wpdb->insert_id));
            } else {
                $existing_count++;
            }
        }

        error_log(sprintf(
            'AHGMH Migration 002: Wildarten migration completed - Created: %d, Existing: %d',
            $created_count,
            $existing_count
        ));

        return true;
    }

    /**
     * Migrate Jagdbezirke hierarchy
     *
     * Migrates from ahgmh_jagdbezirke to normalized structure:
     * - Creates Meldegruppen entries
     * - Creates Eigenjagdbezirke entries with proper foreign keys
     *
     * @return bool True on success, false on failure
     */
    private function migrate_hierarchie() {
        global $wpdb;

        error_log('AHGMH Migration 002: Starting Hierarchie migration');

        // Check if jagdbezirke table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ahgmh_jagdbezirke'");
        if (!$table_exists) {
            error_log('AHGMH Migration 002: Jagdbezirke table does not exist, skipping hierarchie migration');
            return true; // Not an error, just nothing to migrate
        }

        // Check which column exists for active status (active or ungueltig)
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}ahgmh_jagdbezirke");
        $has_active = false;
        $has_ungueltig = false;

        foreach ($columns as $col) {
            if ($col->Field === 'active') $has_active = true;
            if ($col->Field === 'ungueltig') $has_ungueltig = true;
        }

        // Build query based on available columns
        if ($has_active) {
            $jagdbezirke = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}ahgmh_jagdbezirke WHERE active = 1"
            );
        } elseif ($has_ungueltig) {
            $jagdbezirke = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}ahgmh_jagdbezirke WHERE ungueltig = 0"
            );
        } else {
            // No filter column, get all
            $jagdbezirke = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}ahgmh_jagdbezirke"
            );
        }

        if ($wpdb->last_error) {
            error_log(sprintf(
                'AHGMH Migration 002 ERROR: Failed to fetch Jagdbezirke: %s',
                $wpdb->last_error
            ));
            return false;
        }

        error_log(sprintf('AHGMH Migration 002: Found %d Jagdbezirke to migrate', count($jagdbezirke)));

        $meldegruppen_map = array();
        $meldegruppen_created = 0;
        $eigenjagdbezirke_created = 0;
        $skipped_wildart_count = 0;

        foreach ($jagdbezirke as $jb) {
            $wildart_name = isset($jb->wildart) ? $jb->wildart : 'Rotwild';
            $meldegruppe_name = isset($jb->meldegruppe) ? $jb->meldegruppe : 'Gruppe_A';

            // Get/Create Wildart
            $wildart_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}hgmh_wildarten WHERE name = %s",
                $wildart_name
            ));

            if ($wpdb->last_error) {
                error_log(sprintf(
                    'AHGMH Migration 002 ERROR: Failed to lookup Wildart "%s": %s',
                    $wildart_name,
                    $wpdb->last_error
                ));
                return false;
            }

            if (!$wildart_id) {
                $skipped_wildart_count++;
                error_log(sprintf(
                    'AHGMH Migration 002: Skipping Jagdbezirk "%s" - Wildart "%s" not found',
                    $jb->jagdbezirk,
                    $wildart_name
                ));
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

                if ($wpdb->last_error) {
                    error_log(sprintf(
                        'AHGMH Migration 002 ERROR: Failed to lookup Meldegruppe "%s": %s',
                        $meldegruppe_name,
                        $wpdb->last_error
                    ));
                    return false;
                }

                if (!$mg_id) {
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'hgmh_meldegruppen',
                        array(
                            'wildart_id' => $wildart_id,
                            'name' => $meldegruppe_name,
                            'is_active' => 1
                        )
                    );

                    if ($result === false) {
                        error_log(sprintf(
                            'AHGMH Migration 002 ERROR: Failed to insert Meldegruppe "%s": %s',
                            $meldegruppe_name,
                            $wpdb->last_error
                        ));
                        return false;
                    }

                    $mg_id = $wpdb->insert_id;
                    $meldegruppen_created++;
                    error_log(sprintf(
                        'AHGMH Migration 002: Created Meldegruppe "%s" for Wildart ID %d (ID: %d)',
                        $meldegruppe_name,
                        $wildart_id,
                        $mg_id
                    ));
                }

                $meldegruppen_map[$map_key] = $mg_id;
            }

            // Create Eigenjagdbezirk
            $result = $wpdb->insert(
                $wpdb->prefix . 'hgmh_eigenjagdbezirke',
                array(
                    'meldegruppe_id' => $meldegruppen_map[$map_key],
                    'name' => $jb->jagdbezirk,
                    'description' => isset($jb->bemerkung) ? $jb->bemerkung : '',
                    'is_active' => 1
                )
            );

            if ($result === false) {
                error_log(sprintf(
                    'AHGMH Migration 002 ERROR: Failed to insert Eigenjagdbezirk "%s": %s',
                    $jb->jagdbezirk,
                    $wpdb->last_error
                ));
                return false;
            }

            $eigenjagdbezirke_created++;
        }

        error_log(sprintf(
            'AHGMH Migration 002: Hierarchie migration completed - Meldegruppen created: %d, Eigenjagdbezirke created: %d, Skipped: %d',
            $meldegruppen_created,
            $eigenjagdbezirke_created,
            $skipped_wildart_count
        ));

        return true;
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
     * @return int|false Number of successfully migrated submissions, or false on critical error
     */
    private function migrate_submissions() {
        global $wpdb;

        error_log('AHGMH Migration 002: Starting Submissions migration');

        $submissions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ahgmh_submissions"
        );

        if ($wpdb->last_error) {
            error_log(sprintf(
                'AHGMH Migration 002 ERROR: Failed to fetch submissions: %s',
                $wpdb->last_error
            ));
            return false;
        }

        error_log(sprintf('AHGMH Migration 002: Found %d submissions to migrate', count($submissions)));

        $migrated_count = 0;
        $skipped_count = 0;

        foreach ($submissions as $old) {
            // Resolve Wildart ID
            $wildart_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}hgmh_wildarten WHERE name = %s",
                $old->game_species
            ));

            if ($wpdb->last_error) {
                error_log(sprintf(
                    'AHGMH Migration 002 ERROR: Database error looking up Wildart for submission ID %d: %s',
                    $old->id,
                    $wpdb->last_error
                ));
                return false;
            }

            if (!$wildart_id) {
                $skipped_count++;
                error_log(
                    sprintf(
                        'AHGMH Migration 002: Skipping submission ID %d - Wildart "%s" not found',
                        $old->id,
                        $old->game_species
                    )
                );
                continue;
            }

            // Resolve Eigenjagdbezirk ID
            // First, try to find it directly in the new table
            $eigenjagdbezirk_id = $wpdb->get_var($wpdb->prepare("
                SELECT e.id
                FROM {$wpdb->prefix}hgmh_eigenjagdbezirke e
                JOIN {$wpdb->prefix}hgmh_meldegruppen m ON e.meldegruppe_id = m.id
                WHERE m.wildart_id = %d AND e.name = %s
            ", $wildart_id, $old->field5));

            if ($wpdb->last_error) {
                error_log(sprintf(
                    'AHGMH Migration 002 ERROR: Database error looking up Eigenjagdbezirk for submission ID %d: %s',
                    $old->id,
                    $wpdb->last_error
                ));
                return false;
            }

            // If not found, try to create it by looking up the old jagdbezirke table
            if (!$eigenjagdbezirk_id) {
                // Look up in old jagdbezirke table to get meldegruppe info
                $old_jb = $wpdb->get_row($wpdb->prepare(
                    "SELECT meldegruppe, wildart FROM {$wpdb->prefix}ahgmh_jagdbezirke
                     WHERE jagdbezirk = %s LIMIT 1",
                    $old->field5
                ));

                if ($old_jb) {
                    // Get or create meldegruppe
                    $meldegruppe_name = $old_jb->meldegruppe ?: 'Standard';
                    $meldegruppe_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}hgmh_meldegruppen
                         WHERE wildart_id = %d AND name = %s",
                        $wildart_id, $meldegruppe_name
                    ));

                    if (!$meldegruppe_id) {
                        // Create meldegruppe
                        $wpdb->insert(
                            $wpdb->prefix . 'hgmh_meldegruppen',
                            ['wildart_id' => $wildart_id, 'name' => $meldegruppe_name, 'is_active' => 1]
                        );
                        $meldegruppe_id = $wpdb->insert_id;
                        error_log(sprintf('AHGMH Migration 002: Created Meldegruppe "%s" for submission %d', $meldegruppe_name, $old->id));
                    }

                    // Create eigenjagdbezirk
                    $wpdb->insert(
                        $wpdb->prefix . 'hgmh_eigenjagdbezirke',
                        ['meldegruppe_id' => $meldegruppe_id, 'name' => $old->field5, 'is_active' => 1]
                    );
                    $eigenjagdbezirk_id = $wpdb->insert_id;
                    error_log(sprintf('AHGMH Migration 002: Created Eigenjagdbezirk "%s" for submission %d', $old->field5, $old->id));
                }
            }

            if (!$eigenjagdbezirk_id) {
                $skipped_count++;
                error_log(
                    sprintf(
                        'AHGMH Migration 002: Skipping submission ID %d - Could not resolve Eigenjagdbezirk "%s" for Wildart ID %d',
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

            if ($result === false) {
                error_log(sprintf(
                    'AHGMH Migration 002 ERROR: Failed to insert submission ID %d: %s',
                    $old->id,
                    $wpdb->last_error
                ));
                return false;
            }

            $migrated_count++;
        }

        error_log(sprintf(
            'AHGMH Migration 002: Submissions migration completed - Migrated: %d, Skipped: %d',
            $migrated_count,
            $skipped_count
        ));

        if ($skipped_count > 0) {
            error_log(
                sprintf(
                    'AHGMH Migration 002 WARNING: Skipped %d submissions due to missing references',
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

        error_log('AHGMH Migration 002: Starting rollback (down migration)');

        // Truncate tables in reverse dependency order
        $tables = array(
            'hgmh_submissions_v2',
            'hgmh_eigenjagdbezirke',
            'hgmh_meldegruppen',
            'hgmh_wildarten'
        );

        foreach ($tables as $table) {
            $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$table}");

            if ($result === false) {
                error_log(sprintf(
                    'AHGMH Migration 002 ERROR: Failed to truncate table %s: %s',
                    $table,
                    $wpdb->last_error
                ));
                return false;
            }

            error_log(sprintf('AHGMH Migration 002: Truncated table %s', $table));
        }

        delete_option('ahgmh_migration_002_completed');
        error_log('AHGMH Migration 002: Rollback completed successfully');

        return true;
    }
}
