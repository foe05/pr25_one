<?php
/**
 * AHGMH Permissions Service
 * 
 * Handles 3-level permission system:
 * 1. Besucher (ohne WP-Login): Nur [abschuss_summary]
 * 2. Obmann (WP-User): Meldegruppen-spezifischer Zugriff (pro Wildart)
 * 3. Vorstand (WP-Admin): Vollzugriff + Obmann-Verwaltung
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Permissions_Service {
    
    /**
     * Check if user can access specific meldegruppe for a wildart
     * 
     * @param int $user_id WordPress user ID
     * @param string $wildart Wildart name (e.g., 'Rotwild', 'Damwild')
     * @param string $meldegruppe Meldegruppe name
     * @return bool True if user can access
     */
    public static function user_can_access_meldegruppe($user_id, $wildart, $meldegruppe) {
        // Vorstand has full access
        if (self::is_vorstand($user_id)) {
            return true;
        }
        
        // Check wildart-specific meldegruppe assignment
        $assigned_meldegruppe = self::get_user_meldegruppe($user_id, $wildart);
        
        return ($assigned_meldegruppe === $meldegruppe);
    }
    
    /**
     * Check if user is Obmann for specific wildart
     * 
     * @param int $user_id WordPress user ID
     * @param string $wildart Wildart name
     * @return bool True if user is assigned to meldegruppe for this wildart
     */
    public static function is_obmann_for_wildart($user_id, $wildart) {
        if (!$user_id || !is_user_logged_in()) {
            return false;
        }
        
        $assigned_meldegruppe = self::get_user_meldegruppe($user_id, $wildart);
        return !empty($assigned_meldegruppe);
    }
    
    /**
     * Check if user is Vorstand (admin)
     * 
     * @param int $user_id WordPress user ID  
     * @return bool True if user has admin capabilities
     */
    public static function is_vorstand($user_id) {
        if (!$user_id) {
            return false;
        }
        
        return user_can($user_id, 'manage_options');
    }
    
    /**
     * Get user's assigned meldegruppe for specific wildart
     * 
     * @param int $user_id WordPress user ID
     * @param string $wildart Wildart name
     * @return string|false Meldegruppe name or false if not assigned
     */
    public static function get_user_meldegruppe($user_id, $wildart) {
        if (!$user_id) {
            return false;
        }
        
        $meta_key = self::get_wildart_meta_key($wildart);
        return get_user_meta($user_id, $meta_key, true);
    }
    
    /**
     * Assign user to meldegruppe for specific wildart
     * 
     * @param int $user_id WordPress user ID
     * @param string $wildart Wildart name
     * @param string $meldegruppe Meldegruppe name
     * @return bool True on success
     */
    public static function assign_user_to_meldegruppe($user_id, $wildart, $meldegruppe) {
        if (!$user_id || !$wildart || !$meldegruppe) {
            return false;
        }
        
        // Only Vorstand can assign users
        if (!self::is_vorstand(get_current_user_id())) {
            return false;
        }
        
        $meta_key = self::get_wildart_meta_key($wildart);
        return update_user_meta($user_id, $meta_key, sanitize_text_field($meldegruppe));
    }
    
    /**
     * Remove user assignment from meldegruppe for specific wildart
     * 
     * @param int $user_id WordPress user ID
     * @param string $wildart Wildart name
     * @return bool True on success
     */
    public static function remove_user_assignment($user_id, $wildart) {
        if (!$user_id || !$wildart) {
            return false;
        }
        
        // Only Vorstand can remove assignments
        if (!self::is_vorstand(get_current_user_id())) {
            return false;
        }
        
        $meta_key = self::get_wildart_meta_key($wildart);
        return delete_user_meta($user_id, $meta_key);
    }
    
    /**
     * Get all wildarten where user has meldegruppe assignment
     * 
     * @param int $user_id WordPress user ID
     * @return array Array of wildarten with assignments
     */
    public static function get_user_wildarten($user_id) {
        if (!$user_id) {
            return array();
        }
        
        // Get all species from options
        $all_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $user_wildarten = array();
        
        foreach ($all_species as $wildart) {
            if (self::is_obmann_for_wildart($user_id, $wildart)) {
                $user_wildarten[] = $wildart;
            }
        }
        
        return $user_wildarten;
    }
    
    /**
     * Check if user can access shortcode
     * 
     * @param string $shortcode_name Name of shortcode
     * @param array $atts Shortcode attributes
     * @return bool True if user can access
     */
    public static function can_access_shortcode($shortcode_name, $atts = array()) {
        $user_id = get_current_user_id();
        
        switch ($shortcode_name) {
            case 'abschuss_summary':
                // Public access for summary
                return true;
                
            case 'abschuss_form':
            case 'abschuss_table':
                // Require login
                if (!$user_id) {
                    return false;
                }
                
                // Vorstand has full access
                if (self::is_vorstand($user_id)) {
                    return true;
                }
                
                // Check if user has assignment for requested wildart
                if (isset($atts['species']) && !empty($atts['species'])) {
                    return self::is_obmann_for_wildart($user_id, $atts['species']);
                }
                
                // If no specific species, check if user has any assignment
                $user_wildarten = self::get_user_wildarten($user_id);
                return !empty($user_wildarten);
                
            case 'abschuss_admin':
            case 'abschuss_limits':
                // Admin only
                return self::is_vorstand($user_id);
                
            default:
                return false;
        }
    }
    
    /**
     * Filter data based on user permissions
     * 
     * @param array $data Original data
     * @param string $context Context (submissions, summary, etc.)
     * @param array $params Additional parameters
     * @return array Filtered data
     */
    public static function filter_data_for_user($data, $context, $params = array()) {
        $user_id = get_current_user_id();
        
        // Vorstand sees everything
        if (self::is_vorstand($user_id)) {
            return $data;
        }
        
        // Visitors see limited data
        if (!$user_id) {
            return self::filter_data_for_visitors($data, $context);
        }
        
        // Obmann sees filtered data
        return self::filter_data_for_obmann($data, $context, $user_id, $params);
    }
    
    /**
     * Filter data for visitors (non-logged in users)
     */
    private static function filter_data_for_visitors($data, $context) {
        switch ($context) {
            case 'summary':
                // Only show aggregated statistics, no details
                return $data;
            default:
                return array();
        }
    }
    
    /**
     * Filter data for Obmann (logged in users with meldegruppe assignments)
     */
    private static function filter_data_for_obmann($data, $context, $user_id, $params) {
        $species = isset($params['species']) ? $params['species'] : '';
        
        if (!$species) {
            return array();
        }
        
        $user_meldegruppe = self::get_user_meldegruppe($user_id, $species);
        
        if (!$user_meldegruppe) {
            return array();
        }
        
        // Filter data to only show user's meldegruppe
        return array_filter($data, function($item) use ($user_meldegruppe) {
            return isset($item['meldegruppe']) && $item['meldegruppe'] === $user_meldegruppe;
        });
    }
    
    /**
     * Generate login form HTML for restricted shortcodes
     * 
     * @param string $shortcode_name Name of shortcode that requires login
     * @param string $redirect_to URL to redirect to after login
     * @return string HTML login form
     */
    public static function get_login_form($shortcode_name = '', $redirect_to = '') {
        if (empty($redirect_to)) {
            $redirect_to = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        
        $login_url = wp_login_url($redirect_to);
        
        $html = '<div class="ahgmh-login-required alert alert-info">';
        $html .= '<h4><i class="bi bi-lock"></i> Anmeldung erforderlich</h4>';
        $html .= '<p>Um diese Funktion zu nutzen, müssen Sie sich anmelden.</p>';
        $html .= '<a href="' . esc_url($login_url) . '" class="btn btn-primary">';
        $html .= '<i class="bi bi-box-arrow-in-right"></i> Anmelden';
        $html .= '</a>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate permission denied HTML
     * 
     * @param string $reason Reason for denial
     * @return string HTML error message
     */
    public static function get_permission_denied($reason = '') {
        $html = '<div class="ahgmh-permission-denied alert alert-warning">';
        $html .= '<h4><i class="bi bi-exclamation-triangle"></i> Zugriff verweigert</h4>';
        
        if ($reason) {
            $html .= '<p>' . esc_html($reason) . '</p>';
        } else {
            $html .= '<p>Sie haben keine Berechtigung für diese Funktion.</p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get wildart-specific user meta key
     * 
     * @param string $wildart Wildart name
     * @return string Meta key
     */
    private static function get_wildart_meta_key($wildart) {
        return 'ahgmh_assigned_meldegruppe_' . sanitize_key($wildart);
    }
    
    /**
     * Get all users assigned to meldegruppe for specific wildart
     * 
     * @param string $wildart Wildart name
     * @param string $meldegruppe Meldegruppe name
     * @return array Array of user IDs
     */
    public static function get_users_by_meldegruppe($wildart, $meldegruppe) {
        if (!self::is_vorstand(get_current_user_id())) {
            return array();
        }
        
        $meta_key = self::get_wildart_meta_key($wildart);
        
        $users = get_users(array(
            'meta_key' => $meta_key,
            'meta_value' => $meldegruppe,
            'fields' => 'ID'
        ));
        
        return $users;
    }
    
    /**
     * Get all meldegruppe assignments for a user
     * 
     * @param int $user_id WordPress user ID
     * @return array Array of assignments [wildart => meldegruppe]
     */
    public static function get_user_assignments($user_id) {
        if (!$user_id) {
            return array();
        }
        
        $assignments = array();
        $all_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        foreach ($all_species as $wildart) {
            $meldegruppe = self::get_user_meldegruppe($user_id, $wildart);
            if ($meldegruppe) {
                $assignments[$wildart] = $meldegruppe;
            }
        }
        
        return $assignments;
    }
    
    /**
     * Debug function to show current user permissions
     * 
     * @return string HTML debug output
     */
    public static function debug_permissions() {
        if (!WP_DEBUG) {
            return '';
        }
        
        $user_id = get_current_user_id();
        $html = '<div class="ahgmh-debug-permissions alert alert-secondary">';
        $html .= '<h5>Debug: User Permissions</h5>';
        $html .= '<p><strong>User ID:</strong> ' . ($user_id ?: 'Not logged in') . '</p>';
        
        if ($user_id) {
            $user = get_user_by('ID', $user_id);
            $html .= '<p><strong>Username:</strong> ' . $user->user_login . '</p>';
            $html .= '<p><strong>Is Vorstand:</strong> ' . (self::is_vorstand($user_id) ? 'Yes' : 'No') . '</p>';
            
            $assignments = self::get_user_assignments($user_id);
            if (!empty($assignments)) {
                $html .= '<p><strong>Meldegruppe Assignments:</strong></p>';
                $html .= '<ul>';
                foreach ($assignments as $wildart => $meldegruppe) {
                    $html .= '<li>' . $wildart . ': ' . $meldegruppe . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p><strong>No meldegruppe assignments</strong></p>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
