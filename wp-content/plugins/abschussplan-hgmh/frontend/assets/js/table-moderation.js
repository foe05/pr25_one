/**
 * Table Moderation script
 * Handles approve, edit, and reject actions for submissions
 */
(function($) {
    'use strict';

    // Wait for the DOM to be ready
    $(document).ready(function() {

        /**
         * Announce status changes to screen readers
         * @param {string} message - Message to announce
         */
        function announceStatus(message) {
            var $announcer = $('#moderation-status-announcer');
            if ($announcer.length) {
                $announcer.text(message);
            }
        }

        /**
         * Handle approve button click
         */
        $(document).on('click', '.btn-approve', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var submissionId = $btn.data('submission-id');

            // Confirm action
            if (!confirm(ahgmh_table_moderation.strings.confirm_approve)) {
                return;
            }

            // Disable button to prevent multiple clicks
            $btn.prop('disabled', true)
                .attr('aria-busy', 'true')
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

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
                        announceStatus(ahgmh_table_moderation.strings.success_approved);

                        // Update the row - remove action buttons and show status badge
                        var $row = $btn.closest('tr');
                        var $moderationCell = $row.find('td:last');
                        $moderationCell.html('<span class="badge bg-success">Freigegeben</span>');
                    } else {
                        // Show error notification
                        var message = response.data && response.data.message
                            ? response.data.message
                            : ahgmh_table_moderation.strings.error_generic;
                        showNotification('danger', message);
                        announceStatus(message);

                        // Re-enable button
                        $btn.prop('disabled', false)
                            .attr('aria-busy', 'false')
                            .html('<span aria-hidden="true">&#10003;</span> Freigeben');
                    }
                },
                error: function() {
                    // Show general error notification
                    showNotification('danger', ahgmh_table_moderation.strings.error_generic);
                    announceStatus(ahgmh_table_moderation.strings.error_generic);

                    // Re-enable button
                    $btn.prop('disabled', false)
                        .attr('aria-busy', 'false')
                        .html('<span aria-hidden="true">&#10003;</span> Freigeben');
                }
            });
        });

        /**
         * Handle edit button click - open modal and populate form
         */
        $(document).on('click', '.btn-edit', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var submissionId = $btn.data('submission-id');
            var $row = $btn.closest('tr');

            // Extract data from table row cells
            var $cells = $row.find('td');

            // Get field values from table cells
            var dateText = $cells.eq(0).text().trim(); // dd.mm.yy format
            var jagdbezirk = $cells.eq(1).text().trim(); // May include meldegruppe in parentheses
            var abschuss = $cells.eq(2).text().trim();
            var wus = $cells.eq(3).text().trim();
            var interneNotiz = $cells.eq(4).text().trim();
            var bemerkung = $cells.eq(5).text().trim();

            // Convert date from dd.mm.yy to YYYY-MM-DD for input[type="date"]
            var dateValue = '';
            if (dateText) {
                var dateParts = dateText.split('.');
                if (dateParts.length === 3) {
                    var day = dateParts[0].length < 2 ? '0' + dateParts[0] : dateParts[0];
                    var month = dateParts[1].length < 2 ? '0' + dateParts[1] : dateParts[1];
                    var year = dateParts[2];
                    // Convert 2-digit year to 4-digit (assuming 2000s for years < 50, 1900s otherwise)
                    if (year.length === 2) {
                        year = parseInt(year) < 50 ? '20' + year : '19' + year;
                    }
                    dateValue = year + '-' + month + '-' + day;
                }
            }

            // Extract jagdbezirk without meldegruppe (remove anything in parentheses)
            var jagdbezirkValue = jagdbezirk.replace(/\s*\([^)]*\)\s*$/, '').trim();

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
            $('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
            $('#edit-form-response').hide();

            // Open the modal
            var editModal = new bootstrap.Modal(document.getElementById('editSubmissionModal'));
            editModal.show();

            // Move focus to first field when modal opens
            $('#editSubmissionModal').one('shown.bs.modal', function() {
                $('#edit-field1').focus();
            });
        });

        /**
         * Handle save button click in edit modal
         */
        $('#save-submission-btn').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $form = $('#edit-submission-form');
            var submissionId = $form.attr('data-submission-id');

            // Clear previous errors
            $form.find('.form-error').text('').hide();
            $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');

            // Validate required fields
            var hasErrors = false;
            $form.find('[required]').each(function() {
                var $field = $(this);
                if (!$field.val()) {
                    $field.addClass('is-invalid').attr('aria-invalid', 'true');
                    $field.siblings('.form-error').text('Dieses Feld ist erforderlich.').show();
                    hasErrors = true;
                }
            });

            if (hasErrors) {
                // Focus first invalid field
                $form.find('.is-invalid:first').focus();
                return;
            }

            // Disable button to prevent multiple submissions
            $btn.prop('disabled', true)
                .attr('aria-busy', 'true')
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird gespeichert...');

            // Prepare form data
            var formData = {
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
                        var successMsg = ahgmh_table_moderation.strings.success_updated || 'Abschussmeldung wurde erfolgreich aktualisiert.';
                        showNotification('success', successMsg);
                        announceStatus(successMsg);

                        // Close the modal
                        var editModal = bootstrap.Modal.getInstance(document.getElementById('editSubmissionModal'));
                        editModal.hide();

                        // Return focus to the edit button that triggered the modal
                        var $editBtn = $('.btn-edit[data-submission-id="' + submissionId + '"]');
                        $editBtn.focus();

                        // Update the table row with new data
                        var $row = $editBtn.closest('tr');
                        var $cells = $row.find('td');

                        // Format date for display (convert YYYY-MM-DD back to dd.mm.yy)
                        var editDateValue = $('#edit-field1').val();
                        var displayDate = editDateValue;
                        if (editDateValue) {
                            var editDateParts = editDateValue.split('-');
                            if (editDateParts.length === 3) {
                                var displayYear = editDateParts[0].substring(2);
                                var displayMonth = editDateParts[1];
                                var displayDay = editDateParts[2];
                                displayDate = displayDay + '.' + displayMonth + '.' + displayYear;
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
                        var message = response.data && response.data.message
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
                    $btn.prop('disabled', false)
                        .attr('aria-busy', 'false')
                        .text('Speichern');
                }
            });
        });

        /**
         * Handle reject button click - open modal
         */
        $(document).on('click', '.btn-reject', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var submissionId = $btn.data('submission-id');

            // Set submission ID on the form
            $('#reject-submission-form').attr('data-submission-id', submissionId);

            // Clear the comment field and any previous error messages
            $('#reject-comment').val('');
            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
            $('#reject-form-response').hide();

            // Open the modal
            var rejectModal = new bootstrap.Modal(document.getElementById('rejectSubmissionModal'));
            rejectModal.show();

            // Move focus to comment field when modal opens
            $('#rejectSubmissionModal').one('shown.bs.modal', function() {
                $('#reject-comment').focus();
            });
        });

        /**
         * Handle reject submit button click
         */
        $('#confirm-reject-btn').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $form = $('#reject-submission-form');
            var submissionId = $form.attr('data-submission-id');
            var $commentField = $('#reject-comment');
            var comment = $commentField.val().trim();

            // Clear previous error messages
            $('.form-error').text('').hide();
            $('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');

            // Validate comment field - it is required
            if (!comment) {
                $commentField.addClass('is-invalid').attr('aria-invalid', 'true');
                $commentField.siblings('.form-error').text('Dieses Feld ist erforderlich.').show();
                $commentField.focus();
                return;
            }

            // Disable button to prevent multiple submissions
            $btn.prop('disabled', true)
                .attr('aria-busy', 'true')
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wird abgelehnt...');

            // Prepare form data
            var formData = {
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
                        var successMsg = ahgmh_table_moderation.strings.success_rejected || 'Abschussmeldung wurde erfolgreich abgelehnt.';
                        showNotification('success', successMsg);
                        announceStatus(successMsg);

                        // Close the modal
                        var rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectSubmissionModal'));
                        rejectModal.hide();

                        // Remove the table row since submission is rejected
                        var $row = $('.btn-reject[data-submission-id="' + submissionId + '"]').closest('tr');
                        $row.fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        // Show error message in modal
                        var message = response.data && response.data.message
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
                    $btn.prop('disabled', false)
                        .attr('aria-busy', 'false')
                        .text('Ablehnen bestätigen');
                }
            });
        });

        // Handle keyboard events on moderation buttons (Enter/Space)
        $(document).on('keydown', '.btn-approve, .btn-edit, .btn-reject', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            }
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
        var $notification = $('<div>', {
            'class': 'alert alert-' + type + ' alert-dismissible fade show moderation-notification',
            'role': 'alert',
            html: message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>'
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
