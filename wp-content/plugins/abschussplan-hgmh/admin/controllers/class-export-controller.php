<?php
/**
 * Export Controller
 * Handles secure CSV and Excel exports
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export Controller Class
 */
class AHGMH_Export_Controller {
    
    private $export_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->export_service = new AHGMH_Export_Service();
        $this->register_ajax_handlers();
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_quick_export', array($this, 'ajax_quick_export'));
        add_action('wp_ajax_ahgmh_export_data', array($this, 'ajax_export_data'));
    }
    
    /**
     * AJAX: Quick CSV export
     */
    public function ajax_quick_export() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $species = sanitize_text_field($_POST['species'] ?? '');
            $format = sanitize_text_field($_POST['format'] ?? 'csv');
            
            [$species, $format] = AHGMH_Validation_Service::validate_export_params($species, $format);
            
            $result = $this->export_service->create_export($species, $format);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Export: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX: Custom data export with filters
     */
    public function ajax_export_data() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $filters = [
                'species' => sanitize_text_field($_POST['species'] ?? ''),
                'meldegruppe' => sanitize_text_field($_POST['meldegruppe'] ?? ''),
                'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
                'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
                'format' => sanitize_text_field($_POST['format'] ?? 'csv')
            ];
            
            $result = $this->export_service->create_filtered_export($filters);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Export: ' . esc_html($e->getMessage()));
        }
    }
}
