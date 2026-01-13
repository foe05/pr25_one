<?php
/**
 * Email Service Class
 * Business logic for email notifications and communications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Service for notifications and communications
 */
class AHGMH_Email_Service {

    private $from_email;
    private $from_name;

    /**
     * Constructor
     */
    public function __construct() {
        $this->from_email = get_option('ahgmh_email_from', get_option('admin_email'));
        $this->from_name = get_option('ahgmh_email_from_name', get_bloginfo('name'));
    }

    /**
     * Send submission confirmation email
     *
     * @param string $to Recipient email address
     * @param array $submission_data Submission data
     * @return bool Success status
     */
    public function send_submission_confirmation($to, $submission_data) {
        if (!is_email($to)) {
            return false;
        }

        try {
            $subject = $this->get_confirmation_subject($submission_data);
            $message = $this->get_confirmation_message($submission_data);
            $headers = $this->get_email_headers();

            return wp_mail($to, $subject, $message, $headers);

        } catch (Exception $e) {
            error_log('AHGMH Email Service - Confirmation email error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send moderation notification email
     *
     * @param string $to Recipient email address
     * @param array $submission_data Submission data
     * @return bool Success status
     */
    public function send_moderation_notification($to, $submission_data) {
        if (!is_email($to)) {
            return false;
        }

        try {
            $subject = $this->get_moderation_subject($submission_data);
            $message = $this->get_moderation_message($submission_data);
            $headers = $this->get_email_headers();

            return wp_mail($to, $subject, $message, $headers);

        } catch (Exception $e) {
            error_log('AHGMH Email Service - Moderation notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send approval notification email
     *
     * @param string $to Recipient email address
     * @param array $submission_data Submission data
     * @return bool Success status
     */
    public function send_approval_notification($to, $submission_data) {
        if (!is_email($to)) {
            return false;
        }

        try {
            $subject = $this->get_approval_subject($submission_data);
            $message = $this->get_approval_message($submission_data);
            $headers = $this->get_email_headers();

            return wp_mail($to, $subject, $message, $headers);

        } catch (Exception $e) {
            error_log('AHGMH Email Service - Approval notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send rejection notification email
     *
     * @param string $to Recipient email address
     * @param array $submission_data Submission data
     * @param string $reason Rejection reason
     * @return bool Success status
     */
    public function send_rejection_notification($to, $submission_data, $reason = '') {
        if (!is_email($to)) {
            return false;
        }

        try {
            $subject = $this->get_rejection_subject($submission_data);
            $message = $this->get_rejection_message($submission_data, $reason);
            $headers = $this->get_email_headers();

            return wp_mail($to, $subject, $message, $headers);

        } catch (Exception $e) {
            error_log('AHGMH Email Service - Rejection notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get confirmation email subject
     *
     * @param array $submission_data Submission data
     * @return string Email subject
     */
    private function get_confirmation_subject($submission_data) {
        $subject = sprintf(
            __('Abschussmeldung erhalten - %s', 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? '')
        );

        return apply_filters('ahgmh_confirmation_email_subject', $subject, $submission_data);
    }

    /**
     * Get confirmation email message
     *
     * @param array $submission_data Submission data
     * @return string Email message
     */
    private function get_confirmation_message($submission_data) {
        $message = sprintf(
            __("Ihre Abschussmeldung wurde erfolgreich empfangen.\n\nDetails:\nWildart: %s\nKategorie: %s\nAnzahl: %d\nDatum: %s\n\nStatus: In Prüfung\n\nSie erhalten eine weitere Benachrichtigung nach der Prüfung.", 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? ''),
            esc_html($submission_data['kategorie'] ?? ''),
            absint($submission_data['anzahl'] ?? 0),
            esc_html($submission_data['datum'] ?? '')
        );

        return apply_filters('ahgmh_confirmation_email_message', $message, $submission_data);
    }

    /**
     * Get moderation notification subject
     *
     * @param array $submission_data Submission data
     * @return string Email subject
     */
    private function get_moderation_subject($submission_data) {
        $subject = sprintf(
            __('Neue Abschussmeldung zu prüfen - %s', 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? '')
        );

        return apply_filters('ahgmh_moderation_email_subject', $subject, $submission_data);
    }

    /**
     * Get moderation notification message
     *
     * @param array $submission_data Submission data
     * @return string Email message
     */
    private function get_moderation_message($submission_data) {
        $admin_url = admin_url('admin.php?page=abschussplan-hgmh-submissions');

        $message = sprintf(
            __("Eine neue Abschussmeldung wartet auf Prüfung.\n\nDetails:\nWildart: %s\nKategorie: %s\nMeldegruppe: %s\nAnzahl: %d\nDatum: %s\n\nZur Prüfung: %s", 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? ''),
            esc_html($submission_data['kategorie'] ?? ''),
            esc_html($submission_data['meldegruppe'] ?? ''),
            absint($submission_data['anzahl'] ?? 0),
            esc_html($submission_data['datum'] ?? ''),
            esc_url($admin_url)
        );

        return apply_filters('ahgmh_moderation_email_message', $message, $submission_data);
    }

    /**
     * Get approval notification subject
     *
     * @param array $submission_data Submission data
     * @return string Email subject
     */
    private function get_approval_subject($submission_data) {
        $subject = sprintf(
            __('Abschussmeldung genehmigt - %s', 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? '')
        );

        return apply_filters('ahgmh_approval_email_subject', $subject, $submission_data);
    }

    /**
     * Get approval notification message
     *
     * @param array $submission_data Submission data
     * @return string Email message
     */
    private function get_approval_message($submission_data) {
        $message = sprintf(
            __("Ihre Abschussmeldung wurde genehmigt.\n\nDetails:\nWildart: %s\nKategorie: %s\nAnzahl: %d\nDatum: %s\n\nStatus: Genehmigt", 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? ''),
            esc_html($submission_data['kategorie'] ?? ''),
            absint($submission_data['anzahl'] ?? 0),
            esc_html($submission_data['datum'] ?? '')
        );

        return apply_filters('ahgmh_approval_email_message', $message, $submission_data);
    }

    /**
     * Get rejection notification subject
     *
     * @param array $submission_data Submission data
     * @return string Email subject
     */
    private function get_rejection_subject($submission_data) {
        $subject = sprintf(
            __('Abschussmeldung abgelehnt - %s', 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? '')
        );

        return apply_filters('ahgmh_rejection_email_subject', $subject, $submission_data);
    }

    /**
     * Get rejection notification message
     *
     * @param array $submission_data Submission data
     * @param string $reason Rejection reason
     * @return string Email message
     */
    private function get_rejection_message($submission_data, $reason = '') {
        $reason_text = !empty($reason) ? "\n\nGrund: " . esc_html($reason) : '';

        $message = sprintf(
            __("Ihre Abschussmeldung wurde leider abgelehnt.\n\nDetails:\nWildart: %s\nKategorie: %s\nAnzahl: %d\nDatum: %s%s\n\nStatus: Abgelehnt\n\nBitte korrigieren Sie die Angaben und reichen Sie die Meldung erneut ein.", 'abschussplan-hgmh'),
            esc_html($submission_data['art'] ?? ''),
            esc_html($submission_data['kategorie'] ?? ''),
            absint($submission_data['anzahl'] ?? 0),
            esc_html($submission_data['datum'] ?? ''),
            $reason_text
        );

        return apply_filters('ahgmh_rejection_email_message', $message, $submission_data, $reason);
    }

    /**
     * Get email headers
     *
     * @return array Email headers
     */
    private function get_email_headers() {
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', $this->from_name, $this->from_email)
        ];

        return apply_filters('ahgmh_email_headers', $headers);
    }

    /**
     * Test email configuration
     *
     * @param string $to Test recipient email
     * @return bool Success status
     */
    public function test_email_configuration($to) {
        if (!is_email($to)) {
            return false;
        }

        $subject = __('Test Email - Abschussplan HGMH', 'abschussplan-hgmh');
        $message = __('Dies ist eine Test-E-Mail vom Abschussplan HGMH Plugin. Wenn Sie diese E-Mail erhalten haben, ist die E-Mail-Konfiguration korrekt.', 'abschussplan-hgmh');
        $headers = $this->get_email_headers();

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get email statistics
     *
     * @return array Email statistics
     */
    public function get_email_stats() {
        // Stub method - will be implemented with email logging
        return [
            'total_sent' => 0,
            'confirmations_sent' => 0,
            'notifications_sent' => 0,
            'approvals_sent' => 0,
            'rejections_sent' => 0,
            'last_sent' => null
        ];
    }
}
