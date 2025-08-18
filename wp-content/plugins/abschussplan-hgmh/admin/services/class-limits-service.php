<?php
/**
 * Limits Service - Business logic for limits management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Limits_Service {
    
    public function save_limits($limits) {
        $sanitized_limits = AHGMH_Validation_Service::sanitize_text_array($limits);
        update_option('ahgmh_limits', $sanitized_limits);
    }
    
    public function toggle_custom_limits($enabled) {
        update_option('ahgmh_custom_limits_enabled', (bool)$enabled);
    }
    
    public function get_limits() {
        return get_option('ahgmh_limits', []);
    }
}
