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
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $responseContainer = $('#abschuss-form-response'); // Updated to match template
            
            // Mark as submitting and disable the submit button to prevent multiple submissions
            $form.data('submitting', true);
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird gespeichert...');
            
            // Get form data manually (more reliable than FormData with older browsers)
            const formData = new FormData();
            formData.append('action', 'submit_abschuss_form');
            formData.append('ahgmh_nonce', $('#ahgmh_nonce').val());
            formData.append('game_species', $('#game_species').val());
            formData.append('field1', $('#field1').val());
            formData.append('field2', $('#field2').val());
            formData.append('field3', $('#field3').val());
            formData.append('field4', $('#field4').val());
            formData.append('field5', $('#field5').val());
            formData.append('field6', $('#field6').val());
            

            
            // Additional date validation
            const dateValue = new Date($('#field1').val());
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time portion for proper comparison
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1); // Get tomorrow's date
            
            if (dateValue >= tomorrow) {
                $('#field1').addClass('is-invalid');
                $('#field1').siblings('.form-error').text('Das Datum darf nicht in der Zukunft liegen.').show();
                $form.data('submitting', false);
                $submitBtn.prop('disabled', false).text('Speichern');
                return;
            }
            
            // Validate Jagdbezirk selection
            if (!$('#field5').val()) {
                $('#field5').addClass('is-invalid');
                $('#field5').siblings('.form-error').text('Bitte wählen Sie einen Jagdbezirk aus.').show();
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
                        
                        // Reset the form but keep the date and game species
                        const currentDate = $('#field1').val();
                        const currentSpecies = $('#game_species').val();
                        $form[0].reset();
                        $('#field1').val(currentDate);
                        $('#game_species').val(currentSpecies);
                        
                        // Remove the is-invalid class from all fields
                        $('.form-control, .form-select').removeClass('is-invalid');
                        $('.form-error').hide();
                        
                        // Refresh the abschuss_table if present (use AJAX instead of page reload)
                        if ($('.abschussplan-hgmh-table').length || $('.submissions-table-container').length) {
                            refreshSubmissionsTable();
                        } else {
                            // Fallback: Reload page after short delay if no table containers found
                            console.log('No table containers found, reloading page...');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        // Show error message
                        $responseContainer.removeClass('alert-success').addClass('alert-danger').text(response.data.message).show();
                        
                        // Display field specific errors
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, error) {
                                const $field = $form.find(`[name="${field}"]`);
                                
                                // Special handling for WUS duplicate error - show popup like 7-digit validation
                                if (field === 'field3' && error.includes('bereits vergeben')) {
                                    alert(error);
                                    $field.focus(); // Focus the field after popup
                                } else {
                                    // Standard error display for other fields
                                    $field.addClass('is-invalid');
                                    $field.siblings('.form-error').text(error).show();
                                }
                            });
                        }
                    }
                },
                error: function() {
                    // Show general error message
                    $responseContainer.removeClass('alert-success').addClass('alert-danger')
                        .text('Es gab einen Fehler beim Speichern. Bitte versuchen Sie es erneut.').show();
                },
                complete: function() {
                    // Reset submitting flag and re-enable the submit button
                    $form.data('submitting', false);
                    $submitBtn.prop('disabled', false).text('Speichern');
                    
                    // Scroll to the response message
                    $('html, body').animate({
                        scrollTop: $responseContainer.offset().top - 100
                    }, 500);
                }
            });
        });
        
        // Real-time validation for inputs and selects
        $('.abschussplan-hgmh-form input, .abschussplan-hgmh-form select').on('blur change', function() {
            const $field = $(this);
            const fieldName = $field.attr('name');
            const fieldValue = $field.val();
            
            // Validate required fields
            if ($field.prop('required') && !fieldValue) {
                $field.addClass('is-invalid');
                $field.siblings('.form-error').text('This field is required').show();
            } else {
                $field.removeClass('is-invalid');
                $field.siblings('.form-error').text('').hide();
            }
        });
        
        // WUS field specific validation
        $('#field3').on('input', function() {
            const $field = $(this);
            const fieldValue = $field.val();
            
            // Show feedback message if WUS exceeds 7 digits
            if (fieldValue && fieldValue.length > 7) {
                alert('WUS-Nummer darf maximal 7 Stellen haben.');
                $field.focus();
            }
        });
        
        // WUS field validation on blur (when user finishes entering)
        $('#field3').on('blur', function() {
            const $field = $(this);
            const fieldValue = $field.val();
            const numValue = parseInt(fieldValue);
            
            // Only validate range if user has entered a number
            if (fieldValue) {
                if (fieldValue.length === 7) {
                    // Check if WUS is in valid range (1000000-9999999)
                    if (isNaN(numValue) || numValue < 1000000 || numValue > 9999999) {
                        alert('WUS-Nummer muss zwischen 1000000 und 9999999 liegen.');
                        $field.focus();
                    }
                } else if (fieldValue.length > 0 && fieldValue.length < 7) {
                    // If user entered something but not 7 digits, show helpful message
                    alert('WUS-Nummer muss genau 7 Stellen haben (1000000-9999999).');
                    $field.focus();
                }
            }
        });
    });

    /**
     * Refresh the submissions table via AJAX
     */
    function refreshSubmissionsTable() {
        // Check if we're on a page with abschuss_table shortcode
        const $tableContainer = $('.abschussplan-hgmh-table, .submissions-table-container, .table-responsive');
        
        if ($tableContainer.length === 0) {
            console.log('No table container found for refresh');
            // Fallback: reload page
            setTimeout(function() {
                window.location.reload();
            }, 1000);
            return;
        }
        
        console.log('Refreshing table via AJAX...');
        
        // Add loading indicator
        $tableContainer.prepend('<div class="table-loading-overlay"><div class="spinner-border" role="status"><span class="sr-only">Lade...</span></div></div>');
        
        // Get current page data to maintain filters/pagination
        const currentPage = new URLSearchParams(window.location.search).get('ahgmh_page') || 1;
        const species = $('#species-filter').val() || '';
        
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
                console.log('AJAX refresh response:', response);
                if (response.success && response.data.html) {
                    // Replace table content
                    $tableContainer.html(response.data.html);
                    console.log('Table refreshed successfully');
                } else {
                    console.log('AJAX refresh failed, reloading page');
                    // Fallback: reload page if AJAX fails
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX refresh error:', error);
                // Fallback: reload page if AJAX fails
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            complete: function() {
                // Remove loading indicator
                $('.table-loading-overlay').remove();
            }
        });
    }
})(jQuery);
