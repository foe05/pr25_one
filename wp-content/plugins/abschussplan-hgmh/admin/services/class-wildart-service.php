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
        $meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        
        if (isset($meldegruppen[$wildart]) && !empty($meldegruppen[$wildart])) {
            return AHGMH_Validation_Service::sanitize_text_array($meldegruppen[$wildart]);
        }
        
        // Return default meldegruppen if none configured
        return ['Gruppe_A', 'Gruppe_B', 'Gruppe_C'];
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
        
        return $result;
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
        
        // Migration: Convert old 'meldegruppen_specific' to 'jagdbezirk_specific'
        if ($original_mode === 'meldegruppen_specific') {
            $modes[$wildart] = 'jagdbezirk_specific';
            update_option('ahgmh_limit_modes', $modes);
            return 'jagdbezirk_specific';
        } else if ($original_mode === null) {
            // Never explicitly set - intelligent detection
            $all_limits = get_option('ahgmh_wildart_limits', []);
            $has_specific_limits = isset($all_limits[$wildart]) && !empty($all_limits[$wildart]);
            
            return $has_specific_limits ? 'jagdbezirk_specific' : 'hegegemeinschaft_total';
        }
        
        return $original_mode;
    }
    
    /**
     * Set limit mode for wildart
     */
    public function set_limit_mode($wildart, $mode) {
        if (!in_array($mode, ['jagdbezirk_specific', 'hegegemeinschaft_total'])) {
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
