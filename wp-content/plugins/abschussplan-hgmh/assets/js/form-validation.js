/**
 * Form validation script
 */
(function($) {
    'use strict';

    // Wait for the DOM to be ready
    $(document).ready(function() {

        // Remove any existing handlers to prevent duplicates, then add new one
        $('.abschussplan-hgmh-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            // Prevent multiple submissions - check if already submitting
            if ($(this).data('submitting') === true) {
                return false;
            }

            // Reset previous error messages
            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid');

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $responseContainer = $('#abschuss-form-response');

            // Mark as submitting and disable the submit button to prevent multiple submissions
            $form.data('submitting', true);
            $submitBtn.prop('disabled', true)
                .attr('aria-busy', 'true')
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird gespeichert...');

            // Get form data manually (more reliable than FormData with older browsers)
            var formData = new FormData();
            formData.append('action', 'submit_abschuss_form');
            formData.append('ahgmh_nonce', $('#ahgmh_nonce').val());
            formData.append('game_species', $('#game_species').val());
            formData.append('field1', $('#field1').val());
            formData.append('field2', $('#field2').val());
            formData.append('field3', $('#field3').val());
            formData.append('field4', $('#field4').val());
            formData.append('field5', $('#field5').val());
            formData.append('field6', $('#field6').val());
            // Only append field7 if the container is visible (element may not exist when no Jagdbezirke are configured)
            var field7Val = ($('#jagdbezirk-container').is(':visible') && $('#field7').length) ? ($('#field7').val() || '') : '';
            formData.append('field7', field7Val); // Jagdbezirk

            // Additional date validation
            var dateValue = new Date($('#field1').val());
            var today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time portion for proper comparison
            var tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1); // Get tomorrow's date

            if (dateValue >= tomorrow) {
                $('#field1').addClass('is-invalid');
                $('#field1').siblings('.form-error').text('Das Datum darf nicht in der Zukunft liegen.').show();
                resetSubmitState($form, $submitBtn);
                return;
            }

            // Validate Meldegruppe selection
            if (!$('#field5').val()) {
                $('#field5').addClass('is-invalid');
                $('#field5').siblings('.form-error').text('Bitte wählen Sie eine Meldegruppe aus.').show();
                resetSubmitState($form, $submitBtn);
                return;
            }

            // Validate Jagdbezirk selection (only if container is visible)
            if ($('#jagdbezirk-container').is(':visible') && !$('#field7').val()) {
                $('#field7').addClass('is-invalid');
                $('#field7').siblings('.form-error').text('Bitte wählen Sie einen Jagdbezirk aus.').show();
                resetSubmitState($form, $submitBtn);
                return;
            }

            // Send AJAX request
            $.ajax({
                url: ahgmh_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $responseContainer
                            .removeClass('alert-danger')
                            .addClass('alert-success')
                            .text(response.data.message)
                            .show();

                        // Reset the form but keep the date and game species
                        var currentDate = $('#field1').val();
                        var currentSpecies = $('#game_species').val();
                        $form[0].reset();
                        $('#field1').val(currentDate);
                        $('#game_species').val(currentSpecies);

                        // Hide Jagdbezirk container after form reset (will be shown again when Meldegruppe is selected)
                        $('#jagdbezirk-container').hide();
                        $('#field7').html('<option value="" selected disabled>Bitte zuerst Meldegruppe wählen...</option>');

                        // Remove the is-invalid class from all fields
                        $('.form-control, .form-select').removeClass('is-invalid');
                        $('.form-error').hide();

                        // Refresh the abschuss_table if present (use AJAX instead of page reload)
                        if ($('.abschussplan-hgmh-table').length || $('.submissions-table-container').length) {
                            refreshSubmissionsTable();
                        } else {
                            // Fallback: Reload page after short delay if no table containers found
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        // Show error message
                        $responseContainer
                            .removeClass('alert-success')
                            .addClass('alert-danger')
                            .text(response.data.message)
                            .show();

                        // Display field specific errors
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, error) {
                                var $field = $form.find('[name="' + field + '"]');

                                // Standard inline error display for all fields
                                $field.addClass('is-invalid');
                                $field.attr('aria-invalid', 'true');
                                $field.siblings('.form-error').text(error).show();

                                // Focus on first error field (especially for server-side validation like duplicate WUS)
                                if (field === 'field3') {
                                    $field.focus();
                                }
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Show general error message
                    var message = 'Es gab einen Fehler beim Speichern. Bitte versuchen Sie es erneut.';
                    if (status === 'timeout') {
                        message = 'Die Anfrage hat zu lange gedauert. Bitte versuchen Sie es erneut.';
                    }
                    $responseContainer
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .text(message)
                        .show();
                },
                complete: function() {
                    resetSubmitState($form, $submitBtn);

                    // Scroll to the response message
                    if ($responseContainer.is(':visible')) {
                        $('html, body').animate({
                            scrollTop: $responseContainer.offset().top - 100
                        }, 500);
                    }
                }
            });
        });

        /**
         * Reset submit button and form state
         */
        function resetSubmitState($form, $submitBtn) {
            $form.data('submitting', false);
            $submitBtn
                .prop('disabled', false)
                .attr('aria-busy', 'false')
                .text('Speichern');
        }

        // Real-time validation for inputs and selects
        $('.abschussplan-hgmh-form input, .abschussplan-hgmh-form select').on('blur change', function() {
            var $field = $(this);
            var fieldValue = $field.val();

            // Validate required fields
            if ($field.prop('required') && !fieldValue) {
                $field.addClass('is-invalid');
                $field.attr('aria-invalid', 'true');
                $field.siblings('.form-error').text('Dieses Feld ist erforderlich.').show();
            } else {
                $field.removeClass('is-invalid');
                $field.removeAttr('aria-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });

        // WUS field specific validation
        $('#field3').on('input', function() {
            var $field = $(this);
            var fieldValue = $field.val();

            // Show feedback message if WUS exceeds 7 digits
            if (fieldValue && fieldValue.length > 7) {
                $field.addClass('is-invalid');
                $field.attr('aria-invalid', 'true');
                $field.siblings('.form-error').text('WUS-Nummer darf maximal 7 Stellen haben.').show();
            } else {
                // Clear the error if the length is valid
                $field.removeClass('is-invalid');
                $field.removeAttr('aria-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });

        // WUS field validation on blur (when user finishes entering)
        $('#field3').on('blur', function() {
            var $field = $(this);
            var fieldValue = $field.val();
            var numValue = parseInt(fieldValue);

            // Only validate range if user has entered a number
            if (fieldValue) {
                if (fieldValue.length === 7) {
                    // Check if WUS is in valid range (1000000-9999999)
                    if (isNaN(numValue) || numValue < 1000000 || numValue > 9999999) {
                        $field.addClass('is-invalid');
                        $field.attr('aria-invalid', 'true');
                        $field.siblings('.form-error').text('WUS-Nummer muss zwischen 1000000 und 9999999 liegen.').show();
                    } else {
                        // Clear error if valid
                        $field.removeClass('is-invalid');
                        $field.removeAttr('aria-invalid');
                        $field.siblings('.form-error').text('').hide();
                    }
                } else if (fieldValue.length > 0 && fieldValue.length < 7) {
                    // If user entered something but not 7 digits, show helpful message
                    $field.addClass('is-invalid');
                    $field.attr('aria-invalid', 'true');
                    $field.siblings('.form-error').text('WUS-Nummer muss genau 7 Stellen haben (1000000-9999999).').show();
                }
            } else {
                // Clear error if field is empty
                $field.removeClass('is-invalid');
                $field.removeAttr('aria-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });

        // Meldegruppe change handler - load Jagdbezirke dynamically
        $('#field5').on('change', function() {
            var meldegruppe = $(this).val();
            var $jagdbezirkContainer = $('#jagdbezirk-container');
            var $jagdbezirkSelect = $('#field7');
            var $loadingIndicator = $('#jagdbezirk-loading');

            if (!meldegruppe) {
                // No Meldegruppe selected - hide Jagdbezirk container
                $jagdbezirkContainer.hide();
                $jagdbezirkSelect.html('<option value="" selected disabled>Bitte zuerst Meldegruppe wählen...</option>');
                return;
            }

            // Show loading indicator
            $loadingIndicator.show();
            $jagdbezirkSelect.prop('disabled', true);
            $jagdbezirkSelect.attr('aria-busy', 'true');

            // Make AJAX request to get Jagdbezirke for this Meldegruppe
            $.ajax({
                url: ahgmh_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ahgmh_get_jagdbezirke_by_meldegruppe',
                    nonce: $('#ahgmh_nonce').val(),
                    meldegruppe: meldegruppe
                },
                success: function(response) {
                    if (response.success && response.data.jagdbezirke) {
                        // Clear existing options and add new ones
                        $jagdbezirkSelect.empty();
                        $jagdbezirkSelect.append('<option value="" selected disabled>Bitte wählen...</option>');

                        if (response.data.jagdbezirke.length > 0) {
                            // Add options for each Jagdbezirk
                            $.each(response.data.jagdbezirke, function(index, jb) {
                                $jagdbezirkSelect.append(
                                    $('<option></option>')
                                        .attr('value', jb.value)
                                        .text(jb.label)
                                );
                            });

                            // Auto-select if only one Jagdbezirk is available
                            if (response.data.jagdbezirke.length === 1) {
                                $jagdbezirkSelect.val(response.data.jagdbezirke[0].value);
                            }

                            // Show the Jagdbezirk container
                            $jagdbezirkContainer.show();
                        } else {
                            // No Jagdbezirke configured for this Meldegruppe - keep container hidden
                            $jagdbezirkContainer.hide();
                            $jagdbezirkSelect.html('<option value="" selected disabled>Bitte zuerst Meldegruppe wählen...</option>');
                        }
                    } else {
                        // Error loading Jagdbezirke - keep container hidden
                        $jagdbezirkContainer.hide();
                        $jagdbezirkSelect.html('<option value="" selected disabled>Bitte zuerst Meldegruppe wählen...</option>');
                    }
                },
                error: function() {
                    // Error - keep container hidden
                    $jagdbezirkContainer.hide();
                    $jagdbezirkSelect.html('<option value="" selected disabled>Bitte zuerst Meldegruppe wählen...</option>');
                },
                complete: function() {
                    // Hide loading indicator and re-enable select
                    $loadingIndicator.hide();
                    $jagdbezirkSelect.prop('disabled', false);
                    $jagdbezirkSelect.attr('aria-busy', 'false');
                }
            });
        });

        // If Meldegruppe is already pre-selected (PHP auto-select for single option),
        // trigger change to load Jagdbezirke immediately on page load
        if ($('#field5').val()) {
            $('#field5').trigger('change');
        }
    });

    /**
     * Generate skeleton loading rows for table
     * @returns {string} HTML string with 5 skeleton rows x 8 cells
     */
    function generateSkeletonRows() {
        var skeletonRows = [];
        var labels = [
            'Abschussdatum',
            'Jagdbezirk',
            'Abschuss',
            'WUS',
            'Interne Notiz',
            'Bemerkung',
            'Erstellt von',
            'Erstellt am'
        ];

        // Generate 5 skeleton rows
        for (var i = 0; i < 5; i++) {
            var row = '<tr class="skeleton-row">';

            // Generate 8 cells per row
            for (var j = 0; j < 8; j++) {
                row += '<td data-label="' + labels[j] + '"><div class="skeleton-loader" aria-hidden="true"></div></td>';
            }

            row += '</tr>';
            skeletonRows.push(row);
        }

        return skeletonRows.join('');
    }

    /**
     * Refresh the submissions table via AJAX
     */
    function refreshSubmissionsTable() {
        // Check if we're on a page with abschuss_table shortcode
        var $tableContainer = $('.abschussplan-hgmh-table, .submissions-table-container, .table-responsive');

        if ($tableContainer.length === 0) {
            // Fallback: reload page
            setTimeout(function() {
                window.location.reload();
            }, 1000);
            return;
        }

        // Replace table body with skeleton loading rows
        var $tbody = $tableContainer.find('tbody');
        if ($tbody.length > 0) {
            $tbody.attr('aria-busy', 'true');
            $tbody.html(generateSkeletonRows());
        }

        // Get current page data to maintain filters/pagination
        var currentPage = new URLSearchParams(window.location.search).get('ahgmh_page') || 1;
        var species = $('#species-filter').val() || '';

        // Make AJAX request to refresh table content
        $.ajax({
            url: ahgmh_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_refresh_table',
                nonce: ahgmh_ajax.nonce,
                page: currentPage,
                species: species
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    // Replace table content
                    $tableContainer.html(response.data.html);
                } else {
                    // Fallback: reload page if AJAX fails
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }
            },
            error: function() {
                // Fallback: reload page if AJAX fails
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            complete: function() {
                if ($tbody.length > 0) {
                    $tbody.attr('aria-busy', 'false');
                }
            }
        });
    }
})(jQuery);
