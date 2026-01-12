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

    /**
     * Constructor
     */
    public function __construct() {
        $this->bulk_operations_service = new AHGMH_Bulk_Operations_Service();
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

            // Perform bulk update
            $result = $this->bulk_operations_service->bulk_update($sanitized_ids, $update_data);

            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'updated_count' => $result['updated_count'],
                    'failed_count' => $result['failed_count'],
                    'failed_ids' => $result['failed_ids'],
                    'total_requested' => $result['total_requested']
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

            // Get records before deletion for undo capability (future enhancement)
            $records_before = $this->bulk_operations_service->get_records_by_ids($sanitized_ids);

            // Perform bulk delete
            $result = $this->bulk_operations_service->bulk_delete($sanitized_ids);

            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'deleted_count' => $result['deleted_count'],
                    'failed_count' => $result['failed_count'],
                    'failed_ids' => $result['failed_ids'],
                    'total_requested' => $result['total_requested']
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

            // Perform mass assignment
            $result = $this->bulk_operations_service->mass_assign_meldegruppe($sanitized_ids, $jagdbezirk);

            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'updated_count' => $result['updated_count'],
                    'failed_count' => $result['failed_count'],
                    'failed_ids' => $result['failed_ids'],
                    'total_requested' => $result['total_requested']
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
