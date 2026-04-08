<?php
/**
 * Page View Logger Service
 *
 * Handles logging and statistics for page views of shortcodes.
 *
 * Test: Instantiate AHGMH_Page_View_Logger, call
 *       log_page_view( 'abschuss_form', ['species' => 'Rotwild'] ) and
 *       verify an insert_id is returned.
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logs and queries page-view rows in ahgmh_page_views.
 */
class AHGMH_Page_View_Logger {

    /** @var string Fully-qualified table name. */
    private $table_name;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ahgmh_page_views';
    }

    /*
    |----------------------------------------------------------------------
    | Writing
    |----------------------------------------------------------------------
    */

    /**
     * Log a single page view.
     *
     * @param string $shortcode_name Shortcode identifier.
     * @param array  $attributes     Shortcode attributes.
     * @return int|false Insert ID on success, false on failure.
     */
    public function log_page_view( $shortcode_name, $attributes = array() ) {
        global $wpdb;

        $user_id          = get_current_user_id();
        $user_display_name = '';

        if ( $user_id ) {
            $user              = get_userdata( $user_id );
            $user_display_name = $user ? $user->display_name : '';
        }

        $ip_address = get_option( 'ahgmh_log_ip_addresses', true )
            ? $this->get_ip_address()
            : null;

        $referer    = isset( $_SERVER['HTTP_REFERER'] )
            ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
            : '';
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
            : '';

        $data = array(
            'shortcode_name'       => sanitize_text_field( $shortcode_name ),
            'user_id'              => $user_id,
            'user_display_name'    => sanitize_text_field( $user_display_name ),
            'ip_address'           => $ip_address,
            'shortcode_attributes' => wp_json_encode( $attributes ),
            'page_url'             => esc_url_raw( $this->get_current_url() ),
            'referer'              => $referer,
            'user_agent'           => $user_agent,
            'created_at'           => current_time( 'mysql' ),
        );

        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            error_log(
                sprintf( '[AHGMH Page View Logger] Insert failed: %s', $wpdb->last_error )
            );
            return false;
        }

        return $wpdb->insert_id;
    }

    /*
    |----------------------------------------------------------------------
    | Reading
    |----------------------------------------------------------------------
    */

    /**
     * Get page-view rows, optionally filtered.
     *
     * @param array $filters { shortcode_name, user_id, date_from, date_to }
     * @param int   $limit   Rows per page.
     * @param int   $offset  Row offset.
     * @return array Row arrays.
     */
    public function get_statistics( $filters = array(), $limit = 100, $offset = 0 ) {
        global $wpdb;

        list( $where_sql, $values ) = $this->build_where( $filters );

        $values[] = (int) $limit;
        $values[] = (int) $offset;

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built safely above.
        return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
    }

    /**
     * Get total row count matching filters.
     *
     * @param array $filters Optional filters.
     * @return int Total count.
     */
    public function get_total_count( $filters = array() ) {
        global $wpdb;

        list( $where_sql, $values ) = $this->build_where( $filters );

        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";

        if ( $values ) {
            $sql = $wpdb->prepare( $sql, $values );
        }

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Get summary statistics.
     *
     * @param array $filters { date_from, date_to }
     * @return array Summary data.
     */
    public function get_summary( $filters = array() ) {
        global $wpdb;

        // Only date filters for summary.
        $summary_filters = array_intersect_key( $filters, array_flip( array( 'date_from', 'date_to' ) ) );
        list( $where_sql, $values ) = $this->build_where( $summary_filters );

        $prep = function ( $sql ) use ( $wpdb, $values ) {
            return $values ? $wpdb->prepare( $sql, $values ) : $sql;
        };

        $total_views      = (int) $wpdb->get_var( $prep( "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}" ) );
        $unique_users     = (int) $wpdb->get_var( $prep( "SELECT COUNT(DISTINCT user_id) FROM {$this->table_name} WHERE {$where_sql}" ) );
        $anonymous_views  = (int) $wpdb->get_var( $prep( "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql} AND user_id = 0" ) );

        $views_by_shortcode = $wpdb->get_results(
            $prep( "SELECT shortcode_name, COUNT(*) as count FROM {$this->table_name} WHERE {$where_sql} GROUP BY shortcode_name ORDER BY count DESC" ),
            ARRAY_A
        );

        $views_by_day = $wpdb->get_results(
            $prep( "SELECT DATE(created_at) as date, COUNT(*) as count FROM {$this->table_name} WHERE {$where_sql} GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30" ),
            ARRAY_A
        );

        $top_users = $wpdb->get_results(
            $prep( "SELECT user_id, user_display_name, COUNT(*) as count FROM {$this->table_name} WHERE {$where_sql} AND user_id > 0 GROUP BY user_id, user_display_name ORDER BY count DESC LIMIT 10" ),
            ARRAY_A
        );

        return array(
            'total_views'         => $total_views,
            'unique_users'        => $unique_users,
            'anonymous_views'     => $anonymous_views,
            'authenticated_views' => $total_views - $anonymous_views,
            'views_by_shortcode'  => $views_by_shortcode,
            'views_by_day'        => $views_by_day,
            'top_users'           => $top_users,
        );
    }

    /*
    |----------------------------------------------------------------------
    | Maintenance
    |----------------------------------------------------------------------
    */

    /**
     * Delete page-view rows older than a given number of days.
     *
     * @param int $days Days to keep.
     * @return int|false Deleted count, or false on failure.
     */
    public function cleanup_old_logs( $days = 90 ) {
        global $wpdb;

        $threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < %s",
                $threshold
            )
        );
    }

    /**
     * Delete all page-view rows.
     *
     * @return int|false Affected rows, or false on failure.
     */
    public function delete_all_logs() {
        global $wpdb;
        return $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );
    }

    /**
     * Export page views as CSV string.
     *
     * @param array $filters Optional filters.
     * @return string CSV content.
     */
    public function export_csv( $filters = array() ) {
        $rows = $this->get_statistics( $filters, 10000, 0 );

        $csv = "ID,Shortcode,User ID,User Name,IP Address,Attributes,Page URL,Referer,Created At\n";

        foreach ( $rows as $v ) {
            $csv .= sprintf(
                "%d,%s,%d,%s,%s,%s,%s,%s,%s\n",
                $v['id'],
                $this->escape_csv( $v['shortcode_name'] ),
                $v['user_id'],
                $this->escape_csv( $v['user_display_name'] ),
                $this->escape_csv( $v['ip_address'] ?? '' ),
                $this->escape_csv( $v['shortcode_attributes'] ),
                $this->escape_csv( $v['page_url'] ),
                $this->escape_csv( $v['referer'] ),
                $v['created_at']
            );
        }

        return $csv;
    }

    /*
    |----------------------------------------------------------------------
    | Helpers (private)
    |----------------------------------------------------------------------
    */

    /**
     * Build a WHERE clause and parameter array from a filter set.
     *
     * Eliminates duplicated WHERE-building logic across query methods.
     *
     * @param array $filters Possible keys: shortcode_name, user_id, date_from, date_to.
     * @return array [ string $where_sql, array $values ]
     */
    private function build_where( $filters ) {
        $clauses = array( '1=1' );
        $values  = array();

        if ( ! empty( $filters['shortcode_name'] ) ) {
            $clauses[] = 'shortcode_name = %s';
            $values[]  = sanitize_text_field( $filters['shortcode_name'] );
        }

        if ( ! empty( $filters['user_id'] ) ) {
            $clauses[] = 'user_id = %d';
            $values[]  = (int) $filters['user_id'];
        }

        if ( ! empty( $filters['date_from'] ) ) {
            $clauses[] = 'DATE(created_at) >= %s';
            $values[]  = sanitize_text_field( $filters['date_from'] );
        }

        if ( ! empty( $filters['date_to'] ) ) {
            $clauses[] = 'DATE(created_at) <= %s';
            $values[]  = sanitize_text_field( $filters['date_to'] );
        }

        return array( implode( ' AND ', $clauses ), $values );
    }

    /**
     * Escape a single CSV field.
     *
     * @param string $field Raw field value.
     * @return string Escaped field.
     */
    private function escape_csv( $field ) {
        if ( false !== strpos( $field, ',' ) || false !== strpos( $field, '"' ) || false !== strpos( $field, "\n" ) ) {
            return '"' . str_replace( '"', '""', $field ) . '"';
        }
        return $field;
    }

    /**
     * Get the current page URL.
     *
     * @return string Current URL.
     */
    private function get_current_url() {
        $protocol = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https' : 'http';
        $host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
        $uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        return $protocol . '://' . $host . $uri;
    }

    /**
     * Resolve the visitor's IP address, respecting proxy headers.
     *
     * @return string|null IP address, or null when unavailable.
     */
    private function get_ip_address() {
        $keys = array(
            'HTTP_CF_CONNECTING_IP', // CloudFlare.
            'HTTP_X_FORWARDED_FOR',  // Proxy.
            'HTTP_X_REAL_IP',        // Nginx proxy.
            'REMOTE_ADDR',           // Direct connection.
        );

        foreach ( $keys as $key ) {
            if ( ! isset( $_SERVER[ $key ] ) ) {
                continue;
            }

            $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

            // Handle comma-separated IPs (take first one).
            if ( false !== strpos( $ip, ',' ) ) {
                $parts = explode( ',', $ip );
                $ip    = trim( $parts[0] );
            }

            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                if ( get_option( 'ahgmh_anonymize_ip', false ) ) {
                    return $this->anonymize_ip( $ip );
                }
                return $ip;
            }
        }

        return null;
    }

    /**
     * Anonymise an IP address for GDPR compliance.
     *
     * IPv4: zeroes the last octet. IPv6: zeroes the last 80 bits.
     *
     * @param string $ip IP address.
     * @return string Anonymised IP.
     */
    private function anonymize_ip( $ip ) {
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            $parts    = explode( '.', $ip );
            $parts[3] = '0';
            return implode( '.', $parts );
        }

        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            $parts = explode( ':', $ip );
            for ( $i = 3; $i < 8; $i++ ) {
                if ( isset( $parts[ $i ] ) ) {
                    $parts[ $i ] = '0';
                }
            }
            return implode( ':', $parts );
        }

        return $ip;
    }
}
