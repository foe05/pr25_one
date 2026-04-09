/**
 * Public Form Validation Script
 * Handles AJAX submission for public (non-logged-in) users
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        $('.abschussplan-hgmh-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            if ($(this).data('submitting') === true) {
                return false;
            }

            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid');

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $responseContainer = $('#abschuss-form-response');

            $form.data('submitting', true);
            $submitBtn.prop('disabled', true)
                .attr('aria-busy', 'true')
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird gespeichert...');

            var formData = new FormData();
            formData.append('action', 'submit_abschuss_form_public');
            formData.append('ahgmh_nonce', $('#ahgmh_nonce').val());
            formData.append('game_species', $('#game_species').val());
            formData.append('email', $('#email').val());
            formData.append('field1', $('#field1').val());
            formData.append('field2', $('#field2').val());
            formData.append('field3', $('#field3').val());
            formData.append('field4', $('#field4').val());
            formData.append('field5', $('#field5').val());
            formData.append('field6', $('#field6').val());

            // Validate date
            var dateValue = new Date($('#field1').val());
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            if (dateValue >= tomorrow) {
                $('#field1').addClass('is-invalid').attr('aria-invalid', 'true');
                $('#field1').siblings('.form-error').text('Das Datum darf nicht in der Zukunft liegen.').show();
                resetSubmitState($form, $submitBtn);
                return;
            }

            // Validate Meldegruppe
            if (!$('#field5').val()) {
                $('#field5').addClass('is-invalid').attr('aria-invalid', 'true');
                $('#field5').siblings('.form-error').text('Bitte wählen Sie eine Meldegruppe aus.').show();
                resetSubmitState($form, $submitBtn);
                return;
            }

            // Validate email
            var emailValue = $('#email').val();
            if (!emailValue || !emailValue.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                $('#email').addClass('is-invalid').attr('aria-invalid', 'true');
                $('#email').siblings('.form-error').text('Bitte geben Sie eine gültige E-Mail-Adresse ein.').show();
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

                        // Reset form
                        var currentDate = $('#field1').val();
                        var currentSpecies = $('#game_species').val();
                        $form[0].reset();
                        $('#field1').val(currentDate);
                        $('#game_species').val(currentSpecies);

                        $('.form-control, .form-select').removeClass('is-invalid').removeAttr('aria-invalid');
                        $('.form-error').hide();
                    } else {
                        // Show error message
                        $responseContainer
                            .removeClass('alert-success')
                            .addClass('alert-danger')
                            .text(response.data.message)
                            .show();

                        // Display field-specific errors
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, error) {
                                var $field = $form.find('[name="' + field + '"]');
                                $field.addClass('is-invalid').attr('aria-invalid', 'true');
                                $field.siblings('.form-error').text(error).show();
                            });
                        }
                    }
                },
                error: function(xhr, status) {
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

        // Real-time validation
        $('.abschussplan-hgmh-form input, .abschussplan-hgmh-form select').on('blur change', function() {
            var $field = $(this);
            var fieldValue = $field.val();

            if ($field.prop('required') && !fieldValue) {
                $field.addClass('is-invalid').attr('aria-invalid', 'true');
                $field.siblings('.form-error').text('Dieses Feld ist erforderlich.').show();
            } else {
                $field.removeClass('is-invalid').removeAttr('aria-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });

        // WUS field validation - use inline errors instead of alert()
        $('#field3').on('input', function() {
            var $field = $(this);
            var fieldValue = $field.val();

            if (fieldValue && fieldValue.length > 7) {
                $field.addClass('is-invalid').attr('aria-invalid', 'true');
                $field.siblings('.form-error').text('WUS-Nummer darf maximal 7 Stellen haben.').show();
            } else {
                $field.removeClass('is-invalid').removeAttr('aria-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });

        $('#field3').on('blur', function() {
            var $field = $(this);
            var fieldValue = $field.val();
            var numValue = parseInt(fieldValue);

            if (fieldValue) {
                if (fieldValue.length === 7) {
                    if (isNaN(numValue) || numValue < 1000000 || numValue > 9999999) {
                        $field.addClass('is-invalid').attr('aria-invalid', 'true');
                        $field.siblings('.form-error').text('WUS-Nummer muss zwischen 1000000 und 9999999 liegen.').show();
                    } else {
                        $field.removeClass('is-invalid').removeAttr('aria-invalid');
                        $field.siblings('.form-error').text('').hide();
                    }
                } else if (fieldValue.length > 0 && fieldValue.length < 7) {
                    $field.addClass('is-invalid').attr('aria-invalid', 'true');
                    $field.siblings('.form-error').text('WUS-Nummer muss genau 7 Stellen haben (1000000-9999999).').show();
                }
            } else {
                $field.removeClass('is-invalid').removeAttr('aria-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });
    });
})(jQuery);
