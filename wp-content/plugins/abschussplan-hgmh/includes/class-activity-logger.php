<?php
/**
 * Activity Logger Service Class
 *
 * Handles logging of user activities for statistics and compliance
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for logging and tracking user activities
 */
class AHGMH_Activity_Logger {
    /**
     * Table name for activity logs
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ahgmh_activity_log';
    }

    /**
     * Log an activity
     *
     * @param string $action Action type (form_view, form_submit, email_verify, submission_approve, submission_reject, submission_edit)
     * @param array $context Additional context data
     * @return int|false Insert ID on success, false on failure
     */
    public function log($action, $context = array()) {
        global $wpdb;

        // Validate action
        $valid_actions = array(
            'form_view',
            'form_submit',
            'email_verify',
            'submission_approve',
            'submission_reject',
            'submission_edit'
        );

        if (!in_array($action, $valid_actions)) {
            return false;
        }

        // Get current user info
        $user_id = get_current_user_id();

        // Get and hash IP address for GDPR compliance
        $ip_address = $this->get_ip_address();
        $ip_hash = null;

        if ($ip_address) {
            $ip_hash = hash('sha256', $ip_address);
        }

        // Prepare data
        $data = array(
            'user_id' => $user_id,
            'action' => sanitize_text_field($action),
            'context' => wp_json_encode($context),
            'ip_hash' => $ip_hash,
            'created_at' => current_time('mysql')
        );

        // Insert into database
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array('%d', '%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get IP address from request
     *
     * @return string|null IP address or null if not available
     */
    private function get_ip_address() {
        // Check for proxy headers first
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    // Validate IP address
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        // Fallback to REMOTE_ADDR
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        return null;
    }
}
