<?php
/**
 * Report Service Class
 * Business logic for report data aggregation and statistical calculations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Report Service for data aggregation and statistics
 */
class AHGMH_Report_Service {

    private $cache_timeout = 600; // 10 minutes

    /**
     * Get seasonal data for a specific hunting season
     *
     * @param string $season_start Start date (Y-m-d format)
     * @param string $season_end End date (Y-m-d format)
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Seasonal data with aggregations
     */
    public function get_seasonal_data($season_start, $season_end, $species = '', $meldegruppe = '') {
        $cache_key = 'ahgmh_seasonal_data_' . md5($season_start . $season_end . $species . $meldegruppe);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_seasonal_data($season_start, $season_end, $species, $meldegruppe);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Get date range data with custom start and end dates
     *
     * @param string $start_date Start date (Y-m-d format)
     * @param string $end_date End date (Y-m-d format)
     * @param array $filters Optional filters (species, meldegruppe, kategorie)
     * @return array Date range data
     */
    public function get_date_range_data($start_date, $end_date, $filters = []) {
        $cache_key = 'ahgmh_daterange_data_' . md5($start_date . $end_date . serialize($filters));
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_date_range_data($start_date, $end_date, $filters);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Get compliance data comparing actual vs. planned harvests
     *
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Compliance data with status indicators
     */
    public function get_compliance_data($species = '', $meldegruppe = '') {
        $cache_key = 'ahgmh_compliance_data_' . md5($species . $meldegruppe);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_compliance_data($species, $meldegruppe);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Calculate seasonal data
     *
     * @param string $season_start
     * @param string $season_end
     * @param string $species
     * @param string $meldegruppe
     * @return array
     */
    private function calculate_seasonal_data($season_start, $season_end, $species, $meldegruppe) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            // Build WHERE clause
            $where_conditions = ["datum >= %s", "datum <= %s"];
            $where_values = [$season_start, $season_end];

            if (!empty($species)) {
                $where_conditions[] = "art = %s";
                $where_values[] = $species;
            }

            if (!empty($meldegruppe)) {
                $where_conditions[] = "meldegruppe = %s";
                $where_values[] = $meldegruppe;
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Total submissions in season
            $total_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
            $total_submissions = $wpdb->get_var($wpdb->prepare($total_query, $where_values));

            // Total harvests (sum of anzahl)
            $harvest_query = "SELECT SUM(anzahl) FROM $table_name WHERE $where_clause";
            $total_harvests = $wpdb->get_var($wpdb->prepare($harvest_query, $where_values));

            // Species breakdown
            $species_query = "SELECT art as species, SUM(anzahl) as count
                             FROM $table_name
                             WHERE $where_clause
                             GROUP BY art
                             ORDER BY count DESC";
            $species_breakdown = $wpdb->get_results($wpdb->prepare($species_query, $where_values));

            // Category breakdown
            $category_query = "SELECT kategorie as category, SUM(anzahl) as count
                              FROM $table_name
                              WHERE $where_clause
                              GROUP BY kategorie
                              ORDER BY count DESC";
            $category_breakdown = $wpdb->get_results($wpdb->prepare($category_query, $where_values));

            // Meldegruppe breakdown
            $meldegruppe_query = "SELECT meldegruppe, SUM(anzahl) as count
                                 FROM $table_name
                                 WHERE $where_clause
                                 GROUP BY meldegruppe
                                 ORDER BY count DESC";
            $meldegruppe_breakdown = $wpdb->get_results($wpdb->prepare($meldegruppe_query, $where_values));

            // Monthly distribution
            $monthly_query = "SELECT DATE_FORMAT(datum, '%%Y-%%m') as month,
                             SUM(anzahl) as count
                             FROM $table_name
                             WHERE $where_clause
                             GROUP BY DATE_FORMAT(datum, '%%Y-%%m')
                             ORDER BY month ASC";
            $monthly_distribution = $wpdb->get_results($wpdb->prepare($monthly_query, $where_values));

            return [
                'period' => [
                    'start' => $season_start,
                    'end' => $season_end
                ],
                'summary' => [
                    'total_submissions' => absint($total_submissions),
                    'total_harvests' => absint($total_harvests)
                ],
                'species_breakdown' => $this->sanitize_breakdown($species_breakdown),
                'category_breakdown' => $this->sanitize_breakdown($category_breakdown),
                'meldegruppe_breakdown' => $this->sanitize_breakdown($meldegruppe_breakdown),
                'monthly_distribution' => $this->sanitize_monthly_data($monthly_distribution),
                'filters' => [
                    'species' => $species,
                    'meldegruppe' => $meldegruppe
                ],
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Calculate date range data
     *
     * @param string $start_date
     * @param string $end_date
     * @param array $filters
     * @return array
     */
    private function calculate_date_range_data($start_date, $end_date, $filters) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            // Build WHERE clause
            $where_conditions = ["datum >= %s", "datum <= %s"];
            $where_values = [$start_date, $end_date];

            if (!empty($filters['species'])) {
                $where_conditions[] = "art = %s";
                $where_values[] = $filters['species'];
            }

            if (!empty($filters['meldegruppe'])) {
                $where_conditions[] = "meldegruppe = %s";
                $where_values[] = $filters['meldegruppe'];
            }

            if (!empty($filters['kategorie'])) {
                $where_conditions[] = "kategorie = %s";
                $where_values[] = $filters['kategorie'];
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Get all submissions in range
            $submissions_query = "SELECT datum, art, kategorie, meldegruppe, anzahl, wus_nummer
                                 FROM $table_name
                                 WHERE $where_clause
                                 ORDER BY datum DESC";
            $submissions = $wpdb->get_results($wpdb->prepare($submissions_query, $where_values));

            // Statistical summaries
            $stats_query = "SELECT
                              COUNT(*) as submission_count,
                              SUM(anzahl) as total_harvests,
                              COUNT(DISTINCT art) as species_count,
                              COUNT(DISTINCT meldegruppe) as meldegruppe_count
                           FROM $table_name
                           WHERE $where_clause";
            $stats = $wpdb->get_row($wpdb->prepare($stats_query, $where_values));

            return [
                'period' => [
                    'start' => $start_date,
                    'end' => $end_date
                ],
                'statistics' => [
                    'submission_count' => absint($stats->submission_count ?? 0),
                    'total_harvests' => absint($stats->total_harvests ?? 0),
                    'species_count' => absint($stats->species_count ?? 0),
                    'meldegruppe_count' => absint($stats->meldegruppe_count ?? 0)
                ],
                'submissions' => $this->sanitize_submissions($submissions),
                'filters' => $filters,
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Calculate compliance data
     *
     * @param string $species
     * @param string $meldegruppe
     * @return array
     */
    private function calculate_compliance_data($species, $meldegruppe) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            $compliance_data = [];

            // Get all species if none specified
            $species_list = !empty($species) ? [$species] : get_option('ahgmh_species', ['Rotwild', 'Damwild']);

            foreach ($species_list as $current_species) {
                // Get categories for this species
                $categories_key = 'ahgmh_categories_' . sanitize_key($current_species);
                $categories = get_option($categories_key, []);

                // Get limits for this species
                $limits = $this->get_species_limits($current_species, $meldegruppe);

                $species_compliance = [
                    'species' => $current_species,
                    'categories' => [],
                    'total' => [
                        'current' => 0,
                        'limit' => 0,
                        'percentage' => 0,
                        'status' => 'good'
                    ]
                ];

                foreach ($categories as $category) {
                    // Get current count
                    $count_query = "SELECT SUM(anzahl) FROM $table_name
                                   WHERE art = %s AND kategorie = %s";
                    $query_params = [$current_species, $category];

                    if (!empty($meldegruppe)) {
                        $count_query .= " AND meldegruppe = %s";
                        $query_params[] = $meldegruppe;
                    }

                    $current_count = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
                    $current_count = absint($current_count);

                    // Get limit for this category
                    $category_limit = isset($limits[$category]) ? absint($limits[$category]) : 0;

                    // Calculate percentage and status
                    $percentage = $category_limit > 0 ? ($current_count / $category_limit) * 100 : 0;
                    $status = $this->get_compliance_status($percentage);

                    $species_compliance['categories'][$category] = [
                        'current' => $current_count,
                        'limit' => $category_limit,
                        'percentage' => round($percentage, 1),
                        'status' => $status
                    ];

                    // Add to totals
                    $species_compliance['total']['current'] += $current_count;
                    $species_compliance['total']['limit'] += $category_limit;
                }

                // Calculate total percentage and status
                if ($species_compliance['total']['limit'] > 0) {
                    $total_percentage = ($species_compliance['total']['current'] / $species_compliance['total']['limit']) * 100;
                    $species_compliance['total']['percentage'] = round($total_percentage, 1);
                    $species_compliance['total']['status'] = $this->get_compliance_status($total_percentage);
                }

                $compliance_data[] = $species_compliance;
            }

            return [
                'compliance' => $compliance_data,
                'filters' => [
                    'species' => $species,
                    'meldegruppe' => $meldegruppe
                ],
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Get species limits
     *
     * @param string $species
     * @param string $meldegruppe
     * @return array
     */
    private function get_species_limits($species, $meldegruppe) {
        $all_limits = get_option('ahgmh_wildart_limits', []);

        if (!isset($all_limits[$species])) {
            return [];
        }

        $species_limits = $all_limits[$species];

        // If meldegruppe is specified, get meldegruppe-specific limits
        if (!empty($meldegruppe) && isset($species_limits[$meldegruppe]) && is_array($species_limits[$meldegruppe])) {
            return $species_limits[$meldegruppe];
        }

        // Otherwise, sum all meldegruppen limits for hegegemeinschaft total
        $total_limits = [];
        foreach ($species_limits as $mg => $categories) {
            if (is_array($categories)) {
                foreach ($categories as $category => $limit) {
                    if (!isset($total_limits[$category])) {
                        $total_limits[$category] = 0;
                    }
                    $total_limits[$category] += absint($limit);
                }
            }
        }

        return $total_limits;
    }

    /**
     * Get compliance status from percentage
     *
     * @param float $percentage
     * @return string Status: good, warning, critical, exceeded
     */
    private function get_compliance_status($percentage) {
        if ($percentage >= 110) return 'exceeded';
        if ($percentage >= 95) return 'critical';
        if ($percentage >= 80) return 'warning';
        return 'good';
    }

    /**
     * Get detailed compliance by meldegruppe
     *
     * @param string $species Species to analyze
     * @return array Meldegruppe compliance breakdown
     */
    public function get_compliance_by_meldegruppe($species = '') {
        $cache_key = 'ahgmh_compliance_by_mg_' . md5($species);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_compliance_by_meldegruppe($species);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Calculate compliance by meldegruppe
     *
     * @param string $species
     * @return array
     */
    private function calculate_compliance_by_meldegruppe($species) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            $meldegruppen_data = [];

            // Get all species if none specified
            $species_list = !empty($species) ? [$species] : get_option('ahgmh_species', ['Rotwild', 'Damwild']);

            // Get all configured meldegruppen
            $meldegruppen = get_option('ahgmh_meldegruppen', []);

            foreach ($species_list as $current_species) {
                // Get categories for this species
                $categories_key = 'ahgmh_categories_' . sanitize_key($current_species);
                $categories = get_option($categories_key, []);

                // Get all limits for this species
                $all_limits = get_option('ahgmh_wildart_limits', []);
                $species_limits = isset($all_limits[$current_species]) ? $all_limits[$current_species] : [];

                foreach ($meldegruppen as $meldegruppe) {
                    $meldegruppe_compliance = [
                        'meldegruppe' => $meldegruppe,
                        'species' => $current_species,
                        'categories' => [],
                        'total' => [
                            'current' => 0,
                            'limit' => 0,
                            'remaining' => 0,
                            'percentage' => 0,
                            'status' => 'good'
                        ]
                    ];

                    foreach ($categories as $category) {
                        // Get current count for this meldegruppe
                        $count_query = "SELECT SUM(anzahl) FROM $table_name
                                       WHERE art = %s AND kategorie = %s AND meldegruppe = %s";
                        $current_count = $wpdb->get_var($wpdb->prepare($count_query, [$current_species, $category, $meldegruppe]));
                        $current_count = absint($current_count);

                        // Get limit for this meldegruppe and category
                        $category_limit = 0;
                        if (isset($species_limits[$meldegruppe][$category])) {
                            $category_limit = absint($species_limits[$meldegruppe][$category]);
                        }

                        // Calculate percentage, remaining, and status
                        $percentage = $category_limit > 0 ? ($current_count / $category_limit) * 100 : 0;
                        $remaining = max(0, $category_limit - $current_count);
                        $status = $this->get_compliance_status($percentage);

                        $meldegruppe_compliance['categories'][$category] = [
                            'current' => $current_count,
                            'limit' => $category_limit,
                            'remaining' => $remaining,
                            'percentage' => round($percentage, 1),
                            'status' => $status
                        ];

                        // Add to totals
                        $meldegruppe_compliance['total']['current'] += $current_count;
                        $meldegruppe_compliance['total']['limit'] += $category_limit;
                    }

                    // Calculate total percentage, remaining, and status
                    if ($meldegruppe_compliance['total']['limit'] > 0) {
                        $total_percentage = ($meldegruppe_compliance['total']['current'] / $meldegruppe_compliance['total']['limit']) * 100;
                        $meldegruppe_compliance['total']['percentage'] = round($total_percentage, 1);
                        $meldegruppe_compliance['total']['remaining'] = max(0, $meldegruppe_compliance['total']['limit'] - $meldegruppe_compliance['total']['current']);
                        $meldegruppe_compliance['total']['status'] = $this->get_compliance_status($total_percentage);
                    }

                    $meldegruppen_data[] = $meldegruppe_compliance;
                }
            }

            return [
                'meldegruppen' => $meldegruppen_data,
                'filters' => [
                    'species' => $species
                ],
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Get compliance summary across all species
     *
     * @return array Overall compliance summary
     */
    public function get_compliance_summary() {
        $cache_key = 'ahgmh_compliance_summary';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_compliance_summary();
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Calculate compliance summary
     *
     * @return array
     */
    private function calculate_compliance_summary() {
        try {
            $species_list = get_option('ahgmh_species', ['Rotwild', 'Damwild']);
            $summary = [
                'overall' => [
                    'total_current' => 0,
                    'total_limit' => 0,
                    'total_remaining' => 0,
                    'percentage' => 0,
                    'status' => 'good'
                ],
                'by_species' => [],
                'status_counts' => [
                    'good' => 0,
                    'warning' => 0,
                    'critical' => 0,
                    'exceeded' => 0
                ]
            ];

            foreach ($species_list as $species) {
                $compliance_data = $this->calculate_compliance_data($species, '');

                if (isset($compliance_data['compliance'][0])) {
                    $species_data = $compliance_data['compliance'][0];

                    // Add to overall totals
                    $summary['overall']['total_current'] += $species_data['total']['current'];
                    $summary['overall']['total_limit'] += $species_data['total']['limit'];

                    // Calculate remaining
                    $remaining = max(0, $species_data['total']['limit'] - $species_data['total']['current']);

                    // Store species summary
                    $summary['by_species'][$species] = [
                        'current' => $species_data['total']['current'],
                        'limit' => $species_data['total']['limit'],
                        'remaining' => $remaining,
                        'percentage' => $species_data['total']['percentage'],
                        'status' => $species_data['total']['status']
                    ];

                    // Count statuses
                    $status = $species_data['total']['status'];
                    if (isset($summary['status_counts'][$status])) {
                        $summary['status_counts'][$status]++;
                    }
                }
            }

            // Calculate overall percentage and status
            if ($summary['overall']['total_limit'] > 0) {
                $overall_percentage = ($summary['overall']['total_current'] / $summary['overall']['total_limit']) * 100;
                $summary['overall']['percentage'] = round($overall_percentage, 1);
                $summary['overall']['total_remaining'] = max(0, $summary['overall']['total_limit'] - $summary['overall']['total_current']);
                $summary['overall']['status'] = $this->get_compliance_status($overall_percentage);
            }

            $summary['generated_at'] = current_time('mysql');

            return $summary;

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Get remaining harvest capacity for a species/category
     *
     * @param string $species Species name
     * @param string $category Category name (optional)
     * @param string $meldegruppe Meldegruppe (optional)
     * @return array Remaining capacity data
     */
    public function get_remaining_capacity($species, $category = '', $meldegruppe = '') {
        $cache_key = 'ahgmh_remaining_capacity_' . md5($species . $category . $meldegruppe);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_remaining_capacity($species, $category, $meldegruppe);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Calculate remaining capacity
     *
     * @param string $species
     * @param string $category
     * @param string $meldegruppe
     * @return array
     */
    private function calculate_remaining_capacity($species, $category, $meldegruppe) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            // Get limits
            $limits = $this->get_species_limits($species, $meldegruppe);

            $capacity_data = [];

            // If specific category requested
            if (!empty($category)) {
                $categories = [$category];
            } else {
                // Get all categories for species
                $categories_key = 'ahgmh_categories_' . sanitize_key($species);
                $categories = get_option($categories_key, []);
            }

            foreach ($categories as $cat) {
                // Get current count
                $count_query = "SELECT SUM(anzahl) FROM $table_name WHERE art = %s AND kategorie = %s";
                $query_params = [$species, $cat];

                if (!empty($meldegruppe)) {
                    $count_query .= " AND meldegruppe = %s";
                    $query_params[] = $meldegruppe;
                }

                $current_count = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
                $current_count = absint($current_count);

                $limit = isset($limits[$cat]) ? absint($limits[$cat]) : 0;
                $remaining = max(0, $limit - $current_count);
                $percentage = $limit > 0 ? ($current_count / $limit) * 100 : 0;

                $capacity_data[$cat] = [
                    'current' => $current_count,
                    'limit' => $limit,
                    'remaining' => $remaining,
                    'percentage' => round($percentage, 1),
                    'status' => $this->get_compliance_status($percentage),
                    'can_harvest' => $remaining > 0
                ];
            }

            return [
                'species' => $species,
                'meldegruppe' => $meldegruppe,
                'capacity' => $capacity_data,
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Sanitize breakdown array
     *
     * @param array $breakdown
     * @return array
     */
    private function sanitize_breakdown($breakdown) {
        if (!is_array($breakdown)) return [];

        $sanitized = [];
        foreach ($breakdown as $item) {
            $name = '';
            if (isset($item->species)) {
                $name = esc_html($item->species);
            } elseif (isset($item->category)) {
                $name = esc_html($item->category);
            } elseif (isset($item->meldegruppe)) {
                $name = esc_html($item->meldegruppe);
            }

            $sanitized[] = [
                'name' => $name,
                'count' => absint($item->count ?? 0)
            ];
        }

        return $sanitized;
    }

    /**
     * Sanitize monthly data
     *
     * @param array $monthly_data
     * @return array
     */
    private function sanitize_monthly_data($monthly_data) {
        if (!is_array($monthly_data)) return [];

        $sanitized = [];
        foreach ($monthly_data as $item) {
            $sanitized[] = [
                'month' => esc_html($item->month ?? ''),
                'count' => absint($item->count ?? 0)
            ];
        }

        return $sanitized;
    }

    /**
     * Sanitize submissions array
     *
     * @param array $submissions
     * @return array
     */
    private function sanitize_submissions($submissions) {
        if (!is_array($submissions)) return [];

        $sanitized = [];
        foreach ($submissions as $submission) {
            $sanitized[] = [
                'datum' => esc_html($submission->datum ?? ''),
                'art' => esc_html($submission->art ?? ''),
                'kategorie' => esc_html($submission->kategorie ?? ''),
                'meldegruppe' => esc_html($submission->meldegruppe ?? ''),
                'anzahl' => absint($submission->anzahl ?? 0),
                'wus_nummer' => esc_html($submission->wus_nummer ?? '')
            ];
        }

        return $sanitized;
    }

    /**
     * Get fallback data when errors occur
     *
     * @return array
     */
    private function get_fallback_data() {
        return [
            'error' => true,
            'message' => 'Fehler beim Laden der Berichtsdaten',
            'generated_at' => current_time('mysql')
        ];
    }

    /**
     * Get year-over-year comparison between current and previous hunting seasons
     *
     * @param string $current_season_start Current season start date (Y-m-d)
     * @param string $current_season_end Current season end date (Y-m-d)
     * @param string $previous_season_start Previous season start date (Y-m-d)
     * @param string $previous_season_end Previous season end date (Y-m-d)
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Year-over-year comparison data
     */
    public function get_year_over_year_comparison($current_season_start, $current_season_end, $previous_season_start, $previous_season_end, $species = '', $meldegruppe = '') {
        $cache_key = 'ahgmh_yoy_comparison_' . md5($current_season_start . $current_season_end . $previous_season_start . $previous_season_end . $species . $meldegruppe);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_year_over_year_comparison($current_season_start, $current_season_end, $previous_season_start, $previous_season_end, $species, $meldegruppe);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Calculate year-over-year comparison
     *
     * @param string $current_season_start
     * @param string $current_season_end
     * @param string $previous_season_start
     * @param string $previous_season_end
     * @param string $species
     * @param string $meldegruppe
     * @return array
     */
    private function calculate_year_over_year_comparison($current_season_start, $current_season_end, $previous_season_start, $previous_season_end, $species, $meldegruppe) {
        try {
            // Get current season data
            $current_data = $this->calculate_seasonal_data($current_season_start, $current_season_end, $species, $meldegruppe);

            // Get previous season data
            $previous_data = $this->calculate_seasonal_data($previous_season_start, $previous_season_end, $species, $meldegruppe);

            // Calculate changes
            $current_harvests = $current_data['summary']['total_harvests'];
            $previous_harvests = $previous_data['summary']['total_harvests'];
            $harvest_change = $current_harvests - $previous_harvests;
            $harvest_change_percent = $previous_harvests > 0 ? (($harvest_change / $previous_harvests) * 100) : 0;

            $current_submissions = $current_data['summary']['total_submissions'];
            $previous_submissions = $previous_data['summary']['total_submissions'];
            $submission_change = $current_submissions - $previous_submissions;
            $submission_change_percent = $previous_submissions > 0 ? (($submission_change / $previous_submissions) * 100) : 0;

            // Compare species breakdowns
            $species_comparison = $this->compare_breakdowns(
                $current_data['species_breakdown'],
                $previous_data['species_breakdown']
            );

            // Compare category breakdowns
            $category_comparison = $this->compare_breakdowns(
                $current_data['category_breakdown'],
                $previous_data['category_breakdown']
            );

            // Compare meldegruppe breakdowns
            $meldegruppe_comparison = $this->compare_breakdowns(
                $current_data['meldegruppe_breakdown'],
                $previous_data['meldegruppe_breakdown']
            );

            return [
                'current_season' => [
                    'period' => $current_data['period'],
                    'total_harvests' => $current_harvests,
                    'total_submissions' => $current_submissions
                ],
                'previous_season' => [
                    'period' => $previous_data['period'],
                    'total_harvests' => $previous_harvests,
                    'total_submissions' => $previous_submissions
                ],
                'changes' => [
                    'harvest_change' => $harvest_change,
                    'harvest_change_percent' => round($harvest_change_percent, 1),
                    'submission_change' => $submission_change,
                    'submission_change_percent' => round($submission_change_percent, 1)
                ],
                'comparisons' => [
                    'species' => $species_comparison,
                    'categories' => $category_comparison,
                    'meldegruppen' => $meldegruppe_comparison
                ],
                'filters' => [
                    'species' => $species,
                    'meldegruppe' => $meldegruppe
                ],
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Get monthly trends and submission rates
     *
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Monthly trends data
     */
    public function get_monthly_trends($start_date, $end_date, $species = '', $meldegruppe = '') {
        $cache_key = 'ahgmh_monthly_trends_' . md5($start_date . $end_date . $species . $meldegruppe);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_monthly_trends($start_date, $end_date, $species, $meldegruppe);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Calculate monthly trends
     *
     * @param string $start_date
     * @param string $end_date
     * @param string $species
     * @param string $meldegruppe
     * @return array
     */
    private function calculate_monthly_trends($start_date, $end_date, $species, $meldegruppe) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            // Build WHERE clause
            $where_conditions = ["datum >= %s", "datum <= %s"];
            $where_values = [$start_date, $end_date];

            if (!empty($species)) {
                $where_conditions[] = "art = %s";
                $where_values[] = $species;
            }

            if (!empty($meldegruppe)) {
                $where_conditions[] = "meldegruppe = %s";
                $where_values[] = $meldegruppe;
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Get monthly aggregations
            $monthly_query = "SELECT
                                DATE_FORMAT(datum, '%%Y-%%m') as month,
                                COUNT(*) as submission_count,
                                SUM(anzahl) as harvest_count,
                                COUNT(DISTINCT art) as species_count,
                                COUNT(DISTINCT meldegruppe) as meldegruppe_count
                             FROM $table_name
                             WHERE $where_clause
                             GROUP BY DATE_FORMAT(datum, '%%Y-%%m')
                             ORDER BY month ASC";
            $monthly_data = $wpdb->get_results($wpdb->prepare($monthly_query, $where_values));

            // Calculate trends (month-over-month changes)
            $trends = [];
            $previous_month = null;

            foreach ($monthly_data as $current_month) {
                $month_trend = [
                    'month' => esc_html($current_month->month),
                    'submission_count' => absint($current_month->submission_count),
                    'harvest_count' => absint($current_month->harvest_count),
                    'species_count' => absint($current_month->species_count),
                    'meldegruppe_count' => absint($current_month->meldegruppe_count),
                    'submission_rate' => 0,
                    'harvest_rate' => 0,
                    'trend' => 'stable'
                ];

                if ($previous_month !== null) {
                    // Calculate submission rate change
                    $submission_change = absint($current_month->submission_count) - absint($previous_month->submission_count);
                    $submission_rate = absint($previous_month->submission_count) > 0
                        ? ($submission_change / absint($previous_month->submission_count)) * 100
                        : 0;

                    // Calculate harvest rate change
                    $harvest_change = absint($current_month->harvest_count) - absint($previous_month->harvest_count);
                    $harvest_rate = absint($previous_month->harvest_count) > 0
                        ? ($harvest_change / absint($previous_month->harvest_count)) * 100
                        : 0;

                    $month_trend['submission_rate'] = round($submission_rate, 1);
                    $month_trend['harvest_rate'] = round($harvest_rate, 1);

                    // Determine trend direction
                    $avg_rate = ($submission_rate + $harvest_rate) / 2;
                    if ($avg_rate > 10) {
                        $month_trend['trend'] = 'increasing';
                    } elseif ($avg_rate < -10) {
                        $month_trend['trend'] = 'decreasing';
                    } else {
                        $month_trend['trend'] = 'stable';
                    }
                }

                $trends[] = $month_trend;
                $previous_month = $current_month;
            }

            // Calculate overall statistics
            $total_submissions = array_sum(array_column($trends, 'submission_count'));
            $total_harvests = array_sum(array_column($trends, 'harvest_count'));
            $avg_submissions_per_month = count($trends) > 0 ? $total_submissions / count($trends) : 0;
            $avg_harvests_per_month = count($trends) > 0 ? $total_harvests / count($trends) : 0;

            return [
                'period' => [
                    'start' => $start_date,
                    'end' => $end_date
                ],
                'monthly_trends' => $trends,
                'statistics' => [
                    'total_months' => count($trends),
                    'total_submissions' => $total_submissions,
                    'total_harvests' => $total_harvests,
                    'avg_submissions_per_month' => round($avg_submissions_per_month, 1),
                    'avg_harvests_per_month' => round($avg_harvests_per_month, 1)
                ],
                'filters' => [
                    'species' => $species,
                    'meldegruppe' => $meldegruppe
                ],
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Get seasonal patterns and peak activity periods
     *
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Seasonal patterns data
     */
    public function get_seasonal_patterns($start_date, $end_date, $species = '', $meldegruppe = '') {
        $cache_key = 'ahgmh_seasonal_patterns_' . md5($start_date . $end_date . $species . $meldegruppe);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $this->calculate_seasonal_patterns($start_date, $end_date, $species, $meldegruppe);
        set_transient($cache_key, $data, $this->cache_timeout);

        return $data;
    }

    /**
     * Calculate seasonal patterns
     *
     * @param string $start_date
     * @param string $end_date
     * @param string $species
     * @param string $meldegruppe
     * @return array
     */
    private function calculate_seasonal_patterns($start_date, $end_date, $species, $meldegruppe) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        try {
            // Build WHERE clause
            $where_conditions = ["datum >= %s", "datum <= %s"];
            $where_values = [$start_date, $end_date];

            if (!empty($species)) {
                $where_conditions[] = "art = %s";
                $where_values[] = $species;
            }

            if (!empty($meldegruppe)) {
                $where_conditions[] = "meldegruppe = %s";
                $where_values[] = $meldegruppe;
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Get activity by month (regardless of year)
            $month_pattern_query = "SELECT
                                      DATE_FORMAT(datum, '%%m') as month_number,
                                      MONTHNAME(datum) as month_name,
                                      COUNT(*) as submission_count,
                                      SUM(anzahl) as harvest_count
                                   FROM $table_name
                                   WHERE $where_clause
                                   GROUP BY DATE_FORMAT(datum, '%%m'), MONTHNAME(datum)
                                   ORDER BY month_number ASC";
            $month_patterns = $wpdb->get_results($wpdb->prepare($month_pattern_query, $where_values));

            // Get activity by day of week
            $weekday_pattern_query = "SELECT
                                        DAYOFWEEK(datum) as weekday_number,
                                        DAYNAME(datum) as weekday_name,
                                        COUNT(*) as submission_count,
                                        SUM(anzahl) as harvest_count
                                      FROM $table_name
                                      WHERE $where_clause
                                      GROUP BY DAYOFWEEK(datum), DAYNAME(datum)
                                      ORDER BY weekday_number ASC";
            $weekday_patterns = $wpdb->get_results($wpdb->prepare($weekday_pattern_query, $where_values));

            // Identify peak periods
            $peaks = $this->identify_peak_periods($month_patterns, $weekday_patterns);

            // Sanitize monthly patterns
            $sanitized_months = [];
            foreach ($month_patterns as $pattern) {
                $sanitized_months[] = [
                    'month_number' => esc_html($pattern->month_number),
                    'month_name' => esc_html($pattern->month_name),
                    'submission_count' => absint($pattern->submission_count),
                    'harvest_count' => absint($pattern->harvest_count)
                ];
            }

            // Sanitize weekday patterns
            $sanitized_weekdays = [];
            foreach ($weekday_patterns as $pattern) {
                $sanitized_weekdays[] = [
                    'weekday_number' => absint($pattern->weekday_number),
                    'weekday_name' => esc_html($pattern->weekday_name),
                    'submission_count' => absint($pattern->submission_count),
                    'harvest_count' => absint($pattern->harvest_count)
                ];
            }

            return [
                'period' => [
                    'start' => $start_date,
                    'end' => $end_date
                ],
                'monthly_patterns' => $sanitized_months,
                'weekday_patterns' => $sanitized_weekdays,
                'peak_periods' => $peaks,
                'filters' => [
                    'species' => $species,
                    'meldegruppe' => $meldegruppe
                ],
                'generated_at' => current_time('mysql')
            ];

        } catch (Exception $e) {
            return $this->get_fallback_data();
        }
    }

    /**
     * Identify peak activity periods from patterns
     *
     * @param array $month_patterns Monthly patterns
     * @param array $weekday_patterns Weekday patterns
     * @return array Peak periods data
     */
    private function identify_peak_periods($month_patterns, $weekday_patterns) {
        $peaks = [
            'peak_months' => [],
            'peak_weekdays' => [],
            'peak_month' => null,
            'peak_weekday' => null
        ];

        // Find peak month(s)
        if (!empty($month_patterns)) {
            $max_harvest = 0;
            foreach ($month_patterns as $pattern) {
                $harvest_count = absint($pattern->harvest_count);
                if ($harvest_count > $max_harvest) {
                    $max_harvest = $harvest_count;
                }
            }

            // Get months with harvests >= 80% of peak
            $threshold = $max_harvest * 0.8;
            foreach ($month_patterns as $pattern) {
                $harvest_count = absint($pattern->harvest_count);
                if ($harvest_count >= $threshold && $harvest_count > 0) {
                    $peaks['peak_months'][] = [
                        'month_name' => esc_html($pattern->month_name),
                        'harvest_count' => $harvest_count,
                        'is_peak' => $harvest_count === $max_harvest
                    ];

                    if ($harvest_count === $max_harvest && $peaks['peak_month'] === null) {
                        $peaks['peak_month'] = esc_html($pattern->month_name);
                    }
                }
            }
        }

        // Find peak weekday(s)
        if (!empty($weekday_patterns)) {
            $max_harvest = 0;
            foreach ($weekday_patterns as $pattern) {
                $harvest_count = absint($pattern->harvest_count);
                if ($harvest_count > $max_harvest) {
                    $max_harvest = $harvest_count;
                }
            }

            // Get weekdays with harvests >= 80% of peak
            $threshold = $max_harvest * 0.8;
            foreach ($weekday_patterns as $pattern) {
                $harvest_count = absint($pattern->harvest_count);
                if ($harvest_count >= $threshold && $harvest_count > 0) {
                    $peaks['peak_weekdays'][] = [
                        'weekday_name' => esc_html($pattern->weekday_name),
                        'harvest_count' => $harvest_count,
                        'is_peak' => $harvest_count === $max_harvest
                    ];

                    if ($harvest_count === $max_harvest && $peaks['peak_weekday'] === null) {
                        $peaks['peak_weekday'] = esc_html($pattern->weekday_name);
                    }
                }
            }
        }

        return $peaks;
    }

    /**
     * Compare two breakdown arrays and calculate differences
     *
     * @param array $current_breakdown Current period breakdown
     * @param array $previous_breakdown Previous period breakdown
     * @return array Comparison with changes
     */
    private function compare_breakdowns($current_breakdown, $previous_breakdown) {
        $comparison = [];

        // Create lookup for previous data
        $previous_lookup = [];
        foreach ($previous_breakdown as $item) {
            $previous_lookup[$item['name']] = $item['count'];
        }

        // Compare current with previous
        foreach ($current_breakdown as $item) {
            $name = $item['name'];
            $current_count = $item['count'];
            $previous_count = isset($previous_lookup[$name]) ? $previous_lookup[$name] : 0;

            $change = $current_count - $previous_count;
            $change_percent = $previous_count > 0 ? (($change / $previous_count) * 100) : 0;

            $comparison[] = [
                'name' => $name,
                'current' => $current_count,
                'previous' => $previous_count,
                'change' => $change,
                'change_percent' => round($change_percent, 1)
            ];

            // Remove from previous lookup
            unset($previous_lookup[$name]);
        }

        // Add items that only exist in previous (now at zero)
        foreach ($previous_lookup as $name => $previous_count) {
            $comparison[] = [
                'name' => $name,
                'current' => 0,
                'previous' => $previous_count,
                'change' => -$previous_count,
                'change_percent' => -100.0
            ];
        }

        return $comparison;
    }

    /**
     * Clear all report caches
     */
    public function clear_cache() {
        global $wpdb;

        // Delete all transients related to reports and compliance
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_ahgmh_seasonal_data_%'
                OR option_name LIKE '_transient_ahgmh_daterange_data_%'
                OR option_name LIKE '_transient_ahgmh_compliance_data_%'
                OR option_name LIKE '_transient_ahgmh_compliance_by_mg_%'
                OR option_name LIKE '_transient_ahgmh_compliance_summary%'
                OR option_name LIKE '_transient_ahgmh_remaining_capacity_%'
                OR option_name LIKE '_transient_ahgmh_yoy_comparison_%'
                OR option_name LIKE '_transient_ahgmh_monthly_trends_%'
                OR option_name LIKE '_transient_ahgmh_seasonal_patterns_%'
                OR option_name LIKE '_transient_timeout_ahgmh_seasonal_data_%'
                OR option_name LIKE '_transient_timeout_ahgmh_daterange_data_%'
                OR option_name LIKE '_transient_timeout_ahgmh_compliance_data_%'
                OR option_name LIKE '_transient_timeout_ahgmh_compliance_by_mg_%'
                OR option_name LIKE '_transient_timeout_ahgmh_compliance_summary%'
                OR option_name LIKE '_transient_timeout_ahgmh_remaining_capacity_%'
                OR option_name LIKE '_transient_timeout_ahgmh_yoy_comparison_%'
                OR option_name LIKE '_transient_timeout_ahgmh_monthly_trends_%'
                OR option_name LIKE '_transient_timeout_ahgmh_seasonal_patterns_%'"
        );
    }
}
