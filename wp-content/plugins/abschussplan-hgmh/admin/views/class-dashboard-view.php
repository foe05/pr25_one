<?php
/**
 * Dashboard View - Renders dashboard UI components
 *
 * Provides reusable rendering methods for the main dashboard
 * and the WordPress dashboard widget.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Dashboard_View {

    /**
     * Render main dashboard
     *
     * @param array $stats Dashboard statistics
     */
    public function render_dashboard($stats) {
        ?>
        <div class="wrap ahgmh-admin-modern">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php echo esc_html__('Abschussplan HGMH - Dashboard', 'abschussplan-hgmh'); ?>
            </h1>

            <!-- Top Stats Cards -->
            <div class="ahgmh-dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="postbox" style="margin: 0; padding: 15px;">
                    <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($stats['total_submissions']); ?></div>
                    <div style="color: #646970; font-size: 13px;"><?php echo esc_html__('Gesamte Meldungen', 'abschussplan-hgmh'); ?></div>
                </div>

                <div class="postbox" style="margin: 0; padding: 15px;">
                    <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($stats['submissions_this_month']); ?></div>
                    <div style="color: #646970; font-size: 13px;"><?php echo esc_html__('Dieser Monat', 'abschussplan-hgmh'); ?></div>
                </div>

                <div class="postbox" style="margin: 0; padding: 15px;">
                    <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html(isset($stats['submissions_this_week']) ? $stats['submissions_this_week'] : '-'); ?></div>
                    <div style="color: #646970; font-size: 13px;"><?php echo esc_html__('Diese Woche', 'abschussplan-hgmh'); ?></div>
                </div>

                <div class="postbox" style="margin: 0; padding: 15px; display: flex; align-items: center; justify-content: center;">
                    <button id="quick-export" class="button button-primary">
                        <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                        <?php echo esc_html__('CSV Export', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Charts Column -->
                <div>
                    <?php $this->render_species_chart(isset($stats['species_stats']) ? $stats['species_stats'] : []); ?>
                    <?php $this->render_meldegruppen_stats(isset($stats['top_meldegruppen']) ? $stats['top_meldegruppen'] : []); ?>
                </div>

                <!-- Sidebar Column -->
                <div>
                    <?php $this->render_recent_submissions(isset($stats['recent_submissions']) ? $stats['recent_submissions'] : []); ?>
                    <?php $this->render_monthly_trend(isset($stats['monthly_trend']) ? $stats['monthly_trend'] : []); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render widget version for WP dashboard
     *
     * @param array $stats Dashboard statistics
     */
    public function render_widget($stats) {
        ?>
        <div class="ahgmh-wp-dashboard-widget">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                <div style="padding: 10px; background: #f6f7f7; border-radius: 4px;">
                    <strong style="display: block; font-size: 20px;"><?php echo esc_html($stats['total_submissions']); ?></strong>
                    <span style="color: #646970; font-size: 12px;"><?php echo esc_html__('Gesamte Meldungen', 'abschussplan-hgmh'); ?></span>
                </div>
                <div style="padding: 10px; background: #f6f7f7; border-radius: 4px;">
                    <strong style="display: block; font-size: 20px;"><?php echo esc_html($stats['submissions_this_month']); ?></strong>
                    <span style="color: #646970; font-size: 12px;"><?php echo esc_html__('Dieser Monat', 'abschussplan-hgmh'); ?></span>
                </div>
            </div>

            <div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=abschussplan-hgmh')); ?>" class="button button-primary">
                    <?php echo esc_html__('Dashboard oeffnen', 'abschussplan-hgmh'); ?>
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
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Meldungen nach Wildart', 'abschussplan-hgmh'); ?></h2>
            </div>
            <div class="inside">
                <?php if (!empty($species_stats)):
                    $max_count = max(array_column($species_stats, 'count'));
                    foreach ($species_stats as $species): ?>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <span style="min-width: 100px; font-weight: 600;"><?php echo esc_html($species['name']); ?></span>
                            <div style="flex: 1; background: #f0f0f1; height: 20px; border-radius: 3px; overflow: hidden;">
                                <div style="background: #2271b1; height: 100%; width: <?php echo $max_count > 0 ? min(($species['count'] / $max_count) * 100, 100) : 0; ?>%;"></div>
                            </div>
                            <span style="min-width: 40px; text-align: right; font-weight: 600;"><?php echo esc_html($species['count']); ?></span>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p style="color: #646970;"><?php echo esc_html__('Noch keine Meldungen vorhanden.', 'abschussplan-hgmh'); ?></p>
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
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Neueste Meldungen', 'abschussplan-hgmh'); ?></h2>
            </div>
            <div class="inside">
                <?php if (!empty($recent_submissions)): ?>
                    <?php foreach ($recent_submissions as $submission): ?>
                        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f1;">
                            <span style="font-weight: 600;"><?php echo esc_html($submission['art']); ?></span>
                            <span style="color: #646970;">
                                <?php echo esc_html($submission['anzahl']); ?>x -
                                <?php echo esc_html(date('d.m.Y', strtotime($submission['datum']))); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #646970;"><?php echo esc_html__('Noch keine Meldungen vorhanden.', 'abschussplan-hgmh'); ?></p>
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
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Top Meldegruppen', 'abschussplan-hgmh'); ?></h2>
            </div>
            <div class="inside">
                <?php if (!empty($meldegruppen_stats)): ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></th>
                                <th style="text-align: right;"><?php echo esc_html__('Meldungen', 'abschussplan-hgmh'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meldegruppen_stats as $meldegruppe): ?>
                            <tr>
                                <td><?php echo esc_html($meldegruppe['name']); ?></td>
                                <td style="text-align: right; font-weight: 600;"><?php echo esc_html($meldegruppe['count']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #646970;"><?php echo esc_html__('Noch keine Meldegruppen-Daten vorhanden.', 'abschussplan-hgmh'); ?></p>
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
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Monatlicher Trend', 'abschussplan-hgmh'); ?></h2>
            </div>
            <div class="inside">
                <?php if (!empty($monthly_trend)): ?>
                    <?php foreach ($monthly_trend as $month): ?>
                        <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #f0f0f1;">
                            <span><?php echo esc_html($month['month']); ?></span>
                            <span style="font-weight: 600;"><?php echo esc_html($month['count']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #646970;"><?php echo esc_html__('Noch keine Trend-Daten verfuegbar.', 'abschussplan-hgmh'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
