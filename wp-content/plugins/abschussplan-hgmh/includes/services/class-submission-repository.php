<?php
/**
 * Submission Repository Class
 * Data access layer for submission operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Submission Repository for database operations
 */
class AHGMH_Submission_Repository {

    /**
     * Get submission by ID
     *
     * @param int $submission_id The submission ID
     * @return object|null Submission object or null if not found
     */
    public function get_by_id($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            $submission = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                absint($submission_id)
            ));

            if (!$submission) {
                return null;
            }

            return $submission;

        } catch (Exception $e) {
            error_log('Submission Repository Error (get_by_id): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update submission status
     *
     * @param int $submission_id The submission ID
     * @param string $status The new status
     * @param array $fields Additional fields to update (e.g., approved_by, approved_at)
     * @return bool True on success, false on failure
     */
    public function update_status($submission_id, $status, $fields = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            // Prepare base update data
            $update_data = [
                'status' => sanitize_text_field($status)
            ];

            // Merge additional fields
            $update_data = array_merge($update_data, $this->sanitize_fields($fields));

            // Prepare format array
            $format = array_fill(0, count($update_data), '%s');

            $result = $wpdb->update(
                $table_name,
                $update_data,
                ['id' => absint($submission_id)],
                $format,
                ['%d']
            );

            if ($result === false) {
                error_log('Submission Repository Error: Failed to update status for submission ID ' . $submission_id);
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log('Submission Repository Error (update_status): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update submission fields
     *
     * @param int $submission_id The submission ID
     * @param array $fields Associative array of fields to update
     * @return bool True on success, false on failure
     */
    public function update_fields($submission_id, $fields) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            if (empty($fields)) {
                return false;
            }

            // Sanitize fields
            $sanitized_fields = $this->sanitize_fields($fields);

            // Prepare format array
            $format = array_fill(0, count($sanitized_fields), '%s');

            $result = $wpdb->update(
                $table_name,
                $sanitized_fields,
                ['id' => absint($submission_id)],
                $format,
                ['%d']
            );

            if ($result === false) {
                error_log('Submission Repository Error: Failed to update fields for submission ID ' . $submission_id);
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log('Submission Repository Error (update_fields): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get submission created_at timestamp
     *
     * @param int $submission_id The submission ID
     * @return string|null The created_at timestamp or null if not found
     */
    public function get_submitted_at($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            $created_at = $wpdb->get_var($wpdb->prepare(
                "SELECT created_at FROM $table_name WHERE id = %d",
                absint($submission_id)
            ));

            return $created_at;

        } catch (Exception $e) {
            error_log('Submission Repository Error (get_submitted_at): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize field values for database operations
     *
     * @param array $fields Associative array of fields
     * @return array Sanitized fields
     */
    private function sanitize_fields($fields) {
        $sanitized = [];

        foreach ($fields as $key => $value) {
            // Handle different field types
            if (is_null($value)) {
                $sanitized[$key] = null;
            } elseif ($key === 'approved_by' || $key === 'rejected_by' || $key === 'time_to_approval') {
                $sanitized[$key] = absint($value);
            } elseif ($key === 'approved_at' || $key === 'rejected_at') {
                // DateTime fields - validate format
                $sanitized[$key] = sanitize_text_field($value);
            } elseif ($key === 'rejection_reason') {
                $sanitized[$key] = sanitize_textarea_field($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }
}
