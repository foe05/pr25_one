<?php
/**
 * Report Generator Service Class
 * Generates formatted report data structures (HTML, CSV, PDF-ready)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Report Generator Service for creating formatted reports
 */
class AHGMH_Report_Generator_Service {

    private $report_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->report_service = new AHGMH_Report_Service();
    }

    /**
     * Generate seasonal summary report
     *
     * @param string $season_start Start date (Y-m-d format)
     * @param string $season_end End date (Y-m-d format)
     * @param array $filters Optional filters (species, meldegruppe)
     * @param string $format Output format: 'array', 'html', 'csv'
     * @return array Report data structure
     */
    public function generate_seasonal_report($season_start, $season_end, $filters = [], $format = 'array') {
        try {
            // Get seasonal data from report service
            $species = isset($filters['species']) ? $filters['species'] : '';
            $meldegruppe = isset($filters['meldegruppe']) ? $filters['meldegruppe'] : '';

            $data = $this->report_service->get_seasonal_data($season_start, $season_end, $species, $meldegruppe);

            // Add report metadata
            $report = [
                'report_type' => 'seasonal_summary',
                'title' => 'Saisonbericht',
                'metadata' => [
                    'generated_at' => current_time('mysql'),
                    'generated_by' => wp_get_current_user()->display_name,
                    'period' => [
                        'start' => $season_start,
                        'end' => $season_end
                    ],
                    'filters' => $filters
                ],
                'data' => $data
            ];

            // Format according to requested output format
            return $this->format_report($report, $format);

        } catch (Exception $e) {
            return $this->get_error_report($e->getMessage());
        }
    }

    /**
     * Generate custom date range report
     *
     * @param string $start_date Start date (Y-m-d format)
     * @param string $end_date End date (Y-m-d format)
     * @param array $filters Optional filters
     * @param string $format Output format: 'array', 'html', 'csv'
     * @return array Report data structure
     */
    public function generate_date_range_report($start_date, $end_date, $filters = [], $format = 'array') {
        try {
            // Get date range data from report service
            $data = $this->report_service->get_date_range_data($start_date, $end_date, $filters);

            // Add report metadata
            $report = [
                'report_type' => 'date_range',
                'title' => 'Zeitraumbericht',
                'metadata' => [
                    'generated_at' => current_time('mysql'),
                    'generated_by' => wp_get_current_user()->display_name,
                    'period' => [
                        'start' => $start_date,
                        'end' => $end_date
                    ],
                    'filters' => $filters
                ],
                'data' => $data
            ];

            // Format according to requested output format
            return $this->format_report($report, $format);

        } catch (Exception $e) {
            return $this->get_error_report($e->getMessage());
        }
    }

    /**
     * Generate compliance report
     *
     * @param array $filters Optional filters (species, meldegruppe)
     * @param string $format Output format: 'array', 'html', 'csv'
     * @return array Report data structure
     */
    public function generate_compliance_report($filters = [], $format = 'array') {
        try {
            // Get compliance data from report service
            $species = isset($filters['species']) ? $filters['species'] : '';
            $meldegruppe = isset($filters['meldegruppe']) ? $filters['meldegruppe'] : '';

            $data = $this->report_service->get_compliance_data($species, $meldegruppe);

            // Get compliance by meldegruppe for detailed breakdown
            if (empty($meldegruppe)) {
                $data['meldegruppe_breakdown'] = $this->report_service->get_compliance_by_meldegruppe($species);
            }

            // Get compliance summary
            if (empty($species)) {
                $data['summary'] = $this->report_service->get_compliance_summary();
            }

            // Add report metadata
            $report = [
                'report_type' => 'compliance',
                'title' => 'Compliance-Bericht',
                'metadata' => [
                    'generated_at' => current_time('mysql'),
                    'generated_by' => wp_get_current_user()->display_name,
                    'filters' => $filters
                ],
                'data' => $data
            ];

            // Format according to requested output format
            return $this->format_report($report, $format);

        } catch (Exception $e) {
            return $this->get_error_report($e->getMessage());
        }
    }

    /**
     * Generate trend analysis report
     *
     * @param string $current_season_start Current season start date
     * @param string $current_season_end Current season end date
     * @param string $previous_season_start Previous season start date
     * @param string $previous_season_end Previous season end date
     * @param array $filters Optional filters
     * @param string $format Output format: 'array', 'html', 'csv'
     * @return array Report data structure
     */
    public function generate_trend_report($current_season_start, $current_season_end, $previous_season_start, $previous_season_end, $filters = [], $format = 'array') {
        try {
            $species = isset($filters['species']) ? $filters['species'] : '';
            $meldegruppe = isset($filters['meldegruppe']) ? $filters['meldegruppe'] : '';

            // Get year-over-year comparison
            $yoy_data = $this->report_service->get_year_over_year_comparison(
                $current_season_start,
                $current_season_end,
                $previous_season_start,
                $previous_season_end,
                $species,
                $meldegruppe
            );

            // Get monthly trends
            $monthly_trends = $this->report_service->get_monthly_trends(
                $current_season_start,
                $current_season_end,
                $species,
                $meldegruppe
            );

            // Get seasonal patterns
            $seasonal_patterns = $this->report_service->get_seasonal_patterns(
                $current_season_start,
                $current_season_end,
                $species,
                $meldegruppe
            );

            // Add report metadata
            $report = [
                'report_type' => 'trend_analysis',
                'title' => 'Trendanalyse',
                'metadata' => [
                    'generated_at' => current_time('mysql'),
                    'generated_by' => wp_get_current_user()->display_name,
                    'current_period' => [
                        'start' => $current_season_start,
                        'end' => $current_season_end
                    ],
                    'previous_period' => [
                        'start' => $previous_season_start,
                        'end' => $previous_season_end
                    ],
                    'filters' => $filters
                ],
                'data' => [
                    'year_over_year' => $yoy_data,
                    'monthly_trends' => $monthly_trends,
                    'seasonal_patterns' => $seasonal_patterns
                ]
            ];

            // Format according to requested output format
            return $this->format_report($report, $format);

        } catch (Exception $e) {
            return $this->get_error_report($e->getMessage());
        }
    }

    /**
     * Format report according to output format
     *
     * @param array $report Report data
     * @param string $format Output format
     * @return array Formatted report
     */
    private function format_report($report, $format) {
        switch ($format) {
            case 'html':
                return $this->format_as_html($report);
            case 'csv':
                return $this->format_as_csv($report);
            case 'pdf':
                return $this->format_for_pdf($report);
            case 'array':
            default:
                return $report;
        }
    }

    /**
     * Format report as HTML
     *
     * @param array $report Report data
     * @return array Report with HTML content
     */
    private function format_as_html($report) {
        $html = $this->generate_html_header($report);

        switch ($report['report_type']) {
            case 'seasonal_summary':
                $html .= $this->generate_seasonal_html($report['data']);
                break;
            case 'date_range':
                $html .= $this->generate_date_range_html($report['data']);
                break;
            case 'compliance':
                $html .= $this->generate_compliance_html($report['data']);
                break;
            case 'trend_analysis':
                $html .= $this->generate_trend_html($report['data']);
                break;
        }

        $html .= $this->generate_html_footer($report);

        $report['html'] = $html;
        return $report;
    }

    /**
     * Format report as CSV
     *
     * @param array $report Report data
     * @return array Report with CSV content
     */
    private function format_as_csv($report) {
        $csv = $this->generate_csv_header($report);

        switch ($report['report_type']) {
            case 'seasonal_summary':
                $csv .= $this->generate_seasonal_csv($report['data']);
                break;
            case 'date_range':
                $csv .= $this->generate_date_range_csv($report['data']);
                break;
            case 'compliance':
                $csv .= $this->generate_compliance_csv($report['data']);
                break;
            case 'trend_analysis':
                $csv .= $this->generate_trend_csv($report['data']);
                break;
        }

        $report['csv'] = $csv;
        return $report;
    }

    /**
     * Format report for PDF generation
     *
     * @param array $report Report data
     * @return array Report with PDF-ready HTML
     */
    private function format_for_pdf($report) {
        $html = $this->generate_pdf_header($report);

        switch ($report['report_type']) {
            case 'seasonal_summary':
                $html .= $this->generate_seasonal_pdf_html($report['data']);
                break;
            case 'date_range':
                $html .= $this->generate_date_range_pdf_html($report['data']);
                break;
            case 'compliance':
                $html .= $this->generate_compliance_pdf_html($report['data']);
                break;
            case 'trend_analysis':
                $html .= $this->generate_trend_pdf_html($report['data']);
                break;
        }

        $html .= $this->generate_pdf_footer($report);

        $report['pdf_html'] = $html;
        return $report;
    }

    /**
     * Generate HTML header
     *
     * @param array $report Report data
     * @return string HTML header
     */
    private function generate_html_header($report) {
        $title = esc_html($report['title']);
        $generated_at = esc_html($report['metadata']['generated_at']);
        $generated_by = esc_html($report['metadata']['generated_by']);

        $html = '<div class="ahgmh-report">';
        $html .= '<div class="report-header">';
        $html .= '<h1>' . $title . '</h1>';
        $html .= '<p class="report-meta">Erstellt am: ' . $generated_at . ' von ' . $generated_by . '</p>';

        if (isset($report['metadata']['period'])) {
            $start = esc_html($report['metadata']['period']['start']);
            $end = esc_html($report['metadata']['period']['end']);
            $html .= '<p class="report-period">Zeitraum: ' . $start . ' bis ' . $end . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate HTML footer
     *
     * @param array $report Report data
     * @return string HTML footer
     */
    private function generate_html_footer($report) {
        return '</div>';
    }

    /**
     * Generate seasonal report HTML
     *
     * @param array $data Seasonal data
     * @return string HTML content
     */
    private function generate_seasonal_html($data) {
        $html = '<div class="report-section">';
        $html .= '<h2>Zusammenfassung</h2>';
        $html .= '<div class="summary-stats">';
        $html .= '<div class="stat-box">';
        $html .= '<span class="stat-label">Meldungen gesamt:</span> ';
        $html .= '<span class="stat-value">' . absint($data['summary']['total_submissions']) . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-box">';
        $html .= '<span class="stat-label">Abschüsse gesamt:</span> ';
        $html .= '<span class="stat-value">' . absint($data['summary']['total_harvests']) . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // Species breakdown
        if (!empty($data['species_breakdown'])) {
            $html .= '<h3>Aufschlüsselung nach Wildart</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Wildart</th><th>Anzahl</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($data['species_breakdown'] as $item) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($item['name']) . '</td>';
                $html .= '<td>' . absint($item['count']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        // Category breakdown
        if (!empty($data['category_breakdown'])) {
            $html .= '<h3>Aufschlüsselung nach Kategorie</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Kategorie</th><th>Anzahl</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($data['category_breakdown'] as $item) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($item['name']) . '</td>';
                $html .= '<td>' . absint($item['count']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        // Meldegruppe breakdown
        if (!empty($data['meldegruppe_breakdown'])) {
            $html .= '<h3>Aufschlüsselung nach Meldegruppe</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Meldegruppe</th><th>Anzahl</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($data['meldegruppe_breakdown'] as $item) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($item['name']) . '</td>';
                $html .= '<td>' . absint($item['count']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate date range report HTML
     *
     * @param array $data Date range data
     * @return string HTML content
     */
    private function generate_date_range_html($data) {
        $html = '<div class="report-section">';
        $html .= '<h2>Statistiken</h2>';
        $html .= '<div class="summary-stats">';
        $html .= '<div class="stat-box">';
        $html .= '<span class="stat-label">Meldungen:</span> ';
        $html .= '<span class="stat-value">' . absint($data['statistics']['submission_count']) . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-box">';
        $html .= '<span class="stat-label">Abschüsse:</span> ';
        $html .= '<span class="stat-value">' . absint($data['statistics']['total_harvests']) . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-box">';
        $html .= '<span class="stat-label">Wildarten:</span> ';
        $html .= '<span class="stat-value">' . absint($data['statistics']['species_count']) . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // Submissions table
        if (!empty($data['submissions'])) {
            $html .= '<h3>Meldungen</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Datum</th><th>Wildart</th><th>Kategorie</th><th>Meldegruppe</th><th>Anzahl</th><th>WUS-Nr.</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($data['submissions'] as $submission) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($submission['datum']) . '</td>';
                $html .= '<td>' . esc_html($submission['art']) . '</td>';
                $html .= '<td>' . esc_html($submission['kategorie']) . '</td>';
                $html .= '<td>' . esc_html($submission['meldegruppe']) . '</td>';
                $html .= '<td>' . absint($submission['anzahl']) . '</td>';
                $html .= '<td>' . esc_html($submission['wus_nummer']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate compliance report HTML
     *
     * @param array $data Compliance data
     * @return string HTML content
     */
    private function generate_compliance_html($data) {
        $html = '<div class="report-section">';
        $html .= '<h2>Compliance-Status</h2>';

        if (!empty($data['compliance'])) {
            foreach ($data['compliance'] as $species_data) {
                $html .= '<h3>' . esc_html($species_data['species']) . '</h3>';
                $html .= '<table class="report-table compliance-table">';
                $html .= '<thead><tr><th>Kategorie</th><th>Aktuell</th><th>Limit</th><th>Prozent</th><th>Status</th></tr></thead>';
                $html .= '<tbody>';

                foreach ($species_data['categories'] as $category => $cat_data) {
                    $status_class = esc_attr($cat_data['status']);
                    $html .= '<tr class="status-' . $status_class . '">';
                    $html .= '<td>' . esc_html($category) . '</td>';
                    $html .= '<td>' . absint($cat_data['current']) . '</td>';
                    $html .= '<td>' . absint($cat_data['limit']) . '</td>';
                    $html .= '<td>' . floatval($cat_data['percentage']) . '%</td>';
                    $html .= '<td><span class="badge badge-' . $status_class . '">' . esc_html($cat_data['status']) . '</span></td>';
                    $html .= '</tr>';
                }

                // Total row
                $total = $species_data['total'];
                $status_class = esc_attr($total['status']);
                $html .= '<tr class="total-row status-' . $status_class . '">';
                $html .= '<td><strong>Gesamt</strong></td>';
                $html .= '<td><strong>' . absint($total['current']) . '</strong></td>';
                $html .= '<td><strong>' . absint($total['limit']) . '</strong></td>';
                $html .= '<td><strong>' . floatval($total['percentage']) . '%</strong></td>';
                $html .= '<td><span class="badge badge-' . $status_class . '">' . esc_html($total['status']) . '</span></td>';
                $html .= '</tr>';

                $html .= '</tbody></table>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate trend analysis report HTML
     *
     * @param array $data Trend data
     * @return string HTML content
     */
    private function generate_trend_html($data) {
        $html = '<div class="report-section">';
        $html .= '<h2>Jahresvergleich</h2>';

        if (isset($data['year_over_year'])) {
            $yoy = $data['year_over_year'];
            $changes = $yoy['changes'];

            $html .= '<div class="comparison-stats">';
            $html .= '<div class="stat-box">';
            $html .= '<span class="stat-label">Abschussänderung:</span> ';
            $html .= '<span class="stat-value">' . intval($changes['harvest_change']) . ' (' . floatval($changes['harvest_change_percent']) . '%)</span>';
            $html .= '</div>';
            $html .= '<div class="stat-box">';
            $html .= '<span class="stat-label">Meldungsänderung:</span> ';
            $html .= '<span class="stat-value">' . intval($changes['submission_change']) . ' (' . floatval($changes['submission_change_percent']) . '%)</span>';
            $html .= '</div>';
            $html .= '</div>';
        }

        if (isset($data['monthly_trends']['monthly_trends'])) {
            $html .= '<h3>Monatliche Trends</h3>';
            $html .= '<table class="report-table">';
            $html .= '<thead><tr><th>Monat</th><th>Meldungen</th><th>Abschüsse</th><th>Trend</th></tr></thead>';
            $html .= '<tbody>';

            foreach ($data['monthly_trends']['monthly_trends'] as $trend) {
                $trend_class = esc_attr($trend['trend']);
                $html .= '<tr>';
                $html .= '<td>' . esc_html($trend['month']) . '</td>';
                $html .= '<td>' . absint($trend['submission_count']) . '</td>';
                $html .= '<td>' . absint($trend['harvest_count']) . '</td>';
                $html .= '<td><span class="badge badge-' . $trend_class . '">' . esc_html($trend['trend']) . '</span></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate CSV header
     *
     * @param array $report Report data
     * @return string CSV header
     */
    private function generate_csv_header($report) {
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= '"' . $report['title'] . '"' . "\n";
        $csv .= '"Erstellt am: ' . $report['metadata']['generated_at'] . '"' . "\n";
        $csv .= '"Erstellt von: ' . $report['metadata']['generated_by'] . '"' . "\n";

        if (isset($report['metadata']['period'])) {
            $csv .= '"Zeitraum: ' . $report['metadata']['period']['start'] . ' bis ' . $report['metadata']['period']['end'] . '"' . "\n";
        }

        $csv .= "\n";

        return $csv;
    }

    /**
     * Generate seasonal report CSV
     *
     * @param array $data Seasonal data
     * @return string CSV content
     */
    private function generate_seasonal_csv($data) {
        $csv = '"Zusammenfassung"' . "\n";
        $csv .= '"Meldungen gesamt","' . $data['summary']['total_submissions'] . '"' . "\n";
        $csv .= '"Abschüsse gesamt","' . $data['summary']['total_harvests'] . '"' . "\n";
        $csv .= "\n";

        // Species breakdown
        if (!empty($data['species_breakdown'])) {
            $csv .= '"Wildart","Anzahl"' . "\n";
            foreach ($data['species_breakdown'] as $item) {
                $csv .= '"' . $item['name'] . '","' . $item['count'] . '"' . "\n";
            }
            $csv .= "\n";
        }

        // Category breakdown
        if (!empty($data['category_breakdown'])) {
            $csv .= '"Kategorie","Anzahl"' . "\n";
            foreach ($data['category_breakdown'] as $item) {
                $csv .= '"' . $item['name'] . '","' . $item['count'] . '"' . "\n";
            }
            $csv .= "\n";
        }

        // Meldegruppe breakdown
        if (!empty($data['meldegruppe_breakdown'])) {
            $csv .= '"Meldegruppe","Anzahl"' . "\n";
            foreach ($data['meldegruppe_breakdown'] as $item) {
                $csv .= '"' . $item['name'] . '","' . $item['count'] . '"' . "\n";
            }
        }

        return $csv;
    }

    /**
     * Generate date range report CSV
     *
     * @param array $data Date range data
     * @return string CSV content
     */
    private function generate_date_range_csv($data) {
        $csv = '"Statistiken"' . "\n";
        $csv .= '"Meldungen","' . $data['statistics']['submission_count'] . '"' . "\n";
        $csv .= '"Abschüsse","' . $data['statistics']['total_harvests'] . '"' . "\n";
        $csv .= '"Wildarten","' . $data['statistics']['species_count'] . '"' . "\n";
        $csv .= "\n";

        // Submissions
        if (!empty($data['submissions'])) {
            $csv .= '"Datum","Wildart","Kategorie","Meldegruppe","Anzahl","WUS-Nr."' . "\n";
            foreach ($data['submissions'] as $submission) {
                $csv .= '"' . $submission['datum'] . '",';
                $csv .= '"' . $submission['art'] . '",';
                $csv .= '"' . $submission['kategorie'] . '",';
                $csv .= '"' . $submission['meldegruppe'] . '",';
                $csv .= '"' . $submission['anzahl'] . '",';
                $csv .= '"' . $submission['wus_nummer'] . '"' . "\n";
            }
        }

        return $csv;
    }

    /**
     * Generate compliance report CSV
     *
     * @param array $data Compliance data
     * @return string CSV content
     */
    private function generate_compliance_csv($data) {
        $csv = '"Compliance-Status"' . "\n";

        if (!empty($data['compliance'])) {
            foreach ($data['compliance'] as $species_data) {
                $csv .= "\n" . '"' . $species_data['species'] . '"' . "\n";
                $csv .= '"Kategorie","Aktuell","Limit","Prozent","Status"' . "\n";

                foreach ($species_data['categories'] as $category => $cat_data) {
                    $csv .= '"' . $category . '",';
                    $csv .= '"' . $cat_data['current'] . '",';
                    $csv .= '"' . $cat_data['limit'] . '",';
                    $csv .= '"' . $cat_data['percentage'] . '%",';
                    $csv .= '"' . $cat_data['status'] . '"' . "\n";
                }

                // Total
                $total = $species_data['total'];
                $csv .= '"Gesamt",';
                $csv .= '"' . $total['current'] . '",';
                $csv .= '"' . $total['limit'] . '",';
                $csv .= '"' . $total['percentage'] . '%",';
                $csv .= '"' . $total['status'] . '"' . "\n";
            }
        }

        return $csv;
    }

    /**
     * Generate trend analysis report CSV
     *
     * @param array $data Trend data
     * @return string CSV content
     */
    private function generate_trend_csv($data) {
        $csv = '"Trendanalyse"' . "\n";

        if (isset($data['year_over_year'])) {
            $yoy = $data['year_over_year'];
            $changes = $yoy['changes'];

            $csv .= '"Jahresvergleich"' . "\n";
            $csv .= '"Abschussänderung","' . $changes['harvest_change'] . '","' . $changes['harvest_change_percent'] . '%"' . "\n";
            $csv .= '"Meldungsänderung","' . $changes['submission_change'] . '","' . $changes['submission_change_percent'] . '%"' . "\n";
            $csv .= "\n";
        }

        if (isset($data['monthly_trends']['monthly_trends'])) {
            $csv .= '"Monatliche Trends"' . "\n";
            $csv .= '"Monat","Meldungen","Abschüsse","Trend"' . "\n";

            foreach ($data['monthly_trends']['monthly_trends'] as $trend) {
                $csv .= '"' . $trend['month'] . '",';
                $csv .= '"' . $trend['submission_count'] . '",';
                $csv .= '"' . $trend['harvest_count'] . '",';
                $csv .= '"' . $trend['trend'] . '"' . "\n";
            }
        }

        return $csv;
    }

    /**
     * Generate PDF header (simplified HTML for PDF)
     *
     * @param array $report Report data
     * @return string PDF HTML header
     */
    private function generate_pdf_header($report) {
        $html = '<html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }';
        $html .= 'h1 { font-size: 18pt; margin-bottom: 10px; }';
        $html .= 'h2 { font-size: 14pt; margin-top: 20px; margin-bottom: 10px; }';
        $html .= 'h3 { font-size: 12pt; margin-top: 15px; margin-bottom: 8px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }';
        $html .= 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
        $html .= 'th { background-color: #f8f9fa; font-weight: bold; }';
        $html .= '.report-meta { color: #666; font-size: 9pt; margin-bottom: 5px; }';
        $html .= '.stat-box { display: inline-block; margin-right: 20px; margin-bottom: 10px; }';
        $html .= '.stat-label { font-weight: bold; }';
        $html .= '.badge { padding: 2px 8px; border-radius: 3px; font-size: 9pt; }';
        $html .= '.badge-good { background-color: #d4edda; color: #155724; }';
        $html .= '.badge-warning { background-color: #fff3cd; color: #856404; }';
        $html .= '.badge-critical { background-color: #f8d7da; color: #721c24; }';
        $html .= '.badge-exceeded { background-color: #d1ecf1; color: #0c5460; }';
        $html .= '.total-row { font-weight: bold; background-color: #f8f9fa; }';
        $html .= '</style>';
        $html .= '</head><body>';

        $title = esc_html($report['title']);
        $generated_at = esc_html($report['metadata']['generated_at']);
        $generated_by = esc_html($report['metadata']['generated_by']);

        $html .= '<h1>' . $title . '</h1>';
        $html .= '<p class="report-meta">Erstellt am: ' . $generated_at . ' von ' . $generated_by . '</p>';

        if (isset($report['metadata']['period'])) {
            $start = esc_html($report['metadata']['period']['start']);
            $end = esc_html($report['metadata']['period']['end']);
            $html .= '<p class="report-meta">Zeitraum: ' . $start . ' bis ' . $end . '</p>';
        }

        return $html;
    }

    /**
     * Generate PDF footer
     *
     * @param array $report Report data
     * @return string PDF HTML footer
     */
    private function generate_pdf_footer($report) {
        return '</body></html>';
    }

    /**
     * Generate seasonal report PDF HTML
     *
     * @param array $data Seasonal data
     * @return string PDF HTML content
     */
    private function generate_seasonal_pdf_html($data) {
        return $this->generate_seasonal_html($data);
    }

    /**
     * Generate date range report PDF HTML
     *
     * @param array $data Date range data
     * @return string PDF HTML content
     */
    private function generate_date_range_pdf_html($data) {
        return $this->generate_date_range_html($data);
    }

    /**
     * Generate compliance report PDF HTML
     *
     * @param array $data Compliance data
     * @return string PDF HTML content
     */
    private function generate_compliance_pdf_html($data) {
        return $this->generate_compliance_html($data);
    }

    /**
     * Generate trend report PDF HTML
     *
     * @param array $data Trend data
     * @return string PDF HTML content
     */
    private function generate_trend_pdf_html($data) {
        return $this->generate_trend_html($data);
    }

    /**
     * Get error report structure
     *
     * @param string $message Error message
     * @return array Error report
     */
    private function get_error_report($message) {
        return [
            'error' => true,
            'message' => esc_html($message),
            'generated_at' => current_time('mysql')
        ];
    }
}
