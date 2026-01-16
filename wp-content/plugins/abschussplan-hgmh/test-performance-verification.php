<?php
/**
 * Query Performance Verification Script
 *
 * This script verifies that the new database indexes improve query performance
 * as expected. It runs EXPLAIN queries and measures execution time.
 *
 * Usage: Place this file in the plugin directory and access it via browser
 * or run via wp-cli: wp eval-file wp-content/plugins/abschussplan-hgmh/test-performance-verification.php
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

global $wpdb;
$submissions_table = $wpdb->prefix . 'ahgmh_submissions';
$jagdbezirke_table = $wpdb->prefix . 'ahgmh_jagdbezirke';

echo "<h1>Query Performance Verification - AHGMH Plugin</h1>\n\n";
echo "<p><em>Testing query performance improvements from database indexes</em></p>\n\n";

// Helper function to run EXPLAIN and measure query time
function test_query($title, $query, $expected_index = '') {
    global $wpdb;

    echo "<h3>$title</h3>\n";
    echo "<pre><strong>Query:</strong>\n$query\n</pre>\n";

    // Run EXPLAIN
    $explain_query = "EXPLAIN $query";
    $start_time = microtime(true);
    $explain_result = $wpdb->get_results($explain_query, ARRAY_A);
    $explain_time = microtime(true) - $start_time;

    // Run actual query to measure execution time
    $start_time = microtime(true);
    $wpdb->get_results($query, ARRAY_A);
    $query_time = microtime(true) - $start_time;

    echo "<strong>EXPLAIN Output:</strong>\n";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
    echo "<tr>";
    foreach (array('table', 'type', 'possible_keys', 'key', 'key_len', 'rows', 'Extra') as $col) {
        echo "<th>$col</th>";
    }
    echo "</tr>\n";

    foreach ($explain_result as $row) {
        echo "<tr>";
        echo "<td>" . esc_html($row['table'] ?? '') . "</td>";
        echo "<td>" . esc_html($row['type'] ?? '') . "</td>";
        echo "<td>" . esc_html($row['possible_keys'] ?? 'NULL') . "</td>";
        echo "<td><strong>" . esc_html($row['key'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . esc_html($row['key_len'] ?? '') . "</td>";
        echo "<td>" . esc_html($row['rows'] ?? '') . "</td>";
        echo "<td>" . esc_html($row['Extra'] ?? '') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n\n";

    // Analyze results
    $index_used = false;
    $index_name = '';
    foreach ($explain_result as $row) {
        if (!empty($row['key']) && $row['key'] !== 'NULL') {
            $index_used = true;
            $index_name = $row['key'];
            break;
        }
    }

    echo "<strong>Performance Metrics:</strong>\n";
    echo "<ul>\n";
    echo "<li>Query execution time: <strong>" . number_format($query_time * 1000, 2) . " ms</strong></li>\n";
    echo "<li>EXPLAIN execution time: " . number_format($explain_time * 1000, 2) . " ms</li>\n";

    if ($index_used) {
        echo "<li>✅ Index used: <strong>$index_name</strong></li>\n";
        if (!empty($expected_index) && strpos($index_name, $expected_index) !== false) {
            echo "<li>✅ Expected index '$expected_index' is being used</li>\n";
        } elseif (!empty($expected_index)) {
            echo "<li>⚠️  Using '$index_name' instead of expected '$expected_index' (may still be optimal)</li>\n";
        }
    } else {
        echo "<li>❌ No index used - full table scan detected!</li>\n";
    }

    // Performance assessment
    if ($query_time < 0.1) {
        echo "<li>✅ <strong>EXCELLENT</strong> performance (&lt;100ms)</li>\n";
    } elseif ($query_time < 0.5) {
        echo "<li>✅ <strong>GOOD</strong> performance (&lt;500ms)</li>\n";
    } elseif ($query_time < 1.0) {
        echo "<li>⚠️  <strong>ACCEPTABLE</strong> performance (500-1000ms)</li>\n";
    } else {
        echo "<li>❌ <strong>POOR</strong> performance (&gt;1000ms) - needs optimization</li>\n";
    }
    echo "</ul>\n\n";

    return array(
        'index_used' => $index_used,
        'index_name' => $index_name,
        'query_time' => $query_time,
        'rows_examined' => $explain_result[0]['rows'] ?? 0
    );
}

echo "<hr>\n\n";
echo "<h2>Test 1: Species Filtering</h2>\n";
$result1 = test_query(
    "Query 1: Filter by game_species",
    "SELECT * FROM $submissions_table WHERE game_species = 'Rotwild'",
    'game_species'
);

echo "<hr>\n\n";
echo "<h2>Test 2: Species + Category Filtering (Composite Index)</h2>\n";
$result2 = test_query(
    "Query 2: Filter by game_species AND field2 (category)",
    "SELECT COUNT(*) FROM $submissions_table WHERE game_species = 'Rotwild' AND field2 = 'Hirsch'",
    'species_category'
);

echo "<hr>\n\n";
echo "<h2>Test 3: JOIN Operation with Jagdbezirke</h2>\n";
$result3 = test_query(
    "Query 3: JOIN submissions with jagdbezirke",
    "SELECT s.*, j.meldegruppe FROM $submissions_table s LEFT JOIN $jagdbezirke_table j ON s.field5 = j.jagdbezirk WHERE s.game_species = 'Rotwild'",
    'species'
);

echo "<hr>\n\n";
echo "<h2>Test 4: Date Range Filtering (Composite Index)</h2>\n";
$result4 = test_query(
    "Query 4: Filter by game_species AND created_at",
    "SELECT * FROM $submissions_table WHERE game_species = 'Rotwild' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    'species_date'
);

echo "<hr>\n\n";
echo "<h2>Test 5: Category Filtering</h2>\n";
$result5 = test_query(
    "Query 5: Filter by field2 (category) only",
    "SELECT * FROM $submissions_table WHERE field2 = 'Hirsch' LIMIT 100",
    'category'
);

echo "<hr>\n\n";
echo "<h2>Test 6: Jagdbezirk Filtering</h2>\n";
$result6 = test_query(
    "Query 6: Filter by field5 (jagdbezirk) only",
    "SELECT * FROM $submissions_table WHERE field5 = 'JB-001' LIMIT 100",
    'jagdbezirk'
);

echo "<hr>\n\n";
echo "<h2>Test 7: Created Date Filtering</h2>\n";
$result7 = test_query(
    "Query 7: Filter by created_at date range",
    "SELECT * FROM $submissions_table WHERE created_at >= '2024-01-01' ORDER BY created_at DESC LIMIT 100",
    'created_at'
);

echo "<hr>\n\n";
echo "<h2>Test 8: Complex JOIN with Multiple Filters</h2>\n";
$result8 = test_query(
    "Query 8: Complex query with JOIN and multiple filters",
    "SELECT s.*, j.meldegruppe, j.wildart FROM $submissions_table s LEFT JOIN $jagdbezirke_table j ON s.field5 = j.jagdbezirk WHERE s.game_species = 'Rotwild' AND s.field2 = 'Hirsch' AND s.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
    'species'
);

// Summary Report
echo "<hr>\n\n";
echo "<h2>Performance Summary</h2>\n\n";

$all_results = array($result1, $result2, $result3, $result4, $result5, $result6, $result7, $result8);
$tests_passed = 0;
$total_tests = count($all_results);
$total_time = 0;

foreach ($all_results as $result) {
    if ($result['index_used']) {
        $tests_passed++;
    }
    $total_time += $result['query_time'];
}

$avg_time = $total_time / $total_tests;

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Metric</th><th>Value</th><th>Status</th></tr>\n";

echo "<tr>";
echo "<td><strong>Tests with index usage</strong></td>";
echo "<td><strong>$tests_passed / $total_tests</strong></td>";
echo "<td>" . ($tests_passed >= 6 ? "✅ PASS" : "❌ FAIL") . "</td>";
echo "</tr>\n";

echo "<tr>";
echo "<td><strong>Average query time</strong></td>";
echo "<td><strong>" . number_format($avg_time * 1000, 2) . " ms</strong></td>";
echo "<td>" . ($avg_time < 0.1 ? "✅ EXCELLENT" : ($avg_time < 0.5 ? "✅ GOOD" : "⚠️  NEEDS IMPROVEMENT")) . "</td>";
echo "</tr>\n";

echo "<tr>";
echo "<td><strong>Total query time (all 8 queries)</strong></td>";
echo "<td><strong>" . number_format($total_time * 1000, 2) . " ms</strong></td>";
echo "<td>" . ($total_time < 0.8 ? "✅ EXCELLENT" : ($total_time < 2.0 ? "✅ GOOD" : "⚠️  NEEDS IMPROVEMENT")) . "</td>";
echo "</tr>\n";

echo "</table>\n\n";

// Expected vs Actual Performance
echo "<h3>Expected Performance Targets (from DATABASE_PERFORMANCE_OPTIMIZATION.md)</h3>\n";
echo "<ul>\n";
echo "<li><strong>Submission filtering:</strong> Target &lt;100ms (-88% improvement)</li>\n";
echo "<li><strong>Permission queries:</strong> Target &lt;50ms (-86% improvement)</li>\n";
echo "<li><strong>Limits calculation:</strong> Target &lt;100ms (-80% improvement)</li>\n";
echo "</ul>\n\n";

// Final Assessment
echo "<h3>Final Assessment</h3>\n";

if ($tests_passed >= 6 && $avg_time < 0.1) {
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>\n";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ ALL PERFORMANCE TARGETS MET</h4>\n";
    echo "<p style='color: #155724;'>The database indexes are working correctly and providing the expected performance improvements. ";
    echo "Query times are well below the target thresholds.</p>\n";
    echo "</div>\n";
} elseif ($tests_passed >= 6 && $avg_time < 0.5) {
    echo "<div style='background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px;'>\n";
    echo "<h4 style='color: #0c5460; margin-top: 0;'>✅ PERFORMANCE TARGETS ACCEPTABLE</h4>\n";
    echo "<p style='color: #0c5460;'>The database indexes are working and providing good performance improvements. ";
    echo "Query times are within acceptable ranges for production use.</p>\n";
    echo "</div>\n";
} elseif ($tests_passed >= 4) {
    echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>\n";
    echo "<h4 style='color: #856404; margin-top: 0;'>⚠️  PARTIAL SUCCESS</h4>\n";
    echo "<p style='color: #856404;'>Some indexes are working, but not all queries are optimized. ";
    echo "Review the detailed results above to identify which indexes may need adjustment.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>\n";
    echo "<h4 style='color: #721c24; margin-top: 0;'>❌ PERFORMANCE ISSUES DETECTED</h4>\n";
    echo "<p style='color: #721c24;'>Most queries are not using indexes. Please review the database schema and ensure all indexes were created correctly. ";
    echo "Run the test-db-upgrade.php script to verify index creation.</p>\n";
    echo "</div>\n";
}

// Recommendations
echo "\n<h3>Recommendations</h3>\n";
echo "<ul>\n";

if ($tests_passed < $total_tests) {
    echo "<li>Run <code>ANALYZE TABLE $submissions_table;</code> to update index statistics</li>\n";
    echo "<li>Verify all indexes exist with <code>SHOW INDEX FROM $submissions_table;</code></li>\n";
    echo "<li>Check MySQL query optimizer settings</li>\n";
}

if ($avg_time > 0.1) {
    echo "<li>Consider adding more specific indexes for your most common query patterns</li>\n";
    echo "<li>Review table size - very large tables may need partitioning</li>\n";
    echo "<li>Check for table locks or other database contention issues</li>\n";
}

if ($tests_passed >= 6 && $avg_time < 0.1) {
    echo "<li>✅ No action needed - performance is excellent!</li>\n";
    echo "<li>Continue monitoring query performance in production</li>\n";
    echo "<li>Consider implementing query caching for frequently accessed data</li>\n";
}

echo "</ul>\n\n";

// Database Statistics
echo "<hr>\n\n";
echo "<h2>Database Statistics</h2>\n";

// Get table row counts
$submissions_count = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");
$jagdbezirke_count = $wpdb->get_var("SELECT COUNT(*) FROM $jagdbezirke_table");

echo "<ul>\n";
echo "<li><strong>Submissions records:</strong> " . number_format($submissions_count) . "</li>\n";
echo "<li><strong>Jagdbezirke records:</strong> " . number_format($jagdbezirke_count) . "</li>\n";
echo "</ul>\n\n";

// Index summary
echo "<h3>Active Indexes on Submissions Table</h3>\n";
$indexes = $wpdb->get_results("SHOW INDEX FROM $submissions_table", ARRAY_A);
$index_names = array();
foreach ($indexes as $index) {
    if (!in_array($index['Key_name'], $index_names)) {
        $index_names[] = $index['Key_name'];
    }
}
echo "<ul>\n";
foreach ($index_names as $idx_name) {
    echo "<li><code>$idx_name</code></li>\n";
}
echo "</ul>\n\n";

echo "<h3>Active Indexes on Jagdbezirke Table</h3>\n";
$jagd_indexes = $wpdb->get_results("SHOW INDEX FROM $jagdbezirke_table", ARRAY_A);
$jagd_index_names = array();
foreach ($jagd_indexes as $index) {
    if (!in_array($index['Key_name'], $jagd_index_names)) {
        $jagd_index_names[] = $index['Key_name'];
    }
}
echo "<ul>\n";
foreach ($jagd_index_names as $idx_name) {
    echo "<li><code>$idx_name</code></li>\n";
}
echo "</ul>\n\n";

echo "<hr>\n";
echo "\n<em>Test completed at: " . date('Y-m-d H:i:s') . "</em>\n";
echo "\n<p><strong>Next Steps:</strong> Document these results and proceed with production deployment if all tests pass.</p>\n";
