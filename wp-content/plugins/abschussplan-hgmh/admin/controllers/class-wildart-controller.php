<?php
/**
 * Wildart Controller
 * Handles Master-Detail UI for wildart configuration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wildart Controller Class
 * Manages wildart CRUD operations with proper security
 */
class AHGMH_Wildart_Controller {
    
    private $wildart_service;
    private $wildart_view;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->wildart_service = new AHGMH_Wildart_Service();
        $this->wildart_view = new AHGMH_Wildart_View();
        
        $this->register_ajax_handlers();
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_create_wildart', array($this, 'ajax_create_wildart'));
        add_action('wp_ajax_ahgmh_delete_wildart', array($this, 'ajax_delete_wildart'));
        add_action('wp_ajax_ahgmh_load_wildart_config', array($this, 'ajax_load_wildart_config'));
        add_action('wp_ajax_ahgmh_save_wildart_categories', array($this, 'ajax_save_wildart_categories'));
        add_action('wp_ajax_ahgmh_save_wildart_meldegruppen', array($this, 'ajax_save_wildart_meldegruppen'));
        add_action('wp_ajax_ahgmh_toggle_limit_mode', array($this, 'ajax_toggle_limit_mode'));
        add_action('wp_ajax_ahgmh_save_limits', array($this, 'ajax_save_limits'));
    }
    
    /**
     * AJAX: Create new wildart
     */
    public function ajax_create_wildart() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $name = AHGMH_Validation_Service::validate_species_name($_POST['name'] ?? '');
            if (!$name) {
                wp_send_json_error('Ungültiger Wildart-Name');
                return;
            }
            
            $result = $this->wildart_service->create_wildart($name);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Erstellen: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX: Delete wildart
     */
    public function ajax_delete_wildart() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $wildart = sanitize_text_field($_POST['wildart'] ?? '');
            if (empty($wildart)) {
                wp_send_json_error('Wildart nicht angegeben');
                return;
            }
            
            $result = $this->wildart_service->delete_wildart($wildart);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Löschen: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX: Load wildart configuration
     */
    public function ajax_load_wildart_config() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $wildart = sanitize_text_field($_POST['wildart'] ?? '');
            if (empty($wildart)) {
                wp_send_json_error('Wildart nicht angegeben');
                return;
            }
            
            $config = $this->wildart_service->get_wildart_config($wildart);
            $html = $this->wildart_view->render_detail_panel($wildart, $config);
            
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Laden: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX: Save wildart categories
     */
    public function ajax_save_wildart_categories() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $wildart = sanitize_text_field($_POST['wildart'] ?? '');
            $categories = AHGMH_Validation_Service::sanitize_text_array($_POST['categories'] ?? []);
            
            if (empty($wildart)) {
                wp_send_json_error('Wildart nicht angegeben');
                return;
            }
            
            $this->wildart_service->save_categories($wildart, $categories);
            wp_send_json_success(array(
                'message' => __('Kategorien erfolgreich gespeichert', 'abschussplan-hgmh')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Speichern: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX: Save wildart meldegruppen
     */
    public function ajax_save_wildart_meldegruppen() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $wildart = sanitize_text_field($_POST['wildart'] ?? '');
            $meldegruppen = AHGMH_Validation_Service::sanitize_text_array($_POST['meldegruppen'] ?? []);
            
            if (empty($wildart)) {
                wp_send_json_error('Wildart nicht angegeben');
                return;
            }
            
            $this->wildart_service->save_meldegruppen($wildart, $meldegruppen);
            wp_send_json_success(array(
                'message' => __('Meldegruppen erfolgreich gespeichert', 'abschussplan-hgmh')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Speichern: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX: Toggle limit mode
     */
    public function ajax_toggle_limit_mode() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $wildart = sanitize_text_field($_POST['wildart'] ?? '');
            $mode = sanitize_text_field($_POST['mode'] ?? '');
            
            if (empty($wildart) || !in_array($mode, ['meldegruppen_specific', 'hegegemeinschaft_total'])) {
                wp_send_json_error('Ungültige Parameter');
                return;
            }
            
            $this->wildart_service->set_limit_mode($wildart, $mode);
            wp_send_json_success(array(
                'message' => __('Limit-Modus erfolgreich geändert', 'abschussplan-hgmh')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Ändern des Modus: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * AJAX: Save limits
     */
    public function ajax_save_limits() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $wildart = sanitize_text_field($_POST['wildart'] ?? '');
            $limits = $_POST['limits'] ?? [];
            
            if (empty($wildart)) {
                wp_send_json_error('Wildart nicht angegeben');
                return;
            }
            
            $validated_limits = AHGMH_Validation_Service::validate_wildart_data(['limits' => $limits]);
            $this->wildart_service->save_limits($wildart, $validated_limits['limits']);
            
            wp_send_json_success(array(
                'message' => __('Limits erfolgreich gespeichert', 'abschussplan-hgmh')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Speichern der Limits: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * Render wildart configuration section
     */
    public function render_config_section() {
        $wildarten = $this->wildart_service->get_all_wildarten();
        return $this->wildart_view->render_master_detail_ui($wildarten);
    }
}
