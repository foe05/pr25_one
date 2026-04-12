<?php
/**
 * AHGMH Email Service
 *
 * Handles all email notifications for the plugin:
 * - Verification emails to submitters
 * - Approval notifications to Obmann
 * - Approval confirmations to submitters
 * - Rejection notifications to submitters
 *
 * All emails are logged to the activity log (hgmh_email_log table)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Email_Service {

    /**
     * Send verification email to submission creator
     *
     * @param array $submission Submission data (requires: email, name, wildart, datum)
     * @return bool True if email sent successfully
     */
    public static function send_verification_email($submission) {
        if (empty($submission['email']) || empty($submission['name'])) {
            error_log('AHGMH Email Service: Missing required fields for verification email');
            return false;
        }

        $to = sanitize_email($submission['email']);
        $subject = 'Bestätigung Ihrer Abschussmeldung - HGMH';

        // Load email template
        $template_path = plugin_dir_path(dirname(__FILE__, 2)) . 'templates/email/verification.php';
        $message = self::get_email_content($template_path, $submission);

        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email
        self::log_email(
            'verification',
            $to,
            $subject,
            isset($submission['id']) ? $submission['id'] : null
        );

        return $sent;
    }

    /**
     * Send approval notification to Obmann or Vorstand
     *
     * @param array $submission Submission data
     * @param bool $to_obmann Whether to send to Obmann (true) or Vorstand (false)
     * @return bool True if email sent successfully
     */
    public static function send_approval_notification($submission, $to_obmann = true) {
        if (empty($submission['wildart']) || empty($submission['meldegruppe'])) {
            error_log('AHGMH Email Service: Missing required fields for approval notification');
            return false;
        }

        // Get recipient email
        if ($to_obmann) {
            $recipient_email = self::get_obmann_email($submission['wildart'], $submission['meldegruppe']);
        } else {
            $recipient_email = get_option('admin_email');
        }

        if (empty($recipient_email)) {
            error_log('AHGMH Email Service: No recipient email found for approval notification');
            return false;
        }

        $to = sanitize_email($recipient_email);
        $subject = 'Neue Abschussmeldung zur Freigabe - HGMH';

        // Add approval link to submission data
        $submission['approval_link'] = admin_url('admin.php?page=ahgmh-admin&action=review');

        // Load email template
        $template_path = plugin_dir_path(dirname(__FILE__, 2)) . 'templates/email/approval-notification.php';
        $message = self::get_email_content($template_path, $submission);

        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email
        self::log_email(
            'approval_notification',
            $to,
            $subject,
            isset($submission['id']) ? $submission['id'] : null
        );

        return $sent;
    }

    /**
     * Send rejection notification to submission creator
     *
     * @param array $submission Submission data (requires: email, name, wildart)
     * @param string $reason Rejection reason
     * @return bool True if email sent successfully
     */
    public static function send_rejection_notification($submission, $reason) {
        if (empty($submission['email']) || empty($submission['name'])) {
            error_log('AHGMH Email Service: Missing required fields for rejection notification');
            return false;
        }

        $to = sanitize_email($submission['email']);
        $subject = 'Ihre Abschussmeldung wurde abgelehnt - HGMH';

        // Add rejection reason and rejected_by to submission data
        $submission['reason'] = sanitize_text_field($reason);
        $current_user = wp_get_current_user();
        $submission['rejected_by'] = $current_user->display_name;

        // Load email template
        $template_path = plugin_dir_path(dirname(__FILE__, 2)) . 'templates/email/rejection.php';
        $message = self::get_email_content($template_path, $submission);

        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email
        self::log_email(
            'rejection',
            $to,
            $subject,
            isset($submission['id']) ? $submission['id'] : null
        );

        return $sent;
    }

    /**
     * Send approval confirmation to submission creator
     *
     * @param array $submission Submission data (requires: email, name, wildart, datum)
     * @return bool True if email sent successfully
     */
    public static function send_approval_confirmation($submission) {
        if (empty($submission['email']) || empty($submission['name'])) {
            error_log('AHGMH Email Service: Missing required fields for approval confirmation');
            return false;
        }

        $to = sanitize_email($submission['email']);
        $subject = 'Ihre Abschussmeldung wurde freigegeben - HGMH';

        // Add approved_by to submission data
        $current_user = wp_get_current_user();
        $submission['approved_by'] = $current_user->display_name;

        // Load email template
        $template_path = plugin_dir_path(dirname(__FILE__, 2)) . 'templates/email/approval-confirmation.php';
        $message = self::get_email_content($template_path, $submission);

        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email
        self::log_email(
            'approval_confirmation',
            $to,
            $subject,
            isset($submission['id']) ? $submission['id'] : null
        );

        return $sent;
    }

    /**
     * Get email content from template with variable replacement
     *
     * @param string $template_path Path to template file
     * @param array $data Submission data for variable replacement
     * @return string Email content with replaced variables
     */
    private static function get_email_content($template_path, $data) {
        // If template exists, load it
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $content = ob_get_clean();
        } else {
            // Fallback to simple message if template not found
            $content = 'Ihre Abschussmeldung wurde registriert.';
        }

        // Replace variables in template
        $content = self::replace_variables($content, $data);

        return $content;
    }

    /**
     * Replace template variables with actual values
     *
     * Supported variables:
     * {{name}} - Submitter name
     * {{wildart}} - Wildlife species
     * {{date}} or {{datum}} - Submission date
     * {{link}} - Action link
     * {{approval_link}} - Approval page link
     * {{reason}} - Rejection reason
     * {{approved_by}} - User who approved
     * {{rejected_by}} - User who rejected
     * {{obmann_name}} - Obmann name
     * {{submitter_name}} - Submitter name
     *
     * @param string $content Template content
     * @param array $data Data array with values
     * @return string Content with replaced variables
     */
    private static function replace_variables($content, $data) {
        $replacements = array(
            '{{name}}' => isset($data['name']) ? esc_html($data['name']) : '',
            '{{wildart}}' => isset($data['wildart']) ? esc_html($data['wildart']) : '',
            '{{art}}' => isset($data['wildart']) ? esc_html($data['wildart']) : (isset($data['art']) ? esc_html($data['art']) : ''),
            '{{date}}' => isset($data['datum']) ? esc_html($data['datum']) : '',
            '{{datum}}' => isset($data['datum']) ? esc_html($data['datum']) : '',
            '{{link}}' => isset($data['link']) ? esc_url($data['link']) : '',
            '{{approval_link}}' => isset($data['approval_link']) ? esc_url($data['approval_link']) : '',
            '{{reason}}' => isset($data['reason']) ? esc_html($data['reason']) : '',
            '{{approved_by}}' => isset($data['approved_by']) ? esc_html($data['approved_by']) : '',
            '{{rejected_by}}' => isset($data['rejected_by']) ? esc_html($data['rejected_by']) : '',
            '{{obmann_name}}' => isset($data['obmann_name']) ? esc_html($data['obmann_name']) : '',
            '{{submitter_name}}' => isset($data['submitter_name']) ? esc_html($data['submitter_name']) : (isset($data['name']) ? esc_html($data['name']) : ''),
            '{{meldegruppe}}' => isset($data['meldegruppe']) ? esc_html($data['meldegruppe']) : '',
            '{{kategorie}}' => isset($data['kategorie']) ? esc_html($data['kategorie']) : '',
            '{{anzahl}}' => isset($data['anzahl']) ? absint($data['anzahl']) : '0',
        );

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Log email to activity log database table
     *
     * @param string $email_type Type of email (verification, approval_notification, etc.)
     * @param string $recipient Email recipient
     * @param string $subject Email subject
     * @param int|null $submission_id Associated submission ID
     * @return bool True if logged successfully
     */
    private static function log_email($email_type, $recipient, $subject, $submission_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hgmh_email_log';

        $result = $wpdb->insert(
            $table_name,
            array(
                'email_type' => sanitize_text_field($email_type),
                'recipient' => sanitize_email($recipient),
                'subject' => sanitize_text_field($subject),
                'sent_at' => current_time('mysql'),
                'submission_id' => $submission_id ? absint($submission_id) : null
            ),
            array('%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            error_log('AHGMH Email Service: Failed to log email to database');
            return false;
        }

        return true;
    }

    /**
     * Get Obmann email for specific wildart and meldegruppe
     *
     * @param string $wildart Wildlife species
     * @param string $meldegruppe Meldegruppe name
     * @return string|false Obmann email or false if not found
     */
    private static function get_obmann_email($wildart, $meldegruppe) {
        // Get all users
        $users = get_users();

        foreach ($users as $user) {
            // Check if user has permission for this wildart/meldegruppe
            if (class_exists('AHGMH_Permissions_Service')) {
                if (AHGMH_Permissions_Service::user_can_access_meldegruppe($user->ID, $wildart, $meldegruppe)) {
                    return $user->user_email;
                }
            }
        }

        // Fallback to admin email if no specific Obmann found
        return get_option('admin_email');
    }

    /**
     * Get email logs for a specific submission
     *
     * @param int $submission_id Submission ID
     * @return array Array of email log entries
     */
    public static function get_submission_emails($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hgmh_email_log';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE submission_id = %d ORDER BY sent_at DESC",
                $submission_id
            ),
            ARRAY_A
        );

        return $results ? $results : array();
    }

    /**
     * Get all email logs with optional filters
     *
     * @param array $filters Optional filters (email_type, date_from, date_to, limit)
     * @return array Array of email log entries
     */
    public static function get_email_logs($filters = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hgmh_email_log';

        $where_conditions = array();
        $where_values = array();

        if (!empty($filters['email_type'])) {
            $where_conditions[] = 'email_type = %s';
            $where_values[] = $filters['email_type'];
        }

        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'sent_at >= %s';
            $where_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'sent_at <= %s';
            $where_values[] = $filters['date_to'];
        }

        $query = "SELECT * FROM $table_name";

        if (!empty($where_conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $query .= ' ORDER BY sent_at DESC';

        if (!empty($filters['limit'])) {
            $query .= ' LIMIT ' . absint($filters['limit']);
        }

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results ? $results : array();
    }
}
