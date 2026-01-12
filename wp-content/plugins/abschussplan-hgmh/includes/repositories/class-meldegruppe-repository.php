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
     * Option key for Obmann assignments
     */
    private $obmann_option_key = 'ahgmh_meldegruppe_obmann';

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

        // Get current assignments
        $assignments = get_option($this->obmann_option_key, []);
        if (!is_array($assignments)) {
            $assignments = [];
        }

        // Create unique key for wildart + meldegruppe combination
        $key = $this->get_obmann_key($wildart_name, $meldegruppe_name);
        $assignments[$key] = [
            'wildart' => $wildart_name,
            'meldegruppe' => $meldegruppe_name,
            'user_id' => $user_id,
            'assigned_at' => current_time('mysql')
        ];

        return update_option($this->obmann_option_key, $assignments);
    }

    /**
     * Remove Obmann assignment from a meldegruppe
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @return bool True on success, false on failure
     */
    public function remove_obmann($wildart_name, $meldegruppe_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);

        $assignments = get_option($this->obmann_option_key, []);
        if (!is_array($assignments)) {
            return true; // Nothing to remove
        }

        $key = $this->get_obmann_key($wildart_name, $meldegruppe_name);
        if (isset($assignments[$key])) {
            unset($assignments[$key]);
            return update_option($this->obmann_option_key, $assignments);
        }

        return true; // Already not assigned
    }

    /**
     * Get Obmann assignment for a specific meldegruppe
     *
     * @param string $wildart_name The wildart name
     * @param string $meldegruppe_name The meldegruppe name
     * @return array|null Assignment data or null if not assigned
     */
    public function get_obmann($wildart_name, $meldegruppe_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppe_name = sanitize_text_field($meldegruppe_name);

        $assignments = get_option($this->obmann_option_key, []);
        if (!is_array($assignments)) {
            return null;
        }

        $key = $this->get_obmann_key($wildart_name, $meldegruppe_name);
        return isset($assignments[$key]) ? $assignments[$key] : null;
    }

    /**
     * Get all Obmann assignments
     *
     * @return array Array of all assignments
     */
    public function get_obmann_assignments() {
        $assignments = get_option($this->obmann_option_key, []);
        return is_array($assignments) ? $assignments : [];
    }

    /**
     * Get all Obmann assignments for a specific wildart
     *
     * @param string $wildart_name The wildart name
     * @return array Array of assignments for the wildart
     */
    public function get_obmann_assignments_by_wildart($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $all_assignments = $this->get_obmann_assignments();
        $wildart_assignments = [];

        foreach ($all_assignments as $key => $assignment) {
            if (isset($assignment['wildart']) && $assignment['wildart'] === $wildart_name) {
                $wildart_assignments[$key] = $assignment;
            }
        }

        return $wildart_assignments;
    }

    /**
     * Get all Obmann assignments for a specific user
     *
     * @param int $user_id The WordPress user ID
     * @return array Array of assignments for the user
     */
    public function get_obmann_assignments_by_user($user_id) {
        $user_id = absint($user_id);
        if ($user_id <= 0) {
            return [];
        }

        $all_assignments = $this->get_obmann_assignments();
        $user_assignments = [];

        foreach ($all_assignments as $key => $assignment) {
            if (isset($assignment['user_id']) && $assignment['user_id'] === $user_id) {
                $user_assignments[$key] = $assignment;
            }
        }

        return $user_assignments;
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
}
