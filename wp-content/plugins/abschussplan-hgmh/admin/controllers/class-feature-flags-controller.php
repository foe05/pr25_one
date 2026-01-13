<?php
/**
 * Feature Flags Controller
 *
 * Handles admin interface and AJAX for feature flags
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for managing feature flags in admin area
 */
class AHGMH_Feature_Flags_Controller {
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register AJAX handler (runs on every admin request)
        add_action('wp_ajax_hgmh_save_feature_flags', array($this, 'ajax_save_flags'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'abschussplan-hgmh',
            __('Feature Flags', 'abschussplan-hgmh'),
            '🚩 ' . __('Feature Flags', 'abschussplan-hgmh'),
            'manage_options',
            'hgmh-feature-flags',
            array($this, 'render_page')
        );
    }

    /**
     * Handle AJAX save request
     */
    public function ajax_save_flags() {
        check_ajax_referer('hgmh_feature_flags_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Keine Berechtigung'));
        }

        $flags = isset($_POST['flags']) ? $_POST['flags'] : array();

        foreach (HGMH_Feature_Flags::get_all_flags() as $flag_name => $flag_meta) {
            if (isset($flags[$flag_name])) {
                HGMH_Feature_Flags::enable($flag_name);
            } else {
                HGMH_Feature_Flags::disable($flag_name);
            }
        }

        wp_send_json_success(array('message' => 'Feature Flags gespeichert'));
    }

    /**
     * Render admin page
     */
    public function render_page() {
        require_once AHGMH_PLUGIN_DIR . 'admin/feature-flags.php';
    }
}
