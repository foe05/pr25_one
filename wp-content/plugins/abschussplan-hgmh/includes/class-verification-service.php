<?php
/**
 * Verification Service Class
 * Handles email verification for public form submissions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verification Service for email token generation and validation
 */
class AHGMH_Verification_Service {

    /**
     * Token expiration time in hours
     */
    const TOKEN_EXPIRY_HOURS = 48;

    /**
     * Constructor
     */
    public function __construct() {
        // Register init hook to handle verification requests
        add_action('init', array($this, 'handle_verification_request'));

        // Register action to display verification messages
        add_action('wp_footer', array($this, 'display_verification_message'));
    }

    /**
     * Handle verification request from URL query parameter
     * Checks for verify_email parameter, validates token, and updates status
     */
    public function handle_verification_request() {
        // Check if verify_email parameter exists
        if (!isset($_GET['verify_email'])) {
            return;
        }

        // Get and sanitize token
        $token = sanitize_text_field($_GET['verify_email']);

        // Verify the email using static method
        $result = self::verify_email($token);

        // Set up admin notice or display message
        if ($result['success']) {
            // Store success message in transient for display
            set_transient('ahgmh_verification_message', array(
                'type' => 'success',
                'message' => $result['message']
            ), 60);
        } else {
            // Store error message in transient for display
            set_transient('ahgmh_verification_message', array(
                'type' => 'error',
                'message' => $result['message']
            ), 60);
        }

        // Redirect to home page without query parameter to prevent re-processing
        wp_safe_redirect(remove_query_arg('verify_email'));
        exit;
    }

    /**
     * Display verification message from transient
     * Shows success or error message after email verification
     */
    public function display_verification_message() {
        $message = get_transient('ahgmh_verification_message');

        if (!$message) {
            return;
        }

        // Delete transient after retrieving it
        delete_transient('ahgmh_verification_message');

        $alert_class = $message['type'] === 'success' ? 'alert-success' : 'alert-danger';
        $icon = $message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle';

        echo '<div class="container mt-4">';
        echo '<div class="alert ' . esc_attr($alert_class) . ' alert-dismissible fade show" role="alert">';
        echo '<i class="bi bi-' . esc_attr($icon) . '"></i> ';
        echo esc_html($message['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Generate a secure verification token
     *
     * @return string 64-character hexadecimal token
     */
    public static function generate_token() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate verification token
     *
     * @param string $token The token to validate
     * @return array|false Submission data if valid, false otherwise
     */
    public static function validate_token($token) {
        global $wpdb;

        if (empty($token)) {
            return false;
        }

        $token      = sanitize_text_field($token);
        $table_name = $wpdb->prefix . 'hgmh_submissions_v2';

        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE verification_token = %s",
            $token
        ), ARRAY_A);

        if (!$submission) {
            return false;
        }

        // Already verified
        if (!empty($submission['verified_at'])) {
            return false;
        }

        // Check expiry using submitted_at + TOKEN_EXPIRY_HOURS
        $expires_at = strtotime($submission['submitted_at']) + (self::TOKEN_EXPIRY_HOURS * HOUR_IN_SECONDS);
        if (time() > $expires_at) {
            return false;
        }

        return $submission;
    }

    /**
     * Verify email and update submission status
     *
     * @param string $token Verification token
     * @return array Result array with success status and message
     */
    public static function verify_email($token) {
        $submission = self::validate_token($token);

        if (!$submission) {
            return array(
                'success' => false,
                'message' => __('Ungültiger oder abgelaufener Verifizierungslink.', 'abschussplan-hgmh'),
            );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'hgmh_submissions_v2';

        // Mark as verified: set verified_at, advance status to 'pending' (awaiting Obmann)
        $result = $wpdb->update(
            $table_name,
            array(
                'verified_at'          => current_time('mysql'),
                'status'               => 'pending',
                'verification_token'   => null,
            ),
            array('id' => $submission['id']),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Fehler bei der Verifizierung. Bitte versuchen Sie es später erneut.', 'abschussplan-hgmh'),
            );
        }

        return array(
            'success' => true,
            'message' => __('Email erfolgreich verifiziert! Ihre Meldung wurde bestätigt.', 'abschussplan-hgmh'),
        );
    }

    /**
     * Send verification email to submitter
     *
     * @param string $email Recipient email address
     * @param string $token Verification token
     * @param array $submission_data Submission data for email context
     * @return bool Success status
     */
    public static function send_verification_email($email, $token, $submission_data = array()) {
        // Sanitize and validate email
        $email = sanitize_email($email);

        if (!is_email($email)) {
            return false;
        }

        // Generate verification URL
        $verification_url = add_query_arg('verify_email', $token, home_url());

        // Email subject
        $subject = __('Bitte bestätigen Sie Ihre Abschuss-Meldung', 'abschussplan-hgmh');

        // Build email body
        $message = self::build_verification_email_body($verification_url, $submission_data);

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        // Send email
        $sent = wp_mail($email, $subject, $message, $headers);

        // Log for debugging if WP_DEBUG is enabled
        if (!$sent && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AHGMH: Failed to send verification email to ' . $email);
        }

        return $sent;
    }

    /**
     * Build verification email body HTML
     *
     * @param string $verification_url Verification URL
     * @param array $submission_data Submission data
     * @return string HTML email body
     */
    private static function build_verification_email_body($verification_url, $submission_data) {
        $species  = isset($submission_data['wildart_name']) ? esc_html($submission_data['wildart_name'])
                  : (isset($submission_data['game_species']) ? esc_html($submission_data['game_species']) : '');
        $category = isset($submission_data['category']) ? esc_html($submission_data['category'])
                  : (isset($submission_data['field2']) ? esc_html($submission_data['field2']) : '');

        $body = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        $body .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">';
        $body .= '<h2 style="color: #2c5f2d;">' . __('Email-Verifizierung erforderlich', 'abschussplan-hgmh') . '</h2>';
        $body .= '<p>' . __('Vielen Dank für Ihre Abschuss-Meldung.', 'abschussplan-hgmh') . '</p>';

        if ($species || $category) {
            $body .= '<p><strong>' . __('Ihre Meldung:', 'abschussplan-hgmh') . '</strong></p>';
            $body .= '<ul>';
            if ($species) {
                $body .= '<li>' . __('Wildart:', 'abschussplan-hgmh') . ' ' . $species . '</li>';
            }
            if ($category) {
                $body .= '<li>' . __('Kategorie:', 'abschussplan-hgmh') . ' ' . $category . '</li>';
            }
            $body .= '</ul>';
        }

        $body .= '<p>' . __('Bitte bestätigen Sie Ihre Email-Adresse durch Klicken auf den folgenden Link:', 'abschussplan-hgmh') . '</p>';
        $body .= '<p style="text-align: center; margin: 30px 0;">';
        $body .= '<a href="' . esc_url($verification_url) . '" style="background-color: #2c5f2d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">';
        $body .= __('Email-Adresse bestätigen', 'abschussplan-hgmh');
        $body .= '</a>';
        $body .= '</p>';
        $body .= '<p style="font-size: 12px; color: #666;">' . __('Dieser Link ist 48 Stunden gültig.', 'abschussplan-hgmh') . '</p>';
        $body .= '<p style="font-size: 12px; color: #666;">' . __('Falls Sie diese Meldung nicht erstellt haben, können Sie diese Email ignorieren.', 'abschussplan-hgmh') . '</p>';
        $body .= '</div>';
        $body .= '</body></html>';

        return $body;
    }

    /**
     * Create submission with email-verification-pending status.
     *
     * Accepts legacy field names (game_species, field1-6) and maps them
     * to the normalized hgmh_submissions_v2 schema.
     *
     * @param array $data Submission data including 'email' for the submitter
     * @return int|false Submission ID on success, false on failure
     */
    public static function create_pending_submission($data) {
        global $wpdb;

        $table_name             = $wpdb->prefix . 'hgmh_submissions_v2';
        $wildarten_table        = $wpdb->prefix . 'hgmh_wildarten';
        $eigenjagdbezirke_table = $wpdb->prefix . 'hgmh_eigenjagdbezirke';

        // Resolve wildart_id
        $wildart_name = isset($data['game_species']) ? sanitize_text_field($data['game_species']) : '';
        $wildart_id   = 0;
        if ($wildart_name) {
            $wildart_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $wildarten_table WHERE name = %s AND is_active = 1",
                $wildart_name
            ));
        }

        // Resolve eigenjagdbezirk_id (field5 = jagdbezirk name)
        $ejb_name = isset($data['field5']) ? sanitize_text_field($data['field5']) : '';
        $ejb_id   = 0;
        if ($ejb_name) {
            $ejb_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $eigenjagdbezirke_table WHERE name = %s AND is_active = 1",
                $ejb_name
            ));
        }

        $token = self::generate_token();

        $insert_data = array(
            'wildart_id'           => $wildart_id,
            'eigenjagdbezirk_id'   => $ejb_id,
            'category'             => isset($data['field2']) ? sanitize_text_field($data['field2']) : '',
            'harvest_date'         => isset($data['field1']) ? sanitize_text_field($data['field1']) : '',
            'wus_number'           => isset($data['field3']) ? sanitize_text_field($data['field3']) : '',
            'internal_note'        => isset($data['field6']) ? sanitize_textarea_field($data['field6']) : '',
            'submitted_by_user_id' => isset($data['user_id']) ? absint($data['user_id']) : 0,
            'submitted_by_email'   => isset($data['email']) ? sanitize_email($data['email']) : '',
            'status'               => 'pending_email',
            'verification_token'   => $token,
        );

        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AHGMH: Failed to create pending submission - ' . $wpdb->last_error);
            }
            return false;
        }

        $submission_id = $wpdb->insert_id;

        // Build email context with new field names
        $email_context = array(
            'wildart_name' => $wildart_name,
            'category'     => $insert_data['category'],
        );

        $email_sent = self::send_verification_email(
            $insert_data['submitted_by_email'],
            $token,
            $email_context
        );

        if (!$email_sent && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AHGMH: Submission created but verification email failed for submission ID ' . $submission_id);
        }

        return $submission_id;
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    public static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Only use the first IP in the chain to prevent spoofing
            $forwarded = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ips = explode(',', $forwarded);
            $ip = trim($ips[0]);
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
        }

        // Validate IP address
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '0.0.0.0';
    }

    /**
     * Clean up expired tokens periodically
     * This should be called via WordPress cron or periodically
     *
     * @return int Number of expired tokens cleaned up
     */
    public static function cleanup_expired_tokens() {
        global $wpdb;

        $table_name   = $wpdb->prefix . 'hgmh_submissions_v2';
        $expiry_hours = self::TOKEN_EXPIRY_HOURS;

        // Expire pending_email submissions whose token window has passed.
        // submitted_at + TOKEN_EXPIRY_HOURS hours < NOW()
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name
             SET status = 'expired', verification_token = NULL
             WHERE status = 'pending_email'
             AND submitted_at < DATE_SUB(%s, INTERVAL %d HOUR)",
            current_time('mysql'),
            $expiry_hours
        ));

        return $result !== false ? $result : 0;
    }
}
