<?php
/**
 * Undo Controller
 * Handles AJAX requests for undo operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Undo Controller Class
 */
class AHGMH_Undo_Controller {

    private $undo_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->undo_service = new AHGMH_Undo_Service();
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_undo_operation', array($this, 'ajax_undo_operation'));
        add_action('wp_ajax_ahgmh_get_recent_operations', array($this, 'ajax_get_recent_operations'));
    }

    /**
     * AJAX: Undo a specific operation
     *
     * Reverts changes from a logged operation and restores previous state
     */
    public function ajax_undo_operation() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Validate required parameters
            if (!isset($_POST['log_id'])) {
                throw new Exception(__('Erforderliche Parameter fehlen.', 'abschussplan-hgmh'));
            }

            $log_id = intval($_POST['log_id']);

            if ($log_id <= 0) {
                throw new Exception(__('Ungültige Log-ID.', 'abschussplan-hgmh'));
            }

            // Check if operation can be undone
            if (!$this->undo_service->can_undo($log_id)) {
                throw new Exception(__('Diese Operation kann nicht rückgängig gemacht werden. Sie ist möglicherweise zu alt oder enthält keine Änderungen.', 'abschussplan-hgmh'));
            }

            // Perform undo operation
            $result = $this->undo_service->undo_operation($log_id);

            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'operation_type' => $result['operation_type'],
                    'restored_count' => $result['restored_count'],
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
                'message' => __('Fehler beim Rückgängigmachen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }

    /**
     * AJAX: Get recent operations that can be undone
     *
     * Returns list of recent operations with details for undo UI
     */
    public function ajax_get_recent_operations() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

            // Validate pagination parameters
            $limit = max(1, min($limit, 100)); // Between 1 and 100
            $offset = max(0, $offset);

            // Get recent operations
            $operations = $this->undo_service->get_recent_operations($limit, $offset);

            // Format operations for display
            $formatted_operations = array();
            foreach ($operations as $operation) {
                $can_undo = $this->undo_service->can_undo($operation['id']);

                $formatted_operations[] = array(
                    'id' => $operation['id'],
                    'operation_type' => $operation['operation_type'],
                    'description' => $operation['description'],
                    'affected_count' => $operation['affected_count'],
                    'change_count' => $operation['change_count'],
                    'user_name' => $operation['user_name'],
                    'created_at' => $operation['created_at'],
                    'can_undo' => $can_undo
                );
            }

            wp_send_json_success(array(
                'operations' => $formatted_operations,
                'total' => count($formatted_operations),
                'limit' => $limit,
                'offset' => $offset
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Fehler beim Laden der Operationen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }
}
