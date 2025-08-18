<?php
/**
 * Data Controller - Manages submission data
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Data_Controller {
    
    /**
     * Render data management page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Use existing table display for now
        $table_display = new AHGMH_Table_Display();
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Meldungen verwalten', 'abschussplan-hgmh') . '</h1>';
        $table_display->display_submissions_table();
        echo '</div>';
    }
}
