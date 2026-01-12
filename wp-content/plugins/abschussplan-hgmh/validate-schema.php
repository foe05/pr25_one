<?php
/**
 * Database Schema Validation Script
 *
 * This script validates that migration 001 created the correct schema:
 * - Semantic column names (wildart_id, harvest_date, wus_number, etc.)
 * - NO field1-field6 columns
 * - Correct indexes and relationships
 *
 * Usage:
 * - CLI: php validate-schema.php
 * - Browser: Access as admin with ?validate_schema=1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    // If running from command line, load WordPress
    if (php_sapi_name() === 'cli') {
        // Try to find wp-load.php
        $wp_load_paths = [
            __DIR__ . '/../../../wp-load.php',
            __DIR__ . '/../../../../wp-load.php',
            __DIR__ . '/../../../../../wp-load.php',
        ];

        foreach ($wp_load_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                break;
            }
        }

        if (!defined('ABSPATH')) {
            die("Error: Could not find WordPress installation\n");
        }
    } else {
        exit;
    }
}

/**
 * Schema Validation Class
 */
class AHGMH_Schema_Validator {

    private $wpdb;
    private $results = [];
    private $errors = [];
    private $warnings = [];

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Run all validation checks
     */
    public function validate_all() {
        $this->log_header("🔍 Database Schema Validation for Migration 001");
        $this->log_separator();

        // Check 1: Verify tables exist
        $this->check_tables_exist();

        // Check 2: Validate wp_hgmh_submissions_v2 schema
        $this->validate_submissions_v2_schema();

        // Check 3: Verify NO field1-field6 columns
        $this->check_no_legacy_fields();

        // Check 4: Validate all table schemas
        $this->validate_wildarten_schema();
        $this->validate_meldegruppen_schema();
        $this->validate_eigenjagdbezirke_schema();
        $this->validate_moderation_history_schema();
        $this->validate_activity_log_schema();

        // Check 5: Verify indexes
        $this->validate_indexes();

        // Check 6: Verify relationships (via indexed columns)
        $this->validate_relationships();

        // Print summary
        $this->print_summary();

        return count($this->errors) === 0;
    }

    /**
     * Check 1: Verify all 6 tables exist
     */
    private function check_tables_exist() {
        $this->log_test("Check 1: Verify All 6 Tables Exist");

        $expected_tables = [
            'hgmh_wildarten',
            'hgmh_meldegruppen',
            'hgmh_eigenjagdbezirke',
            'hgmh_submissions_v2',
            'hgmh_moderation_history',
            'hgmh_activity_log'
        ];

        $all_exist = true;
        foreach ($expected_tables as $table) {
            $full_table_name = $this->wpdb->prefix . $table;
            $exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") === $full_table_name;

            if ($exists) {
                $this->log_success("Table exists: {$full_table_name}");
            } else {
                $this->log_error("Table missing: {$full_table_name}");
                $this->errors[] = "Missing table: {$full_table_name}";
                $all_exist = false;
            }
        }

        if ($all_exist) {
            $this->results['tables_exist'] = true;
            $this->log_pass("All 6 tables exist");
        } else {
            $this->results['tables_exist'] = false;
            $this->log_fail("Some tables are missing");
        }
    }

    /**
     * Check 2: Validate wp_hgmh_submissions_v2 has semantic columns
     */
    private function validate_submissions_v2_schema() {
        $this->log_test("Check 2: Validate wp_hgmh_submissions_v2 Schema");

        $table_name = $this->wpdb->prefix . 'hgmh_submissions_v2';

        // Check if table exists first
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            $this->log_fail("Table {$table_name} does not exist - skipping validation");
            $this->errors[] = "Cannot validate schema - table missing";
            return;
        }

        $columns = $this->wpdb->get_results("DESCRIBE {$table_name}");

        // Required semantic columns with their expected types
        $required_columns = [
            'id' => 'bigint',
            'wildart_id' => 'bigint',
            'eigenjagdbezirk_id' => 'bigint',
            'category' => 'varchar',
            'harvest_date' => 'datetime',
            'wus_number' => 'varchar',
            'internal_note' => 'text',
            'submitted_by_user_id' => 'bigint',
            'submitted_by_email' => 'varchar',
            'submitted_at' => 'datetime',
            'status' => 'varchar',
            'verification_token' => 'varchar',
            'verified_at' => 'datetime',
            'approved_by_user_id' => 'bigint',
            'approved_at' => 'datetime',
            'approval_comment' => 'text',
            'time_to_email_verify' => 'int',
            'time_to_approval' => 'int',
            'total_processing_time' => 'int',
            'updated_at' => 'datetime'
        ];

        $all_found = true;
        foreach ($required_columns as $col_name => $expected_type) {
            $found = false;
            $actual_type = null;

            foreach ($columns as $column) {
                if ($column->Field === $col_name) {
                    $found = true;
                    $actual_type = $column->Type;
                    break;
                }
            }

            if ($found) {
                // Check type matches (case-insensitive, partial match)
                if (stripos($actual_type, $expected_type) !== false) {
                    $this->log_success("Column '{$col_name}' exists with type '{$actual_type}'");
                } else {
                    $this->log_warning("Column '{$col_name}' exists but type mismatch: expected '{$expected_type}', got '{$actual_type}'");
                    $this->warnings[] = "Type mismatch for {$col_name}";
                }
            } else {
                $this->log_error("Column missing: {$col_name}");
                $this->errors[] = "Missing column: {$col_name}";
                $all_found = false;
            }
        }

        if ($all_found) {
            $this->results['submissions_v2_schema'] = true;
            $this->log_pass("All semantic columns exist in submissions_v2");
        } else {
            $this->results['submissions_v2_schema'] = false;
            $this->log_fail("Some semantic columns are missing");
        }
    }

    /**
     * Check 3: Verify NO field1-field6 columns exist
     */
    private function check_no_legacy_fields() {
        $this->log_test("Check 3: Verify NO Legacy field1-field6 Columns");

        $table_name = $this->wpdb->prefix . 'hgmh_submissions_v2';

        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            $this->log_warning("Table {$table_name} does not exist - skipping check");
            return;
        }

        $columns = $this->wpdb->get_results("DESCRIBE {$table_name}");

        $legacy_fields = ['field1', 'field2', 'field3', 'field4', 'field5', 'field6'];
        $found_legacy = false;

        foreach ($columns as $column) {
            if (in_array($column->Field, $legacy_fields)) {
                $this->log_error("Legacy column found: {$column->Field}");
                $this->errors[] = "Legacy column exists: {$column->Field}";
                $found_legacy = true;
            }
        }

        if (!$found_legacy) {
            $this->results['no_legacy_fields'] = true;
            $this->log_pass("No legacy field1-field6 columns found ✓");
        } else {
            $this->results['no_legacy_fields'] = false;
            $this->log_fail("Legacy field columns found - schema not fully migrated");
        }
    }

    /**
     * Check 4a: Validate wildarten schema
     */
    private function validate_wildarten_schema() {
        $this->validate_table_schema('hgmh_wildarten', [
            'id' => 'bigint',
            'name' => 'varchar',
            'display_order' => 'int',
            'is_active' => 'tinyint',
            'created_at' => 'datetime'
        ]);
    }

    /**
     * Check 4b: Validate meldegruppen schema
     */
    private function validate_meldegruppen_schema() {
        $this->validate_table_schema('hgmh_meldegruppen', [
            'id' => 'bigint',
            'wildart_id' => 'bigint',
            'name' => 'varchar',
            'obmann_user_id' => 'bigint',
            'is_active' => 'tinyint',
            'created_at' => 'datetime'
        ]);
    }

    /**
     * Check 4c: Validate eigenjagdbezirke schema
     */
    private function validate_eigenjagdbezirke_schema() {
        $this->validate_table_schema('hgmh_eigenjagdbezirke', [
            'id' => 'bigint',
            'meldegruppe_id' => 'bigint',
            'name' => 'varchar',
            'description' => 'text',
            'is_active' => 'tinyint',
            'created_at' => 'datetime'
        ]);
    }

    /**
     * Check 4d: Validate moderation_history schema
     */
    private function validate_moderation_history_schema() {
        $this->validate_table_schema('hgmh_moderation_history', [
            'id' => 'bigint',
            'submission_id' => 'bigint',
            'action' => 'varchar',
            'performed_by_user_id' => 'bigint',
            'performed_by_email' => 'varchar',
            'old_status' => 'varchar',
            'new_status' => 'varchar',
            'comment' => 'text',
            'performed_at' => 'datetime'
        ]);
    }

    /**
     * Check 4e: Validate activity_log schema
     */
    private function validate_activity_log_schema() {
        $this->validate_table_schema('hgmh_activity_log', [
            'id' => 'bigint',
            'user_id' => 'bigint',
            'user_email' => 'varchar',
            'ip_address_hash' => 'varchar',
            'action' => 'varchar',
            'entity_type' => 'varchar',
            'entity_id' => 'bigint',
            'details' => 'text',
            'created_at' => 'datetime'
        ]);
    }

    /**
     * Helper: Validate a table's schema
     */
    private function validate_table_schema($table_name, $expected_columns) {
        $this->log_test("Validate {$table_name} Schema");

        $full_table_name = $this->wpdb->prefix . $table_name;

        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") !== $full_table_name) {
            $this->log_fail("Table {$full_table_name} does not exist");
            $this->errors[] = "Missing table: {$full_table_name}";
            return;
        }

        $columns = $this->wpdb->get_results("DESCRIBE {$full_table_name}");

        $all_found = true;
        foreach ($expected_columns as $col_name => $expected_type) {
            $found = false;
            foreach ($columns as $column) {
                if ($column->Field === $col_name) {
                    $found = true;
                    if (stripos($column->Type, $expected_type) === false) {
                        $this->log_warning("{$table_name}.{$col_name}: expected {$expected_type}, got {$column->Type}");
                    } else {
                        $this->log_success("{$col_name} ✓");
                    }
                    break;
                }
            }

            if (!$found) {
                $this->log_error("Missing column: {$col_name}");
                $this->errors[] = "{$table_name} missing column: {$col_name}";
                $all_found = false;
            }
        }

        if ($all_found) {
            $this->log_pass("{$table_name} schema valid");
        } else {
            $this->log_fail("{$table_name} schema invalid");
        }
    }

    /**
     * Check 5: Validate indexes
     */
    private function validate_indexes() {
        $this->log_test("Check 5: Validate Indexes");

        // Key indexes to check
        $index_checks = [
            'hgmh_submissions_v2' => ['wildart_id', 'eigenjagdbezirk_id', 'status', 'harvest_date'],
            'hgmh_meldegruppen' => ['wildart_id', 'obmann_user_id'],
            'hgmh_eigenjagdbezirke' => ['meldegruppe_id'],
            'hgmh_moderation_history' => ['submission_id', 'performed_by_user_id', 'action'],
            'hgmh_activity_log' => ['user_id', 'action', 'created_at']
        ];

        $all_indexes_valid = true;

        foreach ($index_checks as $table => $expected_indexes) {
            $full_table_name = $this->wpdb->prefix . $table;

            if ($this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") !== $full_table_name) {
                continue;
            }

            $indexes = $this->wpdb->get_results("SHOW INDEX FROM {$full_table_name}");
            $index_columns = [];

            foreach ($indexes as $index) {
                $index_columns[] = $index->Column_name;
            }

            foreach ($expected_indexes as $expected_col) {
                if (in_array($expected_col, $index_columns)) {
                    $this->log_success("{$table}: index on {$expected_col} ✓");
                } else {
                    $this->log_warning("{$table}: missing index on {$expected_col}");
                    $this->warnings[] = "Missing index: {$table}.{$expected_col}";
                }
            }
        }

        $this->log_pass("Index validation complete");
    }

    /**
     * Check 6: Validate relationships (via indexed foreign key columns)
     */
    private function validate_relationships() {
        $this->log_test("Check 6: Validate Relationships");

        // Check relationship columns exist
        $relationships = [
            'hgmh_meldegruppen.wildart_id' => 'hgmh_wildarten.id',
            'hgmh_eigenjagdbezirke.meldegruppe_id' => 'hgmh_meldegruppen.id',
            'hgmh_submissions_v2.wildart_id' => 'hgmh_wildarten.id',
            'hgmh_submissions_v2.eigenjagdbezirk_id' => 'hgmh_eigenjagdbezirke.id',
            'hgmh_moderation_history.submission_id' => 'hgmh_submissions_v2.id'
        ];

        $all_valid = true;
        foreach ($relationships as $from => $to) {
            list($from_table, $from_col) = explode('.', $from);
            list($to_table, $to_col) = explode('.', $to);

            $from_full = $this->wpdb->prefix . $from_table;
            $to_full = $this->wpdb->prefix . $to_table;

            // Check column exists in source table
            $from_exists = $this->column_exists($from_full, $from_col);
            $to_exists = $this->column_exists($to_full, $to_col);

            if ($from_exists && $to_exists) {
                $this->log_success("Relationship: {$from} → {$to} ✓");
            } else {
                $this->log_error("Relationship broken: {$from} → {$to}");
                $this->errors[] = "Invalid relationship: {$from} → {$to}";
                $all_valid = false;
            }
        }

        if ($all_valid) {
            $this->log_pass("All relationships valid");
        } else {
            $this->log_fail("Some relationships are broken");
        }
    }

    /**
     * Helper: Check if column exists in table
     */
    private function column_exists($table, $column) {
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return false;
        }

        $columns = $this->wpdb->get_results("DESCRIBE {$table}");
        foreach ($columns as $col) {
            if ($col->Field === $column) {
                return true;
            }
        }
        return false;
    }

    /**
     * Print summary
     */
    private function print_summary() {
        $this->log_separator();
        $this->log_header("📊 Validation Summary");
        $this->log_separator();

        $total_checks = count($this->results);
        $passed = count(array_filter($this->results, function($r) { return $r === true; }));
        $failed = $total_checks - $passed;

        $this->log_info("Total Checks: {$total_checks}");
        $this->log("Passed: {$passed}");
        $this->log("Failed: {$failed}");
        $this->log("Warnings: " . count($this->warnings));
        $this->log("Errors: " . count($this->errors));

        $this->log_separator();

        if (count($this->errors) > 0) {
            $this->log_header("❌ VALIDATION FAILED");
            $this->log("\nErrors found:");
            foreach ($this->errors as $error) {
                $this->log("  • {$error}");
            }
        } else {
            $this->log_header("✅ ALL VALIDATION CHECKS PASSED");
            $this->log("\n✓ Schema has semantic column names");
            $this->log("✓ No legacy field1-field6 columns");
            $this->log("✓ All tables exist with correct schemas");
            $this->log("✓ Indexes are in place");
            $this->log("✓ Relationships are valid");
        }

        if (count($this->warnings) > 0) {
            $this->log("\n⚠️  Warnings:");
            foreach ($this->warnings as $warning) {
                $this->log("  • {$warning}");
            }
        }

        $this->log_separator();
    }

    // Logging helpers
    private function log_pass($message) {
        $this->log_success("✅ PASS: {$message}");
    }

    private function log_fail($message) {
        $this->log_error("❌ FAIL: {$message}");
    }

    private function log_header($message) {
        $this->log("\n" . $message . "\n");
    }

    private function log_separator() {
        $this->log(str_repeat("=", 70));
    }

    private function log_test($message) {
        $this->log("\n{$message}");
    }

    private function log_info($message) {
        $this->log("   ℹ️  {$message}");
    }

    private function log_success($message) {
        $this->log("   ✓ {$message}");
    }

    private function log_error($message) {
        $this->log("   ✗ {$message}");
    }

    private function log_warning($message) {
        $this->log("   ⚠️  {$message}");
    }

    private function log($message) {
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        } else {
            echo $message . "<br>\n";
        }
    }
}

// Run validation if this file is accessed directly or via CLI
if (php_sapi_name() === 'cli' || (isset($_GET['validate_schema']) && current_user_can('manage_options'))) {
    $validator = new AHGMH_Schema_Validator();
    $success = $validator->validate_all();

    if (php_sapi_name() === 'cli') {
        exit($success ? 0 : 1);
    }
}
