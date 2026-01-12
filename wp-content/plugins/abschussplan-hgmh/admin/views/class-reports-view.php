<?php
/**
 * Reports View - Renders report generation interface with date pickers and filters
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Reports_View {

    /**
     * Render main reports page
     *
     * @param array $data Page data including species_list, meldegruppen, current_season, hunting_seasons
     */
    public function render_reports_page($data) {
        $species_list = isset($data['species_list']) ? $data['species_list'] : [];
        $meldegruppen = isset($data['meldegruppen']) ? $data['meldegruppen'] : [];
        $current_season = isset($data['current_season']) ? $data['current_season'] : [];
        $hunting_seasons = isset($data['hunting_seasons']) ? $data['hunting_seasons'] : [];
        ?>
        <div class="wrap ahgmh-reports-page">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php echo esc_html__('Berichte', 'abschussplan-hgmh'); ?>
            </h1>

            <div class="ahgmh-reports-container">
                <!-- Report Configuration Section -->
                <div class="ahgmh-reports-config">
                    <h2><?php echo esc_html__('Bericht konfigurieren', 'abschussplan-hgmh'); ?></h2>

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

                <!-- Report Preview Section -->
                <div class="ahgmh-reports-preview" id="report-preview-container">
                    <div class="ahgmh-reports-placeholder">
                        <span class="dashicons dashicons-media-document"></span>
                        <p><?php echo esc_html__('Konfigurieren Sie einen Bericht und klicken Sie auf "Vorschau anzeigen", um das Ergebnis zu sehen.', 'abschussplan-hgmh'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->render_styles(); ?>
        <?php $this->render_scripts(); ?>
        <?php
    }

    /**
     * Render report type selection
     */
    private function render_report_type_selection() {
        ?>
        <div class="ahgmh-config-section">
            <h3><?php echo esc_html__('Berichtstyp', 'abschussplan-hgmh'); ?></h3>
            <div class="ahgmh-report-types">
                <label class="ahgmh-report-type-option">
                    <input type="radio" name="report_type" value="seasonal" checked>
                    <div class="report-type-card">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <strong><?php echo esc_html__('Saisonbericht', 'abschussplan-hgmh'); ?></strong>
                        <p><?php echo esc_html__('Übersicht über eine komplette Jagdsaison', 'abschussplan-hgmh'); ?></p>
                    </div>
                </label>

                <label class="ahgmh-report-type-option">
                    <input type="radio" name="report_type" value="date_range">
                    <div class="report-type-card">
                        <span class="dashicons dashicons-clock"></span>
                        <strong><?php echo esc_html__('Zeitraumbericht', 'abschussplan-hgmh'); ?></strong>
                        <p><?php echo esc_html__('Bericht für einen benutzerdefinierten Zeitraum', 'abschussplan-hgmh'); ?></p>
                    </div>
                </label>

                <label class="ahgmh-report-type-option">
                    <input type="radio" name="report_type" value="compliance">
                    <div class="report-type-card">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <strong><?php echo esc_html__('Compliance-Bericht', 'abschussplan-hgmh'); ?></strong>
                        <p><?php echo esc_html__('Aktueller Status der Abschussplanerfüllung', 'abschussplan-hgmh'); ?></p>
                    </div>
                </label>

                <label class="ahgmh-report-type-option">
                    <input type="radio" name="report_type" value="trend">
                    <div class="report-type-card">
                        <span class="dashicons dashicons-chart-line"></span>
                        <strong><?php echo esc_html__('Trendanalyse', 'abschussplan-hgmh'); ?></strong>
                        <p><?php echo esc_html__('Vergleich und Trends über mehrere Saisons', 'abschussplan-hgmh'); ?></p>
                    </div>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Render quick season buttons
     *
     * @param array $current_season Current season info
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
        <div class="ahgmh-config-section ahgmh-quick-seasons" id="quick-seasons-section">
            <h3><?php echo esc_html__('Schnellauswahl', 'abschussplan-hgmh'); ?></h3>
            <div class="ahgmh-button-group">
                <button type="button" class="button button-secondary quick-season-btn"
                        data-season="<?php echo esc_attr($current_season_value); ?>">
                    <span class="dashicons dashicons-calendar"></span>
                    <?php echo esc_html__('Aktuelle Saison', 'abschussplan-hgmh'); ?>
                    <?php if (!empty($current_season_value)): ?>
                        (<?php echo esc_html($current_season_value); ?>)
                    <?php endif; ?>
                </button>

                <button type="button" class="button button-secondary quick-season-btn"
                        data-season="<?php echo esc_attr($previous_season_value); ?>">
                    <span class="dashicons dashicons-backup"></span>
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
        <div class="ahgmh-config-section ahgmh-date-range" id="date-range-section" style="display: none;">
            <h3><?php echo esc_html__('Zeitraum wählen', 'abschussplan-hgmh'); ?></h3>
            <div class="ahgmh-date-inputs">
                <div class="date-input-group">
                    <label for="start-date"><?php echo esc_html__('Startdatum:', 'abschussplan-hgmh'); ?></label>
                    <input type="date" id="start-date" name="start_date" class="regular-text">
                </div>
                <div class="date-input-group">
                    <label for="end-date"><?php echo esc_html__('Enddatum:', 'abschussplan-hgmh'); ?></label>
                    <input type="date" id="end-date" name="end_date" class="regular-text">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render season selector
     *
     * @param array $hunting_seasons Available hunting seasons
     */
    private function render_season_selector($hunting_seasons) {
        ?>
        <div class="ahgmh-config-section ahgmh-season-selector" id="season-selector-section">
            <h3><?php echo esc_html__('Jagdjahr wählen', 'abschussplan-hgmh'); ?></h3>
            <select id="season-select" name="season" class="regular-text">
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
     *
     * @param array $species_list Available species
     * @param array $meldegruppen Available meldegruppen
     */
    private function render_filters($species_list, $meldegruppen) {
        ?>
        <div class="ahgmh-config-section">
            <h3><?php echo esc_html__('Filter (optional)', 'abschussplan-hgmh'); ?></h3>
            <div class="ahgmh-filters-group">
                <div class="filter-item">
                    <label for="filter-species"><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?></label>
                    <select id="filter-species" name="species" class="regular-text">
                        <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($species_list as $species): ?>
                            <option value="<?php echo esc_attr($species); ?>">
                                <?php echo esc_html($species); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="filter-meldegruppe"><?php echo esc_html__('Meldegruppe:', 'abschussplan-hgmh'); ?></label>
                    <select id="filter-meldegruppe" name="meldegruppe" class="regular-text">
                        <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($meldegruppen as $mg): ?>
                            <option value="<?php echo esc_attr($mg); ?>">
                                <?php echo esc_html($mg); ?>
                            </option>
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
        <div class="ahgmh-config-section">
            <h3><?php echo esc_html__('Ausgabeformat', 'abschussplan-hgmh'); ?></h3>
            <div class="ahgmh-output-formats">
                <label class="ahgmh-output-format-option">
                    <input type="radio" name="output_format" value="preview" checked>
                    <span class="dashicons dashicons-visibility"></span>
                    <?php echo esc_html__('Vorschau anzeigen', 'abschussplan-hgmh'); ?>
                </label>

                <label class="ahgmh-output-format-option">
                    <input type="radio" name="output_format" value="csv">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php echo esc_html__('CSV herunterladen', 'abschussplan-hgmh'); ?>
                </label>

                <label class="ahgmh-output-format-option">
                    <input type="radio" name="output_format" value="pdf">
                    <span class="dashicons dashicons-pdf"></span>
                    <?php echo esc_html__('PDF herunterladen', 'abschussplan-hgmh'); ?>
                </label>

                <label class="ahgmh-output-format-option">
                    <input type="radio" name="output_format" value="email">
                    <span class="dashicons dashicons-email"></span>
                    <?php echo esc_html__('Per E-Mail senden', 'abschussplan-hgmh'); ?>
                </label>
            </div>

            <!-- Email recipient input (hidden by default) -->
            <div id="email-recipient-section" style="display: none; margin-top: 15px;">
                <label for="email-recipient"><?php echo esc_html__('E-Mail-Adresse:', 'abschussplan-hgmh'); ?></label>
                <input type="email" id="email-recipient" name="recipient" class="regular-text"
                       placeholder="<?php echo esc_attr__('empfaenger@beispiel.de', 'abschussplan-hgmh'); ?>">
            </div>
        </div>
        <?php
    }

    /**
     * Render action buttons
     */
    private function render_action_buttons() {
        ?>
        <div class="ahgmh-config-section ahgmh-actions">
            <button type="button" class="button button-primary button-large" id="generate-report-btn">
                <span class="dashicons dashicons-yes"></span>
                <?php echo esc_html__('Bericht erstellen', 'abschussplan-hgmh'); ?>
            </button>

            <button type="button" class="button button-secondary" id="reset-form-btn">
                <span class="dashicons dashicons-undo"></span>
                <?php echo esc_html__('Zurücksetzen', 'abschussplan-hgmh'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render inline styles
     */
    private function render_styles() {
        ?>
        <style>
            .ahgmh-reports-container {
                display: grid;
                grid-template-columns: 400px 1fr;
                gap: 20px;
                margin-top: 20px;
            }

            .ahgmh-reports-config {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
            }

            .ahgmh-reports-preview {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                min-height: 600px;
            }

            .ahgmh-reports-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: #999;
                text-align: center;
                padding: 40px;
            }

            .ahgmh-reports-placeholder .dashicons {
                font-size: 64px;
                width: 64px;
                height: 64px;
                margin-bottom: 20px;
            }

            .ahgmh-config-section {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid #f0f0f1;
            }

            .ahgmh-config-section:last-child {
                border-bottom: none;
            }

            .ahgmh-config-section h3 {
                margin: 0 0 15px 0;
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }

            .ahgmh-report-types {
                display: grid;
                gap: 10px;
            }

            .ahgmh-report-type-option {
                display: block;
                cursor: pointer;
            }

            .ahgmh-report-type-option input[type="radio"] {
                display: none;
            }

            .report-type-card {
                padding: 12px;
                border: 2px solid #dcdcde;
                border-radius: 4px;
                transition: all 0.2s;
            }

            .ahgmh-report-type-option input[type="radio"]:checked + .report-type-card {
                border-color: #2271b1;
                background-color: #f0f6fc;
            }

            .report-type-card:hover {
                border-color: #2271b1;
            }

            .report-type-card .dashicons {
                color: #2271b1;
                margin-bottom: 5px;
            }

            .report-type-card strong {
                display: block;
                margin-bottom: 5px;
                font-size: 13px;
            }

            .report-type-card p {
                margin: 0;
                font-size: 12px;
                color: #646970;
            }

            .ahgmh-button-group {
                display: flex;
                gap: 10px;
            }

            .ahgmh-button-group .button {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }

            .ahgmh-date-inputs {
                display: grid;
                gap: 15px;
            }

            .date-input-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                font-size: 13px;
            }

            .ahgmh-filters-group {
                display: grid;
                gap: 15px;
            }

            .filter-item label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                font-size: 13px;
            }

            .ahgmh-output-formats {
                display: grid;
                gap: 10px;
            }

            .ahgmh-output-format-option {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
            }

            .ahgmh-output-format-option:hover {
                border-color: #2271b1;
            }

            .ahgmh-output-format-option input[type="radio"]:checked {
                accent-color: #2271b1;
            }

            .ahgmh-actions {
                display: flex;
                gap: 10px;
            }

            .ahgmh-actions .button-large {
                flex: 1;
            }

            #report-preview-container.loading {
                opacity: 0.6;
                pointer-events: none;
            }

            .report-preview-content {
                animation: fadeIn 0.3s;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            .ahgmh-reports-error {
                padding: 15px;
                background: #fcf3f3;
                border-left: 4px solid #dc3232;
                margin-bottom: 15px;
            }

            .ahgmh-reports-success {
                padding: 15px;
                background: #f0f6fc;
                border-left: 4px solid #00a32a;
                margin-bottom: 15px;
            }

            @media (max-width: 1280px) {
                .ahgmh-reports-container {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }

    /**
     * Render inline JavaScript for form handling and AJAX operations
     */
    private function render_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var reportForm = {
                init: function() {
                    this.bindEvents();
                    this.updateFormVisibility();
                },

                bindEvents: function() {
                    // Report type change
                    $('input[name="report_type"]').on('change', this.updateFormVisibility.bind(this));

                    // Output format change
                    $('input[name="output_format"]').on('change', this.updateOutputFormat.bind(this));

                    // Quick season buttons
                    $('.quick-season-btn').on('click', this.selectQuickSeason.bind(this));

                    // Generate report button
                    $('#generate-report-btn').on('click', this.generateReport.bind(this));

                    // Reset form button
                    $('#reset-form-btn').on('click', this.resetForm.bind(this));
                },

                updateFormVisibility: function() {
                    var reportType = $('input[name="report_type"]:checked').val();

                    // Hide all sections first
                    $('#quick-seasons-section').hide();
                    $('#date-range-section').hide();
                    $('#season-selector-section').hide();

                    // Show relevant sections
                    if (reportType === 'seasonal') {
                        $('#quick-seasons-section').show();
                        $('#season-selector-section').show();
                    } else if (reportType === 'date_range') {
                        $('#date-range-section').show();
                    } else if (reportType === 'trend') {
                        $('#season-selector-section').show();
                    }
                },

                updateOutputFormat: function() {
                    var outputFormat = $('input[name="output_format"]:checked').val();

                    if (outputFormat === 'email') {
                        $('#email-recipient-section').show();
                    } else {
                        $('#email-recipient-section').hide();
                    }
                },

                selectQuickSeason: function(e) {
                    var season = $(e.currentTarget).data('season');
                    $('#season-select').val(season);
                },

                generateReport: function() {
                    var reportType = $('input[name="report_type"]:checked').val();
                    var outputFormat = $('input[name="output_format"]:checked').val();

                    // Validate form
                    var validation = this.validateForm(reportType, outputFormat);
                    if (!validation.valid) {
                        alert(validation.message);
                        return;
                    }

                    // Build request data
                    var data = this.buildRequestData(reportType, outputFormat);

                    // Handle different output formats
                    if (outputFormat === 'preview') {
                        this.previewReport(data);
                    } else if (outputFormat === 'csv') {
                        this.downloadCSV(data);
                    } else if (outputFormat === 'pdf') {
                        this.downloadPDF(data);
                    } else if (outputFormat === 'email') {
                        this.emailReport(data);
                    }
                },

                validateForm: function(reportType, outputFormat) {
                    // Validate date range for date_range reports
                    if (reportType === 'date_range') {
                        var startDate = $('#start-date').val();
                        var endDate = $('#end-date').val();

                        if (!startDate || !endDate) {
                            return {
                                valid: false,
                                message: '<?php echo esc_js(__('Bitte wählen Sie Start- und Enddatum.', 'abschussplan-hgmh')); ?>'
                            };
                        }

                        if (new Date(startDate) > new Date(endDate)) {
                            return {
                                valid: false,
                                message: '<?php echo esc_js(__('Startdatum muss vor Enddatum liegen.', 'abschussplan-hgmh')); ?>'
                            };
                        }
                    }

                    // Validate email for email format
                    if (outputFormat === 'email') {
                        var recipient = $('#email-recipient').val();
                        if (!recipient || !this.isValidEmail(recipient)) {
                            return {
                                valid: false,
                                message: '<?php echo esc_js(__('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'abschussplan-hgmh')); ?>'
                            };
                        }
                    }

                    return { valid: true };
                },

                isValidEmail: function(email) {
                    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                },

                buildRequestData: function(reportType, outputFormat) {
                    var data = {
                        action: 'ahgmh_preview_report',
                        nonce: '<?php echo wp_create_nonce('ahgmh_admin_nonce'); ?>',
                        report_type: reportType,
                        output_format: 'html',
                        species: $('#filter-species').val(),
                        meldegruppe: $('#filter-meldegruppe').val()
                    };

                    // Add type-specific parameters
                    if (reportType === 'seasonal') {
                        data.season = $('#season-select').val();
                    } else if (reportType === 'date_range') {
                        data.start_date = $('#start-date').val();
                        data.end_date = $('#end-date').val();
                    } else if (reportType === 'trend') {
                        data.current_season = $('#season-select').val();
                    }

                    return data;
                },

                previewReport: function(data) {
                    var $container = $('#report-preview-container');
                    var $btn = $('#generate-report-btn');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: data,
                        beforeSend: function() {
                            $container.addClass('loading').html('<p><?php echo esc_js(__('Lädt...', 'abschussplan-hgmh')); ?></p>');
                            $btn.prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success && response.data.html) {
                                $container.html('<div class="report-preview-content">' + response.data.html + '</div>');
                            } else {
                                $container.html('<div class="ahgmh-reports-error">' +
                                    (response.data || '<?php echo esc_js(__('Fehler beim Laden der Vorschau.', 'abschussplan-hgmh')); ?>') +
                                    '</div>');
                            }
                        },
                        error: function() {
                            $container.html('<div class="ahgmh-reports-error"><?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'abschussplan-hgmh')); ?></div>');
                        },
                        complete: function() {
                            $container.removeClass('loading');
                            $btn.prop('disabled', false);
                        }
                    });
                },

                downloadCSV: function(data) {
                    var $btn = $('#generate-report-btn');

                    // Create form for CSV download
                    var form = $('<form>', {
                        method: 'POST',
                        action: ajaxurl
                    });

                    // Add data as hidden fields
                    data.action = 'ahgmh_download_report_csv';
                    $.each(data, function(key, value) {
                        form.append($('<input>', {
                            type: 'hidden',
                            name: key,
                            value: value
                        }));
                    });

                    // Submit form
                    $('body').append(form);
                    form.submit();
                    form.remove();

                    // Show success message
                    $('#report-preview-container').html(
                        '<div class="ahgmh-reports-success">' +
                        '<?php echo esc_js(__('CSV-Download wurde gestartet.', 'abschussplan-hgmh')); ?>' +
                        '</div>'
                    );
                },

                downloadPDF: function(data) {
                    var $btn = $('#generate-report-btn');

                    // Create form for PDF download
                    var form = $('<form>', {
                        method: 'POST',
                        action: ajaxurl
                    });

                    // Add data as hidden fields
                    data.action = 'ahgmh_download_report_pdf';
                    $.each(data, function(key, value) {
                        form.append($('<input>', {
                            type: 'hidden',
                            name: key,
                            value: value
                        }));
                    });

                    // Submit form
                    $('body').append(form);
                    form.submit();
                    form.remove();

                    // Show success message
                    $('#report-preview-container').html(
                        '<div class="ahgmh-reports-success">' +
                        '<?php echo esc_js(__('PDF-Download wurde gestartet.', 'abschussplan-hgmh')); ?>' +
                        '</div>'
                    );
                },

                emailReport: function(data) {
                    var $btn = $('#generate-report-btn');
                    var $container = $('#report-preview-container');

                    data.action = 'ahgmh_email_report';
                    data.recipient = $('#email-recipient').val();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: data,
                        beforeSend: function() {
                            $container.addClass('loading').html('<p><?php echo esc_js(__('Sendet E-Mail...', 'abschussplan-hgmh')); ?></p>');
                            $btn.prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                $container.html('<div class="ahgmh-reports-success">' + response.data.message + '</div>');
                            } else {
                                $container.html('<div class="ahgmh-reports-error">' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            $container.html('<div class="ahgmh-reports-error"><?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'abschussplan-hgmh')); ?></div>');
                        },
                        complete: function() {
                            $container.removeClass('loading');
                            $btn.prop('disabled', false);
                        }
                    });
                },

                resetForm: function() {
                    // Reset radio buttons
                    $('input[name="report_type"][value="seasonal"]').prop('checked', true);
                    $('input[name="output_format"][value="preview"]').prop('checked', true);

                    // Reset selects and inputs
                    $('#season-select').prop('selectedIndex', 0);
                    $('#filter-species').val('');
                    $('#filter-meldegruppe').val('');
                    $('#start-date').val('');
                    $('#end-date').val('');
                    $('#email-recipient').val('');

                    // Reset preview
                    $('#report-preview-container').html(
                        '<div class="ahgmh-reports-placeholder">' +
                        '<span class="dashicons dashicons-media-document"></span>' +
                        '<p><?php echo esc_js(__('Konfigurieren Sie einen Bericht und klicken Sie auf "Vorschau anzeigen", um das Ergebnis zu sehen.', 'abschussplan-hgmh')); ?></p>' +
                        '</div>'
                    );

                    // Update visibility
                    this.updateFormVisibility();
                    this.updateOutputFormat();
                }
            };

            // Initialize
            reportForm.init();
        });
        </script>
        <?php
    }
}
