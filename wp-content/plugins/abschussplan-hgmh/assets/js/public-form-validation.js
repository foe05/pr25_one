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

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $responseContainer = $('#abschuss-form-response');

            $form.data('submitting', true);
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird gespeichert...');

            const formData = new FormData();
            formData.append('action', 'submit_abschuss_form_public'); // CORRECT ACTION FOR PUBLIC FORM
            formData.append('ahgmh_nonce', $('#ahgmh_nonce').val());
            formData.append('game_species', $('#game_species').val());
            formData.append('email', $('#email').val()); // EMAIL FIELD FOR PUBLIC FORM
            formData.append('field1', $('#field1').val());
            formData.append('field2', $('#field2').val());
            formData.append('field3', $('#field3').val());
            formData.append('field4', $('#field4').val());
            formData.append('field5', $('#field5').val());
            formData.append('field6', $('#field6').val());

            // Validate date
            const dateValue = new Date($('#field1').val());
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            if (dateValue >= tomorrow) {
                $('#field1').addClass('is-invalid');
                $('#field1').siblings('.form-error').text('Das Datum darf nicht in der Zukunft liegen.').show();
                $form.data('submitting', false);
                $submitBtn.prop('disabled', false).text('Speichern');
                return;
            }

            // Validate Meldegruppe
            if (!$('#field5').val()) {
                $('#field5').addClass('is-invalid');
                $('#field5').siblings('.form-error').text('Bitte wählen Sie einen Jagdbezirk aus.').show();
                $form.data('submitting', false);
                $submitBtn.prop('disabled', false).text('Speichern');
                return;
            }

            // Validate email
            const emailValue = $('#email').val();
            if (!emailValue || !emailValue.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                $('#email').addClass('is-invalid');
                $('#email').siblings('.form-error').text('Bitte geben Sie eine gültige E-Mail-Adresse ein.').show();
                $form.data('submitting', false);
                $submitBtn.prop('disabled', false).text('Speichern');
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
                        $responseContainer.removeClass('alert-danger').addClass('alert-success').text(response.data.message).show();

                        // Reset form
                        const currentDate = $('#field1').val();
                        const currentSpecies = $('#game_species').val();
                        $form[0].reset();
                        $('#field1').val(currentDate);
                        $('#game_species').val(currentSpecies);

                        $('.form-control, .form-select').removeClass('is-invalid');
                        $('.form-error').hide();
                    } else {
                        // Show error message
                        $responseContainer.removeClass('alert-success').addClass('alert-danger').text(response.data.message).show();

                        // Display field-specific errors
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, error) {
                                const $field = $form.find(`[name="${field}"]`);
                                $field.addClass('is-invalid');
                                $field.siblings('.form-error').text(error).show();
                            });
                        }
                    }
                },
                error: function() {
                    $responseContainer.removeClass('alert-success').addClass('alert-danger')
                        .text('Es gab einen Fehler beim Speichern. Bitte versuchen Sie es erneut.').show();
                },
                complete: function() {
                    $form.data('submitting', false);
                    $submitBtn.prop('disabled', false).text('Speichern');

                    $('html, body').animate({
                        scrollTop: $responseContainer.offset().top - 100
                    }, 500);
                }
            });
        });

        // Real-time validation
        $('.abschussplan-hgmh-form input, .abschussplan-hgmh-form select').on('blur change', function() {
            const $field = $(this);
            const fieldValue = $field.val();

            if ($field.prop('required') && !fieldValue) {
                $field.addClass('is-invalid');
                $field.siblings('.form-error').text('Dieses Feld ist erforderlich').show();
            } else {
                $field.removeClass('is-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });

        // WUS field validation
        $('#field3').on('input', function() {
            const $field = $(this);
            const fieldValue = $field.val();

            if (fieldValue && fieldValue.length > 7) {
                alert('WUS-Nummer darf maximal 7 Stellen haben.');
                $field.focus();
            }
        });

        $('#field3').on('blur', function() {
            const $field = $(this);
            const fieldValue = $field.val();
            const numValue = parseInt(fieldValue);

            if (fieldValue) {
                if (fieldValue.length === 7) {
                    if (isNaN(numValue) || numValue < 1000000 || numValue > 9999999) {
                        alert('WUS-Nummer muss zwischen 1000000 und 9999999 liegen.');
                        $field.focus();
                    }
                } else if (fieldValue.length > 0 && fieldValue.length < 7) {
                    alert('WUS-Nummer muss genau 7 Stellen haben (1000000-9999999).');
                    $field.focus();
                }
            }
        });
    });
})(jQuery);
