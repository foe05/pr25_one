<?php
/**
 * Bulk Operations Service Class
 * Handles bulk update, bulk delete, and mass field assignment operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk Operations Service for managing multiple records efficiently
 */
class AHGMH_Bulk_Operations_Service {

    /**
     * Database handler instance
     *
     * @var AHGMH_Database_Handler
     */
    private $db_handler;

    /**
     * Batch size for processing large datasets
     *
     * @var int
     */
    private $batch_size = 100;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db_handler = new AHGMH_Database_Handler();
    }

    /**
     * Bulk update multiple records
     *
     * @param array $record_ids Array of record IDs to update
     * @param array $update_data Associative array of fields to update
     * @return array Result with success count, failed IDs, and errors
     */
    public function bulk_update($record_ids, $update_data) {
        // Validate inputs
        if (empty($record_ids) || !is_array($record_ids)) {
            return [
                'success' => false,
                'message' => __('Keine Datensätze zum Aktualisieren ausgewählt.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        if (empty($update_data) || !is_array($update_data)) {
            return [
                'success' => false,
                'message' => __('Keine Aktualisierungsdaten angegeben.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        // Sanitize record IDs
        $record_ids = array_map('intval', $record_ids);
        $record_ids = array_filter($record_ids, function($id) {
            return $id > 0;
        });

        if (empty($record_ids)) {
            return [
                'success' => false,
                'message' => __('Ungültige Datensatz-IDs.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        // Sanitize update data
        $sanitized_data = $this->sanitize_update_data($update_data);

        if (empty($sanitized_data)) {
            return [
                'success' => false,
                'message' => __('Keine gültigen Aktualisierungsdaten.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        // Process records in batches
        $batches = array_chunk($record_ids, $this->batch_size);
        $updated_count = 0;
        $failed_ids = [];

        foreach ($batches as $batch) {
            foreach ($batch as $record_id) {
                $result = $this->db_handler->update_submission($record_id, $sanitized_data);

                if ($result !== false) {
                    $updated_count++;
                } else {
                    $failed_ids[] = $record_id;
                }
            }
        }

        $total_records = count($record_ids);
        $success = $updated_count > 0;
        $message = sprintf(
            _n(
                '%d Datensatz erfolgreich aktualisiert.',
                '%d Datensätze erfolgreich aktualisiert.',
                $updated_count,
                'abschussplan-hgmh'
            ),
            $updated_count
        );

        if (!empty($failed_ids)) {
            $message .= ' ' . sprintf(
                _n(
                    '%d Datensatz konnte nicht aktualisiert werden.',
                    '%d Datensätze konnten nicht aktualisiert werden.',
                    count($failed_ids),
                    'abschussplan-hgmh'
                ),
                count($failed_ids)
            );
        }

        return [
            'success' => $success,
            'message' => $message,
            'updated_count' => $updated_count,
            'failed_count' => count($failed_ids),
            'failed_ids' => $failed_ids,
            'total_requested' => $total_records
        ];
    }

    /**
     * Bulk delete multiple records
     *
     * @param array $record_ids Array of record IDs to delete
     * @return array Result with success count, failed IDs, and errors
     */
    public function bulk_delete($record_ids) {
        // Validate inputs
        if (empty($record_ids) || !is_array($record_ids)) {
            return [
                'success' => false,
                'message' => __('Keine Datensätze zum Löschen ausgewählt.', 'abschussplan-hgmh'),
                'deleted_count' => 0,
                'failed_ids' => []
            ];
        }

        // Sanitize record IDs
        $record_ids = array_map('intval', $record_ids);
        $record_ids = array_filter($record_ids, function($id) {
            return $id > 0;
        });

        if (empty($record_ids)) {
            return [
                'success' => false,
                'message' => __('Ungültige Datensatz-IDs.', 'abschussplan-hgmh'),
                'deleted_count' => 0,
                'failed_ids' => []
            ];
        }

        // Process records in batches
        $batches = array_chunk($record_ids, $this->batch_size);
        $deleted_count = 0;
        $failed_ids = [];

        foreach ($batches as $batch) {
            foreach ($batch as $record_id) {
                $result = $this->db_handler->delete_submission($record_id);

                if ($result !== false) {
                    $deleted_count++;
                } else {
                    $failed_ids[] = $record_id;
                }
            }
        }

        $total_records = count($record_ids);
        $success = $deleted_count > 0;
        $message = sprintf(
            _n(
                '%d Datensatz erfolgreich gelöscht.',
                '%d Datensätze erfolgreich gelöscht.',
                $deleted_count,
                'abschussplan-hgmh'
            ),
            $deleted_count
        );

        if (!empty($failed_ids)) {
            $message .= ' ' . sprintf(
                _n(
                    '%d Datensatz konnte nicht gelöscht werden.',
                    '%d Datensätze konnten nicht gelöscht werden.',
                    count($failed_ids),
                    'abschussplan-hgmh'
                ),
                count($failed_ids)
            );
        }

        return [
            'success' => $success,
            'message' => $message,
            'deleted_count' => $deleted_count,
            'failed_count' => count($failed_ids),
            'failed_ids' => $failed_ids,
            'total_requested' => $total_records
        ];
    }

    /**
     * Mass assign meldegruppe to selected records
     *
     * @param array $record_ids Array of record IDs to update
     * @param string $jagdbezirk Jagdbezirk to assign
     * @return array Result with success count, failed IDs, and errors
     */
    public function mass_assign_meldegruppe($record_ids, $jagdbezirk) {
        // Validate inputs
        if (empty($record_ids) || !is_array($record_ids)) {
            return [
                'success' => false,
                'message' => __('Keine Datensätze ausgewählt.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        if (empty($jagdbezirk) || !is_string($jagdbezirk)) {
            return [
                'success' => false,
                'message' => __('Kein Jagdbezirk angegeben.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        // Sanitize jagdbezirk
        $jagdbezirk = sanitize_text_field($jagdbezirk);

        // Validate jagdbezirk exists
        if (!$this->validate_jagdbezirk($jagdbezirk)) {
            return [
                'success' => false,
                'message' => __('Ungültiger Jagdbezirk.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        // Use bulk_update to assign jagdbezirk (field5)
        $update_data = [
            'field5' => $jagdbezirk
        ];

        $result = $this->bulk_update($record_ids, $update_data);

        // Update message to be specific about meldegruppe assignment
        if ($result['success']) {
            $result['message'] = sprintf(
                _n(
                    '%d Datensatz erfolgreich dem Jagdbezirk "%s" zugewiesen.',
                    '%d Datensätze erfolgreich dem Jagdbezirk "%s" zugewiesen.',
                    $result['updated_count'],
                    'abschussplan-hgmh'
                ),
                $result['updated_count'],
                esc_html($jagdbezirk)
            );

            if (!empty($result['failed_ids'])) {
                $result['message'] .= ' ' . sprintf(
                    _n(
                        '%d Datensatz konnte nicht zugewiesen werden.',
                        '%d Datensätze konnten nicht zugewiesen werden.',
                        count($result['failed_ids']),
                        'abschussplan-hgmh'
                    ),
                    count($result['failed_ids'])
                );
            }
        }

        return $result;
    }

    /**
     * Bulk update status for selected records
     *
     * @param array $record_ids Array of record IDs to update
     * @param string $field Field to update (field1, field2, field3, field4, field5, field6)
     * @param string $value Value to set
     * @return array Result with success count, failed IDs, and errors
     */
    public function bulk_update_field($record_ids, $field, $value) {
        // Validate field name
        $allowed_fields = ['field1', 'field2', 'field3', 'field4', 'field5', 'field6', 'game_species'];

        if (!in_array($field, $allowed_fields)) {
            return [
                'success' => false,
                'message' => __('Ungültiges Feld.', 'abschussplan-hgmh'),
                'updated_count' => 0,
                'failed_ids' => []
            ];
        }

        // Sanitize value
        if ($field === 'field6') {
            $value = sanitize_textarea_field($value);
        } else {
            $value = sanitize_text_field($value);
        }

        // Use bulk_update
        $update_data = [
            $field => $value
        ];

        return $this->bulk_update($record_ids, $update_data);
    }

    /**
     * Get records by IDs for preview or validation
     *
     * @param array $record_ids Array of record IDs
     * @return array Array of records
     */
    public function get_records_by_ids($record_ids) {
        global $wpdb;

        if (empty($record_ids) || !is_array($record_ids)) {
            return [];
        }

        // Sanitize record IDs
        $record_ids = array_map('intval', $record_ids);
        $record_ids = array_filter($record_ids, function($id) {
            return $id > 0;
        });

        if (empty($record_ids)) {
            return [];
        }

        $table_name = $this->db_handler->get_table_name();
        $placeholders = implode(',', array_fill(0, count($record_ids), '%d'));

        $query = $wpdb->prepare(
            "SELECT s.*, j.meldegruppe
             FROM $table_name s
             LEFT JOIN {$wpdb->prefix}ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk
             WHERE s.id IN ($placeholders)",
            $record_ids
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results ? $results : [];
    }

    /**
     * Sanitize update data
     *
     * @param array $data Raw update data
     * @return array Sanitized data
     */
    private function sanitize_update_data($data) {
        $sanitized = [];
        $allowed_fields = [
            'game_species', 'field1', 'field2', 'field3', 'field4', 'field5', 'field6'
        ];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                continue;
            }

            if ($key === 'field6') {
                $sanitized[$key] = sanitize_textarea_field($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Validate jagdbezirk exists in database
     *
     * @param string $jagdbezirk Jagdbezirk name
     * @return bool True if valid, false otherwise
     */
    private function validate_jagdbezirk($jagdbezirk) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE jagdbezirk = %s AND active = 1",
            $jagdbezirk
        ));

        return $count > 0;
    }

    /**
     * Get batch size for processing
     *
     * @return int Batch size
     */
    public function get_batch_size() {
        return $this->batch_size;
    }

    /**
     * Set batch size for processing
     *
     * @param int $size Batch size (minimum 10, maximum 500)
     */
    public function set_batch_size($size) {
        $size = intval($size);

        if ($size < 10) {
            $size = 10;
        } elseif ($size > 500) {
            $size = 500;
        }

        $this->batch_size = $size;
    }
}
