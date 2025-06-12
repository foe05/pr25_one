<?php
/**
 * Modern Admin Page Class
 * Implements a modern WordPress admin interface with dashboard widgets and tabbed settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Admin Page Class with dashboard-style interface
 */
class AHGMH_Admin_Page_Modern {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ahgmh_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_ahgmh_quick_export', array($this, 'ajax_quick_export'));
        
        // Add WordPress dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    /**
     * Add modern admin menu structure (3 main pages)
     */
    public function add_admin_menu() {
        // Main menu page - Dashboard
        add_menu_page(
            __('Abschussplan HGMH', 'abschussplan-hgmh'),
            __('Abschussplan', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh',
            array($this, 'render_dashboard'),
            'dashicons-chart-pie',
            30
        );
        
        // Dashboard (Übersicht)
        add_submenu_page(
            'abschussplan-hgmh',
            __('Dashboard', 'abschussplan-hgmh'),
            __('📊 Dashboard', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh',
            array($this, 'render_dashboard')
        );
        
        // Data Management (Meldungen)
        add_submenu_page(
            'abschussplan-hgmh',
            __('Meldungen verwalten', 'abschussplan-hgmh'),
            __('📋 Meldungen', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-data',
            array($this, 'render_data_management')
        );
        
        // Settings (Einstellungen)
        add_submenu_page(
            'abschussplan-hgmh',
            __('Einstellungen', 'abschussplan-hgmh'),
            __('⚙️ Einstellungen', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'abschussplan-hgmh') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ahgmh-admin-modern',
            AHGMH_PLUGIN_URL . 'admin/assets/admin-modern.css',
            array(),
            AHGMH_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'ahgmh-admin-modern',
            AHGMH_PLUGIN_URL . 'admin/assets/admin-modern.js',
            array('jquery'),
            AHGMH_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script(
            'ahgmh-admin-modern',
            'ahgmh_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ahgmh_admin_nonce'),
                'strings' => array(
                    'loading' => __('Lädt...', 'abschussplan-hgmh'),
                    'error' => __('Ein Fehler ist aufgetreten', 'abschussplan-hgmh'),
                    'success' => __('Erfolgreich gespeichert', 'abschussplan-hgmh'),
                )
            )
        );
    }

    /**
     * Render modern dashboard with widgets
     */
    public function render_dashboard() {
        $database = abschussplan_hgmh()->database;
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap ahgmh-admin-modern">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php echo esc_html__('Abschussplan HGMH - Dashboard', 'abschussplan-hgmh'); ?>
            </h1>
            
            <!-- Top Stats Cards -->
            <div class="ahgmh-dashboard-stats">
                <div class="ahgmh-stat-card">
                    <div class="stat-icon dashicons dashicons-list-view"></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['total_submissions']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Gesamte Meldungen', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
                
                <div class="ahgmh-stat-card">
                    <div class="stat-icon dashicons dashicons-calendar-alt"></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['this_month']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Dieser Monat', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
                
                <div class="ahgmh-stat-card">
                    <div class="stat-icon dashicons dashicons-groups"></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['active_users']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Aktive Jäger', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
                
                <div class="ahgmh-stat-card">
                    <div class="stat-icon dashicons dashicons-location"></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['species_count']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Wildarten', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
            </div>

            <div class="ahgmh-dashboard-content">
                <!-- Quick Actions Panel -->
                <div class="ahgmh-panel ahgmh-quick-actions">
                    <h2 class="panel-title">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php echo esc_html__('Schnellaktionen', 'abschussplan-hgmh'); ?>
                    </h2>
                    <div class="quick-actions-grid">
                        <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-data'); ?>" class="quick-action-btn">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php echo esc_html__('Alle Meldungen', 'abschussplan-hgmh'); ?>
                        </a>
                        <button class="quick-action-btn" id="quick-export">
                            <span class="dashicons dashicons-download"></span>
                            <?php echo esc_html__('CSV Export', 'abschussplan-hgmh'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-settings'); ?>" class="quick-action-btn">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php echo esc_html__('Einstellungen', 'abschussplan-hgmh'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-data&tab=analysis'); ?>" class="quick-action-btn">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php echo esc_html__('Auswertungen', 'abschussplan-hgmh'); ?>
                        </a>
                    </div>
                </div>

                <!-- Species Progress Panel -->
                <div class="ahgmh-panel ahgmh-species-progress">
                    <h2 class="panel-title">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php echo esc_html__('Status nach Wildart', 'abschussplan-hgmh'); ?>
                    </h2>
                    <div class="species-progress-content">
                        <?php $this->render_species_progress($stats['species_progress']); ?>
                    </div>
                </div>

                <!-- Recent Activity Panel -->
                <div class="ahgmh-panel ahgmh-recent-activity">
                    <h2 class="panel-title">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html__('Letzte Aktivitäten', 'abschussplan-hgmh'); ?>
                    </h2>
                    <div class="recent-activity-content">
                        <?php $this->render_recent_activity($stats['recent_submissions']); ?>
                    </div>
                </div>

                <!-- Shortcode Reference Panel -->
                <div class="ahgmh-panel ahgmh-shortcodes">
                    <h2 class="panel-title">
                        <span class="dashicons dashicons-editor-code"></span>
                        <?php echo esc_html__('Shortcode Referenz', 'abschussplan-hgmh'); ?>
                    </h2>
                    <div class="shortcodes-content">
                        <?php $this->render_shortcode_reference(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render data management page with tabs
     */
    public function render_data_management() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        ?>
        <div class="wrap ahgmh-admin-modern">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-list-view"></span>
                <?php echo esc_html__('Meldungen verwalten', 'abschussplan-hgmh'); ?>
            </h1>
            
            <!-- Tab Navigation -->
            <nav class="ahgmh-tab-nav" aria-label="Secondary menu">
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-data&tab=overview'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php echo esc_html__('Übersicht', 'abschussplan-hgmh'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-data&tab=submissions'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'submissions' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php echo esc_html__('Alle Meldungen', 'abschussplan-hgmh'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-data&tab=analysis'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'analysis' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php echo esc_html__('Auswertungen', 'abschussplan-hgmh'); ?>
                </a>
            </nav>

            <!-- Tab Content -->
            <div class="ahgmh-tab-content">
                <?php
                switch ($active_tab) {
                    case 'overview':
                        $this->render_data_overview();
                        break;
                    case 'submissions':
                        $this->render_submissions_table();
                        break;
                    case 'analysis':
                        $this->render_data_analysis();
                        break;
                    default:
                        $this->render_data_overview();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page with tabs
     */
    public function render_settings() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'database';
        ?>
        <div class="wrap ahgmh-admin-modern">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Einstellungen', 'abschussplan-hgmh'); ?>
            </h1>
            
            <!-- Tab Navigation -->
            <nav class="ahgmh-tab-nav" aria-label="Settings menu">
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-settings&tab=database'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'database' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-database"></span>
                    <?php echo esc_html__('Datenbank', 'abschussplan-hgmh'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-settings&tab=species'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'species' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html__('Wildarten', 'abschussplan-hgmh'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-settings&tab=categories'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'categories' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-category"></span>
                    <?php echo esc_html__('Kategorien', 'abschussplan-hgmh'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-settings&tab=districts'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'districts' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php echo esc_html__('Jagdbezirke', 'abschussplan-hgmh'); ?>
                </a>
            </nav>

            <!-- Tab Content -->
            <div class="ahgmh-tab-content">
                <?php
                switch ($active_tab) {
                    case 'database':
                        $this->render_database_settings();
                        break;
                    case 'species':
                        $this->render_species_settings();
                        break;
                    case 'categories':
                        $this->render_categories_settings();
                        break;
                    case 'districts':
                        $this->render_districts_settings();
                        break;
                    default:
                        $this->render_database_settings();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        $database = abschussplan_hgmh()->database;
        
        $total_submissions = $database->count_submissions();
        $this_month = $database->count_submissions_this_month();
        $active_users = $database->count_active_users();
        $species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $species_progress = $this->get_species_progress();
        $recent_submissions = $database->get_submissions(5, 0);
        
        return array(
            'total_submissions' => $total_submissions,
            'this_month' => $this_month,
            'active_users' => $active_users,
            'species_count' => count($species),
            'species_progress' => $species_progress,
            'recent_submissions' => $recent_submissions
        );
    }

    /**
     * Get species progress data
     */
    private function get_species_progress() {
        $species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $progress_data = array();
        
        foreach ($species as $species_name) {
            $categories = get_option('ahgmh_categories', array());
            $total_limit = 0;
            $total_current = 0;
            
            foreach ($categories as $category) {
                $limit_key = 'abschuss_category_limits_' . sanitize_key($species_name);
                $limits = get_option($limit_key, array());
                $limit = isset($limits[$category]) ? intval($limits[$category]) : 0;
                
                $current = abschussplan_hgmh()->database->count_submissions_by_species_category($species_name, $category);
                
                $total_limit += $limit;
                $total_current += $current;
            }
            
            $percentage = $total_limit > 0 ? round(($total_current / $total_limit) * 100, 1) : 0;
            
            $progress_data[] = array(
                'species' => $species_name,
                'current' => $total_current,
                'limit' => $total_limit,
                'percentage' => $percentage
            );
        }
        
        return $progress_data;
    }

    /**
     * Render species progress bars
     */
    private function render_species_progress($progress_data) {
        foreach ($progress_data as $data) {
            $status_class = 'low';
            if ($data['percentage'] >= 90) {
                $status_class = 'high';
            } elseif ($data['percentage'] >= 70) {
                $status_class = 'medium';
            }
            ?>
            <div class="species-progress-item">
                <div class="species-info">
                    <span class="species-name"><?php echo esc_html($data['species']); ?></span>
                    <span class="species-numbers"><?php echo esc_html($data['current'] . '/' . $data['limit']); ?></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo esc_attr($status_class); ?>" 
                             style="width: <?php echo esc_attr(min($data['percentage'], 100)); ?>%"></div>
                    </div>
                    <span class="progress-percentage"><?php echo esc_html($data['percentage']); ?>%</span>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Render recent activity
     */
    private function render_recent_activity($recent_submissions) {
        if (empty($recent_submissions)) {
            echo '<p class="no-activity">' . esc_html__('Keine aktuellen Aktivitäten', 'abschussplan-hgmh') . '</p>';
            return;
        }
        
        foreach ($recent_submissions as $submission) {
            $user = get_userdata($submission['user_id']);
            $user_name = $user ? $user->display_name : __('Unbekannter Benutzer', 'abschussplan-hgmh');
            $time_ago = human_time_diff(strtotime($submission['created_at']), current_time('timestamp'));
            ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <span class="dashicons dashicons-plus-alt"></span>
                </div>
                <div class="activity-content">
                    <span class="activity-user"><?php echo esc_html($user_name); ?></span>
                    <span class="activity-action"><?php echo esc_html__('hat eine Meldung hinzugefügt:', 'abschussplan-hgmh'); ?></span>
                    <span class="activity-details"><?php echo esc_html($submission['game_species'] . ' - ' . $submission['field2']); ?></span>
                    <span class="activity-time"><?php echo esc_html(sprintf(__('vor %s', 'abschussplan-hgmh'), $time_ago)); ?></span>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Render shortcode reference
     */
    private function render_shortcode_reference() {
        $shortcodes = array(
            array(
                'code' => '[abschuss_form species="Rotwild"]',
                'description' => __('Zeigt das Abschussformular für eine bestimmte Wildart an', 'abschussplan-hgmh')
            ),
            array(
                'code' => '[abschuss_table limit="10"]',
                'description' => __('Zeigt eine Tabelle mit Abschussmeldungen an', 'abschussplan-hgmh')
            ),
            array(
                'code' => '[abschuss_summary species="Rotwild"]',
                'description' => __('Zeigt eine Zusammenfassung der Abschusszahlen an', 'abschussplan-hgmh')
            ),
            array(
                'code' => '[abschuss_admin]',
                'description' => __('Zeigt die Admin-Konfiguration an (nur für Administratoren)', 'abschussplan-hgmh')
            ),
        );
        
        foreach ($shortcodes as $shortcode) {
            ?>
            <div class="shortcode-item">
                <code class="shortcode-code"><?php echo esc_html($shortcode['code']); ?></code>
                <span class="shortcode-description"><?php echo esc_html($shortcode['description']); ?></span>
            </div>
            <?php
        }
    }

    /**
     * Render data overview tab
     */
    private function render_data_overview() {
        echo '<div class="ahgmh-panel">';
        echo '<p>' . esc_html__('Datenübersicht wird hier angezeigt...', 'abschussplan-hgmh') . '</p>';
        echo '</div>';
    }

    /**
     * Render submissions table tab
     */
    private function render_submissions_table() {
        echo '<div class="ahgmh-panel">';
        echo '<p>' . esc_html__('Meldungstabelle wird hier angezeigt...', 'abschussplan-hgmh') . '</p>';
        echo '</div>';
    }

    /**
     * Render data analysis tab
     */
    private function render_data_analysis() {
        echo '<div class="ahgmh-panel">';
        echo '<p>' . esc_html__('Datenauswertung wird hier angezeigt...', 'abschussplan-hgmh') . '</p>';
        echo '</div>';
    }

    /**
     * Render database settings tab
     */
    private function render_database_settings() {
        echo '<div class="ahgmh-panel">';
        echo '<p>' . esc_html__('Datenbankeinstellungen werden hier angezeigt...', 'abschussplan-hgmh') . '</p>';
        echo '</div>';
    }

    /**
     * Render species settings tab
     */
    private function render_species_settings() {
        echo '<div class="ahgmh-panel">';
        echo '<p>' . esc_html__('Wildarten-Einstellungen werden hier angezeigt...', 'abschussplan-hgmh') . '</p>';
        echo '</div>';
    }

    /**
     * Render categories settings tab
     */
    private function render_categories_settings() {
        echo '<div class="ahgmh-panel">';
        echo '<p>' . esc_html__('Kategorien-Einstellungen werden hier angezeigt...', 'abschussplan-hgmh') . '</p>';
        echo '</div>';
    }

    /**
     * Render districts settings tab
     */
    private function render_districts_settings() {
        echo '<div class="ahgmh-panel">';
        echo '<p>' . esc_html__('Jagdbezirke-Einstellungen werden hier angezeigt...', 'abschussplan-hgmh') . '</p>';
        echo '</div>';
    }

    /**
     * AJAX handler for dashboard stats
     */
    public function ajax_dashboard_stats() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for quick export
     */
    public function ajax_quick_export() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        // Trigger CSV export
        $export_url = admin_url('admin-ajax.php?action=export_abschuss_csv');
        wp_send_json_success(array('export_url' => $export_url));
    }

    /**
     * Add WordPress Dashboard Widget
     */
    public function add_dashboard_widget() {
        // Only show to users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'ahgmh_dashboard_widget',
            __('🦌 Abschussplan HGMH - Übersicht', 'abschussplan-hgmh'),
            array($this, 'render_dashboard_widget'),
            array($this, 'render_dashboard_widget_config')
        );
    }

    /**
     * Render WordPress Dashboard Widget
     */
    public function render_dashboard_widget() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="ahgmh-wp-dashboard-widget">
            <!-- Quick Stats -->
            <div class="ahgmh-wp-stats-grid">
                <div class="ahgmh-wp-stat-item">
                    <span class="dashicons dashicons-list-view ahgmh-wp-stat-icon"></span>
                    <div class="ahgmh-wp-stat-content">
                        <strong><?php echo esc_html($stats['total_submissions']); ?></strong>
                        <span><?php echo esc_html__('Gesamt', 'abschussplan-hgmh'); ?></span>
                    </div>
                </div>
                
                <div class="ahgmh-wp-stat-item">
                    <span class="dashicons dashicons-calendar-alt ahgmh-wp-stat-icon"></span>
                    <div class="ahgmh-wp-stat-content">
                        <strong><?php echo esc_html($stats['this_month']); ?></strong>
                        <span><?php echo esc_html__('Dieser Monat', 'abschussplan-hgmh'); ?></span>
                    </div>
                </div>
                
                <div class="ahgmh-wp-stat-item">
                    <span class="dashicons dashicons-groups ahgmh-wp-stat-icon"></span>
                    <div class="ahgmh-wp-stat-content">
                        <strong><?php echo esc_html($stats['active_users']); ?></strong>
                        <span><?php echo esc_html__('Aktive Jäger', 'abschussplan-hgmh'); ?></span>
                    </div>
                </div>
                
                <div class="ahgmh-wp-stat-item">
                    <span class="dashicons dashicons-location ahgmh-wp-stat-icon"></span>
                    <div class="ahgmh-wp-stat-content">
                        <strong><?php echo esc_html($stats['species_count']); ?></strong>
                        <span><?php echo esc_html__('Wildarten', 'abschussplan-hgmh'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Species Progress Summary -->
            <div class="ahgmh-wp-progress-section">
                <h4 class="ahgmh-wp-section-title">
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php echo esc_html__('Wildarten Status', 'abschussplan-hgmh'); ?>
                </h4>
                
                <div class="ahgmh-wp-progress-list">
                    <?php 
                    $progress_data = array_slice($stats['species_progress'], 0, 3); // Show top 3
                    foreach ($progress_data as $data) {
                        $status_class = 'low';
                        $status_text = __('Gut', 'abschussplan-hgmh');
                        
                        if ($data['percentage'] >= 90) {
                            $status_class = 'high';
                            $status_text = __('Hoch', 'abschussplan-hgmh');
                        } elseif ($data['percentage'] >= 70) {
                            $status_class = 'medium';
                            $status_text = __('Mittel', 'abschussplan-hgmh');
                        }
                        ?>
                        <div class="ahgmh-wp-progress-item">
                            <div class="ahgmh-wp-progress-info">
                                <span class="species-name"><?php echo esc_html($data['species']); ?></span>
                                <span class="species-count"><?php echo esc_html($data['current'] . '/' . $data['limit']); ?></span>
                            </div>
                            <div class="ahgmh-wp-progress-bar">
                                <div class="progress-fill <?php echo esc_attr($status_class); ?>" 
                                     style="width: <?php echo esc_attr(min($data['percentage'], 100)); ?>%"></div>
                            </div>
                            <span class="ahgmh-wp-status-badge status-<?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($data['percentage']); ?>%
                            </span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <?php if (!empty($stats['recent_submissions'])) : ?>
            <div class="ahgmh-wp-activity-section">
                <h4 class="ahgmh-wp-section-title">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo esc_html__('Letzte Aktivitäten', 'abschussplan-hgmh'); ?>
                </h4>
                
                <div class="ahgmh-wp-activity-list">
                    <?php 
                    $recent_items = array_slice($stats['recent_submissions'], 0, 3); // Show 3 most recent
                    foreach ($recent_items as $submission) {
                        $user = get_userdata($submission['user_id']);
                        $user_name = $user ? $user->display_name : __('Unbekannt', 'abschussplan-hgmh');
                        $time_ago = human_time_diff(strtotime($submission['created_at']), current_time('timestamp'));
                        ?>
                        <div class="ahgmh-wp-activity-item">
                            <span class="activity-user"><?php echo esc_html($user_name); ?></span>
                            <span class="activity-details"><?php echo esc_html($submission['game_species'] . ' - ' . $submission['field2']); ?></span>
                            <span class="activity-time"><?php echo esc_html(sprintf(__('vor %s', 'abschussplan-hgmh'), $time_ago)); ?></span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="ahgmh-wp-actions">
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php echo esc_html__('Zum Dashboard', 'abschussplan-hgmh'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-data'); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php echo esc_html__('Alle Meldungen', 'abschussplan-hgmh'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin-ajax.php?action=export_abschuss_csv'); ?>" 
                   class="button" target="_blank">
                    <span class="dashicons dashicons-download"></span>
                    <?php echo esc_html__('CSV Export', 'abschussplan-hgmh'); ?>
                </a>
            </div>
        </div>

        <style>
        .ahgmh-wp-dashboard-widget {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .ahgmh-wp-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .ahgmh-wp-stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
            border-left: 3px solid #2271b1;
        }

        .ahgmh-wp-stat-icon {
            color: #2271b1;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .ahgmh-wp-stat-content strong {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: #1d2327;
            line-height: 1;
        }

        .ahgmh-wp-stat-content span {
            font-size: 11px;
            color: #646970;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ahgmh-wp-section-title {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 15px 0 10px 0;
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            border-bottom: 1px solid #f0f0f1;
            padding-bottom: 5px;
        }

        .ahgmh-wp-section-title .dashicons {
            color: #2271b1;
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        .ahgmh-wp-progress-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f1;
        }

        .ahgmh-wp-progress-item:last-child {
            border-bottom: none;
        }

        .ahgmh-wp-progress-info {
            display: flex;
            flex-direction: column;
            min-width: 80px;
        }

        .ahgmh-wp-progress-info .species-name {
            font-weight: 600;
            font-size: 12px;
            color: #1d2327;
        }

        .ahgmh-wp-progress-info .species-count {
            font-size: 11px;
            color: #646970;
        }

        .ahgmh-wp-progress-bar {
            flex: 1;
            height: 6px;
            background: #f0f0f1;
            border-radius: 3px;
            overflow: hidden;
        }

        .ahgmh-wp-progress-bar .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .ahgmh-wp-progress-bar .progress-fill.low {
            background: #00a32a;
        }

        .ahgmh-wp-progress-bar .progress-fill.medium {
            background: #dba617;
        }

        .ahgmh-wp-progress-bar .progress-fill.high {
            background: #d63638;
        }

        .ahgmh-wp-status-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 35px;
            text-align: center;
        }

        .ahgmh-wp-status-badge.status-low {
            background: #d1e7dd;
            color: #0f5132;
        }

        .ahgmh-wp-status-badge.status-medium {
            background: #fff3cd;
            color: #664d03;
        }

        .ahgmh-wp-status-badge.status-high {
            background: #f8d7da;
            color: #842029;
        }

        .ahgmh-wp-activity-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f1;
        }

        .ahgmh-wp-activity-item:last-child {
            border-bottom: none;
        }

        .ahgmh-wp-activity-item .activity-user {
            font-weight: 600;
            font-size: 12px;
            color: #1d2327;
        }

        .ahgmh-wp-activity-item .activity-details {
            font-size: 11px;
            color: #2271b1;
        }

        .ahgmh-wp-activity-item .activity-time {
            font-size: 10px;
            color: #8c8f94;
        }

        .ahgmh-wp-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f1;
            flex-wrap: wrap;
        }

        .ahgmh-wp-actions .button {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            font-size: 11px;
            height: auto;
            text-decoration: none;
        }

        .ahgmh-wp-actions .button .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }

        /* Responsive adjustments */
        @media (max-width: 782px) {
            .ahgmh-wp-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .ahgmh-wp-actions {
                flex-direction: column;
            }
            
            .ahgmh-wp-actions .button {
                justify-content: center;
            }
        }
        </style>
        <?php
    }

    /**
     * Render dashboard widget configuration (optional)
     */
    public function render_dashboard_widget_config() {
        // Configuration options for the widget
        $show_progress = get_option('ahgmh_dashboard_show_progress', true);
        $show_activity = get_option('ahgmh_dashboard_show_activity', true);
        
        if (isset($_POST['ahgmh_dashboard_config'])) {
            update_option('ahgmh_dashboard_show_progress', isset($_POST['show_progress']));
            update_option('ahgmh_dashboard_show_activity', isset($_POST['show_activity']));
        }
        ?>
        <p>
            <label>
                <input type="checkbox" name="show_progress" <?php checked($show_progress); ?> />
                <?php echo esc_html__('Wildarten-Fortschritt anzeigen', 'abschussplan-hgmh'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="show_activity" <?php checked($show_activity); ?> />
                <?php echo esc_html__('Letzte Aktivitäten anzeigen', 'abschussplan-hgmh'); ?>
            </label>
        </p>
        <input type="hidden" name="ahgmh_dashboard_config" value="1" />
        <?php
    }
}
