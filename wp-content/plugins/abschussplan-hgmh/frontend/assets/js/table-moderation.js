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
