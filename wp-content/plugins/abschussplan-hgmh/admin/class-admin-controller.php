<?php
/**
 * Main Admin Controller -- coordinates all admin functionality.
 *
 * This controller is the intended replacement for the monolithic
 * AHGMH_Admin_Page_Modern class.  It delegates to specialised
 * sub-controllers for each admin page / feature area.
 *
 * NOTE: Currently not activated in production.  The legacy
 * AHGMH_Admin_Page_Modern is still the primary admin entry-point.
 * Individual sub-controllers ARE active (instantiated in the main
 * plugin file) for their AJAX handlers.
 *
 * Test: Instantiate AHGMH_Admin_Controller and verify that the
 *       admin_menu hook is registered and the sub-controllers are
 *       initialised.
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Coordinates dashboard, data, settings, and wildart management.
 */
class AHGMH_Admin_Controller {

    /** @var AHGMH_Dashboard_Controller */
    private $dashboard_controller;

    /** @var AHGMH_Data_Controller */
    private $data_controller;

    /** @var AHGMH_Settings_Controller */
    private $settings_controller;

    /** @var AHGMH_Wildart_Controller */
    private $wildart_controller;

    /** @var AHGMH_Export_Controller */
    private $export_controller;

    /** @var AHGMH_Limits_Controller */
    private $limits_controller;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_controllers();
        $this->register_hooks();
    }

    /**
     * Create sub-controller instances.
     */
    private function init_controllers() {
        $this->dashboard_controller = new AHGMH_Dashboard_Controller();
        $this->data_controller      = new AHGMH_Data_Controller();
        $this->settings_controller  = new AHGMH_Settings_Controller();
        $this->wildart_controller   = new AHGMH_Wildart_Controller();
        $this->export_controller    = new AHGMH_Export_Controller();
        $this->limits_controller    = new AHGMH_Limits_Controller();
    }

    /**
     * Register WordPress hooks.
     */
    private function register_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
    }

    /**
     * Register the admin menu structure.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Abschussplan HGMH', 'abschussplan-hgmh' ),
            __( 'Abschussplan', 'abschussplan-hgmh' ),
            'manage_options',
            'abschussplan-hgmh',
            array( $this->dashboard_controller, 'render' ),
            'dashicons-chart-pie',
            30
        );

        add_submenu_page(
            'abschussplan-hgmh',
            __( 'Dashboard', 'abschussplan-hgmh' ),
            __( 'Dashboard', 'abschussplan-hgmh' ),
            'manage_options',
            'abschussplan-hgmh',
            array( $this->dashboard_controller, 'render' )
        );

        add_submenu_page(
            'abschussplan-hgmh',
            __( 'Meldungen verwalten', 'abschussplan-hgmh' ),
            __( 'Meldungen', 'abschussplan-hgmh' ),
            'manage_options',
            'abschussplan-hgmh-data',
            array( $this->data_controller, 'render' )
        );

        add_submenu_page(
            'abschussplan-hgmh',
            __( 'Einstellungen', 'abschussplan-hgmh' ),
            __( 'Einstellungen', 'abschussplan-hgmh' ),
            'manage_options',
            'abschussplan-hgmh-settings',
            array( $this->settings_controller, 'render' )
        );
    }

    /**
     * Enqueue admin scripts and styles for plugin pages.
     *
     * @param string $hook The current admin page hook suffix.
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( false === strpos( $hook, 'abschussplan-hgmh' ) ) {
            return;
        }

        wp_enqueue_style(
            'ahgmh-admin-modern',
            AHGMH_PLUGIN_URL . 'admin/assets/admin-modern.css',
            array(),
            AHGMH_PLUGIN_VERSION
        );

        wp_enqueue_style(
            'ahgmh-admin-modern-extracted',
            AHGMH_PLUGIN_URL . 'admin/assets/admin-modern-extracted.css',
            array( 'ahgmh-admin-modern' ),
            AHGMH_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ahgmh-core',
            AHGMH_PLUGIN_URL . 'admin/assets/modules/core.js',
            array( 'jquery' ),
            AHGMH_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'ahgmh-core',
            'ahgmh_admin',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ahgmh_admin_nonce' ),
            )
        );

        if ( 'toplevel_page_abschussplan-hgmh' === $hook ) {
            wp_enqueue_script(
                'ahgmh-dashboard',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/dashboard.js',
                array( 'jquery', 'ahgmh-core' ),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }

        if ( 'abschussplan_page_abschussplan-hgmh-data' === $hook ) {
            wp_enqueue_script(
                'ahgmh-quick-actions',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/quick-actions.js',
                array( 'jquery', 'ahgmh-core' ),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }

        if ( 'abschussplan_page_abschussplan-hgmh-wildarten' === $hook ) {
            wp_enqueue_script(
                'ahgmh-wildart-config',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/wildart-config.js',
                array( 'jquery', 'ahgmh-core' ),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }

        if ( 'abschussplan_page_abschussplan-hgmh-obleute' === $hook ) {
            wp_enqueue_script(
                'ahgmh-obmann-management',
                AHGMH_PLUGIN_URL . 'admin/assets/modules/obmann-management.js',
                array( 'jquery', 'ahgmh-core' ),
                AHGMH_PLUGIN_VERSION,
                true
            );
        }
    }

    /**
     * Add the WP Dashboard widget.
     */
    public function add_dashboard_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'ahgmh_dashboard_widget',
            __( 'Abschussplan HGMH - Übersicht', 'abschussplan-hgmh' ),
            array( $this->dashboard_controller, 'render_widget' )
        );
    }

    /**
     * Route a request to the appropriate controller.
     *
     * @param string $page The page slug.
     */
    public function route_request( $page ) {
        switch ( $page ) {
            case 'abschussplan-hgmh':
                $this->dashboard_controller->render();
                break;
            case 'abschussplan-hgmh-data':
                $this->data_controller->render();
                break;
            case 'abschussplan-hgmh-settings':
                $this->settings_controller->render();
                break;
            default:
                $this->dashboard_controller->render();
                break;
        }
    }
}
