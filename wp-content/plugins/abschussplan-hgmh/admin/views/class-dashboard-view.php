<?php
/**
 * Dashboard View - Renders dashboard UI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Dashboard_View {
    
    /**
     * Render main dashboard
     */
    public function render_dashboard($stats) {
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
                        <div class="stat-number"><?php echo esc_html($stats['submissions_this_month']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Dieser Monat', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
                
                <div class="ahgmh-stat-card">
                    <div class="stat-icon dashicons dashicons-calendar"></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['submissions_this_week']); ?></div>
                        <div class="stat-label"><?php echo esc_html__('Diese Woche', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
                
                <div class="ahgmh-stat-card">
                    <div class="stat-icon dashicons dashicons-download"></div>
                    <div class="stat-content">
                        <button id="quick-export" class="button button-primary">
                            <?php echo esc_html__('CSV Export', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="ahgmh-dashboard-row">
                <div class="ahgmh-dashboard-col-8">
                    <?php $this->render_species_chart($stats['species_stats']); ?>
                </div>
                <div class="ahgmh-dashboard-col-4">
                    <?php $this->render_recent_submissions($stats['recent_submissions']); ?>
                </div>
            </div>
            
            <!-- Bottom Row -->
            <div class="ahgmh-dashboard-row">
                <div class="ahgmh-dashboard-col-6">
                    <?php $this->render_meldegruppen_stats($stats['top_meldegruppen']); ?>
                </div>
                <div class="ahgmh-dashboard-col-6">
                    <?php $this->render_monthly_trend($stats['monthly_trend']); ?>
                </div>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Render widget version for WP dashboard
     */
    public function render_widget($stats) {
        ?>
        <div class="ahgmh-wp-dashboard-widget">
            <div class="widget-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($stats['total_submissions']); ?></span>
                    <span class="stat-label"><?php echo esc_html__('Gesamte Meldungen', 'abschussplan-hgmh'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($stats['submissions_this_month']); ?></span>
                    <span class="stat-label"><?php echo esc_html__('Dieser Monat', 'abschussplan-hgmh'); ?></span>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=abschussplan-hgmh')); ?>" class="button button-primary">
                    <?php echo esc_html__('Dashboard öffnen', 'abschussplan-hgmh'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render species statistics chart
     */
    private function render_species_chart($species_stats) {
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Meldungen nach Wildart', 'abschussplan-hgmh'); ?></h2>
            <div class="species-chart">
                <?php if (!empty($species_stats)): ?>
                    <?php foreach ($species_stats as $species): ?>
                        <div class="species-bar">
                            <span class="species-name"><?php echo esc_html($species['name']); ?></span>
                            <div class="species-progress">
                                <div class="progress-bar" style="width: <?php echo min(($species['count'] / max(array_column($species_stats, 'count'))) * 100, 100); ?>%"></div>
                                <span class="species-count"><?php echo esc_html($species['count']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php echo esc_html__('Noch keine Meldungen vorhanden.', 'abschussplan-hgmh'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent submissions
     */
    private function render_recent_submissions($recent_submissions) {
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Neueste Meldungen', 'abschussplan-hgmh'); ?></h2>
            <div class="recent-submissions">
                <?php if (!empty($recent_submissions)): ?>
                    <?php foreach ($recent_submissions as $submission): ?>
                        <div class="submission-item">
                            <div class="submission-species"><?php echo esc_html($submission['art']); ?></div>
                            <div class="submission-details">
                                <span class="submission-count"><?php echo esc_html($submission['anzahl']); ?>x</span>
                                <span class="submission-date"><?php echo esc_html(date('d.m.Y', strtotime($submission['datum']))); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php echo esc_html__('Noch keine Meldungen vorhanden.', 'abschussplan-hgmh'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render meldegruppen statistics
     */
    private function render_meldegruppen_stats($meldegruppen_stats) {
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Top Meldegruppen', 'abschussplan-hgmh'); ?></h2>
            <div class="meldegruppen-stats">
                <?php if (!empty($meldegruppen_stats)): ?>
                    <?php foreach ($meldegruppen_stats as $meldegruppe): ?>
                        <div class="meldegruppe-item">
                            <span class="meldegruppe-name"><?php echo esc_html($meldegruppe['name']); ?></span>
                            <span class="meldegruppe-count"><?php echo esc_html($meldegruppe['count']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php echo esc_html__('Noch keine Meldegruppen-Daten vorhanden.', 'abschussplan-hgmh'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render monthly trend
     */
    private function render_monthly_trend($monthly_trend) {
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Monatlicher Trend', 'abschussplan-hgmh'); ?></h2>
            <div class="monthly-trend">
                <?php if (!empty($monthly_trend)): ?>
                    <?php foreach ($monthly_trend as $month): ?>
                        <div class="trend-item">
                            <span class="trend-month"><?php echo esc_html($month['month']); ?></span>
                            <span class="trend-count"><?php echo esc_html($month['count']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php echo esc_html__('Noch keine Trend-Daten verfügbar.', 'abschussplan-hgmh'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
