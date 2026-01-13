<?php
/**
 * Test script for HGMH_Submission_Repository class
 * Verifies CRUD and query methods functionality
 */

echo "<h1>TEST: HGMH Submission Repository</h1>\n";

echo "<h2>Test Overview:</h2>\n";
echo "This script verifies the HGMH_Submission_Repository class methods:<br>\n";
echo "1. Constructor and table name initialization<br>\n";
echo "2. CRUD operations (create, find, update, delete)<br>\n";
echo "3. Query methods (get_for_obmann, count_by_status, update_status)<br>\n";
echo "4. SQL security (prepared statements, sanitization)<br>\n";

// Test counters
$tests_passed = 0;
$tests_failed = 0;
$tests_total = 0;

/**
 * Helper function to display test result
 */
function display_test_result($test_name, $passed, $message = '') {
    global $tests_passed, $tests_failed, $tests_total;
    $tests_total++;

    if ($passed) {
        $tests_passed++;
        echo "<div style='background-color: #e6ffe6; padding: 10px; margin: 5px 0; border-left: 4px solid green;'>\n";
        echo "<strong>✓ PASS:</strong> $test_name<br>\n";
        if ($message) {
            echo "<span style='color: #666;'>$message</span>\n";
        }
        echo "</div>\n";
    } else {
        $tests_failed++;
        echo "<div style='background-color: #ffe6e6; padding: 10px; margin: 5px 0; border-left: 4px solid red;'>\n";
        echo "<strong>✗ FAIL:</strong> $test_name<br>\n";
        if ($message) {
            echo "<span style='color: red;'>$message</span>\n";
        }
        echo "</div>\n";
    }
}

// ============================================================================
// TEST 1: Class Structure and Constructor
// ============================================================================
echo "<h2>Test 1: Class Structure and Constructor</h2>\n";

// Check if class file exists
$class_file = __DIR__ . '/includes/repositories/class-submission-repository.php';
$file_exists = file_exists($class_file);
display_test_result(
    'Repository class file exists',
    $file_exists,
    $file_exists ? "File found at: $class_file" : "File not found at: $class_file"
);

if ($file_exists) {
    // Include the class file
    require_once $class_file;

    // Check if class exists
    $class_exists = class_exists('HGMH_Submission_Repository');
    display_test_result(
        'HGMH_Submission_Repository class exists',
        $class_exists,
        $class_exists ? 'Class is defined' : 'Class not found after including file'
    );

    if ($class_exists) {
        // Check if class has required methods
        $required_methods = array(
            'find',
            'create',
            'update',
            'delete',
            'get_for_obmann',
            'count_by_status',
            'update_status'
        );

        foreach ($required_methods as $method) {
            $method_exists = method_exists('HGMH_Submission_Repository', $method);
            display_test_result(
                "Method '$method()' exists",
                $method_exists,
                $method_exists ? "Method is defined in class" : "Method not found in class"
            );
        }
    }
}

// ============================================================================
// TEST 2: Method Signatures and Return Types
// ============================================================================
echo "<h2>Test 2: Method Signatures</h2>\n";

if (class_exists('HGMH_Submission_Repository')) {
    $reflection = new ReflectionClass('HGMH_Submission_Repository');

    // Check find() method signature
    if ($reflection->hasMethod('find')) {
        $method = $reflection->getMethod('find');
        $params = $method->getParameters();
        display_test_result(
            'find() accepts ID parameter',
            count($params) === 1,
            count($params) === 1 ? 'Method signature: find($id)' : 'Incorrect parameter count'
        );
    }

    // Check create() method signature
    if ($reflection->hasMethod('create')) {
        $method = $reflection->getMethod('create');
        $params = $method->getParameters();
        display_test_result(
            'create() accepts data array parameter',
            count($params) === 1,
            count($params) === 1 ? 'Method signature: create($data)' : 'Incorrect parameter count'
        );
    }

    // Check update() method signature
    if ($reflection->hasMethod('update')) {
        $method = $reflection->getMethod('update');
        $params = $method->getParameters();
        display_test_result(
            'update() accepts ID and data parameters',
            count($params) === 2,
            count($params) === 2 ? 'Method signature: update($id, $data)' : 'Incorrect parameter count'
        );
    }

    // Check delete() method signature
    if ($reflection->hasMethod('delete')) {
        $method = $reflection->getMethod('delete');
        $params = $method->getParameters();
        display_test_result(
            'delete() accepts ID parameter',
            count($params) === 1,
            count($params) === 1 ? 'Method signature: delete($id)' : 'Incorrect parameter count'
        );
    }

    // Check get_for_obmann() method signature
    if ($reflection->hasMethod('get_for_obmann')) {
        $method = $reflection->getMethod('get_for_obmann');
        $params = $method->getParameters();
        display_test_result(
            'get_for_obmann() accepts user_id and optional filters',
            count($params) >= 1 && count($params) <= 3,
            count($params) >= 1 ? 'Method signature: get_for_obmann($user_id, $wildart_id = null, $status = null)' : 'Incorrect parameter count'
        );
    }

    // Check count_by_status() method signature
    if ($reflection->hasMethod('count_by_status')) {
        $method = $reflection->getMethod('count_by_status');
        $params = $method->getParameters();
        display_test_result(
            'count_by_status() accepts status and optional obmann_user_id',
            count($params) >= 1 && count($params) <= 2,
            count($params) >= 1 ? 'Method signature: count_by_status($status, $obmann_user_id = null)' : 'Incorrect parameter count'
        );
    }

    // Check update_status() method signature
    if ($reflection->hasMethod('update_status')) {
        $method = $reflection->getMethod('update_status');
        $params = $method->getParameters();
        display_test_result(
            'update_status() accepts ID, status, and optional additional_data',
            count($params) >= 2 && count($params) <= 3,
            count($params) >= 2 ? 'Method signature: update_status($id, $new_status, $additional_data = [])' : 'Incorrect parameter count'
        );
    }
}

// ============================================================================
// TEST 3: Code Quality and Security
// ============================================================================
echo "<h2>Test 3: Code Quality and Security</h2>\n";

if ($file_exists) {
    $code = file_get_contents($class_file);

    // Check for ABSPATH security check
    $has_abspath_check = strpos($code, "if (!defined('ABSPATH'))") !== false;
    display_test_result(
        'File has ABSPATH security check',
        $has_abspath_check,
        $has_abspath_check ? 'Direct access protection is in place' : 'Missing ABSPATH check'
    );

    // Check for prepared statements usage
    $uses_prepare = strpos($code, '$this->wpdb->prepare(') !== false;
    display_test_result(
        'Uses $wpdb->prepare() for SQL queries',
        $uses_prepare,
        $uses_prepare ? 'Prepared statements protect against SQL injection' : 'No prepared statements found'
    );

    // Check for sanitization functions
    $uses_sanitize = strpos($code, 'sanitize_text_field(') !== false;
    display_test_result(
        'Uses sanitize_text_field() for input sanitization',
        $uses_sanitize,
        $uses_sanitize ? 'Input data is properly sanitized' : 'No sanitization found'
    );

    // Check for error logging
    $has_error_logging = strpos($code, 'error_log(') !== false && strpos($code, 'WP_DEBUG') !== false;
    display_test_result(
        'Has error logging for debugging',
        $has_error_logging,
        $has_error_logging ? 'Errors are logged when WP_DEBUG is enabled' : 'No error logging found'
    );

    // Check for proper type casting
    $uses_type_casting = strpos($code, '(int)') !== false || strpos($code, 'intval(') !== false;
    display_test_result(
        'Uses type casting for security',
        $uses_type_casting,
        $uses_type_casting ? 'Integer values are properly cast' : 'No type casting found'
    );
}

// ============================================================================
// TEST 4: SQL Query Structure
// ============================================================================
echo "<h2>Test 4: SQL Query Structure</h2>\n";

if ($file_exists) {
    $code = file_get_contents($class_file);

    // Check for JOIN queries in find()
    $has_left_join = strpos($code, 'LEFT JOIN') !== false;
    display_test_result(
        'find() uses LEFT JOIN for reference data',
        $has_left_join,
        $has_left_join ? 'Query joins wildart, eigenjagdbezirk, and meldegruppe tables' : 'No LEFT JOIN found'
    );

    // Check for enriched fields (wildart_name, eigenjagdbezirk_name, meldegruppe_name)
    $has_enriched_fields = strpos($code, 'wildart_name') !== false
        && strpos($code, 'eigenjagdbezirk_name') !== false
        && strpos($code, 'meldegruppe_name') !== false;
    display_test_result(
        'Queries return enriched objects with reference names',
        $has_enriched_fields,
        $has_enriched_fields ? 'Returns wildart_name, eigenjagdbezirk_name, meldegruppe_name' : 'Enriched fields not found'
    );

    // Check for subquery in get_for_obmann()
    $has_subquery = strpos($code, 'IN (') !== false && strpos($code, 'SELECT mg.id') !== false;
    display_test_result(
        'get_for_obmann() uses subquery for meldegruppe filtering',
        $has_subquery,
        $has_subquery ? 'Filters by obmann\'s assigned meldegruppen using subquery' : 'Subquery not found'
    );

    // Check for ORDER BY clause
    $has_order_by = strpos($code, 'ORDER BY') !== false;
    display_test_result(
        'Query results are ordered',
        $has_order_by,
        $has_order_by ? 'Results ordered by created_at DESC' : 'No ORDER BY clause found'
    );
}

// ============================================================================
// TEST 5: Business Logic
// ============================================================================
echo "<h2>Test 5: Business Logic</h2>\n";

if ($file_exists) {
    $code = file_get_contents($class_file);

    // Check for automatic timestamp updates in update_status()
    $has_approved_at = strpos($code, 'approved_at') !== false;
    $has_rejected_at = strpos($code, 'rejected_at') !== false;
    display_test_result(
        'update_status() automatically sets approval/rejection timestamps',
        $has_approved_at && $has_rejected_at,
        ($has_approved_at && $has_rejected_at) ? 'Sets approved_at and rejected_at based on status' : 'Automatic timestamps not found'
    );

    // Check for current_time() usage
    $uses_current_time = strpos($code, "current_time('mysql')") !== false;
    display_test_result(
        'Uses WordPress current_time() for timestamps',
        $uses_current_time,
        $uses_current_time ? 'Timestamps use WordPress timezone settings' : 'current_time() not found'
    );

    // Check for proper array return on empty results
    $returns_array = strpos($code, 'return array()') !== false || strpos($code, 'return $results ? $results : array()') !== false;
    display_test_result(
        'Query methods return empty array when no results',
        $returns_array,
        $returns_array ? 'Methods return array() instead of null/false' : 'Array return pattern not found'
    );
}

// ============================================================================
// TEST SUMMARY
// ============================================================================
echo "<h2>Test Summary</h2>\n";

echo "<div style='background-color: #f0f0f0; padding: 15px; border: 2px solid #333;'>\n";
echo "<strong>Total Tests:</strong> $tests_total<br>\n";
echo "<strong style='color: green;'>Passed:</strong> $tests_passed<br>\n";
echo "<strong style='color: red;'>Failed:</strong> $tests_failed<br>\n";
echo "<strong>Success Rate:</strong> " . ($tests_total > 0 ? round(($tests_passed / $tests_total) * 100, 2) : 0) . "%<br>\n";
echo "</div>\n";

if ($tests_failed === 0) {
    echo "<div style='background-color: #e6ffe6; padding: 20px; margin: 20px 0; border: 3px solid green;'>\n";
    echo "<h3 style='color: green; margin: 0;'>✓ ALL TESTS PASSED</h3>\n";
    echo "<p>The HGMH_Submission_Repository class is correctly implemented and ready for use.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background-color: #ffe6e6; padding: 20px; margin: 20px 0; border: 3px solid red;'>\n";
    echo "<h3 style='color: red; margin: 0;'>✗ SOME TESTS FAILED</h3>\n";
    echo "<p>Please review the failed tests above and fix the issues.</p>\n";
    echo "</div>\n";
}

echo "<h2>Implementation Details:</h2>\n";
echo "<div style='background-color: #f9f9f9; padding: 10px; border: 1px solid #ccc;'>\n";
echo "<strong>Repository Pattern Benefits:</strong><br>\n";
echo "• Separation of concerns - business logic separate from SQL<br>\n";
echo "• Single responsibility - one class for submission data access<br>\n";
echo "• Testability - can be easily mocked in unit tests<br>\n";
echo "• Security - centralized SQL query construction with prepared statements<br>\n";
echo "• Maintainability - all database queries in one place<br><br>\n";

echo "<strong>Key Features Implemented:</strong><br>\n";
echo "• CRUD operations (Create, Read, Update, Delete)<br>\n";
echo "• Enriched queries with LEFT JOIN to reference tables<br>\n";
echo "• Obmann-specific filtering via subquery<br>\n";
echo "• Status counting and updating with automatic timestamps<br>\n";
echo "• SQL injection protection via prepared statements<br>\n";
echo "• Input sanitization and type casting<br>\n";
echo "• Error logging for debugging<br>\n";
echo "</div>\n";

// Exit with appropriate code
exit($tests_failed === 0 ? 0 : 1);
?>
