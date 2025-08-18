<?php
/**
 * Settings Controller - Manages plugin settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Settings_Controller {
    
    private $wildart_controller;
    
    public function __construct() {
        $this->wildart_controller = new AHGMH_Wildart_Controller();
    }
    
    /**
     * Render settings page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap ahgmh-admin-modern">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Einstellungen', 'abschussplan-hgmh'); ?>
            </h1>
            
            <div class="ahgmh-settings-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#wildart-config" class="nav-tab nav-tab-active"><?php echo esc_html__('Wildarten-Konfiguration', 'abschussplan-hgmh'); ?></a>
                    <a href="#general" class="nav-tab"><?php echo esc_html__('Allgemein', 'abschussplan-hgmh'); ?></a>
                    <a href="#export" class="nav-tab"><?php echo esc_html__('Export', 'abschussplan-hgmh'); ?></a>
                </nav>
                
                <div id="wildart-config" class="ahgmh-tab-content">
                    <?php echo $this->wildart_controller->render_config_section(); ?>
                </div>
                
                <div id="general" class="ahgmh-tab-content" style="display: none;">
                    <h2><?php echo esc_html__('Allgemeine Einstellungen', 'abschussplan-hgmh'); ?></h2>
                    <p><?php echo esc_html__('Hier können allgemeine Plugin-Einstellungen konfiguriert werden.', 'abschussplan-hgmh'); ?></p>
                </div>
                
                <div id="export" class="ahgmh-tab-content" style="display: none;">
                    <h2><?php echo esc_html__('Export-Einstellungen', 'abschussplan-hgmh'); ?></h2>
                    <p><?php echo esc_html__('Export-Konfiguration für CSV und Excel-Dateien.', 'abschussplan-hgmh'); ?></p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                // Update tabs
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show content
                $('.ahgmh-tab-content').hide();
                $($(this).attr('href')).show();
            });
        });
        </script>
        <?php
    }
}
