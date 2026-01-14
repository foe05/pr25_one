<?php
/**
 * Email Service Class
 * Handles sending reports via WordPress mail with attachments and HTML templates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Service for sending reports and notifications
 */
class AHGMH_Email_Service {

    /**
     * Email queue option key
     */
    const QUEUE_OPTION_KEY = 'ahgmh_email_queue';

    /**
     * Maximum emails to send per batch
     */
    const BATCH_SIZE = 10;

    /**
     * PDF Service instance
     * @var AHGMH_PDF_Service
     */
    private $pdf_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->pdf_service = new AHGMH_PDF_Service();
    }

    /**
     * Send report email with optional PDF attachment
     *
     * @param string $recipient Email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Optional settings (pdf_html, pdf_filename, from_name, from_email)
     * @return bool Success status
     */
    public function send_report_email($recipient, $subject, $body, $options = array()) {
        try {
            // Validate recipient email
            if (!is_email($recipient)) {
                error_log('AHGMH Email Service: Invalid recipient email - ' . $recipient);
                return false;
            }

            // Prepare email headers
            $headers = $this->prepare_headers($options);

            // Prepare attachments if PDF HTML is provided
            $attachments = array();
            if (isset($options['pdf_html']) && !empty($options['pdf_html'])) {
                $attachment_path = $this->create_pdf_attachment($options['pdf_html'], $options);
                if ($attachment_path) {
                    $attachments[] = $attachment_path;
                }
            }

            // Send email using WordPress wp_mail()
            $result = wp_mail($recipient, $subject, $body, $headers, $attachments);

            // Clean up temporary PDF file if it was created
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        @unlink($attachment);
                    }
                }
            }

            // Log result
            if ($result) {
                error_log('AHGMH Email Service: Email sent successfully to ' . $recipient);
            } else {
                error_log('AHGMH Email Service: Failed to send email to ' . $recipient);
            }

            return $result;

        } catch (Exception $e) {
            error_log('AHGMH Email Service: Exception sending email - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send report to multiple recipients
     *
     * @param array $recipients Array of email addresses
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Optional settings
     * @return array Results array with success/failure counts
     */
    public function send_to_multiple($recipients, $subject, $body, $options = array()) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );

        // Validate recipients
        if (!is_array($recipients) || empty($recipients)) {
            $results['errors'][] = 'No recipients provided';
            return $results;
        }

        // Send to each recipient
        foreach ($recipients as $recipient) {
            $recipient = sanitize_email($recipient);

            if (!is_email($recipient)) {
                $results['failed']++;
                $results['errors'][] = 'Invalid email: ' . $recipient;
                continue;
            }

            $result = $this->send_report_email($recipient, $subject, $body, $options);

            if ($result) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = 'Failed to send to: ' . $recipient;
            }
        }

        return $results;
    }

    /**
     * Queue email for later sending (bulk operations)
     *
     * @param string|array $recipients Email address or array of addresses
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Optional settings
     * @return bool Success status
     */
    public function queue_email($recipients, $subject, $body, $options = array()) {
        // Ensure recipients is an array
        if (!is_array($recipients)) {
            $recipients = array($recipients);
        }

        // Get current queue
        $queue = get_option(self::QUEUE_OPTION_KEY, array());
        if (!is_array($queue)) {
            $queue = array();
        }

        // Add each recipient to queue
        foreach ($recipients as $recipient) {
            $recipient = sanitize_email($recipient);

            if (is_email($recipient)) {
                $queue[] = array(
                    'recipient' => $recipient,
                    'subject' => sanitize_text_field($subject),
                    'body' => wp_kses_post($body),
                    'options' => $options,
                    'queued_at' => current_time('mysql'),
                    'attempts' => 0
                );
            }
        }

        // Save updated queue
        return update_option(self::QUEUE_OPTION_KEY, $queue);
    }

    /**
     * Process email queue (send batch of queued emails)
     *
     * @param int $batch_size Number of emails to send in this batch
     * @return array Results with processed count and errors
     */
    public function process_queue($batch_size = null) {
        if ($batch_size === null) {
            $batch_size = self::BATCH_SIZE;
        }

        $results = array(
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );

        // Get current queue
        $queue = get_option(self::QUEUE_OPTION_KEY, array());
        if (!is_array($queue) || empty($queue)) {
            return $results;
        }

        // Process batch
        $remaining = array();
        $processed_count = 0;

        foreach ($queue as $index => $email) {
            // Stop if batch size reached
            if ($processed_count >= $batch_size) {
                $remaining[] = $email;
                continue;
            }

            // Skip if too many attempts
            if (isset($email['attempts']) && $email['attempts'] >= 3) {
                $results['failed']++;
                $results['errors'][] = 'Max attempts reached for: ' . $email['recipient'];
                continue;
            }

            // Attempt to send
            $result = $this->send_report_email(
                $email['recipient'],
                $email['subject'],
                $email['body'],
                isset($email['options']) ? $email['options'] : array()
            );

            $processed_count++;
            $results['processed']++;

            if ($result) {
                $results['success']++;
            } else {
                // Re-queue with incremented attempt count
                $email['attempts'] = isset($email['attempts']) ? $email['attempts'] + 1 : 1;
                $remaining[] = $email;
                $results['failed']++;
                $results['errors'][] = 'Failed to send to: ' . $email['recipient'];
            }
        }

        // Update queue with remaining items
        update_option(self::QUEUE_OPTION_KEY, $remaining);

        return $results;
    }

    /**
     * Get current queue status
     *
     * @return array Queue information
     */
    public function get_queue_status() {
        $queue = get_option(self::QUEUE_OPTION_KEY, array());

        return array(
            'count' => count($queue),
            'items' => $queue
        );
    }

    /**
     * Clear email queue
     *
     * @return bool Success status
     */
    public function clear_queue() {
        return delete_option(self::QUEUE_OPTION_KEY);
    }

    /**
     * Send email using HTML template
     *
     * @param string $recipient Email address
     * @param string $subject Email subject
     * @param string $template_name Template name (without .php extension)
     * @param array $template_vars Variables to pass to template
     * @param array $options Optional settings
     * @return bool Success status
     */
    public function send_with_template($recipient, $subject, $template_name, $template_vars = array(), $options = array()) {
        // Load template
        $body = $this->load_email_template($template_name, $template_vars);

        if ($body === false) {
            error_log('AHGMH Email Service: Failed to load template - ' . $template_name);
            return false;
        }

        // Send email
        return $this->send_report_email($recipient, $subject, $body, $options);
    }

    /**
     * Load email template and replace variables
     *
     * @param string $template_name Template name (without .php extension)
     * @param array $vars Variables to pass to template
     * @return string|false Template HTML or false on failure
     */
    private function load_email_template($template_name, $vars = array()) {
        $template_path = AHGMH_PLUGIN_DIR . 'templates/email/' . $template_name . '.php';

        if (!file_exists($template_path)) {
            error_log('AHGMH Email Service: Template not found - ' . $template_path);
            return false;
        }

        // Extract variables for template
        extract($vars);

        // Start output buffering
        ob_start();

        // Include template
        include $template_path;

        // Get contents and clean buffer
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Create PDF attachment from HTML
     *
     * @param string $html HTML content for PDF
     * @param array $options Options including pdf_filename
     * @return string|false Path to temporary PDF file or false on failure
     */
    private function create_pdf_attachment($html, $options = array()) {
        // Check if PDF service is available
        $status = $this->pdf_service->check_status();
        if (!$status['available']) {
            error_log('AHGMH Email Service: PDF service not available - ' . $status['message']);
            return false;
        }

        // Generate filename
        $filename = isset($options['pdf_filename']) ? $options['pdf_filename'] : 'report-' . time();
        $filename = sanitize_file_name($filename);

        // Create temporary file path
        $temp_dir = get_temp_dir();
        $temp_file = $temp_dir . $filename . '.pdf';

        // Generate PDF
        $result = $this->pdf_service->save_pdf($html, $temp_file);

        if (!$result || !file_exists($temp_file)) {
            error_log('AHGMH Email Service: Failed to create PDF attachment');
            return false;
        }

        return $temp_file;
    }

    /**
     * Prepare email headers
     *
     * @param array $options Options including from_name, from_email
     * @return array Email headers
     */
    private function prepare_headers($options = array()) {
        $headers = array();

        // Set content type to HTML
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        // Set From header
        $from_name = isset($options['from_name']) ? $options['from_name'] : get_option('blogname');
        $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');

        // Sanitize from values
        $from_name = sanitize_text_field($from_name);
        $from_email = sanitize_email($from_email);

        if (is_email($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        // Add Reply-To if specified
        if (isset($options['reply_to']) && is_email($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . sanitize_email($options['reply_to']);
        }

        return $headers;
    }

    /**
     * Test email configuration by sending test email
     *
     * @param string $recipient Test recipient email
     * @return bool Success status
     */
    public function send_test_email($recipient) {
        if (!is_email($recipient)) {
            return false;
        }

        $subject = __('[Abschussplan] Test Email', 'abschussplan-hgmh');

        $body = $this->load_email_template('test-email', array(
            'site_name' => get_option('blogname'),
            'timestamp' => current_time('mysql')
        ));

        // Fallback if template not found
        if ($body === false) {
            $body = '<html><body>';
            $body .= '<h2>' . __('Test Email', 'abschussplan-hgmh') . '</h2>';
            $body .= '<p>' . __('Dies ist eine Test-Email vom Abschussplan HGMH Plugin.', 'abschussplan-hgmh') . '</p>';
            $body .= '<p>' . sprintf(__('Gesendet am: %s', 'abschussplan-hgmh'), current_time('mysql')) . '</p>';
            $body .= '</body></html>';
        }

        return $this->send_report_email($recipient, $subject, $body);
    }

    /**
     * Get email sending statistics
     *
     * @return array Statistics
     */
    public function get_statistics() {
        // This could be enhanced to track statistics in options or custom table
        // For now, return basic queue information
        $queue_status = $this->get_queue_status();

        return array(
            'queue_count' => $queue_status['count'],
            'queue_items' => $queue_status['items']
        );
    }
}
