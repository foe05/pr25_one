<?php
/**
 * Moderation Service Class
 * Business logic for approve/reject/edit workflow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Moderation Service for submission workflow
 */
class HGMH_Moderation_Service {

    private $repository;
    private $email_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->repository = new HGMH_Submission_Repository();
        $this->email_service = new AHGMH_Email_Service();
    }

    /**
     * Approve a submission
     *
     * @param int $submission_id The submission ID
     * @param int $obmann_user_id The approving user ID
     * @param string $comment Optional comment
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function approve($submission_id, $obmann_user_id, $comment = '') {
        try {
            // 1. Validate submission exists
            $submission = $this->repository->find($submission_id);
            if (!$submission) {
                return new WP_Error(
                    'submission_not_found',
                    __('Meldung nicht gefunden.', 'abschussplan-hgmh')
                );
            }

            // 2. Get moderator info
            $moderator = get_userdata($obmann_user_id);
            if (!$moderator) {
                return new WP_Error(
                    'invalid_moderator',
                    __('Ungültiger Moderator.', 'abschussplan-hgmh')
                );
            }

            // 3. Calculate time to approval
            $time_to_approval = $this->calculate_time_to_approval($submission);

            // 4. Update status
            $previous_status = $submission->status;
            $update_fields = [
                'approved_by_user_id' => absint($obmann_user_id),
                'approved_at'         => current_time('mysql'),
                'time_to_approval'    => $time_to_approval,
            ];

            $result = $this->repository->update_status(
                $submission_id,
                'approved',
                $update_fields
            );

            if (!$result) {
                return new WP_Error(
                    'update_failed',
                    __('Status-Aktualisierung fehlgeschlagen.', 'abschussplan-hgmh')
                );
            }

            // 5. Log to moderation history
            $this->log_to_history(
                $submission_id,
                'approve',
                $previous_status,
                'approved',
                $obmann_user_id,
                $moderator->display_name,
                $comment
            );

            // 6. Trigger activity log
            $this->trigger_activity_log('approve', $submission_id, $obmann_user_id, [
                'previous_status' => $previous_status,
                'new_status' => 'approved',
                'time_to_approval' => $time_to_approval,
                'comment' => $comment
            ]);

            // 7. Send email notification
            if (!empty($submission->email)) {
                $submission_data = $this->get_submission_data_for_email($submission);
                $this->email_service->send_approval_notification(
                    $submission->email,
                    $submission_data
                );
            }

            return true;

        } catch (Exception $e) {
            error_log('HGMH Moderation Service - Approve error: ' . $e->getMessage());
            return new WP_Error(
                'approval_error',
                __('Fehler beim Genehmigen der Meldung.', 'abschussplan-hgmh')
            );
        }
    }

    /**
     * Reject a submission
     *
     * @param int $submission_id The submission ID
     * @param int $obmann_user_id The rejecting user ID
     * @param string $reason Rejection reason (REQUIRED!)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function reject($submission_id, $obmann_user_id, $reason) {
        // Validate reason is provided (spec requirement: "Reason ist Pflicht!")
        if (empty(trim($reason))) {
            return new WP_Error(
                'reason_required',
                __('Ablehnungsgrund ist erforderlich.', 'abschussplan-hgmh')
            );
        }

        try {
            // 1. Validate submission exists
            $submission = $this->repository->find($submission_id);
            if (!$submission) {
                return new WP_Error(
                    'submission_not_found',
                    __('Meldung nicht gefunden.', 'abschussplan-hgmh')
                );
            }

            // 2. Get moderator info
            $moderator = get_userdata($obmann_user_id);
            if (!$moderator) {
                return new WP_Error(
                    'invalid_moderator',
                    __('Ungültiger Moderator.', 'abschussplan-hgmh')
                );
            }

            // 3. Update status — rejection stored via approved_by_user_id/approved_at/approval_comment
            $previous_status = $submission->status;
            $update_fields = [
                'approved_by_user_id' => absint($obmann_user_id),
                'approved_at'         => current_time('mysql'),
                'approval_comment'    => sanitize_textarea_field($reason),
            ];

            $result = $this->repository->update_status(
                $submission_id,
                'rejected',
                $update_fields
            );

            if (!$result) {
                return new WP_Error(
                    'update_failed',
                    __('Status-Aktualisierung fehlgeschlagen.', 'abschussplan-hgmh')
                );
            }

            // 4. Log to moderation history
            $this->log_to_history(
                $submission_id,
                'reject',
                $previous_status,
                'rejected',
                $obmann_user_id,
                $moderator->display_name,
                $reason
            );

            // 5. Trigger activity log
            $this->trigger_activity_log('reject', $submission_id, $obmann_user_id, [
                'previous_status' => $previous_status,
                'new_status' => 'rejected',
                'reason' => $reason
            ]);

            // 6. Send email notification
            if (!empty($submission->email)) {
                $submission_data = $this->get_submission_data_for_email($submission);
                $this->email_service->send_rejection_notification(
                    $submission->email,
                    $submission_data,
                    $reason
                );
            }

            return true;

        } catch (Exception $e) {
            error_log('HGMH Moderation Service - Reject error: ' . $e->getMessage());
            return new WP_Error(
                'rejection_error',
                __('Fehler beim Ablehnen der Meldung.', 'abschussplan-hgmh')
            );
        }
    }

    /**
     * Edit a submission
     *
     * @param int $submission_id The submission ID
     * @param int $obmann_user_id The editing user ID
     * @param array $updated_data Updated submission data
     * @param string $comment Optional comment (reason for edit)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function edit($submission_id, $obmann_user_id, $updated_data, $comment = '') {
        try {
            // 1. Validate submission exists
            $submission = $this->repository->find($submission_id);
            if (!$submission) {
                return new WP_Error(
                    'submission_not_found',
                    __('Meldung nicht gefunden.', 'abschussplan-hgmh')
                );
            }

            // 2. Get moderator info
            $moderator = get_userdata($obmann_user_id);
            if (!$moderator) {
                return new WP_Error(
                    'invalid_moderator',
                    __('Ungültiger Moderator.', 'abschussplan-hgmh')
                );
            }

            // 3. Validate and sanitize updated data
            $sanitized_data = $this->sanitize_submission_data($updated_data);
            if (empty($sanitized_data)) {
                return new WP_Error(
                    'invalid_data',
                    __('Ungültige Meldungsdaten.', 'abschussplan-hgmh')
                );
            }

            // 4. Store previous values for history (new schema field names)
            $previous_data = [
                'wildart_name'         => $submission->wildart_name ?? '',
                'category'             => $submission->category ?? '',
                'harvest_date'         => $submission->harvest_date ?? '',
                'eigenjagdbezirk_name' => $submission->eigenjagdbezirk_name ?? '',
                'meldegruppe_name'     => $submission->meldegruppe_name ?? '',
            ];

            // 5. Update submission data
            $result = $this->repository->update($submission_id, $sanitized_data);

            if (!$result) {
                return new WP_Error(
                    'update_failed',
                    __('Aktualisierung der Meldung fehlgeschlagen.', 'abschussplan-hgmh')
                );
            }

            // 6. Log to moderation history
            $this->log_to_history(
                $submission_id,
                'edit',
                $submission->status,
                $submission->status,
                $obmann_user_id,
                $moderator->display_name,
                $comment
            );

            // 7. Trigger activity log
            $this->trigger_activity_log('edit', $submission_id, $obmann_user_id, [
                'previous_data' => $previous_data,
                'updated_data' => $sanitized_data,
                'comment' => $comment
            ]);

            return true;

        } catch (Exception $e) {
            error_log('HGMH Moderation Service - Edit error: ' . $e->getMessage());
            return new WP_Error(
                'edit_error',
                __('Fehler beim Bearbeiten der Meldung.', 'abschussplan-hgmh')
            );
        }
    }

    /**
     * Sanitize submission data for editing
     *
     * @param array $data Raw submission data
     * @return array Sanitized data
     */
    private function sanitize_submission_data($data) {
        $sanitized = [];

        if (isset($data['wildart_id'])) {
            $sanitized['wildart_id'] = absint($data['wildart_id']);
        }

        if (isset($data['eigenjagdbezirk_id'])) {
            $sanitized['eigenjagdbezirk_id'] = absint($data['eigenjagdbezirk_id']);
        }

        if (isset($data['category'])) {
            $sanitized['category'] = sanitize_text_field($data['category']);
        }

        if (isset($data['harvest_date'])) {
            $sanitized['harvest_date'] = sanitize_text_field($data['harvest_date']);
        }

        if (isset($data['wus_number'])) {
            $sanitized['wus_number'] = sanitize_text_field($data['wus_number']);
        }

        if (isset($data['internal_note'])) {
            $sanitized['internal_note'] = sanitize_textarea_field($data['internal_note']);
        }

        return $sanitized;
    }

    /**
     * Calculate time to approval in minutes
     *
     * @param int $submission_id The submission ID
     * @return int Time to approval in minutes
     */
    private function calculate_time_to_approval($submission) {
        $submitted_at = $submission->submitted_at ?? null;

        if (!$submitted_at) {
            return 0;
        }

        try {
            $submitted_time = new DateTime($submitted_at);
            $current_time = new DateTime(current_time('mysql'));
            $interval = $current_time->diff($submitted_time);

            // Convert to minutes
            $minutes = ($interval->days * 24 * 60) +
                       ($interval->h * 60) +
                       $interval->i;

            return absint($minutes);

        } catch (Exception $e) {
            error_log('HGMH Moderation Service - Time calculation error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Log moderation action to history table
     *
     * @param int $submission_id The submission ID
     * @param string $action The action performed
     * @param string $previous_status Previous status
     * @param string $new_status New status
     * @param int $moderator_id Moderator user ID
     * @param string $moderator_name Moderator display name
     * @param string $comment Optional comment
     * @return bool Success status
     */
    private function log_to_history($submission_id, $action, $previous_status, $new_status, $moderator_id, $moderator_name, $comment = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hgmh_moderation_history';
        $moderator  = get_userdata($moderator_id);

        try {
            $result = $wpdb->insert(
                $table_name,
                [
                    'submission_id'       => absint($submission_id),
                    'action'              => sanitize_text_field($action),
                    'performed_by_user_id' => absint($moderator_id),
                    'performed_by_email'  => $moderator ? sanitize_email($moderator->user_email) : null,
                    'old_status'          => sanitize_text_field($previous_status),
                    'new_status'          => sanitize_text_field($new_status),
                    'comment'             => sanitize_textarea_field($comment),
                    'performed_at'        => current_time('mysql'),
                ],
                ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
            );

            if ($result === false) {
                error_log('HGMH Moderation Service - Failed to log history for submission ID ' . $submission_id);
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log('HGMH Moderation Service - History logging error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Trigger activity log via WordPress action hook
     *
     * @param string $action The action performed
     * @param int $submission_id The submission ID
     * @param int $user_id The user ID
     * @param array $data Additional data
     */
    private function trigger_activity_log($action, $submission_id, $user_id, $data = []) {
        do_action('ahgmh_moderation_activity', [
            'action' => sanitize_text_field($action),
            'submission_id' => absint($submission_id),
            'user_id' => absint($user_id),
            'timestamp' => current_time('mysql'),
            'data' => $data
        ]);
    }

    /**
     * Get submission data formatted for email
     *
     * @param object $submission The submission object
     * @return array Formatted submission data
     */
    private function get_submission_data_for_email($submission) {
        return [
            'wildart'              => esc_html($submission->wildart_name ?? ''),
            'category'             => esc_html($submission->category ?? ''),
            'harvest_date'         => esc_html($submission->harvest_date ?? ''),
            'eigenjagdbezirk'      => esc_html($submission->eigenjagdbezirk_name ?? ''),
            'meldegruppe'          => esc_html($submission->meldegruppe_name ?? ''),
            'submission_id'        => absint($submission->id ?? 0),
        ];
    }
}
