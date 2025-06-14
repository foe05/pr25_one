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
        add_action('wp_ajax_ahgmh_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_ahgmh_delete_submission', array($this, 'ajax_delete_submission'));
        add_action('wp_ajax_ahgmh_danger_action', array($this, 'ajax_danger_action'));
        add_action('wp_ajax_ahgmh_add_jagdbezirk', array($this, 'ajax_add_jagdbezirk'));
        add_action('wp_ajax_ahgmh_edit_jagdbezirk', array($this, 'ajax_edit_jagdbezirk'));
        add_action('wp_ajax_ahgmh_delete_jagdbezirk', array($this, 'ajax_delete_jagdbezirk'));
        add_action('wp_ajax_ahgmh_add_species', array($this, 'ajax_add_species'));
        add_action('wp_ajax_ahgmh_delete_species', array($this, 'ajax_delete_species'));
        add_action('wp_ajax_ahgmh_add_category', array($this, 'ajax_add_category'));
        add_action('wp_ajax_ahgmh_edit_category', array($this, 'ajax_edit_category'));
        add_action('wp_ajax_ahgmh_delete_category', array($this, 'ajax_delete_category'));
        add_action('wp_ajax_ahgmh_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_ahgmh_rename_table', array($this, 'ajax_rename_table'));
        add_action('wp_ajax_ahgmh_save_category_settings', array($this, 'ajax_save_category_settings'));
        add_action('wp_ajax_ahgmh_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_save_export_config', array($this, 'ajax_save_export_config'));
        
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
                
                <?php 
                $last_submission = $this->get_last_submission_info();
                if ($last_submission): ?>
                <div class="ahgmh-stat-card highlight">
                    <div class="stat-icon dashicons dashicons-calendar-alt"></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($last_submission['wus_number']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Letzte WUS-Nummer', 'abschussplan-hgmh'); ?></div>
                        <div class="stat-details">
                            <?php echo esc_html($last_submission['species'] . ' - ' . mysql2date('d.m.Y', $last_submission['date'])); ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="ahgmh-stat-card">
                    <div class="stat-icon dashicons dashicons-calendar-alt"></div>
                    <div class="stat-content">
                        <div class="stat-number">-</div>
                        <div class="stat-label"><?php echo esc_html__('Noch keine Meldungen', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
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
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-settings&tab=export'); ?>" 
                   class="ahgmh-tab <?php echo $active_tab === 'export' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-download"></span>
                    <?php echo esc_html__('CSV Export', 'abschussplan-hgmh'); ?>
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
                    case 'export':
                        $this->render_export_settings();
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
        $submissions_this_month = $database->count_submissions_this_month();
        $active_users = $database->count_active_users();
        $species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $species_progress = $this->get_species_progress();
        $recent_submissions = $database->get_submissions(5, 0);
        $category_counts = $database->get_category_counts();
        
        // Get species statistics
        $species_stats = array();
        foreach ($species as $species_name) {
            $species_stats[$species_name] = $database->count_submissions_by_species($species_name);
        }
        
        return array(
            'total_submissions' => $total_submissions,
            'submissions_this_month' => $submissions_this_month,
            'active_users' => $active_users,
            'species_count' => count($species),
            'species_progress' => $species_progress,
            'recent_submissions' => $recent_submissions,
            'category_counts' => $category_counts,
            'species_stats' => $species_stats
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
                'description' => __('Zeigt das Abschussformular für eine bestimmte Wildart an', 'abschussplan-hgmh'),
                'attributes' => __('species: Wildart (Rotwild, Damwild, etc.)', 'abschussplan-hgmh')
            ),
            array(
                'code' => '[abschuss_table limit="10" species="Rotwild"]',
                'description' => __('Zeigt eine Tabelle mit Abschussmeldungen an', 'abschussplan-hgmh'),
                'attributes' => __('limit: Anzahl Einträge, species: Wildart filtern, page: Seitennummer', 'abschussplan-hgmh')
            ),
            array(
                'code' => '[abschuss_summary species="Rotwild"]',
                'description' => __('Zeigt eine Zusammenfassung der Abschusszahlen an', 'abschussplan-hgmh'),
                'attributes' => __('species: Wildart für Zusammenfassung', 'abschussplan-hgmh')
            ),
            array(
                'code' => '[abschuss_limits species="Rotwild"]',
                'description' => __('Zeigt die Limits-Konfiguration für eine Wildart an (nur für Administratoren)', 'abschussplan-hgmh'),
                'attributes' => __('species: Wildart für Limits-Verwaltung', 'abschussplan-hgmh')
            ),
            array(
                'code' => '[abschuss_admin]',
                'description' => __('Zeigt die Admin-Konfiguration an (nur für Administratoren)', 'abschussplan-hgmh'),
                'attributes' => __('Keine Attribute erforderlich', 'abschussplan-hgmh')
            ),
        );
        
        foreach ($shortcodes as $shortcode) {
            ?>
            <div class="shortcode-item">
                <code class="shortcode-code"><?php echo esc_html($shortcode['code']); ?></code>
                <div class="shortcode-info">
                    <span class="shortcode-description"><?php echo esc_html($shortcode['description']); ?></span>
                    <small class="shortcode-attributes"><?php echo esc_html($shortcode['attributes']); ?></small>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Render data overview tab
     */
    private function render_data_overview() {
        $database = abschussplan_hgmh()->database;
        $stats = $this->get_dashboard_stats();
        $last_wus = $this->get_last_wus_info();
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Datenübersicht', 'abschussplan-hgmh'); ?></h2>
            
            <div class="ahgmh-stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['total_submissions']); ?></div>
                    <div class="stat-label"><?php echo esc_html__('Gesamte Meldungen', 'abschussplan-hgmh'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo esc_html($stats['submissions_this_month']); ?></div>
                    <div class="stat-label"><?php echo esc_html__('Meldungen diesen Monat', 'abschussplan-hgmh'); ?></div>
                </div>
                <?php if ($last_wus): ?>
                <div class="stat-card highlight">
                    <div class="stat-number"><?php echo esc_html($last_wus['wus_number']); ?></div>
                    <div class="stat-label"><?php echo esc_html__('Letzte WUS-Nummer', 'abschussplan-hgmh'); ?></div>
                    <div class="stat-details">
                        <?php echo esc_html($last_wus['species']); ?> - 
                        <?php echo esc_html(mysql2date('d.m.Y H:i', $last_wus['submitted_at'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Export Actions -->
            <div class="ahgmh-export-section">
                <h3><?php echo esc_html__('Daten exportieren', 'abschussplan-hgmh'); ?></h3>
                <p><?php echo esc_html__('Exportieren Sie Ihre Daten in verschiedenen Formaten:', 'abschussplan-hgmh'); ?></p>
                
                <div class="export-buttons">
                    <button class="button button-primary ahgmh-export-btn" data-format="csv" data-species="">
                        <span class="dashicons dashicons-download"></span>
                        <?php echo esc_html__('Alle als CSV exportieren', 'abschussplan-hgmh'); ?>
                    </button>
                    <?php foreach ($species_list as $species): ?>
                        <button class="button button-secondary ahgmh-export-btn" data-format="csv" data-species="<?php echo esc_attr($species); ?>">
                            <span class="dashicons dashicons-download"></span>
                            <?php printf(esc_html__('Nur %s als CSV', 'abschussplan-hgmh'), esc_html($species)); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render submissions table tab
     */
    private function render_submissions_table() {
        $database = abschussplan_hgmh()->database;
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        // Get parameters
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;
        $species_filter = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : '';
        
        // Get submissions
        $submissions = $database->get_submissions_by_species($per_page, $offset, $species_filter);
        $total_submissions = $database->count_submissions_by_species($species_filter);
        $total_pages = ceil($total_submissions / $per_page);
        
        ?>
        <div class="ahgmh-panel">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" style="display: inline-block;">
                        <input type="hidden" name="page" value="abschussplan-hgmh-data">
                        <input type="hidden" name="tab" value="submissions">
                        <select name="species" onchange="this.form.submit()">
                            <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                            <?php foreach ($species_list as $species): ?>
                                <option value="<?php echo esc_attr($species); ?>" <?php selected($species_filter, $species); ?>>
                                    <?php echo esc_html($species); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    
                    <button class="button button-secondary ahgmh-export-btn" data-format="csv" data-species="<?php echo esc_attr($species_filter); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <?php echo esc_html__('Aktuelle Auswahl exportieren', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(esc_html__('%s Einträge', 'abschussplan-hgmh'), number_format_i18n($total_submissions)); ?>
                    </span>
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $page,
                        'type' => 'plain'
                    ));
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('ID', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('WUS-Nummer', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Erlegungsort', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Jagdbezirk', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Datum', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
                                <?php echo esc_html__('Keine Meldungen gefunden.', 'abschussplan-hgmh'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo esc_html($submission['id']); ?></td>
                                <td><?php echo esc_html($submission['game_species']); ?></td>
                                <td><?php echo esc_html($submission['field2']); ?></td>
                                <td><?php echo esc_html($submission['field3']); ?></td>
                                <td><?php echo esc_html($submission['field4']); ?></td>
                                <td><?php echo esc_html($submission['field5']); ?></td>
                                <td><?php echo esc_html(mysql2date('d.m.Y H:i', $submission['created_at'])); ?></td>
                                <td>
                                    <button class="button button-small button-link-delete ahgmh-delete-submission" 
                                            data-id="<?php echo esc_attr($submission['id']); ?>"
                                            data-nonce="<?php echo wp_create_nonce('ahgmh_delete_submission'); ?>">
                                        <?php echo esc_html__('Löschen', 'abschussplan-hgmh'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render data analysis tab
     */
    private function render_data_analysis() {
        $database = abschussplan_hgmh()->database;
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Datenauswertung', 'abschussplan-hgmh'); ?></h2>
            
            <!-- Species Statistics -->
            <div class="ahgmh-analysis-section">
                <h3><?php echo esc_html__('Wildarten-Statistiken', 'abschussplan-hgmh'); ?></h3>
                <div class="species-stats">
                    <?php foreach ($stats['species_stats'] as $species => $count): ?>
                        <div class="species-stat-item">
                            <div class="species-name"><?php echo esc_html($species); ?></div>
                            <div class="species-count"><?php echo esc_html($count); ?> <?php echo esc_html__('Meldungen', 'abschussplan-hgmh'); ?></div>
                            <div class="species-bar">
                                <div class="species-bar-fill" style="width: <?php echo esc_attr(($count / max(1, max($stats['species_stats']))) * 100); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Category Analysis -->
            <div class="ahgmh-analysis-section">
                <h3><?php echo esc_html__('Kategorien-Analyse', 'abschussplan-hgmh'); ?></h3>
                <div class="category-grid">
                    <?php foreach ($stats['category_counts'] as $category => $count): ?>
                        <div class="category-card">
                            <div class="category-count"><?php echo esc_html($count); ?></div>
                            <div class="category-name"><?php echo esc_html($category); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="ahgmh-analysis-section">
                <h3><?php echo esc_html__('Monatlicher Trend', 'abschussplan-hgmh'); ?></h3>
                <div class="trend-info">
                    <p><?php printf(
                        esc_html__('In diesem Monat wurden %s Meldungen erfasst.', 'abschussplan-hgmh'),
                        '<strong>' . esc_html($stats['submissions_this_month']) . '</strong>'
                    ); ?></p>
                    <p><?php printf(
                        esc_html__('Insgesamt sind %s Meldungen in der Datenbank gespeichert.', 'abschussplan-hgmh'),
                        '<strong>' . esc_html($stats['total_submissions']) . '</strong>'
                    ); ?></p>
                </div>
            </div>

            <!-- Export for Analysis -->
            <div class="ahgmh-analysis-section">
                <h3><?php echo esc_html__('Daten für externe Analyse exportieren', 'abschussplan-hgmh'); ?></h3>
                <p><?php echo esc_html__('Exportieren Sie Ihre Daten für weitere Analysen in externen Tools:', 'abschussplan-hgmh'); ?></p>
                
                <div class="export-buttons">
                    <button class="button button-primary ahgmh-export-btn" data-format="csv" data-species="">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php echo esc_html__('Vollständiger Datensatz (CSV)', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render database settings tab
     */
    private function render_database_settings() {
        $database = abschussplan_hgmh()->database;
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Datenbank-Verwaltung', 'abschussplan-hgmh'); ?></h2>
            
            <!-- Database Status -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Datenbank-Status', 'abschussplan-hgmh'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Tabellen-Status', 'abschussplan-hgmh'); ?></th>
                        <td>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                            <?php echo esc_html__('Datenbanktabellen sind aktiv', 'abschussplan-hgmh'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Gesamte Datensätze', 'abschussplan-hgmh'); ?></th>
                        <td><?php echo esc_html($stats['total_submissions']); ?> <?php echo esc_html__('Meldungen', 'abschussplan-hgmh'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Aktive Benutzer', 'abschussplan-hgmh'); ?></th>
                        <td><?php echo esc_html($stats['active_users']); ?> <?php echo esc_html__('Benutzer', 'abschussplan-hgmh'); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Database Management Actions -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Datenverwaltung', 'abschussplan-hgmh'); ?></h3>
                <p class="description">
                    <?php echo esc_html__('Achtung: Diese Aktionen können nicht rückgängig gemacht werden. Erstellen Sie vor der Durchführung ein Backup.', 'abschussplan-hgmh'); ?>
                </p>
                
                <div class="ahgmh-danger-zone">
                    <h4><?php echo esc_html__('Gefährliche Aktionen', 'abschussplan-hgmh'); ?></h4>
                    
                    <div class="danger-action">
                        <h5><?php echo esc_html__('Alle Meldungen löschen', 'abschussplan-hgmh'); ?></h5>
                        <p><?php echo esc_html__('Löscht alle Meldungen aus der Datenbank. Jagdbezirke bleiben erhalten.', 'abschussplan-hgmh'); ?></p>
                        <button class="button button-secondary ahgmh-danger-btn" 
                                data-action="delete_all_submissions" 
                                data-confirm="<?php echo esc_attr(__('Sind Sie sicher? Alle Meldungen werden unwiderruflich gelöscht!', 'abschussplan-hgmh')); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php echo esc_html__('Alle Meldungen löschen', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>

                    <div class="danger-action">
                        <h5><?php echo esc_html__('Meldungen nach Wildart löschen', 'abschussplan-hgmh'); ?></h5>
                        <p><?php echo esc_html__('Löscht alle Meldungen einer bestimmten Wildart.', 'abschussplan-hgmh'); ?></p>
                        <div style="margin-bottom: 10px;">
                            <select id="ahgmh-species-select">
                                <option value=""><?php echo esc_html__('Wildart auswählen...', 'abschussplan-hgmh'); ?></option>
                                <?php
                                $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
                                foreach ($species_list as $species):
                                ?>
                                    <option value="<?php echo esc_attr($species); ?>"><?php echo esc_html($species); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="button button-secondary ahgmh-danger-btn" 
                                data-action="delete_species_submissions" 
                                data-confirm="<?php echo esc_attr(__('Sind Sie sicher? Alle Meldungen dieser Wildart werden unwiderruflich gelöscht!', 'abschussplan-hgmh')); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php echo esc_html__('Wildart-Meldungen löschen', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>

                    <div class="danger-action">
                        <h5><?php echo esc_html__('Meldungen nach Datumsbereich löschen', 'abschussplan-hgmh'); ?></h5>
                        <p><?php echo esc_html__('Löscht alle Meldungen zwischen den ausgewählten Daten (inklusive).', 'abschussplan-hgmh'); ?></p>
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                <div>
                                    <label for="ahgmh-date-from" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php echo esc_html__('Von:', 'abschussplan-hgmh'); ?>
                                    </label>
                                    <input type="date" 
                                           id="ahgmh-date-from" 
                                           class="regular-text ahgmh-datepicker"
                                           style="width: 150px;">
                                </div>
                                <div>
                                    <label for="ahgmh-date-to" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php echo esc_html__('Bis:', 'abschussplan-hgmh'); ?>
                                    </label>
                                    <input type="date" 
                                           id="ahgmh-date-to" 
                                           class="regular-text ahgmh-datepicker"
                                           style="width: 150px;">
                                </div>
                            </div>
                            <p class="description" style="margin-top: 8px;">
                                <?php echo esc_html__('Beide Felder müssen ausgefüllt werden. Das "Von"-Datum darf nicht nach dem "Bis"-Datum liegen.', 'abschussplan-hgmh'); ?>
                            </p>
                        </div>
                        <button class="button button-secondary ahgmh-danger-btn" 
                                data-action="delete_daterange_submissions" 
                                data-confirm="<?php echo esc_attr(__('Sind Sie sicher? Alle Meldungen im ausgewählten Datumsbereich werden unwiderruflich gelöscht!', 'abschussplan-hgmh')); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php echo esc_html__('Meldungen im Datumsbereich löschen', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Rename -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Datenbankentabelle umbenennen', 'abschussplan-hgmh'); ?></h3>
                <div class="ahgmh-danger-zone">
                    <p class="description">
                        <strong><?php echo esc_html__('Warnung:', 'abschussplan-hgmh'); ?></strong>
                        <?php echo esc_html__('Das Umbenennen der Datenbanktabelle kann zu Datenverlust führen. Erstellen Sie unbedingt ein Backup vor dieser Aktion!', 'abschussplan-hgmh'); ?>
                    </p>
                    
                    <form id="ahgmh-rename-table-form" class="ahgmh-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="current_table_name"><?php echo esc_html__('Aktuelle Tabelle', 'abschussplan-hgmh'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="current_table_name" value="<?php echo esc_attr($database->get_table_name()); ?>" readonly class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="new_table_name"><?php echo esc_html__('Neuer Tabellenname', 'abschussplan-hgmh'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="new_table_name" name="new_table_name" class="regular-text" placeholder="wp_ahgmh_submissions_new">
                                    <p class="description"><?php echo esc_html__('Neuer Name für die Datenbanktabelle (ohne Prefix)', 'abschussplan-hgmh'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-secondary ahgmh-danger-btn">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php echo esc_html__('Tabelle umbenennen', 'abschussplan-hgmh'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Backup Information -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Backup & Export', 'abschussplan-hgmh'); ?></h3>
                <p><?php echo esc_html__('Erstellen Sie regelmäßig Backups Ihrer Daten:', 'abschussplan-hgmh'); ?></p>
                
                <button class="button button-primary ahgmh-export-btn" data-format="csv" data-species="">
                    <span class="dashicons dashicons-download"></span>
                    <?php echo esc_html__('Vollständiges Backup (CSV)', 'abschussplan-hgmh'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render species settings tab
     */
    private function render_species_settings() {
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Wildarten verwalten', 'abschussplan-hgmh'); ?></h2>
            
            <!-- Add New Species -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Neue Wildart hinzufügen', 'abschussplan-hgmh'); ?></h3>
                <form id="ahgmh-add-species-form" class="ahgmh-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="new_species"><?php echo esc_html__('Wildart Name', 'abschussplan-hgmh'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="new_species" name="species_name" class="regular-text" required>
                                <p class="description"><?php echo esc_html__('Name der Wildart (z.B. "Muffelwild")', 'abschussplan-hgmh'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php echo esc_html__('Wildart hinzufügen', 'abschussplan-hgmh'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <!-- Existing Species -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Bestehende Wildarten', 'abschussplan-hgmh'); ?></h3>
                
                <?php if (empty($species_list)): ?>
                    <p><?php echo esc_html__('Keine Wildarten gefunden. Fügen Sie die erste Wildart hinzu.', 'abschussplan-hgmh'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                                <th scope="col"><?php echo esc_html__('Meldungen', 'abschussplan-hgmh'); ?></th>
                                <th scope="col"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $database = abschussplan_hgmh()->database;
                            foreach ($species_list as $index => $species): 
                                $submission_count = $database->count_submissions_by_species($species);
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($species); ?></strong></td>
                                    <td><?php echo esc_html($submission_count); ?> <?php echo esc_html__('Meldungen', 'abschussplan-hgmh'); ?></td>
                                    <td>
                                        <?php if (count($species_list) > 1): ?>
                                            <button class="button button-small button-link-delete ahgmh-delete-species" 
                                                    data-species="<?php echo esc_attr($species); ?>"
                                                    data-index="<?php echo esc_attr($index); ?>">
                                                <?php echo esc_html__('Entfernen', 'abschussplan-hgmh'); ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="description"><?php echo esc_html__('Mindestens eine Wildart erforderlich', 'abschussplan-hgmh'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
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
        
        // Get last WUS submission for dashboard refresh (use same logic as get_last_wus_info)
        $last_wus = $this->get_last_wus_info();
        
        // Format the submitted_at for AJAX response
        if ($last_wus && isset($last_wus['submitted_at'])) {
            $last_wus['submitted_at'] = mysql2date('d.m.Y H:i', $last_wus['submitted_at']);
        }
        
        $stats['last_wus'] = $last_wus;
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

    /**
     * Get last submitted WUS information
     */
    private function get_last_wus_info() {
        $database = abschussplan_hgmh()->database;
        
        // Get recent submissions and find the first one with a valid WUS number
        $recent_submissions = $database->get_submissions(10, 0); // Get more submissions to find one with WUS
        
        if (!empty($recent_submissions)) {
            foreach ($recent_submissions as $submission) {
                // Only consider submissions with non-empty WUS numbers (must be numeric and > 0)
                if (!empty($submission['field3']) && is_numeric($submission['field3']) && intval($submission['field3']) > 0) {
                    return array(
                        'wus_number' => $submission['field3'],
                        'submitted_at' => $submission['created_at'],
                        'species' => $submission['game_species']
                    );
                }
            }
        }
        
        return null;
    }

    /**
     * Get last submission information for dashboard
     */
    private function get_last_submission_info() {
        $database = abschussplan_hgmh()->database;
        
        // Get most recent submission
        $recent_submissions = $database->get_submissions(1, 0);
        
        if (!empty($recent_submissions)) {
            $last_submission = $recent_submissions[0];
            return array(
                'wus_number' => $last_submission['field3'],
                'date' => $last_submission['field1'], // Abschussdatum
                'species' => $last_submission['game_species']
            );
        }
        
        return null;
    }

    /**
     * Get available years from submissions
     */
    private function get_available_years() {
        global $wpdb;
        $database = abschussplan_hgmh()->database;
        $table_name = $database->get_table_name();
        
        $results = $wpdb->get_results("
            SELECT YEAR(created_at) as year, COUNT(*) as count 
            FROM $table_name 
            GROUP BY YEAR(created_at) 
            ORDER BY year DESC
        ", ARRAY_A);
        
        return $results ?: array();
    }

    /**
     * Render categories settings tab
     */
    private function render_categories_settings() {
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $active_species = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : $species_list[0];
        
        // Get categories for selected species
        $categories_key = 'ahgmh_categories_' . sanitize_key($active_species);
        $categories = get_option($categories_key, array()); // Start with empty categories for new species
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Kategorien verwalten', 'abschussplan-hgmh'); ?></h2>
            
            <!-- Species Selection -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Wildart auswählen', 'abschussplan-hgmh'); ?></h3>
                <form method="get">
                    <input type="hidden" name="page" value="abschussplan-hgmh-settings">
                    <input type="hidden" name="tab" value="categories">
                    <select name="species" onchange="this.form.submit()">
                        <?php foreach ($species_list as $species): ?>
                            <option value="<?php echo esc_attr($species); ?>" <?php selected($active_species, $species); ?>>
                                <?php echo esc_html($species); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Add New Category -->
            <div class="ahgmh-settings-section">
                <h3><?php printf(esc_html__('Neue Kategorie für %s hinzufügen', 'abschussplan-hgmh'), esc_html($active_species)); ?></h3>
                <form id="ahgmh-add-category-form" class="ahgmh-form">
                    <input type="hidden" name="species" value="<?php echo esc_attr($active_species); ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="new_category"><?php echo esc_html__('Kategorie Name', 'abschussplan-hgmh'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="new_category" name="category_name" class="regular-text" required>
                                <p class="description"><?php echo esc_html__('Name der Kategorie (z.B. "Alttier (AK 2)")', 'abschussplan-hgmh'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php echo esc_html__('Kategorie hinzufügen', 'abschussplan-hgmh'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <!-- Existing Categories -->
            <div class="ahgmh-settings-section">
                <h3><?php printf(esc_html__('Kategorien für %s', 'abschussplan-hgmh'), esc_html($active_species)); ?></h3>
                
                <?php if (empty($categories)): ?>
                    <p><?php echo esc_html__('Keine Kategorien gefunden. Fügen Sie die erste Kategorie hinzu.', 'abschussplan-hgmh'); ?></p>
                <?php else: ?>
                    <form id="ahgmh-category-settings-form" method="post">
                        <?php wp_nonce_field('ahgmh_category_settings', 'ahgmh_category_settings_nonce'); ?>
                        <input type="hidden" name="species" value="<?php echo esc_attr($active_species); ?>">
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Meldungen', 'abschussplan-hgmh'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Abschuss Soll', 'abschussplan-hgmh'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Überschießen möglich', 'abschussplan-hgmh'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $database = abschussplan_hgmh()->database;
                                $exceeding_option_key = 'abschuss_category_allow_exceeding_' . sanitize_key($active_species);
                                $allow_exceeding = get_option($exceeding_option_key, array());
                                $limits_option_key = 'abschuss_category_limits_' . sanitize_key($active_species);
                                $category_limits = get_option($limits_option_key, array());
                                
                                foreach ($categories as $index => $category): 
                                    $submission_count = $database->count_submissions_by_species_category($active_species, $category);
                                    $exceeding_allowed = isset($allow_exceeding[$category]) ? $allow_exceeding[$category] : false;
                                    $limit_value = isset($category_limits[$category]) ? intval($category_limits[$category]) : 0;
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($category); ?></strong></td>
                                        <td><?php echo esc_html($submission_count); ?> <?php echo esc_html__('Meldungen', 'abschussplan-hgmh'); ?></td>
                                        <td>
                                            <input type="number" 
                                                   name="category_limits[<?php echo esc_attr($category); ?>]" 
                                                   value="<?php echo esc_attr($limit_value); ?>"
                                                   min="0" 
                                                   class="small-text ahgmh-limit-input"
                                                   data-category="<?php echo esc_attr($category); ?>"
                                                   style="width: 80px;">
                                        </td>
                                        <td>
                                            <label class="ahgmh-switch">
                                                <input type="checkbox" 
                                                       name="allow_exceeding[<?php echo esc_attr($category); ?>]" 
                                                       value="1" 
                                                       <?php checked($exceeding_allowed, true); ?>
                                                       class="ahgmh-exceeding-checkbox"
                                                       data-category="<?php echo esc_attr($category); ?>">
                                                <span class="ahgmh-slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small ahgmh-edit-category" 
                                                    data-species="<?php echo esc_attr($active_species); ?>"
                                                    data-category="<?php echo esc_attr($category); ?>"
                                                    data-index="<?php echo esc_attr($index); ?>">
                                                <?php echo esc_html__('Bearbeiten', 'abschussplan-hgmh'); ?>
                                            </button>
                                            <button type="button" class="button button-small button-link-delete ahgmh-delete-category" 
                                                    data-species="<?php echo esc_attr($active_species); ?>"
                                                    data-category="<?php echo esc_attr($category); ?>"
                                                    data-index="<?php echo esc_attr($index); ?>">
                                                <?php echo esc_html__('Löschen', 'abschussplan-hgmh'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Einstellungen speichern', 'abschussplan-hgmh'); ?>
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get default categories for a species
     */
    private function get_default_categories($species) {
        $defaults = array(
            'Rotwild' => array(
                'Wildkalb (AK0)',
                'Schmaltier (AK 1)', 
                'Alttier (AK 2)',
                'Hirschkalb (AK 0)',
                'Schmalspießer (AK1)',
                'Junger Hirsch (AK 2)',
                'Mittelalter Hirsch (AK 3)',
                'Alter Hirsch (AK 4)'
            ),
            'Damwild' => array(
                'Wildkalb (AK0)',
                'Schmaltier (AK 1)', 
                'Alttier (AK 2)',
                'Hirschkalb (AK 0)',
                'Schmalspießer (AK1)',
                'Junger Hirsch (AK 2)',
                'Mittelalter Hirsch (AK 3)',
                'Alter Hirsch (AK 4)'
            )
        );
        
        return isset($defaults[$species]) ? $defaults[$species] : array();
    }

    /**
     * Render districts (Jagdbezirke) settings tab
     */
    private function render_districts_settings() {
        $database = abschussplan_hgmh()->database;
        $jagdbezirke = $database->get_active_jagdbezirke();
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Jagdbezirke verwalten', 'abschussplan-hgmh'); ?></h2>
            
            <!-- Add New Jagdbezirk -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Neuen Jagdbezirk hinzufügen', 'abschussplan-hgmh'); ?></h3>
                <form id="ahgmh-add-jagdbezirk-form" class="ahgmh-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="new_jagdbezirk"><?php echo esc_html__('Jagdbezirk Name', 'abschussplan-hgmh'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="new_jagdbezirk" name="jagdbezirk" class="regular-text" required>
                                <p class="description"><?php echo esc_html__('Name des Jagdbezirks (z.B. "Jagdbezirk Nordwest")', 'abschussplan-hgmh'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="new_meldegruppe"><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="new_meldegruppe" name="meldegruppe" class="regular-text" placeholder="Gruppe A" required>
                                <p class="description"><?php echo esc_html__('Meldegruppe für diesen Jagdbezirk (Pflichtfeld)', 'abschussplan-hgmh'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="new_bemerkung"><?php echo esc_html__('Bemerkung', 'abschussplan-hgmh'); ?></label>
                            </th>
                            <td>
                                <textarea id="new_bemerkung" name="bemerkung" rows="3" class="large-text"></textarea>
                                <p class="description"><?php echo esc_html__('Optionale Bemerkungen zum Jagdbezirk', 'abschussplan-hgmh'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php echo esc_html__('Jagdbezirk hinzufügen', 'abschussplan-hgmh'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <!-- Existing Jagdbezirke -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Bestehende Jagdbezirke', 'abschussplan-hgmh'); ?></h3>
                
                <?php if (empty($jagdbezirke)): ?>
                    <p><?php echo esc_html__('Keine Jagdbezirke gefunden. Fügen Sie den ersten Jagdbezirk hinzu.', 'abschussplan-hgmh'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo esc_html__('Jagdbezirk', 'abschussplan-hgmh'); ?></th>
                                <th scope="col"><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></th>
                                <th scope="col"><?php echo esc_html__('Bemerkung', 'abschussplan-hgmh'); ?></th>
                                <th scope="col"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jagdbezirke as $jagdbezirk): ?>
                                <tr>
                                    <td><?php echo esc_html($jagdbezirk['jagdbezirk']); ?></td>
                                    <td><?php echo esc_html($jagdbezirk['meldegruppe']); ?></td>
                                    <td><?php echo esc_html($jagdbezirk['bemerkung']); ?></td>
                                    <td>
                                        <button class="button button-small ahgmh-edit-jagdbezirk" 
                                                data-id="<?php echo esc_attr($jagdbezirk['id']); ?>">
                                            <?php echo esc_html__('Bearbeiten', 'abschussplan-hgmh'); ?>
                                        </button>
                                        <button class="button button-small button-link-delete ahgmh-delete-jagdbezirk" 
                                                data-id="<?php echo esc_attr($jagdbezirk['id']); ?>"
                                                data-name="<?php echo esc_attr($jagdbezirk['jagdbezirk']); ?>">
                                            <?php echo esc_html__('Löschen', 'abschussplan-hgmh'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render export settings tab
     */
    private function render_export_settings() {
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $database = abschussplan_hgmh()->database;
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('CSV Export & Download', 'abschussplan-hgmh'); ?></h2>
            
            <!-- Export Statistics -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Export-Statistiken', 'abschussplan-hgmh'); ?></h3>
                <div class="ahgmh-stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo esc_html($stats['total_submissions']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Verfügbare Meldungen', 'abschussplan-hgmh'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo esc_html($stats['submissions_this_month']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Neue Meldungen (diesen Monat)', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Export by Species -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Export nach Wildart', 'abschussplan-hgmh'); ?></h3>
                <p><?php echo esc_html__('Klicken Sie auf einen der folgenden Buttons, um Daten für eine bestimmte Wildart zu exportieren:', 'abschussplan-hgmh'); ?></p>
                
                <div class="export-buttons">
                    <?php foreach ($species_list as $species): 
                        $species_count = $database->count_submissions_by_species($species);
                    ?>
                        <div class="export-species-item">
                            <button class="button button-primary ahgmh-export-btn" 
                                    data-format="csv" 
                                    data-species="<?php echo esc_attr($species); ?>">
                                <span class="dashicons dashicons-download"></span>
                                <?php printf(esc_html__('%s exportieren (%d Meldungen)', 'abschussplan-hgmh'), 
                                           esc_html($species), 
                                           esc_html($species_count)); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Complete Export -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Vollständiger Export', 'abschussplan-hgmh'); ?></h3>
                <p><?php echo esc_html__('Exportieren Sie alle Meldungen in einer einzigen CSV-Datei:', 'abschussplan-hgmh'); ?></p>
                
                <button class="button button-primary button-hero ahgmh-export-btn" 
                        data-format="csv" 
                        data-species="">
                    <span class="dashicons dashicons-download"></span>
                    <?php printf(esc_html__('Alle Meldungen exportieren (%d Einträge)', 'abschussplan-hgmh'), 
                               esc_html($stats['total_submissions'])); ?>
                </button>
            </div>

            <!-- Export Configuration -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Export-Konfiguration', 'abschussplan-hgmh'); ?></h3>
                <div class="ahgmh-form-row">
                    <label for="export_filename_pattern"><?php echo esc_html__('Dateiname-Muster:', 'abschussplan-hgmh'); ?></label>
                    <input type="text" 
                           id="export_filename_pattern" 
                           name="export_filename_pattern" 
                           value="<?php echo esc_attr(get_option('ahgmh_export_filename_pattern', 'abschussplan_{species}_{date}')); ?>"
                           placeholder="abschussplan_{species}_{date}"
                           class="regular-text">
                    <p class="description">
                        <?php echo esc_html__('Verfügbare Platzhalter: {species} (Wildart), {date} (YYYY-MM-DD), {datetime} (YYYY-MM-DD_HH-MM)', 'abschussplan-hgmh'); ?>
                    </p>
                </div>
                
                <div class="ahgmh-form-row">
                    <label>
                        <input type="checkbox" 
                               id="export_include_time" 
                               name="export_include_time" 
                               <?php checked(get_option('ahgmh_export_include_time', false)); ?>>
                        <?php echo esc_html__('Uhrzeit in Dateinamen einschließen', 'abschussplan-hgmh'); ?>
                    </label>
                </div>
                
                <button type="button" class="button" id="save_export_config">
                    <?php echo esc_html__('Export-Einstellungen speichern', 'abschussplan-hgmh'); ?>
                </button>
            </div>

            <!-- Export Parameters -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Export-Parameter & API-Zugriff', 'abschussplan-hgmh'); ?></h3>
                <div class="ahgmh-info-box">
                    <h4><?php echo esc_html__('Export-URL:', 'abschussplan-hgmh'); ?></h4>
                    <code><?php echo esc_html(admin_url('admin-ajax.php?action=export_abschuss_csv')); ?></code>
                    
                    <h4><?php echo esc_html__('Verfügbare Parameter:', 'abschussplan-hgmh'); ?></h4>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Parameter', 'abschussplan-hgmh'); ?></th>
                                <th><?php echo esc_html__('Beschreibung', 'abschussplan-hgmh'); ?></th>
                                <th><?php echo esc_html__('Beispiel', 'abschussplan-hgmh'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>species</code></td>
                                <td><?php echo esc_html__('Filtert nach Wildart', 'abschussplan-hgmh'); ?></td>
                                <td><code>species=Rotwild</code></td>
                            </tr>
                            <tr>
                                <td><code>from</code></td>
                                <td><?php echo esc_html__('Startdatum (YYYY-MM-DD)', 'abschussplan-hgmh'); ?></td>
                                <td><code>from=2024-01-01</code></td>
                            </tr>
                            <tr>
                                <td><code>to</code></td>
                                <td><?php echo esc_html__('Enddatum (YYYY-MM-DD)', 'abschussplan-hgmh'); ?></td>
                                <td><code>to=2024-12-31</code></td>
                            </tr>
                            <tr>
                                <td><code>filename</code></td>
                                <td><?php echo esc_html__('Eigener Dateiname (ohne .csv)', 'abschussplan-hgmh'); ?></td>
                                <td><code>filename=export_2024</code></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4><?php echo esc_html__('Beispiel-URLs:', 'abschussplan-hgmh'); ?></h4>
                    <ul>
                        <li><code><?php echo esc_html(admin_url('admin-ajax.php?action=export_abschuss_csv&species=Rotwild')); ?></code></li>
                        <li><code><?php echo esc_html(admin_url('admin-ajax.php?action=export_abschuss_csv&from=2024-01-01&to=2024-12-31')); ?></code></li>
                        <li><code><?php echo esc_html(admin_url('admin-ajax.php?action=export_abschuss_csv&species=Damwild&filename=damwild_export')); ?></code></li>
                    </ul>
                </div>
            </div>

            <!-- Export Information -->
            <div class="ahgmh-settings-section">
                <h3><?php echo esc_html__('Export-Informationen', 'abschussplan-hgmh'); ?></h3>
                <div class="ahgmh-info-box">
                    <h4><?php echo esc_html__('CSV-Datei enthält folgende Spalten:', 'abschussplan-hgmh'); ?></h4>
                    <ul>
                        <li><strong><?php echo esc_html__('ID:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Eindeutige Meldungs-ID', 'abschussplan-hgmh'); ?></li>
                        <li><strong><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Art des erlegten Wildes', 'abschussplan-hgmh'); ?></li>
                        <li><strong><?php echo esc_html__('Kategorie:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Altersklasse/Geschlecht', 'abschussplan-hgmh'); ?></li>
                        <li><strong><?php echo esc_html__('WUS-Nummer:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Wildursprungsschein-Nummer', 'abschussplan-hgmh'); ?></li>
                        <li><strong><?php echo esc_html__('Erlegungsort:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Ort der Erlegung', 'abschussplan-hgmh'); ?></li>
                        <li><strong><?php echo esc_html__('Jagdbezirk:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Zuständiger Jagdbezirk', 'abschussplan-hgmh'); ?></li>
                        <li><strong><?php echo esc_html__('Datum:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Abschussdatum und Uhrzeit', 'abschussplan-hgmh'); ?></li>
                    </ul>
                    
                    <h4><?php echo esc_html__('Technische Details:', 'abschussplan-hgmh'); ?></h4>
                    <ul>
                        <li><?php echo esc_html__('Format: CSV (Comma-Separated Values)', 'abschussplan-hgmh'); ?></li>
                        <li><?php echo esc_html__('Zeichenkodierung: UTF-8', 'abschussplan-hgmh'); ?></li>
                        <li><?php echo esc_html__('Trennzeichen: Komma (,)', 'abschussplan-hgmh'); ?></li>
                        <li><?php echo esc_html__('Textbegrenzung: Anführungszeichen (")', 'abschussplan-hgmh'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    // ========================================
    // AJAX HANDLERS
    // ========================================

    /**
     * AJAX: Export data with filters
     */
    public function ajax_export_data() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $species = sanitize_text_field($_POST['species'] ?? '');
        
        $database = abschussplan_hgmh()->database;
        $submissions = $database->get_submissions_by_species(0, 0, $species);
        
        if (empty($submissions)) {
            wp_send_json_error(__('Keine Daten zum Exportieren gefunden', 'abschussplan-hgmh'));
        }
        
        // Generate CSV content
        $csv_content = $this->generate_csv_content($submissions);
        
        // Create filename
        $timestamp = date('Y-m-d_H-i-s');
        $species_suffix = $species ? '_' . sanitize_file_name($species) : '';
        $filename = "abschuss_export_{$timestamp}{$species_suffix}.csv";
        
        // Save to uploads directory
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($file_path, $csv_content) === false) {
            wp_send_json_error(__('Fehler beim Erstellen der Export-Datei', 'abschussplan-hgmh'));
        }
        
        $download_url = $upload_dir['url'] . '/' . $filename;
        
        wp_send_json_success(array(
            'download_url' => $download_url,
            'filename' => $filename
        ));
    }

    /**
     * AJAX: Delete submission
     */
    public function ajax_delete_submission() {
        check_ajax_referer('ahgmh_delete_submission', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            wp_send_json_error(__('Ungültige Meldungs-ID', 'abschussplan-hgmh'));
        }
        
        $database = abschussplan_hgmh()->database;
        
        if ($database->delete_submission($id)) {
            wp_send_json_success(__('Meldung erfolgreich gelöscht', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Löschen der Meldung', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Danger actions (bulk delete operations)
     */
    public function ajax_danger_action() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $action = sanitize_text_field($_POST['danger_action'] ?? '');
        $database = abschussplan_hgmh()->database;
        
        switch ($action) {
            case 'delete_all_submissions':
                if ($database->delete_all_submissions()) {
                    wp_send_json_success(__('Alle Meldungen wurden gelöscht', 'abschussplan-hgmh'));
                } else {
                    wp_send_json_error(__('Fehler beim Löschen aller Meldungen', 'abschussplan-hgmh'));
                }
                break;
                
            case 'delete_species_submissions':
                $species = sanitize_text_field($_POST['species'] ?? '');
                if (empty($species)) {
                    wp_send_json_error(__('Keine Wildart ausgewählt', 'abschussplan-hgmh'));
                }
                
                if ($database->delete_submissions_by_species($species)) {
                    wp_send_json_success(sprintf(__('Alle %s-Meldungen wurden gelöscht', 'abschussplan-hgmh'), $species));
                } else {
                    wp_send_json_error(__('Fehler beim Löschen der Wildart-Meldungen', 'abschussplan-hgmh'));
                }
                break;
                
            case 'delete_daterange_submissions':
                $date_from = sanitize_text_field($_POST['date_from'] ?? '');
                $date_to = sanitize_text_field($_POST['date_to'] ?? '');
                
                if (empty($date_from) || empty($date_to)) {
                    wp_send_json_error(__('Beide Datumsfelder sind erforderlich', 'abschussplan-hgmh'));
                }
                
                // Validate date format and range
                $from_timestamp = strtotime($date_from);
                $to_timestamp = strtotime($date_to);
                
                if ($from_timestamp === false || $to_timestamp === false) {
                    wp_send_json_error(__('Ungültige Datumsformate', 'abschussplan-hgmh'));
                }
                
                if ($from_timestamp > $to_timestamp) {
                    wp_send_json_error(__('Das "Von"-Datum darf nicht nach dem "Bis"-Datum liegen', 'abschussplan-hgmh'));
                }
                
                if ($this->delete_submissions_by_daterange($date_from, $date_to)) {
                    wp_send_json_success(sprintf(__('Alle Meldungen zwischen %s und %s wurden gelöscht', 'abschussplan-hgmh'), $date_from, $date_to));
                } else {
                    wp_send_json_error(__('Fehler beim Löschen der Meldungen im Datumsbereich', 'abschussplan-hgmh'));
                }
                break;
                
            default:
                wp_send_json_error(__('Unbekannte Aktion', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Add Jagdbezirk
     */
    public function ajax_add_jagdbezirk() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $data = array(
            'jagdbezirk' => sanitize_text_field($_POST['jagdbezirk'] ?? ''),
            'meldegruppe' => sanitize_text_field($_POST['meldegruppe'] ?? ''),
            'bemerkung' => sanitize_textarea_field($_POST['bemerkung'] ?? ''),
            'ungueltig' => 0
        );
        
        if (empty($data['jagdbezirk'])) {
            wp_send_json_error(__('Jagdbezirk Name ist erforderlich', 'abschussplan-hgmh'));
        }
        
        if (empty($data['meldegruppe'])) {
            wp_send_json_error(__('Meldegruppe ist erforderlich', 'abschussplan-hgmh'));
        }
        
        $database = abschussplan_hgmh()->database;
        
        if ($database->insert_jagdbezirk($data)) {
            wp_send_json_success(__('Jagdbezirk erfolgreich hinzugefügt', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Hinzufügen des Jagdbezirks', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Edit Jagdbezirk
     */
    public function ajax_edit_jagdbezirk() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $id = intval($_POST['id'] ?? 0);
        $data = array(
            'jagdbezirk' => sanitize_text_field($_POST['jagdbezirk'] ?? ''),
            'meldegruppe' => sanitize_text_field($_POST['meldegruppe'] ?? ''),
            'bemerkung' => sanitize_textarea_field($_POST['bemerkung'] ?? '')
        );
        
        if ($id <= 0) {
            wp_send_json_error(__('Ungültige Jagdbezirk-ID', 'abschussplan-hgmh'));
        }
        
        if (empty($data['jagdbezirk'])) {
            wp_send_json_error(__('Jagdbezirk Name ist erforderlich', 'abschussplan-hgmh'));
        }
        
        if (empty($data['meldegruppe'])) {
            wp_send_json_error(__('Meldegruppe ist erforderlich', 'abschussplan-hgmh'));
        }
        
        $database = abschussplan_hgmh()->database;
        
        if ($database->update_jagdbezirk($id, $data)) {
            wp_send_json_success(__('Jagdbezirk erfolgreich aktualisiert', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Aktualisieren des Jagdbezirks', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Delete Jagdbezirk
     */
    public function ajax_delete_jagdbezirk() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            wp_send_json_error(__('Ungültige Jagdbezirk-ID', 'abschussplan-hgmh'));
        }
        
        $database = abschussplan_hgmh()->database;
        
        if ($database->delete_jagdbezirk($id)) {
            wp_send_json_success(__('Jagdbezirk erfolgreich gelöscht', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Löschen des Jagdbezirks', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Add Species
     */
    public function ajax_add_species() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $species_name = sanitize_text_field($_POST['species_name'] ?? '');
        
        if (empty($species_name)) {
            wp_send_json_error(__('Wildart Name ist erforderlich', 'abschussplan-hgmh'));
        }
        
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        if (in_array($species_name, $species_list)) {
            wp_send_json_error(__('Diese Wildart existiert bereits', 'abschussplan-hgmh'));
        }
        
        $species_list[] = $species_name;
        
        if (update_option('ahgmh_species', $species_list)) {
            // Initialize empty categories for new species
            $categories_key = 'ahgmh_categories_' . sanitize_key($species_name);
            update_option($categories_key, array()); // Start with empty categories
            
            wp_send_json_success(__('Wildart erfolgreich hinzugefügt', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Hinzufügen der Wildart', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Delete Species
     */
    public function ajax_delete_species() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $species = sanitize_text_field($_POST['species'] ?? '');
        $index = intval($_POST['index'] ?? -1);
        
        if (empty($species) || $index < 0) {
            wp_send_json_error(__('Ungültige Wildart', 'abschussplan-hgmh'));
        }
        
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        if (count($species_list) <= 1) {
            wp_send_json_error(__('Mindestens eine Wildart muss erhalten bleiben', 'abschussplan-hgmh'));
        }
        
        if (isset($species_list[$index]) && $species_list[$index] === $species) {
            unset($species_list[$index]);
            $species_list = array_values($species_list); // Re-index array
            
            if (update_option('ahgmh_species', $species_list)) {
                // Clean up categories for removed species
                $categories_key = 'ahgmh_categories_' . sanitize_key($species);
                delete_option($categories_key);
                
                wp_send_json_success(__('Wildart erfolgreich entfernt', 'abschussplan-hgmh'));
            } else {
                wp_send_json_error(__('Fehler beim Entfernen der Wildart', 'abschussplan-hgmh'));
            }
        } else {
            wp_send_json_error(__('Wildart nicht gefunden', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Add Category
     */
    public function ajax_add_category() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $species = sanitize_text_field($_POST['species'] ?? '');
        $category_name = sanitize_text_field($_POST['category_name'] ?? '');
        
        if (empty($species) || empty($category_name)) {
            wp_send_json_error(__('Wildart und Kategorie Name sind erforderlich', 'abschussplan-hgmh'));
        }
        
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        if (in_array($category_name, $categories)) {
            wp_send_json_error(__('Diese Kategorie existiert bereits für diese Wildart', 'abschussplan-hgmh'));
        }
        
        $categories[] = $category_name;
        
        if (update_option($categories_key, $categories)) {
            wp_send_json_success(__('Kategorie erfolgreich hinzugefügt', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Hinzufügen der Kategorie', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Edit Category
     */
    public function ajax_edit_category() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $species = sanitize_text_field($_POST['species'] ?? '');
        $old_category = sanitize_text_field($_POST['old_category'] ?? '');
        $new_category = sanitize_text_field($_POST['new_category'] ?? '');
        $index = intval($_POST['index'] ?? -1);
        
        if (empty($species) || empty($old_category) || empty($new_category) || $index < 0) {
            wp_send_json_error(__('Alle Felder sind erforderlich', 'abschussplan-hgmh'));
        }
        
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        // Check if old category exists at the specified index
        if (!isset($categories[$index]) || $categories[$index] !== $old_category) {
            wp_send_json_error(__('Kategorie nicht gefunden oder Index ungültig', 'abschussplan-hgmh'));
        }
        
        // Check if new category name already exists (but not at current index)
        foreach ($categories as $i => $cat) {
            if ($i !== $index && $cat === $new_category) {
                wp_send_json_error(__('Eine Kategorie mit diesem Namen existiert bereits', 'abschussplan-hgmh'));
            }
        }
        
        // Update the category
        $categories[$index] = $new_category;
        
        // Update categories in database
        if (update_option($categories_key, $categories)) {
            // Also update any existing exceeding settings for this category
            $exceeding_option_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
            $exceeding_settings = get_option($exceeding_option_key, array());
            
            if (isset($exceeding_settings[$old_category])) {
                $exceeding_settings[$new_category] = $exceeding_settings[$old_category];
                unset($exceeding_settings[$old_category]);
                update_option($exceeding_option_key, $exceeding_settings);
            }
            
            wp_send_json_success(__('Kategorie erfolgreich aktualisiert', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Aktualisieren der Kategorie', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Delete Category
     */
    public function ajax_delete_category() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $species = sanitize_text_field($_POST['species'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $index = intval($_POST['index'] ?? -1);
        
        if (empty($species) || empty($category) || $index < 0) {
            wp_send_json_error(__('Ungültige Parameter', 'abschussplan-hgmh'));
        }
        
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        if (isset($categories[$index]) && $categories[$index] === $category) {
            unset($categories[$index]);
            $categories = array_values($categories); // Re-index array
            
            if (update_option($categories_key, $categories)) {
                wp_send_json_success(__('Kategorie erfolgreich gelöscht', 'abschussplan-hgmh'));
            } else {
                wp_send_json_error(__('Fehler beim Löschen der Kategorie', 'abschussplan-hgmh'));
            }
        } else {
            wp_send_json_error(__('Kategorie nicht gefunden', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Save Export Settings
     */
    public function ajax_export_settings() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $settings = array(
            'filename_pattern' => sanitize_text_field($_POST['filename_pattern'] ?? 'abschuss_export_%Y%m%d'),
            'base_url' => esc_url_raw($_POST['base_url'] ?? home_url()),
            'api_endpoint' => sanitize_text_field($_POST['api_endpoint'] ?? '/wp-json/ahgmh/v1/export'),
            'download_url_pattern' => sanitize_text_field($_POST['download_url_pattern'] ?? '/wp-admin/admin-ajax.php?action=ahgmh_download_export&file=%s&nonce=%s')
        );
        
        if (update_option('ahgmh_export_settings', $settings)) {
            wp_send_json_success(__('Export-Einstellungen erfolgreich gespeichert', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Speichern der Export-Einstellungen', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX: Rename Database Table
     */
    public function ajax_rename_table() {
        check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        global $wpdb;
        
        $new_table_name = sanitize_text_field($_POST['new_table_name'] ?? '');
        
        if (empty($new_table_name)) {
            wp_send_json_error(__('Neuer Tabellenname ist erforderlich', 'abschussplan-hgmh'));
        }
        
        $database = abschussplan_hgmh()->database;
        $current_table = $database->get_table_name();
        $new_table = $wpdb->prefix . sanitize_key($new_table_name);
        
        // Check if new table name already exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_table'");
        if ($table_exists) {
            wp_send_json_error(__('Eine Tabelle mit diesem Namen existiert bereits', 'abschussplan-hgmh'));
        }
        
        // Rename table
        $result = $wpdb->query("RENAME TABLE `$current_table` TO `$new_table`");
        
        if ($result === false) {
            wp_send_json_error(__('Fehler beim Umbenennen der Tabelle', 'abschussplan-hgmh'));
        }
        
        wp_send_json_success(__('Tabelle erfolgreich umbenannt', 'abschussplan-hgmh'));
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Generate CSV content from submissions
     */
    private function generate_csv_content($submissions) {
        $csv_lines = array();
        
        // CSV Header
        $csv_lines[] = '"ID","Wildart","Kategorie","WUS-Nummer","Erlegungsort","Jagdbezirk","Datum"';
        
        // CSV Data
        foreach ($submissions as $submission) {
            $line = array(
                $submission['id'],
                $submission['game_species'],
                $submission['field2'],
                $submission['field3'],
                $submission['field4'],
                $submission['field5'],
                mysql2date('d.m.Y H:i', $submission['created_at'])
            );
            
            // Escape and quote CSV fields
            $escaped_line = array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $line);
            
            $csv_lines[] = implode(',', $escaped_line);
        }
        
        return implode("\n", $csv_lines);
    }

    /**
     * Delete submissions by date range
     */
    private function delete_submissions_by_daterange($date_from, $date_to) {
        global $wpdb;
        $database = abschussplan_hgmh()->database;
        $table_name = $database->get_table_name();
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE DATE(created_at) BETWEEN %s AND %s",
            $date_from,
            $date_to
        ));
        
        return $result !== false;
    }

    /**
     * AJAX: Save Category Settings (including allow exceeding)
     */
    public function ajax_save_category_settings() {
        // Use the form nonce if available, otherwise the admin nonce
        if (isset($_POST['ahgmh_category_settings_nonce'])) {
            check_ajax_referer('ahgmh_category_settings', 'ahgmh_category_settings_nonce');
        } else {
            check_ajax_referer('ahgmh_admin_nonce', 'nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'abschussplan-hgmh'));
        }
        
        $species = sanitize_text_field($_POST['species'] ?? '');
        $allow_exceeding = isset($_POST['allow_exceeding']) ? (array) $_POST['allow_exceeding'] : array();
        $category_limits = isset($_POST['category_limits']) ? (array) $_POST['category_limits'] : array();
        
        if (empty($species)) {
            wp_send_json_error(__('Wildart ist erforderlich', 'abschussplan-hgmh'));
        }
        
        // Save allow exceeding settings
        $exceeding_option_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        $limits_option_key = 'abschuss_category_limits_' . sanitize_key($species);
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        // Build the settings arrays
        $exceeding_settings = array();
        $limits_settings = array();
        foreach ($categories as $category) {
            $exceeding_settings[$category] = isset($allow_exceeding[$category]) && $allow_exceeding[$category] == '1';
            $limits_settings[$category] = isset($category_limits[$category]) ? intval($category_limits[$category]) : 0;
        }
        
        // Save both settings
        $exceeding_saved = update_option($exceeding_option_key, $exceeding_settings);
        $limits_saved = update_option($limits_option_key, $limits_settings);
        
        if ($exceeding_saved || $limits_saved) {
            wp_send_json_success(__('Kategorien-Einstellungen erfolgreich gespeichert', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Speichern der Kategorien-Einstellungen', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX handler for saving export configuration
     */
    public function ajax_save_export_config() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ahgmh_admin_nonce')) {
            wp_send_json_error(__('Sicherheitsprüfung fehlgeschlagen', 'abschussplan-hgmh'));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nicht ausreichende Berechtigung', 'abschussplan-hgmh'));
            return;
        }

        $filename_pattern = sanitize_text_field($_POST['filename_pattern'] ?? 'abschussplan_{species}_{date}');
        $include_time = $_POST['include_time'] === 'true';

        // Validate filename pattern
        if (empty($filename_pattern)) {
            wp_send_json_error(__('Dateiname-Muster darf nicht leer sein', 'abschussplan-hgmh'));
            return;
        }

        // Save configuration
        $pattern_saved = update_option('ahgmh_export_filename_pattern', $filename_pattern);
        $time_saved = update_option('ahgmh_export_include_time', $include_time);

        if ($pattern_saved || $time_saved) {
            wp_send_json_success(__('Export-Einstellungen erfolgreich gespeichert', 'abschussplan-hgmh'));
        } else {
            wp_send_json_error(__('Fehler beim Speichern der Export-Einstellungen', 'abschussplan-hgmh'));
        }
    }
}
