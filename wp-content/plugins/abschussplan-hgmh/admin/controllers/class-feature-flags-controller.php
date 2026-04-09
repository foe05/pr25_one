<?php
/**
 * Feature Flags Controller
 *
 * Handles the admin interface and AJAX for toggling feature flags.
 *
 * Test: Navigate to wp-admin > Abschussplan > Feature Flags. Toggle a
 *       flag and verify the page shows a success message.
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin interface for managing feature flags.
 */
class AHGMH_Feature_Flags_Controller {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'wp_ajax_hgmh_save_feature_flags', array( $this, 'ajax_save_flags' ) );
    }

    /**
     * Add a sub-menu page for feature flags.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'abschussplan-hgmh',
            __( 'Feature Flags', 'abschussplan-hgmh' ),
            __( 'Feature Flags', 'abschussplan-hgmh' ),
            'manage_options',
            'hgmh-feature-flags',
            array( $this, 'render_page' )
        );
    }

    /**
     * AJAX: Persist feature-flag toggles.
     *
     * Test: POST hgmh_save_feature_flags with flags[use_moderation] = 'on'.
     *       Expect success and the flag to be enabled.
     */
    public function ajax_save_flags() {
        check_ajax_referer( 'hgmh_feature_flags_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Keine Berechtigung', 'abschussplan-hgmh' ),
            ) );
        }

        $flags = isset( $_POST['flags'] ) ? $_POST['flags'] : array();

        foreach ( HGMH_Feature_Flags::get_all_flags() as $flag_name => $flag_meta ) {
            if ( isset( $flags[ $flag_name ] ) ) {
                HGMH_Feature_Flags::enable( $flag_name );
            } else {
                HGMH_Feature_Flags::disable( $flag_name );
            }
        }

        wp_send_json_success( array(
            'message' => __( 'Feature Flags gespeichert', 'abschussplan-hgmh' ),
        ) );
    }

    /**
     * Render the feature-flags admin page.
     */
    public function render_page() {
        require_once AHGMH_PLUGIN_DIR . 'admin/feature-flags.php';
    }
}
