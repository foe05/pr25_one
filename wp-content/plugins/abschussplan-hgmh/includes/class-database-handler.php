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
        $this->table_name = $wpdb->prefix . 'ahgmh_submissions';
    }

    /**
     * Create the database table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL DEFAULT 0,
            game_species varchar(100) NOT NULL DEFAULT 'Rotwild',
            field1 text NOT NULL,
            field2 text NOT NULL,
            field3 text NOT NULL,
            field4 text NOT NULL,
            field5 text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create Jagdbezirk configuration table
        $this->create_jagdbezirk_table();
    }
    
    /**
     * Get the table name
     */
    public function get_table_name() {
        return $this->table_name;
    }
    
    /**
     * Create the Jagdbezirk configuration table
     */
    public function create_jagdbezirk_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            wildart varchar(255) DEFAULT NULL,
            jagdbezirk varchar(255) NOT NULL,
            meldegruppe varchar(255) NOT NULL,
            ungueltig tinyint(1) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            bemerkung text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY wildart_active (wildart, active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create wildart-specific meldegruppen configuration table
        $this->create_meldegruppen_config_table();
        
        // Create meldegruppen-specific limits table
        $this->create_meldegruppen_limits_table();
        
        // Add some default Jagdbezirke if table is empty
        $this->seed_default_jagdbezirke();
    }

    /**
     * Create the meldegruppen configuration table for wildart-specific settings
     */
    public function create_meldegruppen_config_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_meldegruppen_config';
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
        
        $table_name = $wpdb->prefix . 'ahgmh_meldegruppen_limits';
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
     * Seed default Jagdbezirke if table is empty
     */
    private function seed_default_jagdbezirke() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        // Check if table has any records
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($count == 0) {
            // Add some default entries
            $defaults = array(
                array('jagdbezirk' => 'Jagdbezirk 1', 'meldegruppe' => 'Gruppe A', 'ungueltig' => 0, 'bemerkung' => 'Standard Jagdbezirk'),
                array('jagdbezirk' => 'Jagdbezirk 2', 'meldegruppe' => 'Gruppe B', 'ungueltig' => 0, 'bemerkung' => 'Standard Jagdbezirk'),
                array('jagdbezirk' => 'Jagdbezirk 3', 'meldegruppe' => 'Gruppe A', 'ungueltig' => 1, 'bemerkung' => 'Inaktiver Jagdbezirk')
            );
            
            foreach ($defaults as $default) {
                $wpdb->insert(
                    $table_name,
                    $default,
                    array('%s', '%s', '%d', '%s')
                );
            }
        }
    }

    /**
     * Insert form submission into database
     *
     * @param array $data Form data
     * @return int|false The number of rows inserted, or false on error
     */
    public function insert_submission($data) {
        global $wpdb;
        
        // Sanitize data - include user_id and game_species
        $sanitized_data = array(
            'user_id' => isset($data['user_id']) ? intval($data['user_id']) : 0,
            'game_species' => isset($data['game_species']) ? sanitize_text_field($data['game_species']) : 'Rotwild',
            'field1' => sanitize_text_field($data['field1']),
            'field2' => sanitize_text_field($data['field2']),
            'field3' => sanitize_text_field($data['field3']),
            'field4' => sanitize_text_field($data['field4']),
            'field5' => sanitize_text_field($data['field5'])
        );
        
        // Insert data
        $result = $wpdb->insert(
            $this->table_name,
            $sanitized_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get all form submissions
     *
     * @param int $limit Number of results to get
     * @param int $offset Offset for pagination
     * @return array Array of submissions
     */
    public function get_submissions($limit = 10, $offset = 0) {
        global $wpdb;
        
        $query = "SELECT s.*, j.meldegruppe 
                  FROM $this->table_name s 
                  LEFT JOIN {$wpdb->prefix}ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk 
                  ORDER BY s.created_at DESC";
        
        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $results;
    }

    /**
     * Count total submissions
     *
     * @return int Total count of submissions
     */
    public function count_submissions() {
        global $wpdb;
        
        $query = "SELECT COUNT(*) FROM $this->table_name";
        $count = $wpdb->get_var($query);
        
        return (int) $count;
    }

    /**
     * Count submissions this month
     *
     * @return int Count of submissions this month
     */
    public function count_submissions_this_month() {
        global $wpdb;
        
        // Get the current month and year in a more reliable way
        $current_month = date('m');
        $current_year = date('Y');
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name 
             WHERE MONTH(created_at) = %s 
             AND YEAR(created_at) = %s",
            $current_month, $current_year
        );
        
        $count = $wpdb->get_var($query);
        
        return (int) $count;
    }

    /**
     * Count active users (users who have submitted)
     *
     * @return int Count of active users
     */
    public function count_active_users() {
        global $wpdb;
        
        $query = "SELECT COUNT(DISTINCT user_id) FROM $this->table_name WHERE user_id > 0";
        $count = $wpdb->get_var($query);
        
        return (int) $count;
    }

    /**
     * Count submissions by species and category
     *
     * @param string $species Species name
     * @param string $category Category name
     * @return int Count of submissions
     */
    public function count_submissions_by_species_category($species, $category) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name 
             WHERE game_species = %s AND field2 = %s",
            $species, $category
        );
        $count = $wpdb->get_var($query);
        
        return (int) $count;
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
     * Delete all submissions for a specific species
     *
     * @param string $species The game species
     * @return bool True on success, false on failure
     */
    public function delete_submissions_by_species($species) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('game_species' => $species),
            array('%s')
        );
        
        return $result !== false;
    }

    /**
     * Get submission counts per category
     *
     * @param string $species Filter by game species (optional)
     * @param string $meldegruppe Filter by meldegruppe (optional)
     * @return array Array with category counts
     */
    public function get_category_counts($species = '', $meldegruppe = '') {
        global $wpdb;
        
        $query = "SELECT s.field2 as category, COUNT(*) as count 
                  FROM $this->table_name s";
        
        // Add JOIN if meldegruppe filter is needed
        if (!empty($meldegruppe)) {
            $query .= " LEFT JOIN {$wpdb->prefix}ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk";
        }
        
        $query .= " WHERE s.field2 != ''";
        
        if (!empty($species)) {
            $query .= $wpdb->prepare(" AND s.game_species = %s", $species);
        }
        
        if (!empty($meldegruppe)) {
            $query .= $wpdb->prepare(" AND j.meldegruppe = %s", $meldegruppe);
        }
        
        $query .= " GROUP BY s.field2";
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
     * @param int $limit Number of results to get
     * @param int $offset Offset for pagination
     * @param string $species Filter by game species (optional)
     * @return array Array of submissions
     */
    public function get_submissions_by_species($limit = 10, $offset = 0, $species = '') {
        global $wpdb;
        
        $query = "SELECT s.*, j.meldegruppe 
                  FROM $this->table_name s 
                  LEFT JOIN {$wpdb->prefix}ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk";
        
        if (!empty($species)) {
            $query .= $wpdb->prepare(" WHERE s.game_species = %s", $species);
        }
        
        $query .= " ORDER BY s.created_at DESC";
        
        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $results;
    }

    /**
     * Count submissions filtered by species
     *
     * @param string $species Filter by game species (optional)
     * @return int Total count of submissions
     */
    public function count_submissions_by_species($species = '') {
        global $wpdb;
        
        $query = "SELECT COUNT(*) FROM $this->table_name";
        
        if (!empty($species)) {
            $query .= $wpdb->prepare(" WHERE game_species = %s", $species);
        }
        
        $count = $wpdb->get_var($query);
        
        return (int) $count;
    }
    
    /**
     * Check if WUS number already exists in database
     * 
     * @param string $wus_number The WUS number to check
     * @return bool True if WUS exists, false otherwise
     */
    public function check_wus_exists($wus_number) {
        global $wpdb;
        
        $query = "SELECT COUNT(*) FROM $this->table_name WHERE field3 = %s";
        $count = $wpdb->get_var($wpdb->prepare($query, $wus_number));
        
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
        
        $config_table = $wpdb->prefix . 'ahgmh_meldegruppen_config';
        
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
     * Get global meldegruppen from jagdbezirke table
     * 
     * @return array Array of global meldegruppen
     */
    public function get_global_meldegruppen() {
        global $wpdb;
        
        $jagdbezirke_table = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        $query = "SELECT DISTINCT meldegruppe 
                  FROM $jagdbezirke_table 
                  WHERE ungueltig = 0 
                  ORDER BY meldegruppe";
        
        return $wpdb->get_col($query);
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
        
        $config_table = $wpdb->prefix . 'ahgmh_meldegruppen_config';
        
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
        
        $limits_table = $wpdb->prefix . 'ahgmh_meldegruppen_limits';
        
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
        
        $limits_table = $wpdb->prefix . 'ahgmh_meldegruppen_limits';
        
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
        
        $limits_table = $wpdb->prefix . 'ahgmh_meldegruppen_limits';
        
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
        
        $limits_table = $wpdb->prefix . 'ahgmh_meldegruppen_limits';
        
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
        
        $limits_table = $wpdb->prefix . 'ahgmh_meldegruppen_limits';
        
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
     * This method removes the database table
     */
    public static function cleanup_database() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_submissions';
        $jagdbezirk_table = $wpdb->prefix . 'ahgmh_jagdbezirke';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        $wpdb->query("DROP TABLE IF EXISTS $jagdbezirk_table");
    }
    
    /**
     * Get all Jagdbezirke
     */
    public function get_jagdbezirke() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            // Create table if it doesn't exist
            $this->create_jagdbezirk_table();
        }
        
        $query = "SELECT * FROM $table_name ORDER BY jagdbezirk ASC";
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get active Jagdbezirke (not marked as invalid)
     */
    public function get_active_jagdbezirke() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            // Create table if it doesn't exist
            $this->create_jagdbezirk_table();
        }
        
        $query = "SELECT * FROM $table_name WHERE ungueltig = 0 AND wildart IS NULL ORDER BY jagdbezirk ASC";
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Insert new Jagdbezirk
     */
    public function insert_jagdbezirk($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        $sanitized_data = array(
            'jagdbezirk' => sanitize_text_field($data['jagdbezirk']),
            'meldegruppe' => sanitize_text_field($data['meldegruppe']),
            'ungueltig' => (isset($data['ungueltig']) && ($data['ungueltig'] === '1' || $data['ungueltig'] === 1)) ? 1 : 0,
            'bemerkung' => sanitize_textarea_field($data['bemerkung'])
        );
        
        $result = $wpdb->insert(
            $table_name,
            $sanitized_data,
            array('%s', '%s', '%d', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update Jagdbezirk
     */
    public function update_jagdbezirk($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        $sanitized_data = array(
            'jagdbezirk' => sanitize_text_field($data['jagdbezirk']),
            'meldegruppe' => sanitize_text_field($data['meldegruppe']),
            'ungueltig' => (isset($data['ungueltig']) && ($data['ungueltig'] === '1' || $data['ungueltig'] === 1)) ? 1 : 0,
            'bemerkung' => sanitize_textarea_field($data['bemerkung'])
        );
        
        return $wpdb->update(
            $table_name,
            $sanitized_data,
            array('id' => $id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
    }
    
    /**
     * Delete Jagdbezirk
     */
    public function delete_jagdbezirk($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Delete all Jagdbezirke
     */
    public function delete_all_jagdbezirke() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        return $wpdb->query("DELETE FROM $table_name");
    }

    /**
     * Get jagdbezirke for specific wildart
     */
    public function get_wildart_jagdbezirke($wildart) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        if ($this->is_wildart_specific_enabled()) {
            // Return wildart-specific jagdbezirke
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE wildart = %s AND active = 1 ORDER BY jagdbezirk ASC",
                $wildart
            ), ARRAY_A);
        } else {
            // Return global jagdbezirke
            $results = $wpdb->get_results(
                "SELECT * FROM $table_name WHERE wildart IS NULL AND active = 1 ORDER BY jagdbezirk ASC",
                ARRAY_A
            );
        }
        
        return $results ?: array();
    }

    /**
     * Save wildart-specific jagdbezirk
     */
    public function save_wildart_jagdbezirk($wildart, $jagdbezirk_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        return $wpdb->insert(
            $table_name,
            array(
                'wildart' => $wildart,
                'jagdbezirk' => $jagdbezirk_data['jagdbezirk'],
                'meldegruppe' => $jagdbezirk_data['meldegruppe'],
                'bemerkung' => $jagdbezirk_data['bemerkung'],
                'active' => 1
            ),
            array('%s', '%s', '%s', '%s', '%d')
        );
    }

    /**
     * Save global jagdbezirk
     */
    public function save_global_jagdbezirk($jagdbezirk_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        return $wpdb->insert(
            $table_name,
            array(
                'wildart' => null,
                'jagdbezirk' => $jagdbezirk_data['jagdbezirk'],
                'meldegruppe' => $jagdbezirk_data['meldegruppe'],
                'bemerkung' => $jagdbezirk_data['bemerkung'],
                'active' => 1
            ),
            array('%s', '%s', '%s', '%s', '%d')
        );
    }

    /**
     * Clear global jagdbezirke
     */
    public function clear_global_jagdbezirke() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        return $wpdb->query("DELETE FROM $table_name WHERE wildart IS NULL");
    }

    /**
     * Clear wildart-specific jagdbezirke
     */
    public function clear_wildart_jagdbezirke() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        return $wpdb->query("DELETE FROM $table_name WHERE wildart IS NOT NULL");
    }

    /**
     * Get all meldegruppen (for public summary validation)
     * 
     * @return array Array of all available meldegruppen
     */
    public function get_all_meldegruppen() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        $query = "SELECT DISTINCT meldegruppe FROM $table_name WHERE meldegruppe != '' ORDER BY meldegruppe";
        $results = $wpdb->get_col($query);
        
        return $results ?: array();
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
            // Case 3: Show specific meldegruppe, all species for this meldegruppe
            $summary_data = $this->get_meldegruppe_summary_data($meldegruppe, $available_species);
            
        } else {
            // Case 4: Show specific species + meldegruppe combination
            $summary_data = $this->get_specific_summary_data($species, $meldegruppe);
        }
        
        return $summary_data;
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
                
                // Get limits for this species/category
                $limits_key = 'abschuss_category_limits_' . sanitize_key($species);
                $species_limits = get_option($limits_key, array());
                $limit_value = isset($species_limits[$category]) ? (int) $species_limits[$category] : 0;
                
                // Accumulate limits
                if (!isset($combined_limits[$category])) {
                    $combined_limits[$category] = 0;
                }
                $combined_limits[$category] += $limit_value;
                
                // Get counts for this species/category
                $category_counts = $this->get_category_counts($species);
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
        
        $limits_key = 'abschuss_category_limits_' . sanitize_key($species);
        $limits = get_option($limits_key, array());
        
        $exceeding_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        $allow_exceeding = get_option($exceeding_key, array());
        
        $counts = $this->get_category_counts($species);
        
        return array(
            'categories' => $categories,
            'limits' => $limits,
            'counts' => $counts,
            'allow_exceeding' => $allow_exceeding
        );
    }

    /**
     * Get summary data for specific meldegruppe (all species)
     * 
     * @param string $meldegruppe Meldegruppe name
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
            
            foreach ($species_categories as $category) {
                if (!in_array($category, $all_categories)) {
                    $all_categories[] = $category;
                }
                
                // Get limits for this species/category
                $limits_key = 'abschuss_category_limits_' . sanitize_key($species);
                $species_limits = get_option($limits_key, array());
                $limit_value = isset($species_limits[$category]) ? (int) $species_limits[$category] : 0;
                
                // Accumulate limits
                if (!isset($combined_limits[$category])) {
                    $combined_limits[$category] = 0;
                }
                $combined_limits[$category] += $limit_value;
                
                // Get counts for this species/category/meldegruppe
                $category_counts = $this->get_category_counts($species, $meldegruppe);
                $count_value = isset($category_counts[$category]) ? (int) $category_counts[$category] : 0;
                
                // Accumulate counts
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
     * @param string $meldegruppe Meldegruppe name
     * @return array Summary data
     */
    private function get_specific_summary_data($species, $meldegruppe) {
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        $limits_key = 'abschuss_category_limits_' . sanitize_key($species);
        $limits = get_option($limits_key, array());
        
        $exceeding_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        $allow_exceeding = get_option($exceeding_key, array());
        
        $counts = $this->get_category_counts($species, $meldegruppe);
        
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
            // Delete all submissions for this wildart
            $deleted_submissions = $wpdb->delete(
                $this->table_name,
                array('game_species' => $wildart_name),
                array('%s')
            );
            
            // Delete wildart-specific jagdbezirke
            $jagdbezirke_table = $wpdb->prefix . 'ahgmh_jagdbezirke';
            $deleted_jagdbezirke = $wpdb->delete(
                $jagdbezirke_table,
                array('wildart' => $wildart_name),
                array('%s')
            );
            
            // Delete wildart-specific meldegruppen configuration
            $meldegruppen_config_table = $wpdb->prefix . 'ahgmh_meldegruppen_config';
            $deleted_config = $wpdb->delete(
                $meldegruppen_config_table,
                array('wildart' => $wildart_name),
                array('%s')
            );
            
            // Delete wildart-specific limits
            $limits_table = $wpdb->prefix . 'ahgmh_meldegruppen_limits';
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
        
        $meldegruppen_config_table = $wpdb->prefix . 'ahgmh_meldegruppen_config';
        
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
        
        $meldegruppen_config_table = $wpdb->prefix . 'ahgmh_meldegruppen_config';
        
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
            $mode = 'meldegruppen_specific'; // Default fallback
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
        
        $meldegruppen_config_table = $wpdb->prefix . 'ahgmh_meldegruppen_config';
        
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
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->table_name} 
            WHERE game_species = %s 
                AND field2 = %s 
                AND field4 = %s
        ", sanitize_text_field($species), sanitize_text_field($category), sanitize_text_field($meldegruppe)));
    }
}
