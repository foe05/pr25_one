(function ($) {
    'use strict';

    /**
     * Initialize reports module
     */
    function initReportsModule() {
        // Only initialize if we're on a reports-related page
        if ($('.ahgmh-compliance-dashboard').length > 0) {
            initComplianceDashboard();
        }
        if ($('.ahgmh-reports-page').length > 0) {
            initReportsPage();
        }
        if ($('.ahgmh-schedule-settings-page').length > 0) {
            initScheduleSettings();
        }
    }

    /**
     * ================================================================
     * COMPLIANCE DASHBOARD
     * ================================================================
     */
    function initComplianceDashboard() {
        // Apply filters
        $('#apply-compliance-filters').on('click', function() {
            var season = $('#compliance-season-filter').val();
            var species = $('#compliance-species-filter').val();
            var meldegruppe = $('#compliance-meldegruppe-filter').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_compliance_filter',
                    nonce: ahgmh_reports.nonce,
                    season: season,
                    species: species,
                    meldegruppe: meldegruppe
                },
                beforeSend: function() {
                    $('#apply-compliance-filters').prop('disabled', true).text(ahgmh_reports.strings.loading);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || ahgmh_reports.strings.error);
                    }
                },
                error: function() {
                    alert(ahgmh_reports.strings.error);
                },
                complete: function() {
                    $('#apply-compliance-filters').prop('disabled', false).text(ahgmh_reports.strings.apply_filter);
                }
            });
        });

        // Refresh data
        $('#refresh-compliance').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_compliance_refresh',
                    nonce: ahgmh_reports.nonce
                },
                beforeSend: function() {
                    $('#refresh-compliance').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || ahgmh_reports.strings.error);
                    }
                },
                error: function() {
                    alert(ahgmh_reports.strings.error);
                },
                complete: function() {
                    $('#refresh-compliance').prop('disabled', false);
                }
            });
        });
    }

    /**
     * ================================================================
     * REPORTS PAGE
     * ================================================================
     */
    function initReportsPage() {
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
                            message: ahgmh_reports.strings.select_dates
                        };
                    }

                    if (new Date(startDate) > new Date(endDate)) {
                        return {
                            valid: false,
                            message: ahgmh_reports.strings.start_before_end
                        };
                    }
                }

                // Validate email for email format
                if (outputFormat === 'email') {
                    var recipient = $('#email-recipient').val();
                    if (!recipient || !this.isValidEmail(recipient)) {
                        return {
                            valid: false,
                            message: ahgmh_reports.strings.valid_email
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
                    nonce: ahgmh_reports.nonce,
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
                        $container.addClass('loading').html('<p>' + ahgmh_reports.strings.loading + '</p>');
                        $btn.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $container.html('<div class="report-preview-content">' + response.data.html + '</div>');
                        } else {
                            $container.html('<div class="ahgmh-reports-error">' +
                                (response.data || ahgmh_reports.strings.preview_error) +
                                '</div>');
                        }
                    },
                    error: function() {
                        $container.html('<div class="ahgmh-reports-error">' + ahgmh_reports.strings.error + '</div>');
                    },
                    complete: function() {
                        $container.removeClass('loading');
                        $btn.prop('disabled', false);
                    }
                });
            },

            downloadCSV: function(data) {
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
                    ahgmh_reports.strings.csv_started +
                    '</div>'
                );
            },

            downloadPDF: function(data) {
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
                    ahgmh_reports.strings.pdf_started +
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
                        $container.addClass('loading').html('<p>' + ahgmh_reports.strings.sending_email + '</p>');
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
                        $container.html('<div class="ahgmh-reports-error">' + ahgmh_reports.strings.error + '</div>');
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
                    '<p>' + ahgmh_reports.strings.configure_report + '</p>' +
                    '</div>'
                );

                // Update visibility
                this.updateFormVisibility();
                this.updateOutputFormat();
            }
        };

        // Initialize
        reportForm.init();
    }

    /**
     * ================================================================
     * SCHEDULE SETTINGS
     * ================================================================
     */
    function initScheduleSettings() {
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
            if (!confirm(ahgmh_reports.strings.confirm_delete_schedule)) {
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
            testEmail();
        });
    }

    /**
     * Open schedule form modal
     */
    function openScheduleForm() {
        // Reset form
        $('#schedule-form')[0].reset();
        $('#schedule-id').val('');
        $('#schedule-form-title').text(ahgmh_reports.strings.new_schedule);

        // Hide day fields initially
        $('#field-day-of-week').hide();
        $('#field-day-of-month').hide();

        // Show modal
        $('#schedule-form-modal').fadeIn(200);
    }

    /**
     * Close schedule form modal
     */
    function closeScheduleForm() {
        $('#schedule-form-modal').fadeOut(200);
    }

    /**
     * Load schedule data for editing
     */
    function loadScheduleForEdit(scheduleId, $scheduleItem) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_get_schedule',
                nonce: ahgmh_reports.nonce,
                schedule_id: scheduleId
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateScheduleForm(response.data);
                    $('#schedule-form-title').text(ahgmh_reports.strings.edit_schedule);
                    $('#schedule-form-modal').fadeIn(200);
                } else {
                    alert(response.data || ahgmh_reports.strings.error);
                }
            },
            error: function() {
                alert(ahgmh_reports.strings.error);
            }
        });
    }

    /**
     * Populate schedule form with data
     */
    function populateScheduleForm(schedule) {
        $('#schedule-id').val(schedule.id);
        $('#schedule-name').val(schedule.name);
        $('#schedule-report-type').val(schedule.report_type);
        $('#schedule-frequency').val(schedule.frequency).trigger('change');
        $('#schedule-time').val(schedule.time || '00:00');
        $('#schedule-day-of-week').val(schedule.day_of_week || '1');
        $('#schedule-day-of-month').val(schedule.day_of_month || '1');
        $('#schedule-recipients').val(schedule.recipients ? schedule.recipients.join('\n') : '');
        $('#schedule-species').val(schedule.filters && schedule.filters.species ? schedule.filters.species : '');
        $('#schedule-meldegruppe').val(schedule.filters && schedule.filters.meldegruppe ? schedule.filters.meldegruppe : '');
        $('#schedule-enabled').prop('checked', schedule.enabled);
    }

    /**
     * Save schedule
     */
    function saveSchedule() {
        const $btn = $('#btn-save-schedule');
        const originalText = $btn.text();

        // Get form data
        const formData = {
            action: 'ahgmh_save_schedule',
            nonce: ahgmh_reports.nonce,
            schedule_id: $('#schedule-id').val(),
            name: $('#schedule-name').val(),
            report_type: $('#schedule-report-type').val(),
            frequency: $('#schedule-frequency').val(),
            time: $('#schedule-time').val(),
            day_of_week: $('#schedule-day-of-week').val(),
            day_of_month: $('#schedule-day-of-month').val(),
            recipients: $('#schedule-recipients').val(),
            species: $('#schedule-species').val(),
            meldegruppe: $('#schedule-meldegruppe').val(),
            enabled: $('#schedule-enabled').is(':checked') ? 1 : 0
        };

        // Validate
        if (!formData.name || !formData.recipients) {
            alert(ahgmh_reports.strings.required_fields);
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $btn.prop('disabled', true).text(ahgmh_reports.strings.saving);
            },
            success: function(response) {
                if (response.success) {
                    closeScheduleForm();
                    location.reload();
                } else {
                    alert(response.data || ahgmh_reports.strings.error);
                }
            },
            error: function() {
                alert(ahgmh_reports.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Delete schedule
     */
    function deleteSchedule(scheduleId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_delete_schedule',
                nonce: ahgmh_reports.nonce,
                schedule_id: scheduleId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || ahgmh_reports.strings.error);
                }
            },
            error: function() {
                alert(ahgmh_reports.strings.error);
            }
        });
    }

    /**
     * Toggle schedule enabled/disabled
     */
    function toggleSchedule(scheduleId, enabled) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_toggle_schedule',
                nonce: ahgmh_reports.nonce,
                schedule_id: scheduleId,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    // Update status display
                    const $scheduleItem = $('.ahgmh-schedule-item[data-schedule-id="' + scheduleId + '"]');
                    if (enabled) {
                        $scheduleItem.removeClass('status-disabled').addClass('status-enabled');
                        $scheduleItem.find('.schedule-status').removeClass('badge-secondary').addClass('badge-success')
                            .text(ahgmh_reports.strings.active);
                    } else {
                        $scheduleItem.removeClass('status-enabled').addClass('status-disabled');
                        $scheduleItem.find('.schedule-status').removeClass('badge-success').addClass('badge-secondary')
                            .text(ahgmh_reports.strings.inactive);
                    }
                } else {
                    alert(response.data || ahgmh_reports.strings.error);
                    // Revert toggle
                    $('.schedule-enabled-toggle[data-schedule-id="' + scheduleId + '"]').prop('checked', !enabled);
                }
            },
            error: function() {
                alert(ahgmh_reports.strings.error);
                // Revert toggle
                $('.schedule-enabled-toggle[data-schedule-id="' + scheduleId + '"]').prop('checked', !enabled);
            }
        });
    }

    /**
     * Load schedule execution history
     */
    function loadScheduleHistory(scheduleId) {
        const $container = $('#schedule-history-container');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_get_schedule_history',
                nonce: ahgmh_reports.nonce,
                schedule_id: scheduleId
            },
            beforeSend: function() {
                $container.html('<p>' + ahgmh_reports.strings.loading + '</p>');
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $container.html(response.data.html);
                } else {
                    $container.html('<p>' + ahgmh_reports.strings.no_history + '</p>');
                }
            },
            error: function() {
                $container.html('<p>' + ahgmh_reports.strings.error + '</p>');
            }
        });
    }

    /**
     * Test schedule configuration
     */
    function testSchedule() {
        const $btn = $('#btn-test-schedule');
        const originalText = $btn.text();

        const formData = {
            action: 'ahgmh_test_schedule',
            nonce: ahgmh_reports.nonce,
            name: $('#schedule-name').val(),
            report_type: $('#schedule-report-type').val(),
            frequency: $('#schedule-frequency').val(),
            time: $('#schedule-time').val(),
            day_of_week: $('#schedule-day-of-week').val(),
            day_of_month: $('#schedule-day-of-month').val()
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $btn.prop('disabled', true).text(ahgmh_reports.strings.testing);
            },
            success: function(response) {
                if (response.success) {
                    alert(ahgmh_reports.strings.test_success);
                } else {
                    alert(response.data || ahgmh_reports.strings.test_failed);
                }
            },
            error: function() {
                alert(ahgmh_reports.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Test email delivery
     */
    function testEmail() {
        const $btn = $('#btn-test-email');
        const originalText = $btn.text();
        const recipients = $('#schedule-recipients').val();

        if (!recipients) {
            alert(ahgmh_reports.strings.enter_recipients);
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_test_email',
                nonce: ahgmh_reports.nonce,
                recipients: recipients
            },
            beforeSend: function() {
                $btn.prop('disabled', true).text(ahgmh_reports.strings.sending_email);
            },
            success: function(response) {
                if (response.success) {
                    alert(ahgmh_reports.strings.email_sent);
                } else {
                    alert(response.data || ahgmh_reports.strings.email_failed);
                }
            },
            error: function() {
                alert(ahgmh_reports.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Check if ahgmh_reports object is available
        if (typeof ahgmh_reports === 'undefined') {
            return;
        }

        initReportsModule();
    });

})(jQuery);
