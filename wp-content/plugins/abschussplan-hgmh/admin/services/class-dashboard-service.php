<?php
/**
 * Dashboard Service Class
 * Business logic for dashboard statistics and calculations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Service for statistics and calculations
 */
class AHGMH_Dashboard_Service {
    
    private $cache_timeout = 300; // 5 minutes
    
    /**
     * Get dashboard statistics with caching
     */
    public function get_dashboard_stats() {
        $cached = get_transient('ahgmh_dashboard_stats');
        if ($cached !== false) {
            return $cached;
        }
        
        $stats = $this->calculate_dashboard_stats();
        set_transient('ahgmh_dashboard_stats', $stats, $this->cache_timeout);
        
        return $stats;
    }
    
    /**
     * Calculate dashboard statistics
     */
    private function calculate_dashboard_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';
        
        try {
            // Total submissions
            $total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            // This month submissions
            $submissions_this_month = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE MONTH(datum) = %d AND YEAR(datum) = %d",
                date('n'),
                date('Y')
            ));
            
            // This week submissions
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $submissions_this_week = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE datum >= %s",
                $week_start
            ));
            
            // Species breakdown
            $species_stats = $wpdb->get_results(
                "SELECT art as species, COUNT(*) as count FROM $table_name GROUP BY art ORDER BY count DESC"
            );
            
            // Recent submissions
            $recent_submissions = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
                5
            ));
            
            // Top meldegruppen
            $top_meldegruppen = $wpdb->get_results(
                "SELECT meldegruppe, COUNT(*) as count FROM $table_name GROUP BY meldegruppe ORDER BY count DESC LIMIT 5"
            );
            
            // Monthly trend (last 12 months)
            $monthly_trend = $this->get_monthly_trend();
            
            return [
                'total_submissions' => absint($total_submissions),
                'submissions_this_month' => absint($submissions_this_month),
                'submissions_this_week' => absint($submissions_this_week),
                'species_stats' => $this->sanitize_stats_array($species_stats),
                'recent_submissions' => $this->sanitize_submissions_array($recent_submissions),
                'top_meldegruppen' => $this->sanitize_stats_array($top_meldegruppen),
                'monthly_trend' => $monthly_trend,
                'last_updated' => current_time('mysql')
            ];
            
        } catch (Exception $e) {
            return $this->get_fallback_stats();
        }
    }
    
    /**
     * Get monthly trend data for charts
     */
    private function get_monthly_trend() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';
        
        try {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(datum, '%%Y-%%m') as month_year,
                    COUNT(*) as count
                FROM $table_name 
                WHERE datum >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(datum, '%%Y-%%m')
                ORDER BY month_year ASC"
            ));
            
            $trend = [];
            foreach ($results as $result) {
                $trend[] = [
                    'month' => esc_html($result->month_year),
                    'count' => absint($result->count)
                ];
            }
            
            return $trend;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get species progress with limits
     */
    public function get_species_progress() {
        $wildarten = get_option('ahgmh_species', []);
        $progress = [];
        
        foreach ($wildarten as $species) {
            $current = $this->get_species_count($species);
            $limit = $this->get_species_limit($species);
            
            $percentage = $limit > 0 ? ($current / $limit) * 100 : 0;
            $status = $this->get_status_from_percentage($percentage);
            
            $progress[$species] = [
                'current' => $current,
                'limit' => $limit,
                'percentage' => round($percentage, 1),
                'status' => $status
            ];
        }
        
        return $progress;
    }
    
    /**
     * Get count for specific species
     */
    private function get_species_count($species) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(anzahl) FROM $table_name WHERE art = %s",
            $species
        ));
        
        return absint($count);
    }
    
    /**
     * Get limit for specific species
     */
    private function get_species_limit($species) {
        $limits = get_option('ahgmh_wildart_limits', []);
        
        if (isset($limits[$species])) {
            $total = 0;
            foreach ($limits[$species] as $meldegruppe => $categories) {
                if (is_array($categories)) {
                    $total += array_sum(array_map('absint', $categories));
                }
            }
            return $total;
        }
        
        return 100; // Default limit
    }
    
    /**
     * Get status from percentage
     */
    private function get_status_from_percentage($percentage) {
        if ($percentage >= 110) return 'exceeded';
        if ($percentage >= 95) return 'critical';
        if ($percentage >= 80) return 'warning';
        return 'good';
    }
    
    /**
     * Sanitize statistics array
     */
    private function sanitize_stats_array($stats) {
        if (!is_array($stats)) return [];
        
        $sanitized = [];
        foreach ($stats as $stat) {
            if (isset($stat->species) || isset($stat->meldegruppe)) {
                $sanitized[] = [
                    'name' => esc_html($stat->species ?? $stat->meldegruppe ?? ''),
                    'count' => absint($stat->count ?? 0)
                ];
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize submissions array
     */
    private function sanitize_submissions_array($submissions) {
        if (!is_array($submissions)) return [];
        
        $sanitized = [];
        foreach ($submissions as $submission) {
            $sanitized[] = [
                'id' => absint($submission->id ?? 0),
                'datum' => esc_html($submission->datum ?? ''),
                'art' => esc_html($submission->art ?? ''),
                'kategorie' => esc_html($submission->kategorie ?? ''),
                'meldegruppe' => esc_html($submission->meldegruppe ?? ''),
                'anzahl' => absint($submission->anzahl ?? 0),
                'created_at' => esc_html($submission->created_at ?? '')
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Get fallback stats when database fails
     */
    private function get_fallback_stats() {
        return [
            'total_submissions' => 0,
            'submissions_this_month' => 0,
            'submissions_this_week' => 0,
            'species_stats' => [],
            'recent_submissions' => [],
            'top_meldegruppen' => [],
            'monthly_trend' => [],
            'last_updated' => current_time('mysql'),
            'error' => true
        ];
    }
    
    /**
     * Clear dashboard cache
     */
    public function clear_cache() {
        delete_transient('ahgmh_dashboard_stats');
    }
}
