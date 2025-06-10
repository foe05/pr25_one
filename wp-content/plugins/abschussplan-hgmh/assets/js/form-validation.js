/**
 * Form validation script
 */
(function($) {
    'use strict';

    // Wait for the DOM to be ready
    $(document).ready(function() {
        
        // Form validation
        $('.custom-form').on('submit', function(e) {
            e.preventDefault();
            
            // Reset previous error messages
            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid');
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $responseContainer = $('#form-response');
            
            // Disable the submit button to prevent multiple submissions
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
            
            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'submit_abschuss_form');
            formData.append('nonce', ahgmh_ajax.nonce);
            

            
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
                        
                        // Reset the form
                        $form[0].reset();
                        
                        // Reload submissions table if present
                        if ($('.submissions-table-container').length) {
                            location.reload();
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
                        .text('There was an error submitting the form. Please try again.').show();
                },
                complete: function() {
                    // Re-enable the submit button
                    $submitBtn.prop('disabled', false).text('Submit');
                    
                    // Scroll to the response message
                    $('html, body').animate({
                        scrollTop: $responseContainer.offset().top - 100
                    }, 500);
                }
            });
        });
        
        // Real-time validation for inputs and selects
        $('.custom-form input, .custom-form select').on('blur change', function() {
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
            
            // Check if WUS exceeds 7 digits
            if (fieldValue && fieldValue.length > 7) {
                alert('WUS-Nummer darf maximal 7 Stellen haben.');
                $field.val(fieldValue.substring(0, 7)); // Truncate to 7 digits
            }
        });
    });
})(jQuery);
