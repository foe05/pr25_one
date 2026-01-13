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
}
