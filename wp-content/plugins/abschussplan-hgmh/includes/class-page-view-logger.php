<?php
/**
 * Page View Logger Service Class
 *
 * Handles logging and statistics for page views of shortcodes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for logging and tracking page views
 */
class AHGMH_Page_View_Logger {
    /**
     * Table name for page views
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ahgmh_page_views';
    }

    /**
     * Log a page view
     *
     * @param string $shortcode_name Name of the shortcode
     * @param array $attributes Shortcode attributes
     * @return int|false Insert ID on success, false on failure
     */
    public function log_page_view($shortcode_name, $attributes = array()) {
        global $wpdb;

        // Get current user info
        $user_id = get_current_user_id();
        $user_display_name = '';

        if ($user_id) {
            $user = get_userdata($user_id);
            $user_display_name = $user ? $user->display_name : '';
        }

        // Get IP address (with option to disable)
        $ip_address = $this->get_ip_address();

        // Check if IP logging is disabled
        if (!get_option('ahgmh_log_ip_addresses', true)) {
            $ip_address = null;
        }

        // Get page URL and referer
        $page_url = $this->get_current_url();
        $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        // Prepare data
        $data = array(
            'shortcode_name' => sanitize_text_field($shortcode_name),
            'user_id' => $user_id,
            'user_display_name' => sanitize_text_field($user_display_name),
            'ip_address' => $ip_address,
            'shortcode_attributes' => wp_json_encode($attributes),
            'page_url' => esc_url_raw($page_url),
            'referer' => $referer,
            'user_agent' => $user_agent,
            'created_at' => current_time('mysql')
        );

        // Insert into database
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get page view statistics
     *
     * @param array $filters Optional filters (shortcode_name, user_id, date_from, date_to)
     * @param int $limit Number of results to return
     * @param int $offset Offset for pagination
     * @return array Statistics data
     */
    public function get_statistics($filters = array(), $limit = 100, $offset = 0) {
        global $wpdb;

        // Build WHERE clause
        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($filters['shortcode_name'])) {
            $where_clauses[] = 'shortcode_name = %s';
            $where_values[] = sanitize_text_field($filters['shortcode_name']);
        }

        if (!empty($filters['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = intval($filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'DATE(created_at) >= %s';
            $where_values[] = sanitize_text_field($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'DATE(created_at) <= %s';
            $where_values[] = sanitize_text_field($filters['date_to']);
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Prepare query
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = intval($limit);
        $where_values[] = intval($offset);

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    /**
     * Get total count for pagination
     *
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function get_total_count($filters = array()) {
        global $wpdb;

        // Build WHERE clause
        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($filters['shortcode_name'])) {
            $where_clauses[] = 'shortcode_name = %s';
            $where_values[] = sanitize_text_field($filters['shortcode_name']);
        }

        if (!empty($filters['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = intval($filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'DATE(created_at) >= %s';
            $where_values[] = sanitize_text_field($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'DATE(created_at) <= %s';
            $where_values[] = sanitize_text_field($filters['date_to']);
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Prepare query
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return intval($wpdb->get_var($query));
    }

    /**
     * Get summary statistics
     *
     * @param array $filters Optional filters
     * @return array Summary data
     */
    public function get_summary($filters = array()) {
        global $wpdb;

        // Build WHERE clause
        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'DATE(created_at) >= %s';
            $where_values[] = sanitize_text_field($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'DATE(created_at) <= %s';
            $where_values[] = sanitize_text_field($filters['date_to']);
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Total views
        $total_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";
        if (!empty($where_values)) {
            $total_query = $wpdb->prepare($total_query, $where_values);
        }
        $total_views = intval($wpdb->get_var($total_query));

        // Unique users
        $users_query = "SELECT COUNT(DISTINCT user_id) FROM {$this->table_name} WHERE {$where_sql}";
        if (!empty($where_values)) {
            $users_query = $wpdb->prepare($users_query, $where_values);
        }
        $unique_users = intval($wpdb->get_var($users_query));

        // Anonymous views (user_id = 0)
        $anon_where_clauses = $where_clauses;
        $anon_where_clauses[] = 'user_id = 0';
        $anon_where_sql = implode(' AND ', $anon_where_clauses);
        $anon_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$anon_where_sql}";
        if (!empty($where_values)) {
            $anon_query = $wpdb->prepare($anon_query, $where_values);
        }
        $anonymous_views = intval($wpdb->get_var($anon_query));

        // Views by shortcode
        $shortcode_query = "SELECT shortcode_name, COUNT(*) as count FROM {$this->table_name} WHERE {$where_sql} GROUP BY shortcode_name ORDER BY count DESC";
        if (!empty($where_values)) {
            $shortcode_query = $wpdb->prepare($shortcode_query, $where_values);
        }
        $views_by_shortcode = $wpdb->get_results($shortcode_query, ARRAY_A);

        // Views by day (last 30 days)
        $daily_query = "SELECT DATE(created_at) as date, COUNT(*) as count FROM {$this->table_name} WHERE {$where_sql} GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30";
        if (!empty($where_values)) {
            $daily_query = $wpdb->prepare($daily_query, $where_values);
        }
        $views_by_day = $wpdb->get_results($daily_query, ARRAY_A);

        // Top users
        $top_users_query = "SELECT user_id, user_display_name, COUNT(*) as count FROM {$this->table_name} WHERE {$where_sql} AND user_id > 0 GROUP BY user_id, user_display_name ORDER BY count DESC LIMIT 10";
        if (!empty($where_values)) {
            $top_users_query = $wpdb->prepare($top_users_query, $where_values);
        }
        $top_users = $wpdb->get_results($top_users_query, ARRAY_A);

        return array(
            'total_views' => $total_views,
            'unique_users' => $unique_users,
            'anonymous_views' => $anonymous_views,
            'authenticated_views' => $total_views - $anonymous_views,
            'views_by_shortcode' => $views_by_shortcode,
            'views_by_day' => $views_by_day,
            'top_users' => $top_users
        );
    }

    /**
     * Clean up old logs
     *
     * @param int $days Number of days to keep (older logs will be deleted)
     * @return int|false Number of deleted rows or false on failure
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < %s",
                $date_threshold
            )
        );

        return $result;
    }

    /**
     * Delete all logs
     *
     * @return int|false Number of deleted rows or false on failure
     */
    public function delete_all_logs() {
        global $wpdb;

        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }

    /**
     * Export page views as CSV
     *
     * @param array $filters Optional filters
     * @return string CSV content
     */
    public function export_csv($filters = array()) {
        $page_views = $this->get_statistics($filters, 10000, 0); // Get up to 10k records

        // Create CSV content
        $csv_content = "ID,Shortcode,User ID,User Name,IP Address,Attributes,Page URL,Referer,Created At\n";

        foreach ($page_views as $view) {
            $csv_content .= sprintf(
                "%d,%s,%d,%s,%s,%s,%s,%s,%s\n",
                $view['id'],
                $this->escape_csv($view['shortcode_name']),
                $view['user_id'],
                $this->escape_csv($view['user_display_name']),
                $this->escape_csv($view['ip_address'] ?? ''),
                $this->escape_csv($view['shortcode_attributes']),
                $this->escape_csv($view['page_url']),
                $this->escape_csv($view['referer']),
                $view['created_at']
            );
        }

        return $csv_content;
    }

    /**
     * Escape CSV field
     *
     * @param string $field Field to escape
     * @return string Escaped field
     */
    private function escape_csv($field) {
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }

    /**
     * Get current page URL
     *
     * @return string Current URL
     */
    private function get_current_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field($_SERVER['HTTP_HOST']) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';

        return $protocol . '://' . $host . $uri;
    }

    /**
     * Get visitor IP address
     *
     * @return string|null IP address or null if disabled
     */
    private function get_ip_address() {
        // Check various headers for IP (considering proxies)
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // CloudFlare
            'HTTP_X_FORWARDED_FOR',  // Proxy
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR'            // Direct connection
        );

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);

                // Handle comma-separated IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    // Anonymize IP if option is enabled
                    if (get_option('ahgmh_anonymize_ip', false)) {
                        return $this->anonymize_ip($ip);
                    }
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Anonymize IP address (GDPR-compliant)
     *
     * @param string $ip IP address
     * @return string Anonymized IP
     */
    private function anonymize_ip($ip) {
        // IPv4: Remove last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        // IPv6: Remove last 80 bits (keep first 48 bits)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            for ($i = 3; $i < 8; $i++) {
                if (isset($parts[$i])) {
                    $parts[$i] = '0';
                }
            }
            return implode(':', $parts);
        }

        return $ip;
    }
}
