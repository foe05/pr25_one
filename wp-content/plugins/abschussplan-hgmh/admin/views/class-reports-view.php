<?php
/**
 * Reports View - Renders report generation interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Reports_View {

    /**
     * Render main reports page (embedded in tab context)
     *
     * @param array $data Page data including species_list, meldegruppen, current_season, hunting_seasons
     */
    public function render_reports_page($data) {
        $species_list = isset($data['species_list']) ? $data['species_list'] : [];
        $meldegruppen = isset($data['meldegruppen']) ? $data['meldegruppen'] : [];
        $current_season = isset($data['current_season']) ? $data['current_season'] : [];
        $hunting_seasons = isset($data['hunting_seasons']) ? $data['hunting_seasons'] : [];
        ?>
        <div class="ahgmh-reports-page" style="display: grid; grid-template-columns: minmax(320px, 400px) 1fr; gap: 20px; margin-top: 20px;">
            <!-- Report Configuration Section -->
            <div class="postbox" style="margin: 0;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html__('Bericht konfigurieren', 'abschussplan-hgmh'); ?></h2>
                </div>
                <div class="inside">
                    <!-- Report Type Selection -->
                    <?php $this->render_report_type_selection(); ?>

                    <!-- Quick Season Buttons -->
                    <?php $this->render_quick_season_buttons($current_season); ?>

                    <!-- Date Range Picker -->
                    <?php $this->render_date_range_picker(); ?>

                    <!-- Season Selector -->
                    <?php $this->render_season_selector($hunting_seasons); ?>

                    <!-- Filters Section -->
                    <?php $this->render_filters($species_list, $meldegruppen); ?>

                    <!-- Output Format Selection -->
                    <?php $this->render_output_format_selection(); ?>

                    <!-- Action Buttons -->
                    <?php $this->render_action_buttons(); ?>
                </div>
            </div>

            <!-- Report Preview Section -->
            <div class="postbox" style="margin: 0; min-height: 500px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html__('Vorschau', 'abschussplan-hgmh'); ?></h2>
                </div>
                <div class="inside" id="report-preview-container">
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 400px; color: #646970; text-align: center;">
                        <span class="dashicons dashicons-media-document" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 15px;"></span>
                        <p><?php echo esc_html__('Konfigurieren Sie einen Bericht und klicken Sie auf "Bericht erstellen", um das Ergebnis zu sehen.', 'abschussplan-hgmh'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render report type selection
     */
    private function render_report_type_selection() {
        ?>
        <fieldset style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1;">
            <legend style="font-weight: 600; font-size: 14px; margin-bottom: 10px;"><?php echo esc_html__('Berichtstyp', 'abschussplan-hgmh'); ?></legend>
            <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                <input type="radio" name="report_type" value="seasonal" checked>
                <strong><?php echo esc_html__('Saisonbericht', 'abschussplan-hgmh'); ?></strong>
                <span style="display: block; margin-left: 24px; color: #646970; font-size: 12px;">
                    <?php echo esc_html__('Uebersicht ueber eine komplette Jagdsaison', 'abschussplan-hgmh'); ?>
                </span>
            </label>
            <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                <input type="radio" name="report_type" value="date_range">
                <strong><?php echo esc_html__('Zeitraumbericht', 'abschussplan-hgmh'); ?></strong>
                <span style="display: block; margin-left: 24px; color: #646970; font-size: 12px;">
                    <?php echo esc_html__('Bericht fuer einen benutzerdefinierten Zeitraum', 'abschussplan-hgmh'); ?>
                </span>
            </label>
            <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                <input type="radio" name="report_type" value="compliance">
                <strong><?php echo esc_html__('Compliance-Bericht', 'abschussplan-hgmh'); ?></strong>
                <span style="display: block; margin-left: 24px; color: #646970; font-size: 12px;">
                    <?php echo esc_html__('Aktueller Status der Abschussplanerfuellung', 'abschussplan-hgmh'); ?>
                </span>
            </label>
            <label style="display: block; cursor: pointer;">
                <input type="radio" name="report_type" value="trend">
                <strong><?php echo esc_html__('Trendanalyse', 'abschussplan-hgmh'); ?></strong>
                <span style="display: block; margin-left: 24px; color: #646970; font-size: 12px;">
                    <?php echo esc_html__('Vergleich und Trends ueber mehrere Saisons', 'abschussplan-hgmh'); ?>
                </span>
            </label>
        </fieldset>
        <?php
    }

    /**
     * Render quick season buttons
     */
    private function render_quick_season_buttons($current_season) {
        $current_season_value = isset($current_season['value']) ? $current_season['value'] : '';
        $previous_season_value = '';
        if (!empty($current_season_value) && preg_match('/^(\d{4})-(\d{4})$/', $current_season_value, $matches)) {
            $prev_start = intval($matches[1]) - 1;
            $prev_end = intval($matches[2]) - 1;
            $previous_season_value = sprintf('%d-%d', $prev_start, $prev_end);
        }
        ?>
        <div id="quick-seasons-section" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1;">
            <p style="font-weight: 600; margin-bottom: 8px;"><?php echo esc_html__('Schnellauswahl', 'abschussplan-hgmh'); ?></p>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="button quick-season-btn"
                        data-season="<?php echo esc_attr($current_season_value); ?>">
                    <?php echo esc_html__('Aktuelle Saison', 'abschussplan-hgmh'); ?>
                    <?php if (!empty($current_season_value)): ?>
                        (<?php echo esc_html($current_season_value); ?>)
                    <?php endif; ?>
                </button>
                <button type="button" class="button quick-season-btn"
                        data-season="<?php echo esc_attr($previous_season_value); ?>">
                    <?php echo esc_html__('Letzte Saison', 'abschussplan-hgmh'); ?>
                    <?php if (!empty($previous_season_value)): ?>
                        (<?php echo esc_html($previous_season_value); ?>)
                    <?php endif; ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render date range picker
     */
    private function render_date_range_picker() {
        ?>
        <div id="date-range-section" style="display: none; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1;">
            <p style="font-weight: 600; margin-bottom: 8px;"><?php echo esc_html__('Zeitraum waehlen', 'abschussplan-hgmh'); ?></p>
            <div style="display: grid; gap: 10px;">
                <div>
                    <label for="start-date" style="display: block; margin-bottom: 4px; font-size: 13px;"><?php echo esc_html__('Startdatum:', 'abschussplan-hgmh'); ?></label>
                    <input type="date" id="start-date" name="start_date" class="regular-text">
                </div>
                <div>
                    <label for="end-date" style="display: block; margin-bottom: 4px; font-size: 13px;"><?php echo esc_html__('Enddatum:', 'abschussplan-hgmh'); ?></label>
                    <input type="date" id="end-date" name="end_date" class="regular-text">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render season selector
     */
    private function render_season_selector($hunting_seasons) {
        ?>
        <div id="season-selector-section" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1;">
            <label for="season-select" style="display: block; font-weight: 600; margin-bottom: 8px;">
                <?php echo esc_html__('Jagdjahr waehlen', 'abschussplan-hgmh'); ?>
            </label>
            <select id="season-select" name="season" class="regular-text" style="width: 100%;">
                <?php foreach ($hunting_seasons as $season): ?>
                    <option value="<?php echo esc_attr($season['value']); ?>">
                        <?php echo esc_html(sprintf(__('Jagdjahr %s', 'abschussplan-hgmh'), $season['label'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render filter options
     */
    private function render_filters($species_list, $meldegruppen) {
        ?>
        <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1;">
            <p style="font-weight: 600; margin-bottom: 8px;"><?php echo esc_html__('Filter (optional)', 'abschussplan-hgmh'); ?></p>
            <div style="display: grid; gap: 10px;">
                <div>
                    <label for="filter-species" style="display: block; margin-bottom: 4px; font-size: 13px;"><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?></label>
                    <select id="filter-species" name="species" class="regular-text" style="width: 100%;">
                        <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($species_list as $species): ?>
                            <option value="<?php echo esc_attr($species); ?>"><?php echo esc_html($species); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter-meldegruppe" style="display: block; margin-bottom: 4px; font-size: 13px;"><?php echo esc_html__('Meldegruppe:', 'abschussplan-hgmh'); ?></label>
                    <select id="filter-meldegruppe" name="meldegruppe" class="regular-text" style="width: 100%;">
                        <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($meldegruppen as $mg): ?>
                            <option value="<?php echo esc_attr($mg); ?>"><?php echo esc_html($mg); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render output format selection
     */
    private function render_output_format_selection() {
        ?>
        <fieldset style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f1;">
            <legend style="font-weight: 600; font-size: 14px; margin-bottom: 10px;"><?php echo esc_html__('Ausgabeformat', 'abschussplan-hgmh'); ?></legend>
            <label style="display: block; margin-bottom: 6px; cursor: pointer;">
                <input type="radio" name="output_format" value="preview" checked>
                <?php echo esc_html__('Vorschau anzeigen', 'abschussplan-hgmh'); ?>
            </label>
            <label style="display: block; margin-bottom: 6px; cursor: pointer;">
                <input type="radio" name="output_format" value="csv">
                <?php echo esc_html__('CSV herunterladen', 'abschussplan-hgmh'); ?>
            </label>
            <label style="display: block; margin-bottom: 6px; cursor: pointer;">
                <input type="radio" name="output_format" value="pdf">
                <?php echo esc_html__('PDF herunterladen', 'abschussplan-hgmh'); ?>
            </label>
            <label style="display: block; cursor: pointer;">
                <input type="radio" name="output_format" value="email">
                <?php echo esc_html__('Per E-Mail senden', 'abschussplan-hgmh'); ?>
            </label>

            <!-- Email recipient input (hidden by default) -->
            <div id="email-recipient-section" style="display: none; margin-top: 10px;">
                <label for="email-recipient" style="display: block; margin-bottom: 4px; font-size: 13px;"><?php echo esc_html__('E-Mail-Adresse:', 'abschussplan-hgmh'); ?></label>
                <input type="email" id="email-recipient" name="recipient" class="regular-text" style="width: 100%;"
                       placeholder="<?php echo esc_attr__('empfaenger@beispiel.de', 'abschussplan-hgmh'); ?>">
            </div>
        </fieldset>
        <?php
    }

    /**
     * Render action buttons
     */
    private function render_action_buttons() {
        ?>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="button button-primary" id="generate-report-btn">
                <?php echo esc_html__('Bericht erstellen', 'abschussplan-hgmh'); ?>
            </button>
            <button type="button" class="button" id="reset-form-btn">
                <?php echo esc_html__('Zuruecksetzen', 'abschussplan-hgmh'); ?>
            </button>
        </div>
        <?php
    }
}
