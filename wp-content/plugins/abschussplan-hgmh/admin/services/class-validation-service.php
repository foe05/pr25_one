<?php
/**
 * Validation Service Class
 * Centralized security and validation for admin operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validation Service for security and input sanitization
 */
class AHGMH_Validation_Service {
    
    /**
     * Verify AJAX request with nonce and capability check
     */
    public static function verify_ajax_request($action = 'ahgmh_admin_nonce', $capability = 'manage_options') {
        // Verify nonce - use 'nonce' parameter name to match JavaScript
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Sicherheitsprüfung fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify user capability
        if (!current_user_can($capability)) {
            wp_send_json_error(array(
                'message' => __('Unzureichende Berechtigungen.', 'abschussplan-hgmh')
            ));
        }
    }
    
    /**
     * Sanitize array of text fields deeply
     */
    public static function sanitize_text_array($data) {
        if (!is_array($data)) {
            return sanitize_text_field($data);
        }
        
        return array_map(function($item) {
            return is_array($item) ? self::sanitize_text_array($item) : sanitize_text_field($item);
        }, $data);
    }
    
    /**
     * Validate and sanitize wildart data
     */
    public static function validate_wildart_data($data) {
        $sanitized = [];
        
        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
            if (empty($sanitized['name'])) {
                wp_send_json_error(array(
                    'message' => __('Wildart-Name darf nicht leer sein', 'abschussplan-hgmh')
                ));
            }
        }
        
        if (isset($data['categories'])) {
            $sanitized['categories'] = self::sanitize_text_array($data['categories']);
        }
        
        if (isset($data['meldegruppen'])) {
            $sanitized['meldegruppen'] = self::sanitize_text_array($data['meldegruppen']);
        }
        
        if (isset($data['limits'])) {
            $sanitized['limits'] = self::validate_limits_data($data['limits']);
        }
        
        return $sanitized;
    }
    
    /**
     * Validate and sanitize limits data
     */
    private static function validate_limits_data($limits) {
        if (!is_array($limits)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($limits as $meldegruppe => $categories) {
            $sanitized_meldegruppe = sanitize_text_field($meldegruppe);
            if (!empty($sanitized_meldegruppe) && is_array($categories)) {
                $sanitized[$sanitized_meldegruppe] = [];
                foreach ($categories as $category => $value) {
                    $sanitized_category = sanitize_text_field($category);
                    $sanitized_value = absint($value);
                    if (!empty($sanitized_category)) {
                        $sanitized[$sanitized_meldegruppe][$sanitized_category] = $sanitized_value;
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate pagination parameters
     */
    public static function validate_pagination($page, $per_page = 20) {
        $page = absint($page);
        $per_page = absint($per_page);
        
        if ($page < 1) $page = 1;
        if ($per_page < 1) $per_page = 20;
        if ($per_page > 100) $per_page = 100; // Prevent excessive queries
        
        return [$page, $per_page];
    }
    
    /**
     * Escape output for safe HTML rendering
     */
    public static function safe_output($data, $context = 'html') {
        if (is_array($data)) {
            return array_map(function($item) use ($context) {
                return self::safe_output($item, $context);
            }, $data);
        }
        
        switch ($context) {
            case 'attr':
                return esc_attr($data);
            case 'url':
                return esc_url($data);
            case 'js':
                return esc_js($data);
            case 'html':
            default:
                return esc_html($data);
        }
    }
    
    /**
     * Validate species name
     */
    public static function validate_species_name($name) {
        $name = sanitize_text_field($name);
        
        if (empty($name)) {
            return false;
        }
        
        if (strlen($name) > 100) {
            return false;
        }
        
        // Only allow letters, numbers, spaces, and basic punctuation
        if (!preg_match('/^[a-zA-ZäöüÄÖÜß0-9\s\-\(\)]+$/', $name)) {
            return false;
        }
        
        return $name;
    }
    
    /**
     * Validate export parameters
     */
    public static function validate_export_params($species, $format) {
        $allowed_formats = ['csv', 'excel'];
        $format = sanitize_text_field($format);
        
        if (!in_array($format, $allowed_formats)) {
            $format = 'csv';
        }
        
        $species = sanitize_text_field($species);
        
        return [$species, $format];
    }
    
    /**
     * Generate secure filename for exports
     */
    public static function generate_secure_filename($prefix = 'export', $extension = 'csv') {
        $timestamp = date('Y-m-d_H-i-s');
        $random = wp_generate_password(8, false);
        
        return sanitize_file_name($prefix . '_' . $timestamp . '_' . $random . '.' . $extension);
    }
}
