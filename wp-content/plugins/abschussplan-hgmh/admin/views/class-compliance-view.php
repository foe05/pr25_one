<?php
/**
 * Compliance View - Renders compliance dashboard UI using WordPress admin patterns
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Compliance_View {

    /**
     * Render main compliance dashboard
     *
     * @param array $compliance_data Compliance data from service
     * @param array $species_list Available species for filtering
     * @param array $meldegruppen Available meldegruppen for filtering
     */
    public function render_dashboard($compliance_data, $species_list, $meldegruppen) {
        ?>
        <div class="ahgmh-compliance-dashboard">

            <!-- Filter Bar -->
            <?php $this->render_filter_bar($species_list, $meldegruppen, $compliance_data['filters']); ?>

            <!-- Overall Summary -->
            <div id="compliance-summary-container">
                <?php $this->render_summary($compliance_data['summary']); ?>
            </div>

            <!-- Species Compliance Details -->
            <div id="compliance-details-container">
                <?php $this->render_details($compliance_data['compliance']); ?>
            </div>

            <!-- Meldegruppe Breakdown -->
            <div id="compliance-meldegruppe-container">
                <?php $this->render_meldegruppe_breakdown($compliance_data['by_meldegruppe']); ?>
            </div>

            <!-- Last Update Timestamp -->
            <div class="ahgmh-compliance-footer" style="margin-top: 20px; color: #646970; font-size: 13px;">
                <p class="last-update">
                    <?php
                    echo esc_html__('Zuletzt aktualisiert:', 'abschussplan-hgmh') . ' ';
                    echo esc_html(current_time('d.m.Y H:i:s'));
                    ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render filter bar with species, meldegruppe, and season dropdowns
     *
     * @param array $species_list Available species
     * @param array $meldegruppen Available meldegruppen
     * @param array $current_filters Currently applied filters
     */
    private function render_filter_bar($species_list, $meldegruppen, $current_filters) {
        $hunting_seasons = $this->get_hunting_seasons();
        ?>
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="inside" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <div>
                    <label for="compliance-season-filter" style="display: block; margin-bottom: 4px; font-weight: 600;">
                        <?php echo esc_html__('Jagdjahr:', 'abschussplan-hgmh'); ?>
                    </label>
                    <select id="compliance-season-filter" name="season" class="regular-text">
                        <option value=""><?php echo esc_html__('Aktuelles Jagdjahr', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($hunting_seasons as $season_key => $season_label): ?>
                            <option value="<?php echo esc_attr($season_key); ?>" <?php selected($current_filters['season'], $season_key); ?>>
                                <?php echo esc_html($season_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="compliance-species-filter" style="display: block; margin-bottom: 4px; font-weight: 600;">
                        <?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?>
                    </label>
                    <select id="compliance-species-filter" name="species" class="regular-text">
                        <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($species_list as $species): ?>
                            <option value="<?php echo esc_attr($species); ?>" <?php selected($current_filters['species'], $species); ?>>
                                <?php echo esc_html($species); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="compliance-meldegruppe-filter" style="display: block; margin-bottom: 4px; font-weight: 600;">
                        <?php echo esc_html__('Meldegruppe:', 'abschussplan-hgmh'); ?>
                    </label>
                    <select id="compliance-meldegruppe-filter" name="meldegruppe" class="regular-text">
                        <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($meldegruppen as $mg): ?>
                            <option value="<?php echo esc_attr($mg); ?>" <?php selected($current_filters['meldegruppe'], $mg); ?>>
                                <?php echo esc_html($mg); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="button" class="button button-primary" id="apply-compliance-filters">
                        <?php echo esc_html__('Filter anwenden', 'abschussplan-hgmh'); ?>
                    </button>
                    <button type="button" class="button" id="refresh-compliance">
                        <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                        <?php echo esc_html__('Aktualisieren', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render overall compliance summary with statistics cards
     *
     * @param array $summary Summary data from service
     */
    public function render_summary($summary) {
        if (isset($summary['error']) || empty($summary)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Fehler beim Laden der Zusammenfassung.', 'abschussplan-hgmh') . '</p></div>';
            return;
        }

        $overall = $summary['overall'];
        $status_class = $this->get_wp_status_class($overall['status']);
        ?>
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Gesamtuebersicht', 'abschussplan-hgmh'); ?></h2>
            </div>
            <div class="inside">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                    <div class="ahgmh-stat-card <?php echo esc_attr($status_class); ?>">
                        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Aktuell', 'abschussplan-hgmh'); ?></div>
                        <div style="font-size: 24px; font-weight: 600;"><?php echo esc_html(number_format($overall['total_current'], 0, ',', '.')); ?></div>
                    </div>
                    <div class="ahgmh-stat-card <?php echo esc_attr($status_class); ?>">
                        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Limit', 'abschussplan-hgmh'); ?></div>
                        <div style="font-size: 24px; font-weight: 600;"><?php echo esc_html(number_format($overall['total_limit'], 0, ',', '.')); ?></div>
                    </div>
                    <div class="ahgmh-stat-card <?php echo esc_attr($status_class); ?>">
                        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Verbleibend', 'abschussplan-hgmh'); ?></div>
                        <div style="font-size: 24px; font-weight: 600;"><?php echo esc_html(number_format($overall['total_remaining'], 0, ',', '.')); ?></div>
                    </div>
                    <div class="ahgmh-stat-card <?php echo esc_attr($status_class); ?>">
                        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Erfuellung', 'abschussplan-hgmh'); ?></div>
                        <div style="font-size: 24px; font-weight: 600;"><?php echo esc_html($overall['percentage']); ?>%</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render compliance details for each species with category breakdowns
     *
     * @param array $compliance Compliance data by species
     */
    public function render_details($compliance) {
        if (isset($compliance['error']) || empty($compliance['compliance'])) {
            echo '<div class="notice notice-info"><p>' . esc_html__('Keine Compliance-Daten verfuegbar. Konfigurieren Sie zuerst Wildarten und Limits.', 'abschussplan-hgmh') . '</p></div>';
            return;
        }

        foreach ($compliance['compliance'] as $species_data) {
            $species = $species_data['species'];
            $total = $species_data['total'];
            $status_class = $this->get_wp_status_class($total['status']);
            $bar_color = $this->get_bar_color($total['status']);
            ?>
            <div class="postbox" style="margin-bottom: 15px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html($species); ?></h2>
                </div>
                <div class="inside">
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 15px;">
                        <span style="font-weight: 600;"><?php echo esc_html__('Gesamt:', 'abschussplan-hgmh'); ?></span>
                        <span><?php echo esc_html($total['current'] . ' / ' . $total['limit']); ?></span>
                        <div style="flex: 1; background: #f0f0f1; height: 20px; border-radius: 3px; overflow: hidden;">
                            <div style="background: <?php echo esc_attr($bar_color); ?>; height: 100%; width: <?php echo esc_attr(min($total['percentage'], 100)); ?>%; transition: width 0.3s;"></div>
                        </div>
                        <span style="font-weight: 600;"><?php echo esc_html($total['percentage']); ?>%</span>
                    </div>

                    <?php if (!empty($species_data['categories'])): ?>
                    <table class="widefat striped" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                                <th style="text-align: right;"><?php echo esc_html__('Aktuell', 'abschussplan-hgmh'); ?></th>
                                <th style="text-align: right;"><?php echo esc_html__('Limit', 'abschussplan-hgmh'); ?></th>
                                <th style="width: 40%;"><?php echo esc_html__('Fortschritt', 'abschussplan-hgmh'); ?></th>
                                <th style="text-align: right;"><?php echo esc_html__('Prozent', 'abschussplan-hgmh'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($species_data['categories'] as $category => $data):
                                $cat_bar_color = $this->get_bar_color($data['status']);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($category); ?></strong></td>
                                <td style="text-align: right;"><?php echo esc_html($data['current']); ?></td>
                                <td style="text-align: right;"><?php echo esc_html($data['limit']); ?></td>
                                <td>
                                    <div style="background: #f0f0f1; height: 16px; border-radius: 3px; overflow: hidden;">
                                        <div style="background: <?php echo esc_attr($cat_bar_color); ?>; height: 100%; width: <?php echo esc_attr(min($data['percentage'], 100)); ?>%;"></div>
                                    </div>
                                </td>
                                <td style="text-align: right;"><?php echo esc_html($data['percentage']); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Render meldegruppe breakdown table
     *
     * @param array $by_meldegruppe Meldegruppe-specific compliance data
     */
    public function render_meldegruppe_breakdown($by_meldegruppe) {
        if (isset($by_meldegruppe['error']) || empty($by_meldegruppe['meldegruppen'])) {
            return;
        }
        ?>
        <div class="postbox" style="margin-bottom: 20px;">
            <div class="postbox-header">
                <h2 class="hndle"><?php echo esc_html__('Meldegruppen-Uebersicht', 'abschussplan-hgmh'); ?></h2>
            </div>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                            <th style="text-align: right;"><?php echo esc_html__('Aktuell', 'abschussplan-hgmh'); ?></th>
                            <th style="text-align: right;"><?php echo esc_html__('Limit', 'abschussplan-hgmh'); ?></th>
                            <th style="text-align: right;"><?php echo esc_html__('Verbleibend', 'abschussplan-hgmh'); ?></th>
                            <th style="text-align: center;"><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($by_meldegruppe['meldegruppen'] as $mg_data):
                            $total = $mg_data['total'];
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($mg_data['meldegruppe']); ?></strong></td>
                                <td><?php echo esc_html($mg_data['species']); ?></td>
                                <td style="text-align: right;"><?php echo esc_html(number_format($total['current'], 0, ',', '.')); ?></td>
                                <td style="text-align: right;"><?php echo esc_html(number_format($total['limit'], 0, ',', '.')); ?></td>
                                <td style="text-align: right;"><?php echo esc_html(number_format($total['remaining'], 0, ',', '.')); ?></td>
                                <td style="text-align: center;">
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; background: <?php echo esc_attr($this->get_badge_bg($total['status'])); ?>; color: <?php echo esc_attr($this->get_badge_color($total['status'])); ?>;">
                                        <?php echo esc_html($this->get_status_label($total['status'])); ?>
                                        (<?php echo esc_html($total['percentage']); ?>%)
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Get WordPress-compatible status class
     */
    private function get_wp_status_class($status) {
        $classes = [
            'good' => 'status-good',
            'warning' => 'status-warning',
            'critical' => 'status-critical',
            'exceeded' => 'status-exceeded'
        ];
        return isset($classes[$status]) ? $classes[$status] : 'status-good';
    }

    /**
     * Get progress bar color for status
     */
    private function get_bar_color($status) {
        $colors = [
            'good' => '#00a32a',
            'warning' => '#dba617',
            'critical' => '#d63638',
            'exceeded' => '#1d2327'
        ];
        return isset($colors[$status]) ? $colors[$status] : '#00a32a';
    }

    /**
     * Get badge background color
     */
    private function get_badge_bg($status) {
        $colors = [
            'good' => '#edfaef',
            'warning' => '#fcf9e8',
            'critical' => '#fcf0f1',
            'exceeded' => '#f0f0f1'
        ];
        return isset($colors[$status]) ? $colors[$status] : '#edfaef';
    }

    /**
     * Get badge text color
     */
    private function get_badge_color($status) {
        $colors = [
            'good' => '#00a32a',
            'warning' => '#996800',
            'critical' => '#d63638',
            'exceeded' => '#1d2327'
        ];
        return isset($colors[$status]) ? $colors[$status] : '#00a32a';
    }

    /**
     * Get status label for display
     */
    private function get_status_label($status) {
        $labels = [
            'good' => __('Gut', 'abschussplan-hgmh'),
            'warning' => __('Achtung', 'abschussplan-hgmh'),
            'critical' => __('Kritisch', 'abschussplan-hgmh'),
            'exceeded' => __('Ueberschritten', 'abschussplan-hgmh')
        ];
        return isset($labels[$status]) ? $labels[$status] : $labels['good'];
    }

    /**
     * Get available hunting seasons for filter dropdown
     */
    private function get_hunting_seasons() {
        $seasons = [];
        $current_year = intval(date('Y'));
        $current_month = intval(date('m'));

        if ($current_month < 4) {
            $current_season_start = $current_year - 1;
        } else {
            $current_season_start = $current_year;
        }

        for ($i = 0; $i < 5; $i++) {
            $season_start_year = $current_season_start - $i;
            $season_end_year = $season_start_year + 1;
            $season_key = $season_start_year . '-' . $season_end_year;
            $season_label = sprintf(
                __('Jagdjahr %d/%d', 'abschussplan-hgmh'),
                $season_start_year,
                $season_end_year
            );
            $seasons[$season_key] = $season_label;
        }

        return $seasons;
    }
}
