<?php
/**
 * Compliance Controller
 * Handles compliance dashboard display and AJAX operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compliance Controller Class
 */
class AHGMH_Compliance_Controller {

    private $report_service;
    private $view;

    /**
     * Constructor
     */
    public function __construct() {
        // Load report service
        require_once AHGMH_PLUGIN_DIR . 'admin/services/class-report-service.php';
        $this->report_service = new AHGMH_Report_Service();

        // Load view
        require_once AHGMH_PLUGIN_DIR . 'admin/views/class-compliance-view.php';
        $this->view = new AHGMH_Compliance_View();

        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_compliance_refresh', array($this, 'ajax_compliance_refresh'));
        add_action('wp_ajax_ahgmh_compliance_filter', array($this, 'ajax_compliance_filter'));
    }

    /**
     * Render compliance dashboard page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get initial compliance data
        $compliance_data = $this->get_compliance_data();

        // Get filter options
        $species_list = get_option('ahgmh_species', ['Rotwild', 'Damwild']);
        $meldegruppen = get_option('ahgmh_meldegruppen', []);

        // Render the page using the view
        $this->view->render_dashboard($compliance_data, $species_list, $meldegruppen);
    }

    /**
     * Get compliance data
     *
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Compliance data
     */
    private function get_compliance_data($species = '', $meldegruppe = '') {
        // Get overall compliance data
        $compliance = $this->report_service->get_compliance_data($species, $meldegruppe);

        // Get compliance by meldegruppe for detailed breakdown
        $by_meldegruppe = $this->report_service->get_compliance_by_meldegruppe($species);

        // Get compliance summary
        $summary = $this->report_service->get_compliance_summary();

        return [
            'compliance' => $compliance,
            'by_meldegruppe' => $by_meldegruppe,
            'summary' => $summary,
            'filters' => [
                'species' => $species,
                'meldegruppe' => $meldegruppe
            ]
        ];
    }

    /**
     * AJAX handler for compliance refresh
     */
    public function ajax_compliance_refresh() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Clear cache to get fresh data
            $this->report_service->clear_cache();

            // Get compliance data
            $compliance_data = $this->get_compliance_data();

            wp_send_json_success([
                'data' => $compliance_data,
                'message' => __('Compliance-Daten aktualisiert', 'abschussplan-hgmh')
            ]);
        } catch (Exception $e) {
            error_log('AHGMH Compliance Refresh Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Aktualisieren der Compliance-Daten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX handler for compliance filter
     */
    public function ajax_compliance_filter() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get and sanitize filter parameters
            $species = isset($_POST['species']) ? sanitize_text_field($_POST['species']) : '';
            $meldegruppe = isset($_POST['meldegruppe']) ? sanitize_text_field($_POST['meldegruppe']) : '';

            // Get filtered compliance data
            $compliance_data = $this->get_compliance_data($species, $meldegruppe);

            wp_send_json_success([
                'data' => $compliance_data,
                'message' => __('Filter angewendet', 'abschussplan-hgmh')
            ]);
        } catch (Exception $e) {
            error_log('AHGMH Compliance Filter Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Filtern der Compliance-Daten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
        }
    }

}
