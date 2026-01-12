<?php
/**
 * Schedule Settings View
 * Renders the scheduled reports configuration interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Schedule_Settings_View {

    /**
     * Render main schedule settings page
     *
     * @param array $data Page data including schedules, species_list, meldegruppen, stats
     */
    public function render_settings_page($data) {
        $schedules = isset($data['schedules']) ? $data['schedules'] : [];
        $species_list = isset($data['species_list']) ? $data['species_list'] : [];
        $meldegruppen = isset($data['meldegruppen']) ? $data['meldegruppen'] : [];
        $stats = isset($data['stats']) ? $data['stats'] : [];
        ?>
        <div class="wrap ahgmh-schedule-settings-page">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-clock"></span>
                <?php echo esc_html__('Geplante Berichte', 'abschussplan-hgmh'); ?>
            </h1>

            <div class="ahgmh-schedule-container">
                <!-- Statistics Section -->
                <?php $this->render_statistics($stats); ?>

                <!-- Existing Schedules Section -->
                <div class="ahgmh-schedules-list-section">
                    <div class="ahgmh-section-header">
                        <h2><?php echo esc_html__('Bestehende Zeitpläne', 'abschussplan-hgmh'); ?></h2>
                        <button type="button" class="button button-primary" id="btn-add-new-schedule">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php echo esc_html__('Neuer Zeitplan', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>

                    <?php $this->render_schedules_list($schedules); ?>
                </div>

                <!-- Schedule Form (hidden by default, shown when adding/editing) -->
                <?php $this->render_schedule_form($species_list, $meldegruppen); ?>

                <!-- Schedule History Section -->
                <div class="ahgmh-schedule-history-section">
                    <h2><?php echo esc_html__('Ausführungsverlauf', 'abschussplan-hgmh'); ?></h2>
                    <div id="schedule-history-container">
                        <p class="ahgmh-history-placeholder">
                            <?php echo esc_html__('Wählen Sie einen Zeitplan aus, um dessen Verlauf anzuzeigen.', 'abschussplan-hgmh'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->render_styles(); ?>
        <?php $this->render_scripts(); ?>
        <?php
    }

    /**
     * Render statistics section
     *
     * @param array $stats Statistics data
     */
    private function render_statistics($stats) {
        $total = isset($stats['total_schedules']) ? $stats['total_schedules'] : 0;
        $enabled = isset($stats['enabled_schedules']) ? $stats['enabled_schedules'] : 0;
        $total_executions = isset($stats['total_executions']) ? $stats['total_executions'] : 0;
        $successful = isset($stats['successful_executions']) ? $stats['successful_executions'] : 0;
        ?>
        <div class="ahgmh-stats-section">
            <div class="ahgmh-stat-card">
                <span class="dashicons dashicons-calendar-alt"></span>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($total); ?></div>
                    <div class="stat-label"><?php echo esc_html__('Zeitpläne', 'abschussplan-hgmh'); ?></div>
                </div>
            </div>
            <div class="ahgmh-stat-card">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($enabled); ?></div>
                    <div class="stat-label"><?php echo esc_html__('Aktiv', 'abschussplan-hgmh'); ?></div>
                </div>
            </div>
            <div class="ahgmh-stat-card">
                <span class="dashicons dashicons-backup"></span>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($total_executions); ?></div>
                    <div class="stat-label"><?php echo esc_html__('Ausführungen', 'abschussplan-hgmh'); ?></div>
                </div>
            </div>
            <div class="ahgmh-stat-card">
                <span class="dashicons dashicons-thumbs-up"></span>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($successful); ?></div>
                    <div class="stat-label"><?php echo esc_html__('Erfolgreich', 'abschussplan-hgmh'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render schedules list
     *
     * @param array $schedules Array of schedules
     */
    private function render_schedules_list($schedules) {
        if (empty($schedules)) {
            ?>
            <div class="ahgmh-no-schedules">
                <p><?php echo esc_html__('Noch keine Zeitpläne konfiguriert.', 'abschussplan-hgmh'); ?></p>
                <p><?php echo esc_html__('Klicken Sie auf "Neuer Zeitplan", um Ihren ersten geplanten Bericht zu erstellen.', 'abschussplan-hgmh'); ?></p>
            </div>
            <?php
            return;
        }

        ?>
        <div class="ahgmh-schedules-list">
            <?php foreach ($schedules as $schedule): ?>
                <?php $this->render_schedule_item($schedule); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render single schedule item
     *
     * @param array $schedule Schedule data
     */
    private function render_schedule_item($schedule) {
        $schedule_id = isset($schedule['id']) ? $schedule['id'] : '';
        $name = isset($schedule['name']) ? $schedule['name'] : '';
        $report_type = isset($schedule['report_type']) ? $schedule['report_type'] : '';
        $frequency = isset($schedule['frequency']) ? $schedule['frequency'] : '';
        $enabled = isset($schedule['enabled']) ? $schedule['enabled'] : false;
        $last_run = isset($schedule['last_run']) ? $schedule['last_run'] : null;
        $next_run = isset($schedule['next_run']) ? $schedule['next_run'] : null;
        $recipients = isset($schedule['recipients']) ? $schedule['recipients'] : [];

        $status_class = $enabled ? 'status-enabled' : 'status-disabled';
        $status_text = $enabled ? __('Aktiv', 'abschussplan-hgmh') : __('Inaktiv', 'abschussplan-hgmh');

        // Format dates
        $last_run_formatted = $last_run ? date('d.m.Y H:i', strtotime($last_run)) : __('Noch nie', 'abschussplan-hgmh');
        $next_run_formatted = $next_run ? date('d.m.Y H:i', strtotime($next_run)) : __('Nicht geplant', 'abschussplan-hgmh');

        // Translate report type
        $report_type_labels = [
            'seasonal' => __('Saisonbericht', 'abschussplan-hgmh'),
            'date_range' => __('Zeitraumbericht', 'abschussplan-hgmh'),
            'compliance' => __('Compliance-Bericht', 'abschussplan-hgmh'),
            'trend' => __('Trendanalyse', 'abschussplan-hgmh')
        ];
        $report_type_label = isset($report_type_labels[$report_type]) ? $report_type_labels[$report_type] : $report_type;

        // Translate frequency
        $frequency_labels = [
            'daily' => __('Täglich', 'abschussplan-hgmh'),
            'weekly' => __('Wöchentlich', 'abschussplan-hgmh'),
            'monthly' => __('Monatlich', 'abschussplan-hgmh')
        ];
        $frequency_label = isset($frequency_labels[$frequency]) ? $frequency_labels[$frequency] : $frequency;
        ?>
        <div class="ahgmh-schedule-item <?php echo esc_attr($status_class); ?>" data-schedule-id="<?php echo esc_attr($schedule_id); ?>">
            <div class="schedule-header">
                <div class="schedule-title">
                    <h3><?php echo esc_html($name); ?></h3>
                    <span class="schedule-status badge-<?php echo $enabled ? 'success' : 'secondary'; ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>
                </div>
                <div class="schedule-actions">
                    <button type="button" class="button button-small btn-view-history"
                            data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                            title="<?php echo esc_attr__('Verlauf anzeigen', 'abschussplan-hgmh'); ?>">
                        <span class="dashicons dashicons-chart-line"></span>
                    </button>
                    <button type="button" class="button button-small btn-edit-schedule"
                            data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                            title="<?php echo esc_attr__('Bearbeiten', 'abschussplan-hgmh'); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <label class="schedule-toggle">
                        <input type="checkbox" class="schedule-enabled-toggle"
                               data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                               <?php checked($enabled); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <button type="button" class="button button-small button-link-delete btn-delete-schedule"
                            data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                            title="<?php echo esc_attr__('Löschen', 'abschussplan-hgmh'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>

            <div class="schedule-details">
                <div class="schedule-detail-row">
                    <span class="detail-label"><?php echo esc_html__('Berichtstyp:', 'abschussplan-hgmh'); ?></span>
                    <span class="detail-value"><?php echo esc_html($report_type_label); ?></span>
                </div>
                <div class="schedule-detail-row">
                    <span class="detail-label"><?php echo esc_html__('Häufigkeit:', 'abschussplan-hgmh'); ?></span>
                    <span class="detail-value"><?php echo esc_html($frequency_label); ?></span>
                </div>
                <div class="schedule-detail-row">
                    <span class="detail-label"><?php echo esc_html__('Empfänger:', 'abschussplan-hgmh'); ?></span>
                    <span class="detail-value"><?php echo esc_html(count($recipients)); ?>
                        (<?php echo esc_html(implode(', ', array_slice($recipients, 0, 2))); ?><?php if (count($recipients) > 2) echo '...'; ?>)
                    </span>
                </div>
                <div class="schedule-detail-row">
                    <span class="detail-label"><?php echo esc_html__('Letzte Ausführung:', 'abschussplan-hgmh'); ?></span>
                    <span class="detail-value"><?php echo esc_html($last_run_formatted); ?></span>
                </div>
                <div class="schedule-detail-row">
                    <span class="detail-label"><?php echo esc_html__('Nächste Ausführung:', 'abschussplan-hgmh'); ?></span>
                    <span class="detail-value"><?php echo esc_html($next_run_formatted); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render schedule form
     *
     * @param array $species_list Species list
     * @param array $meldegruppen Meldegruppen list
     */
    private function render_schedule_form($species_list, $meldegruppen) {
        ?>
        <div id="schedule-form-modal" class="ahgmh-modal" style="display: none;">
            <div class="ahgmh-modal-content">
                <div class="ahgmh-modal-header">
                    <h2 id="schedule-form-title"><?php echo esc_html__('Neuer Zeitplan', 'abschussplan-hgmh'); ?></h2>
                    <button type="button" class="ahgmh-modal-close">&times;</button>
                </div>

                <div class="ahgmh-modal-body">
                    <form id="schedule-form">
                        <input type="hidden" id="schedule-id" name="schedule_id" value="">

                        <!-- Basic Info -->
                        <div class="form-section">
                            <h3><?php echo esc_html__('Grundinformationen', 'abschussplan-hgmh'); ?></h3>

                            <div class="form-field">
                                <label for="schedule-name"><?php echo esc_html__('Name des Zeitplans', 'abschussplan-hgmh'); ?> <span class="required">*</span></label>
                                <input type="text" id="schedule-name" name="name" class="regular-text" required>
                            </div>

                            <div class="form-field">
                                <label for="schedule-report-type"><?php echo esc_html__('Berichtstyp', 'abschussplan-hgmh'); ?> <span class="required">*</span></label>
                                <select id="schedule-report-type" name="report_type" required>
                                    <option value="seasonal"><?php echo esc_html__('Saisonbericht', 'abschussplan-hgmh'); ?></option>
                                    <option value="date_range"><?php echo esc_html__('Zeitraumbericht', 'abschussplan-hgmh'); ?></option>
                                    <option value="compliance"><?php echo esc_html__('Compliance-Bericht', 'abschussplan-hgmh'); ?></option>
                                    <option value="trend"><?php echo esc_html__('Trendanalyse', 'abschussplan-hgmh'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Schedule Settings -->
                        <div class="form-section">
                            <h3><?php echo esc_html__('Zeitplan-Einstellungen', 'abschussplan-hgmh'); ?></h3>

                            <div class="form-field">
                                <label for="schedule-frequency"><?php echo esc_html__('Häufigkeit', 'abschussplan-hgmh'); ?> <span class="required">*</span></label>
                                <select id="schedule-frequency" name="frequency" required>
                                    <option value="daily"><?php echo esc_html__('Täglich', 'abschussplan-hgmh'); ?></option>
                                    <option value="weekly"><?php echo esc_html__('Wöchentlich', 'abschussplan-hgmh'); ?></option>
                                    <option value="monthly"><?php echo esc_html__('Monatlich', 'abschussplan-hgmh'); ?></option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="schedule-time"><?php echo esc_html__('Uhrzeit (HH:MM)', 'abschussplan-hgmh'); ?></label>
                                <input type="time" id="schedule-time" name="time" value="00:00">
                            </div>

                            <div class="form-field" id="field-day-of-week" style="display: none;">
                                <label for="schedule-day-of-week"><?php echo esc_html__('Wochentag', 'abschussplan-hgmh'); ?></label>
                                <select id="schedule-day-of-week" name="day_of_week">
                                    <option value="0"><?php echo esc_html__('Sonntag', 'abschussplan-hgmh'); ?></option>
                                    <option value="1" selected><?php echo esc_html__('Montag', 'abschussplan-hgmh'); ?></option>
                                    <option value="2"><?php echo esc_html__('Dienstag', 'abschussplan-hgmh'); ?></option>
                                    <option value="3"><?php echo esc_html__('Mittwoch', 'abschussplan-hgmh'); ?></option>
                                    <option value="4"><?php echo esc_html__('Donnerstag', 'abschussplan-hgmh'); ?></option>
                                    <option value="5"><?php echo esc_html__('Freitag', 'abschussplan-hgmh'); ?></option>
                                    <option value="6"><?php echo esc_html__('Samstag', 'abschussplan-hgmh'); ?></option>
                                </select>
                            </div>

                            <div class="form-field" id="field-day-of-month" style="display: none;">
                                <label for="schedule-day-of-month"><?php echo esc_html__('Tag des Monats (1-28)', 'abschussplan-hgmh'); ?></label>
                                <input type="number" id="schedule-day-of-month" name="day_of_month" min="1" max="28" value="1">
                            </div>
                        </div>

                        <!-- Recipients -->
                        <div class="form-section">
                            <h3><?php echo esc_html__('Empfänger', 'abschussplan-hgmh'); ?></h3>

                            <div class="form-field">
                                <label for="schedule-recipients"><?php echo esc_html__('E-Mail-Adressen (eine pro Zeile)', 'abschussplan-hgmh'); ?> <span class="required">*</span></label>
                                <textarea id="schedule-recipients" name="recipients" rows="5" class="large-text" required
                                          placeholder="beispiel@domain.de"></textarea>
                                <p class="description">
                                    <?php echo esc_html__('Geben Sie eine E-Mail-Adresse pro Zeile ein.', 'abschussplan-hgmh'); ?>
                                </p>
                            </div>

                            <div class="form-field">
                                <button type="button" class="button button-secondary" id="btn-test-email">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php echo esc_html__('Test-E-Mail senden', 'abschussplan-hgmh'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="form-section">
                            <h3><?php echo esc_html__('Filter (optional)', 'abschussplan-hgmh'); ?></h3>

                            <div class="form-field">
                                <label for="schedule-species"><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></label>
                                <select id="schedule-species" name="species">
                                    <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                                    <?php foreach ($species_list as $species): ?>
                                        <option value="<?php echo esc_attr($species); ?>"><?php echo esc_html($species); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="schedule-meldegruppe"><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></label>
                                <select id="schedule-meldegruppe" name="meldegruppe">
                                    <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                                    <?php foreach ($meldegruppen as $meldegruppe): ?>
                                        <option value="<?php echo esc_attr($meldegruppe); ?>"><?php echo esc_html($meldegruppe); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="form-section">
                            <div class="form-field">
                                <label>
                                    <input type="checkbox" id="schedule-enabled" name="enabled" value="1" checked>
                                    <?php echo esc_html__('Zeitplan aktivieren', 'abschussplan-hgmh'); ?>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="ahgmh-modal-footer">
                    <button type="button" class="button button-secondary ahgmh-modal-close">
                        <?php echo esc_html__('Abbrechen', 'abschussplan-hgmh'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="btn-test-schedule">
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo esc_html__('Konfiguration testen', 'abschussplan-hgmh'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="btn-save-schedule">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo esc_html__('Zeitplan speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render inline styles
     */
    private function render_styles() {
        ?>
        <style>
            .ahgmh-schedule-settings-page {
                max-width: 1400px;
            }

            .ahgmh-page-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 23px;
                font-weight: 400;
                margin: 0 0 20px;
                padding: 9px 0 4px;
                line-height: 1.3;
            }

            .ahgmh-schedule-container {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            /* Statistics */
            .ahgmh-stats-section {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }

            .ahgmh-stat-card {
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 20px;
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .ahgmh-stat-card .dashicons {
                font-size: 32px;
                width: 32px;
                height: 32px;
                color: #2271b1;
            }

            .ahgmh-stat-card .stat-value {
                font-size: 24px;
                font-weight: 600;
                color: #1d2327;
            }

            .ahgmh-stat-card .stat-label {
                font-size: 13px;
                color: #646970;
            }

            /* Schedules List */
            .ahgmh-schedules-list-section {
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 20px;
            }

            .ahgmh-section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .ahgmh-section-header h2 {
                margin: 0;
            }

            .ahgmh-no-schedules {
                text-align: center;
                padding: 40px 20px;
                color: #646970;
            }

            .ahgmh-schedules-list {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .ahgmh-schedule-item {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                background: #fafafa;
                transition: all 0.2s ease;
            }

            .ahgmh-schedule-item:hover {
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }

            .ahgmh-schedule-item.status-disabled {
                opacity: 0.7;
            }

            .schedule-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .schedule-title {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .schedule-title h3 {
                margin: 0;
                font-size: 16px;
            }

            .schedule-status {
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }

            .badge-success {
                background: #d4edda;
                color: #155724;
            }

            .badge-secondary {
                background: #e2e3e5;
                color: #383d41;
            }

            .schedule-actions {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .schedule-toggle {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
            }

            .schedule-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }

            .toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            .schedule-toggle input:checked + .toggle-slider {
                background-color: #2271b1;
            }

            .schedule-toggle input:checked + .toggle-slider:before {
                transform: translateX(20px);
            }

            .schedule-details {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 10px;
            }

            .schedule-detail-row {
                display: flex;
                gap: 8px;
            }

            .detail-label {
                font-weight: 600;
                color: #646970;
            }

            .detail-value {
                color: #1d2327;
            }

            /* Modal */
            .ahgmh-modal {
                display: none;
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
            }

            .ahgmh-modal-content {
                background-color: #fff;
                margin: 5% auto;
                padding: 0;
                border: 1px solid #ccc;
                border-radius: 4px;
                width: 90%;
                max-width: 800px;
                max-height: 85vh;
                overflow-y: auto;
            }

            .ahgmh-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
                border-bottom: 1px solid #ddd;
            }

            .ahgmh-modal-header h2 {
                margin: 0;
            }

            .ahgmh-modal-close {
                background: none;
                border: none;
                font-size: 28px;
                font-weight: bold;
                color: #aaa;
                cursor: pointer;
            }

            .ahgmh-modal-close:hover {
                color: #000;
            }

            .ahgmh-modal-body {
                padding: 20px;
            }

            .ahgmh-modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                padding: 20px;
                border-top: 1px solid #ddd;
            }

            /* Form */
            .form-section {
                margin-bottom: 30px;
            }

            .form-section h3 {
                margin-top: 0;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }

            .form-field {
                margin-bottom: 15px;
            }

            .form-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }

            .form-field .required {
                color: #d63638;
            }

            .form-field input[type="text"],
            .form-field input[type="time"],
            .form-field input[type="number"],
            .form-field select,
            .form-field textarea {
                width: 100%;
                max-width: 100%;
            }

            .form-field .description {
                margin-top: 5px;
                font-size: 12px;
                color: #646970;
            }

            /* History */
            .ahgmh-schedule-history-section {
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 20px;
            }

            .ahgmh-history-placeholder {
                text-align: center;
                color: #646970;
                padding: 20px;
            }

            .ahgmh-history-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .ahgmh-history-item {
                border-left: 3px solid #ddd;
                padding: 10px 15px;
                background: #fafafa;
            }

            .ahgmh-history-item.success {
                border-left-color: #46b450;
            }

            .ahgmh-history-item.error {
                border-left-color: #d63638;
            }

            .history-time {
                font-size: 12px;
                color: #646970;
            }

            .history-status {
                font-weight: 600;
                margin-bottom: 5px;
            }

            .history-details {
                font-size: 13px;
                color: #646970;
            }

            /* Responsive */
            @media screen and (max-width: 782px) {
                .ahgmh-stats-section {
                    grid-template-columns: 1fr;
                }

                .schedule-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }

                .schedule-details {
                    grid-template-columns: 1fr;
                }

                .ahgmh-modal-content {
                    width: 95%;
                    margin: 2% auto;
                }
            }
        </style>
        <?php
    }

    /**
     * Render inline scripts
     */
    private function render_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            'use strict';

            // Add new schedule button
            $('#btn-add-new-schedule').on('click', function() {
                openScheduleForm();
            });

            // Edit schedule button
            $(document).on('click', '.btn-edit-schedule', function() {
                const scheduleId = $(this).data('schedule-id');
                const scheduleItem = $(this).closest('.ahgmh-schedule-item');
                loadScheduleForEdit(scheduleId, scheduleItem);
            });

            // Delete schedule button
            $(document).on('click', '.btn-delete-schedule', function() {
                if (!confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie diesen Zeitplan löschen möchten?', 'abschussplan-hgmh')); ?>')) {
                    return;
                }

                const scheduleId = $(this).data('schedule-id');
                deleteSchedule(scheduleId);
            });

            // Toggle schedule enabled/disabled
            $(document).on('change', '.schedule-enabled-toggle', function() {
                const scheduleId = $(this).data('schedule-id');
                const enabled = $(this).is(':checked');
                toggleSchedule(scheduleId, enabled);
            });

            // View history button
            $(document).on('click', '.btn-view-history', function() {
                const scheduleId = $(this).data('schedule-id');
                loadScheduleHistory(scheduleId);
            });

            // Close modal
            $('.ahgmh-modal-close').on('click', function() {
                closeScheduleForm();
            });

            // Click outside modal to close
            $('.ahgmh-modal').on('click', function(e) {
                if (e.target === this) {
                    closeScheduleForm();
                }
            });

            // Frequency change - show/hide day fields
            $('#schedule-frequency').on('change', function() {
                const frequency = $(this).val();
                $('#field-day-of-week').toggle(frequency === 'weekly');
                $('#field-day-of-month').toggle(frequency === 'monthly');
            });

            // Save schedule button
            $('#btn-save-schedule').on('click', function() {
                saveSchedule();
            });

            // Test schedule button
            $('#btn-test-schedule').on('click', function() {
                testSchedule();
            });

            // Test email button
            $('#btn-test-email').on('click', function() {
                sendTestEmail();
            });

            /**
             * Open schedule form
             */
            function openScheduleForm(isEdit = false) {
                $('#schedule-form')[0].reset();
                $('#schedule-id').val('');
                $('#schedule-form-title').text(isEdit ?
                    '<?php echo esc_js(__('Zeitplan bearbeiten', 'abschussplan-hgmh')); ?>' :
                    '<?php echo esc_js(__('Neuer Zeitplan', 'abschussplan-hgmh')); ?>'
                );
                $('#schedule-form-modal').fadeIn(200);
            }

            /**
             * Close schedule form
             */
            function closeScheduleForm() {
                $('#schedule-form-modal').fadeOut(200);
            }

            /**
             * Load schedule for editing
             */
            function loadScheduleForEdit(scheduleId, scheduleItem) {
                // This would need to load the full schedule data via AJAX
                // For now, we'll just open the form
                openScheduleForm(true);
                $('#schedule-id').val(scheduleId);
            }

            /**
             * Save schedule (create or update)
             */
            function saveSchedule() {
                const scheduleId = $('#schedule-id').val();
                const isUpdate = scheduleId !== '';

                // Collect form data
                const formData = new FormData($('#schedule-form')[0]);
                formData.append('action', isUpdate ? 'ahgmh_update_schedule' : 'ahgmh_create_schedule');
                formData.append('nonce', ahgmh_admin.nonce);

                if (isUpdate) {
                    formData.append('schedule_id', scheduleId);
                }

                // Parse recipients textarea into array
                const recipientsText = $('#schedule-recipients').val();
                const recipients = recipientsText.split('\n').map(r => r.trim()).filter(r => r);
                formData.delete('recipients');
                recipients.forEach(recipient => {
                    formData.append('recipients[]', recipient);
                });

                // Show loading
                const $btn = $('#btn-save-schedule');
                const originalText = $btn.html();
                $btn.html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Speichert...', 'abschussplan-hgmh')); ?>').prop('disabled', true);

                $.ajax({
                    url: ahgmh_admin.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            closeScheduleForm();
                            location.reload(); // Reload to show updated schedule
                        } else {
                            alert('<?php echo esc_js(__('Fehler:', 'abschussplan-hgmh')); ?> ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')); ?>');
                        console.error('Error:', error);
                    },
                    complete: function() {
                        $btn.html(originalText).prop('disabled', false);
                    }
                });
            }

            /**
             * Delete schedule
             */
            function deleteSchedule(scheduleId) {
                $.ajax({
                    url: ahgmh_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_delete_schedule',
                        nonce: ahgmh_admin.nonce,
                        schedule_id: scheduleId
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('<?php echo esc_js(__('Fehler:', 'abschussplan-hgmh')); ?> ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')); ?>');
                        console.error('Error:', error);
                    }
                });
            }

            /**
             * Toggle schedule enabled/disabled
             */
            function toggleSchedule(scheduleId, enabled) {
                $.ajax({
                    url: ahgmh_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_toggle_schedule',
                        nonce: ahgmh_admin.nonce,
                        schedule_id: scheduleId,
                        enabled: enabled
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update UI without reload
                            const $item = $('.ahgmh-schedule-item[data-schedule-id="' + scheduleId + '"]');
                            if (enabled) {
                                $item.removeClass('status-disabled').addClass('status-enabled');
                                $item.find('.schedule-status').removeClass('badge-secondary').addClass('badge-success').text('<?php echo esc_js(__('Aktiv', 'abschussplan-hgmh')); ?>');
                            } else {
                                $item.removeClass('status-enabled').addClass('status-disabled');
                                $item.find('.schedule-status').removeClass('badge-success').addClass('badge-secondary').text('<?php echo esc_js(__('Inaktiv', 'abschussplan-hgmh')); ?>');
                            }
                        } else {
                            alert('<?php echo esc_js(__('Fehler:', 'abschussplan-hgmh')); ?> ' + response.data);
                            // Revert toggle
                            $('.schedule-enabled-toggle[data-schedule-id="' + scheduleId + '"]').prop('checked', !enabled);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')); ?>');
                        // Revert toggle
                        $('.schedule-enabled-toggle[data-schedule-id="' + scheduleId + '"]').prop('checked', !enabled);
                        console.error('Error:', error);
                    }
                });
            }

            /**
             * Test schedule configuration
             */
            function testSchedule() {
                const formData = new FormData($('#schedule-form')[0]);
                formData.append('action', 'ahgmh_test_schedule');
                formData.append('nonce', ahgmh_admin.nonce);

                // Parse recipients
                const recipientsText = $('#schedule-recipients').val();
                const recipients = recipientsText.split('\n').map(r => r.trim()).filter(r => r);
                formData.delete('recipients');
                recipients.forEach(recipient => {
                    formData.append('recipients[]', recipient);
                });

                $.ajax({
                    url: ahgmh_admin.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            const result = response.data.test_results;
                            alert(
                                '<?php echo esc_js(__('Konfiguration ist gültig!', 'abschussplan-hgmh')); ?>\n\n' +
                                '<?php echo esc_js(__('Nächste Ausführung:', 'abschussplan-hgmh')); ?> ' + result.next_run + '\n' +
                                '<?php echo esc_js(__('Zeitraum:', 'abschussplan-hgmh')); ?> ' + result.date_range.start + ' - ' + result.date_range.end + '\n' +
                                '<?php echo esc_js(__('Empfänger:', 'abschussplan-hgmh')); ?> ' + result.recipients_count
                            );
                        } else {
                            alert('<?php echo esc_js(__('Fehler:', 'abschussplan-hgmh')); ?> ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')); ?>');
                        console.error('Error:', error);
                    }
                });
            }

            /**
             * Send test email
             */
            function sendTestEmail() {
                const recipientsText = $('#schedule-recipients').val();
                const recipients = recipientsText.split('\n').map(r => r.trim()).filter(r => r);

                if (recipients.length === 0) {
                    alert('<?php echo esc_js(__('Bitte geben Sie mindestens eine E-Mail-Adresse ein.', 'abschussplan-hgmh')); ?>');
                    return;
                }

                const recipient = recipients[0]; // Send to first recipient

                $.ajax({
                    url: ahgmh_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_send_test_email',
                        nonce: ahgmh_admin.nonce,
                        recipient: recipient
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                        } else {
                            alert('<?php echo esc_js(__('Fehler:', 'abschussplan-hgmh')); ?> ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')); ?>');
                        console.error('Error:', error);
                    }
                });
            }

            /**
             * Load schedule history
             */
            function loadScheduleHistory(scheduleId) {
                $.ajax({
                    url: ahgmh_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_get_schedule_history',
                        nonce: ahgmh_admin.nonce,
                        schedule_id: scheduleId,
                        limit: 20
                    },
                    success: function(response) {
                        if (response.success) {
                            renderScheduleHistory(response.data.history);
                        } else {
                            alert('<?php echo esc_js(__('Fehler:', 'abschussplan-hgmh')); ?> ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')); ?>');
                        console.error('Error:', error);
                    }
                });
            }

            /**
             * Render schedule history
             */
            function renderScheduleHistory(history) {
                const $container = $('#schedule-history-container');

                if (history.length === 0) {
                    $container.html('<p class="ahgmh-history-placeholder"><?php echo esc_js(__('Keine Ausführungen gefunden.', 'abschussplan-hgmh')); ?></p>');
                    return;
                }

                let html = '<div class="ahgmh-history-list">';
                history.forEach(function(item) {
                    const isSuccess = item.results && item.results.success;
                    const statusClass = isSuccess ? 'success' : 'error';
                    const statusText = isSuccess ?
                        '<?php echo esc_js(__('Erfolgreich', 'abschussplan-hgmh')); ?>' :
                        '<?php echo esc_js(__('Fehlgeschlagen', 'abschussplan-hgmh')); ?>';

                    html += '<div class="ahgmh-history-item ' + statusClass + '">';
                    html += '<div class="history-time">' + item.executed_at + '</div>';
                    html += '<div class="history-status">' + statusText + '</div>';

                    if (item.results) {
                        if (item.results.success && item.results.results) {
                            const sent = item.results.results.successful || 0;
                            const failed = item.results.results.failed || 0;
                            html += '<div class="history-details">';
                            html += '<?php echo esc_js(__('Gesendet:', 'abschussplan-hgmh')); ?> ' + sent + ', ';
                            html += '<?php echo esc_js(__('Fehlgeschlagen:', 'abschussplan-hgmh')); ?> ' + failed;
                            html += '</div>';
                        } else if (item.results.error) {
                            html += '<div class="history-details">' + item.results.error + '</div>';
                        }
                    }

                    html += '</div>';
                });
                html += '</div>';

                $container.html(html);
            }

            // Initialize frequency-based fields
            $('#schedule-frequency').trigger('change');
        });
        </script>
        <?php
    }
}
