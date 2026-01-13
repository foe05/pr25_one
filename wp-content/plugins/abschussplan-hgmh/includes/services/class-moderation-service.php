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
        $this->repository = new AHGMH_Submission_Repository();
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
            $submission = $this->repository->get_by_id($submission_id);
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
            $time_to_approval = $this->calculate_time_to_approval($submission_id);

            // 4. Update status
            $previous_status = $submission->status;
            $update_fields = [
                'approved_by' => absint($obmann_user_id),
                'approved_at' => current_time('mysql'),
                'time_to_approval' => $time_to_approval
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
     * @param string $comment Optional comment (reason for rejection)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function reject($submission_id, $obmann_user_id, $comment = '') {
        try {
            // 1. Validate submission exists
            $submission = $this->repository->get_by_id($submission_id);
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

            // 3. Update status
            $previous_status = $submission->status;
            $update_fields = [
                'rejected_by' => absint($obmann_user_id),
                'rejected_at' => current_time('mysql')
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
                $comment
            );

            // 5. Trigger activity log
            $this->trigger_activity_log('reject', $submission_id, $obmann_user_id, [
                'previous_status' => $previous_status,
                'new_status' => 'rejected',
                'comment' => $comment
            ]);

            // 6. Send email notification
            if (!empty($submission->email)) {
                $submission_data = $this->get_submission_data_for_email($submission);
                $this->email_service->send_rejection_notification(
                    $submission->email,
                    $submission_data,
                    $comment
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
            $submission = $this->repository->get_by_id($submission_id);
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

            // 4. Store previous values for history
            $previous_data = [
                'art' => $submission->art ?? '',
                'kategorie' => $submission->kategorie ?? '',
                'anzahl' => $submission->anzahl ?? 0,
                'datum' => $submission->datum ?? '',
                'meldegruppe' => $submission->meldegruppe ?? ''
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

        if (isset($data['art'])) {
            $sanitized['art'] = sanitize_text_field($data['art']);
        }

        if (isset($data['kategorie'])) {
            $sanitized['kategorie'] = sanitize_text_field($data['kategorie']);
        }

        if (isset($data['anzahl'])) {
            $sanitized['anzahl'] = absint($data['anzahl']);
        }

        if (isset($data['datum'])) {
            $sanitized['datum'] = sanitize_text_field($data['datum']);
        }

        if (isset($data['meldegruppe'])) {
            $sanitized['meldegruppe'] = sanitize_text_field($data['meldegruppe']);
        }

        if (isset($data['bemerkung'])) {
            $sanitized['bemerkung'] = sanitize_textarea_field($data['bemerkung']);
        }

        return $sanitized;
    }

    /**
     * Calculate time to approval in minutes
     *
     * @param int $submission_id The submission ID
     * @return int Time to approval in minutes
     */
    private function calculate_time_to_approval($submission_id) {
        $submitted_at = $this->repository->get_submitted_at($submission_id);

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
        $table_name = $wpdb->prefix . 'ahgmh_moderation_history';

        try {
            $result = $wpdb->insert(
                $table_name,
                [
                    'submission_id' => absint($submission_id),
                    'action' => sanitize_text_field($action),
                    'previous_status' => sanitize_text_field($previous_status),
                    'new_status' => sanitize_text_field($new_status),
                    'moderator_id' => absint($moderator_id),
                    'moderator_name' => sanitize_text_field($moderator_name),
                    'comment' => sanitize_textarea_field($comment),
                    'created_at' => current_time('mysql')
                ],
                [
                    '%d', // submission_id
                    '%s', // action
                    '%s', // previous_status
                    '%s', // new_status
                    '%d', // moderator_id
                    '%s', // moderator_name
                    '%s', // comment
                    '%s'  // created_at
                ]
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
            'art' => esc_html($submission->art ?? ''),
            'kategorie' => esc_html($submission->kategorie ?? ''),
            'anzahl' => absint($submission->anzahl ?? 0),
            'datum' => esc_html($submission->datum ?? ''),
            'meldegruppe' => esc_html($submission->meldegruppe ?? ''),
            'submission_id' => absint($submission->id ?? 0)
        ];
    }
}
