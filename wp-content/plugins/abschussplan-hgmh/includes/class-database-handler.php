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
            jagdbezirk varchar(255) NOT NULL,
            meldegruppe varchar(255) NOT NULL,
            ungueltig tinyint(1) NOT NULL DEFAULT 0,
            bemerkung text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add some default Jagdbezirke if table is empty
        $this->seed_default_jagdbezirke();
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
     * Delete all submissions
     *
     * @return bool True on success, false on failure
     */
    public function delete_all_submissions() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE $this->table_name");
        
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
        
        $query = "SELECT * FROM $table_name WHERE ungueltig = 0 ORDER BY jagdbezirk ASC";
        
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
}
