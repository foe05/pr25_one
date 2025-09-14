<?php
/**
 * Uninstall script for Abschussplan HGMH Plugin
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It removes all plugin data including database tables and options.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if uninstall is called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin database tables and options
 */
function ahgmh_uninstall_cleanup() {
    global $wpdb;
    
    // Include the database handler class for cleanup
    require_once plugin_dir_path(__FILE__) . 'includes/class-database-handler.php';
    
    // Remove the main submissions table using the database handler
    AHGMH_Database_Handler::cleanup_database();
    
    // Remove all plugin options from wp_options table
    $plugin_options = array(
        'ahgmh_species',
        'ahgmh_categories',
        'abschuss_db_config',
        'abschussplan_hgmh_settings',
        'abschussplan_hgmh_version',
        'ahgmh_export_filename'
    );
    
    // Remove standard plugin options
    foreach ($plugin_options as $option) {
        delete_option($option);
    }
    
    // Remove species-specific options (dynamic options with patterns)
    $species_options = $wpdb->get_results(
        "SELECT option_name FROM {$wpdb->options} 
         WHERE option_name LIKE 'abschuss_category_limits_%' 
            OR option_name LIKE 'abschuss_category_allow_exceeding_%'
            OR option_name LIKE 'ahgmh_%'
            OR option_name LIKE 'abschussplan_hgmh_%'"
    );
    
    $deleted_options_count = 0;
    foreach ($species_options as $option) {
        if (delete_option($option->option_name)) {
            $deleted_options_count++;
        }
    }
    
    // Remove all Obmann assignments (user meta keys)
    $obmann_meta_keys = $wpdb->get_results(
        "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} 
         WHERE meta_key LIKE 'ahgmh_assigned_meldegruppe_%'"
    );
    
    $deleted_meta_count = 0;
    foreach ($obmann_meta_keys as $meta) {
        $deleted = $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => $meta->meta_key),
            array('%s')
        );
        if ($deleted) {
            $deleted_meta_count += $deleted;
        }
    }
    
    // Log cleanup results (optional - for debugging)
    error_log("Abschussplan HGMH Plugin: Uninstall cleanup completed. Removed " . count($plugin_options) . " standard options, " . $deleted_options_count . " species-specific options, and " . $deleted_meta_count . " Obmann assignments.");
    
    // Clear any cached data
    wp_cache_flush();
}

// Execute cleanup
ahgmh_uninstall_cleanup();
