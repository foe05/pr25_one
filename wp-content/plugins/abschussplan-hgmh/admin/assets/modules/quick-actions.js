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
     * Build a <select> element from an options array.
     * Handles both plain strings and objects {id, display_name} (users) or {name} (meldegruppen/categories).
     */
    function buildSelect(name, options, selected, placeholder) {
        let html = `<select name="${name}" class="regular-text" style="width:100%;box-sizing:border-box;">`;
        if (placeholder) {
            html += `<option value="">${escHtml(placeholder)}</option>`;
        }
        const selectedStr = String(selected ?? '');
        options.forEach(function(opt) {
            let val, label;
            if (typeof opt === 'string') {
                val = opt;
                label = opt;
            } else if (opt.id !== undefined) {
                // User objects: {id, display_name}
                val = String(opt.id);
                label = opt.display_name || opt.name || val;
            } else {
                val = opt.name || opt.value || '';
                label = opt.display_name || opt.name || opt.label || val;
            }
            const sel = (val === selectedStr) ? ' selected' : '';
            html += `<option value="${escAttr(val)}"${sel}>${escHtml(label)}</option>`;
        });
        html += '</select>';
        return html;
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escAttr(str) { return escHtml(str); }

    /**
     * Convert MySQL datetime (YYYY-MM-DD HH:MM:SS) to datetime-local (YYYY-MM-DDTHH:MM)
     */
    function toDateTimeLocal(dtStr) {
        if (!dtStr) return '';
        return dtStr.replace(' ', 'T').substring(0, 16);
    }

    /**
     * Handle edit submission — loads data via AJAX, shows inline form with dropdowns
     */
    function handleEditSubmission(id, $button) {
        const $row = $button.closest('tr');

        // Prevent opening multiple edit rows
        if ($('.ahgmh-edit-submission-container').length > 0) {
            return;
        }

        $button.prop('disabled', true).text('Lade...');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_get_submission_data',
                id: id,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                $button.prop('disabled', false).text('Bearbeiten');

                if (!response.success) {
                    window.AHGMH.showNotification(response.data || 'Laden fehlgeschlagen', 'error');
                    return;
                }

                const sub = response.data.submission;
                const opts = response.data.options;

                const kategorieSelect  = buildSelect('kategorie',  opts.categories  || [], sub.kategorie,  'Bitte wählen...');
                const meldeGruppeSelect = buildSelect('meldegruppe', opts.meldegruppen || [], sub.meldegruppe, 'Bitte wählen...');
                const userSelect       = buildSelect('user_id',    opts.users       || [], sub.user_id,    'Bitte wählen...');

                // Initial Jagdbezirk placeholder — will be replaced once Meldegruppe AJAX fires
                const jagdbezirkInitial = sub.jagdbezirk
                    ? `<option value="${escAttr(sub.jagdbezirk)}" selected>${escHtml(sub.jagdbezirk)}</option>`
                    : '<option value="">Bitte Meldegruppe wählen...</option>';

                const editFormHtml = `
                    <td colspan="12" class="ahgmh-edit-submission-row"
                        style="padding:14px 16px;background:#f0f4f8;border-top:3px solid #2271b1;">
                        <form class="ahgmh-edit-submission-form" data-id="${id}">

                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;
                                        padding-bottom:10px;border-bottom:1px solid #c3c4c7;">
                                <button type="submit" class="button button-primary">&#10003; Speichern</button>
                                <button type="button" class="button ahgmh-cancel-edit-submission">Abbrechen</button>
                            </div>

                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
                                        gap:12px;margin-bottom:14px;">
                                <div>
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Wildart</label>
                                    <input type="text" name="wildart" value="${escAttr(sub.wildart)}"
                                        class="regular-text" readonly
                                        style="width:100%;box-sizing:border-box;background:#e8eaeb;cursor:default;">
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Kategorie</label>
                                    ${kategorieSelect}
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">WUS-Nummer</label>
                                    <input type="text" name="wus_nummer" value="${escAttr(sub.wus_nummer)}"
                                        class="regular-text" style="width:100%;box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Abschussdatum</label>
                                    <input type="datetime-local" name="datum"
                                        value="${escAttr(toDateTimeLocal(sub.harvest_date))}"
                                        class="regular-text" style="width:100%;box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Meldegruppe</label>
                                    ${meldeGruppeSelect}
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Jagdbezirk</label>
                                    <select name="jagdbezirk" id="edit-jagdbezirk-${id}"
                                            class="regular-text" style="width:100%;box-sizing:border-box;">
                                        ${jagdbezirkInitial}
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Erfasser</label>
                                    ${userSelect}
                                </div>
                                <div style="grid-column:span 2">
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Bemerkung</label>
                                    <textarea name="bemerkung" class="regular-text" rows="2"
                                        style="width:100%;box-sizing:border-box;">${escHtml(sub.bemerkung)}</textarea>
                                </div>
                                <div style="grid-column:span 2">
                                    <label style="display:block;font-weight:600;margin-bottom:3px;">Interne Notiz</label>
                                    <textarea name="interne_notiz" class="regular-text" rows="2"
                                        style="width:100%;box-sizing:border-box;">${escHtml(sub.interne_notiz)}</textarea>
                                </div>
                            </div>

                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="submit" class="button button-primary">&#10003; Speichern</button>
                                <button type="button" class="button ahgmh-cancel-edit-submission">Abbrechen</button>
                            </div>
                        </form>
                    </td>
                `;

                $row.hide().after(`<tr class="ahgmh-edit-submission-container">${editFormHtml}</tr>`);

                const $editRow          = $('.ahgmh-edit-submission-container').last();
                const $editForm         = $editRow.find('.ahgmh-edit-submission-form');
                const $jagdbezirkSelect = $editRow.find(`#edit-jagdbezirk-${id}`);
                const $meldeGruppeSelectEl = $editRow.find('select[name="meldegruppe"]');

                // Track initial jagdbezirk for pre-selection after first Meldegruppe AJAX load
                let initialJagdbezirk = sub.jagdbezirk;

                // Load Jagdbezirke when Meldegruppe changes
                $meldeGruppeSelectEl.on('change', function() {
                    const mg = $(this).val();
                    if (!mg) {
                        $jagdbezirkSelect.html('<option value="">Bitte Meldegruppe wählen...</option>');
                        initialJagdbezirk = '';
                        return;
                    }
                    $jagdbezirkSelect.html('<option value="">Lade...</option>');
                    $.ajax({
                        url: ahgmh_admin.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'ahgmh_admin_get_jagdbezirke_by_meldegruppe',
                            nonce: ahgmh_admin.nonce,
                            meldegruppe: mg
                        },
                        success: function(r) {
                            if (r.success && r.data && r.data.length > 0) {
                                let options = '<option value="">Bitte wählen...</option>';
                                r.data.forEach(function(jb) {
                                    const name = jb.jagdbezirk || jb.name || jb;
                                    options += `<option value="${escAttr(name)}">${escHtml(name)}</option>`;
                                });
                                $jagdbezirkSelect.html(options);
                                // Pre-select current value on initial load
                                if (initialJagdbezirk) {
                                    $jagdbezirkSelect.val(initialJagdbezirk);
                                    initialJagdbezirk = '';
                                }
                            } else {
                                $jagdbezirkSelect.html('<option value="">Kein Jagdbezirk konfiguriert</option>');
                                initialJagdbezirk = '';
                            }
                        },
                        error: function() {
                            $jagdbezirkSelect.html('<option value="">Fehler beim Laden</option>');
                        }
                    });
                });

                // Trigger on load if meldegruppe already selected (populates + pre-selects Jagdbezirk)
                if ($meldeGruppeSelectEl.val()) {
                    $meldeGruppeSelectEl.trigger('change');
                }

                // Handle form submission
                $editForm.on('submit', function(e) {
                    e.preventDefault();
                    const $submitBtns = $editForm.find('button[type="submit"]');
                    if ($submitBtns.first().prop('disabled')) return false;
                    $submitBtns.prop('disabled', true).text('Speichere...');

                    $.ajax({
                        url: ahgmh_admin.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'ahgmh_edit_submission',
                            id: id,
                            nonce: ahgmh_admin.nonce,
                            wildart:      $editForm.find('input[name="wildart"]').val(),
                            kategorie:    $editForm.find('select[name="kategorie"]').val(),
                            wus_nummer:   $editForm.find('input[name="wus_nummer"]').val(),
                            interne_notiz: $editForm.find('textarea[name="interne_notiz"]').val(),
                            bemerkung:    $editForm.find('textarea[name="bemerkung"]').val(),
                            jagdbezirk:   $jagdbezirkSelect.val(),
                            datum:        $editForm.find('input[name="datum"]').val(),
                            user_id:      $editForm.find('select[name="user_id"]').val()
                        },
                        success: function(r) {
                            if (r.success) {
                                window.AHGMH.showNotification('Meldung aktualisiert', 'success');
                                setTimeout(function() { location.reload(); }, 800);
                            } else {
                                window.AHGMH.showNotification(r.data || 'Speichern fehlgeschlagen', 'error');
                                $submitBtns.prop('disabled', false).text('Speichern');
                            }
                        },
                        error: function() {
                            window.AHGMH.showNotification('Netzwerkfehler beim Speichern', 'error');
                            $submitBtns.prop('disabled', false).text('Speichern');
                        }
                    });
                });

                // Handle cancel (both top and bottom cancel buttons)
                $editRow.find('.ahgmh-cancel-edit-submission').on('click', function() {
                    $editRow.remove();
                    $row.show();
                });
            },
            error: function() {
                $button.prop('disabled', false).text('Bearbeiten');
                window.AHGMH.showNotification('Laden fehlgeschlagen', 'error');
            }
        });
    }

    /**
     * Handle admin approve submission
     */
    function handleApproveSubmission(id, $button) {
        if (!confirm('Meldung freigeben?')) return;
        const $row = $button.closest('tr');
        $button.prop('disabled', true);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_admin_approve_submission',
                id: id,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.AHGMH.showNotification('Meldung freigegeben', 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    window.AHGMH.showNotification(response.data || 'Fehler beim Freigeben', 'error');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                window.AHGMH.showNotification('Netzwerkfehler', 'error');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Handle admin reject submission
     */
    function handleRejectSubmission(id, $button) {
        const comment = prompt('Ablehnungsgrund (optional):') || '';
        if (comment === null) return; // user cancelled prompt
        const $row = $button.closest('tr');
        $button.prop('disabled', true);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_admin_reject_submission',
                id: id,
                comment: comment,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.AHGMH.showNotification('Meldung abgelehnt', 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    window.AHGMH.showNotification(response.data || 'Fehler beim Ablehnen', 'error');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                window.AHGMH.showNotification('Netzwerkfehler', 'error');
                $button.prop('disabled', false);
            }
        });
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

        // Approve submission buttons
        $(document).on('click', '.ahgmh-admin-approve-submission', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            handleApproveSubmission(id, $(this));
        });

        // Reject submission buttons
        $(document).on('click', '.ahgmh-admin-reject-submission', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            handleRejectSubmission(id, $(this));
        });
    }

    // Export functions to global scope for use by other modules
    window.AHGMH_QuickActions = {
        init: init,
        handleQuickExport: handleQuickExport,
        handleDeleteSubmission: handleDeleteSubmission,
        handleEditSubmission: handleEditSubmission,
        handleApproveSubmission: handleApproveSubmission,
        handleRejectSubmission: handleRejectSubmission
    };

    // Auto-initialize on document ready
    $(document).ready(function () {
        // Check if we're on a page that needs quick actions
        if ($('.ahgmh-data-management').length > 0 || $('#quick-export').length > 0 || $('.ahgmh-delete-submission').length > 0) {
            init();
        }
    });

})(jQuery);
