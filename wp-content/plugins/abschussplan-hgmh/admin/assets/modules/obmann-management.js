(function ($) {
    'use strict';

    /**
     * Initialize Obmann Management
     */
    function initObmannManagement() {
        // Check if we're on the obmann management page
        if (!$('#obmann-assignment-form').length) {
            return;
        }

        // Load assignments table on page load
        refreshObmannTable();

        // Form submission
        $('#obmann-assignment-form').on('submit', function (e) {
            e.preventDefault();
            assignObmann();
        });

        // Form reset
        $('#obmann-assignment-form').on('reset', function () {
            $('#meldegruppe').prop('disabled', true).html('<option value="">Erst Wildart auswählen...</option>');
            // Clear edit mode
            clearEditMode();
        });

        // Clear edit mode when wildart changes
        $(document).on('change', '#wildart', function () {
            clearEditMode();
        });

        // Edit assignment buttons (unbind first to prevent duplicates)
        $(document).off('click', '.edit-assignment').on('click', '.edit-assignment', function () {
            var userId = $(this).data('user-id');
            var wildart = $(this).data('wildart');
            var meldegruppe = $(this).data('meldegruppe');

            editAssignment(userId, wildart, meldegruppe);
        });

        // Remove assignment buttons (unbind first to prevent duplicates)
        $(document).off('click', '.remove-assignment').on('click', '.remove-assignment', function () {
            var userId = $(this).data('user-id');
            var wildart = $(this).data('wildart');

            if (confirm('Sind Sie sicher, dass Sie diese Zuweisung entfernen möchten?')) {
                removeAssignment(userId, wildart);
            }
        });
    }

    /**
     * Load meldegruppen for selected wildart
     */
    window.loadMeldegruppenForWildart = function (wildart) {
        var $meldegruppenSelect = $('#meldegruppe');

        if (!wildart) {
            $meldegruppenSelect.prop('disabled', true)
                .html('<option value="">Erst Wildart auswählen...</option>');
            return;
        }

        // Show loading
        $meldegruppenSelect.prop('disabled', true)
            .html('<option value="">Laden...</option>');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_get_meldegruppen_for_wildart',
                wildart: wildart,
                nonce: ahgmh_admin.nonce
            },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    var options = '<option value="">Meldegruppe auswählen...</option>';

                    response.data.forEach(function (meldegruppe) {
                        options += '<option value="' + meldegruppe + '">' + meldegruppe + '</option>';
                    });

                    $meldegruppenSelect.html(options).prop('disabled', false);
                } else {
                    $meldegruppenSelect.html('<option value="">Keine Meldegruppen für diese Wildart gefunden</option>');
                }
            },
            error: function () {
                $meldegruppenSelect.html('<option value="">Fehler beim Laden</option>');
                window.AHGMH.showNotification('Fehler beim Laden der Meldegruppen', 'error');
            }
        });
    };

    /**
     * Assign obmann to meldegruppe
     */
    function assignObmann() {
        var $form = $('#obmann-assignment-form');
        var $submitBtn = $form.find('button[type="submit"]');
        var originalBtnText = $submitBtn.text();
        var isEditMode = $form.hasClass('edit-mode');

        // Disable submit button during processing
        $submitBtn.prop('disabled', true).text(isEditMode ? 'Aktualisiere...' : 'Speichere...');

        var formData = {
            action: 'ahgmh_assign_obmann_meldegruppe',
            user_id: $('#user_id').val(),
            wildart: $('#wildart').val(),
            meldegruppe: $('#meldegruppe').val(),
            nonce: ahgmh_admin.nonce
        };

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    var message = isEditMode ? 'Zuweisung erfolgreich aktualisiert!' : 'Obmann erfolgreich zugewiesen!';
                    window.AHGMH.showNotification(message, 'success');

                    // Reset form and clear edit mode
                    $('#obmann-assignment-form')[0].reset();
                    $('#meldegruppe').prop('disabled', true).html('<option value="">Erst Wildart auswählen...</option>');
                    clearEditMode();

                    // Refresh table
                    refreshObmannTable();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : (response.data || 'Fehler beim Speichern der Zuweisung');
                    window.AHGMH.showNotification(errorMsg, 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim Speichern der Zuweisung', 'error');
            },
            complete: function () {
                // Re-enable submit button
                $submitBtn.prop('disabled', false);
                if (!$form.hasClass('edit-mode')) {
                    $submitBtn.text('Obmann zuweisen');
                } else {
                    $submitBtn.text('Zuweisung aktualisieren');
                }
            }
        });
    }

    /**
     * Edit assignment
     */
    function editAssignment(userId, wildart, currentMeldegruppe) {
        var userName = $('#obmann-assignments-table').find('tr[data-user-id="' + userId + '"][data-wildart="' + wildart + '"]').find('.column-user strong').text();

        // Get formatted wildart display from data attribute
        var $row = $('#obmann-assignments-table').find('tr[data-user-id="' + userId + '"][data-wildart="' + wildart + '"]');
        var displayWildart = $row.data('wildart-display') || wildart.charAt(0).toUpperCase() + wildart.slice(1).replace(/_/g, '');

        if (confirm('Möchten Sie diese Zuweisung bearbeiten?\n\nUser: ' + userName + '\nWildart: ' + displayWildart + '\nAktuelle Meldegruppe: ' + currentMeldegruppe)) {

            var $form = $('#obmann-assignment-form');
            var $submitBtn = $form.find('button[type="submit"]');

            // Clear edit mode first
            clearEditMode();

            // Pre-fill the form
            $('#user_id').val(userId);
            $('#wildart').val(wildart);

            // Load meldegruppen for selected wildart
            loadMeldegruppenForWildart(wildart);

            // Wait for meldegruppen to load, then set current selection
            setTimeout(function () {
                $('#meldegruppe').val(currentMeldegruppe);

                // Set form to edit mode
                $form.addClass('edit-mode');
                $form.prepend('<div class="ahgmh-edit-notice"><strong>Bearbeitungsmodus:</strong> Ändern Sie die Meldegruppe und klicken Sie auf "Zuweisung aktualisieren"</div>');
                $submitBtn.text('Zuweisung aktualisieren');

                // Scroll to form
                $('html, body').animate({
                    scrollTop: $form.offset().top - 50
                }, 300);

            }, 800); // Increased timeout to ensure meldegruppen are loaded
        }
    }

    /**
     * Remove assignment
     */
    function removeAssignment(userId, wildart) {
        // Find the button that was clicked to show loading state
        var $button = $('.remove-assignment[data-user-id="' + userId + '"][data-wildart="' + wildart + '"]');

        var $buttonIcon = $button.find('i');
        var originalClass = $buttonIcon.attr('class');

        // Show loading state
        $button.prop('disabled', true);
        $buttonIcon.attr('class', 'dashicons dashicons-update-alt ahgmh-spinning');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_remove_obmann_assignment',
                user_id: userId,
                wildart: wildart,
                nonce: ahgmh_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    window.AHGMH.showNotification('Zuweisung erfolgreich entfernt!', 'success');

                    // Clear edit mode if we're editing this assignment
                    var $form = $('#obmann-assignment-form');
                    if ($form.hasClass('edit-mode') && $('#user_id').val() == userId && $('#wildart').val() == wildart) {
                        clearEditMode();
                        $form[0].reset();
                        $('#meldegruppe').prop('disabled', true).html('<option value="">Erst Wildart auswählen...</option>');
                    }

                    // Refresh table
                    refreshObmannTable();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : (response.data || 'Fehler beim Entfernen der Zuweisung');
                    window.AHGMH.showNotification(errorMsg, 'error');

                    // Restore button state on error
                    $button.prop('disabled', false);
                    $buttonIcon.attr('class', originalClass);
                }
            },
            error: function (xhr, status, error) {
                window.AHGMH.showNotification('Netzwerkfehler beim Entfernen der Zuweisung', 'error');

                // Restore button state on error
                $button.prop('disabled', false);
                $buttonIcon.attr('class', originalClass);
            }
        });
    }

    /**
     * Clear edit mode visual indicators
     */
    function clearEditMode() {
        var $form = $('#obmann-assignment-form');
        $form.removeClass('edit-mode');
        $form.find('.ahgmh-edit-notice').remove();
        $form.find('button[type="submit"]').text('Obmann zuweisen');
    }

    /**
     * Refresh obmann assignments table
     */
    function refreshObmannTable() {
        var $container = $('#obmann-assignments-table');

        // Clear edit mode when refreshing table
        clearEditMode();

        $container.html('<div class="ahgmh-loading"><span class="spinner is-active"></span> Lade Zuweisungen...</div>');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_get_obmann_assignments',
                nonce: ahgmh_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    $container.html(response.data.html);
                } else {
                    $container.html('<div class="ahgmh-error">Fehler beim Laden der Zuweisungen</div>');
                }
            },
            error: function () {
                $container.html('<div class="ahgmh-error">Fehler beim Laden der Zuweisungen</div>');
            }
        });
    }

    /**
     * Reset all assignments
     */
    function resetAllAssignments() {
        if (confirm('WARNUNG: Sind Sie sicher, dass Sie ALLE Obmann-Zuweisungen entfernen möchten?\n\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
            $.ajax({
                url: ahgmh_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ahgmh_reset_all_assignments',
                    nonce: ahgmh_admin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        window.AHGMH.showNotification(response.data.message, 'success');
                        // Clear form and refresh table
                        $('#obmann-assignment-form')[0].reset();
                        $('#meldegruppe').prop('disabled', true).html('<option value="">Erst Wildart auswählen...</option>');
                        clearEditMode();
                        refreshObmannTable();
                    } else {
                        window.AHGMH.showNotification(response.data.message || 'Fehler beim Zurücksetzen aller Zuweisungen', 'error');
                    }
                },
                error: function () {
                    window.AHGMH.showNotification('Netzwerkfehler beim Zurücksetzen', 'error');
                }
            });
        }
    }

    // Make functions globally available
    window.refreshObmannTable = refreshObmannTable;
    window.resetAllAssignments = resetAllAssignments;

    // Initialize on document ready
    $(document).ready(function () {
        // Check if ahgmh_admin object is available
        if (typeof ahgmh_admin === 'undefined') {
            return;
        }

        initObmannManagement();
    });

})(jQuery);
