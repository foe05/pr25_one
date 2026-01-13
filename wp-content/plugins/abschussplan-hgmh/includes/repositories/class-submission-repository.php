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
}
