<?php
/**
 * Email Service Test Script
 *
 * Tests all email functionality:
 * - Verification emails
 * - Approval notifications
 * - Approval confirmations
 * - Rejection notifications
 * - Email logging
 * - Variable replacement
 *
 * To run this test:
 * 1. Access via browser: yoursite.com/wp-content/plugins/abschussplan-hgmh/test-email-service.php?action=test
 * 2. Or via WP-CLI: wp eval-file test-email-service.php
 *
 * Note: This is a temporary test file and should be removed before production deployment.
 */

// Load WordPress environment
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('Error: Could not load WordPress environment. Please run this script from within WordPress or adjust the path.');
    }
}

// Security check - only allow admins to run this test
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run this test script.');
}

// Load the email service
require_once plugin_dir_path(__FILE__) . 'includes/services/class-email-service.php';

/**
 * Test Email Service Class
 */
class AHGMH_Email_Service_Test {

    private $results = array();
    private $test_email = '';

    /**
     * Constructor
     */
    public function __construct() {
        // Use admin email as test recipient
        $this->test_email = get_option('admin_email');

        // Add filter to capture emails instead of actually sending them (optional)
        // Uncomment the line below if you want to test without actually sending emails
        // add_filter('wp_mail', array($this, 'capture_email'), 10, 1);
    }

    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo '<div style="font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5;">';
        echo '<h1 style="color: #2c5530;">AHGMH Email Service - Test Suite</h1>';
        echo '<p><strong>Test Email:</strong> ' . esc_html($this->test_email) . '</p>';
        echo '<hr>';

        // Run each test
        $this->test_verification_email();
        $this->test_approval_notification();
        $this->test_approval_confirmation();
        $this->test_rejection_notification();
        $this->test_email_logging();

        // Display summary
        $this->display_summary();

        echo '</div>';
    }

    /**
     * Test verification email
     */
    private function test_verification_email() {
        echo '<h2 style="color: #2c5530;">Test 1: Verification Email</h2>';

        $submission = array(
            'id' => 1,
            'email' => $this->test_email,
            'name' => 'Max Musterjäger',
            'wildart' => 'Rotwild',
            'datum' => date('d.m.Y'),
            'link' => home_url('/test-link')
        );

        $result = AHGMH_Email_Service::send_verification_email($submission);

        $this->log_result('Verification Email', $result, $submission);
    }

    /**
     * Test approval notification to Obmann
     */
    private function test_approval_notification() {
        echo '<h2 style="color: #2c5530;">Test 2: Approval Notification (to Obmann)</h2>';

        $submission = array(
            'id' => 2,
            'email' => $this->test_email,
            'name' => 'Max Musterjäger',
            'wildart' => 'Rotwild',
            'meldegruppe' => 'Testgruppe A',
            'datum' => date('d.m.Y'),
            'kategorie' => 'Hirsch',
            'anzahl' => 1
        );

        $result = AHGMH_Email_Service::send_approval_notification($submission, true);

        $this->log_result('Approval Notification', $result, $submission);
    }

    /**
     * Test approval confirmation to submitter
     */
    private function test_approval_confirmation() {
        echo '<h2 style="color: #2c5530;">Test 3: Approval Confirmation</h2>';

        $submission = array(
            'id' => 3,
            'email' => $this->test_email,
            'name' => 'Max Musterjäger',
            'wildart' => 'Rotwild',
            'datum' => date('d.m.Y'),
            'link' => home_url('/test-link')
        );

        $result = AHGMH_Email_Service::send_approval_confirmation($submission);

        $this->log_result('Approval Confirmation', $result, $submission);
    }

    /**
     * Test rejection notification
     */
    private function test_rejection_notification() {
        echo '<h2 style="color: #2c5530;">Test 4: Rejection Notification</h2>';

        $submission = array(
            'id' => 4,
            'email' => $this->test_email,
            'name' => 'Max Musterjäger',
            'wildart' => 'Rotwild',
            'datum' => date('d.m.Y'),
            'link' => home_url('/test-link')
        );

        $reason = 'Dies ist ein Testgrund für die Ablehnung. Die Daten entsprechen nicht den Anforderungen.';

        $result = AHGMH_Email_Service::send_rejection_notification($submission, $reason);

        $this->log_result('Rejection Notification', $result, $submission, array('reason' => $reason));
    }

    /**
     * Test email logging functionality
     */
    private function test_email_logging() {
        echo '<h2 style="color: #2c5530;">Test 5: Email Logging</h2>';

        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_email_log';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">';
            echo '<strong>❌ FAILED:</strong> Email log table does not exist. Run plugin activation to create it.';
            echo '</div>';
            $this->results['Email Logging'] = false;
            return;
        }

        // Get recent email logs
        $logs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY sent_at DESC LIMIT 10",
            ARRAY_A
        );

        echo '<div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;">';
        echo '<strong>✅ SUCCESS:</strong> Email log table exists. Found ' . count($logs) . ' recent entries.';
        echo '</div>';

        if (!empty($logs)) {
            echo '<h3>Recent Email Log Entries:</h3>';
            echo '<table style="width: 100%; border-collapse: collapse; background: white; margin: 10px 0;">';
            echo '<thead><tr style="background: #2c5530; color: white;">';
            echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">ID</th>';
            echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Type</th>';
            echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Recipient</th>';
            echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Subject</th>';
            echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Sent At</th>';
            echo '</tr></thead><tbody>';

            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($log['id']) . '</td>';
                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($log['email_type']) . '</td>';
                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($log['recipient']) . '</td>';
                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($log['subject']) . '</td>';
                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($log['sent_at']) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        $this->results['Email Logging'] = true;
    }

    /**
     * Log test result
     */
    private function log_result($test_name, $result, $submission, $extra = array()) {
        $this->results[$test_name] = $result;

        if ($result) {
            echo '<div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;">';
            echo '<strong>✅ SUCCESS:</strong> Email sent successfully';
        } else {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">';
            echo '<strong>❌ FAILED:</strong> Email could not be sent';
        }

        echo '<br><strong>Recipient:</strong> ' . esc_html($submission['email']);
        echo '<br><strong>Test Data:</strong> ' . esc_html(json_encode(array_merge($submission, $extra)));
        echo '</div>';
    }

    /**
     * Display test summary
     */
    private function display_summary() {
        echo '<hr>';
        echo '<h2 style="color: #2c5530;">Test Summary</h2>';

        $total = count($this->results);
        $passed = count(array_filter($this->results));
        $failed = $total - $passed;

        $summary_color = ($failed === 0) ? '#d4edda' : '#fff3cd';
        $text_color = ($failed === 0) ? '#155724' : '#856404';

        echo '<div style="background: ' . $summary_color . '; color: ' . $text_color . '; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;">';
        echo '<h3 style="margin-top: 0;">Results:</h3>';
        echo '<p><strong>Total Tests:</strong> ' . $total . '</p>';
        echo '<p><strong>Passed:</strong> ' . $passed . '</p>';
        echo '<p><strong>Failed:</strong> ' . $failed . '</p>';

        if ($failed === 0) {
            echo '<p style="font-size: 18px; font-weight: bold;">🎉 All tests passed!</p>';
        } else {
            echo '<p style="font-size: 18px; font-weight: bold;">⚠️ Some tests failed. Please check the results above.</p>';
        }
        echo '</div>';

        echo '<h3>Individual Test Results:</h3>';
        echo '<ul>';
        foreach ($this->results as $test => $result) {
            $icon = $result ? '✅' : '❌';
            $status = $result ? 'PASSED' : 'FAILED';
            echo '<li><strong>' . $icon . ' ' . esc_html($test) . ':</strong> ' . $status . '</li>';
        }
        echo '</ul>';

        echo '<hr>';
        echo '<h3>Next Steps:</h3>';
        echo '<ol>';
        echo '<li>Check your email inbox (' . esc_html($this->test_email) . ') for the test emails</li>';
        echo '<li>Verify that variable replacement works correctly ({{name}}, {{wildart}}, etc.)</li>';
        echo '<li>Check the email log table in the database for entries</li>';
        echo '<li>If using an email logging plugin (e.g., WP Mail Logging), check the plugin logs</li>';
        echo '<li>Test both HTML and plain-text versions of the emails</li>';
        echo '</ol>';

        echo '<div style="background: #d1ecf1; color: #0c5460; padding: 10px; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0;">';
        echo '<strong>ℹ️ Note:</strong> This test script should be removed before deploying to production.';
        echo '</div>';
    }

    /**
     * Capture email for testing (optional)
     */
    public function capture_email($args) {
        // Log the email instead of sending it
        error_log('Test Email Captured: ' . print_r($args, true));
        return $args;
    }
}

// Run the tests
$test = new AHGMH_Email_Service_Test();
$test->run_all_tests();
