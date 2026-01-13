/**
 * Table Moderation script
 * Handles approve, edit, and reject actions for submissions
 */
(function($) {
    'use strict';

    // Wait for the DOM to be ready
    $(document).ready(function() {

        /**
         * Handle approve button click
         */
        $('.btn-approve').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const submissionId = $btn.data('submission-id');

            // Confirm action
            if (!confirm(ahgmh_table_moderation.strings.confirm_approve)) {
                return;
            }

            // Disable button to prevent multiple clicks
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

            // Send AJAX request
            $.ajax({
                url: ahgmh_table_moderation.ajax_url,
                type: 'POST',
                data: {
                    action: 'ahgmh_table_approve',
                    nonce: ahgmh_table_moderation.nonce,
                    submission_id: submissionId
                },
                success: function(response) {
                    if (response.success) {
                        // Show success notification
                        showNotification('success', ahgmh_table_moderation.strings.success_approved);

                        // Update the row - remove action buttons and show status badge
                        const $row = $btn.closest('tr');
                        const $moderationCell = $row.find('td:last');
                        $moderationCell.html('<span class="badge bg-success">' + 'Freigegeben' + '</span>');
                    } else {
                        // Show error notification
                        const message = response.data && response.data.message
                            ? response.data.message
                            : ahgmh_table_moderation.strings.error_generic;
                        showNotification('danger', message);

                        // Re-enable button
                        $btn.prop('disabled', false).html('✅');
                    }
                },
                error: function() {
                    // Show general error notification
                    showNotification('danger', ahgmh_table_moderation.strings.error_generic);

                    // Re-enable button
                    $btn.prop('disabled', false).html('✅');
                }
            });
        });

        /**
         * Handle edit button click - open modal and populate form
         */
        $('.btn-edit').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const submissionId = $btn.data('submission-id');
            const $row = $btn.closest('tr');

            // Extract data from table row cells
            const $cells = $row.find('td');

            // Get field values from table cells
            const dateText = $cells.eq(0).text().trim(); // dd.mm.yy format
            const jagdbezirk = $cells.eq(1).text().trim(); // May include meldegruppe in parentheses
            const abschuss = $cells.eq(2).text().trim();
            const wus = $cells.eq(3).text().trim();
            const interneNotiz = $cells.eq(4).text().trim();
            const bemerkung = $cells.eq(5).text().trim();

            // Convert date from dd.mm.yy to YYYY-MM-DD for input[type="date"]
            let dateValue = '';
            if (dateText) {
                const dateParts = dateText.split('.');
                if (dateParts.length === 3) {
                    const day = dateParts[0].padStart(2, '0');
                    const month = dateParts[1].padStart(2, '0');
                    let year = dateParts[2];
                    // Convert 2-digit year to 4-digit (assuming 2000s for years < 50, 1900s otherwise)
                    if (year.length === 2) {
                        year = parseInt(year) < 50 ? '20' + year : '19' + year;
                    }
                    dateValue = year + '-' + month + '-' + day;
                }
            }

            // Extract jagdbezirk without meldegruppe (remove anything in parentheses)
            const jagdbezirkValue = jagdbezirk.replace(/\s*\([^)]*\)\s*$/, '').trim();

            // Populate modal form fields
            $('#edit-submission-form').attr('data-submission-id', submissionId);
            $('#edit-field1').val(dateValue);
            $('#edit-field2').val(abschuss);
            $('#edit-field3').val(wus);
            $('#edit-field5').val(jagdbezirkValue);
            $('#edit-field4').val(bemerkung);
            $('#edit-field6').val(interneNotiz);

            // Clear any previous error messages
            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid');
            $('#edit-form-response').hide();

            // Open the modal
            const editModal = new bootstrap.Modal(document.getElementById('editSubmissionModal'));
            editModal.show();
        });

        /**
         * Handle save button click in edit modal
         */
        $('#save-submission-btn').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const $form = $('#edit-submission-form');
            const submissionId = $form.attr('data-submission-id');

            // Validate required fields
            let hasErrors = false;
            $form.find('[required]').each(function() {
                const $field = $(this);
                if (!$field.val()) {
                    $field.addClass('is-invalid');
                    $field.siblings('.form-error').text('Dieses Feld ist erforderlich').show();
                    hasErrors = true;
                }
            });

            if (hasErrors) {
                return;
            }

            // Disable button to prevent multiple submissions
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird gespeichert...');

            // Prepare form data
            const formData = {
                action: 'ahgmh_table_update',
                nonce: ahgmh_table_moderation.nonce,
                submission_id: submissionId,
                field1: $('#edit-field1').val(),
                field2: $('#edit-field2').val(),
                field3: $('#edit-field3').val(),
                field5: $('#edit-field5').val(),
                field4: $('#edit-field4').val(),
                field6: $('#edit-field6').val()
            };

            // Send AJAX request
            $.ajax({
                url: ahgmh_table_moderation.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success notification
                        showNotification('success', ahgmh_table_moderation.strings.success_updated || 'Abschussmeldung wurde erfolgreich aktualisiert.');

                        // Close the modal
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editSubmissionModal'));
                        editModal.hide();

                        // Update the table row with new data
                        const $row = $('.btn-edit[data-submission-id="' + submissionId + '"]').closest('tr');
                        const $cells = $row.find('td');

                        // Format date for display (convert YYYY-MM-DD back to dd.mm.yy)
                        const dateValue = $('#edit-field1').val();
                        let displayDate = dateValue;
                        if (dateValue) {
                            const dateParts = dateValue.split('-');
                            if (dateParts.length === 3) {
                                const year = dateParts[0].substring(2); // Get last 2 digits of year
                                const month = dateParts[1];
                                const day = dateParts[2];
                                displayDate = day + '.' + month + '.' + year;
                            }
                        }

                        // Update cell contents
                        $cells.eq(0).text(displayDate);
                        $cells.eq(1).text($('#edit-field5').val());
                        $cells.eq(2).text($('#edit-field2').val());
                        $cells.eq(3).text($('#edit-field3').val());
                        $cells.eq(4).text($('#edit-field6').val());
                        $cells.eq(5).text($('#edit-field4').val());
                    } else {
                        // Show error message in modal
                        const message = response.data && response.data.message
                            ? response.data.message
                            : ahgmh_table_moderation.strings.error_generic;
                        $('#edit-form-response').removeClass('alert-success').addClass('alert-danger').text(message).show();
                    }
                },
                error: function() {
                    // Show general error message in modal
                    $('#edit-form-response').removeClass('alert-success').addClass('alert-danger')
                        .text(ahgmh_table_moderation.strings.error_generic).show();
                },
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false).text('Speichern');
                }
            });
        });

        /**
         * Handle reject button click - open modal
         */
        $('.btn-reject').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const submissionId = $btn.data('submission-id');

            // Set submission ID on the form
            $('#reject-submission-form').attr('data-submission-id', submissionId);

            // Clear the comment field and any previous error messages
            $('#reject-comment').val('');
            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid');
            $('#reject-form-response').hide();

            // Open the modal
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectSubmissionModal'));
            rejectModal.show();
        });

        /**
         * Handle reject submit button click
         */
        $('#reject-submission-btn').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const $form = $('#reject-submission-form');
            const submissionId = $form.attr('data-submission-id');
            const $commentField = $('#reject-comment');
            const comment = $commentField.val().trim();

            // Clear previous error messages
            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid');

            // Validate comment field - it is required
            if (!comment) {
                $commentField.addClass('is-invalid');
                $commentField.siblings('.form-error').text('Dieses Feld ist erforderlich').show();
                return;
            }

            // Disable button to prevent multiple submissions
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird abgelehnt...');

            // Prepare form data
            const formData = {
                action: 'ahgmh_table_reject',
                nonce: ahgmh_table_moderation.nonce,
                submission_id: submissionId,
                comment: comment
            };

            // Send AJAX request
            $.ajax({
                url: ahgmh_table_moderation.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success notification
                        showNotification('success', ahgmh_table_moderation.strings.success_rejected || 'Abschussmeldung wurde erfolgreich abgelehnt.');

                        // Close the modal
                        const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectSubmissionModal'));
                        rejectModal.hide();

                        // Remove the table row since submission is rejected
                        const $row = $('.btn-reject[data-submission-id="' + submissionId + '"]').closest('tr');
                        $row.fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        // Show error message in modal
                        const message = response.data && response.data.message
                            ? response.data.message
                            : ahgmh_table_moderation.strings.error_generic;
                        $('#reject-form-response').removeClass('alert-success').addClass('alert-danger').text(message).show();
                    }
                },
                error: function() {
                    // Show general error message in modal
                    $('#reject-form-response').removeClass('alert-success').addClass('alert-danger')
                        .text(ahgmh_table_moderation.strings.error_generic).show();
                },
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false).text('Ablehnen');
                }
            });
        });

    });

    /**
     * Show Bootstrap alert notification
     * @param {string} type - 'success' or 'danger'
     * @param {string} message - Message to display
     */
    function showNotification(type, message) {
        // Remove any existing notifications
        $('.moderation-notification').remove();

        // Create notification element
        const $notification = $('<div>', {
            class: 'alert alert-' + type + ' alert-dismissible fade show moderation-notification',
            role: 'alert',
            html: message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        });

        // Insert at top of table container
        $('.abschuss-table-container').prepend($notification);

        // Scroll to notification
        $('html, body').animate({
            scrollTop: $notification.offset().top - 100
        }, 500);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
})(jQuery);
