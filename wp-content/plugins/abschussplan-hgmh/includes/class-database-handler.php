<?php
/**
 * Database Handler Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling database operations
 */
class AHGMH_Database_Handler {
    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'hgmh_submissions_v2';
    }

    /**
     * Create all plugin database tables.
     * Only creates the current normalized schema (hgmh_* prefix).
     */
    public function create_table() {
        global $wpdb;

        // Drop tables removed in previous versions so stale schemas do not persist.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_page_views" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_operation_log_details" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hgmh_operation_logs" );

        // Create meldegruppen configuration tables
        $this->create_meldegruppen_config_table();
        $this->create_meldegruppen_limits_table();

        // Create all v2 normalized schema tables (submissions, wildarten,
        // eigenjagdbezirke, meldegruppen, moderation history, activity log, email log)
        $this->create_v2_schema_tables();
    }
    
    /**
     * Get the main submissions table name
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Create all v2 normalized schema tables needed by the current codebase.
     * Uses CREATE TABLE IF NOT EXISTS so it is safe to call on existing installs.
     */
    private function create_v2_schema_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_wildarten (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            display_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name),
            KEY is_active (is_active)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_meldegruppen (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wildart_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            obmann_user_id bigint(20) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wildart_name (wildart_id, name),
            KEY wildart_id (wildart_id),
            KEY obmann_user_id (obmann_user_id)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_eigenjagdbezirke (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            meldegruppe_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY meldegruppe_name (meldegruppe_id, name),
            KEY meldegruppe_id (meldegruppe_id)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_jagdbezirk_meldegruppen (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            jagdbezirk_id bigint(20) NOT NULL,
            meldegruppe_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY jagdbezirk_meldegruppe (jagdbezirk_id, meldegruppe_id),
            KEY jagdbezirk_id (jagdbezirk_id),
            KEY meldegruppe_id (meldegruppe_id)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_submissions_v2 (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wildart_id bigint(20) NOT NULL,
            eigenjagdbezirk_id bigint(20) NOT NULL,
            category varchar(100) NOT NULL,
            harvest_date datetime NOT NULL,
            wus_number varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            internal_note text,
            submitted_by_user_id bigint(20) DEFAULT NULL,
            submitted_by_email varchar(255) DEFAULT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'pending_email',
            verification_token varchar(64) DEFAULT NULL,
            verified_at datetime DEFAULT NULL,
            approved_by_user_id bigint(20) DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            approval_comment text,
            time_to_email_verify int(11) DEFAULT NULL,
            time_to_approval int(11) DEFAULT NULL,
            total_processing_time int(11) DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wildart_id (wildart_id),
            KEY eigenjagdbezirk_id (eigenjagdbezirk_id),
            KEY status (status),
            KEY harvest_date (harvest_date)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_moderation_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            performed_by_user_id bigint(20) DEFAULT NULL,
            performed_by_email varchar(255) DEFAULT NULL,
            old_status varchar(50) DEFAULT NULL,
            new_status varchar(50) DEFAULT NULL,
            comment text,
            performed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY performed_by_user_id (performed_by_user_id),
            KEY action (action)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_activity_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            user_email varchar(255) DEFAULT NULL,
            ip_address_hash varchar(64),
            action varchar(100) NOT NULL,
            entity_type varchar(50) DEFAULT NULL,
            entity_id bigint(20) DEFAULT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hgmh_email_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) DEFAULT NULL,
            email_type varchar(50) NOT NULL,
            recipient_email varchar(255) NOT NULL,
            subject varchar(255) DEFAULT NULL,
            body text,
            status varchar(20) DEFAULT 'sent',
            error_message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY email_type (email_type),
            KEY recipient_email (recipient_email),
            KEY sent_at (sent_at)
        ) $charset_collate;" );
    }

    /**
     * Create the meldegruppen configuration table for wildart-specific settings
     */
    public function create_meldegruppen_config_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hgmh_meldegruppen_config';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            wildart varchar(50) NULL,
            meldegruppe varchar(100) NOT NULL,
            jagdbezirke text,
            kategorie varchar(100) DEFAULT NULL,
            limit_value int(11) DEFAULT NULL,
            limit_mode enum('meldegruppen_specific','hegegemeinschaft_total') DEFAULT 'meldegruppen_specific',
            is_wildart_specific tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY wildart_idx (wildart),
            KEY meldegruppe_idx (meldegruppe),
            KEY wildart_meldegruppe_kategorie_idx (wildart, meldegruppe, kategorie)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        }

        /**
        * Create the meldegruppen-specific limits table
        */
    public function create_meldegruppen_limits_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hgmh_meldegruppen_limits';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            species varchar(50) NOT NULL,
            meldegruppe varchar(100) NULL,
            category varchar(100) NOT NULL,
            max_count int(11) NOT NULL DEFAULT 0,
            allow_exceeding tinyint(1) NOT NULL DEFAULT 0,
            has_custom_limits tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_species_meldegruppe_category (species, meldegruppe, category),
            KEY species_idx (species),
            KEY meldegruppe_idx (meldegruppe),
            KEY category_idx (category)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
     
    /**
     * Insert form submission into database.
     *
     * Accepts legacy field names (game_species, field1-field6) and maps them
     * to the normalized hgmh_submissions_v2 schema.
     *
     * Field mapping:
     *   game_species → wildart_id (looked up by name in hgmh_wildarten)
     * Expected keys: game_species, harvest_date, category, wus_number, notes,
     *   eigenjagdbezirk (name string, resolved to ID), internal_note, user_id,
     *   submitter_email, status.
     *
     * @param array $data Submission data
     * @return int|false Insert ID on success, false on error
     */
    public function insert_submission($data) {
        global $wpdb;

        $wildarten_table        = $wpdb->prefix . 'hgmh_wildarten';
        $eigenjagdbezirke_table = $wpdb->prefix . 'hgmh_eigenjagdbezirke';

        // Resolve wildart_id by name
        $wildart_name = isset($data['game_species']) ? sanitize_text_field($data['game_species']) : '';
        $wildart_id   = 0;
        if ( $wildart_name ) {
            $wildart_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $wildarten_table WHERE name = %s AND is_active = 1",
                $wildart_name
            ) );
        }

        // Resolve eigenjagdbezirk_id by name (empty → 0 for wildarts without Jagdbezirke)
        $ejb_name = isset($data['eigenjagdbezirk']) ? sanitize_text_field($data['eigenjagdbezirk']) : '';
        $ejb_id   = 0;
        if ( $ejb_name ) {
            $ejb_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $eigenjagdbezirke_table WHERE name = %s AND is_active = 1",
                $ejb_name
            ) );
        }

        $insert_data = array(
            'wildart_id'           => $wildart_id,
            'eigenjagdbezirk_id'   => $ejb_id,
            'harvest_date'         => isset($data['harvest_date'])   ? sanitize_text_field($data['harvest_date'])      : '',
            'category'             => isset($data['category'])       ? sanitize_text_field($data['category'])          : '',
            'wus_number'           => isset($data['wus_number'])     ? sanitize_text_field($data['wus_number'])        : '',
            'notes'                => isset($data['notes'])          ? sanitize_textarea_field($data['notes'])         : '',
            'internal_note'        => isset($data['internal_note'])  ? sanitize_textarea_field($data['internal_note']) : '',
            'submitted_by_user_id' => isset($data['user_id'])        ? absint($data['user_id'])                        : get_current_user_id(),
            'submitted_by_email'   => isset($data['submitter_email']) ? sanitize_email($data['submitter_email'])       : '',
            'status'               => isset($data['status'])         ? sanitize_text_field($data['status'])            : 'pending_email',
        );

        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if ( $result === false && defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('AHGMH insert error: ' . $wpdb->last_error);
        }

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Shared JOIN fragment for enriched submission queries.
     *
     * @return string SQL snippet
     */
    private function submission_joins() {
        global $wpdb;
        $w  = $wpdb->prefix . 'hgmh_wildarten';
        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';
        return "LEFT JOIN $w w ON s.wildart_id = w.id
                LEFT JOIN $e e ON s.eigenjagdbezirk_id = e.id
                LEFT JOIN $jm jm ON e.id = jm.jagdbezirk_id
                LEFT JOIN $m m ON jm.meldegruppe_id = m.id";
    }

    /**
     * Get all form submissions
     *
     * @param int  $limit        Number of results (0 = unlimited)
     * @param int  $offset       Offset for pagination
     * @param bool $approved_only Only return approved submissions
     * @return array
     */
    public function get_submissions($limit = 10, $offset = 0, $approved_only = false) {
        global $wpdb;

        $query = "SELECT s.*, w.name AS wildart_name, e.name AS eigenjagdbezirk_name, m.name AS meldegruppe_name
                  FROM $this->table_name s
                  " . $this->submission_joins();

        if ($approved_only) {
            $query .= " WHERE s.status = 'approved'";
        }

        $query .= " ORDER BY s.submitted_at DESC";

        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }

        return $wpdb->get_results($query, ARRAY_A) ?: array();
    }

    /**
     * Get a single submission by ID
     *
     * @param int $id Submission ID
     * @return array|null
     */
    public function get_submission_by_id($id) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT s.*, w.name AS wildart_name, e.name AS eigenjagdbezirk_name, m.name AS meldegruppe_name
             FROM $this->table_name s
             " . $this->submission_joins() . "
             WHERE s.id = %d",
            $id
        );

        return $wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Count total submissions
     *
     * @param bool $approved_only Only count approved submissions
     * @return int
     */
    public function count_submissions($approved_only = false) {
        global $wpdb;

        $query = "SELECT COUNT(*) FROM $this->table_name";

        if ($approved_only) {
            $query .= " WHERE status = 'approved'";
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Count submissions this month
     *
     * @return int Count of submissions this month
     */
    public function count_submissions_this_month() {
        global $wpdb;

        $current_month = gmdate('m');
        $current_year  = gmdate('Y');

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name
             WHERE MONTH(submitted_at) = %s
             AND YEAR(submitted_at) = %s",
            $current_month, $current_year
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Count active users (users who have submitted)
     *
     * @return int Count of active users
     */
    public function count_active_users() {
        global $wpdb;

        $query = "SELECT COUNT(DISTINCT submitted_by_user_id) FROM $this->table_name WHERE submitted_by_user_id > 0";
        return (int) $wpdb->get_var($query);
    }

    /**
     * Count submissions by species and category
     *
     * @param string $species  Wildart name
     * @param string $category Category name
     * @return int Count of submissions
     */
    public function count_submissions_by_species_category($species, $category) {
        global $wpdb;

        $w = $wpdb->prefix . 'hgmh_wildarten';

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name s
             INNER JOIN $w w ON s.wildart_id = w.id
             WHERE w.name = %s AND s.category = %s",
            $species, $category
        );

        return (int) $wpdb->get_var($query);
    }



    /**
     * Delete a submission
     *
     * @param int $id Submission ID
     * @return bool Whether the deletion was successful
     */
    public function delete_submission($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Update a submission
     *
     * @param int   $id   Submission ID
     * @param array $data Updated data (new schema field names)
     * @return bool
     */
    public function update_submission($id, $data) {
        global $wpdb;

        $integer_fields = array(
            'wildart_id', 'eigenjagdbezirk_id',
            'submitted_by_user_id', 'approved_by_user_id', 'time_to_approval',
        );

        $filtered_data = array();
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $filtered_data[$key] = $value;
            }
        }

        if (empty($filtered_data)) {
            return false;
        }

        $formats = array();
        foreach ($filtered_data as $key => $value) {
            $formats[] = in_array($key, $integer_fields, true) ? '%d' : '%s';
        }

        $result = $wpdb->update(
            $this->table_name,
            $filtered_data,
            array('id' => $id),
            $formats,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete all submissions for a specific species
     *
     * @param string $species Wildart name
     * @return bool
     */
    public function delete_submissions_by_species($species) {
        global $wpdb;

        $w = $wpdb->prefix . 'hgmh_wildarten';

        $wildart_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $w WHERE name = %s",
            sanitize_text_field($species)
        ) );

        if ( ! $wildart_id ) {
            return false;
        }

        $result = $wpdb->delete(
            $this->table_name,
            array('wildart_id' => $wildart_id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Get submission counts per category.
     *
     * @param string $species      Wildart name (optional)
     * @param string $meldegruppe  Meldegruppe name (optional)
     * @param string $jagdbezirk   Eigenjagdbezirk name (optional)
     * @param bool   $approved_only Only count approved submissions
     * @return array category => count
     */
    public function get_category_counts($species = '', $meldegruppe = '', $jagdbezirk = '', $approved_only = false) {
        global $wpdb;

        $w  = $wpdb->prefix . 'hgmh_wildarten';
        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';

        $query = "SELECT s.category, COUNT(*) as count
                  FROM $this->table_name s
                  LEFT JOIN $w w  ON s.wildart_id = w.id
                  LEFT JOIN $e e  ON s.eigenjagdbezirk_id = e.id
                  LEFT JOIN $jm jm ON e.id = jm.jagdbezirk_id
                  LEFT JOIN $m m  ON jm.meldegruppe_id = m.id
                  WHERE s.category != ''";

        if ($approved_only) {
            $query .= " AND s.status = 'approved'";
        }

        if (!empty($species)) {
            $query .= $wpdb->prepare(" AND w.name = %s", $species);
        }

        if (!empty($meldegruppe)) {
            $query .= $wpdb->prepare(" AND m.name = %s", $meldegruppe);
        }

        if (!empty($jagdbezirk)) {
            $query .= $wpdb->prepare(" AND e.name = %s", $jagdbezirk);
        }

        $query .= " GROUP BY s.category";
        $results = $wpdb->get_results($query, ARRAY_A);

        $counts = array();
        foreach ($results as $result) {
            $counts[$result['category']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Get submissions filtered by species
     *
     * @param int    $limit        Number of results (0 = unlimited)
     * @param int    $offset       Offset for pagination
     * @param string $species      Wildart name (optional)
     * @param bool   $approved_only Only return approved submissions
     * @return array
     */
    public function get_submissions_by_species($limit = 10, $offset = 0, $species = '', $approved_only = false) {
        global $wpdb;

        $query = "SELECT s.*, w.name AS wildart_name, e.name AS eigenjagdbezirk_name, m.name AS meldegruppe_name
                  FROM $this->table_name s
                  " . $this->submission_joins();

        $where = array();

        if (!empty($species)) {
            $where[] = $wpdb->prepare("w.name = %s", $species);
        }

        if ($approved_only) {
            $where[] = "s.status = 'approved'";
        }

        if ($where) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $query .= " ORDER BY s.submitted_at DESC";

        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }

        return $wpdb->get_results($query, ARRAY_A) ?: array();
    }

    /**
     * Count submissions filtered by species
     *
     * @param string $species      Wildart name (optional)
     * @param bool   $approved_only Only count approved submissions
     * @return int
     */
    public function count_submissions_by_species($species = '', $approved_only = false) {
        global $wpdb;

        $w = $wpdb->prefix . 'hgmh_wildarten';

        $query = "SELECT COUNT(*) FROM $this->table_name s
                  LEFT JOIN $w w ON s.wildart_id = w.id";

        $where = array();

        if (!empty($species)) {
            $where[] = $wpdb->prepare("w.name = %s", $species);
        }

        if ($approved_only) {
            $where[] = "s.status = 'approved'";
        }

        if ($where) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        return (int) $wpdb->get_var($query);
    }
     
    /**
     * Get submissions filtered by species and meldegruppe
     *
     * @param string $species      Wildart name
     * @param string $meldegruppe  Meldegruppe name
     * @param int    $limit        Number of results (0 = unlimited)
     * @param int    $offset       Offset for pagination
     * @param bool   $approved_only Only return approved submissions
     * @return array
     */
    public function get_submissions_by_species_and_meldegruppe($species, $meldegruppe, $limit = 10, $offset = 0, $approved_only = false) {
        global $wpdb;

        if (empty($species) || empty($meldegruppe)) {
            return array();
        }

        $query = "SELECT s.*, w.name AS wildart_name, e.name AS eigenjagdbezirk_name, m.name AS meldegruppe_name
                  FROM $this->table_name s
                  " . $this->submission_joins() . "
                  WHERE w.name = %s AND m.name = %s";

        if ($approved_only) {
            $query .= " AND s.status = 'approved'";
        }

        $query .= " ORDER BY s.submitted_at DESC";

        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }

        return $wpdb->get_results($wpdb->prepare($query, $species, $meldegruppe), ARRAY_A) ?: array();
    }

    /**
     * Count submissions filtered by species and meldegruppe
     *
     * @param string $species      Wildart name
     * @param string $meldegruppe  Meldegruppe name
     * @param bool   $approved_only Only count approved submissions
     * @return int
     */
    public function count_submissions_by_species_and_meldegruppe($species, $meldegruppe, $approved_only = false) {
        global $wpdb;

        if (empty($species) || empty($meldegruppe)) {
            return 0;
        }

        $w  = $wpdb->prefix . 'hgmh_wildarten';
        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';

        $query = "SELECT COUNT(*)
                  FROM $this->table_name s
                  LEFT JOIN $w w  ON s.wildart_id = w.id
                  LEFT JOIN $e e  ON s.eigenjagdbezirk_id = e.id
                  LEFT JOIN $jm jm ON e.id = jm.jagdbezirk_id
                  LEFT JOIN $m m  ON jm.meldegruppe_id = m.id
                  WHERE w.name = %s AND m.name = %s";

        if ($approved_only) {
            $query .= " AND s.status = 'approved'";
        }

        return (int) $wpdb->get_var($wpdb->prepare($query, $species, $meldegruppe));
    }

    /**
     * Get submissions filtered by meldegruppe only
     *
     * @param string $meldegruppe  Meldegruppe name
     * @param int    $limit        Number of results (0 = unlimited)
     * @param int    $offset       Offset for pagination
     * @param bool   $approved_only Only return approved submissions
     * @return array
     */
    public function get_submissions_by_meldegruppe($meldegruppe, $limit = 10, $offset = 0, $approved_only = false) {
        global $wpdb;

        if (empty($meldegruppe)) {
            return array();
        }

        $query = "SELECT s.*, w.name AS wildart_name, e.name AS eigenjagdbezirk_name, m.name AS meldegruppe_name
                  FROM $this->table_name s
                  " . $this->submission_joins() . "
                  WHERE m.name = %s";

        if ($approved_only) {
            $query .= " AND s.status = 'approved'";
        }

        $query .= " ORDER BY s.submitted_at DESC";

        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }

        return $wpdb->get_results($wpdb->prepare($query, $meldegruppe), ARRAY_A) ?: array();
    }

    /**
     * Count submissions filtered by meldegruppe only
     *
     * @param string $meldegruppe  Meldegruppe name
     * @param bool   $approved_only Only count approved submissions
     * @return int
     */
    public function count_submissions_by_meldegruppe($meldegruppe, $approved_only = false) {
        global $wpdb;

        if (empty($meldegruppe)) {
            return 0;
        }

        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';

        $query = "SELECT COUNT(*)
                  FROM $this->table_name s
                  LEFT JOIN $e e  ON s.eigenjagdbezirk_id = e.id
                  LEFT JOIN $jm jm ON e.id = jm.jagdbezirk_id
                  LEFT JOIN $m m  ON jm.meldegruppe_id = m.id
                  WHERE m.name = %s";

        if ($approved_only) {
            $query .= " AND s.status = 'approved'";
        }

        return (int) $wpdb->get_var($wpdb->prepare($query, $meldegruppe));
    }

    /**
     * Check if WUS number already exists in database
     *
     * @param string $wus_number The WUS number to check
     * @return bool True if WUS exists, false otherwise
     */
    public function check_wus_exists($wus_number) {
        global $wpdb;

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE wus_number = %s",
            $wus_number
        ) );

        return (int) $count > 0;
    }

    /**
     * Check if wildart-specific meldegruppen mode is enabled
     * 
     * @return bool
     */
    public function is_wildart_specific_enabled() {
        return (bool) get_option('ahgmh_use_wildart_specific_meldegruppen', false);
    }

    /**
     * Toggle wildart-specific meldegruppen mode
     * 
     * @param bool $enabled
     */
    public function set_wildart_specific_mode($enabled) {
        update_option('ahgmh_use_wildart_specific_meldegruppen', (bool) $enabled);
    }

    /**
     * Get meldegruppen for a specific wildart
     * 
     * @param string $wildart The wildart name (optional, gets all if empty)
     * @return array Array of meldegruppen
     */
    public function get_meldegruppen_for_wildart($wildart = '') {
        global $wpdb;
        
        if (!$this->is_wildart_specific_enabled()) {
            // Return global meldegruppen from jagdbezirke table
            return $this->get_global_meldegruppen();
        }
        
        $config_table = $wpdb->prefix . 'hgmh_meldegruppen_config';
        
        if (empty($wildart)) {
            // Get all meldegruppen for all wildarten
            $query = "SELECT DISTINCT meldegruppe FROM $config_table WHERE is_wildart_specific = 1 ORDER BY meldegruppe";
            return $wpdb->get_col($query);
        }
        
        $query = $wpdb->prepare(
            "SELECT meldegruppe FROM $config_table WHERE wildart = %s AND is_wildart_specific = 1 ORDER BY meldegruppe",
            $wildart
        );
        
        return $wpdb->get_col($query);
    }

    /**
     * Get global meldegruppen from normalized table
     *
     * @return array Array of meldegruppe names
     */
    public function get_global_meldegruppen() {
        global $wpdb;

        $table = $wpdb->prefix . 'hgmh_meldegruppen';

        return $wpdb->get_col(
            "SELECT DISTINCT name FROM $table WHERE is_active = 1 ORDER BY name"
        );
    }
    
    /**
     * Check if meldegruppe exists for specific wildart
     *
     * @param string $wildart Wildart name
     * @param string $meldegruppe Meldegruppe name
     * @return bool True if exists
     */
    public function meldegruppe_exists_for_wildart($wildart, $meldegruppe) {
        if (empty($wildart) || empty($meldegruppe)) {
            return false;
        }
        
        // FIX: use correct meldegruppen source for validation
        $wildart_meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        $meldegruppen = isset($wildart_meldegruppen[$wildart]) ? $wildart_meldegruppen[$wildart] : ['Gruppe_A', 'Gruppe_B'];
        return in_array($meldegruppe, $meldegruppen, true);
    }

    /**
     * Save meldegruppen configuration for a wildart
     * 
     * @param string $wildart Wildart name
     * @param array $meldegruppen Array of meldegruppen 
     * @param array $jagdbezirke_map Map of meldegruppe -> jagdbezirke array
     */
    public function save_meldegruppen_config($wildart, $meldegruppen, $jagdbezirke_map = array()) {
        global $wpdb;
        
        $config_table = $wpdb->prefix . 'hgmh_meldegruppen_config';
        
        // First, delete existing config for this wildart
        $wpdb->delete($config_table, array('wildart' => $wildart), array('%s'));
        
        // Insert new configuration
        foreach ($meldegruppen as $meldegruppe) {
            $jagdbezirke_json = isset($jagdbezirke_map[$meldegruppe]) ? 
                json_encode($jagdbezirke_map[$meldegruppe]) : '[]';
            
            $wpdb->insert(
                $config_table,
                array(
                    'wildart' => $wildart,
                    'meldegruppe' => $meldegruppe,
                    'jagdbezirke' => $jagdbezirke_json,
                    'is_wildart_specific' => 1
                ),
                array('%s', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Delete all submissions (used when changing meldegruppen configuration)
     */
    public function delete_all_submissions() {
        global $wpdb;
        
        $result = $wpdb->query("DELETE FROM $this->table_name");
        return $result !== false;
    }

    /**
     * Check if meldegruppe has custom limits for a species
     * 
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name
     * @return bool
     */
    public function meldegruppe_has_custom_limits($species, $meldegruppe) {
        global $wpdb;
        
        $limits_table = $wpdb->prefix . 'hgmh_meldegruppen_limits';
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $limits_table 
             WHERE species = %s AND meldegruppe = %s AND has_custom_limits = 1",
            $species,
            $meldegruppe
        );
        
        return (int) $wpdb->get_var($query) > 0;
    }

    /**
     * Get limits for specific meldegruppe and species
     * 
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name (NULL for species defaults)
     * @return array Array of category => limit
     */
    public function get_meldegruppen_limits($species, $meldegruppe = null) {
        global $wpdb;
        
        $limits_table = $wpdb->prefix . 'hgmh_meldegruppen_limits';
        
        if ($meldegruppe === null) {
            // Get species default limits (for meldegruppen without custom limits)
            $query = $wpdb->prepare(
                "SELECT category, max_count FROM $limits_table 
                 WHERE species = %s AND meldegruppe IS NULL",
                $species
            );
        } else {
            // Get meldegruppe-specific limits
            $query = $wpdb->prepare(
                "SELECT category, max_count FROM $limits_table 
                 WHERE species = %s AND meldegruppe = %s",
                $species,
                $meldegruppe
            );
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $limits = array();
        foreach ($results as $result) {
            $limits[$result['category']] = (int) $result['max_count'];
        }
        
        return $limits;
    }

    /**
     * Get allow exceeding settings for specific meldegruppe and species
     * 
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name (NULL for species defaults)
     * @return array Array of category => allow_exceeding
     */
    public function get_meldegruppen_allow_exceeding($species, $meldegruppe = null) {
        global $wpdb;
        
        $limits_table = $wpdb->prefix . 'hgmh_meldegruppen_limits';
        
        if ($meldegruppe === null) {
            // Get species default settings
            $query = $wpdb->prepare(
                "SELECT category, allow_exceeding FROM $limits_table 
                 WHERE species = %s AND meldegruppe IS NULL",
                $species
            );
        } else {
            // Get meldegruppe-specific settings
            $query = $wpdb->prepare(
                "SELECT category, allow_exceeding FROM $limits_table 
                 WHERE species = %s AND meldegruppe = %s",
                $species,
                $meldegruppe
            );
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $settings = array();
        foreach ($results as $result) {
            $settings[$result['category']] = (bool) $result['allow_exceeding'];
        }
        
        return $settings;
    }

    /**
     * Save limits configuration for meldegruppe
     * 
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name (NULL for species defaults)
     * @param array $limits Array of category => limit
     * @param array $allow_exceeding Array of category => allow_exceeding
     * @param bool $has_custom_limits Whether this meldegruppe uses custom limits
     */
    public function save_meldegruppen_limits($species, $meldegruppe, $limits, $allow_exceeding = array(), $has_custom_limits = true) {
        global $wpdb;
        
        $limits_table = $wpdb->prefix . 'hgmh_meldegruppen_limits';
        
        // First, delete existing limits for this species/meldegruppe combination
        if ($meldegruppe === null) {
            $wpdb->delete($limits_table, array('species' => $species, 'meldegruppe' => null), array('%s', '%s'));
        } else {
            $wpdb->delete($limits_table, array('species' => $species, 'meldegruppe' => $meldegruppe), array('%s', '%s'));
        }
        
        // Insert new limits
        foreach ($limits as $category => $limit) {
            $allow_exceed = isset($allow_exceeding[$category]) ? (bool) $allow_exceeding[$category] : false;
            
            $wpdb->insert(
                $limits_table,
                array(
                    'species' => $species,
                    'meldegruppe' => $meldegruppe,
                    'category' => $category,
                    'max_count' => (int) $limit,
                    'allow_exceeding' => $allow_exceed ? 1 : 0,
                    'has_custom_limits' => $has_custom_limits ? 1 : 0
                ),
                array('%s', '%s', '%s', '%d', '%d', '%d')
            );
        }
    }

    /**
     * Toggle custom limits setting for a meldegruppe
     * 
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name
     * @param bool $has_custom_limits Whether meldegruppe should use custom limits
     */
    public function toggle_meldegruppe_custom_limits($species, $meldegruppe, $has_custom_limits) {
        global $wpdb;
        
        $limits_table = $wpdb->prefix . 'hgmh_meldegruppen_limits';
        
        if (!$has_custom_limits) {
            // Delete custom limits, meldegruppe will fall back to species defaults
            $wpdb->delete(
                $limits_table,
                array('species' => $species, 'meldegruppe' => $meldegruppe),
                array('%s', '%s')
            );
        } else {
            // Initialize with species default limits if they exist
            $default_limits = $this->get_meldegruppen_limits($species, null);
            $default_exceeding = $this->get_meldegruppen_allow_exceeding($species, null);
            
            if (!empty($default_limits)) {
                $this->save_meldegruppen_limits($species, $meldegruppe, $default_limits, $default_exceeding, true);
            }
        }
    }

    /**
     * Get applicable limits for a specific species/meldegruppe/category combination
     * Falls back to species defaults if meldegruppe doesn't have custom limits
     * 
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name
     * @param string $category Category name
     * @return array Array with 'limit' and 'allow_exceeding' keys
     */
    public function get_applicable_limits($species, $meldegruppe, $category) {
        // Check if meldegruppe has custom limits
        if ($this->meldegruppe_has_custom_limits($species, $meldegruppe)) {
            $meldegruppe_limits = $this->get_meldegruppen_limits($species, $meldegruppe);
            $meldegruppe_exceeding = $this->get_meldegruppen_allow_exceeding($species, $meldegruppe);
            
            if (isset($meldegruppe_limits[$category])) {
                return array(
                    'limit' => $meldegruppe_limits[$category],
                    'allow_exceeding' => isset($meldegruppe_exceeding[$category]) ? $meldegruppe_exceeding[$category] : false
                );
            }
        }
        
        // Fall back to species defaults
        $default_limits = $this->get_meldegruppen_limits($species, null);
        $default_exceeding = $this->get_meldegruppen_allow_exceeding($species, null);
        
        return array(
            'limit' => isset($default_limits[$category]) ? $default_limits[$category] : 0,
            'allow_exceeding' => isset($default_exceeding[$category]) ? $default_exceeding[$category] : false
        );
    }
    
    /**
     * Remove all plugin data (for uninstall)
     */
    public static function cleanup_database() {
        global $wpdb;

        $tables = array(
            'hgmh_submissions_v2',
            'hgmh_eigenjagdbezirke',
            'hgmh_jagdbezirk_meldegruppen',
            'hgmh_meldegruppen',
            'hgmh_wildarten',
            'hgmh_moderation_history',
            'hgmh_activity_log',
            'hgmh_email_log',
            'hgmh_meldegruppen_config',
            'hgmh_meldegruppen_limits',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
    }
    
    /**
     * Get all Eigenjagdbezirke
     *
     * @return array
     */
    public function get_jagdbezirke() {
        global $wpdb;

        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';

        return $wpdb->get_results(
            "SELECT e.id, e.name AS jagdbezirk, m.name AS meldegruppe, e.description AS bemerkung, e.is_active AS active
             FROM $e e
             LEFT JOIN $jm jm ON e.id = jm.jagdbezirk_id
             LEFT JOIN $m m ON jm.meldegruppe_id = m.id
             ORDER BY e.name ASC",
            ARRAY_A
        ) ?: array();
    }

    /**
     * Get active Eigenjagdbezirke
     *
     * @return array
     */
    public function get_active_jagdbezirke() {
        global $wpdb;

        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';

        return $wpdb->get_results(
            "SELECT e.id, e.name AS jagdbezirk, m.name AS meldegruppe, e.description AS bemerkung
             FROM $e e
             LEFT JOIN $jm jm ON e.id = jm.jagdbezirk_id
             LEFT JOIN $m m ON jm.meldegruppe_id = m.id
             WHERE e.is_active = 1
             ORDER BY e.name ASC",
            ARRAY_A
        ) ?: array();
    }

    /**
     * Insert new Eigenjagdbezirk
     *
     * @param array $data Keys: jagdbezirk (name), meldegruppe_id or meldegruppe (name), bemerkung
     * @return int|false Insert ID or false
     */
    public function insert_jagdbezirk($data) {
        global $wpdb;

        $e = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $m = $wpdb->prefix . 'hgmh_meldegruppen';

        // Resolve meldegruppe_id
        if (!empty($data['meldegruppe_id'])) {
            $meldegruppe_id = absint($data['meldegruppe_id']);
        } elseif (!empty($data['meldegruppe'])) {
            $meldegruppe_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $m WHERE name = %s",
                sanitize_text_field($data['meldegruppe'])
            ));
        } else {
            $meldegruppe_id = 0;
        }

        $result = $wpdb->insert(
            $e,
            array(
                'name'          => sanitize_text_field($data['jagdbezirk']),
                'meldegruppe_id' => $meldegruppe_id,
                'description'   => isset($data['bemerkung']) ? sanitize_textarea_field($data['bemerkung']) : '',
                'is_active'     => isset($data['ungueltig']) && $data['ungueltig'] ? 0 : 1,
            ),
            array('%s', '%d', '%s', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update Eigenjagdbezirk
     *
     * @param int   $id   Record ID
     * @param array $data Keys: jagdbezirk, meldegruppe_id or meldegruppe, bemerkung, ungueltig
     * @return int|false
     */
    public function update_jagdbezirk($id, $data) {
        global $wpdb;

        $e = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $m = $wpdb->prefix . 'hgmh_meldegruppen';

        $update = array('name' => sanitize_text_field($data['jagdbezirk']));

        if (!empty($data['meldegruppe_id'])) {
            $update['meldegruppe_id'] = absint($data['meldegruppe_id']);
        } elseif (!empty($data['meldegruppe'])) {
            $update['meldegruppe_id'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $m WHERE name = %s",
                sanitize_text_field($data['meldegruppe'])
            ));
        }

        if (isset($data['bemerkung'])) {
            $update['description'] = sanitize_textarea_field($data['bemerkung']);
        }

        if (isset($data['ungueltig'])) {
            $update['is_active'] = $data['ungueltig'] ? 0 : 1;
        }

        return $wpdb->update($e, $update, array('id' => $id), null, array('%d'));
    }

    /**
     * Delete Eigenjagdbezirk
     *
     * @param int $id
     * @return int|false
     */
    public function delete_jagdbezirk($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'hgmh_eigenjagdbezirke', array('id' => $id), array('%d'));
    }

    /**
     * Delete all Eigenjagdbezirke
     *
     * @return int|false
     */
    public function delete_all_jagdbezirke() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$wpdb->prefix}hgmh_eigenjagdbezirke");
    }

    /**
     * Get eigenjagdbezirke for a specific wildart (via meldegruppe relationship)
     *
     * @param string $wildart Wildart name
     * @return array
     */
    public function get_wildart_jagdbezirke($wildart) {
        global $wpdb;

        $e = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $m = $wpdb->prefix . 'hgmh_meldegruppen';
        $w = $wpdb->prefix . 'hgmh_wildarten';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.name AS jagdbezirk, m.name AS meldegruppe, e.description AS bemerkung
             FROM $e e
             INNER JOIN $m m ON e.meldegruppe_id = m.id
             INNER JOIN $w w ON m.wildart_id = w.id
             WHERE w.name = %s AND e.is_active = 1
             ORDER BY e.name ASC",
            sanitize_text_field($wildart)
        ), ARRAY_A) ?: array();
    }

    /**
     * Save an eigenjagdbezirk for a specific wildart
     *
     * @param string $wildart         Wildart name
     * @param array  $jagdbezirk_data Keys: jagdbezirk, meldegruppe, bemerkung
     * @return int|false
     */
    public function save_wildart_jagdbezirk($wildart, $jagdbezirk_data) {
        $data = array_merge($jagdbezirk_data, array('wildart' => $wildart));
        return $this->insert_jagdbezirk($data);
    }

    /**
     * Save a global eigenjagdbezirk (no specific wildart)
     *
     * @param array $jagdbezirk_data Keys: jagdbezirk, meldegruppe, bemerkung
     * @return int|false
     */
    public function save_global_jagdbezirk($jagdbezirk_data) {
        return $this->insert_jagdbezirk($jagdbezirk_data);
    }

    /**
     * Deactivate all eigenjagdbezirke not assigned to a specific wildart
     *
     * @return int|false
     */
    public function clear_global_jagdbezirke() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$wpdb->prefix}hgmh_eigenjagdbezirke WHERE meldegruppe_id = 0 OR meldegruppe_id IS NULL");
    }

    /**
     * Deactivate all wildart-specific eigenjagdbezirke
     *
     * @return int|false
     */
    public function clear_wildart_jagdbezirke() {
        global $wpdb;
        $e = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $m = $wpdb->prefix . 'hgmh_meldegruppen';
        return $wpdb->query(
            "DELETE e FROM $e e INNER JOIN $m m ON e.meldegruppe_id = m.id WHERE m.wildart_id > 0"
        );
    }

    /**
     * Get all meldegruppen (for public summary validation)
     *
     * @return array Array of all meldegruppe names
     */
    public function get_all_meldegruppen() {
        global $wpdb;

        $table = $wpdb->prefix . 'hgmh_meldegruppen';

        return $wpdb->get_col(
            "SELECT DISTINCT name FROM $table WHERE name != '' ORDER BY name"
        ) ?: array();
    }

    /**
     * Get Eigenjagdbezirke by Meldegruppe name.
     *
     * @param string $meldegruppe Meldegruppe name
     * @return array
     */
    public function get_jagdbezirke_by_meldegruppe($meldegruppe) {
        global $wpdb;

        $meldegruppe = sanitize_text_field($meldegruppe);

        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.name AS jagdbezirk, m.name AS meldegruppe, e.description AS bemerkung
             FROM $e e
             INNER JOIN $jm jm ON e.id = jm.jagdbezirk_id
             INNER JOIN $m m ON jm.meldegruppe_id = m.id
             WHERE m.name = %s AND e.is_active = 1
             ORDER BY e.name ASC",
            $meldegruppe
        ), ARRAY_A) ?: array();
    }

    /**
     * Get public summary data based on parameter combination
     * 
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Summary data with categories, limits, counts, allow_exceeding
     */
    public function get_public_summary_data($species = '', $meldegruppe = '') {
        $available_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        // Initialize data structure
        $summary_data = array(
            'categories' => array(),
            'limits' => array(),
            'counts' => array(),
            'allow_exceeding' => array()
        );
        
        if (empty($species) && empty($meldegruppe)) {
            // Case 1: Show Hegegemeinschafts-Gesamt-Statistiken (all species and meldegruppen)
            $summary_data = $this->get_total_summary_data($available_species);
            
        } elseif (!empty($species) && empty($meldegruppe)) {
            // Case 2: Show specific species, all meldegruppen for this species
            $summary_data = $this->get_species_summary_data($species);
            
        } elseif (empty($species) && !empty($meldegruppe)) {
            // Case 3: Show specific meldegruppe (treated as jagdbezirk), all species for this meldegruppe
            $summary_data = $this->get_meldegruppe_summary_data($meldegruppe, $available_species);
            
        } else {
            // Case 4: Show specific species + meldegruppe (treated as jagdbezirk) combination
            $summary_data = $this->get_specific_summary_data($species, $meldegruppe);
        }
        
        return $summary_data;
    }
    
    /**
     * Check if value is an associative array (not indexed)
     *
     * @param mixed $value Value to check
     * @return bool True if associative array
     */
    private function is_assoc_array($value) {
        return is_array($value) && array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * Get the limit mode for a specific species
     *
     * @param string $species Species name
     * @return string 'meldegruppen_specific' or 'hegegemeinschaft_total'
     */
    private function get_limit_mode($species) {
        $limit_modes = get_option('ahgmh_limit_modes', array());
        $original_mode = isset($limit_modes[$species]) ? $limit_modes[$species] : null; // NULL = never explicitly set
        
        // Migration: Convert old 'jagdbezirk_specific' back to 'meldegruppen_specific' for compatibility
        if ($original_mode === 'jagdbezirk_specific') {
            $limit_modes[$species] = 'meldegruppen_specific';
            update_option('ahgmh_limit_modes', $limit_modes);
            return 'meldegruppen_specific';
        } else if ($original_mode === null) {
            // Never explicitly set - intelligent detection based on data structure
            $all_limits = get_option('ahgmh_wildart_limits', array());
            $species_data = isset($all_limits[$species]) ? $all_limits[$species] : array();
            
            // Admin structure is always: species -> meldegruppe -> category -> value
            // So we always have meldegruppen_specific structure now
            // Default to meldegruppen_specific for new admin system
            return 'meldegruppen_specific';
        }
        
        return $original_mode;
    }

    /**
     * Get total summary data for all species and meldegruppen
     * 
     * @param array $species_list Available species
     * @return array Summary data
     */
    private function get_total_summary_data($species_list) {
        $all_categories = array();
        $combined_limits = array();
        $combined_counts = array();
        $combined_allow_exceeding = array();
        
        foreach ($species_list as $species) {
            $categories_key = 'ahgmh_categories_' . sanitize_key($species);
            $species_categories = get_option($categories_key, array());
            
            foreach ($species_categories as $category) {
                if (!in_array($category, $all_categories)) {
                    $all_categories[] = $category;
                }
                
                // Get the limit mode for this species
                $limit_mode = $this->get_limit_mode($species);
                
                // Get limits from new admin system  
                $all_limits = get_option('ahgmh_wildart_limits', array());
                $species_limits = isset($all_limits[$species]) ? $all_limits[$species] : array();
                
                if ($limit_mode === 'hegegemeinschaft_total') {
                    // Mode 1: Use Hegegemeinschaft total limits - sum all meldegruppen for this category
                    $limit_value = 0;
                    foreach ($species_limits as $meldegruppe => $categories) {
                        if (is_array($categories) && isset($categories[$category])) {
                            $limit_value += (int) $categories[$category];
                        }
                    }
                } else {
                    // Mode 2: Jagdbezirk-spezifische Limits - sum all meldegruppe-specific limits
                    $limit_value = 0;
                    foreach ($species_limits as $meldegruppe => $categories) {
                        if (is_array($categories) && isset($categories[$category])) {
                            $limit_value += (int) $categories[$category];
                        }
                    }
                }
                
                // Accumulate limits
                if (!isset($combined_limits[$category])) {
                    $combined_limits[$category] = 0;
                }
                $combined_limits[$category] += $limit_value;
                
                // Get counts for this species/category (Bug #13b & #14: only approved submissions)
                $category_counts = $this->get_category_counts($species, '', '', true);
                $count_value = isset($category_counts[$category]) ? (int) $category_counts[$category] : 0;
                
                // Accumulate counts
                if (!isset($combined_counts[$category])) {
                    $combined_counts[$category] = 0;
                }
                $combined_counts[$category] += $count_value;
                
                // Get allow_exceeding (use OR logic - if any species allows, show as allowed)
                $exceeding_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
                $species_exceeding = get_option($exceeding_key, array());
                $exceeding_value = isset($species_exceeding[$category]) ? (bool) $species_exceeding[$category] : false;
                
                if (!isset($combined_allow_exceeding[$category])) {
                    $combined_allow_exceeding[$category] = false;
                }
                $combined_allow_exceeding[$category] = $combined_allow_exceeding[$category] || $exceeding_value;
            }
        }
        
        return array(
            'categories' => $all_categories,
            'limits' => $combined_limits,
            'counts' => $combined_counts,
            'allow_exceeding' => $combined_allow_exceeding
        );
    }

    /**
     * Get summary data for specific species (all meldegruppen)
     * 
     * @param string $species Species name
     * @return array Summary data
     */
    private function get_species_summary_data($species) {
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        // Get limits from new admin system  
        $limits = array();
        $all_limits = get_option('ahgmh_wildart_limits', array());
        $species_limits = isset($all_limits[$species]) ? $all_limits[$species] : array();
        
        foreach ($categories as $category) {
            $limit_mode = $this->get_limit_mode($species);
            
            // Calculate limit based on admin storage structure
            $limit_value = 0;
            foreach ($species_limits as $meldegruppe => $categories_data) {
                if (is_array($categories_data) && isset($categories_data[$category])) {
                    $limit_value += (int) $categories_data[$category];
                }
            }
            
            $limits[$category] = $limit_value;
        }
        
        // Get allow_exceeding from new admin system (fallback to old system)
        $allow_exceeding = array();
        foreach ($categories as $category) {
            // Try new system first
            if (isset($species_limits[$category . '_allow_exceeding'])) {
                $allow_exceeding[$category] = (bool) $species_limits[$category . '_allow_exceeding'];
            } else {
                // Fallback to old system
                $exceeding_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
                $old_exceeding = get_option($exceeding_key, array());
                $allow_exceeding[$category] = isset($old_exceeding[$category]) ? (bool) $old_exceeding[$category] : false;
            }
        }
        
        // Bug #13b & #14: only count approved submissions for public summary
        $counts = $this->get_category_counts($species, '', '', true);

        return array(
            'categories' => $categories,
            'limits' => $limits,
            'counts' => $counts,
            'allow_exceeding' => $allow_exceeding
        );
    }

    /**
     * Get summary data for specific meldegruppe (treated as jagdbezirk - all species)
     * 
     * @param string $meldegruppe Meldegruppe name (treated as jagdbezirk internally)
     * @param array $species_list Available species
     * @return array Summary data
     */
    private function get_meldegruppe_summary_data($meldegruppe, $species_list) {
        $all_categories = array();
        $combined_limits = array();
        $combined_counts = array();
        $combined_allow_exceeding = array();
        
        foreach ($species_list as $species) {
            $categories_key = 'ahgmh_categories_' . sanitize_key($species);
            $species_categories = get_option($categories_key, array());
            
            // Get the limit mode for this species
            $limit_mode = $this->get_limit_mode($species);
            
            foreach ($species_categories as $category) {
                if (!in_array($category, $all_categories)) {
                    $all_categories[] = $category;
                }
                
                // Get limits from new admin system
                $all_limits = get_option('ahgmh_wildart_limits', array());
                $species_limits = isset($all_limits[$species]) ? $all_limits[$species] : array();
                
                // Determine limits based on mode
                $limit_value = 0;
                if ($limit_mode === 'hegegemeinschaft_total') {
                    // Mode 1: Gesamt-Hegegemeinschaft Limits
                    // Jagdbezirk-gefilterte Tabellen zeigen KEINE Limits (= 0)
                    $limit_value = 0;
                } else {
                    // Mode 2: Jagdbezirk-spezifische Limits  
                    // Zeige spezifische Limits für diese Meldegruppe
                    if (isset($species_limits[$meldegruppe]) && is_array($species_limits[$meldegruppe])) {
                        $limit_value = isset($species_limits[$meldegruppe][$category]) ? (int) $species_limits[$meldegruppe][$category] : 0;
                    }
                }
                
                // For jagdbezirk view, we don't accumulate limits across species
                if (!isset($combined_limits[$category])) {
                    $combined_limits[$category] = $limit_value;
                }
                
                // Get counts for this species/category/meldegruppe (Bug #13b & #14: only approved, direct filtering as jagdbezirk)
                $category_counts = $this->get_category_counts($species, '', $meldegruppe, true);
                $count_value = isset($category_counts[$category]) ? (int) $category_counts[$category] : 0;
                
                // Accumulate counts across species for this jagdbezirk
                if (!isset($combined_counts[$category])) {
                    $combined_counts[$category] = 0;
                }
                $combined_counts[$category] += $count_value;
                
                // Get allow_exceeding
                $exceeding_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
                $species_exceeding = get_option($exceeding_key, array());
                $exceeding_value = isset($species_exceeding[$category]) ? (bool) $species_exceeding[$category] : false;
                
                if (!isset($combined_allow_exceeding[$category])) {
                    $combined_allow_exceeding[$category] = false;
                }
                $combined_allow_exceeding[$category] = $combined_allow_exceeding[$category] || $exceeding_value;
            }
        }
        
        return array(
            'categories' => $all_categories,
            'limits' => $combined_limits,
            'counts' => $combined_counts,
            'allow_exceeding' => $combined_allow_exceeding
        );
    }

    /**
     * Get summary data for specific species + meldegruppe combination
     * 
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name (treated as jagdbezirk internally)
     * @return array Summary data
     */
    private function get_specific_summary_data($species, $meldegruppe) {
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        // Get the limit mode for this species
        $limit_mode = $this->get_limit_mode($species);
        
        // Get limits from new admin system
        $all_limits = get_option('ahgmh_wildart_limits', array());
        $species_limits = isset($all_limits[$species]) ? $all_limits[$species] : array();
        
        // Determine limits based on mode
        $limits = array();
        foreach ($categories as $category) {
            if ($limit_mode === 'hegegemeinschaft_total') {
                // Mode 1: Gesamt-Hegegemeinschaft Limits
                // Species + Jagdbezirk combination zeigt KEINE Limits
                $limits[$category] = 0;
            } else {
                // Mode 2: Jagdbezirk-spezifische Limits  
                // Zeige spezifische Limits für diese Meldegruppe
                $limit_value = 0;
                if (isset($species_limits[$meldegruppe]) && is_array($species_limits[$meldegruppe])) {
                    $limit_value = isset($species_limits[$meldegruppe][$category]) ? (int) $species_limits[$meldegruppe][$category] : 0;
                }
                $limits[$category] = $limit_value;
            }
        }
        
        $exceeding_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        $allow_exceeding = get_option($exceeding_key, array());
        
        // Get counts for specific species + meldegruppe (Bug #13b & #14: only approved, direct filtering as jagdbezirk)
        $counts = $this->get_category_counts($species, '', $meldegruppe, true);

        return array(
            'categories' => $categories,
            'limits' => $limits,
            'counts' => $counts,
            'allow_exceeding' => $allow_exceeding
        );
    }

    /**
     * Save wildart (creates or updates wildart-specific settings)
     */
    public function save_wildart($wildart_name) {
        // Sanitize wildart name
        $wildart_name = sanitize_text_field($wildart_name);
        
        if (empty($wildart_name)) {
            throw new Exception(__('Wildart Name ist erforderlich.', 'abschussplan-hgmh'));
        }
        
        // Initialize empty categories and meldegruppen for new wildart
        $categories_key = 'ahgmh_categories_' . sanitize_key($wildart_name);
        if (!get_option($categories_key, false)) {
            update_option($categories_key, array());
        }
        
        // Initialize wildart-specific meldegruppen configuration
        $this->initialize_wildart_meldegruppen_config($wildart_name);
        
        return true;
    }
    
    /**
     * Delete wildart and optionally its data
     */
    public function delete_wildart($wildart_name, $confirm_delete_data = false) {
        global $wpdb;
        
        $wildart_name = sanitize_text_field($wildart_name);
        
        if (empty($wildart_name)) {
            throw new Exception(__('Wildart Name ist erforderlich.', 'abschussplan-hgmh'));
        }
        
        if ($confirm_delete_data) {
            // Delete all submissions for this wildart (look up ID first)
            $w_table    = $wpdb->prefix . 'hgmh_wildarten';
            $wildart_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $w_table WHERE name = %s",
                $wildart_name
            ) );

            if ($wildart_id) {
                $wpdb->delete($this->table_name, array('wildart_id' => $wildart_id), array('%d'));

                // Remove the wildart entry itself
                $wpdb->delete($w_table, array('id' => $wildart_id), array('%d'));
            }

            // Delete wildart-specific meldegruppen configuration
            $meldegruppen_config_table = $wpdb->prefix . 'hgmh_meldegruppen_config';
            $deleted_config = $wpdb->delete(
                $meldegruppen_config_table,
                array('wildart' => $wildart_name),
                array('%s')
            );
            
            // Delete wildart-specific limits
            $limits_table = $wpdb->prefix . 'hgmh_meldegruppen_limits';
            $deleted_limits = $wpdb->delete(
                $limits_table,
                array('wildart' => $wildart_name),
                array('%s')
            );
            
            // Delete wildart-specific options
            $categories_key = 'ahgmh_categories_' . sanitize_key($wildart_name);
            delete_option($categories_key);
            
            $limits_key = 'abschuss_category_limits_' . sanitize_key($wildart_name);
            delete_option($limits_key);
        }
        
        return true;
    }
    
    /**
     * Save wildart categories
     */
    public function save_wildart_categories($wildart, $categories) {
        $wildart = sanitize_text_field($wildart);
        
        if (empty($wildart)) {
            throw new Exception(__('Wildart ist erforderlich.', 'abschussplan-hgmh'));
        }
        
        // Sanitize categories
        $sanitized_categories = array();
        foreach ($categories as $category) {
            $category = sanitize_text_field(trim($category));
            if (!empty($category)) {
                $sanitized_categories[] = $category;
            }
        }
        
        // Save categories to wildart-specific option
        $categories_key = 'ahgmh_categories_' . sanitize_key($wildart);
        update_option($categories_key, $sanitized_categories);
        
        return true;
    }
    
    /**
     * Save wildart meldegruppen
     */
    public function save_wildart_meldegruppen($wildart, $meldegruppen) {
        global $wpdb;
        
        $wildart = sanitize_text_field($wildart);
        
        if (empty($wildart)) {
            throw new Exception(__('Wildart ist erforderlich.', 'abschussplan-hgmh'));
        }
        
        // Sanitize meldegruppen
        $sanitized_meldegruppen = array();
        foreach ($meldegruppen as $meldegruppe) {
            $meldegruppe = sanitize_text_field(trim($meldegruppe));
            if (!empty($meldegruppe)) {
                $sanitized_meldegruppen[] = $meldegruppe;
            }
        }
        
        $meldegruppen_config_table = $wpdb->prefix . 'hgmh_meldegruppen_config';
        
        // Clear existing meldegruppen for this wildart
        $wpdb->delete(
            $meldegruppen_config_table,
            array('wildart' => $wildart),
            array('%s')
        );
        
        // Insert new meldegruppen
        foreach ($sanitized_meldegruppen as $meldegruppe) {
            $wpdb->insert(
                $meldegruppen_config_table,
                array(
                    'wildart' => $wildart,
                    'meldegruppe' => $meldegruppe,
                    'active' => 1,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%s')
            );
        }
        
        return true;
    }
    
    /**
     * Get overview statistics for a wildart
     */
    public function get_wildart_overview_stats($wildart) {
        global $wpdb;
        
        $wildart = sanitize_text_field($wildart);
        $categories = get_option('ahgmh_categories_' . sanitize_key($wildart), array());
        
        $total_limit = 0;
        $total_current = 0;
        
        foreach ($categories as $category) {
            // Get default limits for this species
            $limits = $this->get_meldegruppen_limits($wildart, null);
            $limit = isset($limits[$category]) ? intval($limits[$category]) : 0;
            
            // Count submissions for this species and category
            $current = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$this->table_name} 
                WHERE game_species = %s AND field2 = %s
            ", $wildart, $category));
            
            $total_limit += $limit;
            $total_current += intval($current);
        }
        
        $percentage = $total_limit > 0 ? round(($total_current / $total_limit) * 100, 1) : 0;
        
        $status = 'low';
        if ($percentage >= 90) {
            $status = 'high';
        } elseif ($percentage >= 70) {
            $status = 'medium';
        }
        
        return array(
            'current' => $total_current,
            'limit' => $total_limit,
            'percentage' => $percentage,
            'status' => $status
        );
    }
    
    /**
     * Initialize wildart-specific meldegruppen configuration
     */
    private function initialize_wildart_meldegruppen_config($wildart) {
        global $wpdb;
        
        $meldegruppen_config_table = $wpdb->prefix . 'hgmh_meldegruppen_config';
        
        // Check if configuration already exists
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $meldegruppen_config_table WHERE wildart = %s
        ", $wildart));
        
        if ($existing == 0) {
            // Initialize with empty configuration - user will add meldegruppen later
            // This ensures the wildart exists in the system even without meldegruppen
            $this->save_wildart_meldegruppen($wildart, array());
        }
        
        return true;
    }
    
    /**
     * Set wildart limit mode
     * 
     * @param string $wildart Wildart name
     * @param string $mode 'meldegruppen_specific' or 'hegegemeinschaft_total'
     * @return bool
     */
    public function set_wildart_limit_mode($wildart, $mode) {
        $wildart = sanitize_text_field($wildart);
        $mode = sanitize_text_field($mode);
        
        if (!in_array($mode, array('meldegruppen_specific', 'hegegemeinschaft_total'))) {
            $mode = 'hegegemeinschaft_total'; // Default fallback
        }
        
        return update_option("ahgmh_limit_mode_" . sanitize_key($wildart), $mode);
    }
    
    /**
     * Get wildart limit mode
     * 
     * @param string $wildart Wildart name
     * @return string 'meldegruppen_specific' or 'hegegemeinschaft_total'
     */
    public function get_wildart_limit_mode($wildart) {
        $wildart = sanitize_text_field($wildart);
        return get_option("ahgmh_limit_mode_" . sanitize_key($wildart), 'meldegruppen_specific');
    }
    
    /**
     * Save meldegruppen-specific limit
     * 
     * @param string $wildart Wildart name
     * @param string $meldegruppe Meldegruppe name
     * @param string $kategorie Category name
     * @param int $limit Limit value
     * @return bool|int
     */
    public function save_meldegruppen_limit($wildart, $meldegruppe, $kategorie, $limit) {
        global $wpdb;
        
        $meldegruppen_config_table = $wpdb->prefix . 'hgmh_meldegruppen_config';
        
        // Check if entry exists
        $existing_id = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM $meldegruppen_config_table 
            WHERE wildart = %s AND meldegruppe = %s AND kategorie = %s
        ", sanitize_text_field($wildart), sanitize_text_field($meldegruppe), sanitize_text_field($kategorie)));
        
        $data = array(
            'wildart' => sanitize_text_field($wildart),
            'meldegruppe' => sanitize_text_field($meldegruppe),
            'kategorie' => sanitize_text_field($kategorie),
            'limit_value' => intval($limit),
            'limit_mode' => 'meldegruppen_specific'
        );
        
        if ($existing_id) {
            // Update existing
            return $wpdb->update(
                $meldegruppen_config_table,
                $data,
                array('id' => $existing_id),
                array('%s', '%s', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new
            $data['active'] = 1;
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert(
                $meldegruppen_config_table,
                $data,
                array('%s', '%s', '%s', '%d', '%s', '%d', '%s')
            );
        }
    }
    
    /**
     * Save hegegemeinschaft total limit
     * 
     * @param string $wildart Wildart name
     * @param string $kategorie Category name
     * @param int $limit Limit value
     * @return bool
     */
    public function save_hegegemeinschaft_limit($wildart, $kategorie, $limit) {
        $wildart = sanitize_text_field($wildart);
        $kategorie = sanitize_text_field($kategorie);
        $limit = intval($limit);
        
        $option_key = "ahgmh_hegegemeinschaft_limit_" . sanitize_key($wildart) . "_" . sanitize_key($kategorie);
        return update_option($option_key, $limit);
    }
    
    /**
     * Get hegegemeinschaft total limit
     * 
     * @param string $wildart Wildart name
     * @param string $kategorie Category name
     * @return int
     */
    public function get_hegegemeinschaft_limit($wildart, $kategorie) {
        $wildart = sanitize_text_field($wildart);
        $kategorie = sanitize_text_field($kategorie);
        
        $option_key = "ahgmh_hegegemeinschaft_limit_" . sanitize_key($wildart) . "_" . sanitize_key($kategorie);
        return intval(get_option($option_key, 0));
    }
    
    /**
     * Get status badge for limit comparison
     * 
     * @param int $ist Current count
     * @param int $soll Target limit
     * @return string HTML status badge
     */
    public function get_status_badge($ist, $soll) {
        if ($soll <= 0) {
            return '<span class="status-badge status-na">⚪ N/A</span>';
        }
        
        $percentage = round(($ist / $soll) * 100);
        
        if ($percentage < 80) {
            return '<span class="status-badge status-low">🟢 ' . $percentage . '%</span>';
        } elseif ($percentage < 95) {
            return '<span class="status-badge status-medium">🟡 ' . $percentage . '%</span>';
        } elseif ($percentage <= 110) {
            return '<span class="status-badge status-high">🔴 ' . $percentage . '%</span>';
        } else {
            return '<span class="status-badge status-exceeded">🔥 ' . $percentage . '%</span>';
        }
    }
    
    /**
     * Count submissions by species, category and meldegruppe
     * 
     * @param string $species Species name
     * @param string $category Category name
     * @param string $meldegruppe Meldegruppe name
     * @return int
     */
    public function count_submissions_by_species_category_meldegruppe($species, $category, $meldegruppe) {
        global $wpdb;

        $w  = $wpdb->prefix . 'hgmh_wildarten';
        $e  = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $jm = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
        $m  = $wpdb->prefix . 'hgmh_meldegruppen';

        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$this->table_name} s
            LEFT JOIN $w w  ON s.wildart_id = w.id
            LEFT JOIN $e e  ON s.eigenjagdbezirk_id = e.id
            LEFT JOIN $jm jm ON e.id = jm.jagdbezirk_id
            LEFT JOIN $m m  ON jm.meldegruppe_id = m.id
            WHERE w.name = %s
              AND s.category = %s
              AND m.name = %s
        ", sanitize_text_field($species), sanitize_text_field($category), sanitize_text_field($meldegruppe)));
    }
    
    /**
     * Get applicable limit for species/meldegruppe/category combination
     * Implements fallback logic: meldegruppe-specific > hegegemeinschaft total > old system
     *
     * @param string $species Species name
     * @param string $meldegruppe Meldegruppe name (can be null for total limits)
     * @param string $category Category name
     * @return int
     */
    public function get_applicable_limit($species, $meldegruppe, $category) {
        global $wpdb;

        if (!empty($meldegruppe)) {
            // Try to get meldegruppe-specific limit from config table
            $meldegruppen_config_table = $wpdb->prefix . 'hgmh_meldegruppen_config';
            $limit_value = $wpdb->get_var($wpdb->prepare("
                SELECT limit_value FROM $meldegruppen_config_table
                WHERE wildart = %s AND meldegruppe = %s AND kategorie = %s
                  AND limit_value IS NOT NULL AND limit_mode = 'meldegruppen_specific'
            ", sanitize_text_field($species), sanitize_text_field($meldegruppe), sanitize_text_field($category)));

            if ($limit_value !== null) {
                return intval($limit_value);
            }
        }

        // Fallback to hegegemeinschaft total limit
        $hegegemeinschaft_limit = $this->get_hegegemeinschaft_limit($species, $category);
        if ($hegegemeinschaft_limit > 0) {
            return $hegegemeinschaft_limit;
        }

        // Final fallback to old WordPress options system
        $limits_key = 'abschuss_category_limits_' . sanitize_key($species);
        $species_limits = get_option($limits_key, array());
        return isset($species_limits[$category]) ? intval($species_limits[$category]) : 0;
    }

    /**
     * Check if wildart has any submissions
     *
     * @param string $wildart Wildart name
     * @return bool True if submissions exist, false otherwise
     */
    public function check_wildart_has_submissions($wildart) {
        global $wpdb;

        $wildart = sanitize_text_field($wildart);

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE game_species = %s",
            $wildart
        );

        $count = $wpdb->get_var($query);

        return (int) $count > 0;
    }

    /**
     * Count submissions this month by status
     *
     * @return array Array with counts per status
     */
    public function count_submissions_this_month_by_status() {
        global $wpdb;

        $current_month = date('m');
        $current_year = date('Y');

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $this->table_name
             WHERE MONTH(created_at) = %s
             AND YEAR(created_at) = %s
             GROUP BY status",
            $current_month, $current_year
        ));

        $status_counts = array(
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0
        );

        foreach ($results as $row) {
            $status = strtolower($row->status);
            if (isset($status_counts[$status])) {
                $status_counts[$status] = (int) $row->count;
            }
            $status_counts['total'] += (int) $row->count;
        }

        return $status_counts;
    }

    /**
     * Count submissions last month
     *
     * @return int Count of submissions last month
     */
    public function count_submissions_last_month() {
        global $wpdb;

        $last_month = date('m', strtotime('-1 month'));
        $last_month_year = date('Y', strtotime('-1 month'));

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name
             WHERE MONTH(created_at) = %s
             AND YEAR(created_at) = %s",
            $last_month, $last_month_year
        );

        $count = $wpdb->get_var($query);

        return (int) $count;
    }

    /**
     * Get status counts by species
     *
     * @return array Array with species as keys and status counts as values
     */
    public function get_status_counts_by_species() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT game_species, status, COUNT(*) as count
             FROM $this->table_name
             GROUP BY game_species, status
             ORDER BY game_species, status"
        );

        $species_status = array();

        foreach ($results as $row) {
            $species = $row->game_species;
            $status = strtolower($row->status);

            if (!isset($species_status[$species])) {
                $species_status[$species] = array(
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'total' => 0
                );
            }

            if (isset($species_status[$species][$status])) {
                $species_status[$species][$status] = (int) $row->count;
            }
            $species_status[$species]['total'] += (int) $row->count;
        }

        return $species_status;
    }
}
