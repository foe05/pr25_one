/**
 * Quick Actions Module
 *
 * Handles quick export, delete, and edit submission functionality
 */
(function ($) {
    'use strict';

    /**
     * Handle quick CSV export
     */
    function handleQuickExport($button, species, format) {
        species = species || '';
        format = format || 'csv';

        const originalText = $button.text();
        $button.prop('disabled', true).text('Exportiere...');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_quick_export',
                nonce: ahgmh_admin.nonce,
                species: species,
                format: format
            },
            success: function (response) {
                if (response.success && response.data.download_url) {
                    // Create temporary download link
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename || 'export.csv';
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    window.AHGMH.showNotification('CSV Export erfolgreich!', 'success');
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : (response.data || 'Unbekannter Fehler');
                    window.AHGMH.showNotification('Fehler beim CSV Export: ' + errorMsg, 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim CSV Export.', 'error');
            },
            complete: function () {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Handle delete submission
     */
    function handleDeleteSubmission(id, nonce, $button) {
        const $row = $button.closest('tr');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_delete_submission',
                id: id,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    window.AHGMH.showNotification('Meldung gelöscht', 'success');
                } else {
                    window.AHGMH.showNotification(response.data || 'Löschen fehlgeschlagen', 'error');
                }
            },
            error: function() {
                window.AHGMH.showNotification('Löschen fehlgeschlagen', 'error');
            }
        });
    }

    /**
     * Handle edit submission
     */
    function handleEditSubmission(id, $button) {
        const $row = $button.closest('tr');
        const $cells = $row.find('td');

        // Extract current values from table cells
        const currentData = {
            id: id,
            wildart: $cells.eq(1).text().trim(),
            kategorie: $cells.eq(2).text().trim(),
            wus_nummer: $cells.eq(3).text().trim(),
            interne_notiz: $cells.eq(4).text().trim(),
            bemerkung: $cells.eq(5).text().trim(),
            jagdbezirk: $cells.eq(6).text().trim(),
            datum: $cells.eq(7).text().trim()
        };

        // Create inline edit form
        const editFormHtml = `
            <td colspan="9" class="ahgmh-edit-submission-row">
                <form class="ahgmh-edit-submission-form" data-id="${id}">
                    <table class="ahgmh-edit-table">
                        <tr>
                            <td><label>Wildart:</label><input type="text" name="wildart" value="${currentData.wildart}" class="regular-text" readonly></td>
                            <td><label>Kategorie:</label><input type="text" name="kategorie" value="${currentData.kategorie}" class="regular-text"></td>
                            <td><label>WUS-Nummer:</label><input type="text" name="wus_nummer" value="${currentData.wus_nummer}" class="regular-text"></td>
                            <td><label>Interne Notiz:</label><textarea name="interne_notiz" class="regular-text" rows="2">${currentData.interne_notiz}</textarea></td>
                        </tr>
                        <tr>
                            <td><label>Bemerkung:</label><textarea name="bemerkung" class="regular-text" rows="2">${currentData.bemerkung}</textarea></td>
                            <td><label>Jagdbezirk:</label><input type="text" name="jagdbezirk" value="${currentData.jagdbezirk}" class="regular-text"></td>
                            <td><label>Datum:</label><input type="datetime-local" name="datum" value="${convertToDateTimeLocal(currentData.datum)}" class="regular-text"></td>
                            <td colspan="2">
                                <button type="submit" class="button button-primary">Speichern</button>
                                <button type="button" class="button button-secondary ahgmh-cancel-edit-submission">Abbrechen</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        `;

        // Hide current row and show edit form
        $row.hide().after(`<tr class="ahgmh-edit-submission-container">${editFormHtml}</tr>`);

        // Get the newly created form element and add event handlers
        const $editRow = $('.ahgmh-edit-submission-container').last();
        const $editForm = $editRow.find('.ahgmh-edit-submission-form');

        // Handle form submission
        $editForm.on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');

            // Prevent multiple submissions
            if ($submitBtn.prop('disabled')) {
                return false;
            }

            $submitBtn.prop('disabled', true).text('Speichere...');

            const formData = {
                action: 'ahgmh_edit_submission',
                id: id,
                nonce: ahgmh_admin.nonce,
                wildart: $form.find('input[name="wildart"]').val(),
                kategorie: $form.find('input[name="kategorie"]').val(),
                wus_nummer: $form.find('input[name="wus_nummer"]').val(),
                interne_notiz: $form.find('textarea[name="interne_notiz"]').val(),
                bemerkung: $form.find('textarea[name="bemerkung"]').val(),
                jagdbezirk: $form.find('input[name="jagdbezirk"]').val(),
                datum: $form.find('input[name="datum"]').val()
            };

            $.ajax({
                url: ahgmh_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        window.AHGMH.showNotification('Meldung aktualisiert', 'success');
                        // Refresh page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        const errorMsg = response.data || 'Speichern fehlgeschlagen';
                        window.AHGMH.showNotification(errorMsg, 'error');
                        $submitBtn.prop('disabled', false).text('Speichern');
                    }
                },
                error: function(xhr, status, error) {
                    window.AHGMH.showNotification('Speichern fehlgeschlagen - Netzwerkfehler', 'error');
                    $submitBtn.prop('disabled', false).text('Speichern');
                }
            });
        });

        // Handle cancel button
        $editRow.find('.ahgmh-cancel-edit-submission').on('click', function() {
            $editRow.remove();
            $row.show();
        });
    }

    /**
     * Helper function to convert date string to datetime-local format
     */
    function convertToDateTimeLocal(dateStr) {
        // dateStr format: "23.12.2024 14:30"
        if (!dateStr || dateStr.trim() === '') return '';

        try {
            const parts = dateStr.split(' ');
            if (parts.length !== 2) return '';

            const dateParts = parts[0].split('.');
            const timeParts = parts[1].split(':');

            if (dateParts.length !== 3 || timeParts.length !== 2) return '';

            // Format: YYYY-MM-DDTHH:MM
            return `${dateParts[2]}-${dateParts[1].padStart(2, '0')}-${dateParts[0].padStart(2, '0')}T${timeParts[0].padStart(2, '0')}:${timeParts[1].padStart(2, '0')}`;
        } catch (error) {
            return '';
        }
    }

    /**
     * Initialize Quick Actions event handlers
     */
    function init() {
        // Quick CSV export - main button
        $(document).on('click', '#quick-export', function (e) {
            e.preventDefault();
            handleQuickExport($(this));
        });

        // Export buttons in tables
        $(document).on('click', '.ahgmh-export-btn', function (e) {
            e.preventDefault();
            var species = $(this).data('species') || '';
            var format = $(this).data('format') || 'csv';
            handleQuickExport($(this), species, format);
        });

        // Quick refresh button
        $('.ahgmh-quick-refresh').on('click', function (e) {
            e.preventDefault();
            location.reload();
        });

        // Delete submission buttons
        $(document).on('click', '.ahgmh-delete-submission', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const nonce = $(this).data('nonce');

            if (confirm('Diese Meldung wirklich löschen?')) {
                handleDeleteSubmission(id, nonce, $(this));
            }
        });

        // Edit submission buttons
        $(document).on('click', '.ahgmh-edit-submission', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            handleEditSubmission(id, $(this));
        });
    }

    // Export functions to global scope for use by other modules
    window.AHGMH_QuickActions = {
        init: init,
        handleQuickExport: handleQuickExport,
        handleDeleteSubmission: handleDeleteSubmission,
        handleEditSubmission: handleEditSubmission
    };

    // Auto-initialize on document ready
    $(document).ready(function () {
        // Check if we're on a page that needs quick actions
        if ($('.ahgmh-data-management').length > 0 || $('#quick-export').length > 0 || $('.ahgmh-delete-submission').length > 0) {
            init();
        }
    });

})(jQuery);
