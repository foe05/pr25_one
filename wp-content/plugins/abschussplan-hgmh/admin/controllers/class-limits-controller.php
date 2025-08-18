<?php
/**
 * Limits Controller - Manages hunting limits
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Limits_Controller {
    
    private $limits_service;
    
    public function __construct() {
        $this->limits_service = new AHGMH_Limits_Service();
        $this->register_ajax_handlers();
    }
    
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_save_meldegruppe_limits', array($this, 'ajax_save_limits'));
        add_action('wp_ajax_ahgmh_toggle_meldegruppe_custom_limits', array($this, 'ajax_toggle_custom_limits'));
    }
    
    public function ajax_save_limits() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $limits = $_POST['limits'] ?? [];
            $this->limits_service->save_limits($limits);
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error(esc_html($e->getMessage()));
        }
    }
    
    public function ajax_toggle_custom_limits() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $enabled = (bool)($_POST['enabled'] ?? false);
            $this->limits_service->toggle_custom_limits($enabled);
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error(esc_html($e->getMessage()));
        }
    }
}
