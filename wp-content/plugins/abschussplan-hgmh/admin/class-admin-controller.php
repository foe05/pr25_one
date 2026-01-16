<?php
/**
 * Main Admin Controller - Coordinates all admin functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Admin Controller Class
 * Coordinates dashboard, data, settings, and wildart management
 */
class AHGMH_Admin_Controller {
    
    private $dashboard_controller;
    private $data_controller;  
    private $settings_controller;
    private $wildart_controller;
    private $export_controller;
    private $limits_controller;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_controllers();
        $this->register_hooks();
    }
    
    /**
     * Initialize sub-controllers
     */
    private function init_controllers() {
        $this->dashboard_controller = new AHGMH_Dashboard_Controller();
        $this->data_controller = new AHGMH_Data_Controller();
        $this->settings_controller = new AHGMH_Settings_Controller();  
        $this->wildart_controller = new AHGMH_Wildart_Controller();
        $this->export_controller = new AHGMH_Export_Controller();
        $this->limits_controller = new AHGMH_Limits_Controller();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }
    
    /**
     * Add admin menu structure
     */
    public function add_admin_menu() {
        // Main menu page - Dashboard
        add_menu_page(
            __('Abschussplan HGMH', 'abschussplan-hgmh'),
            __('Abschussplan', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh',
            array($this->dashboard_controller, 'render'),
            'dashicons-chart-pie',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'abschussplan-hgmh',
            __('Dashboard', 'abschussplan-hgmh'),
            __('📊 Dashboard', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh',
            array($this->dashboard_controller, 'render')
        );
        
        // Data Management submenu
        add_submenu_page(
            'abschussplan-hgmh',
            __('Meldungen verwalten', 'abschussplan-hgmh'),
            __('📋 Meldungen', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-data',
            array($this->data_controller, 'render')
        );
        
        // Settings submenu
        add_submenu_page(
            'abschussplan-hgmh',
            __('Einstellungen', 'abschussplan-hgmh'),
            __('⚙️ Einstellungen', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-settings',
            array($this->settings_controller, 'render')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'abschussplan-hgmh') === false) {
            return;
        }

        // Main admin stylesheet
        wp_enqueue_style(
            'ahgmh-admin-modern',
            AHGMH_PLUGIN_URL . 'admin/assets/admin-modern.css',
            array(),
            AHGMH_PLUGIN_VERSION
        );

        // Extracted CSS (notifications, tooltips, animations)
        wp_enqueue_style(
            'ahgmh-admin-modern-extracted',
            AHGMH_PLUGIN_URL . 'admin/assets/admin-modern-extracted.css',
            array('ahgmh-admin-modern'),
            AHGMH_PLUGIN_VERSION
        );

        // Core module (notifications, tooltips, tab switching) - loads on all admin pages
        wp_enqueue_script(
            'ahgmh-core',
            AHGMH_PLUGIN_URL . 'admin/assets/modules/core.js',
            array('jquery'),
            AHGMH_PLUGIN_VERSION,
            true
        );

        // Dashboard module - only load on Dashboard page
        if ($hook === 'toplevel_page_abschussplan-hgmh') {
            wp_enqueue_script(
                'ahgmh-dashboard',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/dashboard.js',
                array('jquery', 'ahgmh-core'),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }

        // Quick Actions module - only load on Meldungen page
        if ($hook === 'abschussplan_page_abschussplan-hgmh-data') {
            wp_enqueue_script(
                'ahgmh-quick-actions',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/quick-actions.js',
                array('jquery', 'ahgmh-core'),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }

        // Wildart Config module - only load on Wildarten page
        if ($hook === 'abschussplan_page_abschussplan-hgmh-wildarten') {
            wp_enqueue_script(
                'ahgmh-wildart-config',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/wildart-config.js',
                array('jquery', 'ahgmh-core'),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }

        // Obmann Management module - only load on Obleute page
        if ($hook === 'abschussplan_page_abschussplan-hgmh-obleute') {
            wp_enqueue_script(
                'ahgmh-obmann-management',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/obmann-management.js',
                array('jquery', 'ahgmh-core'),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }
    }
    
    /**
     * Add WordPress Dashboard Widget
     */
    public function add_dashboard_widget() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'ahgmh_dashboard_widget',
            __('🦌 Abschussplan HGMH - Übersicht', 'abschussplan-hgmh'),
            array($this->dashboard_controller, 'render_widget')
        );
    }
    
    /**
     * Route request to appropriate controller
     */
    public function route_request($page) {
        switch ($page) {
            case 'abschussplan-hgmh':
                return $this->dashboard_controller->render();
            case 'abschussplan-hgmh-data':
                return $this->data_controller->render();
            case 'abschussplan-hgmh-settings':
                return $this->settings_controller->render();
            default:
                return $this->dashboard_controller->render();
        }
    }
}
