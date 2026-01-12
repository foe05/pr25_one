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
        // Repository handles existence check and cleanup
        $this->repository->delete($wildart);

        $species = $this->repository->get_all();
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
        return $this->repository->get_meldegruppen($wildart);
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

        // Save via repository
        $result = $this->repository->save_meldegruppen($wildart, array_values($meldegruppen));

        // Update the global meldegruppen list for backwards compatibility
        $this->update_global_meldegruppen_list();

        return $result;
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
    
}
