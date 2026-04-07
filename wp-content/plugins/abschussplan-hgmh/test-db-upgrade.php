<?php
/**
 * Database Upgrade Test Script
 *
 * This script verifies that the database upgrade mechanism works correctly
 * and that all new indexes were created successfully.
 *
 * Usage: Place this file in the plugin directory and access it via browser
 * or run via wp-cli: wp eval-file wp-content/plugins/abschussplan-hgmh/test-db-upgrade.php
 */

// Exit if accessed directly (when not in WordPress context)
if (!defined('ABSPATH')) {
    // Allow direct access for testing purposes with proper WordPress loading
    $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('WordPress not found. Please run this script through wp-cli or access via browser when logged in as admin.');
    }
}

// Verify user has admin capabilities
if (!current_user_can('manage_options')) {
    die('Error: You must be an administrator to run this test.');
}

echo "<h1>Database Upgrade Test - AHGMH Plugin</h1>\n\n";

// Test 1: Check DB Version
echo "<h2>Test 1: Database Version Check</h2>\n";
$installed_version = get_option('ahgmh_db_version');
$expected_version = AHGMH_DB_VERSION;
echo "Installed DB Version: " . ($installed_version ?: 'NOT SET') . "\n";
echo "Expected DB Version: $expected_version\n";
if ($installed_version === $expected_version) {
    echo "✅ PASS: DB version is correct\n\n";
} else {
    echo "❌ FAIL: DB version mismatch\n\n";
}

// Test 2: Check Submissions Table Indexes
echo "<h2>Test 2: Submissions Table Indexes</h2>\n";
global $wpdb;
$submissions_table = $wpdb->prefix . 'ahgmh_submissions';
$indexes = $wpdb->get_results("SHOW INDEX FROM $submissions_table", ARRAY_A);

$expected_indexes = array(
    'game_species_idx',
    'category_idx',
    'jagdbezirk_idx',
    'created_at_idx',
    'species_category_idx',
    'species_jagdbezirk_idx',
    'species_date_idx'
);

$found_indexes = array();
foreach ($indexes as $index) {
    if (in_array($index['Key_name'], $expected_indexes)) {
        $found_indexes[] = $index['Key_name'];
    }
}

echo "Expected indexes: " . count($expected_indexes) . "\n";
echo "Found indexes: " . count($found_indexes) . "\n\n";

foreach ($expected_indexes as $expected_idx) {
    if (in_array($expected_idx, $found_indexes)) {
        echo "✅ $expected_idx - FOUND\n";
    } else {
        echo "❌ $expected_idx - MISSING\n";
    }
}

if (count($found_indexes) === count($expected_indexes)) {
    echo "\n✅ PASS: All submissions table indexes created\n\n";
} else {
    echo "\n❌ FAIL: Some indexes are missing\n\n";
}

// Test 3: Check Jagdbezirke Table Index
echo "<h2>Test 3: Jagdbezirke Table Index</h2>\n";
$jagdbezirke_table = $wpdb->prefix . 'ahgmh_jagdbezirke';
$jagd_indexes = $wpdb->get_results("SHOW INDEX FROM $jagdbezirke_table", ARRAY_A);

$jagd_idx_found = false;
foreach ($jagd_indexes as $index) {
    if ($index['Key_name'] === 'jagdbezirk_idx') {
        $jagd_idx_found = true;
        echo "✅ jagdbezirk_idx - FOUND\n";
        echo "   Column: " . $index['Column_name'] . "\n";
        break;
    }
}

if (!$jagd_idx_found) {
    echo "❌ jagdbezirk_idx - MISSING\n";
}
echo "\n";

// Test 4: Test Index Usage with EXPLAIN
echo "<h2>Test 4: Index Usage Verification (EXPLAIN)</h2>\n";

// Test query 1: Species filter
$explain_query1 = "EXPLAIN SELECT * FROM $submissions_table WHERE game_species = 'Rotwild'";
$explain_result1 = $wpdb->get_results($explain_query1, ARRAY_A);
if ($explain_result1 && count($explain_result1) > 0) {
    $possible_keys = $explain_result1[0]['possible_keys'];
    $key_used = $explain_result1[0]['key'];
    echo "Query 1: Filter by game_species\n";
    echo "  Possible keys: " . ($possible_keys ?: 'NULL') . "\n";
    echo "  Key used: " . ($key_used ?: 'NULL') . "\n";
    if (strpos($possible_keys, 'game_species') !== false || strpos($key_used, 'game_species') !== false) {
        echo "  ✅ PASS: Using species index\n\n";
    } else {
        echo "  ⚠️  WARNING: Not using species index\n\n";
    }
}

// Test query 2: Composite index (species + category)
$explain_query2 = "EXPLAIN SELECT COUNT(*) FROM $submissions_table WHERE game_species = 'Rotwild' AND field2 = 'Hirsch'";
$explain_result2 = $wpdb->get_results($explain_query2, ARRAY_A);
if ($explain_result2 && count($explain_result2) > 0) {
    $possible_keys = $explain_result2[0]['possible_keys'];
    $key_used = $explain_result2[0]['key'];
    echo "Query 2: Filter by game_species AND category (field2)\n";
    echo "  Possible keys: " . ($possible_keys ?: 'NULL') . "\n";
    echo "  Key used: " . ($key_used ?: 'NULL') . "\n";
    if (strpos($possible_keys, 'species_category') !== false || strpos($key_used, 'species_category') !== false) {
        echo "  ✅ PASS: Using composite species_category index\n\n";
    } else {
        echo "  ⚠️  INFO: Using other index (acceptable)\n\n";
    }
}

// Test query 3: JOIN with jagdbezirke
$explain_query3 = "EXPLAIN SELECT s.*, j.meldegruppe FROM $submissions_table s LEFT JOIN $jagdbezirke_table j ON s.field5 = j.jagdbezirk WHERE s.game_species = 'Rotwild'";
$explain_result3 = $wpdb->get_results($explain_query3, ARRAY_A);
if ($explain_result3 && count($explain_result3) > 0) {
    echo "Query 3: JOIN with jagdbezirke table\n";
    foreach ($explain_result3 as $row) {
        echo "  Table: " . $row['table'] . "\n";
        echo "    Possible keys: " . ($row['possible_keys'] ?: 'NULL') . "\n";
        echo "    Key used: " . ($row['key'] ?: 'NULL') . "\n";
    }
    echo "\n";
}

// Test 5: Display All Indexes Summary
echo "<h2>Test 5: Complete Index Summary</h2>\n";
echo "<h3>Submissions Table ($submissions_table)</h3>\n";
echo "<pre>";
foreach ($indexes as $index) {
    echo sprintf("%-30s %-20s %s\n",
        $index['Key_name'],
        $index['Column_name'],
        $index['Non_unique'] == 0 ? 'UNIQUE' : 'NON-UNIQUE'
    );
}
echo "</pre>\n\n";

echo "<h3>Jagdbezirke Table ($jagdbezirke_table)</h3>\n";
echo "<pre>";
foreach ($jagd_indexes as $index) {
    echo sprintf("%-30s %-20s %s\n",
        $index['Key_name'],
        $index['Column_name'],
        $index['Non_unique'] == 0 ? 'UNIQUE' : 'NON-UNIQUE'
    );
}
echo "</pre>\n\n";

// Final Summary
echo "<h2>Final Summary</h2>\n";
$tests_passed = 0;
$total_tests = 3;

if ($installed_version === $expected_version) $tests_passed++;
if (count($found_indexes) === count($expected_indexes)) $tests_passed++;
if ($jagd_idx_found) $tests_passed++;

echo "Tests Passed: $tests_passed / $total_tests\n";
if ($tests_passed === $total_tests) {
    echo "✅ <strong>ALL TESTS PASSED</strong>\n";
    echo "\nThe database upgrade mechanism is working correctly.\n";
    echo "All indexes have been created successfully.\n";
} else {
    echo "❌ <strong>SOME TESTS FAILED</strong>\n";
    echo "\nPlease review the output above to identify issues.\n";
}

echo "\n<em>Test completed at: " . date('Y-m-d H:i:s') . "</em>\n";
