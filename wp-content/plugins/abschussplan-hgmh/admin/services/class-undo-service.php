<?php
/**
 * Undo Service Class
 * Logs operations before execution and provides undo capability
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Undo Service for logging operations and restoring previous state
 */
class AHGMH_Undo_Service {

    /**
     * Database handler instance
     *
     * @var AHGMH_Database_Handler
     */
    private $db_handler;

    /**
     * Retention period in days
     *
     * @var int
     */
    private $retention_days = 30;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db_handler = new AHGMH_Database_Handler();
    }

    /**
     * Log an operation before execution
     *
     * @param string $operation_type Type of operation (import, bulk_update, bulk_delete, mass_assign)
     * @param int $affected_count Number of records affected
     * @param string $description Human-readable description of the operation
     * @return int|false Log ID on success, false on failure
     */
    public function log_operation($operation_type, $affected_count, $description = '') {
        global $wpdb;

        $operations_table = $wpdb->prefix . 'ahgmh_operation_logs';
        $user_id = get_current_user_id();

        $result = $wpdb->insert(
            $operations_table,
            [
                'operation_type' => sanitize_text_field($operation_type),
                'user_id' => $user_id,
                'affected_count' => intval($affected_count),
                'description' => sanitize_text_field($description),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%d', '%d', '%s', '%s']
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Log a record change for undo capability
     *
     * @param int $log_id The operation log ID
     * @param int $record_id The submission record ID
     * @param string $field_name The field name that changed
     * @param mixed $old_value The previous value
     * @param mixed $new_value The new value
     * @return bool True on success, false on failure
     */
    public function log_record_change($log_id, $record_id, $field_name, $old_value, $new_value) {
        global $wpdb;

        $details_table = $wpdb->prefix . 'ahgmh_operation_log_details';

        $result = $wpdb->insert(
            $details_table,
            [
                'log_id' => intval($log_id),
                'record_id' => intval($record_id),
                'field_name' => sanitize_text_field($field_name),
                'old_value' => is_array($old_value) ? json_encode($old_value) : $old_value,
                'new_value' => is_array($new_value) ? json_encode($new_value) : $new_value
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Log multiple record changes in bulk
     *
     * @param int $log_id The operation log ID
     * @param array $changes Array of changes with structure: [['record_id' => int, 'field_name' => string, 'old_value' => mixed, 'new_value' => mixed], ...]
     * @return int Number of changes successfully logged
     */
    public function log_bulk_changes($log_id, $changes) {
        $logged_count = 0;

        foreach ($changes as $change) {
            if (!isset($change['record_id']) || !isset($change['field_name'])) {
                continue;
            }

            $old_value = isset($change['old_value']) ? $change['old_value'] : null;
            $new_value = isset($change['new_value']) ? $change['new_value'] : null;

            if ($this->log_record_change($log_id, $change['record_id'], $change['field_name'], $old_value, $new_value)) {
                $logged_count++;
            }
        }

        return $logged_count;
    }

    /**
     * Get records before bulk operation for logging
     *
     * @param array $record_ids Array of record IDs
     * @return array Array of records indexed by ID
     */
    public function get_records_snapshot($record_ids) {
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
            "SELECT * FROM $table_name WHERE id IN ($placeholders)",
            $record_ids
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        // Index by ID for easy lookup
        $snapshot = [];
        if ($results) {
            foreach ($results as $record) {
                $snapshot[$record['id']] = $record;
            }
        }

        return $snapshot;
    }

    /**
     * Undo an operation by restoring previous values
     *
     * @param int $log_id The operation log ID to undo
     * @return array Result with success status, restored count, and message
     */
    public function undo_operation($log_id) {
        global $wpdb;

        $log_id = intval($log_id);

        if ($log_id <= 0) {
            return [
                'success' => false,
                'message' => __('Ungültige Log-ID.', 'abschussplan-hgmh'),
                'restored_count' => 0,
                'failed_ids' => []
            ];
        }

        // Get operation details
        $operations_table = $wpdb->prefix . 'ahgmh_operation_logs';
        $operation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $operations_table WHERE id = %d",
            $log_id
        ), ARRAY_A);

        if (!$operation) {
            return [
                'success' => false,
                'message' => __('Operation nicht gefunden.', 'abschussplan-hgmh'),
                'restored_count' => 0,
                'failed_ids' => []
            ];
        }

        // Get logged changes
        $details_table = $wpdb->prefix . 'ahgmh_operation_log_details';
        $changes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $details_table WHERE log_id = %d ORDER BY record_id, id",
            $log_id
        ), ARRAY_A);

        if (empty($changes)) {
            return [
                'success' => false,
                'message' => __('Keine Änderungen zum Rückgängigmachen gefunden.', 'abschussplan-hgmh'),
                'restored_count' => 0,
                'failed_ids' => []
            ];
        }

        // Group changes by record ID
        $records_to_restore = [];
        foreach ($changes as $change) {
            $record_id = $change['record_id'];
            if (!isset($records_to_restore[$record_id])) {
                $records_to_restore[$record_id] = [];
            }
            $records_to_restore[$record_id][$change['field_name']] = $change['old_value'];
        }

        // Restore records
        $restored_count = 0;
        $failed_ids = [];

        foreach ($records_to_restore as $record_id => $fields) {
            // Handle special case: delete operations (old_value contains full record)
            if ($operation['operation_type'] === 'bulk_delete') {
                $result = $this->restore_deleted_record($record_id, $fields);
            } else {
                // Update existing record with old values
                $result = $this->db_handler->update_submission($record_id, $fields);
            }

            if ($result !== false) {
                $restored_count++;
            } else {
                $failed_ids[] = $record_id;
            }
        }

        $total_records = count($records_to_restore);
        $success = $restored_count > 0;

        $message = sprintf(
            _n(
                '%d Datensatz erfolgreich wiederhergestellt.',
                '%d Datensätze erfolgreich wiederhergestellt.',
                $restored_count,
                'abschussplan-hgmh'
            ),
            $restored_count
        );

        if (!empty($failed_ids)) {
            $message .= ' ' . sprintf(
                _n(
                    '%d Datensatz konnte nicht wiederhergestellt werden.',
                    '%d Datensätze konnten nicht wiederhergestellt werden.',
                    count($failed_ids),
                    'abschussplan-hgmh'
                ),
                count($failed_ids)
            );
        }

        return [
            'success' => $success,
            'message' => $message,
            'operation_type' => $operation['operation_type'],
            'restored_count' => $restored_count,
            'failed_count' => count($failed_ids),
            'failed_ids' => $failed_ids,
            'total_requested' => $total_records
        ];
    }

    /**
     * Restore a deleted record
     *
     * @param int $record_id The original record ID
     * @param array $fields The field values to restore
     * @return bool True on success, false on failure
     */
    private function restore_deleted_record($record_id, $fields) {
        global $wpdb;

        $table_name = $this->db_handler->get_table_name();

        // Check if record still exists (shouldn't for deleted records)
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %d",
            $record_id
        ));

        if ($exists > 0) {
            // Record exists, just update it
            return $this->db_handler->update_submission($record_id, $fields);
        }

        // Re-insert the deleted record with original ID
        // Build insert data
        $insert_data = [];
        $format = [];

        // Include the original ID
        $insert_data['id'] = $record_id;
        $format[] = '%d';

        foreach ($fields as $field_name => $value) {
            $insert_data[$field_name] = $value;
            $format[] = '%s';
        }

        $result = $wpdb->insert($table_name, $insert_data, $format);

        return $result !== false;
    }

    /**
     * Get recent operations that can be undone
     *
     * @param int $limit Number of operations to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of operations with log details
     */
    public function get_recent_operations($limit = 10, $offset = 0) {
        global $wpdb;

        $operations_table = $wpdb->prefix . 'ahgmh_operation_logs';

        $query = $wpdb->prepare(
            "SELECT o.*, u.display_name as user_name
             FROM $operations_table o
             LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
             ORDER BY o.created_at DESC
             LIMIT %d OFFSET %d",
            intval($limit),
            intval($offset)
        );

        $operations = $wpdb->get_results($query, ARRAY_A);

        // Get change counts for each operation
        $details_table = $wpdb->prefix . 'ahgmh_operation_log_details';
        foreach ($operations as &$operation) {
            $change_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT record_id) FROM $details_table WHERE log_id = %d",
                $operation['id']
            ));
            $operation['change_count'] = intval($change_count);
        }

        return $operations;
    }

    /**
     * Get operation details by log ID
     *
     * @param int $log_id The operation log ID
     * @return array|null Operation details or null if not found
     */
    public function get_operation($log_id) {
        global $wpdb;

        $operations_table = $wpdb->prefix . 'ahgmh_operation_logs';

        $operation = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, u.display_name as user_name
             FROM $operations_table o
             LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
             WHERE o.id = %d",
            intval($log_id)
        ), ARRAY_A);

        if (!$operation) {
            return null;
        }

        // Get changes for this operation
        $details_table = $wpdb->prefix . 'ahgmh_operation_log_details';
        $changes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $details_table WHERE log_id = %d ORDER BY record_id, id",
            $log_id
        ), ARRAY_A);

        $operation['changes'] = $changes;
        $operation['change_count'] = count($changes);

        return $operation;
    }

    /**
     * Clean up old operation logs beyond retention period
     *
     * @return array Result with deleted counts
     */
    public function cleanup_old_logs() {
        global $wpdb;

        $operations_table = $wpdb->prefix . 'ahgmh_operation_logs';
        $details_table = $wpdb->prefix . 'ahgmh_operation_log_details';

        // Calculate cutoff date
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$this->retention_days} days"));

        // Get old log IDs
        $old_log_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $operations_table WHERE created_at < %s",
            $cutoff_date
        ));

        if (empty($old_log_ids)) {
            return [
                'success' => true,
                'message' => __('Keine alten Logs zum Aufräumen gefunden.', 'abschussplan-hgmh'),
                'deleted_operations' => 0,
                'deleted_details' => 0
            ];
        }

        // Delete details first (foreign key relationship)
        $placeholders = implode(',', array_fill(0, count($old_log_ids), '%d'));
        $deleted_details = $wpdb->query($wpdb->prepare(
            "DELETE FROM $details_table WHERE log_id IN ($placeholders)",
            $old_log_ids
        ));

        // Delete operations
        $deleted_operations = $wpdb->query($wpdb->prepare(
            "DELETE FROM $operations_table WHERE created_at < %s",
            $cutoff_date
        ));

        $message = sprintf(
            __('%d Operationen und %d Details erfolgreich aufgeräumt.', 'abschussplan-hgmh'),
            $deleted_operations,
            $deleted_details
        );

        return [
            'success' => true,
            'message' => $message,
            'deleted_operations' => $deleted_operations,
            'deleted_details' => $deleted_details,
            'cutoff_date' => $cutoff_date
        ];
    }

    /**
     * Get retention period in days
     *
     * @return int Retention days
     */
    public function get_retention_days() {
        return $this->retention_days;
    }

    /**
     * Set retention period in days
     *
     * @param int $days Number of days to retain logs (minimum 1, maximum 365)
     */
    public function set_retention_days($days) {
        $days = intval($days);

        if ($days < 1) {
            $days = 1;
        } elseif ($days > 365) {
            $days = 365;
        }

        $this->retention_days = $days;
    }

    /**
     * Check if an operation can be undone
     *
     * @param int $log_id The operation log ID
     * @return bool True if operation can be undone, false otherwise
     */
    public function can_undo($log_id) {
        global $wpdb;

        $operations_table = $wpdb->prefix . 'ahgmh_operation_logs';

        $operation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $operations_table WHERE id = %d",
            intval($log_id)
        ), ARRAY_A);

        if (!$operation) {
            return false;
        }

        // Check if operation is within retention period
        $created_timestamp = strtotime($operation['created_at']);
        $retention_timestamp = strtotime("-{$this->retention_days} days");

        if ($created_timestamp < $retention_timestamp) {
            return false;
        }

        // Check if there are changes logged
        $details_table = $wpdb->prefix . 'ahgmh_operation_log_details';
        $change_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $details_table WHERE log_id = %d",
            $log_id
        ));

        return $change_count > 0;
    }
}
