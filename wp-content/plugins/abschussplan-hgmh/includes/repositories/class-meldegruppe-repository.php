<?php
/**
 * Meldegruppe Repository Class
 * Data access layer for meldegruppe operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository for handling meldegruppe data persistence
 */
class AHGMH_Meldegruppe_Repository {

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Meldegruppen config table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Option key for meldegruppen configuration
     */
    private $meldegruppen_option_key = 'ahgmh_wildart_meldegruppen';

    /**
     * Option key for Obmann assignments (legacy - kept for backwards compatibility)
     * Note: Primary storage is now in user meta via AHGMH_Permissions_Service
     */
    private $obmann_option_key = 'ahgmh_meldegruppe_obmann';

    /**
     * User meta key prefix for meldegruppe assignments
     */
    private $user_meta_prefix = 'ahgmh_assigned_meldegruppe_';

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'ahgmh_meldegruppen_config';
    }

    /**
     * Get meldegruppen for a specific wildart
     *
     * @param string $wildart_name The wildart name
     * @return array Array of meldegruppen names
     */
    public function get_by_wildart($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppen = get_option($this->meldegruppen_option_key, []);

        if (isset($meldegruppen[$wildart_name]) && is_array($meldegruppen[$wildart_name])) {
            return array_map('sanitize_text_field', $meldegruppen[$wildart_name]);
        }

        // Return default meldegruppen if none configured
        return ['Gruppe_A', 'Gruppe_B'];
    }

    /**
     * Save meldegruppen for a specific wildart
     *
     * @param string $wildart_name The wildart name
     * @param array $meldegruppen Array of meldegruppen names
     * @return bool True on success, false on failure
     */
    public function save_for_wildart($wildart_name, $meldegruppen) {
        $wildart_name = sanitize_text_field($wildart_name);

        // Sanitize and filter meldegruppen
        $sanitized_meldegruppen = [];
        if (is_array($meldegruppen)) {
            foreach ($meldegruppen as $meldegruppe) {
                $meldegruppe = sanitize_text_field(trim($meldegruppe));
                if (!empty($meldegruppe)) {
                    $sanitized_meldegruppen[] = $meldegruppe;
                }
            }
        }

        // Get current configuration
        $all_meldegruppen = get_option($this->meldegruppen_option_key, []);
        $all_meldegruppen[$wildart_name] = array_values($sanitized_meldegruppen);

        // Save to WordPress options
        return update_option($this->meldegruppen_option_key, $all_meldegruppen);
    }

    /**
     * Get all meldegruppen merged from all wildarten
     *
     * @return array Array of unique meldegruppen names across all wildarten
     */
    public function get_all_merged() {
        $meldegruppen = get_option($this->meldegruppen_option_key, []);
        $all_meldegruppen = [];

        if (!is_array($meldegruppen)) {
            return [];
        }

        // Merge all meldegruppen from all wildarten
        foreach ($meldegruppen as $wildart => $wildart_meldegruppen) {
            if (is_array($wildart_meldegruppen)) {
                $all_meldegruppen = array_merge($all_meldegruppen, $wildart_meldegruppen);
            }
        }

        // Remove duplicates and sanitize
        $all_meldegruppen = array_unique($all_meldegruppen);
        return array_map('sanitize_text_field', array_values($all_meldegruppen));
    }

    /**
     * Get all meldegruppen with their wildart associations
     *
     * @return array Array of meldegruppen with wildart keys
     */
    public function get_all_with_wildarten() {
        $meldegruppen = get_option($this->meldegruppen_option_key, []);

        if (!is_array($meldegruppen)) {
            return [];
        }

        // Sanitize output
        $sanitized = [];
        foreach ($meldegruppen as $wildart => $wildart_meldegruppen) {
            $wildart = sanitize_text_field($wildart);
            if (is_array($wildart_meldegruppen)) {
                $sanitized[$wildart] = array_map('sanitize_text_field', $wildart_meldegruppen);
            }
        }

        return $sanitized;
    }

    /**
     * Check if a meldegruppe exists for a specific wildart
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @return bool True if exists, false otherwise
     */
    public function exists($wildart_name, $meldegruppe_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);

        $meldegruppen = $this->get_by_wildart($wildart_name);
        return in_array($meldegruppe_name, $meldegruppen, true);
    }

    /**
     * Delete a meldegruppe from a specific wildart
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name to delete
     * @return bool True on success, false on failure
     */
    public function delete($wildart_name, $meldegruppe_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);

        $meldegruppen = $this->get_by_wildart($wildart_name);
        $key = array_search($meldegruppe_name, $meldegruppen, true);

        if ($key === false) {
            return false; // Meldegruppe not found
        }

        // Remove the meldegruppe
        unset($meldegruppen[$key]);
        $meldegruppen = array_values($meldegruppen); // Re-index array

        // Save updated list
        return $this->save_for_wildart($wildart_name, $meldegruppen);
    }

    /**
     * Assign an Obmann (supervisor) to a meldegruppe
     *
     * Uses user meta as primary storage (via AHGMH_Permissions_Service pattern)
     * This ensures compatibility with the permission checking system.
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @param int $user_id The WordPress user ID of the Obmann
     * @return bool True on success, false on failure
     */
    public function assign_obmann($wildart_name, $meldegruppe_name, $user_id) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);
        $user_id = absint($user_id);

        if ($user_id <= 0) {
            return false; // Invalid user ID
        }

        // Primary storage: User Meta (compatible with AHGMH_Permissions_Service)
        $meta_key = $this->user_meta_prefix . sanitize_key($wildart_name);

        // First, remove any existing assignment for this user/wildart combination
        delete_user_meta($user_id, $meta_key);

        // Add the new assignment
        $result = add_user_meta($user_id, $meta_key, $meldegruppe_name, true);

        // Also update the database table if it exists (hgmh_meldegruppen.obmann_user_id)
        $this->sync_obmann_to_database($wildart_name, $meldegruppe_name, $user_id);

        return $result !== false;
    }

    /**
     * Sync Obmann assignment to the database table
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @param int $user_id The WordPress user ID
     */
    private function sync_obmann_to_database($wildart_name, $meldegruppe_name, $user_id) {
        global $wpdb;

        // Get meldegruppe ID from database
        $meldegruppe_id = $wpdb->get_var($wpdb->prepare(
            "SELECT m.id FROM {$wpdb->prefix}hgmh_meldegruppen m
             INNER JOIN {$wpdb->prefix}hgmh_wildarten w ON m.wildart_id = w.id
             WHERE w.name = %s AND m.name = %s",
            $wildart_name,
            $meldegruppe_name
        ));

        if ($meldegruppe_id) {
            // Update obmann_user_id in meldegruppen table
            $wpdb->update(
                $wpdb->prefix . 'hgmh_meldegruppen',
                ['obmann_user_id' => $user_id],
                ['id' => $meldegruppe_id],
                ['%d'],
                ['%d']
            );
        }
    }

    /**
     * Remove Obmann assignment from a meldegruppe
     *
     * Removes from user meta (primary storage) and syncs to database table.
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @return bool True on success, false on failure
     */
    public function remove_obmann($wildart_name, $meldegruppe_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);

        // Find the user who has this assignment
        $meta_key = $this->user_meta_prefix . sanitize_key($wildart_name);

        $users = get_users([
            'meta_key' => $meta_key,
            'meta_value' => $meldegruppe_name,
            'fields' => 'ID'
        ]);

        $removed = false;
        foreach ($users as $user_id) {
            delete_user_meta($user_id, $meta_key);
            $removed = true;
        }

        // Also clear from database table
        $this->clear_obmann_from_database($wildart_name, $meldegruppe_name);

        return true;
    }

    /**
     * Clear Obmann assignment from the database table
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     */
    private function clear_obmann_from_database($wildart_name, $meldegruppe_name) {
        global $wpdb;

        // Get meldegruppe ID from database
        $meldegruppe_id = $wpdb->get_var($wpdb->prepare(
            "SELECT m.id FROM {$wpdb->prefix}hgmh_meldegruppen m
             INNER JOIN {$wpdb->prefix}hgmh_wildarten w ON m.wildart_id = w.id
             WHERE w.name = %s AND m.name = %s",
            $wildart_name,
            $meldegruppe_name
        ));

        if ($meldegruppe_id) {
            // Clear obmann_user_id in meldegruppen table
            $wpdb->update(
                $wpdb->prefix . 'hgmh_meldegruppen',
                ['obmann_user_id' => null],
                ['id' => $meldegruppe_id],
                ['%s'],  // NULL as string
                ['%d']
            );
        }
    }

    /**
     * Get Obmann assignment for a specific meldegruppe
     *
     * Reads from user meta (primary storage).
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @return array|null Assignment data or null if not assigned
     */
    public function get_obmann($wildart_name, $meldegruppe_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);

        // Query user meta for this assignment
        $meta_key = $this->user_meta_prefix . sanitize_key($wildart_name);

        $users = get_users([
            'meta_key' => $meta_key,
            'meta_value' => $meldegruppe_name,
            'fields' => ['ID', 'display_name', 'user_email']
        ]);

        if (empty($users)) {
            return null;
        }

        $user = $users[0];
        return [
            'wildart' => $wildart_name,
            'meldegruppe' => $meldegruppe_name,
            'user_id' => $user->ID,
            'user_name' => $user->display_name,
            'user_email' => $user->user_email
        ];
    }

    /**
     * Get all Obmann assignments
     *
     * Reads from user meta (primary storage).
     *
     * @return array Array of all assignments
     */
    public function get_obmann_assignments() {
        global $wpdb;

        // Query all user meta entries with our prefix
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as user_id, u.display_name as user_name, u.user_email,
                    SUBSTRING(um.meta_key, %d) as wildart, um.meta_value as meldegruppe
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             WHERE um.meta_key LIKE %s
             AND um.meta_value != ''
             ORDER BY u.display_name, wildart",
            strlen($this->user_meta_prefix) + 1,
            $this->user_meta_prefix . '%'
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Get all Obmann assignments for a specific wildart
     *
     * Reads from user meta (primary storage).
     *
     * @param string $wildart_name The wildart name
     * @return array Array of assignments for the wildart
     */
    public function get_obmann_assignments_by_wildart($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meta_key = $this->user_meta_prefix . sanitize_key($wildart_name);

        $users = get_users([
            'meta_key' => $meta_key,
            'meta_compare' => 'EXISTS'
        ]);

        $assignments = [];
        foreach ($users as $user) {
            $meldegruppe = get_user_meta($user->ID, $meta_key, true);
            if (!empty($meldegruppe)) {
                $assignments[] = [
                    'wildart' => $wildart_name,
                    'meldegruppe' => $meldegruppe,
                    'user_id' => $user->ID,
                    'user_name' => $user->display_name,
                    'user_email' => $user->user_email
                ];
            }
        }

        return $assignments;
    }

    /**
     * Get all Obmann assignments for a specific user
     *
     * Reads from user meta (primary storage).
     *
     * @param int $user_id The WordPress user ID
     * @return array Array of assignments for the user
     */
    public function get_obmann_assignments_by_user($user_id) {
        global $wpdb;
        $user_id = absint($user_id);

        if ($user_id <= 0) {
            return [];
        }

        // Get all meta entries with our prefix for this user
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT SUBSTRING(meta_key, %d) as wildart, meta_value as meldegruppe
             FROM {$wpdb->usermeta}
             WHERE user_id = %d
             AND meta_key LIKE %s
             AND meta_value != ''",
            strlen($this->user_meta_prefix) + 1,
            $user_id,
            $this->user_meta_prefix . '%'
        ), ARRAY_A);

        $user = get_userdata($user_id);
        $assignments = [];

        foreach ($results as $row) {
            $assignments[] = [
                'wildart' => $row['wildart'],
                'meldegruppe' => $row['meldegruppe'],
                'user_id' => $user_id,
                'user_name' => $user ? $user->display_name : '',
                'user_email' => $user ? $user->user_email : ''
            ];
        }

        return $assignments;
    }

    /**
     * Create a unique key for Obmann assignments
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @return string Unique key
     */
    private function get_obmann_key($wildart_name, $meldegruppe_name) {
        return sanitize_key($wildart_name . '_' . $meldegruppe_name);
    }

    /**
     * Clean up meldegruppe data for a specific wildart
     *
     * @param string $wildart_name The wildart name
     * @return bool True on success
     */
    public function cleanup_wildart_meldegruppen($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);

        // Remove from meldegruppen configuration
        $all_meldegruppen = get_option($this->meldegruppen_option_key, []);
        if (isset($all_meldegruppen[$wildart_name])) {
            unset($all_meldegruppen[$wildart_name]);
            update_option($this->meldegruppen_option_key, $all_meldegruppen);
        }

        // Remove Obmann assignments for this wildart
        $assignments = get_option($this->obmann_option_key, []);
        if (is_array($assignments)) {
            foreach ($assignments as $key => $assignment) {
                if (isset($assignment['wildart']) && $assignment['wildart'] === $wildart_name) {
                    unset($assignments[$key]);
                }
            }
            update_option($this->obmann_option_key, $assignments);
        }

        return true;
    }

    /**
     * Rename meldegruppe across all wildarten
     *
     * @param string $old_name Current meldegruppe name
     * @param string $new_name New meldegruppe name
     * @return bool True on success, false on failure
     */
    public function rename($old_name, $new_name) {
        $old_name = sanitize_text_field(trim($old_name));
        $new_name = sanitize_text_field(trim($new_name));

        if (empty($new_name) || $old_name === $new_name) {
            return false;
        }

        $updated = false;
        $all_meldegruppen = get_option($this->meldegruppen_option_key, []);

        // Update meldegruppe name in all wildarten
        foreach ($all_meldegruppen as $wildart => $meldegruppen) {
            if (is_array($meldegruppen)) {
                $key = array_search($old_name, $meldegruppen, true);
                if ($key !== false) {
                    $all_meldegruppen[$wildart][$key] = $new_name;
                    $updated = true;
                }
            }
        }

        if ($updated) {
            update_option($this->meldegruppen_option_key, $all_meldegruppen);

            // Update Obmann assignments
            $this->rename_obmann_assignments($old_name, $new_name);
        }

        return $updated;
    }

    /**
     * Rename meldegruppe in Obmann assignments
     *
     * @param string $old_name Current meldegruppe name
     * @param string $new_name New meldegruppe name
     * @return void
     */
    private function rename_obmann_assignments($old_name, $new_name) {
        $assignments = get_option($this->obmann_option_key, []);
        if (!is_array($assignments)) {
            return;
        }

        $updated_assignments = [];
        foreach ($assignments as $key => $assignment) {
            if (isset($assignment['meldegruppe']) && $assignment['meldegruppe'] === $old_name) {
                // Create new key with new name
                $wildart = isset($assignment['wildart']) ? $assignment['wildart'] : '';
                $new_key = $this->get_obmann_key($wildart, $new_name);
                $assignment['meldegruppe'] = $new_name;
                $updated_assignments[$new_key] = $assignment;
            } else {
                $updated_assignments[$key] = $assignment;
            }
        }

        update_option($this->obmann_option_key, $updated_assignments);
    }

    /**
     * Check if a meldegruppe has any data associated with it
     * (Used for validation before deletion)
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @return bool True if has associated data, false otherwise
     */
    public function has_associated_data($wildart_name, $meldegruppe_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);

        // Check if there's an Obmann assigned
        $obmann = $this->get_obmann($wildart_name, $meldegruppe_name);
        if ($obmann !== null) {
            return true;
        }

        // Check if there are limits configured in the database
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
             WHERE wildart = %s AND meldegruppe = %s",
            $wildart_name,
            $meldegruppe_name
        );

        $count = $this->wpdb->get_var($query);
        return $count > 0;
    }

    /**
     * Reset all Obmann assignments
     *
     * Clears all user meta entries and database table entries.
     *
     * @return bool True on success
     */
    public function reset_all_assignments() {
        global $wpdb;

        // Delete all user meta entries with our prefix
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            $this->user_meta_prefix . '%'
        ));

        // Clear all obmann_user_id entries in database table
        $wpdb->query(
            "UPDATE {$wpdb->prefix}hgmh_meldegruppen SET obmann_user_id = NULL"
        );

        // Also delete legacy option if it exists
        delete_option($this->obmann_option_key);

        return true;
    }
}
