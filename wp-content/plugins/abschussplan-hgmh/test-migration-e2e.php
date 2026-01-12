<?php
/**
 * End-to-End Migration Workflow Test
 *
 * This script tests the complete migration workflow:
 * 1. Verify migration manager is initialized
 * 2. Check current version
 * 3. Run migration to version 1
 * 4. Verify tables are created
 * 5. Verify table schemas
 * 6. Rollback to version 0
 * 7. Verify tables are dropped
 *
 * Usage: Run this file from WordPress admin or via WP-CLI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    // If running from command line, load WordPress
    if (php_sapi_name() === 'cli') {
        // Try to find wp-load.php
        $wp_load_paths = [
            __DIR__ . '/../../../../wp-load.php',
            __DIR__ . '/../../../wp-load.php',
            __DIR__ . '/../../wp-load.php',
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

// Test class
class AHGMH_Migration_E2E_Test {

    private $migration_manager;
    private $results = [];
    private $errors = [];

    public function __construct() {
        $this->migration_manager = new AHGMH_Migration_Manager();
    }

    /**
     * Run all tests
     */
    public function run_all_tests() {
        $this->log_header("🧪 End-to-End Migration Workflow Test");
        $this->log_separator();

        // Test 1: Verify Migration Manager
        $this->test_migration_manager_initialized();

        // Test 2: Check Initial Version
        $this->test_initial_version();

        // Test 3: Verify Migration 001 Exists
        $this->test_migration_001_exists();

        // Test 4: Run Migration to Version 1
        $this->test_run_migration_up();

        // Test 5: Verify Tables Created
        $this->test_tables_created();

        // Test 6: Verify Table Schemas
        $this->test_table_schemas();

        // Test 7: Rollback to Version 0
        $this->test_rollback_to_zero();

        // Test 8: Verify Tables Dropped
        $this->test_tables_dropped();

        // Test 9: Verify Version Reset
        $this->test_version_reset();

        // Print Summary
        $this->print_summary();
    }

    /**
     * Test 1: Migration Manager Initialized
     */
    private function test_migration_manager_initialized() {
        $this->log_test("Test 1: Migration Manager Initialized");

        if ($this->migration_manager instanceof AHGMH_Migration_Manager) {
            $this->pass("Migration manager initialized successfully");
        } else {
            $this->fail("Migration manager failed to initialize");
        }
    }

    /**
     * Test 2: Check Initial Version
     */
    private function test_initial_version() {
        $this->log_test("Test 2: Check Initial Version");

        $current_version = $this->migration_manager->get_current_version();
        $this->log_info("Current version: {$current_version}");

        if ($current_version >= 0) {
            $this->pass("Version tracking working (current: {$current_version})");
        } else {
            $this->fail("Invalid version number");
        }
    }

    /**
     * Test 3: Migration 001 Exists
     */
    private function test_migration_001_exists() {
        $this->log_test("Test 3: Migration 001 Exists");

        $migrations = $this->migration_manager->get_available_migrations();

        if (isset($migrations[1])) {
            $this->pass("Migration 001 found: {$migrations[1]['name']}");
            $this->log_info("File: {$migrations[1]['file']}");
        } else {
            $this->fail("Migration 001 not found");
        }
    }

    /**
     * Test 4: Run Migration Up
     */
    private function test_run_migration_up() {
        $this->log_test("Test 4: Run Migration to Version 1");

        $result = $this->migration_manager->migrate_to(1);

        if ($result['success']) {
            $this->pass("Migration executed successfully");

            if (!empty($result['log'])) {
                foreach ($result['log'] as $log_entry) {
                    $this->log_info($log_entry);
                }
            }

            $this->log_info("Final version: {$result['final_version']}");
        } else {
            $this->fail("Migration failed");
            if (!empty($result['log'])) {
                foreach ($result['log'] as $log_entry) {
                    $this->log_error($log_entry);
                }
            }
        }
    }

    /**
     * Test 5: Verify Tables Created
     */
    private function test_tables_created() {
        global $wpdb;

        $this->log_test("Test 5: Verify Tables Created");

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
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") === $full_table_name;

            if ($exists) {
                $this->log_success("✓ Table exists: {$full_table_name}");
            } else {
                $this->log_error("✗ Table missing: {$full_table_name}");
                $all_exist = false;
            }
        }

        if ($all_exist) {
            $this->pass("All 6 tables created successfully");
        } else {
            $this->fail("Some tables are missing");
        }
    }

    /**
     * Test 6: Verify Table Schemas
     */
    private function test_table_schemas() {
        global $wpdb;

        $this->log_test("Test 6: Verify Table Schemas");

        // Check submissions_v2 has semantic columns (not field1-field6)
        $table_name = $wpdb->prefix . 'hgmh_submissions_v2';
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");

        $required_columns = [
            'wildart_id',
            'eigenjagdbezirk_id',
            'harvest_date',
            'wus_number',
            'status'
        ];

        $all_found = true;
        foreach ($required_columns as $col) {
            $found = false;
            foreach ($columns as $column) {
                if ($column->Field === $col) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $this->log_success("✓ Column exists: {$col}");
            } else {
                $this->log_error("✗ Column missing: {$col}");
                $all_found = false;
            }
        }

        if ($all_found) {
            $this->pass("Schema has semantic column names (no field1-field6)");
        } else {
            $this->fail("Schema is missing required columns");
        }
    }

    /**
     * Test 7: Rollback to Version 0
     */
    private function test_rollback_to_zero() {
        $this->log_test("Test 7: Rollback to Version 0");

        $result = $this->migration_manager->rollback_to(0);

        if ($result['success']) {
            $this->pass("Rollback executed successfully");

            if (!empty($result['log'])) {
                foreach ($result['log'] as $log_entry) {
                    $this->log_info($log_entry);
                }
            }

            $this->log_info("Final version: {$result['final_version']}");
        } else {
            $this->fail("Rollback failed");
            if (!empty($result['log'])) {
                foreach ($result['log'] as $log_entry) {
                    $this->log_error($log_entry);
                }
            }
        }
    }

    /**
     * Test 8: Verify Tables Dropped
     */
    private function test_tables_dropped() {
        global $wpdb;

        $this->log_test("Test 8: Verify Tables Dropped");

        $expected_tables = [
            'hgmh_wildarten',
            'hgmh_meldegruppen',
            'hgmh_eigenjagdbezirke',
            'hgmh_submissions_v2',
            'hgmh_moderation_history',
            'hgmh_activity_log'
        ];

        $all_dropped = true;
        foreach ($expected_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") === $full_table_name;

            if (!$exists) {
                $this->log_success("✓ Table dropped: {$full_table_name}");
            } else {
                $this->log_error("✗ Table still exists: {$full_table_name}");
                $all_dropped = false;
            }
        }

        if ($all_dropped) {
            $this->pass("All 6 tables dropped successfully");
        } else {
            $this->fail("Some tables still exist");
        }
    }

    /**
     * Test 9: Verify Version Reset
     */
    private function test_version_reset() {
        $this->log_test("Test 9: Verify Version Reset to 0");

        $current_version = $this->migration_manager->get_current_version();

        if ($current_version === 0) {
            $this->pass("Version correctly reset to 0");
        } else {
            $this->fail("Version not reset (current: {$current_version})");
        }
    }

    /**
     * Print Summary
     */
    private function print_summary() {
        $this->log_separator();
        $this->log_header("📊 Test Summary");
        $this->log_separator();

        $passed = count(array_filter($this->results, function($r) { return $r === true; }));
        $failed = count(array_filter($this->results, function($r) { return $r === false; }));
        $total = count($this->results);

        $this->log_info("Total Tests: {$total}");
        $this->log_success("Passed: {$passed}");

        if ($failed > 0) {
            $this->log_error("Failed: {$failed}");
            $this->log_separator();
            $this->log_header("❌ TEST SUITE FAILED");
        } else {
            $this->log_separator();
            $this->log_header("✅ ALL TESTS PASSED");
        }

        $this->log_separator();
    }

    // Logging helpers
    private function pass($message) {
        $this->results[] = true;
        $this->log_success("✅ PASS: {$message}");
    }

    private function fail($message) {
        $this->results[] = false;
        $this->errors[] = $message;
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

    private function log($message) {
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        } else {
            echo $message . "<br>\n";
        }
    }
}

// Run tests if this file is accessed directly or via CLI
if (php_sapi_name() === 'cli' || (isset($_GET['run_migration_test']) && current_user_can('manage_options'))) {
    $test = new AHGMH_Migration_E2E_Test();
    $test->run_all_tests();
}
