<?php
/**
 * Activity Logger Service Class
 *
 * Handles logging of user activities for statistics and compliance.
 *
 * Test: Instantiate AHGMH_Activity_Logger, call log( 'form_submit', [] ),
 *       and verify an insert_id is returned.  An invalid action name
 *       must return false.
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logs and tracks user activities in the ahgmh_activity_log table.
 */
class AHGMH_Activity_Logger {

    /** @var string Fully-qualified table name. */
    private $table_name;

    /** @var string[] Allowed action strings. */
    private const VALID_ACTIONS = array(
        'form_view',
        'form_submit',
        'email_verify',
        'submission_approve',
        'submission_reject',
        'submission_edit',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'hgmh_activity_log';
    }

    /**
     * Log an activity.
     *
     * @param string $action  Action type -- must be one of VALID_ACTIONS.
     * @param array  $context Additional context data (JSON-serialisable).
     * @return int|false Insert ID on success, false on failure.
     */
    public function log( $action, $context = array() ) {
        global $wpdb;

        if ( ! in_array( $action, self::VALID_ACTIONS, true ) ) {
            return false;
        }

        $ip_address = $this->get_ip_address();
        $ip_hash    = $ip_address ? hash( 'sha256', $ip_address ) : null;

        $data = array(
            'user_id'         => get_current_user_id(),
            'action'          => sanitize_text_field( $action ),
            'details'         => wp_json_encode( $context ),
            'ip_address_hash' => $ip_hash,
            'created_at'      => current_time( 'mysql' ),
        );

        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array( '%d', '%s', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            error_log(
                sprintf(
                    '[AHGMH Activity Logger] Insert failed: %s',
                    $wpdb->last_error
                )
            );
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get activity statistics, optionally filtered.
     *
     * @param array $filters {
     *     Optional. Filters to apply.
     *
     *     @type string $action    Action type.
     *     @type int    $user_id   User ID.
     *     @type string $date_from Start date (Y-m-d).
     *     @type string $date_to   End date (Y-m-d).
     * }
     * @return array { total_count: int, breakdown: array, filters: array }
     */
    public function get_stats( $filters = array() ) {
        global $wpdb;

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $filters['action'] ) ) {
            $where[]  = 'action = %s';
            $values[] = sanitize_text_field( $filters['action'] );
        }

        if ( ! empty( $filters['user_id'] ) ) {
            $where[]  = 'user_id = %d';
            $values[] = (int) $filters['user_id'];
        }

        if ( ! empty( $filters['date_from'] ) ) {
            $where[]  = 'DATE(created_at) >= %s';
            $values[] = sanitize_text_field( $filters['date_from'] );
        }

        if ( ! empty( $filters['date_to'] ) ) {
            $where[]  = 'DATE(created_at) <= %s';
            $values[] = sanitize_text_field( $filters['date_to'] );
        }

        $where_sql = implode( ' AND ', $where );

        // Total count.
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";
        if ( $values ) {
            $count_sql = $wpdb->prepare( $count_sql, $values );
        }
        $total_count = (int) $wpdb->get_var( $count_sql );

        // Breakdown by action.
        $breakdown_sql = "SELECT action, COUNT(*) as count FROM {$this->table_name} WHERE {$where_sql} GROUP BY action ORDER BY count DESC";
        if ( $values ) {
            $breakdown_sql = $wpdb->prepare( $breakdown_sql, $values );
        }
        $breakdown = $wpdb->get_results( $breakdown_sql, ARRAY_A );

        return array(
            'total_count' => $total_count,
            'breakdown'   => $breakdown,
            'filters'     => $filters,
        );
    }

    /**
     * Get Obmann performance statistics.
     *
     * @param int|null $obmann_id Optional Obmann user ID to filter by.
     * @return array[] Performance statistics grouped by user.
     */
    public function get_obmann_performance_stats( $obmann_id = null ) {
        global $wpdb;

        $where  = array( "action IN ('submission_approve', 'submission_reject', 'submission_edit')" );
        $values = array();

        if ( null !== $obmann_id ) {
            $where[]  = 'user_id = %d';
            $values[] = (int) $obmann_id;
        }

        $where_sql = implode( ' AND ', $where );

        $query = "
            SELECT user_id, action, COUNT(*) as count
            FROM {$this->table_name}
            WHERE {$where_sql}
            GROUP BY user_id, action
            ORDER BY user_id, action
        ";

        if ( $values ) {
            $query = $wpdb->prepare( $query, $values );
        }

        $results       = $wpdb->get_results( $query, ARRAY_A );
        $stats_by_user = array();

        foreach ( $results as $row ) {
            $uid = (int) $row['user_id'];

            if ( ! isset( $stats_by_user[ $uid ] ) ) {
                $user         = get_userdata( $uid );
                $display_name = $user ? $user->display_name : __( 'Unbekannter Benutzer', 'abschussplan-hgmh' );

                $stats_by_user[ $uid ] = array(
                    'user_id'           => $uid,
                    'display_name'      => $display_name,
                    'approved_count'    => 0,
                    'rejected_count'    => 0,
                    'edited_count'      => 0,
                    'total_submissions' => 0,
                );
            }

            $count = (int) $row['count'];

            switch ( $row['action'] ) {
                case 'submission_approve':
                    $stats_by_user[ $uid ]['approved_count'] = $count;
                    break;
                case 'submission_reject':
                    $stats_by_user[ $uid ]['rejected_count'] = $count;
                    break;
                case 'submission_edit':
                    $stats_by_user[ $uid ]['edited_count'] = $count;
                    break;
            }

            $stats_by_user[ $uid ]['total_submissions'] += $count;
        }

        return array_values( $stats_by_user );
    }

    /**
     * Delete activity-log rows older than a given number of days.
     *
     * @param int $days Number of days to keep.
     * @return int|false Number of deleted rows or false on failure.
     */
    public function cleanup_old_logs( $days = 90 ) {
        global $wpdb;

        $threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < %s",
                $threshold
            )
        );

        if ( false === $result ) {
            error_log(
                sprintf(
                    '[AHGMH Activity Logger] cleanup_old_logs failed: %s',
                    $wpdb->last_error
                )
            );
        }

        return $result;
    }

    /**
     * Resolve the client IP address from $_SERVER.
     *
     * @return string|null IP address, or null when unavailable.
     */
    private function get_ip_address() {
        $keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ( $keys as $key ) {
            if ( ! isset( $_SERVER[ $key ] ) ) {
                continue;
            }

            $value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

            foreach ( explode( ',', $value ) as $ip ) {
                $ip = trim( $ip );
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }

        return isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : null;
    }
}
