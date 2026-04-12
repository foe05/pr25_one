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
 * Moderation Service for handling submission approval, rejection, and updates.
 * Uses HGMH_Submission_Repository / hgmh_submissions_v2.
 */
class AHGMH_Moderation_Service {

    /** @var HGMH_Submission_Repository */
    private $repo;

    public function __construct() {
        $this->repo = new HGMH_Submission_Repository();
    }

    /**
     * Approve a submission.
     *
     * @param int $submission_id
     * @return bool
     */
    public function approve_submission($submission_id) {
        $submission_id = absint($submission_id);
        if ($submission_id <= 0) {
            return false;
        }

        $submission = $this->repo->find($submission_id);
        if (!$submission) {
            error_log('AHGMH Moderation: Submission not found for ID ' . $submission_id);
            return false;
        }

        $result = $this->repo->update_status($submission_id, 'approved', [
            'approved_by_user_id' => get_current_user_id(),
            'approved_at'         => current_time('mysql'),
        ]);

        if ($result) {
            $this->send_approval_notification($submission);
            $this->log_moderation_action($submission_id, 'approve', $submission->status, 'approved');
        }

        return $result;
    }

    /**
     * Reject a submission with a mandatory comment.
     *
     * @param int    $submission_id
     * @param string $comment Rejection reason (required)
     * @return bool
     */
    public function reject_submission($submission_id, $comment) {
        $submission_id = absint($submission_id);
        $comment       = sanitize_textarea_field($comment);

        if ($submission_id <= 0 || empty($comment)) {
            return false;
        }

        $submission = $this->repo->find($submission_id);
        if (!$submission) {
            error_log('AHGMH Moderation: Submission not found for ID ' . $submission_id);
            return false;
        }

        $result = $this->repo->update_status($submission_id, 'rejected', [
            'approved_by_user_id' => get_current_user_id(),
            'approved_at'         => current_time('mysql'),
            'approval_comment'    => $comment,
        ]);

        if ($result) {
            $this->send_rejection_notification($submission, $comment);
            $this->log_moderation_action($submission_id, 'reject', $submission->status, 'rejected', $comment);
        }

        return $result;
    }

    /**
     * Update editable fields of a submission.
     *
     * @param int   $submission_id
     * @param array $data Allowed keys: wildart_id, eigenjagdbezirk_id, category,
     *                    harvest_date, wus_number, internal_note
     * @return bool
     */
    public function update_submission($submission_id, $data) {
        $submission_id = absint($submission_id);
        if ($submission_id <= 0 || empty($data) || !is_array($data)) {
            return false;
        }

        $allowed = ['wildart_id', 'eigenjagdbezirk_id', 'category', 'harvest_date', 'wus_number', 'notes', 'internal_note'];
        $clean   = [];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            if (in_array($key, ['wildart_id', 'eigenjagdbezirk_id'], true)) {
                $clean[$key] = absint($value);
            } elseif (in_array($key, ['notes', 'internal_note'], true)) {
                $clean[$key] = sanitize_textarea_field($value);
            } else {
                $clean[$key] = sanitize_text_field($value);
            }
        }

        if (empty($clean)) {
            return false;
        }

        $result = $this->repo->update($submission_id, $clean);

        if ($result) {
            $this->log_moderation_action($submission_id, 'edit', null, null, implode(', ', array_keys($clean)));
        }

        return $result;
    }

    /**
     * Log a moderation action to hgmh_moderation_history.
     */
    private function log_moderation_action($submission_id, $action, $old_status, $new_status, $comment = '') {
        global $wpdb;
        $user = wp_get_current_user();

        $wpdb->insert(
            $wpdb->prefix . 'hgmh_moderation_history',
            [
                'submission_id'        => absint($submission_id),
                'action'               => sanitize_text_field($action),
                'performed_by_user_id' => $user->ID,
                'performed_by_email'   => $user->user_email,
                'old_status'           => $old_status ? sanitize_text_field($old_status) : null,
                'new_status'           => $new_status ? sanitize_text_field($new_status) : null,
                'comment'              => sanitize_textarea_field($comment),
                'performed_at'         => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Send approval notification email to submitter.
     *
     * @param object $submission Enriched submission from HGMH_Submission_Repository::find()
     */
    private function send_approval_notification($submission) {
        $to = $this->resolve_recipient_email($submission);
        if (!$to) {
            return false;
        }

        $subject = __('Meldung genehmigt', 'abschussplan-hgmh');
        $message = sprintf(
            __("Guten Tag,\n\nIhre Abschussmeldung (ID: %d) wurde genehmigt.\n\nWildart: %s\nKategorie: %s\nAbschussdatum: %s\n\nMit freundlichen Grüßen\nHegegemeinschaft HGMH", 'abschussplan-hgmh'),
            absint($submission->id),
            esc_html($submission->wildart_name ?? ''),
            esc_html($submission->category ?? ''),
            esc_html($submission->harvest_date ?? '')
        );

        return wp_mail($to, $subject, $message, ['Content-Type: text/plain; charset=UTF-8']);
    }

    /**
     * Send rejection notification email to submitter.
     *
     * @param object $submission Enriched submission from HGMH_Submission_Repository::find()
     * @param string $comment    Rejection reason
     */
    private function send_rejection_notification($submission, $comment) {
        $to = $this->resolve_recipient_email($submission);
        if (!$to) {
            return false;
        }

        $subject = __('Meldung abgelehnt', 'abschussplan-hgmh');
        $message = sprintf(
            __("Guten Tag,\n\nIhre Abschussmeldung (ID: %d) wurde abgelehnt.\n\nWildart: %s\nKategorie: %s\nAbschussdatum: %s\n\nGrund der Ablehnung:\n%s\n\nBitte korrigieren Sie die Meldung und reichen Sie sie erneut ein.\n\nMit freundlichen Grüßen\nHegegemeinschaft HGMH", 'abschussplan-hgmh'),
            absint($submission->id),
            esc_html($submission->wildart_name ?? ''),
            esc_html($submission->category ?? ''),
            esc_html($submission->harvest_date ?? ''),
            esc_html($comment)
        );

        return wp_mail($to, $subject, $message, ['Content-Type: text/plain; charset=UTF-8']);
    }

    /**
     * Resolve the recipient email address from a submission object.
     * Falls back from submitted_by_email → WP user email.
     */
    private function resolve_recipient_email($submission) {
        if (!empty($submission->submitted_by_email)) {
            return sanitize_email($submission->submitted_by_email);
        }

        if (!empty($submission->submitted_by_user_id)) {
            $user = get_userdata(absint($submission->submitted_by_user_id));
            if ($user) {
                return $user->user_email;
            }
        }

        return null;
    }
}
