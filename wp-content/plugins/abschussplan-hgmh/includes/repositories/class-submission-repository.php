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
 *
 * Usage Examples:
 *
 * // Create new submission
 * $repo = new HGMH_Submission_Repository();
 * $submission_id = $repo->create([
 *     'wildart_id' => 1,
 *     'eigenjagdbezirk_id' => 2,
 *     'category' => 'AK1',
 *     'harvest_date' => '2024-01-15 14:30:00',
 *     'submitted_by_user_id' => 123
 * ]);
 *
 * // Find submission by ID
 * $submission = $repo->find($submission_id);
 *
 * // Update submission
 * $repo->update($submission_id, [
 *     'category' => 'AK2',
 *     'harvest_date' => '2024-01-15 15:00:00'
 * ]);
 *
 * // Get submissions for obmann (filtered by assigned meldegruppen)
 * $submissions = $repo->get_for_obmann($user_id, $wildart_id, 'pending');
 *
 * // Count submissions by status
 * $pending_count = $repo->count_by_status('pending', $obmann_user_id);
 *
 * // Update submission status with additional data
 * $repo->update_status($submission_id, 'approved', [
 *     'approved_by_user_id' => get_current_user_id(),
 *     'approved_at' => current_time('mysql')
 * ]);
 *
 * // Delete submission
 * $repo->delete($submission_id);
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

    /**
     * Update an existing submission record
     *
     * Updates a submission record with the provided data.
     *
     * @param int $id Submission ID to update
     * @param array $data Submission data array with keys:
     *                    - wildart_id (int): ID of the wildart (game species)
     *                    - eigenjagdbezirk_id (int): ID of the eigenjagdbezirk (hunting district)
     *                    - category (string): Category classification (e.g., 'AK1', 'AK2')
     *                    - harvest_date (string): Date and time of harvest (MySQL datetime format)
     *                    - submitted_by_user_id (int): WordPress user ID of submitter
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        // Prepare data for update
        $update_data = array();
        $format = array();

        if (isset($data['wildart_id'])) {
            $update_data['wildart_id'] = (int) $data['wildart_id'];
            $format[] = '%d';
        }

        if (isset($data['eigenjagdbezirk_id'])) {
            $update_data['eigenjagdbezirk_id'] = (int) $data['eigenjagdbezirk_id'];
            $format[] = '%d';
        }

        if (isset($data['category'])) {
            $update_data['category'] = sanitize_text_field($data['category']);
            $format[] = '%s';
        }

        if (isset($data['harvest_date'])) {
            $update_data['harvest_date'] = sanitize_text_field($data['harvest_date']);
            $format[] = '%s';
        }

        if (isset($data['submitted_by_user_id'])) {
            $update_data['submitted_by_user_id'] = (int) $data['submitted_by_user_id'];
            $format[] = '%d';
        }

        // Return false if no data to update
        if (empty($update_data)) {
            return false;
        }

        // Update data with proper data types
        $result = $this->wpdb->update(
            $this->submissions_table,
            $update_data,
            array('id' => (int) $id),
            $format,
            array('%d')
        );

        // Log error for debugging if WP_DEBUG is enabled
        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HGMH Submission Repository update error: ' . $this->wpdb->last_error);
            error_log('HGMH Submission Repository last query: ' . $this->wpdb->last_query);
        }

        return $result !== false;
    }

    /**
     * Delete a submission record
     *
     * Removes a submission from the database by ID.
     *
     * @param int $id Submission ID to delete
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        // Delete record using prepared statement
        $result = $this->wpdb->delete(
            $this->submissions_table,
            array('id' => (int) $id),
            array('%d')
        );

        // Log error for debugging if WP_DEBUG is enabled
        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HGMH Submission Repository delete error: ' . $this->wpdb->last_error);
            error_log('HGMH Submission Repository last query: ' . $this->wpdb->last_query);
        }

        return $result !== false;
    }

    /**
     * Get submissions for Obmann (district supervisor)
     *
     * Retrieves submissions filtered by the obmann's assigned meldegruppen.
     * Returns enriched data with joined reference tables.
     *
     * @param int $user_id WordPress user ID of the obmann
     * @param int|null $wildart_id Optional wildart ID to filter by
     * @param string|null $status Optional status to filter by
     * @return array Array of submission objects with enriched data
     */
    public function get_for_obmann($user_id, $wildart_id = null, $status = null) {
        if (!$user_id) {
            return array();
        }

        // Start building the query
        $sql = "SELECT
                s.*,
                w.name as wildart_name,
                e.name as eigenjagdbezirk_name,
                m.name as meldegruppe_name
            FROM {$this->submissions_table} s
            LEFT JOIN {$this->wildart_table} w ON s.wildart_id = w.id
            LEFT JOIN {$this->eigenjagdbezirk_table} e ON s.eigenjagdbezirk_id = e.id
            LEFT JOIN {$this->meldegruppe_table} m ON s.meldegruppe_id = m.id
            WHERE 1=1";

        $prepare_args = array();

        // Filter by obmann's assigned meldegruppen
        // Get all user meta keys for meldegruppe assignments
        $usermeta_table = $this->wpdb->usermeta;
        $meta_like = 'ahgmh_assigned_meldegruppe_%';

        // Subquery to get meldegruppe IDs for user's assigned meldegruppen
        $sql .= " AND s.meldegruppe_id IN (
            SELECT mg.id
            FROM {$this->meldegruppe_table} mg
            INNER JOIN {$usermeta_table} um ON mg.name = um.meta_value
            WHERE um.user_id = %d
            AND um.meta_key LIKE %s
        )";
        $prepare_args[] = (int) $user_id;
        $prepare_args[] = $meta_like;

        // Apply optional wildart_id filter
        if ($wildart_id !== null) {
            $sql .= " AND s.wildart_id = %d";
            $prepare_args[] = (int) $wildart_id;
        }

        // Apply optional status filter
        if ($status !== null) {
            $sql .= " AND s.status = %s";
            $prepare_args[] = sanitize_text_field($status);
        }

        // Order by most recent first
        $sql .= " ORDER BY s.created_at DESC";

        // Prepare and execute query
        if (!empty($prepare_args)) {
            $sql = $this->wpdb->prepare($sql, $prepare_args);
        }

        $results = $this->wpdb->get_results($sql);

        // Log error for debugging if WP_DEBUG is enabled
        if ($results === null && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HGMH Submission Repository get_for_obmann error: ' . $this->wpdb->last_error);
            error_log('HGMH Submission Repository last query: ' . $this->wpdb->last_query);
        }

        return $results ? $results : array();
    }

    /**
     * Count submissions by status
     *
     * Returns the number of submissions with a specific status.
     * Optionally filters by obmann's assigned meldegruppen.
     *
     * @param string $status Status to count (e.g., 'pending', 'approved', 'rejected')
     * @param int|null $obmann_user_id Optional obmann user ID to filter by their meldegruppen
     * @return int Count of submissions matching the criteria
     */
    public function count_by_status($status, $obmann_user_id = null) {
        // Start building the query
        $sql = "SELECT COUNT(*) as count
            FROM {$this->submissions_table} s";

        $prepare_args = array();

        // Add obmann meldegruppen filter if user_id is provided
        if ($obmann_user_id !== null) {
            $usermeta_table = $this->wpdb->usermeta;
            $meta_like = 'ahgmh_assigned_meldegruppe_%';

            // Subquery to filter by obmann's assigned meldegruppen
            $sql .= " WHERE s.meldegruppe_id IN (
                SELECT mg.id
                FROM {$this->meldegruppe_table} mg
                INNER JOIN {$usermeta_table} um ON mg.name = um.meta_value
                WHERE um.user_id = %d
                AND um.meta_key LIKE %s
            )";
            $prepare_args[] = (int) $obmann_user_id;
            $prepare_args[] = $meta_like;

            // Add status filter with AND
            $sql .= " AND s.status = %s";
            $prepare_args[] = sanitize_text_field($status);
        } else {
            // No obmann filter, just count by status
            $sql .= " WHERE s.status = %s";
            $prepare_args[] = sanitize_text_field($status);
        }

        // Prepare and execute query
        if (!empty($prepare_args)) {
            $sql = $this->wpdb->prepare($sql, $prepare_args);
        }

        $result = $this->wpdb->get_var($sql);

        // Log error for debugging if WP_DEBUG is enabled
        if ($result === null && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HGMH Submission Repository count_by_status error: ' . $this->wpdb->last_error);
            error_log('HGMH Submission Repository last query: ' . $this->wpdb->last_query);
        }

        return $result !== null ? (int) $result : 0;
    }

    /**
     * Update submission status and related data
     *
     * Updates the status of a submission and optionally updates additional fields.
     * Automatically calculates and updates metrics based on business rules.
     *
     * @param int $id Submission ID to update
     * @param string $new_status New status value (e.g., 'pending', 'approved', 'rejected')
     * @param array $additional_data Optional additional fields to update (e.g., approved_at, approved_by_user_id, rejection_reason)
     * @return bool True on success, false on failure
     */
    public function update_status($id, $new_status, $additional_data = array()) {
        // Prepare data for update
        $update_data = array(
            'status' => sanitize_text_field($new_status)
        );
        $format = array('%s');

        // Add timestamp fields based on status change
        if ($new_status === 'approved' && !isset($additional_data['approved_at'])) {
            $update_data['approved_at'] = current_time('mysql');
            $format[] = '%s';
        } elseif ($new_status === 'rejected' && !isset($additional_data['rejected_at'])) {
            $update_data['rejected_at'] = current_time('mysql');
            $format[] = '%s';
        }

        // Process additional data fields
        if (!empty($additional_data)) {
            foreach ($additional_data as $key => $value) {
                // Sanitize and add each field
                if (is_int($value)) {
                    $update_data[$key] = (int) $value;
                    $format[] = '%d';
                } elseif (is_float($value)) {
                    $update_data[$key] = (float) $value;
                    $format[] = '%f';
                } else {
                    $update_data[$key] = sanitize_text_field($value);
                    $format[] = '%s';
                }
            }
        }

        // Update data with proper data types
        $result = $this->wpdb->update(
            $this->submissions_table,
            $update_data,
            array('id' => (int) $id),
            $format,
            array('%d')
        );

        // Log error for debugging if WP_DEBUG is enabled
        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HGMH Submission Repository update_status error: ' . $this->wpdb->last_error);
            error_log('HGMH Submission Repository last query: ' . $this->wpdb->last_query);
        }

        return $result !== false;
    }
}
