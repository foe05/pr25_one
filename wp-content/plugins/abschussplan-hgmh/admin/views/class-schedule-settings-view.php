<?php
/**
 * Schedule Settings View
 * Renders the scheduled reports configuration interface
 *
 * Uses WordPress-native postbox patterns. Inline CSS/JS removed;
 * styling relies on WP core classes and minimal inline styles.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Schedule_Settings_View {

    /**
     * Render main schedule settings page (embedded in a tab context)
     *
     * @param array $data Page data including schedules, species_list, meldegruppen, stats
     */
    public function render_settings_page($data) {
        $schedules = isset($data['schedules']) ? $data['schedules'] : [];
        $species_list = isset($data['species_list']) ? $data['species_list'] : [];
        $meldegruppen = isset($data['meldegruppen']) ? $data['meldegruppen'] : [];
        $stats = isset($data['stats']) ? $data['stats'] : [];
        ?>
        <div class="ahgmh-schedule-settings-page" style="margin-top: 20px;">
            <!-- Statistics Cards -->
            <?php $this->render_statistics($stats); ?>

            <!-- Existing Schedules Section -->
            <div class="postbox" style="margin-bottom: 20px;">
                <div class="postbox-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="hndle" style="border: 0;"><?php echo esc_html__('Bestehende Zeitplaene', 'abschussplan-hgmh'); ?></h2>
                    <div style="padding-right: 12px;">
                        <button type="button" class="button button-primary" id="btn-add-new-schedule">
                            <span class="dashicons dashicons-plus-alt" style="margin-top: 4px;"></span>
                            <?php echo esc_html__('Neuer Zeitplan', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>
                </div>
                <div class="inside">
                    <?php $this->render_schedules_list($schedules); ?>
                </div>
            </div>

            <!-- Schedule Form (hidden by default, shown when adding/editing) -->
            <?php $this->render_schedule_form($species_list, $meldegruppen); ?>

            <!-- Schedule History Section -->
            <div class="postbox" style="margin-bottom: 20px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html__('Ausfuehrungsverlauf', 'abschussplan-hgmh'); ?></h2>
                </div>
                <div class="inside" id="schedule-history-container">
                    <p style="text-align: center; color: #646970; padding: 20px;">
                        <?php echo esc_html__('Waehlen Sie einen Zeitplan aus, um dessen Verlauf anzuzeigen.', 'abschussplan-hgmh'); ?>
                    </p>
                </div>
            </div>
        </div>
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="postbox" style="margin: 0; padding: 15px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="dashicons dashicons-calendar-alt" style="font-size: 28px; width: 28px; height: 28px; color: #2271b1;"></span>
                    <div>
                        <div style="font-size: 22px; font-weight: 600; color: #1d2327;"><?php echo esc_html($total); ?></div>
                        <div style="font-size: 13px; color: #646970;"><?php echo esc_html__('Zeitplaene', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
            </div>
            <div class="postbox" style="margin: 0; padding: 15px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 28px; width: 28px; height: 28px; color: #00a32a;"></span>
                    <div>
                        <div style="font-size: 22px; font-weight: 600; color: #1d2327;"><?php echo esc_html($enabled); ?></div>
                        <div style="font-size: 13px; color: #646970;"><?php echo esc_html__('Aktiv', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
            </div>
            <div class="postbox" style="margin: 0; padding: 15px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="dashicons dashicons-backup" style="font-size: 28px; width: 28px; height: 28px; color: #2271b1;"></span>
                    <div>
                        <div style="font-size: 22px; font-weight: 600; color: #1d2327;"><?php echo esc_html($total_executions); ?></div>
                        <div style="font-size: 13px; color: #646970;"><?php echo esc_html__('Ausfuehrungen', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>
            </div>
            <div class="postbox" style="margin: 0; padding: 15px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="dashicons dashicons-thumbs-up" style="font-size: 28px; width: 28px; height: 28px; color: #00a32a;"></span>
                    <div>
                        <div style="font-size: 22px; font-weight: 600; color: #1d2327;"><?php echo esc_html($successful); ?></div>
                        <div style="font-size: 13px; color: #646970;"><?php echo esc_html__('Erfolgreich', 'abschussplan-hgmh'); ?></div>
                    </div>
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
            <div style="text-align: center; padding: 40px 20px; color: #646970;">
                <span class="dashicons dashicons-clock" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 15px;"></span>
                <p><?php echo esc_html__('Noch keine Zeitplaene konfiguriert.', 'abschussplan-hgmh'); ?></p>
                <p><?php echo esc_html__('Klicken Sie auf "Neuer Zeitplan", um Ihren ersten geplanten Bericht zu erstellen.', 'abschussplan-hgmh'); ?></p>
            </div>
            <?php
            return;
        }

        foreach ($schedules as $schedule) {
            $this->render_schedule_item($schedule);
        }
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

        $status_bg = $enabled ? '#00a32a' : '#646970';
        $status_text = $enabled ? __('Aktiv', 'abschussplan-hgmh') : __('Inaktiv', 'abschussplan-hgmh');

        $last_run_formatted = $last_run ? date('d.m.Y H:i', strtotime($last_run)) : __('Noch nie', 'abschussplan-hgmh');
        $next_run_formatted = $next_run ? date('d.m.Y H:i', strtotime($next_run)) : __('Nicht geplant', 'abschussplan-hgmh');

        $report_type_labels = [
            'seasonal' => __('Saisonbericht', 'abschussplan-hgmh'),
            'date_range' => __('Zeitraumbericht', 'abschussplan-hgmh'),
            'compliance' => __('Compliance-Bericht', 'abschussplan-hgmh'),
            'trend' => __('Trendanalyse', 'abschussplan-hgmh'),
        ];
        $report_type_label = isset($report_type_labels[$report_type]) ? $report_type_labels[$report_type] : $report_type;

        $frequency_labels = [
            'daily' => __('Taeglich', 'abschussplan-hgmh'),
            'weekly' => __('Woechentlich', 'abschussplan-hgmh'),
            'monthly' => __('Monatlich', 'abschussplan-hgmh'),
        ];
        $frequency_label = isset($frequency_labels[$frequency]) ? $frequency_labels[$frequency] : $frequency;
        ?>
        <div class="ahgmh-schedule-item" data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
             style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 12px; background: #fafafa;<?php echo !$enabled ? ' opacity: 0.7;' : ''; ?>">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h3 style="margin: 0; font-size: 15px;"><?php echo esc_html($name); ?></h3>
                    <span class="schedule-status" style="display: inline-block; background: <?php echo esc_attr($status_bg); ?>; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                        <?php echo esc_html($status_text); ?>
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button" class="button button-small btn-view-history"
                            data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                            title="<?php echo esc_attr__('Verlauf anzeigen', 'abschussplan-hgmh'); ?>">
                        <span class="dashicons dashicons-chart-line" style="margin-top: 3px;"></span>
                    </button>
                    <button type="button" class="button button-small btn-edit-schedule"
                            data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                            title="<?php echo esc_attr__('Bearbeiten', 'abschussplan-hgmh'); ?>">
                        <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span>
                    </button>
                    <label style="position: relative; display: inline-block; width: 36px; height: 20px; margin: 0 4px;">
                        <input type="checkbox" class="schedule-enabled-toggle"
                               data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                               <?php checked($enabled); ?>
                               style="opacity: 0; width: 0; height: 0;">
                        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $enabled ? '#2271b1' : '#ccc'; ?>; transition: .3s; border-radius: 20px;"></span>
                    </label>
                    <button type="button" class="button button-small button-link-delete btn-delete-schedule"
                            data-schedule-id="<?php echo esc_attr($schedule_id); ?>"
                            title="<?php echo esc_attr__('Loeschen', 'abschussplan-hgmh'); ?>">
                        <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                    </button>
                </div>
            </div>

            <!-- Details -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 8px; font-size: 13px;">
                <div>
                    <span style="font-weight: 600; color: #646970;"><?php echo esc_html__('Berichtstyp:', 'abschussplan-hgmh'); ?></span>
                    <span><?php echo esc_html($report_type_label); ?></span>
                </div>
                <div>
                    <span style="font-weight: 600; color: #646970;"><?php echo esc_html__('Haeufigkeit:', 'abschussplan-hgmh'); ?></span>
                    <span><?php echo esc_html($frequency_label); ?></span>
                </div>
                <div>
                    <span style="font-weight: 600; color: #646970;"><?php echo esc_html__('Empfaenger:', 'abschussplan-hgmh'); ?></span>
                    <span><?php echo esc_html(count($recipients)); ?>
                        (<?php echo esc_html(implode(', ', array_slice($recipients, 0, 2))); ?><?php if (count($recipients) > 2) echo '...'; ?>)
                    </span>
                </div>
                <div>
                    <span style="font-weight: 600; color: #646970;"><?php echo esc_html__('Letzte Ausfuehrung:', 'abschussplan-hgmh'); ?></span>
                    <span><?php echo esc_html($last_run_formatted); ?></span>
                </div>
                <div>
                    <span style="font-weight: 600; color: #646970;"><?php echo esc_html__('Naechste Ausfuehrung:', 'abschussplan-hgmh'); ?></span>
                    <span><?php echo esc_html($next_run_formatted); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render schedule form (modal)
     *
     * @param array $species_list Species list
     * @param array $meldegruppen Meldegruppen list
     */
    private function render_schedule_form($species_list, $meldegruppen) {
        ?>
        <div id="schedule-form-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div style="background-color: #fff; margin: 5% auto; border: 1px solid #ccc; border-radius: 4px; width: 90%; max-width: 800px; max-height: 85vh; overflow-y: auto;">
                <!-- Modal Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #ddd;">
                    <h2 id="schedule-form-title" style="margin: 0;"><?php echo esc_html__('Neuer Zeitplan', 'abschussplan-hgmh'); ?></h2>
                    <button type="button" class="ahgmh-modal-close" style="background: none; border: none; font-size: 24px; color: #646970; cursor: pointer;">&times;</button>
                </div>

                <!-- Modal Body -->
                <div style="padding: 20px;">
                    <form id="schedule-form">
                        <input type="hidden" id="schedule-id" name="schedule_id" value="">

                        <!-- Basic Info -->
                        <fieldset style="margin-bottom: 25px; padding-bottom: 20px; border: 0; border-bottom: 1px solid #f0f0f1;">
                            <legend style="font-weight: 600; font-size: 14px; margin-bottom: 10px;"><?php echo esc_html__('Grundinformationen', 'abschussplan-hgmh'); ?></legend>

                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="schedule-name"><?php echo esc_html__('Name des Zeitplans', 'abschussplan-hgmh'); ?> <span style="color: #d63638;">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" id="schedule-name" name="name" class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="schedule-report-type"><?php echo esc_html__('Berichtstyp', 'abschussplan-hgmh'); ?> <span style="color: #d63638;">*</span></label>
                                    </th>
                                    <td>
                                        <select id="schedule-report-type" name="report_type" required>
                                            <option value="seasonal"><?php echo esc_html__('Saisonbericht', 'abschussplan-hgmh'); ?></option>
                                            <option value="date_range"><?php echo esc_html__('Zeitraumbericht', 'abschussplan-hgmh'); ?></option>
                                            <option value="compliance"><?php echo esc_html__('Compliance-Bericht', 'abschussplan-hgmh'); ?></option>
                                            <option value="trend"><?php echo esc_html__('Trendanalyse', 'abschussplan-hgmh'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>

                        <!-- Schedule Settings -->
                        <fieldset style="margin-bottom: 25px; padding-bottom: 20px; border: 0; border-bottom: 1px solid #f0f0f1;">
                            <legend style="font-weight: 600; font-size: 14px; margin-bottom: 10px;"><?php echo esc_html__('Zeitplan-Einstellungen', 'abschussplan-hgmh'); ?></legend>

                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="schedule-frequency"><?php echo esc_html__('Haeufigkeit', 'abschussplan-hgmh'); ?> <span style="color: #d63638;">*</span></label>
                                    </th>
                                    <td>
                                        <select id="schedule-frequency" name="frequency" required>
                                            <option value="daily"><?php echo esc_html__('Taeglich', 'abschussplan-hgmh'); ?></option>
                                            <option value="weekly"><?php echo esc_html__('Woechentlich', 'abschussplan-hgmh'); ?></option>
                                            <option value="monthly"><?php echo esc_html__('Monatlich', 'abschussplan-hgmh'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="schedule-time"><?php echo esc_html__('Uhrzeit (HH:MM)', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <input type="time" id="schedule-time" name="time" value="00:00">
                                    </td>
                                </tr>
                                <tr id="field-day-of-week" style="display: none;">
                                    <th scope="row">
                                        <label for="schedule-day-of-week"><?php echo esc_html__('Wochentag', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <select id="schedule-day-of-week" name="day_of_week">
                                            <option value="0"><?php echo esc_html__('Sonntag', 'abschussplan-hgmh'); ?></option>
                                            <option value="1" selected><?php echo esc_html__('Montag', 'abschussplan-hgmh'); ?></option>
                                            <option value="2"><?php echo esc_html__('Dienstag', 'abschussplan-hgmh'); ?></option>
                                            <option value="3"><?php echo esc_html__('Mittwoch', 'abschussplan-hgmh'); ?></option>
                                            <option value="4"><?php echo esc_html__('Donnerstag', 'abschussplan-hgmh'); ?></option>
                                            <option value="5"><?php echo esc_html__('Freitag', 'abschussplan-hgmh'); ?></option>
                                            <option value="6"><?php echo esc_html__('Samstag', 'abschussplan-hgmh'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr id="field-day-of-month" style="display: none;">
                                    <th scope="row">
                                        <label for="schedule-day-of-month"><?php echo esc_html__('Tag des Monats (1-28)', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="schedule-day-of-month" name="day_of_month" min="1" max="28" value="1" class="small-text">
                                    </td>
                                </tr>
                            </table>
                        </fieldset>

                        <!-- Recipients -->
                        <fieldset style="margin-bottom: 25px; padding-bottom: 20px; border: 0; border-bottom: 1px solid #f0f0f1;">
                            <legend style="font-weight: 600; font-size: 14px; margin-bottom: 10px;"><?php echo esc_html__('Empfaenger', 'abschussplan-hgmh'); ?></legend>

                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="schedule-recipients"><?php echo esc_html__('E-Mail-Adressen', 'abschussplan-hgmh'); ?> <span style="color: #d63638;">*</span></label>
                                    </th>
                                    <td>
                                        <textarea id="schedule-recipients" name="recipients" rows="4" class="large-text" required
                                                  placeholder="beispiel@domain.de"></textarea>
                                        <p class="description">
                                            <?php echo esc_html__('Geben Sie eine E-Mail-Adresse pro Zeile ein.', 'abschussplan-hgmh'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"></th>
                                    <td>
                                        <button type="button" class="button" id="btn-test-email">
                                            <span class="dashicons dashicons-email" style="margin-top: 4px;"></span>
                                            <?php echo esc_html__('Test-E-Mail senden', 'abschussplan-hgmh'); ?>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>

                        <!-- Filters -->
                        <fieldset style="margin-bottom: 25px; padding-bottom: 20px; border: 0; border-bottom: 1px solid #f0f0f1;">
                            <legend style="font-weight: 600; font-size: 14px; margin-bottom: 10px;"><?php echo esc_html__('Filter (optional)', 'abschussplan-hgmh'); ?></legend>

                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="schedule-species"><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <select id="schedule-species" name="species">
                                            <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                                            <?php foreach ($species_list as $species): ?>
                                                <option value="<?php echo esc_attr($species); ?>"><?php echo esc_html($species); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="schedule-meldegruppe"><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <select id="schedule-meldegruppe" name="meldegruppe">
                                            <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                                            <?php foreach ($meldegruppen as $meldegruppe): ?>
                                                <option value="<?php echo esc_attr($meldegruppe); ?>"><?php echo esc_html($meldegruppe); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>

                        <!-- Status -->
                        <div style="margin-bottom: 15px;">
                            <label>
                                <input type="checkbox" id="schedule-enabled" name="enabled" value="1" checked>
                                <strong><?php echo esc_html__('Zeitplan aktivieren', 'abschussplan-hgmh'); ?></strong>
                            </label>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div style="display: flex; justify-content: flex-end; gap: 8px; padding: 15px 20px; border-top: 1px solid #ddd;">
                    <button type="button" class="button ahgmh-modal-close">
                        <?php echo esc_html__('Abbrechen', 'abschussplan-hgmh'); ?>
                    </button>
                    <button type="button" class="button" id="btn-test-schedule">
                        <span class="dashicons dashicons-yes" style="margin-top: 4px;"></span>
                        <?php echo esc_html__('Konfiguration testen', 'abschussplan-hgmh'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="btn-save-schedule">
                        <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span>
                        <?php echo esc_html__('Zeitplan speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
