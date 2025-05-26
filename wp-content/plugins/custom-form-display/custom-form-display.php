<?php
/**
 * Plugin Name: Abschussplan HGMH
 * Plugin URI: #
 * Description: Collect and view game shoots for registration with local hunting authorities in Germany.
 * Version: 1.0.0
 * Author: foe05
 * Text Domain: abschussplan-hgmh
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CFD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFD_PLUGIN_VERSION', '1.0.0');

// Include required files
require_once CFD_PLUGIN_DIR . 'includes/class-database-handler.php';
require_once CFD_PLUGIN_DIR . 'includes/class-form-handler.php';
require_once CFD_PLUGIN_DIR . 'includes/class-table-display.php';
require_once CFD_PLUGIN_DIR . 'admin/class-admin-page.php';

/**
 * Main plugin class
 */
class Custom_Form_Display {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Database handler instance
     */
    public $database;

    /**
     * Form handler instance
     */
    public $form;

    /**
     * Table display instance
     */
    public $table;

    /**
     * Admin page instance
     */
    public $admin;

    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize plugin components
        $this->init();

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));

        // Register deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Initialize plugin components
     */
    private function init() {
        // Initialize database handler
        $this->database = new CFD_Database_Handler();

        // Initialize form handler
        $this->form = new CFD_Form_Handler();

        // Initialize table display
        $this->table = new CFD_Table_Display();

        // Initialize admin page
        $this->admin = new CFD_Admin_Page();
    }

    /**
     * Plugin activation hook
     */
    public function activate_plugin() {
        // Create database table
        $this->database->create_table();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate_plugin() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue Bootstrap from CDN
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.replit.com/agent/bootstrap-agent-dark-theme.min.css',
            array(),
            CFD_PLUGIN_VERSION
        );

        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue Bootstrap JS
        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );

        // Enqueue custom styles
        wp_enqueue_style(
            'cfd-style',
            CFD_PLUGIN_URL . 'assets/css/style.css',
            array('bootstrap-css'),
            CFD_PLUGIN_VERSION
        );

        // Enqueue form validation script
        wp_enqueue_script(
            'cfd-form-validation',
            CFD_PLUGIN_URL . 'assets/js/form-validation.js',
            array('jquery'),
            CFD_PLUGIN_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'cfd-form-validation',
            'cfd_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cfd_form_nonce')
            )
        );
    }
}

// Initialize the plugin
function custom_form_display() {
    return Custom_Form_Display::get_instance();
}

// Start the plugin
custom_form_display();
