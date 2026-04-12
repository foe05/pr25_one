/**
 * Table Moderation script
 * Handles approve, reject, inline-edit and delete actions for submissions
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Announce status changes to screen readers
         */
        function announceStatus(message) {
            var $announcer = $('#moderation-status-announcer');
            if ($announcer.length) {
                $announcer.text(message);
            }
        }

        /**
         * Show Bootstrap-style notification at the top of the table container
         */
        function showNotification(type, message) {
            $('.moderation-notification').remove();
            var $n = $('<div>', {
                'class': 'alert alert-' + type + ' alert-dismissible fade show moderation-notification',
                'role': 'alert',
                html: message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>'
            });
            $('.abschuss-table-container').prepend($n);
            $('html, body').animate({ scrollTop: $n.offset().top - 100 }, 400);
            setTimeout(function() { $n.fadeOut(function() { $(this).remove(); }); }, 5000);
        }

        // ─── Approve ─────────────────────────────────────────────────────────

        $(document).on('click', '.btn-approve', function(e) {
            e.preventDefault();
            if (!confirm(ahgmh_table_moderation.strings.confirm_approve)) return;

            var $btn = $(this);
            var id   = $btn.data('submission-id');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.post(ahgmh_table_moderation.ajax_url, {
                action: 'ahgmh_table_approve',
                nonce: ahgmh_table_moderation.nonce,
                submission_id: id
            }, function(r) {
                if (r.success) {
                    showNotification('success', ahgmh_table_moderation.strings.success_approved);
                    announceStatus(ahgmh_table_moderation.strings.success_approved);
                    $btn.closest('tr').find('td:last').html('<span class="badge bg-success">Genehmigt</span>');
                } else {
                    var msg = (r.data && r.data.message) ? r.data.message : ahgmh_table_moderation.strings.error_generic;
                    showNotification('danger', msg);
                    $btn.prop('disabled', false).html('&#10003; Freigeben');
                }
            }).fail(function() {
                showNotification('danger', ahgmh_table_moderation.strings.error_generic);
                $btn.prop('disabled', false).html('&#10003; Freigeben');
            });
        });

        // ─── Reject (modal) ───────────────────────────────────────────────────

        $(document).on('click', '.btn-reject', function(e) {
            e.preventDefault();
            var id = $(this).data('submission-id');
            $('#reject-submission-form').attr('data-submission-id', id);
            $('#reject-comment').val('');
            $('.form-error').text('').hide();
            $('#reject-form-response').hide();
            var modal = new bootstrap.Modal(document.getElementById('rejectSubmissionModal'));
            modal.show();
            $('#rejectSubmissionModal').one('shown.bs.modal', function() { $('#reject-comment').focus(); });
        });

        $('#confirm-reject-btn').on('click', function(e) {
            e.preventDefault();
            var $btn    = $(this);
            var $form   = $('#reject-submission-form');
            var id      = $form.attr('data-submission-id');
            var comment = $('#reject-comment').val().trim();

            if (!comment) {
                $('#reject-comment').addClass('is-invalid').attr('aria-invalid', 'true')
                    .siblings('.form-error').text('Dieses Feld ist erforderlich.').show();
                $('#reject-comment').focus();
                return;
            }

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Wird abgelehnt...');

            $.post(ahgmh_table_moderation.ajax_url, {
                action: 'ahgmh_table_reject',
                nonce: ahgmh_table_moderation.nonce,
                submission_id: id,
                comment: comment
            }, function(r) {
                if (r.success) {
                    showNotification('success', ahgmh_table_moderation.strings.success_rejected);
                    announceStatus(ahgmh_table_moderation.strings.success_rejected);
                    bootstrap.Modal.getInstance(document.getElementById('rejectSubmissionModal')).hide();
                    $('.btn-reject[data-submission-id="' + id + '"]').closest('tr').fadeOut(400, function() { $(this).remove(); });
                } else {
                    var msg = (r.data && r.data.message) ? r.data.message : ahgmh_table_moderation.strings.error_generic;
                    $('#reject-form-response').removeClass('alert-success').addClass('alert-danger').text(msg).show();
                }
            }).fail(function() {
                $('#reject-form-response').removeClass('alert-success').addClass('alert-danger')
                    .text(ahgmh_table_moderation.strings.error_generic).show();
            }).always(function() {
                $btn.prop('disabled', false).text('Ablehnen bestätigen');
            });
        });

        // ─── Inline Edit ──────────────────────────────────────────────────────

        $(document).on('click', '.btn-edit', function(e) {
            e.preventDefault();

            // Only one edit row at a time
            if ($('.ahgmh-inline-edit-row').length) return;

            var $btn  = $(this);
            var $row  = $btn.closest('tr');
            var id    = $row.data('id');
            var nonce = $row.data('nonce');

            // Read raw values from cells (column order: 0:Datum 1:Wildart 2:Abschuss 3:WUS
            // 4:Meldegruppe 5:Jagdbezirk 6:Bemerkung 7:InterneNotiz 8:ErstelltVon 9:ErstelltAm 10:Moderation)
            var $cells      = $row.find('td');
            var isoDate     = $row.data('harvest-date') || '';
            var wildart     = $cells.eq(1).text().trim();
            var abschuss    = $cells.eq(2).text().trim();
            var wus         = $cells.eq(3).text().trim();
            var meldegruppe = $cells.eq(4).text().trim();
            var jagdbezirk  = $cells.eq(5).text().trim();
            var bemerkung   = $cells.eq(6).text().trim();
            var notiz       = $cells.eq(7).text().trim();
            var colCount    = $cells.length;

            // Show spinner on button while loading options
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            // Load categories and meldegruppen via AJAX
            $.post(ahgmh_table_moderation.ajax_url, {
                action: 'ahgmh_table_get_options',
                nonce:  ahgmh_table_moderation.nonce,
                wildart: wildart
            }, function(r) {
                $btn.prop('disabled', false).html('<i class="bi bi-pencil"></i>');

                var categories  = (r.success && r.data.categories)   ? r.data.categories   : [];
                var meldegruppen = (r.success && r.data.meldegruppen) ? r.data.meldegruppen : [];

                // Build category <select>
                var catOpts = '<option value="">Bitte wählen...</option>';
                categories.forEach(function(cat) {
                    var sel = (cat === abschuss) ? ' selected' : '';
                    catOpts += '<option value="' + escAttr(cat) + '"' + sel + '>' + escHtml(cat) + '</option>';
                });
                // Fallback: if category not in list, add it as option
                if (abschuss && categories.indexOf(abschuss) === -1) {
                    catOpts += '<option value="' + escAttr(abschuss) + '" selected>' + escHtml(abschuss) + '</option>';
                }

                // Build meldegruppe <select>
                var mgOpts = '<option value="">Bitte wählen...</option>';
                meldegruppen.forEach(function(mg) {
                    var sel = (mg === meldegruppe) ? ' selected' : '';
                    mgOpts += '<option value="' + escAttr(mg) + '"' + sel + '>' + escHtml(mg) + '</option>';
                });

                var editHtml =
                    '<tr class="ahgmh-inline-edit-row table-warning">' +
                    '<td colspan="' + colCount + '" style="padding:12px;">' +

                    // Buttons top
                    '<div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #dee2e6;">' +
                    '<button type="button" class="btn btn-primary btn-sm ahgmh-inline-save" data-id="' + id + '" data-nonce="' + escAttr(nonce) + '">Speichern</button>' +
                    '<button type="button" class="btn btn-danger btn-sm ahgmh-inline-delete" data-id="' + id + '" data-nonce="' + escAttr(nonce) + '">Löschen</button>' +
                    '<button type="button" class="btn btn-secondary btn-sm ahgmh-inline-cancel">Abbrechen</button>' +
                    '</div>' +

                    '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:10px;">' +

                    '<div><label style="font-weight:600;display:block;margin-bottom:3px;">Abschussdatum</label>' +
                    '<input type="date" class="form-control form-control-sm ahgmh-edit-datum" value="' + escAttr(isoDate) + '"></div>' +

                    '<div><label style="font-weight:600;display:block;margin-bottom:3px;">Wildart</label>' +
                    '<input type="text" class="form-control form-control-sm" value="' + escAttr(wildart) + '" readonly style="background:#f0f0f0;"></div>' +

                    '<div><label style="font-weight:600;display:block;margin-bottom:3px;">Abschuss</label>' +
                    '<select class="form-select form-select-sm ahgmh-edit-abschuss">' + catOpts + '</select></div>' +

                    '<div><label style="font-weight:600;display:block;margin-bottom:3px;">WUS-Nummer</label>' +
                    '<input type="number" class="form-control form-control-sm ahgmh-edit-wus" value="' + escAttr(wus) + '" min="1000000" max="9999999"></div>' +

                    '<div><label style="font-weight:600;display:block;margin-bottom:3px;">Meldegruppe</label>' +
                    '<select class="form-select form-select-sm ahgmh-edit-meldegruppe">' + mgOpts + '</select></div>' +

                    '<div><label style="font-weight:600;display:block;margin-bottom:3px;">Jagdbezirk</label>' +
                    '<select class="form-select form-select-sm ahgmh-edit-jagdbezirk">' +
                    '<option value="">Wird geladen...</option>' +
                    '</select></div>' +

                    '<div style="grid-column:span 2"><label style="font-weight:600;display:block;margin-bottom:3px;">Bemerkung</label>' +
                    '<textarea class="form-control form-control-sm ahgmh-edit-bemerkung" rows="2">' + escHtml(bemerkung) + '</textarea></div>' +

                    '<div style="grid-column:span 2"><label style="font-weight:600;display:block;margin-bottom:3px;">Interne Notiz</label>' +
                    '<textarea class="form-control form-control-sm ahgmh-edit-notiz" rows="2">' + escHtml(notiz) + '</textarea></div>' +

                    '</div>' +

                    // Buttons bottom
                    '<div style="display:flex;gap:8px;align-items:center;">' +
                    '<button type="button" class="btn btn-primary btn-sm ahgmh-inline-save" data-id="' + id + '" data-nonce="' + escAttr(nonce) + '">Speichern</button>' +
                    '<button type="button" class="btn btn-secondary btn-sm ahgmh-inline-cancel">Abbrechen</button>' +
                    '</div>' +

                    '</td></tr>';

                $row.after(editHtml);
                $row.hide();

                var $editRow   = $row.next('.ahgmh-inline-edit-row');
                var $mgSelect  = $editRow.find('.ahgmh-edit-meldegruppe');
                var $jbSelect  = $editRow.find('.ahgmh-edit-jagdbezirk');
                var initJb     = jagdbezirk; // for pre-selection after first AJAX load

                function loadJagdbezirke(mg) {
                    if (!mg) {
                        $jbSelect.html('<option value="">Bitte Meldegruppe wählen...</option>');
                        return;
                    }
                    $jbSelect.html('<option value="">Lade...</option>').prop('disabled', true);
                    $.post(ahgmh_table_moderation.ajax_url, {
                        action:      'ahgmh_table_get_jagdbezirke',
                        nonce:       ahgmh_table_moderation.nonce,
                        meldegruppe: mg
                    }, function(resp) {
                        $jbSelect.prop('disabled', false);
                        if (resp.success && resp.data && resp.data.length > 0) {
                            var opts = '<option value="">Bitte wählen...</option>';
                            resp.data.forEach(function(jb) {
                                var jbId   = jb.id   || '';
                                var jbName = jb.jagdbezirk || jb.name || '';
                                var sel = (jbName === initJb) ? ' selected' : '';
                                opts += '<option value="' + escAttr(jbId) + '"' + sel + '>' + escHtml(jbName) + '</option>';
                            });
                            $jbSelect.html(opts);
                            initJb = ''; // pre-select only once
                        } else {
                            $jbSelect.html('<option value="">Kein Jagdbezirk konfiguriert</option>');
                            initJb = '';
                        }
                    }).fail(function() {
                        $jbSelect.prop('disabled', false).html('<option value="">Fehler beim Laden</option>');
                    });
                }

                $mgSelect.on('change', function() { loadJagdbezirke($(this).val()); });

                // Trigger initial jagdbezirk load for current meldegruppe
                loadJagdbezirke($mgSelect.val());

                $editRow.find('.ahgmh-edit-datum').focus();

            }).fail(function() {
                $btn.prop('disabled', false).html('<i class="bi bi-pencil"></i>');
                showNotification('danger', ahgmh_table_moderation.strings.error_generic);
            });
        });

        // Cancel inline edit
        $(document).on('click', '.ahgmh-inline-cancel', function() {
            var $editRow = $(this).closest('.ahgmh-inline-edit-row');
            $editRow.prev('tr').show();
            $editRow.remove();
        });

        // Save inline edit
        $(document).on('click', '.ahgmh-inline-save', function() {
            var $btn      = $(this);
            var $editRow  = $btn.closest('.ahgmh-inline-edit-row');
            var id        = $btn.data('id');
            var nonce     = $btn.data('nonce');
            var $dataRow  = $editRow.prev('tr');

            var harvestDate  = $editRow.find('.ahgmh-edit-datum').val();
            var category     = $editRow.find('.ahgmh-edit-abschuss').val().trim();
            var wus          = $editRow.find('.ahgmh-edit-wus').val().trim();
            var bemerkung    = $editRow.find('.ahgmh-edit-bemerkung').val().trim();
            var notiz        = $editRow.find('.ahgmh-edit-notiz').val().trim();
            var ejbId        = $editRow.find('.ahgmh-edit-jagdbezirk').val();
            var mgName       = $editRow.find('.ahgmh-edit-meldegruppe').val();
            var jbName       = $editRow.find('.ahgmh-edit-jagdbezirk option:selected').text();

            $editRow.find('.ahgmh-inline-save').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.post(ahgmh_table_moderation.ajax_url, {
                action:              'ahgmh_table_update',
                nonce:               nonce,
                submission_id:       id,
                harvest_date:        harvestDate,
                category:            category,
                wus_number:          wus,
                notes:               bemerkung,
                internal_note:       notiz,
                eigenjagdbezirk_id:  ejbId
            }, function(r) {
                if (r.success) {
                    showNotification('success', ahgmh_table_moderation.strings.success_updated || 'Meldung aktualisiert.');
                    announceStatus('Meldung aktualisiert.');

                    // Update display cells
                    var $cells = $dataRow.find('td');
                    if (harvestDate) {
                        var p = harvestDate.split('-');
                        if (p.length === 3) {
                            $cells.eq(0).text(p[2] + '.' + p[1] + '.' + p[0].substring(2));
                        }
                    }
                    $cells.eq(2).text(category);
                    $cells.eq(3).text(wus);
                    if (mgName) { $cells.eq(4).text(mgName); }
                    if (jbName && jbName !== 'Bitte wählen...' && jbName !== 'Lade...') {
                        $cells.eq(5).text(jbName);
                    }
                    $cells.eq(6).text(bemerkung);
                    $cells.eq(7).text(notiz);

                    // Update data-harvest-date for subsequent edits
                    $dataRow.attr('data-harvest-date', harvestDate);

                    $editRow.remove();
                    $dataRow.show();
                } else {
                    var msg = (r.data && r.data.message) ? r.data.message : ahgmh_table_moderation.strings.error_generic;
                    showNotification('danger', msg);
                    $editRow.find('.ahgmh-inline-save').prop('disabled', false).text('Speichern');
                }
            }).fail(function() {
                showNotification('danger', ahgmh_table_moderation.strings.error_generic);
                $editRow.find('.ahgmh-inline-save').prop('disabled', false).text('Speichern');
            });
        });

        // Delete from inline edit
        $(document).on('click', '.ahgmh-inline-delete', function() {
            if (!confirm('Diese Meldung wirklich löschen?')) return;

            var $btn     = $(this);
            var $editRow = $btn.closest('.ahgmh-inline-edit-row');
            var $dataRow = $editRow.prev('tr');
            var id       = $btn.data('id');
            var nonce    = $btn.data('nonce');

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.post(ahgmh_table_moderation.ajax_url, {
                action: 'ahgmh_table_delete',
                nonce: nonce,
                submission_id: id
            }, function(r) {
                if (r.success) {
                    showNotification('success', 'Meldung gelöscht.');
                    $editRow.remove();
                    $dataRow.fadeOut(400, function() { $(this).remove(); });
                } else {
                    var msg = (r.data && r.data.message) ? r.data.message : ahgmh_table_moderation.strings.error_generic;
                    showNotification('danger', msg);
                    $btn.prop('disabled', false).text('Löschen');
                }
            }).fail(function() {
                showNotification('danger', ahgmh_table_moderation.strings.error_generic);
                $btn.prop('disabled', false).text('Löschen');
            });
        });

        // Keyboard support
        $(document).on('keydown', '.btn-approve, .btn-edit, .btn-reject', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
        });

        // ─── Helpers ──────────────────────────────────────────────────────────

        function escHtml(str) {
            return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
        function escAttr(str) {
            return escHtml(str).replace(/"/g,'&quot;');
        }

    });

})(jQuery);
