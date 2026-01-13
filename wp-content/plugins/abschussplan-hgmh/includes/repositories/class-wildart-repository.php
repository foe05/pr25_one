<?php
/**
 * Wildart Repository Class
 * Data access layer for wildart operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository for handling wildart data persistence
 */
class AHGMH_Wildart_Repository {

    /**
     * Option key for storing wildarten list
     */
    private $wildarten_option_key = 'ahgmh_species';

    /**
     * Option key prefix for wildart categories
     */
    private $categories_option_prefix = 'ahgmh_categories_';

    /**
     * Option key for wildart meldegruppen configuration
     */
    private $meldegruppen_option_key = 'ahgmh_wildart_meldegruppen';

    /**
     * Option key for wildart limit modes
     */
    private $limit_modes_option_key = 'ahgmh_limit_modes';

    /**
     * Option key for wildart limits
     */
    private $limits_option_key = 'ahgmh_wildart_limits';

    /**
     * Get all wildarten
     *
     * @return array Array of wildart names
     */
    public function get_all() {
        $wildarten = get_option($this->wildarten_option_key, ['Rotwild', 'Damwild', 'Rehwild']);

        // Sanitize output
        if (!is_array($wildarten)) {
            return ['Rotwild', 'Damwild', 'Rehwild'];
        }

        return array_map('sanitize_text_field', $wildarten);
    }

    /**
     * Get wildart by name (acts as ID in this system)
     *
     * @param string $wildart_name The wildart name
     * @return array|null Wildart data or null if not found
     */
    public function get_by_id($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $wildarten = $this->get_all();

        if (!in_array($wildart_name, $wildarten, true)) {
            return null;
        }

        return [
            'name' => $wildart_name,
            'categories' => $this->get_categories($wildart_name),
            'meldegruppen' => $this->get_meldegruppen($wildart_name),
            'limit_mode' => $this->get_limit_mode($wildart_name),
            'limits' => $this->get_limits($wildart_name)
        ];
    }

    /**
     * Check if a wildart exists
     *
     * @param string $wildart_name The wildart name
     * @return bool True if exists
     */
    public function exists($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $wildarten = $this->get_all();

        return in_array($wildart_name, $wildarten, true);
    }

    /**
     * Create a new wildart
     *
     * @param string $wildart_name The wildart name
     * @return bool True on success, false on failure
     * @throws Exception If wildart already exists or name is invalid
     */
    public function create($wildart_name) {
        $wildart_name = sanitize_text_field(trim($wildart_name));

        if (empty($wildart_name)) {
            throw new Exception('Wildart-Name darf nicht leer sein');
        }

        $wildarten = $this->get_all();

        if (in_array($wildart_name, $wildarten, true)) {
            throw new Exception('Wildart existiert bereits');
        }

        $wildarten[] = $wildart_name;
        $result = update_option($this->wildarten_option_key, $wildarten);

        if ($result) {
            // Initialize default categories for new wildart
            $this->initialize_default_categories($wildart_name);

            // Initialize default meldegruppen
            $this->initialize_default_meldegruppen($wildart_name);
        }

        return $result;
    }

    /**
     * Update a wildart (rename)
     *
     * @param string $old_name Current wildart name
     * @param string $new_name New wildart name
     * @return bool True on success, false on failure
     * @throws Exception If wildart not found or new name already exists
     */
    public function update($old_name, $new_name) {
        $old_name = sanitize_text_field(trim($old_name));
        $new_name = sanitize_text_field(trim($new_name));

        if (empty($new_name)) {
            throw new Exception('Neuer Wildart-Name darf nicht leer sein');
        }

        if ($old_name === $new_name) {
            return true; // No change needed
        }

        $wildarten = $this->get_all();
        $key = array_search($old_name, $wildarten, true);

        if ($key === false) {
            throw new Exception('Wildart nicht gefunden');
        }

        if (in_array($new_name, $wildarten, true)) {
            throw new Exception('Neue Wildart-Name existiert bereits');
        }

        // Update wildarten list
        $wildarten[$key] = $new_name;
        $result = update_option($this->wildarten_option_key, $wildarten);

        if ($result) {
            // Migrate categories
            $old_categories_key = $this->categories_option_prefix . sanitize_key($old_name);
            $new_categories_key = $this->categories_option_prefix . sanitize_key($new_name);
            $categories = get_option($old_categories_key, []);
            update_option($new_categories_key, $categories);
            delete_option($old_categories_key);

            // Migrate meldegruppen configuration
            $meldegruppen = get_option($this->meldegruppen_option_key, []);
            if (isset($meldegruppen[$old_name])) {
                $meldegruppen[$new_name] = $meldegruppen[$old_name];
                unset($meldegruppen[$old_name]);
                update_option($this->meldegruppen_option_key, $meldegruppen);
            }

            // Migrate limit modes
            $limit_modes = get_option($this->limit_modes_option_key, []);
            if (isset($limit_modes[$old_name])) {
                $limit_modes[$new_name] = $limit_modes[$old_name];
                unset($limit_modes[$old_name]);
                update_option($this->limit_modes_option_key, $limit_modes);
            }

            // Migrate limits
            $limits = get_option($this->limits_option_key, []);
            if (isset($limits[$old_name])) {
                $limits[$new_name] = $limits[$old_name];
                unset($limits[$old_name]);
                update_option($this->limits_option_key, $limits);
            }
        }

        return $result;
    }

    /**
     * Delete a wildart
     *
     * @param string $wildart_name The wildart name
     * @return bool True on success, false on failure
     * @throws Exception If wildart not found
     */
    public function delete($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $wildarten = $this->get_all();
        $key = array_search($wildart_name, $wildarten, true);

        if ($key === false) {
            throw new Exception('Wildart nicht gefunden');
        }

        // Remove from list
        unset($wildarten[$key]);
        $wildarten = array_values($wildarten); // Re-index array
        $result = update_option($this->wildarten_option_key, $wildarten);

        if ($result) {
            // Clean up related data
            $this->cleanup_wildart_data($wildart_name);
        }

        return $result;
    }

    /**
     * Get categories for a wildart
     *
     * @param string $wildart_name The wildart name
     * @return array Array of category names
     */
    public function get_categories($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $categories_key = $this->categories_option_prefix . sanitize_key($wildart_name);
        $categories = get_option($categories_key, []);

        // If no categories exist, return defaults
        if (empty($categories)) {
            return $this->get_default_categories($wildart_name);
        }

        return is_array($categories) ? array_map('sanitize_text_field', $categories) : [];
    }

    /**
     * Save categories for a wildart
     *
     * @param string $wildart_name The wildart name
     * @param array $categories Array of category names
     * @return bool True on success, false on failure
     * @throws Exception If wildart not found
     */
    public function save_categories($wildart_name, $categories) {
        $wildart_name = sanitize_text_field($wildart_name);

        if (!$this->exists($wildart_name)) {
            throw new Exception('Wildart nicht gefunden');
        }

        // Sanitize and filter categories
        $sanitized_categories = [];
        if (is_array($categories)) {
            foreach ($categories as $category) {
                $category = sanitize_text_field(trim($category));
                if (!empty($category)) {
                    $sanitized_categories[] = $category;
                }
            }
        }

        $categories_key = $this->categories_option_prefix . sanitize_key($wildart_name);
        return update_option($categories_key, $sanitized_categories);
    }

    /**
     * Get meldegruppen for a wildart
     *
     * @param string $wildart_name The wildart name
     * @return array Array of meldegruppen names
     */
    public function get_meldegruppen($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $meldegruppen = get_option($this->meldegruppen_option_key, []);

        if (isset($meldegruppen[$wildart_name]) && is_array($meldegruppen[$wildart_name])) {
            return array_map('sanitize_text_field', $meldegruppen[$wildart_name]);
        }

        // Return default meldegruppen
        return ['Gruppe_A', 'Gruppe_B'];
    }

    /**
     * Save meldegruppen for a wildart
     *
     * @param string $wildart_name The wildart name
     * @param array $meldegruppen Array of meldegruppen names
     * @return bool True on success, false on failure
     * @throws Exception If wildart not found
     */
    public function save_meldegruppen($wildart_name, $meldegruppen) {
        $wildart_name = sanitize_text_field($wildart_name);

        if (!$this->exists($wildart_name)) {
            throw new Exception('Wildart nicht gefunden');
        }

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

        $all_meldegruppen = get_option($this->meldegruppen_option_key, []);
        $all_meldegruppen[$wildart_name] = $sanitized_meldegruppen;

        return update_option($this->meldegruppen_option_key, $all_meldegruppen);
    }

    /**
     * Get limit mode for a wildart
     *
     * @param string $wildart_name The wildart name
     * @return string 'meldegruppen_specific' or 'hegegemeinschaft_total'
     */
    public function get_limit_mode($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $limit_modes = get_option($this->limit_modes_option_key, []);

        if (isset($limit_modes[$wildart_name])) {
            $mode = $limit_modes[$wildart_name];

            // Migration: Convert old 'jagdbezirk_specific' to 'meldegruppen_specific'
            if ($mode === 'jagdbezirk_specific') {
                $limit_modes[$wildart_name] = 'meldegruppen_specific';
                update_option($this->limit_modes_option_key, $limit_modes);
                return 'meldegruppen_specific';
            }

            return $mode;
        }

        // Default to intelligent detection
        $limits = $this->get_limits($wildart_name);
        return !empty($limits) ? 'meldegruppen_specific' : 'hegegemeinschaft_total';
    }

    /**
     * Set limit mode for a wildart
     *
     * @param string $wildart_name The wildart name
     * @param string $mode 'meldegruppen_specific' or 'hegegemeinschaft_total'
     * @return bool True on success, false on failure
     * @throws Exception If mode is invalid
     */
    public function set_limit_mode($wildart_name, $mode) {
        $wildart_name = sanitize_text_field($wildart_name);
        $mode = sanitize_text_field($mode);

        if (!in_array($mode, ['meldegruppen_specific', 'hegegemeinschaft_total'], true)) {
            throw new Exception('Ungültiger Limit-Modus');
        }

        $limit_modes = get_option($this->limit_modes_option_key, []);
        $limit_modes[$wildart_name] = $mode;

        return update_option($this->limit_modes_option_key, $limit_modes);
    }

    /**
     * Get limits for a wildart
     *
     * @param string $wildart_name The wildart name
     * @return array Array of limits configuration
     */
    public function get_limits($wildart_name) {
        $wildart_name = sanitize_text_field($wildart_name);
        $limits = get_option($this->limits_option_key, []);

        return isset($limits[$wildart_name]) && is_array($limits[$wildart_name])
            ? $limits[$wildart_name]
            : [];
    }

    /**
     * Save limits for a wildart
     *
     * @param string $wildart_name The wildart name
     * @param array $limits Limits configuration
     * @return bool True on success, false on failure
     * @throws Exception If wildart not found
     */
    public function save_limits($wildart_name, $limits) {
        $wildart_name = sanitize_text_field($wildart_name);

        if (!$this->exists($wildart_name)) {
            throw new Exception('Wildart nicht gefunden');
        }

        $all_limits = get_option($this->limits_option_key, []);
        $all_limits[$wildart_name] = $limits;

        return update_option($this->limits_option_key, $all_limits);
    }

    /**
     * Initialize default categories for a wildart
     *
     * @param string $wildart_name The wildart name
     * @return void
     */
    private function initialize_default_categories($wildart_name) {
        $default_categories = $this->get_default_categories($wildart_name);
        if (!empty($default_categories)) {
            $this->save_categories($wildart_name, $default_categories);
        }
    }

    /**
     * Initialize default meldegruppen for a wildart
     *
     * @param string $wildart_name The wildart name
     * @return void
     */
    private function initialize_default_meldegruppen($wildart_name) {
        $meldegruppen = get_option($this->meldegruppen_option_key, []);

        // Only initialize if not already set
        if (!isset($meldegruppen[$wildart_name]) || empty($meldegruppen[$wildart_name])) {
            $this->save_meldegruppen($wildart_name, ['Gruppe_A', 'Gruppe_B']);
        }
    }

    /**
     * Get default categories for a wildart species
     *
     * @param string $wildart_name The wildart name
     * @return array Array of default category names
     */
    private function get_default_categories($wildart_name) {
        $defaults = [
            'Rotwild' => [
                'Wildkalb (AK0)', 'Schmaltier (AK 1)', 'Alttier (AK 2)',
                'Hirschkalb (AK 0)', 'Schmalspießer (AK1)', 'Junger Hirsch (AK 2)',
                'Mittelalter Hirsch (AK 3)', 'Alter Hirsch (AK 4)'
            ],
            'Damwild' => [
                'Wildkalb (AK0)', 'Schmaltier (AK 1)', 'Alttier (AK 2)',
                'Hirschkalb (AK 0)', 'Schmalspießer (AK1)', 'Junger Hirsch (AK 2)',
                'Mittelalter Hirsch (AK 3)', 'Alter Hirsch (AK 4)'
            ],
            'Rehwild' => [
                'Rehkitz', 'Rehgeiß', 'Rehbock'
            ]
        ];

        return isset($defaults[$wildart_name]) ? $defaults[$wildart_name] : [];
    }

    /**
     * Save wildart order
     *
     * @param array $ordered_wildarten Array of wildart names in desired order
     * @return bool True on success
     */
    public function save_order($ordered_wildarten) {
        $ordered_wildarten = array_map('sanitize_text_field', $ordered_wildarten);

        // Verify all wildarten exist
        $existing = $this->get_all();
        foreach ($ordered_wildarten as $wildart) {
            if (!in_array($wildart, $existing, true)) {
                return false; // Invalid wildart in order
            }
        }

        return update_option($this->wildarten_option_key, $ordered_wildarten);
    }

    /**
     * Clean up wildart-related data when deleting
     *
     * @param string $wildart_name The wildart name
     * @return void
     */
    private function cleanup_wildart_data($wildart_name) {
        // Remove categories
        $categories_key = $this->categories_option_prefix . sanitize_key($wildart_name);
        delete_option($categories_key);

        // Remove from meldegruppen
        $meldegruppen = get_option($this->meldegruppen_option_key, []);
        if (isset($meldegruppen[$wildart_name])) {
            unset($meldegruppen[$wildart_name]);
            update_option($this->meldegruppen_option_key, $meldegruppen);
        }

        // Remove from limits
        $limits = get_option($this->limits_option_key, []);
        if (isset($limits[$wildart_name])) {
            unset($limits[$wildart_name]);
            update_option($this->limits_option_key, $limits);
        }

        // Remove from limit modes
        $limit_modes = get_option($this->limit_modes_option_key, []);
        if (isset($limit_modes[$wildart_name])) {
            unset($limit_modes[$wildart_name]);
            update_option($this->limit_modes_option_key, $limit_modes);
        }
    }
}
