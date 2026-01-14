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
     * @param string $season Optional season filter (format: YYYY-YYYY)
     * @return array Compliance data
     */
    private function get_compliance_data($species = '', $meldegruppe = '', $season = '') {
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
                'meldegruppe' => $meldegruppe,
                'season' => $season
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
            $season = isset($_POST['season']) ? sanitize_text_field($_POST['season']) : '';

            // Get filtered compliance data
            $compliance_data = $this->get_compliance_data($species, $meldegruppe, $season);

            wp_send_json_success([
                'data' => $compliance_data,
                'message' => __('Filter angewendet', 'abschussplan-hgmh')
            ]);
        } catch (Exception $e) {
            error_log('AHGMH Compliance Filter Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Filtern der Compliance-Daten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
        }
    }

    /**
     * Parse season string to date range
     * German hunting season runs from April 1 to March 31
     *
     * @param string $season Season string (format: YYYY-YYYY)
     * @return array|null Array with 'start' and 'end' dates, or null if invalid
     */
    private function parse_season_to_dates($season) {
        if (empty($season)) {
            return null;
        }

        // Parse season format: YYYY-YYYY
        if (preg_match('/^(\d{4})-(\d{4})$/', $season, $matches)) {
            $start_year = intval($matches[1]);
            $end_year = intval($matches[2]);

            // Validate that end_year is start_year + 1
            if ($end_year !== $start_year + 1) {
                return null;
            }

            return [
                'start' => sprintf('%d-04-01', $start_year),
                'end' => sprintf('%d-03-31', $end_year)
            ];
        }

        return null;
    }

}
