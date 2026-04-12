<?php
/**
 * Wildart Service Class
 * Business logic for wildart operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wildart Service for business logic
 */
class AHGMH_Wildart_Service {

    /**
     * @var AHGMH_Wildart_Repository
     */
    private $repository;

    /**
     * Constructor
     */
    public function __construct() {
        $this->repository = new AHGMH_Wildart_Repository();
    }

    /**
     * Get all wildarten
     */
    public function get_all_wildarten() {
        return $this->repository->get_all();
    }
    
    /**
     * Create new wildart
     */
    public function create_wildart($name) {
        $name = AHGMH_Validation_Service::validate_species_name($name);
        if (!$name) {
            throw new Exception('Ungültiger Wildart-Name');
        }

        // Repository handles existence check and initialization
        $this->repository->create($name);

        $species = $this->repository->get_all();
        return ['species' => $species, 'created' => $name];
    }
    
    /**
     * Delete wildart
     */
    public function delete_wildart($wildart) {
        // Check if wildart has existing submissions
        $db_handler = new AHGMH_Database_Handler();
        if ($db_handler->check_wildart_has_submissions($wildart)) {
            throw new Exception('Diese Wildart kann nicht gelöscht werden, da bereits Meldungen existieren.');
        }

        // Repository handles existence check and cleanup
        $this->repository->delete($wildart);

        $species = $this->repository->get_all();
        return ['species' => $species, 'deleted' => $wildart];
    }
    
    /**
     * Get wildart configuration
     *
     * Returns everything the Einrichtung view needs to render the detail panel:
     *  - categories, meldegruppen (option-based)
     *  - limit_mode, limits
     *  - wp_users           → all WP users for the Obmann dropdown
     *  - obmann_assignments → meldegruppe_name → {user_id, display_name}
     *  - jagdbezirke        → all Jagdbezirke with meldegruppe_ids[]
     *  - meldegruppen_db    → DB rows for this wildart (id, name)
     */
    public function get_wildart_config($wildart) {
        $meldegruppen = $this->get_meldegruppen($wildart);

        return [
            'categories'         => $this->get_categories($wildart),
            'meldegruppen'       => $meldegruppen,
            'limit_mode'         => $this->get_limit_mode($wildart),
            'limits'             => $this->get_limits($wildart),
            'wp_users'           => $this->get_wp_users_for_dropdown(),
            'obmann_assignments' => $this->get_obmann_assignments_for_wildart($wildart),
            'jagdbezirke'        => $this->get_all_jagdbezirke_with_assignments(),
            'meldegruppen_db'    => $this->get_meldegruppen_db_rows($wildart, $meldegruppen),
        ];
    }

    /**
     * Get all WordPress users as a simple array for dropdown rendering.
     */
    private function get_wp_users_for_dropdown() {
        $users  = get_users(['fields' => ['ID', 'display_name', 'user_login'], 'orderby' => 'display_name']);
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id'           => (int) $user->ID,
                'display_name' => $user->display_name,
                'user_login'   => $user->user_login,
            ];
        }
        return $result;
    }

    /**
     * Build a meldegruppe_name → {user_id, display_name} map for this wildart.
     * Reads from the canonical usermeta storage (ahgmh_assigned_meldegruppe_{wildart}).
     */
    private function get_obmann_assignments_for_wildart($wildart) {
        $meta_key    = 'ahgmh_assigned_meldegruppe_' . sanitize_key($wildart);
        $assignments = [];

        $users = get_users([
            'meta_key'     => $meta_key,
            'meta_compare' => '!=',
            'meta_value'   => '',
            'fields'       => ['ID', 'display_name'],
        ]);

        foreach ($users as $user) {
            $assigned_mg = get_user_meta((int) $user->ID, $meta_key, true);
            if (!empty($assigned_mg)) {
                $assignments[$assigned_mg] = [
                    'user_id'      => (int) $user->ID,
                    'display_name' => $user->display_name,
                ];
            }
        }

        return $assignments;
    }

    /**
     * Get all Jagdbezirke (including inactive) with their current meldegruppe_ids[].
     */
    private function get_all_jagdbezirke_with_assignments() {
        $repo = new AHGMH_Jagdbezirk_Repository();
        return $repo->get_all(false);
    }

    /**
     * Get Meldegruppe DB rows (id + name) for a specific wildart.
     * Matches DB rows against the option-based list to filter by wildart.
     *
     * @param string $wildart
     * @param array  $meldegruppen Option-based meldegruppe names for this wildart
     * @return array [{id, name}]
     */
    private function get_meldegruppen_db_rows($wildart, $meldegruppen) {
        if (empty($meldegruppen)) {
            return [];
        }

        $repo   = new AHGMH_Jagdbezirk_Repository();
        $all_db = $repo->get_all_meldegruppen(false);
        $result = [];

        foreach ($all_db as $row) {
            if (in_array($row['name'], $meldegruppen, true)) {
                $result[] = [
                    'id'   => (int) $row['id'],
                    'name' => $row['name'],
                ];
            }
        }

        return $result;
    }
    
    /**
     * Get categories for wildart
     */
    public function get_categories($wildart) {
        return $this->repository->get_categories($wildart);
    }
    
    /**
     * Save categories for wildart
     */
    public function save_categories($wildart, $categories) {
        $categories = AHGMH_Validation_Service::sanitize_text_array($categories);
        $this->repository->save_categories($wildart, $categories);
    }
    
    /**
     * Get meldegruppen for wildart
     */
    public function get_meldegruppen($wildart) {
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);

        if (isset($meldegruppen[$wildart]) && !empty($meldegruppen[$wildart])) {
            return AHGMH_Validation_Service::sanitize_text_array($meldegruppen[$wildart]);
        }

        // Initialize default meldegruppen if none configured
        $this->initialize_default_meldegruppen($wildart);

        // Re-fetch from database after initialization
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);

        if (isset($meldegruppen[$wildart]) && !empty($meldegruppen[$wildart])) {
            return AHGMH_Validation_Service::sanitize_text_array($meldegruppen[$wildart]);
        }

        // Final fallback
        return ['Gruppe_A', 'Gruppe_B'];
    }
    
    /**
     * Save meldegruppen for wildart
     */
    public function save_meldegruppen($wildart, $meldegruppen) {
        // Validate and sanitize input
        $meldegruppen = AHGMH_Validation_Service::sanitize_text_array($meldegruppen);

        // Remove empty entries
        $meldegruppen = array_filter($meldegruppen, function($item) {
            return !empty(trim($item));
        });

        // Get current configuration
        $wildart_meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        $wildart_meldegruppen[$wildart] = array_values($meldegruppen);

        // Save to database
        $result = update_option('ahgmh_wildart_meldegruppen', $wildart_meldegruppen);

        // Also update the global meldegruppen list for backwards compatibility
        $this->update_global_meldegruppen_list();

        // Notify Jagdbezirk repository to re-sync DB tables on next access
        // by clearing any cached state (the repository syncs lazily on empty table).
        // Direct sync: reset the hgmh_meldegruppen table entries for this wildart
        // so get_all_meldegruppen() triggers a full re-sync next time.
        do_action( 'ahgmh_meldegruppen_updated', $wildart, array_values( $meldegruppen ) );

        return $result;
    }
    
    /**
     * Initialize default meldegruppen for new wildart
     */
    private function initialize_default_meldegruppen($wildart) {
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);

        // Only initialize if not already set
        if (!isset($meldegruppen[$wildart]) || empty($meldegruppen[$wildart])) {
            $meldegruppen[$wildart] = ['Gruppe_A', 'Gruppe_B'];
            update_option('ahgmh_wildart_meldegruppen', $meldegruppen);

            // Also update the global meldegruppen list for backwards compatibility
            $this->update_global_meldegruppen_list();
        }
    }
    
    
    /**
     * Update global meldegruppen list from all wildarten
     */
    private function update_global_meldegruppen_list() {
        $all_meldegruppen = [];
        $wildarten = $this->repository->get_all();

        foreach ($wildarten as $wildart) {
            $meldegruppen = $this->repository->get_meldegruppen($wildart);
            $all_meldegruppen = array_merge($all_meldegruppen, $meldegruppen);
        }

        // Remove duplicates and update global list
        $all_meldegruppen = array_unique($all_meldegruppen);
        update_option('ahgmh_meldegruppen', $all_meldegruppen);
    }
    
    /**
     * Get limit mode for wildart
     */
    public function get_limit_mode($wildart) {
        return $this->repository->get_limit_mode($wildart);
    }
    
    /**
     * Set limit mode for wildart
     */
    public function set_limit_mode($wildart, $mode) {
        $this->repository->set_limit_mode($wildart, $mode);
    }
    
    /**
     * Get limits for wildart
     */
    public function get_limits($wildart) {
        return $this->repository->get_limits($wildart);
    }
    
    /**
     * Save limits for wildart
     */
    public function save_limits($wildart, $limits) {
        $this->repository->save_limits($wildart, $limits);
    }

    /**
     * Assign Obmann to meldegruppe
     *
     * @param string $wildart Wildart name
     * @param string $meldegruppe Meldegruppe name
     * @param int $user_id WordPress user ID
     * @return bool True on success, false on failure
     */
    public function assign_obmann($wildart, $meldegruppe, $user_id) {
        $wildart = sanitize_text_field($wildart);
        $meldegruppe = sanitize_text_field($meldegruppe);
        $user_id = absint($user_id);

        if (empty($wildart) || empty($meldegruppe) || $user_id <= 0) {
            throw new Exception('Ungültige Parameter für Obmann-Zuweisung');
        }

        $meldegruppe_repo = new AHGMH_Meldegruppe_Repository();
        return $meldegruppe_repo->assign_obmann($wildart, $meldegruppe, $user_id);
    }

    /**
     * Remove Obmann assignment from meldegruppe
     *
     * @param string $wildart Wildart name
     * @param string $meldegruppe Meldegruppe name
     * @return bool True on success, false on failure
     */
    public function remove_obmann_assignment($wildart, $meldegruppe) {
        $wildart = sanitize_text_field($wildart);
        $meldegruppe = sanitize_text_field($meldegruppe);

        if (empty($wildart) || empty($meldegruppe)) {
            throw new Exception('Ungültige Parameter für Obmann-Entfernung');
        }

        $meldegruppe_repo = new AHGMH_Meldegruppe_Repository();
        return $meldegruppe_repo->remove_obmann($wildart, $meldegruppe);
    }

    /**
     * Get Obmann assignments
     *
     * @param string|null $wildart Optional wildart name to filter assignments
     * @return array Array of Obmann assignments
     */
    public function get_obmann_assignments($wildart = null) {
        $meldegruppe_repo = new AHGMH_Meldegruppe_Repository();

        if ($wildart !== null) {
            $wildart = sanitize_text_field($wildart);
            return $meldegruppe_repo->get_obmann_assignments_by_wildart($wildart);
        }

        return $meldegruppe_repo->get_obmann_assignments();
    }

    /**
     * Save wildart order
     *
     * @param array $ordered_wildarten Array of wildart names in desired order
     * @return bool True on success
     */
    public function save_wildart_order($ordered_wildarten) {
        return $this->repository->save_order($ordered_wildarten);
    }

    /**
     * Get meldegruppen for a specific wildart
     * (Alias for get_meldegruppen for clearer API semantics)
     *
     * @param string $wildart Wildart name
     * @return array Array of meldegruppen
     */
    public function get_meldegruppen_for_wildart($wildart) {
        return $this->get_meldegruppen($wildart);
    }

    /**
     * Reset all Obmann assignments
     *
     * @return bool True on success
     */
    public function reset_all_obmann_assignments() {
        $meldegruppe_repo = new AHGMH_Meldegruppe_Repository();
        return $meldegruppe_repo->reset_all_assignments();
    }

}
