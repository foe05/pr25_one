<?php
/**
 * Moderation Service Class
 * Business logic for submission moderation (approve, reject, update)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Moderation Service for handling submission approval, rejection, and updates
 */
class AHGMH_Moderation_Service {

    /**
     * Approve a submission
     * - Set status to 'approved'
     * - Send email notification to submitter
     * - Log moderation action
     *
     * @param int $submission_id The submission ID to approve
     * @return bool Success/failure
     */
    public function approve_submission($submission_id) {
        global $wpdb;

        $submission_id = absint($submission_id);

        if ($submission_id <= 0) {
            error_log('AHGMH Moderation: Invalid submission ID for approval');
            return false;
        }

        try {
            $table_name = $wpdb->prefix . 'ahgmh_submissions';

            // Get submission details for email notification
            $submission = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $submission_id
            ), ARRAY_A);

            if (!$submission) {
                error_log('AHGMH Moderation: Submission not found for ID ' . $submission_id);
                return false;
            }

            // Update status to 'approved'
            $result = $wpdb->update(
                $table_name,
                array('status' => 'approved'),
                array('id' => $submission_id),
                array('%s'),
                array('%d')
            );

            if ($result === false) {
                error_log('AHGMH Moderation: Failed to update submission status to approved for ID ' . $submission_id);
                return false;
            }

            // Send email notification to submitter
            $this->send_approval_notification($submission);

            // Log moderation action
            error_log('AHGMH Moderation: Submission #' . $submission_id . ' approved');

            return true;

        } catch (Exception $e) {
            error_log('AHGMH Moderation Error (approve): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject a submission with comment
     * - Set status to 'rejected'
     * - Store rejection comment
     * - Send email notification with reason
     * - Log moderation action
     *
     * @param int $submission_id The submission ID to reject
     * @param string $comment Rejection reason (required)
     * @return bool Success/failure
     */
    public function reject_submission($submission_id, $comment) {
        global $wpdb;

        $submission_id = absint($submission_id);
        $comment = sanitize_textarea_field($comment);

        if ($submission_id <= 0) {
            error_log('AHGMH Moderation: Invalid submission ID for rejection');
            return false;
        }

        if (empty($comment)) {
            error_log('AHGMH Moderation: Rejection comment is required');
            return false;
        }

        try {
            $table_name = $wpdb->prefix . 'ahgmh_submissions';

            // Get submission details for email notification
            $submission = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $submission_id
            ), ARRAY_A);

            if (!$submission) {
                error_log('AHGMH Moderation: Submission not found for ID ' . $submission_id);
                return false;
            }

            // Update status to 'rejected' and store comment in field6
            $result = $wpdb->update(
                $table_name,
                array(
                    'status' => 'rejected',
                    'field6' => $comment // Store rejection comment
                ),
                array('id' => $submission_id),
                array('%s', '%s'),
                array('%d')
            );

            if ($result === false) {
                error_log('AHGMH Moderation: Failed to update submission status to rejected for ID ' . $submission_id);
                return false;
            }

            // Send email notification to submitter with rejection reason
            $this->send_rejection_notification($submission, $comment);

            // Log moderation action
            error_log('AHGMH Moderation: Submission #' . $submission_id . ' rejected. Reason: ' . substr($comment, 0, 50));

            return true;

        } catch (Exception $e) {
            error_log('AHGMH Moderation Error (reject): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update submission data
     * - Validate updated data
     * - Update in database
     * - Log changes
     *
     * @param int $submission_id The submission ID
     * @param array $data Updated field values
     * @return bool Success/failure
     */
    public function update_submission($submission_id, $data) {
        global $wpdb;

        $submission_id = absint($submission_id);

        if ($submission_id <= 0) {
            error_log('AHGMH Moderation: Invalid submission ID for update');
            return false;
        }

        if (empty($data) || !is_array($data)) {
            error_log('AHGMH Moderation: No data provided for update');
            return false;
        }

        try {
            $table_name = $wpdb->prefix . 'ahgmh_submissions';

            // Sanitize data
            $sanitized_data = array();
            $allowed_fields = array('game_species', 'field1', 'field2', 'field3', 'field4', 'field5', 'field6');

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    if ($key === 'field6') {
                        $sanitized_data[$key] = sanitize_textarea_field($value);
                    } else {
                        $sanitized_data[$key] = sanitize_text_field($value);
                    }
                }
            }

            if (empty($sanitized_data)) {
                error_log('AHGMH Moderation: No valid fields to update');
                return false;
            }

            // Build format array
            $formats = array_fill(0, count($sanitized_data), '%s');

            // Update submission
            $result = $wpdb->update(
                $table_name,
                $sanitized_data,
                array('id' => $submission_id),
                $formats,
                array('%d')
            );

            if ($result === false) {
                error_log('AHGMH Moderation: Failed to update submission ID ' . $submission_id);
                return false;
            }

            // Log changes
            error_log('AHGMH Moderation: Submission #' . $submission_id . ' updated. Fields: ' . implode(', ', array_keys($sanitized_data)));

            return true;

        } catch (Exception $e) {
            error_log('AHGMH Moderation Error (update): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send approval notification email to submitter
     *
     * @param array $submission The submission data
     * @return bool Email sent successfully
     */
    private function send_approval_notification($submission) {
        // Get submitter email
        $user_id = isset($submission['user_id']) ? absint($submission['user_id']) : 0;

        if ($user_id > 0) {
            $user = get_user_by('id', $user_id);

            if ($user) {
                $to = $user->user_email;
                $subject = __('Meldung genehmigt', 'abschussplan-hgmh');

                $message = sprintf(
                    __('Guten Tag,

Ihre Abschussmeldung (ID: %d) wurde genehmigt.

Wildart: %s
Datum: %s

Mit freundlichen Grüßen
Hegegemeinschaft HGMH', 'abschussplan-hgmh'),
                    $submission['id'],
                    isset($submission['game_species']) ? $submission['game_species'] : 'N/A',
                    isset($submission['created_at']) ? $submission['created_at'] : 'N/A'
                );

                $headers = array('Content-Type: text/plain; charset=UTF-8');

                return wp_mail($to, $subject, $message, $headers);
            }
        }

        return false;
    }

    /**
     * Send rejection notification email to submitter
     *
     * @param array $submission The submission data
     * @param string $comment Rejection reason
     * @return bool Email sent successfully
     */
    private function send_rejection_notification($submission, $comment) {
        // Get submitter email
        $user_id = isset($submission['user_id']) ? absint($submission['user_id']) : 0;

        if ($user_id > 0) {
            $user = get_user_by('id', $user_id);

            if ($user) {
                $to = $user->user_email;
                $subject = __('Meldung abgelehnt', 'abschussplan-hgmh');

                $message = sprintf(
                    __('Guten Tag,

Ihre Abschussmeldung (ID: %d) wurde abgelehnt.

Wildart: %s
Datum: %s

Grund der Ablehnung:
%s

Bitte korrigieren Sie die Meldung und reichen Sie sie erneut ein.

Mit freundlichen Grüßen
Hegegemeinschaft HGMH', 'abschussplan-hgmh'),
                    $submission['id'],
                    isset($submission['game_species']) ? $submission['game_species'] : 'N/A',
                    isset($submission['created_at']) ? $submission['created_at'] : 'N/A',
                    $comment
                );

                $headers = array('Content-Type: text/plain; charset=UTF-8');

                return wp_mail($to, $subject, $message, $headers);
            }
        }

        return false;
    }
}
