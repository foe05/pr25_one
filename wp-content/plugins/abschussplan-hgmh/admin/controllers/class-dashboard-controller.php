<?php
/**
 * Dashboard Controller
 * Handles dashboard display, statistics, and widgets
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Controller Class
 */
class AHGMH_Dashboard_Controller {
    
    private $dashboard_service;
    private $dashboard_view;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->dashboard_service = new AHGMH_Dashboard_Service();
        $this->dashboard_view = new AHGMH_Dashboard_View();
        
        $this->register_ajax_handlers();
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_dashboard_stats', array($this, 'ajax_dashboard_stats'));
    }
    
    /**
     * Render main dashboard page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $stats = $this->dashboard_service->get_dashboard_stats();
        $this->dashboard_view->render_dashboard($stats);
    }
    
    /**
     * Render dashboard widget for WordPress admin
     */
    public function render_widget() {
        $stats = $this->dashboard_service->get_dashboard_stats();
        $this->dashboard_view->render_widget($stats);
    }
    
    /**
     * AJAX handler for dashboard stats
     */
    public function ajax_dashboard_stats() {
        AHGMH_Validation_Service::verify_ajax_request();
        
        try {
            $stats = $this->dashboard_service->get_dashboard_stats();
            wp_send_json_success($stats);
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Laden der Statistiken: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * Get dashboard data for other controllers
     */
    public function get_stats() {
        return $this->dashboard_service->get_dashboard_stats();
    }
}
