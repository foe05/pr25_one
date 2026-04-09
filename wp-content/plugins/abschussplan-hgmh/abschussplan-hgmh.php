<?php
/**
 * Plugin Name: Abschussplan HGMH
 * Plugin URI: https://github.com/foe05/pr25_one
 * Description: Collect and view game shoots for registration with local hunting authorities in Germany. Version 3.0: Complete architectural refactoring with enterprise features including moderation workflow, email verification, activity logging, and migration manager.
 * Version: 26.1.0
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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'AHGMH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AHGMH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AHGMH_PLUGIN_VERSION', '26.1.0' );
define( 'AHGMH_DB_VERSION', '10' );

/*
|--------------------------------------------------------------------------
| Shared includes (front-end + admin)
|--------------------------------------------------------------------------
*/
require_once AHGMH_PLUGIN_DIR . 'includes/class-database-handler.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-migration-manager.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-form-handler.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-table-display.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-permissions-service.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-page-view-logger.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-feature-flags.php';

// Moderation services.
require_once AHGMH_PLUGIN_DIR . 'includes/services/class-submission-repository.php';
require_once AHGMH_PLUGIN_DIR . 'includes/services/class-email-service.php';
require_once AHGMH_PLUGIN_DIR . 'includes/services/class-moderation-service.php';

// Frontend shortcodes.
require_once AHGMH_PLUGIN_DIR . 'frontend/shortcodes/class-table-shortcode.php';

// Public form and verification services.
require_once AHGMH_PLUGIN_DIR . 'includes/class-verification-service.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-rate-limiter.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-public-form-handler.php';
require_once AHGMH_PLUGIN_DIR . 'includes/class-activity-logger.php';
require_once AHGMH_PLUGIN_DIR . 'includes/logging.php';

/*
|--------------------------------------------------------------------------
| Admin-only includes
|--------------------------------------------------------------------------
*/
if ( is_admin() ) {
    // Repositories (required by services).
    require_once AHGMH_PLUGIN_DIR . 'includes/repositories/class-wildart-repository.php';
    require_once AHGMH_PLUGIN_DIR . 'includes/repositories/class-meldegruppe-repository.php';
    require_once AHGMH_PLUGIN_DIR . 'includes/repositories/class-jagdbezirk-repository.php';

    // Admin services.
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-validation-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-dashboard-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-wildart-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-jagdbezirk-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-export-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-limits-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-moderation-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-import-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-column-mapper.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-ljv-template-detector.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-import-validator.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-bulk-operations-service.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/services/class-undo-service.php';

    // Admin views.
    require_once AHGMH_PLUGIN_DIR . 'admin/views/class-dashboard-view.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/views/class-wildart-view.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/views/class-import-view.php';

    // Admin controllers.
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-dashboard-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-data-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-settings-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-wildart-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-jagdbezirk-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-export-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-limits-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-page-views-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-feature-flags-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-import-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-bulk-operations-controller.php';
    require_once AHGMH_PLUGIN_DIR . 'admin/controllers/class-undo-controller.php';

    // Legacy admin page (monolith -- provides menus and page rendering).
    require_once AHGMH_PLUGIN_DIR . 'admin/class-admin-page-modern.php';
}

/*
|--------------------------------------------------------------------------
| Utility function: status badge (used in limit tables)
|--------------------------------------------------------------------------
|
| Returns an HTML badge indicating fulfilment against a quota.
|
| Test: Call ahgmh_status_badge( 45, 50 ) -- expect a green badge.
|       Call ahgmh_status_badge( 95, 50 ) -- expect a red/exceeded badge.
*/
if ( ! function_exists( 'ahgmh_get_status_badge_fallback' ) ) {
    /**
     * Render a coloured status badge comparing current vs. limit.
     *
     * @param int $current Current count.
     * @param int $limit   Allowed limit.
     * @return string HTML badge markup.
     */
    function ahgmh_get_status_badge_fallback( $current, $limit ) {
        if ( 0 === (int) $limit ) {
            return '<span class="status-badge status-unknown">-</span>';
        }

        $percentage = ( $current / $limit ) * 100;

        if ( $percentage >= 110 ) {
            return '<span class="status-badge status-exceeded">' . esc_html( round( $percentage, 1 ) . '%' ) . '</span>';
        } elseif ( $percentage >= 95 ) {
            return '<span class="status-badge status-critical">' . esc_html( round( $percentage, 1 ) . '%' ) . '</span>';
        } elseif ( $percentage >= 80 ) {
            return '<span class="status-badge status-warning">' . esc_html( round( $percentage, 1 ) . '%' ) . '</span>';
        }

        return '<span class="status-badge status-good">' . esc_html( round( $percentage, 1 ) . '%' ) . '</span>';
    }
}

/*
|--------------------------------------------------------------------------
| Main plugin class
|--------------------------------------------------------------------------
*/

/**
 * Main plugin class -- singleton.
 *
 * Bootstraps all plugin components, registers activation/deactivation hooks,
 * and enqueues front-end assets.
 *
 * Test: After activation the database tables listed in
 *       AHGMH_Database_Handler::create_table() must exist.
 *       Deactivation must clear scheduled cron events.
 */
class Abschussplan_HGMH {

    /** @var self|null Singleton instance. */
    private static $instance = null;

    /** @var AHGMH_Database_Handler Database handler. */
    public $database;

    /** @var AHGMH_Form_Handler Form handler. */
    public $form;

    /** @var AHGMH_Table_Display Table display. */
    public $table;

    /** @var AHGMH_Admin_Page_Modern|null Legacy admin page (admin only). */
    public $admin;

    /** @var AHGMH_Public_Form_Handler Public form handler. */
    public $public_form;

    /** @var AHGMH_Verification_Service Verification service. */
    public $verification_service;

    /**
     * Return the singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor -- private to enforce singleton.
     */
    private function __construct() {
        $this->init();

        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Initialise all plugin components.
     *
     * Test: Instantiate the plugin and verify that $this->database,
     *       $this->form, $this->table, $this->public_form, and
     *       $this->verification_service are set.
     */
    private function init() {
        $this->database             = new AHGMH_Database_Handler();
        $this->form                 = new AHGMH_Form_Handler();
        $this->table                = new AHGMH_Table_Display();
        $this->public_form          = new AHGMH_Public_Form_Handler();
        $this->verification_service = new AHGMH_Verification_Service();

        // Frontend table shortcode (self-registers).
        new AHGMH_Table_Shortcode();

        // Database upgrade check.
        add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_db' ) );

        if ( is_admin() ) {
            $this->init_admin();
        }
    }

    /**
     * Initialise admin-only controllers.
     *
     * Each controller self-registers its own AJAX handlers in the
     * constructor, so simple instantiation is sufficient.
     *
     * Test: In an admin context each controller must register its
     *       wp_ajax_* hooks. Verify with has_action().
     */
    private function init_admin() {
        // Legacy admin page (menus + page renders).
        $this->admin = new AHGMH_Admin_Page_Modern();

        // Modular controllers -- each registers its own AJAX handlers.
        new AHGMH_Page_Views_Controller();
        new AHGMH_Feature_Flags_Controller();
        new AHGMH_Jagdbezirk_Controller();
        new AHGMH_Export_Controller();
        new AHGMH_Import_Controller();
        new AHGMH_Bulk_Operations_Controller();
        new AHGMH_Undo_Controller();
        new AHGMH_Wildart_Controller();
        new AHGMH_Limits_Controller();
    }

    /**
     * Plugin activation callback.
     *
     * Creates all database tables and flushes rewrite rules.
     */
    public function activate_plugin() {
        $this->database->create_table();
        $this->seed_default_options();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation callback.
     */
    public function deactivate_plugin() {
        flush_rewrite_rules();
    }

    /**
     * Enqueue front-end scripts and styles.
     *
     * Test: On a page containing one of the plugin shortcodes the
     *       ahgmh-style and ahgmh-form-validation handles must be
     *       enqueued.
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );

        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
            array(),
            '1.11.0'
        );

        wp_enqueue_script( 'jquery' );

        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array( 'jquery' ),
            '5.3.0',
            true
        );

        wp_enqueue_style(
            'ahgmh-style',
            AHGMH_PLUGIN_URL . 'assets/css/style.css',
            array( 'bootstrap-css' ),
            AHGMH_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ahgmh-form-validation',
            AHGMH_PLUGIN_URL . 'assets/js/form-validation.js',
            array( 'jquery' ),
            AHGMH_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'ahgmh-form-validation',
            'ahgmh_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ahgmh_form_nonce' ),
            )
        );
    }

    /**
     * Check whether the DB schema needs updating and run create_table()
     * if the stored version differs from AHGMH_DB_VERSION.
     *
     * Test: Set option ahgmh_db_version to '0', load the plugin, and
     *       verify that it gets updated to AHGMH_DB_VERSION.
     */
    public function maybe_upgrade_db() {
        $installed = get_option( 'ahgmh_db_version' );

        if ( $installed !== AHGMH_DB_VERSION ) {
            $this->database->create_table();
            update_option( 'ahgmh_db_version', AHGMH_DB_VERSION );
        }
    }

    /**
     * Seed default options on first activation.
     *
     * Only writes options that do not already exist so repeated
     * activations are safe.
     *
     * Test: Activate the plugin on a fresh install and verify that
     *       ahgmh_species contains ['Rotwild', 'Damwild'] and the
     *       corresponding category options exist.
     */
    private function seed_default_options() {
        $default_species = array( 'Rotwild', 'Damwild' );

        if ( ! get_option( 'ahgmh_species' ) ) {
            update_option( 'ahgmh_species', $default_species );
        }

        $default_categories = array(
            'Wildkalb (AK0)',
            'Schmaltier (AK 1)',
            'Alttier (AK 2)',
            'Hirschkalb (AK 0)',
            'Schmalspießer (AK1)',
            'Junger Hirsch (AK 2)',
            'Mittelalter Hirsch (AK 3)',
            'Alter Hirsch (AK 4)',
        );

        foreach ( $default_species as $species ) {
            $key = 'ahgmh_categories_' . sanitize_key( $species );
            if ( ! get_option( $key ) ) {
                update_option( $key, $default_categories );
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Global accessor function
|--------------------------------------------------------------------------
*/

/**
 * Return the main plugin singleton.
 *
 * @return Abschussplan_HGMH
 */
function abschussplan_hgmh() {
    return Abschussplan_HGMH::get_instance();
}

/*
|--------------------------------------------------------------------------
| Cron: page-views cleanup
|--------------------------------------------------------------------------
|
| Test: Enable ahgmh_auto_cleanup_enabled, wait for the daily cron to
|       fire, then verify that page-view rows older than the configured
|       number of days have been removed.
*/
add_action( 'ahgmh_page_views_cleanup_hook', 'ahgmh_page_views_cleanup_cron' );
/**
 * Cron callback -- remove old page-view log entries.
 */
function ahgmh_page_views_cleanup_cron() {
    if ( ! get_option( 'ahgmh_auto_cleanup_enabled', false ) ) {
        return;
    }

    $days   = (int) get_option( 'ahgmh_auto_cleanup_days', 90 );
    $logger = new AHGMH_Page_View_Logger();
    $logger->cleanup_old_logs( $days );
}

add_action( 'wp', 'ahgmh_schedule_page_views_cleanup' );
/**
 * Ensure the daily cleanup cron is scheduled.
 */
function ahgmh_schedule_page_views_cleanup() {
    if ( ! wp_next_scheduled( 'ahgmh_page_views_cleanup_hook' ) ) {
        wp_schedule_event( time(), 'daily', 'ahgmh_page_views_cleanup_hook' );
    }
}

register_deactivation_hook( __FILE__, 'ahgmh_clear_page_views_cleanup' );
/**
 * Remove the page-views cleanup cron on deactivation.
 */
function ahgmh_clear_page_views_cleanup() {
    $timestamp = wp_next_scheduled( 'ahgmh_page_views_cleanup_hook' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'ahgmh_page_views_cleanup_hook' );
    }
}

/*
|--------------------------------------------------------------------------
| Cron: activity-log cleanup
|--------------------------------------------------------------------------
|
| Test: Wait for the daily cron to fire and verify that activity-log rows
|       older than 90 days have been removed.
*/
add_action( 'ahgmh_activity_log_cleanup_hook', 'ahgmh_activity_log_cleanup_cron' );
/**
 * Cron callback -- remove old activity-log entries.
 */
function ahgmh_activity_log_cleanup_cron() {
    $logger = new AHGMH_Activity_Logger();
    $logger->cleanup_old_logs( 90 );
}

add_action( 'wp', 'ahgmh_schedule_activity_log_cleanup' );
/**
 * Ensure the daily activity-log cleanup cron is scheduled.
 */
function ahgmh_schedule_activity_log_cleanup() {
    if ( ! wp_next_scheduled( 'ahgmh_activity_log_cleanup_hook' ) ) {
        wp_schedule_event( time(), 'daily', 'ahgmh_activity_log_cleanup_hook' );
    }
}

register_deactivation_hook( __FILE__, 'ahgmh_clear_activity_log_cleanup' );
/**
 * Remove the activity-log cleanup cron on deactivation.
 */
function ahgmh_clear_activity_log_cleanup() {
    $timestamp = wp_next_scheduled( 'ahgmh_activity_log_cleanup_hook' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'ahgmh_activity_log_cleanup_hook' );
    }
}

/*
|--------------------------------------------------------------------------
| Boot
|--------------------------------------------------------------------------
*/
abschussplan_hgmh();
