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
     * Get all wildarten
     */
    public function get_all_wildarten() {
        $species = get_option('ahgmh_species', ['Rotwild', 'Damwild', 'Rehwild']);
        return AHGMH_Validation_Service::sanitize_text_array($species);
    }
    
    /**
     * Create new wildart
     */
    public function create_wildart($name) {
        $name = AHGMH_Validation_Service::validate_species_name($name);
        if (!$name) {
            throw new Exception('Ungültiger Wildart-Name');
        }
        
        $species = $this->get_all_wildarten();
        
        if (in_array($name, $species, true)) {
            throw new Exception('Wildart existiert bereits');
        }
        
        $species[] = $name;
        update_option('ahgmh_species', $species);
        
        // Initialize default meldegruppen for new wildart
        $this->initialize_default_meldegruppen($name);
        
        return ['species' => $species, 'created' => $name];
    }
    
    /**
     * Delete wildart
     */
    public function delete_wildart($wildart) {
        $species = $this->get_all_wildarten();
        $key = array_search($wildart, $species, true);  // Use strict comparison
        
        if ($key === false) {
            throw new Exception('Wildart nicht gefunden');
        }
        
        unset($species[$key]);
        $species = array_values($species);
        update_option('ahgmh_species', $species);
        
        // Clean up related data
        $this->cleanup_wildart_data($wildart);
        
        return ['species' => $species, 'deleted' => $wildart];
    }
    
    /**
     * Get wildart configuration
     */
    public function get_wildart_config($wildart) {
        return [
            'categories' => $this->get_categories($wildart),
            'meldegruppen' => $this->get_meldegruppen($wildart),
            'limit_mode' => $this->get_limit_mode($wildart),
            'limits' => $this->get_limits($wildart)
        ];
    }
    
    /**
     * Get categories for wildart
     */
    public function get_categories($wildart) {
        $categories = get_option('ahgmh_wildart_categories', []);
        
        if (isset($categories[$wildart])) {
            return AHGMH_Validation_Service::sanitize_text_array($categories[$wildart]);
        }
        
        // Return default categories
        return $this->get_default_categories($wildart);
    }
    
    /**
     * Save categories for wildart
     */
    public function save_categories($wildart, $categories) {
        $categories = AHGMH_Validation_Service::sanitize_text_array($categories);
        
        $wildart_categories = get_option('ahgmh_wildart_categories', []);
        $wildart_categories[$wildart] = $categories;
        
        update_option('ahgmh_wildart_categories', $wildart_categories);
    }
    
    /**
     * Get meldegruppen for wildart
     */
    public function get_meldegruppen($wildart) {
        // DEBUG: Log get request
        error_log("=== GET MELDEGRUPPEN DEBUG ===");
        error_log("Requested wildart: " . $wildart);
        
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        error_log("Raw option data: " . print_r($meldegruppen, true));
        
        if (isset($meldegruppen[$wildart]) && !empty($meldegruppen[$wildart])) {
            $result = AHGMH_Validation_Service::sanitize_text_array($meldegruppen[$wildart]);
            error_log("Found data for wildart - returning: " . print_r($result, true));
            error_log("=== END GET DEBUG ===");
            return $result;
        }
        
        error_log("No data found for wildart - initializing defaults");
        // Initialize default meldegruppen if none configured
        $this->initialize_default_meldegruppen($wildart);
        
        // Re-fetch from database after initialization
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        error_log("After initialization: " . print_r($meldegruppen, true));
        
        if (isset($meldegruppen[$wildart]) && !empty($meldegruppen[$wildart])) {
            $result = AHGMH_Validation_Service::sanitize_text_array($meldegruppen[$wildart]);
            error_log("After initialization found data - returning: " . print_r($result, true));
            error_log("=== END GET DEBUG ===");
            return $result;
        }
        
        // Final fallback (should never happen)
        error_log("Final fallback - returning hardcoded defaults");
        error_log("=== END GET DEBUG ===");
        return ['Gruppe_A', 'Gruppe_B'];
    }
    
    /**
     * Save meldegruppen for wildart
     */
    public function save_meldegruppen($wildart, $meldegruppen) {
        // DEBUG: Log incoming data
        error_log("=== SAVE MELDEGRUPPEN DEBUG ===");
        error_log("Wildart: " . $wildart);
        error_log("Incoming meldegruppen: " . print_r($meldegruppen, true));
        
        // Validate and sanitize input
        $meldegruppen = AHGMH_Validation_Service::sanitize_text_array($meldegruppen);
        error_log("After sanitization: " . print_r($meldegruppen, true));
        
        // Remove empty entries
        $meldegruppen = array_filter($meldegruppen, function($item) {
            return !empty(trim($item));
        });
        error_log("After filtering: " . print_r($meldegruppen, true));
        
        // Get current configuration
        $wildart_meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        error_log("Current config before save: " . print_r($wildart_meldegruppen, true));
        
        $wildart_meldegruppen[$wildart] = array_values($meldegruppen);
        error_log("Config after update: " . print_r($wildart_meldegruppen, true));
        
        // Save to database
        $result = update_option('ahgmh_wildart_meldegruppen', $wildart_meldegruppen);
        error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        // Verify save
        $verify = get_option('ahgmh_wildart_meldegruppen', []);
        error_log("Verification - what's actually in DB: " . print_r($verify, true));
        
        // Also update the global meldegruppen list for backwards compatibility
        $this->update_global_meldegruppen_list();
        error_log("=== END SAVE DEBUG ===");
        
        return $result;
    }
    
    /**
     * Initialize default meldegruppen for new wildart
     */
    private function initialize_default_meldegruppen($wildart) {
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        error_log("INIT DEFAULT DEBUG: Current meldegruppen before init: " . print_r($meldegruppen, true));
        
        // Only initialize if not already set
        if (!isset($meldegruppen[$wildart]) || empty($meldegruppen[$wildart])) {
            error_log("INIT DEFAULT DEBUG: Initializing defaults for " . $wildart);
            $meldegruppen[$wildart] = ['Gruppe_A', 'Gruppe_B'];
            $result = update_option('ahgmh_wildart_meldegruppen', $meldegruppen);
            error_log("INIT DEFAULT DEBUG: Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            // Also update the global meldegruppen list for backwards compatibility
            $this->update_global_meldegruppen_list();
        } else {
            error_log("INIT DEFAULT DEBUG: NOT initializing - data already exists for " . $wildart);
        }
    }
    
    /**
     * Initialize default meldegruppen for all existing wildarten
     */
    public function initialize_all_wildarten_meldegruppen() {
        $wildarten = $this->get_all_wildarten();
        foreach ($wildarten as $wildart) {
            $this->initialize_default_meldegruppen($wildart);
        }
    }
    
    /**
     * Update global meldegruppen list from all wildarten
     */
    private function update_global_meldegruppen_list() {
        $all_meldegruppen = [];
        $wildart_meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        
        foreach ($wildart_meldegruppen as $wildart => $meldegruppen) {
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
        $modes = get_option('ahgmh_limit_modes', []);
        $original_mode = isset($modes[$wildart]) ? $modes[$wildart] : null; // NULL = never explicitly set
        
        // Migration: Convert old 'jagdbezirk_specific' back to 'meldegruppen_specific' for compatibility
        if ($original_mode === 'jagdbezirk_specific') {
            $modes[$wildart] = 'meldegruppen_specific';
            update_option('ahgmh_limit_modes', $modes);
            return 'meldegruppen_specific';
        } else if ($original_mode === null) {
            // Never explicitly set - intelligent detection
            $all_limits = get_option('ahgmh_wildart_limits', []);
            $has_specific_limits = isset($all_limits[$wildart]) && !empty($all_limits[$wildart]);
            
            return $has_specific_limits ? 'meldegruppen_specific' : 'hegegemeinschaft_total';
        }
        
        return $original_mode;
    }
    
    /**
     * Set limit mode for wildart
     */
    public function set_limit_mode($wildart, $mode) {
        if (!in_array($mode, ['meldegruppen_specific', 'hegegemeinschaft_total'])) {
            throw new Exception('Ungültiger Limit-Modus');
        }
        
        $modes = get_option('ahgmh_limit_modes', []);
        $modes[$wildart] = $mode;
        
        update_option('ahgmh_limit_modes', $modes);
    }
    
    /**
     * Get limits for wildart
     */
    public function get_limits($wildart) {
        $limits = get_option('ahgmh_wildart_limits', []);
        return isset($limits[$wildart]) ? $limits[$wildart] : [];
    }
    
    /**
     * Save limits for wildart
     */
    public function save_limits($wildart, $limits) {
        $wildart_limits = get_option('ahgmh_wildart_limits', []);
        $wildart_limits[$wildart] = $limits;
        
        update_option('ahgmh_wildart_limits', $wildart_limits);
    }
    
    /**
     * Get default categories for a species
     */
    private function get_default_categories($species) {
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
        
        return isset($defaults[$species]) ? $defaults[$species] : [];
    }
    
    /**
     * Clean up wildart-related data when deleting
     */
    private function cleanup_wildart_data($wildart) {
        // Remove from categories
        $categories = get_option('ahgmh_wildart_categories', []);
        unset($categories[$wildart]);
        update_option('ahgmh_wildart_categories', $categories);
        
        // Remove from meldegruppen
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        unset($meldegruppen[$wildart]);
        update_option('ahgmh_wildart_meldegruppen', $meldegruppen);
        
        // Remove from limits
        $limits = get_option('ahgmh_wildart_limits', []);
        unset($limits[$wildart]);
        update_option('ahgmh_wildart_limits', $limits);
        
        // Remove from limit modes
        $modes = get_option('ahgmh_limit_modes', []);
        unset($modes[$wildart]);
        update_option('ahgmh_limit_modes', $modes);
    }
}
