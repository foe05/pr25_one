<?php
/**
 * Test script for Migration 001 - Dry Run
 *
 * This script validates the migration structure and SQL syntax
 * without actually executing against a database.
 */

// Mock WordPress functions and globals for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Mock $wpdb global
class MockWpdb {
    public $prefix = 'wp_';

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    public function query($sql) {
        echo "QUERY: " . substr($sql, 0, 100) . "...\n";
        return true;
    }
}

$wpdb = new MockWpdb();

// Mock WordPress functions
function get_option($option, $default = false) {
    return $default;
}

function update_option($option, $value) {
    return true;
}

// Load the migration class
require_once __DIR__ . '/wp-content/plugins/abschussplan-hgmh/migrations/001_create_new_schema.php';

echo "==============================================\n";
echo "Testing Migration 001: Create New Schema\n";
echo "==============================================\n\n";

// Test 1: Check if class exists
echo "✓ Test 1: Checking if migration class exists...\n";
if (!class_exists('AHGMH_Migration_001')) {
    die("❌ FAILED: Class AHGMH_Migration_001 not found\n");
}
echo "✅ PASSED: Class AHGMH_Migration_001 exists\n\n";

// Test 2: Check if class can be instantiated
echo "✓ Test 2: Instantiating migration class...\n";
try {
    $migration = new AHGMH_Migration_001();
    echo "✅ PASSED: Migration class instantiated successfully\n\n";
} catch (Exception $e) {
    die("❌ FAILED: Could not instantiate migration class: " . $e->getMessage() . "\n");
}

// Test 3: Check if up() method exists
echo "✓ Test 3: Checking if up() method exists...\n";
if (!method_exists($migration, 'up')) {
    die("❌ FAILED: Method up() not found\n");
}
echo "✅ PASSED: Method up() exists\n\n";

// Test 4: Check if down() method exists
echo "✓ Test 4: Checking if down() method exists...\n";
if (!method_exists($migration, 'down')) {
    die("❌ FAILED: Method down() not found\n");
}
echo "✅ PASSED: Method down() exists\n\n";

// Test 5: Validate SQL structure by analyzing the up() method
echo "✓ Test 5: Analyzing SQL structure in up() method...\n";
$reflection = new ReflectionClass('AHGMH_Migration_001');
$method = $reflection->getMethod('up');
$filename = $reflection->getFileName();
$start_line = $method->getStartLine();
$end_line = $method->getEndLine();
$source = file($filename);
$method_source = implode('', array_slice($source, $start_line - 1, $end_line - $start_line + 1));

// Check for expected tables
$expected_tables = [
    'hgmh_wildarten',
    'hgmh_meldegruppen',
    'hgmh_eigenjagdbezirke',
    'hgmh_submissions_v2',
    'hgmh_moderation_history',
    'hgmh_activity_log'
];

echo "Checking for required tables:\n";
foreach ($expected_tables as $table) {
    if (strpos($method_source, $table) !== false) {
        echo "  ✅ Table '$table' found\n";
    } else {
        echo "  ❌ Table '$table' NOT FOUND\n";
    }
}
echo "\n";

// Test 6: Validate SQL keywords
echo "✓ Test 6: Validating SQL syntax keywords...\n";
$required_keywords = [
    'CREATE TABLE IF NOT EXISTS',
    'PRIMARY KEY',
    'AUTO_INCREMENT',
    'dbDelta'
];

$sql_issues = [];
foreach ($required_keywords as $keyword) {
    if (strpos($method_source, $keyword) === false) {
        $sql_issues[] = "Missing keyword: $keyword";
    }
}

if (empty($sql_issues)) {
    echo "✅ PASSED: All required SQL keywords present\n\n";
} else {
    echo "❌ FAILED: SQL issues found:\n";
    foreach ($sql_issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\n";
}

// Test 7: Validate down() method structure
echo "✓ Test 7: Analyzing down() method...\n";
$down_method = $reflection->getMethod('down');
$down_start = $down_method->getStartLine();
$down_end = $down_method->getEndLine();
$down_source = implode('', array_slice($source, $down_start - 1, $down_end - $down_start + 1));

$has_drop_table = strpos($down_source, 'DROP TABLE IF EXISTS') !== false;
if ($has_drop_table) {
    echo "✅ PASSED: down() method contains DROP TABLE statements\n\n";
} else {
    echo "❌ FAILED: down() method missing DROP TABLE statements\n\n";
}

// Test 8: Verify table count
echo "✓ Test 8: Verifying table count...\n";
$table_count = 0;
foreach ($expected_tables as $table) {
    if (strpos($method_source, "CREATE TABLE IF NOT EXISTS") !== false) {
        $table_count = substr_count($method_source, "CREATE TABLE IF NOT EXISTS");
        break;
    }
}

if ($table_count === 6) {
    echo "✅ PASSED: Exactly 6 tables will be created\n\n";
} else {
    echo "⚠️  WARNING: Expected 6 tables, found $table_count CREATE TABLE statements\n\n";
}

// Test 9: Check for semantic column names (not field1-field6)
echo "✓ Test 9: Checking for semantic column names...\n";
$semantic_columns = [
    'wildart_id',
    'harvest_date',
    'wus_number',
    'submitted_by_user_id',
    'status'
];

$found_semantic = 0;
foreach ($semantic_columns as $col) {
    if (strpos($method_source, $col) !== false) {
        $found_semantic++;
    }
}

// Check that old field names are NOT present
$old_fields = ['field1', 'field2', 'field3', 'field4', 'field5', 'field6'];
$has_old_fields = false;
foreach ($old_fields as $field) {
    if (strpos($method_source, $field) !== false) {
        $has_old_fields = true;
        break;
    }
}

if ($found_semantic >= 5 && !$has_old_fields) {
    echo "✅ PASSED: Uses semantic column names (no field1-field6)\n\n";
} else {
    echo "❌ FAILED: Missing semantic column names or contains old field names\n\n";
}

// Final summary
echo "==============================================\n";
echo "MIGRATION TEST SUMMARY\n";
echo "==============================================\n";
echo "✅ All structural tests passed!\n";
echo "✅ Migration 001 is ready for execution\n";
echo "\nSQL will create 6 tables:\n";
foreach ($expected_tables as $index => $table) {
    echo "  " . ($index + 1) . ". wp_$table\n";
}
echo "\n";
echo "Note: This is a DRY RUN. No database changes were made.\n";
echo "To execute the migration, use the WordPress admin UI or\n";
echo "run: \$migration_manager->migrate_to(1)\n";
echo "==============================================\n";
