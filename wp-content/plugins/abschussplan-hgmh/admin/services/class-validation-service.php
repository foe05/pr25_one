<?php
/**
 * Validation Service Class
 * Centralized security and validation for admin operations
 *
 * This service provides reusable security validation methods for AJAX handlers
 * and admin operations. It eliminates duplicated security checks by providing
 * a single point of validation for nonce verification, capability checks, and
 * data sanitization.
 *
 * @package AbschussplanHGMH
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validation Service for security and input sanitization
 *
 * Provides centralized methods for:
 * - AJAX request security validation (nonce + capability checks)
 * - Input sanitization and validation
 * - Safe output escaping
 * - Export parameter validation
 *
 * Usage in AJAX handlers:
 * ```php
 * function my_ajax_handler() {
 *     AHGMH_Validation_Service::verify_ajax_request();
 *     // Your handler code here
 * }
 * ```
 *
 * @package AbschussplanHGMH
 * @since 1.0.0
 */
class AHGMH_Validation_Service {
    
    /**
     * Verify AJAX request with nonce and capability check
     *
     * This method performs two critical security checks:
     * 1. Verifies the nonce to prevent CSRF attacks
     * 2. Verifies the user has the required capability
     *
     * If either check fails, the method terminates execution with wp_send_json_error()
     * and returns an error message to the client. This eliminates the need for
     * duplicated security checks in every AJAX handler.
     *
     * Example usage:
     * ```php
     * function ahgmh_my_ajax_handler() {
     *     // Single line replaces 5+ lines of boilerplate security checks
     *     AHGMH_Validation_Service::verify_ajax_request();
     *
     *     // Your handler code here - only reached if security checks pass
     *     wp_send_json_success($data);
     * }
     * ```
     *
     * @param string $action The nonce action name. Default: 'ahgmh_admin_nonce'
     * @param string $capability Required user capability. Default: 'manage_options'
     * @return void Terminates with wp_send_json_error() if validation fails
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
     *
     * Recursively sanitizes all string values in an array structure using
     * WordPress's sanitize_text_field(). Works with nested arrays of any depth.
     *
     * Example:
     * ```php
     * $input = [
     *     'name' => '<script>alert("xss")</script>',
     *     'items' => ['item1', 'item2'],
     *     'nested' => ['key' => 'value<script>']
     * ];
     * $clean = AHGMH_Validation_Service::sanitize_text_array($input);
     * // All strings are sanitized, including nested values
     * ```
     *
     * @param mixed $data Input data to sanitize (array or string)
     * @return mixed Sanitized data with same structure as input
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
     *
     * Validates and sanitizes all fields in wildart configuration data:
     * - name: Required, must not be empty
     * - categories: Array of category names
     * - meldegruppen: Array of meldegruppe names
     * - limits: Nested array of meldegruppe/category limits
     *
     * @param array $data Raw wildart data from client
     * @return array Sanitized wildart data
     * @throws void Terminates with wp_send_json_error() if name is empty
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
     *
     * Sanitizes nested array structure for meldegruppe limits:
     * Structure: [meldegruppe => [category => limit_value]]
     *
     * @param mixed $limits Raw limits data
     * @return array Sanitized limits array
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
     *
     * Ensures pagination values are safe integers within acceptable ranges:
     * - page: Minimum 1
     * - per_page: Between 1 and 100 (prevents excessive queries)
     *
     * @param int $page Page number (will be sanitized to positive integer)
     * @param int $per_page Items per page. Default: 20, Max: 100
     * @return array Array containing [$page, $per_page] with validated values
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
     *
     * Applies appropriate escaping function based on output context.
     * Recursively escapes arrays to protect against XSS attacks.
     *
     * Supported contexts:
     * - 'html': Escapes for HTML content (default)
     * - 'attr': Escapes for HTML attributes
     * - 'url': Escapes and validates URLs
     * - 'js': Escapes for JavaScript strings
     *
     * Example:
     * ```php
     * echo AHGMH_Validation_Service::safe_output($user_input, 'html');
     * echo '<a href="' . AHGMH_Validation_Service::safe_output($url, 'url') . '">';
     * ```
     *
     * @param mixed $data Data to escape (string or array)
     * @param string $context Output context ('html', 'attr', 'url', 'js')
     * @return mixed Escaped data with same structure as input
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
     *
     * Validates species name against business rules:
     * - Must not be empty
     * - Maximum 100 characters
     * - Only letters (including German umlauts), numbers, spaces, hyphens, and parentheses
     *
     * @param string $name Species name to validate
     * @return string|false Sanitized species name if valid, false if invalid
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
     *
     * Validates and sanitizes parameters for data export operations.
     * Format is restricted to allowed types for security.
     *
     * @param string $species Species name to filter export (can be empty for all)
     * @param string $format Export format ('csv' or 'excel'). Defaults to 'csv' if invalid
     * @return array Array containing [$species, $format] with validated values
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
     *
     * Creates a unique, unpredictable filename to prevent unauthorized access
     * to export files. Includes timestamp and random string.
     *
     * Example output: export_2026-01-14_15-30-45_aBcD1234.csv
     *
     * @param string $prefix Filename prefix. Default: 'export'
     * @param string $extension File extension. Default: 'csv'
     * @return string Sanitized, unique filename with format: prefix_timestamp_random.extension
     */
    public static function generate_secure_filename($prefix = 'export', $extension = 'csv') {
        $timestamp = date('Y-m-d_H-i-s');
        $random = wp_generate_password(8, false);
        
        return sanitize_file_name($prefix . '_' . $timestamp . '_' . $random . '.' . $extension);
    }
}
