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
class CFD_Database_Handler {
    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'custom_form_submissions';
    }

    /**
     * Create the database table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            field1 text NOT NULL,
            field2 text NOT NULL,
            field3 text NOT NULL,
            field4 text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert form submission into database
     *
     * @param array $data Form data
     * @return int|false The number of rows inserted, or false on error
     */
    public function insert_submission($data) {
        global $wpdb;
        
        // Sanitize data
        $sanitized_data = array(
            'field1' => sanitize_text_field($data['field1']),
            'field2' => sanitize_text_field($data['field2']),
            'field3' => sanitize_text_field($data['field3']),
            'field4' => sanitize_text_field($data['field4'])
        );
        
        // Insert data
        $result = $wpdb->insert(
            $this->table_name,
            $sanitized_data,
            array('%s', '%s', '%s', '%s')
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
        
        $query = "SELECT * FROM $this->table_name ORDER BY created_at DESC";
        
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
}
