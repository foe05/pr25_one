<?php
/**
 * Bulk Operations Controller
 * Handles AJAX requests for bulk update, bulk delete, and mass assignment operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk Operations Controller Class
 */
class AHGMH_Bulk_Operations_Controller {

    private $bulk_operations_service;
    private $undo_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->bulk_operations_service = new AHGMH_Bulk_Operations_Service();
        $this->undo_service = new AHGMH_Undo_Service();
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_bulk_update', array($this, 'ajax_bulk_update'));
        add_action('wp_ajax_ahgmh_bulk_delete', array($this, 'ajax_bulk_delete'));
        add_action('wp_ajax_ahgmh_mass_assign', array($this, 'ajax_mass_assign'));
    }

    /**
     * AJAX: Bulk update multiple records
     *
     * Handles multi-record updates with field validation
     */
    public function ajax_bulk_update() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Validate required parameters
            if (!isset($_POST['record_ids']) || !isset($_POST['update_data'])) {
                throw new Exception(__('Erforderliche Parameter fehlen.', 'abschussplan-hgmh'));
            }

            $record_ids = $_POST['record_ids'];
            $update_data = $_POST['update_data'];

            // Validate record IDs array
            if (!is_array($record_ids) || empty($record_ids)) {
                throw new Exception(__('Keine Datensätze ausgewählt.', 'abschussplan-hgmh'));
            }

            // Validate update data array
            if (!is_array($update_data) || empty($update_data)) {
                throw new Exception(__('Keine Aktualisierungsdaten angegeben.', 'abschussplan-hgmh'));
            }

            // Sanitize record IDs
            $sanitized_ids = array_map('intval', $record_ids);
            $sanitized_ids = array_filter($sanitized_ids, function($id) {
                return $id > 0;
            });

            if (empty($sanitized_ids)) {
                throw new Exception(__('Ungültige Datensatz-IDs.', 'abschussplan-hgmh'));
            }

            // Get records snapshot before update for undo capability
            $records_before = $this->undo_service->get_records_snapshot($sanitized_ids);

            // Log operation for undo
            $description = sprintf(__('%d Datensätze per Massenaktualisierung geändert', 'abschussplan-hgmh'), count($sanitized_ids));
            $log_id = $this->undo_service->log_operation('bulk_update', count($sanitized_ids), $description);

            // Perform bulk update
            $result = $this->bulk_operations_service->bulk_update($sanitized_ids, $update_data);

            // Log changes for undo if operation was logged
            if ($log_id && $result['success']) {
                $changes = array();
                foreach ($sanitized_ids as $record_id) {
                    if (isset($records_before[$record_id])) {
                        foreach ($update_data as $field_name => $new_value) {
                            $old_value = isset($records_before[$record_id][$field_name]) ? $records_before[$record_id][$field_name] : null;
                            if ($old_value !== $new_value) {
                                $changes[] = array(
                                    'record_id' => $record_id,
                                    'field_name' => $field_name,
                                    'old_value' => $old_value,
                                    'new_value' => $new_value
                                );
                            }
                        }
                    }
                }
                if (!empty($changes)) {
                    $this->undo_service->log_bulk_changes($log_id, $changes);
                }
            }

            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'updated_count' => $result['updated_count'],
                    'failed_count' => $result['failed_count'],
                    'failed_ids' => $result['failed_ids'],
                    'total_requested' => $result['total_requested'],
                    'log_id' => $log_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => $result['message']
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Fehler bei Massenaktualisierung: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }

    /**
     * AJAX: Bulk delete multiple records
     *
     * Requires confirmation token for destructive operation safeguard
     */
    public function ajax_bulk_delete() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Validate required parameters
            if (!isset($_POST['record_ids'])) {
                throw new Exception(__('Erforderliche Parameter fehlen.', 'abschussplan-hgmh'));
            }

            $record_ids = $_POST['record_ids'];

            // Validate confirmation token for destructive operation
            if (!isset($_POST['confirm_token']) || $_POST['confirm_token'] !== 'confirm_bulk_delete') {
                throw new Exception(__('Sicherheitsbestätigung fehlt. Bitte bestätigen Sie den Löschvorgang.', 'abschussplan-hgmh'));
            }

            // Validate record IDs array
            if (!is_array($record_ids) || empty($record_ids)) {
                throw new Exception(__('Keine Datensätze ausgewählt.', 'abschussplan-hgmh'));
            }

            // Sanitize record IDs
            $sanitized_ids = array_map('intval', $record_ids);
            $sanitized_ids = array_filter($sanitized_ids, function($id) {
                return $id > 0;
            });

            if (empty($sanitized_ids)) {
                throw new Exception(__('Ungültige Datensatz-IDs.', 'abschussplan-hgmh'));
            }

            // Get records before deletion for undo capability
            $records_before = $this->undo_service->get_records_snapshot($sanitized_ids);

            // Log operation for undo
            $description = sprintf(__('%d Datensätze per Massenlöschung gelöscht', 'abschussplan-hgmh'), count($sanitized_ids));
            $log_id = $this->undo_service->log_operation('bulk_delete', count($sanitized_ids), $description);

            // Perform bulk delete
            $result = $this->bulk_operations_service->bulk_delete($sanitized_ids);

            // Log changes for undo if operation was logged
            if ($log_id && $result['success']) {
                $changes = array();
                foreach ($sanitized_ids as $record_id) {
                    if (isset($records_before[$record_id])) {
                        // Log all fields of the deleted record for restoration
                        foreach ($records_before[$record_id] as $field_name => $old_value) {
                            $changes[] = array(
                                'record_id' => $record_id,
                                'field_name' => $field_name,
                                'old_value' => $old_value,
                                'new_value' => null
                            );
                        }
                    }
                }
                if (!empty($changes)) {
                    $this->undo_service->log_bulk_changes($log_id, $changes);
                }
            }

            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'deleted_count' => $result['deleted_count'],
                    'failed_count' => $result['failed_count'],
                    'failed_ids' => $result['failed_ids'],
                    'total_requested' => $result['total_requested'],
                    'log_id' => $log_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => $result['message']
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Fehler beim Löschen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }

    /**
     * AJAX: Mass assign jagdbezirk (meldegruppe) to selected records
     *
     * Assigns all selected records to a specific jagdbezirk
     */
    public function ajax_mass_assign() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Validate required parameters
            if (!isset($_POST['record_ids']) || !isset($_POST['jagdbezirk'])) {
                throw new Exception(__('Erforderliche Parameter fehlen.', 'abschussplan-hgmh'));
            }

            $record_ids = $_POST['record_ids'];
            $jagdbezirk = sanitize_text_field($_POST['jagdbezirk']);

            // Validate record IDs array
            if (!is_array($record_ids) || empty($record_ids)) {
                throw new Exception(__('Keine Datensätze ausgewählt.', 'abschussplan-hgmh'));
            }

            // Validate jagdbezirk
            if (empty($jagdbezirk)) {
                throw new Exception(__('Kein Jagdbezirk angegeben.', 'abschussplan-hgmh'));
            }

            // Sanitize record IDs
            $sanitized_ids = array_map('intval', $record_ids);
            $sanitized_ids = array_filter($sanitized_ids, function($id) {
                return $id > 0;
            });

            if (empty($sanitized_ids)) {
                throw new Exception(__('Ungültige Datensatz-IDs.', 'abschussplan-hgmh'));
            }

            // Get records snapshot before update for undo capability
            $records_before = $this->undo_service->get_records_snapshot($sanitized_ids);

            // Log operation for undo
            $description = sprintf(__('%d Datensätze dem Jagdbezirk "%s" zugewiesen', 'abschussplan-hgmh'), count($sanitized_ids), $jagdbezirk);
            $log_id = $this->undo_service->log_operation('mass_assign', count($sanitized_ids), $description);

            // Perform mass assignment
            $result = $this->bulk_operations_service->mass_assign_meldegruppe($sanitized_ids, $jagdbezirk);

            // Log changes for undo if operation was logged
            if ($log_id && $result['success']) {
                $changes = array();
                foreach ($sanitized_ids as $record_id) {
                    if (isset($records_before[$record_id])) {
                        $old_value = isset($records_before[$record_id]['jagdbezirk']) ? $records_before[$record_id]['jagdbezirk'] : null;
                        if ($old_value !== $jagdbezirk) {
                            $changes[] = array(
                                'record_id' => $record_id,
                                'field_name' => 'jagdbezirk',
                                'old_value' => $old_value,
                                'new_value' => $jagdbezirk
                            );
                        }
                    }
                }
                if (!empty($changes)) {
                    $this->undo_service->log_bulk_changes($log_id, $changes);
                }
            }

            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'updated_count' => $result['updated_count'],
                    'failed_count' => $result['failed_count'],
                    'failed_ids' => $result['failed_ids'],
                    'total_requested' => $result['total_requested'],
                    'log_id' => $log_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => $result['message']
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Fehler bei Massenzuweisung: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }
}
