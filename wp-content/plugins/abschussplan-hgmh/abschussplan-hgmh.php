<?php
/**
 * Plugin Name: Abschussplan HGMH
 * Plugin URI: https://github.com/foe05/pr25_one
 * Description: Collect and view game shoots for registration with local hunting authorities in Germany.
 * Version: 2.4.0
 * Author: foe05
 * Author URI: https://github.com/foe05
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: abschussplan-hgmh
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 * Update URI: https://github.com/foe05/pr25_one
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AHGMH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AHGMH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AHGMH_PLUGIN_VERSION', '2.4.0');

// Include required files
require_once AHGMH_PLUGIN_DIR . 'includes/class-database-handler.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-form-handler.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-table-display.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-permissions-service.php';

// Include admin-only architecture when needed
if (is_admin()) {
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-validation-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-dashboard-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-wildart-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-export-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-limits-service.php';

    require_once AHGMH_PLUGIN_DIR . 'admin/views/class-dashboard-view.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/views/class-wildart-view.php';

    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-dashboard-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-data-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-settings-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-wildart-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-export-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-limits-controller.php';

    require_once AHGMH_PLUGIN_DIR . 'admin/class-admin-controller.php';

    // Legacy support - keep for backward compatibility
    require_once AHGMH_PLUGIN_DIR . 'admin/class-admin-page-modern.php';
    
    // EMERGENCY: Load emergency AJAX handlers for missing methods
    require_once AHGMH_PLUGIN_DIR . 'admin/ajax-handlers-emergency.php';
}

/**
 * Main plugin class
 */
class Abschussplan_HGMH {
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
     * Admin page instance (Legacy)
     */
    public $admin;
    
    /**
     * New modular admin controller
     */
    public $admin_controller;

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
        $this->database = new AHGMH_Database_Handler();

        // Initialize form handler
        $this->form = new AHGMH_Form_Handler();

        // Initialize table display
        $this->table = new AHGMH_Table_Display();

        // Initialize admin controllers only in admin area
        if (is_admin()) {
            // TEMPORARY: Use only legacy admin to fix broken backend
            $this->admin = new AHGMH_Admin_Page_Modern();
            
            // New modular controller disabled until issues resolved
            // $this->admin_controller = new AHGMH_Admin_Controller();
        }
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
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            AHGMH_PLUGIN_VERSION
        );
        
        // Enqueue Bootstrap Icons
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
            array(),
            AHGMH_PLUGIN_VERSION
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
            'ahgmh-style',
            AHGMH_PLUGIN_URL . 'assets/css/style.css',
            array('bootstrap-css'),
            AHGMH_PLUGIN_VERSION
        );

        // Enqueue form validation script
        wp_enqueue_script(
            'ahgmh-form-validation',
            AHGMH_PLUGIN_URL . 'assets/js/form-validation.js',
            array('jquery'),
            AHGMH_PLUGIN_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'ahgmh-form-validation',
            'ahgmh_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ahgmh_form_nonce')
            )
        );
    }
}

// Initialize the plugin
function abschussplan_hgmh() {
    return Abschussplan_HGMH::get_instance();
}

// Plugin activation hook
// Removed duplicate hook - already registered in class constructor

function ahgmh_activate_plugin() {
    // Ensure database tables are created
    $plugin = abschussplan_hgmh();
    $plugin->database->create_table();
    
    // Initialize default species and categories
    $default_species = array('Rotwild', 'Damwild');
    
    // Only set default species if none exist yet
    if (!get_option('ahgmh_species')) {
        update_option('ahgmh_species', $default_species);
    }
    
    // Set default categories for Rotwild
    $rotwild_categories_key = 'ahgmh_categories_rotwild';
    if (!get_option($rotwild_categories_key)) {
        $rotwild_categories = array(
            'Wildkalb (AK0)',
            'Schmaltier (AK 1)', 
            'Alttier (AK 2)',
            'Hirschkalb (AK 0)',
            'Schmalspießer (AK1)',
            'Junger Hirsch (AK 2)',
            'Mittelalter Hirsch (AK 3)',
            'Alter Hirsch (AK 4)'
        );
        update_option($rotwild_categories_key, $rotwild_categories);
    }
    
    // Set default categories for Damwild  
    $damwild_categories_key = 'ahgmh_categories_damwild';
    if (!get_option($damwild_categories_key)) {
        $damwild_categories = array(
            'Wildkalb (AK0)',
            'Schmaltier (AK 1)', 
            'Alttier (AK 2)',
            'Hirschkalb (AK 0)',
            'Schmalspießer (AK1)',
            'Junger Hirsch (AK 2)',
            'Mittelalter Hirsch (AK 3)',
            'Alter Hirsch (AK 4)'
        );
        update_option($damwild_categories_key, $damwild_categories);
    }
}

// Start the plugin
abschussplan_hgmh();
