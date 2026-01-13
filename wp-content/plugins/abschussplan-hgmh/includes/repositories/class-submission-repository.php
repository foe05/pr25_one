<?php
/**
 * Submission Repository Class
 * Data access layer for submission operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository class for handling submission database operations
 * Provides clean abstraction layer separating business logic from SQL
 */
class HGMH_Submission_Repository {
    /**
     * Submissions table name
     */
    private $submissions_table;

    /**
     * Wildart reference table name
     */
    private $wildart_table;

    /**
     * Eigenjagdbezirk reference table name
     */
    private $eigenjagdbezirk_table;

    /**
     * Meldegruppe reference table name
     */
    private $meldegruppe_table;

    /**
     * WordPress database object
     */
    private $wpdb;

    /**
     * Constructor
     * Initializes table names based on WordPress table prefix
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->submissions_table = $wpdb->prefix . 'ahgmh_submissions_v2';
        $this->wildart_table = $wpdb->prefix . 'ahgmh_wildart';
        $this->eigenjagdbezirk_table = $wpdb->prefix . 'ahgmh_eigenjagdbezirk';
        $this->meldegruppe_table = $wpdb->prefix . 'ahgmh_meldegruppe';
    }

    /**
     * Find a submission by ID with enriched reference data
     *
     * Retrieves a submission record with joined reference data from wildart,
     * eigenjagdbezirk, and meldegruppe tables.
     *
     * @param int $id Submission ID
     * @return object|null Submission object with enriched data or null if not found
     */
    public function find($id) {
        $sql = $this->wpdb->prepare(
            "SELECT
                s.*,
                w.name as wildart_name,
                e.name as eigenjagdbezirk_name,
                m.name as meldegruppe_name
            FROM {$this->submissions_table} s
            LEFT JOIN {$this->wildart_table} w ON s.wildart_id = w.id
            LEFT JOIN {$this->eigenjagdbezirk_table} e ON s.eigenjagdbezirk_id = e.id
            LEFT JOIN {$this->meldegruppe_table} m ON s.meldegruppe_id = m.id
            WHERE s.id = %d",
            $id
        );

        return $this->wpdb->get_row($sql);
    }

    /**
     * Create a new submission record
     *
     * Inserts a new submission into the database with required fields.
     *
     * @param array $data Submission data array with keys:
     *                    - wildart_id (int): ID of the wildart (game species)
     *                    - eigenjagdbezirk_id (int): ID of the eigenjagdbezirk (hunting district)
     *                    - category (string): Category classification (e.g., 'AK1', 'AK2')
     *                    - harvest_date (string): Date and time of harvest (MySQL datetime format)
     *                    - submitted_by_user_id (int): WordPress user ID of submitter
     * @return int|false New submission ID on success, false on failure
     */
    public function create($data) {
        // Prepare data for insertion
        $insert_data = array(
            'wildart_id' => isset($data['wildart_id']) ? (int) $data['wildart_id'] : 0,
            'eigenjagdbezirk_id' => isset($data['eigenjagdbezirk_id']) ? (int) $data['eigenjagdbezirk_id'] : 0,
            'category' => isset($data['category']) ? sanitize_text_field($data['category']) : '',
            'harvest_date' => isset($data['harvest_date']) ? sanitize_text_field($data['harvest_date']) : '',
            'submitted_by_user_id' => isset($data['submitted_by_user_id']) ? (int) $data['submitted_by_user_id'] : 0
        );

        // Insert data with proper data types
        $result = $this->wpdb->insert(
            $this->submissions_table,
            $insert_data,
            array('%d', '%d', '%s', '%s', '%d')
        );

        // Log error for debugging if WP_DEBUG is enabled
        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HGMH Submission Repository insert error: ' . $this->wpdb->last_error);
            error_log('HGMH Submission Repository last query: ' . $this->wpdb->last_query);
        }

        return $result ? $this->wpdb->insert_id : false;
    }
}
