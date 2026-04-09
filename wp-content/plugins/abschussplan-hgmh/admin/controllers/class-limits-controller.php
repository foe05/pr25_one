<?php
/**
 * Limits Controller -- manages hunting-quota limits.
 *
 * Test: Instantiate AHGMH_Limits_Controller and verify
 *       wp_ajax_ahgmh_save_meldegruppe_limits and
 *       wp_ajax_ahgmh_toggle_meldegruppe_custom_limits are registered.
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controller for saving / toggling hunting limits via AJAX.
 */
class AHGMH_Limits_Controller {

    /** @var AHGMH_Limits_Service */
    private $limits_service;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->limits_service = new AHGMH_Limits_Service();
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers.
     */
    private function register_ajax_handlers() {
        add_action( 'wp_ajax_ahgmh_save_meldegruppe_limits', array( $this, 'ajax_save_limits' ) );
        add_action( 'wp_ajax_ahgmh_toggle_meldegruppe_custom_limits', array( $this, 'ajax_toggle_custom_limits' ) );
    }

    /**
     * AJAX: Save meldegruppe limits.
     *
     * Test: POST ahgmh_save_meldegruppe_limits with a limits array.
     *       Expect success. An invalid payload should return an error.
     */
    public function ajax_save_limits() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $limits = $_POST['limits'] ?? array();
            $this->limits_service->save_limits( $limits );
            wp_send_json_success( array(
                'message' => __( 'Limits erfolgreich gespeichert', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( esc_html( $e->getMessage() ) );
        }
    }

    /**
     * AJAX: Toggle custom limits per meldegruppe.
     *
     * Test: POST ahgmh_toggle_meldegruppe_custom_limits with enabled=true.
     *       Expect success.
     */
    public function ajax_toggle_custom_limits() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $enabled = ! empty( $_POST['enabled'] );
            $this->limits_service->toggle_custom_limits( $enabled );
            wp_send_json_success( array(
                'message' => __( 'Meldegruppen-Limits Modus geändert', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( esc_html( $e->getMessage() ) );
        }
    }
}
