<?php
/**
 * Wildart Controller
 *
 * Handles the Master-Detail UI for wildart (game species) configuration
 * including categories, meldegruppen, limits, and Obmann assignments.
 *
 * Test: Instantiate AHGMH_Wildart_Controller and verify all wp_ajax_*
 *       hooks are registered via has_action().
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages wildart CRUD and configuration through AJAX.
 */
class AHGMH_Wildart_Controller {

    /** @var AHGMH_Wildart_Service */
    private $wildart_service;

    /** @var AHGMH_Wildart_View */
    private $wildart_view;

    /**
     * Constructor -- wires service, view and AJAX handlers.
     */
    public function __construct() {
        $this->wildart_service = new AHGMH_Wildart_Service();
        $this->wildart_view    = new AHGMH_Wildart_View();

        $this->register_ajax_handlers();
    }

    /**
     * Register all AJAX handlers for wildart operations.
     */
    private function register_ajax_handlers() {
        add_action( 'wp_ajax_ahgmh_create_wildart', array( $this, 'ajax_create_wildart' ) );
        add_action( 'wp_ajax_ahgmh_delete_wildart', array( $this, 'ajax_delete_wildart' ) );
        add_action( 'wp_ajax_ahgmh_load_wildart_config', array( $this, 'ajax_load_wildart_config' ) );
        add_action( 'wp_ajax_ahgmh_save_wildart_categories', array( $this, 'ajax_save_wildart_categories' ) );
        add_action( 'wp_ajax_ahgmh_save_wildart_meldegruppen', array( $this, 'ajax_save_wildart_meldegruppen' ) );
        add_action( 'wp_ajax_ahgmh_toggle_limit_mode', array( $this, 'ajax_toggle_limit_mode' ) );
        add_action( 'wp_ajax_ahgmh_save_limits', array( $this, 'ajax_save_limits' ) );

        // Obmann assignment (Einrichtung tab — usermeta path via AHGMH_Permissions_Service)
        add_action( 'wp_ajax_ahgmh_assign_obmann',            array( $this, 'ajax_assign_obmann' ) );
        add_action( 'wp_ajax_ahgmh_remove_obmann',            array( $this, 'ajax_remove_obmann' ) );

        // Jagdbezirk ↔ Meldegruppe assignment (Einrichtung tab)
        add_action( 'wp_ajax_ahgmh_assign_jagdbezirk_meldegruppe', array( $this, 'ajax_assign_jagdbezirk_meldegruppe' ) );
        add_action( 'wp_ajax_ahgmh_save_meldegruppe_jagdbezirke', array( $this, 'ajax_save_meldegruppe_jagdbezirke' ) );

        // Keep DB tables in sync whenever Meldegruppen are saved via the Wildart-Config UI
        add_action( 'ahgmh_meldegruppen_updated', array( $this, 'on_meldegruppen_updated' ), 10, 2 );
    }

    /**
     * Sync DB Meldegruppen table when options-based config is saved.
     *
     * @param string $wildart     Wildart name
     * @param array  $meldegruppen Updated list of Meldegruppe names
     */
    public function on_meldegruppen_updated( $wildart, $meldegruppen ) {
        $repo = new AHGMH_Jagdbezirk_Repository();
        $repo->sync_meldegruppen_for_wildart( $wildart, $meldegruppen );
    }

    /**
     * AJAX: Create a new wildart.
     *
     * Test: POST ahgmh_create_wildart with name = 'Schwarzwild'.
     *       Expect success with species array containing the new name.
     *       Posting an empty name must return an error.
     */
    public function ajax_create_wildart() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $name = AHGMH_Validation_Service::validate_species_name( $_POST['name'] ?? '' );
            if ( ! $name ) {
                wp_send_json_error( __( 'Ungültiger Wildart-Name', 'abschussplan-hgmh' ) );
                return;
            }

            $result = $this->wildart_service->create_wildart( $name );
            wp_send_json_success( $result );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Erstellen: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Delete a wildart.
     *
     * Test: POST ahgmh_delete_wildart with wildart = 'Schwarzwild'.
     *       Expect success. Posting an empty wildart must return error.
     */
    public function ajax_delete_wildart() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart = sanitize_text_field( $_POST['wildart'] ?? '' );
            if ( empty( $wildart ) ) {
                wp_send_json_error( __( 'Wildart nicht angegeben', 'abschussplan-hgmh' ) );
                return;
            }

            $result = $this->wildart_service->delete_wildart( $wildart );
            wp_send_json_success( $result );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Löschen: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Load wildart configuration panel (HTML).
     *
     * Test: POST ahgmh_load_wildart_config with wildart = 'Rotwild'.
     *       Expect a success response containing HTML string.
     */
    public function ajax_load_wildart_config() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart = sanitize_text_field( $_POST['wildart'] ?? '' );
            if ( empty( $wildart ) ) {
                wp_send_json_error( __( 'Wildart nicht angegeben', 'abschussplan-hgmh' ) );
                return;
            }

            $config = $this->wildart_service->get_wildart_config( $wildart );
            $html   = $this->wildart_view->render_detail_panel( $wildart, $config );

            wp_send_json_success( $html );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Laden: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Save wildart categories.
     *
     * Test: POST ahgmh_save_wildart_categories with wildart = 'Rotwild'
     *       and categories = ['AK0', 'AK1']. Expect success.
     */
    public function ajax_save_wildart_categories() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart    = sanitize_text_field( $_POST['wildart'] ?? '' );
            $categories = AHGMH_Validation_Service::sanitize_text_array( $_POST['categories'] ?? array() );

            if ( empty( $wildart ) ) {
                wp_send_json_error( __( 'Wildart nicht angegeben', 'abschussplan-hgmh' ) );
                return;
            }

            $this->wildart_service->save_categories( $wildart, $categories );
            wp_send_json_success( array(
                'message' => __( 'Kategorien erfolgreich gespeichert', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Speichern: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Save wildart meldegruppen.
     *
     * Test: POST ahgmh_save_wildart_meldegruppen with wildart and a
     *       meldegruppen array. Expect success.
     */
    public function ajax_save_wildart_meldegruppen() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart      = sanitize_text_field( $_POST['wildart'] ?? '' );
            $meldegruppen = AHGMH_Validation_Service::sanitize_text_array( $_POST['meldegruppen'] ?? array() );

            if ( empty( $wildart ) ) {
                wp_send_json_error( __( 'Wildart nicht angegeben', 'abschussplan-hgmh' ) );
                return;
            }

            $this->wildart_service->save_meldegruppen( $wildart, $meldegruppen );
            wp_send_json_success( array(
                'message' => __( 'Meldegruppen erfolgreich gespeichert', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Speichern: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Toggle limit mode between per-meldegruppe and total.
     *
     * Test: POST ahgmh_toggle_limit_mode with wildart and mode. Invalid
     *       mode values must return an error.
     */
    public function ajax_toggle_limit_mode() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart = sanitize_text_field( $_POST['wildart'] ?? '' );
            $mode    = sanitize_text_field( $_POST['mode'] ?? '' );

            if ( empty( $wildart ) || ! in_array( $mode, array( 'meldegruppen_specific', 'hegegemeinschaft_total' ), true ) ) {
                wp_send_json_error( __( 'Ungültige Parameter', 'abschussplan-hgmh' ) );
                return;
            }

            $this->wildart_service->set_limit_mode( $wildart, $mode );
            wp_send_json_success( array(
                'message' => __( 'Limit-Modus erfolgreich geändert', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Ändern des Modus: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Save hunting limits.
     *
     * Test: POST ahgmh_save_limits with wildart and a limits array.
     *       Expect success.
     */
    public function ajax_save_limits() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart = sanitize_text_field( $_POST['wildart'] ?? '' );
            $limits  = $_POST['limits'] ?? array();

            if ( empty( $wildart ) ) {
                wp_send_json_error( __( 'Wildart nicht angegeben', 'abschussplan-hgmh' ) );
                return;
            }

            $validated = AHGMH_Validation_Service::validate_wildart_data( array( 'limits' => $limits ) );
            $this->wildart_service->save_limits( $wildart, $validated['limits'] );

            wp_send_json_success( array(
                'message' => __( 'Limits erfolgreich gespeichert', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Speichern der Limits: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Assign Obmann to a meldegruppe (Einrichtung tab).
     * Uses the canonical usermeta path via AHGMH_Permissions_Service.
     *
     * POST: wildart, meldegruppe, user_id
     */
    public function ajax_assign_obmann() {
        AHGMH_Validation_Service::verify_ajax_request();

        $wildart     = sanitize_text_field( $_POST['wildart'] ?? '' );
        $meldegruppe = sanitize_text_field( $_POST['meldegruppe'] ?? '' );
        $user_id     = absint( $_POST['user_id'] ?? 0 );

        if ( empty( $wildart ) || empty( $meldegruppe ) || $user_id <= 0 ) {
            wp_send_json_error( __( 'Ungültige Parameter', 'abschussplan-hgmh' ) );
            return;
        }

        $user = get_user_by( 'ID', $user_id );
        if ( ! $user ) {
            wp_send_json_error( __( 'Benutzer nicht gefunden', 'abschussplan-hgmh' ) );
            return;
        }

        if ( AHGMH_Permissions_Service::assign_user_to_meldegruppe( $user_id, $wildart, $meldegruppe ) ) {
            wp_send_json_success( [
                'message'      => sprintf(
                    __( '%s erfolgreich als Obmann für %s (%s) zugewiesen', 'abschussplan-hgmh' ),
                    $user->display_name, $meldegruppe, $wildart
                ),
                'user_id'      => $user_id,
                'display_name' => $user->display_name,
                'meldegruppe'  => $meldegruppe,
            ] );
        } else {
            wp_send_json_error( __( 'Fehler beim Zuweisen des Obmanns', 'abschussplan-hgmh' ) );
        }
    }

    /**
     * AJAX: Remove an Obmann assignment (Einrichtung tab).
     * Uses the canonical usermeta path via AHGMH_Permissions_Service.
     *
     * POST: wildart, user_id
     */
    public function ajax_remove_obmann() {
        AHGMH_Validation_Service::verify_ajax_request();

        $wildart = sanitize_text_field( $_POST['wildart'] ?? '' );
        $user_id = absint( $_POST['user_id'] ?? 0 );

        if ( empty( $wildart ) || $user_id <= 0 ) {
            wp_send_json_error( __( 'Ungültige Parameter', 'abschussplan-hgmh' ) );
            return;
        }

        if ( AHGMH_Permissions_Service::remove_user_assignment( $user_id, $wildart ) ) {
            wp_send_json_success( [
                'message' => __( 'Obmann-Zuweisung erfolgreich entfernt', 'abschussplan-hgmh' ),
                'user_id' => $user_id,
            ] );
        } else {
            wp_send_json_error( __( 'Fehler beim Entfernen der Obmann-Zuweisung', 'abschussplan-hgmh' ) );
        }
    }

    /**
     * AJAX: Assign a Jagdbezirk to one or more Meldegruppen (Einrichtung tab).
     *
     * POST: jagdbezirk_id (int), meldegruppe_ids (int[])
     */
    public function ajax_assign_jagdbezirk_meldegruppe() {
        AHGMH_Validation_Service::verify_ajax_request();

        $jagdbezirk_id  = absint( $_POST['jagdbezirk_id'] ?? 0 );
        $meldegruppe_ids = isset( $_POST['meldegruppe_ids'] ) ? array_map( 'absint', (array) $_POST['meldegruppe_ids'] ) : [];

        if ( $jagdbezirk_id <= 0 ) {
            wp_send_json_error( __( 'Ungültige Jagdbezirk-ID', 'abschussplan-hgmh' ) );
            return;
        }

        $repo   = new AHGMH_Jagdbezirk_Repository();
        $result = $repo->assign_meldegruppen( $jagdbezirk_id, $meldegruppe_ids );

        if ( $result ) {
            wp_send_json_success( [
                'message'        => __( 'Jagdbezirk-Zuweisung erfolgreich gespeichert', 'abschussplan-hgmh' ),
                'jagdbezirk_id'  => $jagdbezirk_id,
                'meldegruppe_ids' => $meldegruppe_ids,
            ] );
        } else {
            wp_send_json_error( __( 'Fehler beim Speichern der Jagdbezirk-Zuweisung', 'abschussplan-hgmh' ) );
        }
    }

    /**
     * AJAX: Save which Jagdbezirke belong to a specific Meldegruppe (Einrichtung tab).
     *
     * POST: meldegruppe_id (int), jagdbezirk_ids (int[])
     */
    public function ajax_save_meldegruppe_jagdbezirke() {
        AHGMH_Validation_Service::verify_ajax_request();

        $meldegruppe_id  = absint( $_POST['meldegruppe_id'] ?? 0 );
        $jagdbezirk_ids  = isset( $_POST['jagdbezirk_ids'] ) ? array_map( 'absint', (array) $_POST['jagdbezirk_ids'] ) : [];

        if ( $meldegruppe_id <= 0 ) {
            wp_send_json_error( __( 'Ungültige Meldegruppe-ID', 'abschussplan-hgmh' ) );
            return;
        }

        $repo   = new AHGMH_Jagdbezirk_Repository();
        $result = $repo->save_jagdbezirke_for_meldegruppe( $meldegruppe_id, $jagdbezirk_ids );

        if ( $result ) {
            wp_send_json_success( [
                'message'         => __( 'Jagdbezirke erfolgreich gespeichert', 'abschussplan-hgmh' ),
                'meldegruppe_id'  => $meldegruppe_id,
                'jagdbezirk_ids' => $jagdbezirk_ids,
            ] );
        } else {
            wp_send_json_error( __( 'Fehler beim Speichern der Jagdbezirke', 'abschussplan-hgmh' ) );
        }
    }

    /**
     * AJAX: Get Obmann assignments (legacy stub, kept for backwards compat).
     *
     * POST: optional wildart
     */
    public function ajax_get_obmann_assignments() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart     = isset( $_POST['wildart'] ) ? sanitize_text_field( $_POST['wildart'] ) : null;
            $assignments = $this->wildart_service->get_obmann_assignments( $wildart );

            wp_send_json_success( array( 'assignments' => $assignments ) );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Laden der Zuweisungen: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Save the display order of wildarten.
     *
     * Test: POST with order = ['Damwild', 'Rotwild']. Expect success.
     */
    public function ajax_save_wildart_order() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $order = isset( $_POST['order'] ) ? $_POST['order'] : array();

            if ( ! is_array( $order ) ) {
                wp_send_json_error( __( 'Ungültige Reihenfolge-Daten', 'abschussplan-hgmh' ) );
                return;
            }

            $order  = array_map( 'sanitize_text_field', $order );
            $this->wildart_service->save_wildart_order( $order );

            wp_send_json_success( array(
                'message' => __( 'Reihenfolge erfolgreich gespeichert', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error(
                __( 'Fehler beim Speichern: ', 'abschussplan-hgmh' ) . esc_html( $e->getMessage() )
            );
        }
    }

    /**
     * AJAX: Get meldegruppen for a specific wildart.
     *
     * Test: POST with wildart = 'Rotwild'. Expect meldegruppen array.
     */
    public function ajax_get_meldegruppen_for_wildart() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $wildart = sanitize_text_field( $_POST['wildart'] ?? '' );
            if ( empty( $wildart ) ) {
                wp_send_json_error( __( 'Wildart erforderlich', 'abschussplan-hgmh' ) );
                return;
            }

            $meldegruppen = $this->wildart_service->get_meldegruppen_for_wildart( $wildart );
            wp_send_json_success( array( 'meldegruppen' => $meldegruppen ) );
        } catch ( Exception $e ) {
            wp_send_json_error( esc_html( $e->getMessage() ) );
        }
    }

    /**
     * AJAX: Reset all Obmann assignments.
     *
     * Test: POST ahgmh_reset_all_assignments. Expect success.
     */
    public function ajax_reset_all_assignments() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            $this->wildart_service->reset_all_obmann_assignments();
            wp_send_json_success( array(
                'message' => __( 'Alle Zuweisungen zurückgesetzt', 'abschussplan-hgmh' ),
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( esc_html( $e->getMessage() ) );
        }
    }

    /**
     * Render wildart configuration section.
     *
     * @return string Rendered HTML.
     */
    public function render_config_section() {
        $wildarten = $this->wildart_service->get_all_wildarten();
        return $this->wildart_view->render_master_detail_ui( $wildarten );
    }
}
