<?php
/**
 * Compliance View - Renders compliance dashboard UI with Bootstrap styling
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
        <div class="wrap ahgmh-compliance-dashboard">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php echo esc_html__('Compliance Dashboard', 'abschussplan-hgmh'); ?>
            </h1>

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
            <div class="ahgmh-compliance-footer">
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
        <div class="ahgmh-compliance-filters">
            <div class="filter-group">
                <label for="compliance-season-filter"><?php echo esc_html__('Jagdjahr:', 'abschussplan-hgmh'); ?></label>
                <select id="compliance-season-filter" name="season" class="regular-text">
                    <option value=""><?php echo esc_html__('Aktuelles Jagdjahr', 'abschussplan-hgmh'); ?></option>
                    <?php foreach ($hunting_seasons as $season_key => $season_label): ?>
                        <option value="<?php echo esc_attr($season_key); ?>" <?php selected($current_filters['season'], $season_key); ?>>
                            <?php echo esc_html($season_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="compliance-species-filter"><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?></label>
                <select id="compliance-species-filter" name="species" class="regular-text">
                    <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                    <?php foreach ($species_list as $species): ?>
                        <option value="<?php echo esc_attr($species); ?>" <?php selected($current_filters['species'], $species); ?>>
                            <?php echo esc_html($species); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="compliance-meldegruppe-filter"><?php echo esc_html__('Meldegruppe:', 'abschussplan-hgmh'); ?></label>
                <select id="compliance-meldegruppe-filter" name="meldegruppe" class="regular-text">
                    <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                    <?php foreach ($meldegruppen as $mg): ?>
                        <option value="<?php echo esc_attr($mg); ?>" <?php selected($current_filters['meldegruppe'], $mg); ?>>
                            <?php echo esc_html($mg); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="button" class="button button-primary" id="apply-compliance-filters">
                <?php echo esc_html__('Filter anwenden', 'abschussplan-hgmh'); ?>
            </button>

            <button type="button" class="button" id="refresh-compliance">
                <span class="dashicons dashicons-update"></span>
                <?php echo esc_html__('Aktualisieren', 'abschussplan-hgmh'); ?>
            </button>
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
        $status_class = $this->get_status_class($overall['status']);
        ?>
        <div class="ahgmh-compliance-summary">
            <h2><?php echo esc_html__('Gesamtübersicht', 'abschussplan-hgmh'); ?></h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="compliance-summary-card <?php echo esc_attr($status_class); ?>">
                        <div class="summary-stat">
                            <span class="stat-label"><?php echo esc_html__('Aktuell:', 'abschussplan-hgmh'); ?></span>
                            <span class="stat-value"><?php echo esc_html(number_format($overall['total_current'], 0, ',', '.')); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="compliance-summary-card <?php echo esc_attr($status_class); ?>">
                        <div class="summary-stat">
                            <span class="stat-label"><?php echo esc_html__('Limit:', 'abschussplan-hgmh'); ?></span>
                            <span class="stat-value"><?php echo esc_html(number_format($overall['total_limit'], 0, ',', '.')); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="compliance-summary-card <?php echo esc_attr($status_class); ?>">
                        <div class="summary-stat">
                            <span class="stat-label"><?php echo esc_html__('Verbleibend:', 'abschussplan-hgmh'); ?></span>
                            <span class="stat-value"><?php echo esc_html(number_format($overall['total_remaining'], 0, ',', '.')); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="compliance-summary-card <?php echo esc_attr($status_class); ?>">
                        <div class="summary-stat">
                            <span class="stat-label"><?php echo esc_html__('Erfüllung:', 'abschussplan-hgmh'); ?></span>
                            <span class="stat-value"><?php echo esc_html($overall['percentage']); ?>%</span>
                        </div>
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
            echo '<div class="notice notice-info"><p>' . esc_html__('Keine Compliance-Daten verfügbar.', 'abschussplan-hgmh') . '</p></div>';
            return;
        }

        foreach ($compliance['compliance'] as $species_data) {
            $species = $species_data['species'];
            $total = $species_data['total'];
            $status_class = $this->get_status_class($total['status']);
            ?>
            <div class="ahgmh-compliance-species">
                <h3><?php echo esc_html($species); ?></h3>
                <div class="species-total <?php echo esc_attr($status_class); ?>">
                    <span><?php echo esc_html__('Gesamt:', 'abschussplan-hgmh'); ?></span>
                    <span><?php echo esc_html($total['current'] . ' / ' . $total['limit']); ?></span>
                    <span class="status-badge"><?php echo esc_html($total['percentage']); ?>%</span>
                </div>

                <div class="category-breakdown">
                    <?php foreach ($species_data['categories'] as $category => $data):
                        $cat_status_class = $this->get_status_class($data['status']);
                    ?>
                        <div class="category-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong class="category-name"><?php echo esc_html($category); ?></strong>
                                </div>
                                <div class="col-md-9">
                                    <div class="category-progress">
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar <?php echo esc_attr($this->get_bootstrap_color($data['status'])); ?>"
                                                 role="progressbar"
                                                 style="width: <?php echo esc_attr(min($data['percentage'], 100)); ?>%;"
                                                 aria-valuenow="<?php echo esc_attr($data['percentage']); ?>"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                <?php echo esc_html($data['current'] . ' / ' . $data['limit']); ?>
                                                (<?php echo esc_html($data['percentage']); ?>%)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
        <div class="ahgmh-compliance-meldegruppen">
            <h2><?php echo esc_html__('Meldegruppen-Übersicht', 'abschussplan-hgmh'); ?></h2>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                            <th class="text-end"><?php echo esc_html__('Aktuell', 'abschussplan-hgmh'); ?></th>
                            <th class="text-end"><?php echo esc_html__('Limit', 'abschussplan-hgmh'); ?></th>
                            <th class="text-end"><?php echo esc_html__('Verbleibend', 'abschussplan-hgmh'); ?></th>
                            <th class="text-center"><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($by_meldegruppe['meldegruppen'] as $mg_data):
                            $total = $mg_data['total'];
                            $status_class = $this->get_status_class($total['status']);
                        ?>
                            <tr class="<?php echo esc_attr($status_class); ?>">
                                <td><strong><?php echo esc_html($mg_data['meldegruppe']); ?></strong></td>
                                <td><?php echo esc_html($mg_data['species']); ?></td>
                                <td class="text-end"><?php echo esc_html(number_format($total['current'], 0, ',', '.')); ?></td>
                                <td class="text-end"><?php echo esc_html(number_format($total['limit'], 0, ',', '.')); ?></td>
                                <td class="text-end"><?php echo esc_html(number_format($total['remaining'], 0, ',', '.')); ?></td>
                                <td class="text-center">
                                    <span class="badge <?php echo esc_attr($this->get_bootstrap_badge($total['status'])); ?>">
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
     * Get CSS class for status indicator
     *
     * @param string $status Status indicator (good/warning/critical/exceeded)
     * @return string CSS class
     */
    private function get_status_class($status) {
        $classes = [
            'good' => 'status-good',
            'warning' => 'status-warning',
            'critical' => 'status-critical',
            'exceeded' => 'status-exceeded'
        ];

        return isset($classes[$status]) ? $classes[$status] : 'status-good';
    }

    /**
     * Get Bootstrap color class for progress bars
     *
     * @param string $status Status indicator
     * @return string Bootstrap color class
     */
    private function get_bootstrap_color($status) {
        $colors = [
            'good' => 'bg-success',
            'warning' => 'bg-warning',
            'critical' => 'bg-danger',
            'exceeded' => 'bg-dark'
        ];

        return isset($colors[$status]) ? $colors[$status] : 'bg-success';
    }

    /**
     * Get Bootstrap badge class for status badges
     *
     * @param string $status Status indicator
     * @return string Bootstrap badge class
     */
    private function get_bootstrap_badge($status) {
        $badges = [
            'good' => 'bg-success',
            'warning' => 'bg-warning text-dark',
            'critical' => 'bg-danger',
            'exceeded' => 'bg-dark'
        ];

        return isset($badges[$status]) ? $badges[$status] : 'bg-success';
    }

    /**
     * Get status label for display
     *
     * @param string $status Status indicator
     * @return string Localized status label
     */
    private function get_status_label($status) {
        $labels = [
            'good' => __('Gut', 'abschussplan-hgmh'),
            'warning' => __('Achtung', 'abschussplan-hgmh'),
            'critical' => __('Kritisch', 'abschussplan-hgmh'),
            'exceeded' => __('Überschritten', 'abschussplan-hgmh')
        ];

        return isset($labels[$status]) ? $labels[$status] : $labels['good'];
    }

    /**
     * Get available hunting seasons for filter dropdown
     * German hunting season runs from April 1 to March 31 of the following year
     *
     * @return array Hunting seasons with keys (YYYY-YYYY) and labels
     */
    private function get_hunting_seasons() {
        $seasons = [];
        $current_year = intval(date('Y'));
        $current_month = intval(date('m'));

        // Determine the current hunting season
        // If before April, we're still in the previous season
        if ($current_month < 4) {
            $current_season_start = $current_year - 1;
        } else {
            $current_season_start = $current_year;
        }

        // Generate last 5 hunting seasons
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
