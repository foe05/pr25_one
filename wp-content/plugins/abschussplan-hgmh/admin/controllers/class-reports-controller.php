<?php
/**
 * Reports Controller
 * Handles report generation requests and downloads
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reports Controller Class
 */
class AHGMH_Reports_Controller {

    private $report_generator_service;
    private $report_service;
    private $view;

    /**
     * Constructor
     */
    public function __construct() {
        // Load report services
        require_once AHGMH_PLUGIN_DIR . 'admin/services/class-report-service.php';
        require_once AHGMH_PLUGIN_DIR . 'admin/services/class-report-generator-service.php';

        $this->report_service = new AHGMH_Report_Service();
        $this->report_generator_service = new AHGMH_Report_Generator_Service();

        // Load view
        if (file_exists(AHGMH_PLUGIN_DIR . 'admin/views/class-reports-view.php')) {
            require_once AHGMH_PLUGIN_DIR . 'admin/views/class-reports-view.php';
            $this->view = new AHGMH_Reports_View();
        }

        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_ahgmh_preview_report', array($this, 'ajax_preview_report'));
        add_action('wp_ajax_ahgmh_download_report_csv', array($this, 'ajax_download_csv'));
        add_action('wp_ajax_ahgmh_email_report', array($this, 'ajax_email_report'));
    }

    /**
     * Render reports page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get filter options
        $species_list = get_option('ahgmh_species', ['Rotwild', 'Damwild']);
        $meldegruppen = get_option('ahgmh_meldegruppen', []);

        // Get current hunting season
        $current_season = $this->get_current_hunting_season();

        $data = [
            'species_list' => $species_list,
            'meldegruppen' => $meldegruppen,
            'current_season' => $current_season,
            'hunting_seasons' => $this->get_hunting_seasons()
        ];

        // Render the page using the view if available
        if ($this->view) {
            $this->view->render_reports_page($data);
        } else {
            // Fallback rendering if view not yet implemented
            $this->render_fallback($data);
        }
    }

    /**
     * AJAX handler for report generation
     */
    public function ajax_generate_report() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get and validate parameters
            $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
            $output_format = isset($_POST['output_format']) ? sanitize_text_field($_POST['output_format']) : 'html';

            // Validate report type
            $valid_types = ['seasonal', 'date_range', 'compliance', 'trend'];
            if (!in_array($report_type, $valid_types)) {
                throw new Exception(__('Ungültiger Berichtstyp', 'abschussplan-hgmh'));
            }

            // Get filters
            $filters = $this->get_filters_from_request();

            // Validate date ranges if needed
            $dates = $this->get_dates_from_request($report_type);
            if (isset($dates['error'])) {
                throw new Exception($dates['error']);
            }

            // Generate report based on type
            $report = $this->generate_report($report_type, $dates, $filters, $output_format);

            if (isset($report['error'])) {
                throw new Exception($report['message']);
            }

            wp_send_json_success([
                'report' => $report,
                'message' => __('Bericht erfolgreich erstellt', 'abschussplan-hgmh')
            ]);

        } catch (Exception $e) {
            error_log('AHGMH Report Generation Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for report preview
     */
    public function ajax_preview_report() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get and validate parameters
            $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';

            // Validate report type
            $valid_types = ['seasonal', 'date_range', 'compliance', 'trend'];
            if (!in_array($report_type, $valid_types)) {
                throw new Exception(__('Ungültiger Berichtstyp', 'abschussplan-hgmh'));
            }

            // Get filters
            $filters = $this->get_filters_from_request();

            // Validate date ranges
            $dates = $this->get_dates_from_request($report_type);
            if (isset($dates['error'])) {
                throw new Exception($dates['error']);
            }

            // Generate report with HTML format for preview
            $report = $this->generate_report($report_type, $dates, $filters, 'html');

            if (isset($report['error'])) {
                throw new Exception($report['message']);
            }

            // Return HTML for preview
            wp_send_json_success([
                'html' => isset($report['html']) ? $report['html'] : '',
                'report_data' => $report,
                'message' => __('Vorschau geladen', 'abschussplan-hgmh')
            ]);

        } catch (Exception $e) {
            error_log('AHGMH Report Preview Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for CSV download
     */
    public function ajax_download_csv() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get and validate parameters
            $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';

            // Validate report type
            $valid_types = ['seasonal', 'date_range', 'compliance', 'trend'];
            if (!in_array($report_type, $valid_types)) {
                throw new Exception(__('Ungültiger Berichtstyp', 'abschussplan-hgmh'));
            }

            // Get filters
            $filters = $this->get_filters_from_request();

            // Validate date ranges
            $dates = $this->get_dates_from_request($report_type);
            if (isset($dates['error'])) {
                throw new Exception($dates['error']);
            }

            // Generate report with CSV format
            $report = $this->generate_report($report_type, $dates, $filters, 'csv');

            if (isset($report['error'])) {
                throw new Exception($report['message']);
            }

            if (!isset($report['csv'])) {
                throw new Exception(__('CSV-Daten konnten nicht generiert werden', 'abschussplan-hgmh'));
            }

            // Generate secure filename
            $filename = $this->generate_filename($report_type, 'csv');

            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');
            header('Pragma: public');

            // Output CSV content
            echo $report['csv'];
            exit;

        } catch (Exception $e) {
            error_log('AHGMH CSV Download Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for email report
     */
    public function ajax_email_report() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get and validate parameters
            $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
            $recipient = isset($_POST['recipient']) ? sanitize_email($_POST['recipient']) : '';

            // Validate report type
            $valid_types = ['seasonal', 'date_range', 'compliance', 'trend'];
            if (!in_array($report_type, $valid_types)) {
                throw new Exception(__('Ungültiger Berichtstyp', 'abschussplan-hgmh'));
            }

            // Validate recipient
            if (empty($recipient) || !is_email($recipient)) {
                throw new Exception(__('Ungültige E-Mail-Adresse', 'abschussplan-hgmh'));
            }

            // Get filters
            $filters = $this->get_filters_from_request();

            // Validate date ranges
            $dates = $this->get_dates_from_request($report_type);
            if (isset($dates['error'])) {
                throw new Exception($dates['error']);
            }

            // Generate report with HTML format
            $report = $this->generate_report($report_type, $dates, $filters, 'html');

            if (isset($report['error'])) {
                throw new Exception($report['message']);
            }

            // Send email
            $result = $this->send_report_email($recipient, $report);

            if ($result) {
                wp_send_json_success([
                    'message' => sprintf(__('Bericht erfolgreich an %s gesendet', 'abschussplan-hgmh'), $recipient)
                ]);
            } else {
                throw new Exception(__('E-Mail konnte nicht gesendet werden', 'abschussplan-hgmh'));
            }

        } catch (Exception $e) {
            error_log('AHGMH Email Report Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Generate report based on type
     *
     * @param string $report_type Report type
     * @param array $dates Date information
     * @param array $filters Filters
     * @param string $format Output format
     * @return array Report data
     */
    private function generate_report($report_type, $dates, $filters, $format = 'array') {
        switch ($report_type) {
            case 'seasonal':
                return $this->report_generator_service->generate_seasonal_report(
                    $dates['season_start'],
                    $dates['season_end'],
                    $filters,
                    $format
                );

            case 'date_range':
                return $this->report_generator_service->generate_date_range_report(
                    $dates['start_date'],
                    $dates['end_date'],
                    $filters,
                    $format
                );

            case 'compliance':
                return $this->report_generator_service->generate_compliance_report(
                    $filters,
                    $format
                );

            case 'trend':
                return $this->report_generator_service->generate_trend_report(
                    $dates['current_season_start'],
                    $dates['current_season_end'],
                    $dates['previous_season_start'],
                    $dates['previous_season_end'],
                    $filters,
                    $format
                );

            default:
                return [
                    'error' => true,
                    'message' => __('Unbekannter Berichtstyp', 'abschussplan-hgmh')
                ];
        }
    }

    /**
     * Get filters from request
     *
     * @return array Sanitized filters
     */
    private function get_filters_from_request() {
        $filters = [];

        if (isset($_POST['species']) && !empty($_POST['species'])) {
            $filters['species'] = sanitize_text_field($_POST['species']);
        }

        if (isset($_POST['meldegruppe']) && !empty($_POST['meldegruppe'])) {
            $filters['meldegruppe'] = sanitize_text_field($_POST['meldegruppe']);
        }

        return $filters;
    }

    /**
     * Get dates from request based on report type
     *
     * @param string $report_type Report type
     * @return array Date information or error
     */
    private function get_dates_from_request($report_type) {
        $dates = [];

        switch ($report_type) {
            case 'seasonal':
                // Parse season parameter (format: YYYY-YYYY)
                $season = isset($_POST['season']) ? sanitize_text_field($_POST['season']) : '';

                if (empty($season)) {
                    // Use current season
                    $current_season = $this->get_current_hunting_season();
                    $season = $current_season['value'];
                }

                $season_dates = $this->parse_season_to_dates($season);
                if (!$season_dates) {
                    return ['error' => __('Ungültiges Saisonformat', 'abschussplan-hgmh')];
                }

                $dates['season_start'] = $season_dates['start'];
                $dates['season_end'] = $season_dates['end'];
                break;

            case 'date_range':
                // Get custom date range
                $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
                $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

                // Validate dates
                if (empty($start_date) || empty($end_date)) {
                    return ['error' => __('Start- und Enddatum sind erforderlich', 'abschussplan-hgmh')];
                }

                if (!$this->validate_date($start_date) || !$this->validate_date($end_date)) {
                    return ['error' => __('Ungültiges Datumsformat', 'abschussplan-hgmh')];
                }

                if (strtotime($start_date) > strtotime($end_date)) {
                    return ['error' => __('Startdatum muss vor Enddatum liegen', 'abschussplan-hgmh')];
                }

                $dates['start_date'] = $start_date;
                $dates['end_date'] = $end_date;
                break;

            case 'compliance':
                // Compliance reports don't require date parameters
                // They use current data
                break;

            case 'trend':
                // Get current and previous season
                $current_season = isset($_POST['current_season']) ? sanitize_text_field($_POST['current_season']) : '';

                if (empty($current_season)) {
                    $current_season = $this->get_current_hunting_season()['value'];
                }

                $current_dates = $this->parse_season_to_dates($current_season);
                if (!$current_dates) {
                    return ['error' => __('Ungültiges Saisonformat für aktuelle Saison', 'abschussplan-hgmh')];
                }

                // Calculate previous season
                $previous_season = $this->get_previous_season($current_season);
                $previous_dates = $this->parse_season_to_dates($previous_season);

                $dates['current_season_start'] = $current_dates['start'];
                $dates['current_season_end'] = $current_dates['end'];
                $dates['previous_season_start'] = $previous_dates['start'];
                $dates['previous_season_end'] = $previous_dates['end'];
                break;
        }

        return $dates;
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

    /**
     * Get current hunting season
     *
     * @return array Season info
     */
    private function get_current_hunting_season() {
        $current_date = current_time('Y-m-d');
        $current_year = intval(current_time('Y'));
        $current_month = intval(current_time('m'));

        // German hunting season runs April 1 - March 31
        if ($current_month >= 4) {
            // We're in the season that started this year
            $start_year = $current_year;
            $end_year = $current_year + 1;
        } else {
            // We're in the season that started last year
            $start_year = $current_year - 1;
            $end_year = $current_year;
        }

        return [
            'label' => sprintf('%d-%d', $start_year, $end_year),
            'value' => sprintf('%d-%d', $start_year, $end_year),
            'start' => sprintf('%d-04-01', $start_year),
            'end' => sprintf('%d-03-31', $end_year)
        ];
    }

    /**
     * Get list of hunting seasons (last 5 years)
     *
     * @return array List of seasons
     */
    private function get_hunting_seasons() {
        $seasons = [];
        $current_season = $this->get_current_hunting_season();
        $current_start_year = intval(substr($current_season['value'], 0, 4));

        // Generate last 5 seasons
        for ($i = 0; $i < 5; $i++) {
            $start_year = $current_start_year - $i;
            $end_year = $start_year + 1;

            $seasons[] = [
                'label' => sprintf('%d-%d', $start_year, $end_year),
                'value' => sprintf('%d-%d', $start_year, $end_year)
            ];
        }

        return $seasons;
    }

    /**
     * Get previous season
     *
     * @param string $season Current season (format: YYYY-YYYY)
     * @return string Previous season
     */
    private function get_previous_season($season) {
        if (preg_match('/^(\d{4})-(\d{4})$/', $season, $matches)) {
            $start_year = intval($matches[1]) - 1;
            $end_year = intval($matches[2]) - 1;
            return sprintf('%d-%d', $start_year, $end_year);
        }
        return '';
    }

    /**
     * Validate date format
     *
     * @param string $date Date string
     * @return bool True if valid
     */
    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Generate secure filename
     *
     * @param string $report_type Report type
     * @param string $extension File extension
     * @return string Filename
     */
    private function generate_filename($report_type, $extension) {
        $type_map = [
            'seasonal' => 'saisonbericht',
            'date_range' => 'zeitraumbericht',
            'compliance' => 'compliance-bericht',
            'trend' => 'trendanalyse'
        ];

        $type_name = isset($type_map[$report_type]) ? $type_map[$report_type] : 'bericht';
        $timestamp = date('Y-m-d-His');

        return sprintf('%s-%s.%s', $type_name, $timestamp, $extension);
    }

    /**
     * Send report via email
     *
     * @param string $recipient Recipient email
     * @param array $report Report data
     * @return bool Success status
     */
    private function send_report_email($recipient, $report) {
        $subject = sprintf(
            __('[Abschussplan] %s', 'abschussplan-hgmh'),
            $report['title']
        );

        // Prepare email body
        $body = '<html><body>';
        $body .= '<h2>' . esc_html($report['title']) . '</h2>';
        $body .= '<p>' . __('Anbei finden Sie den angeforderten Bericht.', 'abschussplan-hgmh') . '</p>';

        if (isset($report['html'])) {
            $body .= $report['html'];
        }

        $body .= '<hr>';
        $body .= '<p><small>' . __('Diese E-Mail wurde automatisch generiert.', 'abschussplan-hgmh') . '</small></p>';
        $body .= '</body></html>';

        // Set email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        ];

        // Send email
        return wp_mail($recipient, $subject, $body, $headers);
    }

    /**
     * Fallback rendering when view is not available
     *
     * @param array $data Page data
     */
    private function render_fallback($data) {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Berichte', 'abschussplan-hgmh') . '</h1>';
        echo '<div class="notice notice-info"><p>' . esc_html__('Die Berichte-Ansicht wird gerade implementiert.', 'abschussplan-hgmh') . '</p></div>';
        echo '<p>' . esc_html__('Diese Seite ermöglicht es Ihnen, verschiedene Berichte zu generieren:', 'abschussplan-hgmh') . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__('Saisonberichte', 'abschussplan-hgmh') . '</li>';
        echo '<li>' . esc_html__('Zeitraumberichte', 'abschussplan-hgmh') . '</li>';
        echo '<li>' . esc_html__('Compliance-Berichte', 'abschussplan-hgmh') . '</li>';
        echo '<li>' . esc_html__('Trendanalysen', 'abschussplan-hgmh') . '</li>';
        echo '</ul>';
        echo '</div>';
    }

}
