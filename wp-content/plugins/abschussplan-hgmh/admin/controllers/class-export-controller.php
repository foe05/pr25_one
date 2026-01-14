<?php
/**
 * Export Controller
 * Zentrale Steuerung für alle Export-Operationen
 *
 * Bug #2 Fix: Verwendet zentrale Export-Service-Klasse
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export Controller Class
 * Koordiniert alle Export-bezogenen AJAX-Anfragen
 */
class AHGMH_Export_Controller {

    /**
     * Export Service Instanz
     */
    private $export_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->export_service = new AHGMH_Export_Service();
        $this->register_ajax_handlers();
    }

    /**
     * AJAX-Handler registrieren
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_quick_export', array($this, 'ajax_quick_export'));
        add_action('wp_ajax_ahgmh_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_ahgmh_export_filtered', array($this, 'ajax_export_filtered'));
        add_action('wp_ajax_ahgmh_get_export_options', array($this, 'ajax_get_export_options'));
    }

    /**
     * AJAX: Schnell-Export (für Quick-Export-Button)
     */
    public function ajax_quick_export() {
        // Nonce-Prüfung
        if (!check_ajax_referer('ahgmh_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Sicherheitsprüfung fehlgeschlagen', 'abschussplan-hgmh'));
            return;
        }

        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Keine Berechtigung für diese Aktion', 'abschussplan-hgmh'));
            return;
        }

        try {
            $species = sanitize_text_field($_POST['species'] ?? '');
            $format = sanitize_text_field($_POST['format'] ?? 'csv');

            // Zentrale Export-Service verwenden
            $result = $this->export_service->export_submissions(array(
                'wildart' => $species,
                'format' => $format
            ));

            wp_send_json_success($result);

        } catch (Exception $e) {
            error_log('AHGMH Export Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Export: ', 'abschussplan-hgmh') . esc_html($e->getMessage()));
        }
    }

    /**
     * AJAX: Export mit Filtern
     */
    public function ajax_export_data() {
        // Nonce-Prüfung
        if (!check_ajax_referer('ahgmh_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Sicherheitsprüfung fehlgeschlagen', 'abschussplan-hgmh'));
            return;
        }

        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Keine Berechtigung für diese Aktion', 'abschussplan-hgmh'));
            return;
        }

        try {
            // Filter aus POST-Daten extrahieren
            $filters = array(
                'wildart' => sanitize_text_field($_POST['species'] ?? $_POST['wildart'] ?? ''),
                'meldegruppe' => sanitize_text_field($_POST['meldegruppe'] ?? ''),
                'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
                'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
                'status' => sanitize_text_field($_POST['status'] ?? ''),
                'format' => sanitize_text_field($_POST['format'] ?? 'csv')
            );

            // Zentrale Export-Service verwenden
            $result = $this->export_service->export_submissions($filters);

            if ($result['records'] === 0) {
                wp_send_json_error(__('Keine Daten zum Exportieren gefunden', 'abschussplan-hgmh'));
                return;
            }

            wp_send_json_success($result);

        } catch (Exception $e) {
            error_log('AHGMH Export Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Export: ', 'abschussplan-hgmh') . esc_html($e->getMessage()));
        }
    }

    /**
     * AJAX: Gefilterter Export (erweiterter Export)
     */
    public function ajax_export_filtered() {
        // Nonce-Prüfung
        if (!check_ajax_referer('ahgmh_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Sicherheitsprüfung fehlgeschlagen', 'abschussplan-hgmh'));
            return;
        }

        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Keine Berechtigung für diese Aktion', 'abschussplan-hgmh'));
            return;
        }

        try {
            // Erweiterte Filter
            $filters = array(
                'wildart' => sanitize_text_field($_POST['wildart'] ?? ''),
                'meldegruppe' => sanitize_text_field($_POST['meldegruppe'] ?? ''),
                'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
                'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
                'status' => sanitize_text_field($_POST['status'] ?? ''),
                'format' => sanitize_text_field($_POST['format'] ?? 'csv')
            );

            // Zentrale Export-Service verwenden
            $result = $this->export_service->export_submissions($filters);

            wp_send_json_success($result);

        } catch (Exception $e) {
            error_log('AHGMH Export Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Export: ', 'abschussplan-hgmh') . esc_html($e->getMessage()));
        }
    }

    /**
     * AJAX: Export-Optionen abrufen (für Filter-Dropdowns)
     */
    public function ajax_get_export_options() {
        // Nonce-Prüfung
        if (!check_ajax_referer('ahgmh_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Sicherheitsprüfung fehlgeschlagen', 'abschussplan-hgmh'));
            return;
        }

        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Keine Berechtigung für diese Aktion', 'abschussplan-hgmh'));
            return;
        }

        try {
            $options = array(
                'wildarten' => $this->export_service->get_available_wildarten(),
                'meldegruppen' => $this->export_service->get_available_meldegruppen(),
                'statuses' => $this->export_service->get_available_statuses()
            );

            wp_send_json_success($options);

        } catch (Exception $e) {
            error_log('AHGMH Export Options Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Laden der Optionen', 'abschussplan-hgmh'));
        }
    }

    /**
     * URL-basierter Export (für direkte Links)
     * Wird über admin_init aufgerufen
     */
    public static function handle_url_export() {
        if (!isset($_GET['ahgmh_export']) || $_GET['ahgmh_export'] !== '1') {
            return;
        }

        // Nonce-Prüfung
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'ahgmh_url_export')) {
            wp_die(__('Sicherheitsprüfung fehlgeschlagen', 'abschussplan-hgmh'));
        }

        // Berechtigungsprüfung
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung für diese Aktion', 'abschussplan-hgmh'));
        }

        try {
            $export_service = new AHGMH_Export_Service();

            $filters = array(
                'wildart' => sanitize_text_field($_GET['wildart'] ?? ''),
                'meldegruppe' => sanitize_text_field($_GET['meldegruppe'] ?? ''),
                'date_from' => sanitize_text_field($_GET['date_from'] ?? ''),
                'date_to' => sanitize_text_field($_GET['date_to'] ?? ''),
                'status' => sanitize_text_field($_GET['status'] ?? ''),
                'format' => sanitize_text_field($_GET['format'] ?? 'csv')
            );

            $result = $export_service->export_submissions($filters);

            // Direkter Download
            wp_redirect($result['download_url']);
            exit;

        } catch (Exception $e) {
            wp_die(__('Export fehlgeschlagen: ', 'abschussplan-hgmh') . esc_html($e->getMessage()));
        }
    }

    /**
     * Export-URL generieren
     *
     * @param array $filters Filter-Parameter
     * @return string Export-URL
     */
    public static function get_export_url($filters = array()) {
        $base_url = admin_url('admin.php');

        $params = array_merge(array(
            'ahgmh_export' => '1',
            'nonce' => wp_create_nonce('ahgmh_url_export')
        ), $filters);

        return add_query_arg($params, $base_url);
    }
}

// URL-Export-Handler registrieren
add_action('admin_init', array('AHGMH_Export_Controller', 'handle_url_export'));
