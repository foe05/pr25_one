<?php
/**
 * Jagdbezirk Repository Class
 * Data access layer for Eigenjagdbezirke (hunting districts) operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository for handling Jagdbezirk (Eigenjagdbezirk) data persistence
 */
class AHGMH_Jagdbezirk_Repository {

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Table name for Eigenjagdbezirke
     *
     * @var string
     */
    private $table_name;

    /**
     * Table name for Meldegruppen
     *
     * @var string
     */
    private $meldegruppen_table;

    /**
     * Junction table for many-to-many relationship between Jagdbezirke and Meldegruppen
     *
     * @var string
     */
    private $junction_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'hgmh_eigenjagdbezirke';
        $this->meldegruppen_table = $wpdb->prefix . 'hgmh_meldegruppen';
        $this->junction_table = $wpdb->prefix . 'hgmh_jagdbezirk_meldegruppen';
    }

    /**
     * Ensure junction table exists (for many-to-many relationship)
     * This creates a proper junction table if the schema wasn't set up with it
     */
    public function ensure_junction_table() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->junction_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            jagdbezirk_id bigint(20) NOT NULL,
            meldegruppe_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY jagdbezirk_meldegruppe (jagdbezirk_id, meldegruppe_id),
            KEY jagdbezirk_id (jagdbezirk_id),
            KEY meldegruppe_id (meldegruppe_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get all Jagdbezirke with their assigned Meldegruppen
     *
     * @param bool $only_active Only return active Jagdbezirke
     * @return array Array of Jagdbezirke with Meldegruppen
     */
    public function get_all($only_active = true) {
        $where_clause = $only_active ? "WHERE e.is_active = 1" : "";

        $query = "SELECT e.*,
                         GROUP_CONCAT(DISTINCT m.id ORDER BY m.name) as meldegruppe_ids,
                         GROUP_CONCAT(DISTINCT m.name ORDER BY m.name SEPARATOR ', ') as meldegruppen_names
                  FROM {$this->table_name} e
                  LEFT JOIN {$this->junction_table} jm ON e.id = jm.jagdbezirk_id
                  LEFT JOIN {$this->meldegruppen_table} m ON jm.meldegruppe_id = m.id
                  {$where_clause}
                  GROUP BY e.id
                  ORDER BY e.name ASC";

        $results = $this->wpdb->get_results($query, ARRAY_A);

        // Parse the comma-separated IDs into arrays
        foreach ($results as &$row) {
            $row['meldegruppe_ids'] = !empty($row['meldegruppe_ids'])
                ? array_map('intval', explode(',', $row['meldegruppe_ids']))
                : [];
        }

        return $results ?: [];
    }

    /**
     * Get a single Jagdbezirk by ID
     *
     * @param int $id Jagdbezirk ID
     * @return array|null Jagdbezirk data or null if not found
     */
    public function get_by_id($id) {
        $id = absint($id);

        $query = $this->wpdb->prepare(
            "SELECT e.*,
                    GROUP_CONCAT(DISTINCT m.id ORDER BY m.name) as meldegruppe_ids,
                    GROUP_CONCAT(DISTINCT m.name ORDER BY m.name SEPARATOR ', ') as meldegruppen_names
             FROM {$this->table_name} e
             LEFT JOIN {$this->junction_table} jm ON e.id = jm.jagdbezirk_id
             LEFT JOIN {$this->meldegruppen_table} m ON jm.meldegruppe_id = m.id
             WHERE e.id = %d
             GROUP BY e.id",
            $id
        );

        $result = $this->wpdb->get_row($query, ARRAY_A);

        if ($result) {
            $result['meldegruppe_ids'] = !empty($result['meldegruppe_ids'])
                ? array_map('intval', explode(',', $result['meldegruppe_ids']))
                : [];
        }

        return $result;
    }

    /**
     * Get Jagdbezirk by name
     *
     * @param string $name Jagdbezirk name
     * @return array|null Jagdbezirk data or null if not found
     */
    public function get_by_name($name) {
        $name = sanitize_text_field($name);

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE name = %s",
            $name
        );

        return $this->wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Create a new Jagdbezirk
     *
     * @param array $data Jagdbezirk data (name, description, is_active)
     * @return int|false Inserted ID or false on failure
     */
    public function create($data) {
        $name = sanitize_text_field($data['name'] ?? '');
        $description = sanitize_textarea_field($data['description'] ?? '');
        $is_active = isset($data['is_active']) ? absint($data['is_active']) : 1;

        if (empty($name)) {
            return false;
        }

        // Check if name already exists
        if ($this->get_by_name($name)) {
            return false;
        }

        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'name' => $name,
                'description' => $description,
                'is_active' => $is_active,
                'meldegruppe_id' => 0, // Default value for legacy column
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%d', '%s']
        );

        if ($result === false) {
            return false;
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Update an existing Jagdbezirk
     *
     * @param int $id Jagdbezirk ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $id = absint($id);

        if ($id <= 0) {
            return false;
        }

        $update_data = [];
        $formats = [];

        if (isset($data['name'])) {
            $name = sanitize_text_field($data['name']);
            if (empty($name)) {
                return false;
            }

            // Check if name already exists for different ID
            $existing = $this->get_by_name($name);
            if ($existing && intval($existing['id']) !== $id) {
                return false;
            }

            $update_data['name'] = $name;
            $formats[] = '%s';
        }

        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $formats[] = '%s';
        }

        if (isset($data['is_active'])) {
            $update_data['is_active'] = absint($data['is_active']);
            $formats[] = '%d';
        }

        if (empty($update_data)) {
            return true; // Nothing to update
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $id],
            $formats,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete a Jagdbezirk (soft delete by setting is_active = 0)
     *
     * @param int $id Jagdbezirk ID
     * @param bool $hard_delete If true, permanently delete the record
     * @return bool True on success, false on failure
     */
    public function delete($id, $hard_delete = false) {
        $id = absint($id);

        if ($id <= 0) {
            return false;
        }

        if ($hard_delete) {
            // Remove all Meldegruppen assignments first
            $this->remove_all_meldegruppen($id);

            // Then delete the Jagdbezirk
            $result = $this->wpdb->delete(
                $this->table_name,
                ['id' => $id],
                ['%d']
            );
            return $result !== false;
        }

        // Soft delete
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Restore a soft-deleted Jagdbezirk
     *
     * @param int $id Jagdbezirk ID
     * @return bool True on success, false on failure
     */
    public function restore($id) {
        return $this->update($id, ['is_active' => 1]);
    }

    /**
     * Check if a Jagdbezirk has associated submissions
     *
     * @param int $id Jagdbezirk ID
     * @return bool True if has submissions, false otherwise
     */
    public function has_submissions($id) {
        $id = absint($id);

        $submissions_table = $this->wpdb->prefix . 'hgmh_submissions_v2';

        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$submissions_table} WHERE eigenjagdbezirk_id = %d",
            $id
        ));

        return intval($count) > 0;
    }

    /**
     * Assign Meldegruppen to a Jagdbezirk
     *
     * @param int $jagdbezirk_id Jagdbezirk ID
     * @param array $meldegruppe_ids Array of Meldegruppe IDs
     * @return bool True on success, false on failure
     */
    public function assign_meldegruppen($jagdbezirk_id, $meldegruppe_ids) {
        $jagdbezirk_id = absint($jagdbezirk_id);

        if ($jagdbezirk_id <= 0) {
            return false;
        }

        // Ensure junction table exists
        $this->ensure_junction_table();

        // Remove existing assignments
        $this->remove_all_meldegruppen($jagdbezirk_id);

        // Add new assignments
        if (!empty($meldegruppe_ids) && is_array($meldegruppe_ids)) {
            foreach ($meldegruppe_ids as $meldegruppe_id) {
                $meldegruppe_id = absint($meldegruppe_id);
                if ($meldegruppe_id > 0) {
                    $this->wpdb->insert(
                        $this->junction_table,
                        [
                            'jagdbezirk_id' => $jagdbezirk_id,
                            'meldegruppe_id' => $meldegruppe_id,
                            'created_at' => current_time('mysql')
                        ],
                        ['%d', '%d', '%s']
                    );
                }
            }
        }

        return true;
    }

    /**
     * Remove all Meldegruppen assignments from a Jagdbezirk
     *
     * @param int $jagdbezirk_id Jagdbezirk ID
     * @return bool True on success
     */
    public function remove_all_meldegruppen($jagdbezirk_id) {
        $jagdbezirk_id = absint($jagdbezirk_id);

        $this->wpdb->delete(
            $this->junction_table,
            ['jagdbezirk_id' => $jagdbezirk_id],
            ['%d']
        );

        return true;
    }

    /**
     * Get all Meldegruppen assigned to a Jagdbezirk
     *
     * @param int $jagdbezirk_id Jagdbezirk ID
     * @return array Array of Meldegruppen
     */
    public function get_assigned_meldegruppen($jagdbezirk_id) {
        $jagdbezirk_id = absint($jagdbezirk_id);

        $query = $this->wpdb->prepare(
            "SELECT m.* FROM {$this->meldegruppen_table} m
             INNER JOIN {$this->junction_table} jm ON m.id = jm.meldegruppe_id
             WHERE jm.jagdbezirk_id = %d
             ORDER BY m.name ASC",
            $jagdbezirk_id
        );

        return $this->wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get all available Meldegruppen (for assignment dropdown)
     *
     * @param bool $only_active Only return active Meldegruppen
     * @return array Array of Meldegruppen
     */
    public function get_all_meldegruppen($only_active = true) {
        $where_clause = $only_active ? "WHERE is_active = 1" : "";

        $query = "SELECT * FROM {$this->meldegruppen_table} {$where_clause} ORDER BY name ASC";

        return $this->wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Get Jagdbezirke by Meldegruppe ID
     *
     * @param int $meldegruppe_id Meldegruppe ID
     * @return array Array of Jagdbezirke
     */
    public function get_by_meldegruppe($meldegruppe_id) {
        $meldegruppe_id = absint($meldegruppe_id);

        $query = $this->wpdb->prepare(
            "SELECT e.* FROM {$this->table_name} e
             INNER JOIN {$this->junction_table} jm ON e.id = jm.jagdbezirk_id
             WHERE jm.meldegruppe_id = %d AND e.is_active = 1
             ORDER BY e.name ASC",
            $meldegruppe_id
        );

        return $this->wpdb->get_results($query, ARRAY_A) ?: [];
    }

    /**
     * Count total Jagdbezirke
     *
     * @param bool $only_active Only count active Jagdbezirke
     * @return int Count
     */
    public function count($only_active = true) {
        $where_clause = $only_active ? "WHERE is_active = 1" : "";

        $count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}"
        );

        return intval($count);
    }

    /**
     * Search Jagdbezirke by name
     *
     * @param string $search_term Search term
     * @return array Array of matching Jagdbezirke
     */
    public function search($search_term) {
        $search_term = '%' . $this->wpdb->esc_like(sanitize_text_field($search_term)) . '%';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE name LIKE %s AND is_active = 1
             ORDER BY name ASC",
            $search_term
        );

        return $this->wpdb->get_results($query, ARRAY_A) ?: [];
    }
}
