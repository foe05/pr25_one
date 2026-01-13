<?php
/**
 * Public Form Integration Test Helper
 *
 * This script provides helper functions for testing the public form workflow.
 * Can be executed via WP-CLI, code snippets plugin, or direct inclusion.
 *
 * Usage via WP-CLI:
 *   wp eval-file wp-content/plugins/abschussplan-hgmh/tests/test-helper-public-form.php
 *
 * Usage in WordPress (Code Snippets plugin):
 *   Include this file and call test functions
 */

// Exit if accessed directly (not via WordPress)
if (!defined('ABSPATH')) {
    // For WP-CLI execution
    if (defined('WP_CLI') && WP_CLI) {
        require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    } else {
        die('This script must be run within WordPress environment.');
    }
}

/**
 * Test Helper Class for Public Form Integration Tests
 */
class AHGMH_Public_Form_Test_Helper {

    /**
     * Run all automated tests
     */
    public static function run_all_tests() {
        echo "\n=== AHGMH Public Form Integration Tests ===\n\n";

        $results = array(
            'database_schema' => self::test_database_schema(),
            'verification_service' => self::test_verification_service(),
            'rate_limiter' => self::test_rate_limiter(),
            'email_sending' => self::test_email_configuration(),
            'token_generation' => self::test_token_generation(),
            'ip_detection' => self::test_ip_detection(),
        );

        // Summary
        echo "\n=== Test Summary ===\n";
        $passed = 0;
        $failed = 0;
        foreach ($results as $test_name => $result) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo sprintf("%-30s %s\n", $test_name, $status);
            if ($result) {
                $passed++;
            } else {
                $failed++;
            }
        }
        echo sprintf("\nTotal: %d | Passed: %d | Failed: %d\n", count($results), $passed, $failed);

        return $results;
    }

    /**
     * Test 1: Database Schema Verification
     */
    public static function test_database_schema() {
        global $wpdb;

        echo "Test 1: Database Schema Verification\n";
        echo "=====================================\n";

        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        // Check table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        echo "✓ Table exists: " . ($table_exists ? 'YES' : 'NO') . "\n";

        if (!$table_exists) {
            echo "❌ FAIL: Table does not exist\n\n";
            return false;
        }

        // Check required columns
        $required_columns = array(
            'verification_status',
            'verification_token',
            'token_expires_at',
            'submitter_email',
            'submitter_ip'
        );

        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
        $column_names = wp_list_pluck($columns, 'Field');

        $all_columns_exist = true;
        foreach ($required_columns as $col) {
            $exists = in_array($col, $column_names);
            echo "  - $col: " . ($exists ? '✓' : '✗') . "\n";
            if (!$exists) {
                $all_columns_exist = false;
            }
        }

        // Check verification_status enum values
        $status_column = wp_list_filter($columns, array('Field' => 'verification_status'));
        if (!empty($status_column)) {
            $status_type = reset($status_column)['Type'];
            echo "  - verification_status type: $status_type\n";
            $has_enum = strpos($status_type, "enum('pending','verified','expired')") !== false;
            echo "  - Correct enum values: " . ($has_enum ? '✓' : '✗') . "\n";
        }

        // Check indexes
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name", ARRAY_A);
        $index_names = wp_list_pluck($indexes, 'Key_name');
        $has_token_index = in_array('verification_token', $index_names);
        $has_status_index = in_array('verification_status', $index_names);
        echo "  - verification_token index: " . ($has_token_index ? '✓' : '✗') . "\n";
        echo "  - verification_status index: " . ($has_status_index ? '✓' : '✗') . "\n";

        $success = $all_columns_exist && $has_token_index && $has_status_index;
        echo ($success ? "✅ PASS\n" : "❌ FAIL\n");
        echo "\n";

        return $success;
    }

    /**
     * Test 2: Verification Service
     */
    public static function test_verification_service() {
        echo "Test 2: Verification Service\n";
        echo "=============================\n";

        if (!class_exists('AHGMH_Verification_Service')) {
            echo "❌ FAIL: AHGMH_Verification_Service class not found\n\n";
            return false;
        }

        // Check required methods exist
        $required_methods = array(
            'generate_token',
            'validate_token',
            'verify_email',
            'send_verification_email',
            'create_pending_submission',
            'get_client_ip',
            'cleanup_expired_tokens'
        );

        $all_methods_exist = true;
        foreach ($required_methods as $method) {
            $exists = method_exists('AHGMH_Verification_Service', $method);
            echo "  - $method: " . ($exists ? '✓' : '✗') . "\n";
            if (!$exists) {
                $all_methods_exist = false;
            }
        }

        // Check token expiry constant
        $has_constant = defined('AHGMH_Verification_Service::TOKEN_EXPIRY_HOURS');
        echo "  - TOKEN_EXPIRY_HOURS constant: " . ($has_constant ? '✓' : '✗') . "\n";
        if ($has_constant) {
            echo "  - Token expiry: " . AHGMH_Verification_Service::TOKEN_EXPIRY_HOURS . " hours\n";
        }

        echo ($all_methods_exist ? "✅ PASS\n" : "❌ FAIL\n");
        echo "\n";

        return $all_methods_exist;
    }

    /**
     * Test 3: Rate Limiter
     */
    public static function test_rate_limiter() {
        echo "Test 3: Rate Limiter\n";
        echo "====================\n";

        if (!class_exists('AHGMH_Rate_Limiter')) {
            echo "❌ FAIL: AHGMH_Rate_Limiter class not found\n\n";
            return false;
        }

        // Check constants
        echo "  - MAX_SUBMISSIONS: " . AHGMH_Rate_Limiter::MAX_SUBMISSIONS . "\n";
        echo "  - TIME_WINDOW: " . AHGMH_Rate_Limiter::TIME_WINDOW . " seconds (" . (AHGMH_Rate_Limiter::TIME_WINDOW / 3600) . " hours)\n";

        // Test rate limiter with dummy IP
        $test_ip = '192.168.1.100';

        // Reset first
        AHGMH_Rate_Limiter::reset_rate_limit($test_ip);

        // Check initial state
        $initial_count = AHGMH_Rate_Limiter::get_submission_count($test_ip);
        $is_limited_initial = AHGMH_Rate_Limiter::check_rate_limit($test_ip);
        echo "  - Initial count: $initial_count (should be 0)\n";
        echo "  - Initially limited: " . ($is_limited_initial ? 'YES' : 'NO') . " (should be NO)\n";

        // Simulate 5 submissions
        for ($i = 1; $i <= 5; $i++) {
            AHGMH_Rate_Limiter::increment_submission_count($test_ip);
        }

        $count_after_5 = AHGMH_Rate_Limiter::get_submission_count($test_ip);
        $is_limited_after_5 = AHGMH_Rate_Limiter::check_rate_limit($test_ip);
        echo "  - Count after 5 submissions: $count_after_5\n";
        echo "  - Limited after 5: " . ($is_limited_after_5 ? 'YES' : 'NO') . " (should be YES)\n";

        // Get rate limit info
        $info = AHGMH_Rate_Limiter::get_rate_limit_info($test_ip);
        echo "  - Remaining submissions: " . $info['remaining'] . " (should be 0)\n";
        echo "  - Is limited: " . ($info['is_limited'] ? 'YES' : 'NO') . "\n";

        // Reset for cleanup
        AHGMH_Rate_Limiter::reset_rate_limit($test_ip);

        $success = ($initial_count == 0) && (!$is_limited_initial) &&
                   ($count_after_5 == 5) && ($is_limited_after_5);

        echo ($success ? "✅ PASS\n" : "❌ FAIL\n");
        echo "\n";

        return $success;
    }

    /**
     * Test 4: Email Configuration
     */
    public static function test_email_configuration() {
        echo "Test 4: Email Configuration\n";
        echo "===========================\n";

        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        echo "  - Site name: $site_name\n";
        echo "  - Admin email: $admin_email\n";

        // Test if wp_mail is available
        $wp_mail_exists = function_exists('wp_mail');
        echo "  - wp_mail() function: " . ($wp_mail_exists ? '✓' : '✗') . "\n";

        // Note: We don't actually send test email to avoid spam
        echo "  - Note: Not sending actual test email to avoid spam\n";
        echo "  - To test email sending, use: wp_mail('test@example.com', 'Test', 'Test message');\n";

        echo "✅ PASS (Configuration check only)\n\n";

        return true;
    }

    /**
     * Test 5: Token Generation
     */
    public static function test_token_generation() {
        echo "Test 5: Token Generation\n";
        echo "========================\n";

        // Generate tokens
        $token1 = AHGMH_Verification_Service::generate_token();
        $token2 = AHGMH_Verification_Service::generate_token();

        echo "  - Token 1: " . substr($token1, 0, 32) . "...\n";
        echo "  - Token 2: " . substr($token2, 0, 32) . "...\n";

        // Check token properties
        $length_correct = (strlen($token1) == 64) && (strlen($token2) == 64);
        $are_unique = ($token1 !== $token2);
        $is_hex = ctype_xdigit($token1) && ctype_xdigit($token2);

        echo "  - Length (64 chars): " . ($length_correct ? '✓' : '✗') . "\n";
        echo "  - Unique tokens: " . ($are_unique ? '✓' : '✗') . "\n";
        echo "  - Hexadecimal format: " . ($is_hex ? '✓' : '✗') . "\n";

        $success = $length_correct && $are_unique && $is_hex;
        echo ($success ? "✅ PASS\n" : "❌ FAIL\n");
        echo "\n";

        return $success;
    }

    /**
     * Test 6: IP Detection
     */
    public static function test_ip_detection() {
        echo "Test 6: IP Detection\n";
        echo "====================\n";

        $ip = AHGMH_Verification_Service::get_client_ip();

        echo "  - Detected IP: $ip\n";

        $is_valid = filter_var($ip, FILTER_VALIDATE_IP);
        echo "  - Valid IP format: " . ($is_valid ? '✓' : '✗') . "\n";

        // Check if it's not the fallback
        $is_not_fallback = ($ip !== '0.0.0.0');
        echo "  - Not fallback IP: " . ($is_not_fallback ? '✓' : '✗') . "\n";

        $success = $is_valid && $is_not_fallback;
        echo ($success ? "✅ PASS\n" : "❌ FAIL\n");
        echo "\n";

        return $success;
    }

    /**
     * Helper: Get recent submissions with verification info
     */
    public static function get_recent_submissions($limit = 10) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, game_species, field2, verification_status,
                    submitter_email, submitter_ip,
                    DATE_FORMAT(token_expires_at, '%%Y-%%m-%%d %%H:%%i') as expires,
                    DATE_FORMAT(created_at, '%%Y-%%m-%%d %%H:%%i') as created
             FROM $table_name
             ORDER BY created_at DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);

        return $results;
    }

    /**
     * Helper: Display recent submissions
     */
    public static function display_recent_submissions($limit = 10) {
        echo "\nRecent Submissions:\n";
        echo "==================\n";

        $submissions = self::get_recent_submissions($limit);

        if (empty($submissions)) {
            echo "No submissions found.\n";
            return;
        }

        foreach ($submissions as $sub) {
            echo sprintf(
                "ID: %d | %s | %s | Status: %s | Email: %s | IP: %s | Created: %s\n",
                $sub['id'],
                $sub['game_species'],
                $sub['field2'],
                $sub['verification_status'],
                $sub['submitter_email'],
                $sub['submitter_ip'],
                $sub['created']
            );
        }
        echo "\n";
    }

    /**
     * Helper: Count submissions by status
     */
    public static function count_by_status() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        $results = $wpdb->get_results(
            "SELECT verification_status, COUNT(*) as count
             FROM $table_name
             GROUP BY verification_status",
            ARRAY_A
        );

        echo "\nSubmissions by Status:\n";
        echo "=====================\n";

        foreach ($results as $row) {
            echo sprintf("%-10s: %d\n", $row['verification_status'], $row['count']);
        }
        echo "\n";

        return $results;
    }

    /**
     * Helper: Expire old pending tokens (for testing)
     */
    public static function expire_pending_tokens() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        // Mark all pending tokens as expired
        $result = $wpdb->query(
            "UPDATE $table_name
             SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR)
             WHERE verification_status = 'pending'"
        );

        echo "Marked $result pending tokens as expired (token_expires_at set to 1 hour ago)\n";

        return $result;
    }

    /**
     * Helper: Clean up expired tokens
     */
    public static function cleanup_expired() {
        echo "\nCleaning up expired tokens...\n";

        $cleaned = AHGMH_Verification_Service::cleanup_expired_tokens();

        echo "Cleaned up: $cleaned expired tokens\n\n";

        return $cleaned;
    }

    /**
     * Helper: Reset rate limit for IP
     */
    public static function reset_rate_limit_for_ip($ip = null) {
        if ($ip === null) {
            $ip = AHGMH_Verification_Service::get_client_ip();
        }

        AHGMH_Rate_Limiter::reset_rate_limit($ip);

        echo "Rate limit reset for IP: $ip\n";
    }

    /**
     * Helper: Get rate limit info for IP
     */
    public static function get_rate_limit_info($ip = null) {
        if ($ip === null) {
            $ip = AHGMH_Verification_Service::get_client_ip();
        }

        $info = AHGMH_Rate_Limiter::get_rate_limit_info($ip);

        echo "\nRate Limit Info for IP: $ip\n";
        echo "============================\n";
        echo "Current count: " . $info['current_count'] . "\n";
        echo "Max allowed: " . $info['max_allowed'] . "\n";
        echo "Remaining: " . $info['remaining'] . "\n";
        echo "Is limited: " . ($info['is_limited'] ? 'YES' : 'NO') . "\n";
        echo "Time window: " . $info['time_window_hours'] . " hours\n\n";

        return $info;
    }

    /**
     * Helper: Create test submission
     */
    public static function create_test_submission($email = null) {
        if ($email === null) {
            $email = 'test-' . time() . '@example.com';
        }

        $data = array(
            'user_id' => 0,
            'game_species' => 'Rotwild',
            'field1' => date('Y-m-d', strtotime('-1 day')),
            'field2' => 'Hirsch',
            'field3' => '',
            'field4' => 'Test submission created by test helper',
            'field5' => 'Gruppe_A',
            'field6' => 'Internal test note',
            'email' => $email,
            'ip' => AHGMH_Verification_Service::get_client_ip()
        );

        $submission_id = AHGMH_Verification_Service::create_pending_submission($data);

        if ($submission_id) {
            echo "✓ Test submission created: ID $submission_id | Email: $email\n";
        } else {
            echo "✗ Failed to create test submission\n";
        }

        return $submission_id;
    }

    /**
     * Helper: Verify a submission by ID
     */
    public static function verify_submission_by_id($submission_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        // Get token for this submission
        $token = $wpdb->get_var($wpdb->prepare(
            "SELECT verification_token FROM $table_name WHERE id = %d",
            $submission_id
        ));

        if (!$token) {
            echo "❌ Submission not found: ID $submission_id\n";
            return false;
        }

        // Verify using the token
        $result = AHGMH_Verification_Service::verify_email($token);

        echo "Verification result: " . ($result['success'] ? '✅' : '❌') . " " . $result['message'] . "\n";

        return $result['success'];
    }
}

// Auto-run tests if executed directly via WP-CLI
if (defined('WP_CLI') && WP_CLI) {
    AHGMH_Public_Form_Test_Helper::run_all_tests();
    echo "\n";
    AHGMH_Public_Form_Test_Helper::display_recent_submissions(5);
    AHGMH_Public_Form_Test_Helper::count_by_status();
    AHGMH_Public_Form_Test_Helper::get_rate_limit_info();
}

// Example usage in WordPress (via Code Snippets plugin or functions.php):
/*
// Run all tests
AHGMH_Public_Form_Test_Helper::run_all_tests();

// Display recent submissions
AHGMH_Public_Form_Test_Helper::display_recent_submissions(10);

// Count by status
AHGMH_Public_Form_Test_Helper::count_by_status();

// Create test submission
$submission_id = AHGMH_Public_Form_Test_Helper::create_test_submission('mytest@example.com');

// Verify submission manually
AHGMH_Public_Form_Test_Helper::verify_submission_by_id($submission_id);

// Clean up expired tokens
AHGMH_Public_Form_Test_Helper::cleanup_expired();

// Reset rate limit
AHGMH_Public_Form_Test_Helper::reset_rate_limit_for_ip('192.168.1.1');

// Check rate limit info
AHGMH_Public_Form_Test_Helper::get_rate_limit_info('192.168.1.1');
*/
