/**
 * Modern Admin Interface JavaScript for Abschussplan HGMH
 */
(function($) {
    'use strict';

    /**
     * Initialize admin interface
     */
    function initAdmin() {
        initQuickActions();
        initTabSwitching();
        initTooltips();
        initProgressAnimations();
        initAutoRefresh();

        initWildartConfig();
    }

    /**
     * Initialize quick action buttons
     */
    function initQuickActions() {
        // Quick Export Button
        $('#quick-export').on('click', function(e) {
            e.preventDefault();
            handleQuickExport();
        });

        // Export buttons with data attributes
        $('.ahgmh-export-btn').on('click', function(e) {
            e.preventDefault();
            const format = $(this).data('format');
            const species = $(this).data('species');
            handleExport(format, species, $(this));
        });

        // Delete submission buttons
        $('.ahgmh-delete-submission').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const nonce = $(this).data('nonce');
            handleDeleteSubmission(id, nonce, $(this));
        });

        // Export configuration save button
        $('#save_export_config').on('click', function(e) {
            e.preventDefault();
            handleSaveExportConfig();
        });

        // Danger zone buttons
        $('.ahgmh-danger-btn').on('click', function(e) {
            e.preventDefault();
            const action = $(this).data('action');
            const confirmText = $(this).data('confirm');
            
            if (confirm(confirmText)) {
                handleDangerAction(action, $(this));
            }
        });

        // Jagdbezirk form
        $('#ahgmh-add-jagdbezirk-form').on('submit', function(e) {
            e.preventDefault();
            handleAddJagdbezirk($(this));
        });

        // Edit jagdbezirk buttons
        $('.ahgmh-edit-jagdbezirk').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            handleEditJagdbezirk(id, $(this));
        });

        // Delete jagdbezirk buttons
        $('.ahgmh-delete-jagdbezirk').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            if (confirm(`Jagdbezirk "${name}" wirklich löschen?`)) {
                handleDeleteJagdbezirk(id, $(this));
            }
        });

        // Legacy event handlers removed - replaced by Master-Detail UI
    }

    /**
     * Handle quick CSV export
     */
    function handleQuickExport() {
        const $button = $('#quick-export');
        const originalText = $button.html();
        
        // Show loading state
        $button.html('<span class="dashicons dashicons-update spin"></span> ' + ahgmh_admin.strings.loading);
        $button.prop('disabled', true);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_quick_export',
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Trigger download
                    window.open(response.data.export_url, '_blank');
                    showNotification(ahgmh_admin.strings.success, 'success');
                } else {
                    showNotification(ahgmh_admin.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(ahgmh_admin.strings.error, 'error');
            },
            complete: function() {
                // Restore button state
                $button.html(originalText);
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Handle export with format and species filter
     */
    function handleExport(format, species, $button) {
        const originalText = $button.html();
        
        // Show loading state
        $button.html('<span class="dashicons dashicons-update spin"></span> Exportiere...');
        $button.prop('disabled', true);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_export_data',
                format: format,
                species: species,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Trigger download
                    const link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    link.click();
                    showNotification('Export erfolgreich erstellt', 'success');
                } else {
                    showNotification(response.data || 'Export fehlgeschlagen', 'error');
                }
            },
            error: function() {
                showNotification('Export fehlgeschlagen', 'error');
            },
            complete: function() {
                // Restore button state
                $button.html(originalText);
                $button.prop('disabled', false);
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
                    showNotification('Meldung gelöscht', 'success');
                } else {
                    showNotification(response.data || 'Löschen fehlgeschlagen', 'error');
                }
            },
            error: function() {
                showNotification('Löschen fehlgeschlagen', 'error');
            }
        });
    }

    /**
     * Handle save export configuration
     */
    function handleSaveExportConfig() {
        const pattern = $('#export_filename_pattern').val();
        const includeTime = $('#export_include_time').is(':checked');
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'save_export_config',
                nonce: ahgmh_admin.nonce,
                filename_pattern: pattern,
                include_time: includeTime
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Export-Einstellungen gespeichert', 'success');
                } else {
                    showNotification('Fehler beim Speichern: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Speichern der Export-Einstellungen', 'error');
            }
        });
    }

    /**
     * Handle danger actions (delete all, etc.)
     */
    function handleDangerAction(action, $button) {
        const originalText = $button.html();
        
        // Show loading state
        $button.html('<span class="dashicons dashicons-update spin"></span> Verarbeite...');
        $button.prop('disabled', true);

        let species = '';
        if (action === 'delete_species_submissions') {
            species = $('#ahgmh-species-select').val();
            if (!species) {
                showNotification('Bitte wählen Sie eine Wildart aus', 'error');
                $button.html(originalText).prop('disabled', false);
                return;
            }
        }

        let dateFrom = '';
        let dateTo = '';
        if (action === 'delete_daterange_submissions') {
            dateFrom = $('#ahgmh-date-from').val();
            dateTo = $('#ahgmh-date-to').val();
            
            if (!dateFrom || !dateTo) {
                showNotification('Bitte füllen Sie beide Datumsfelder aus', 'error');
                $button.html(originalText).prop('disabled', false);
                return;
            }
            
            // Validate date range
            if (new Date(dateFrom) > new Date(dateTo)) {
                showNotification('Das "Von"-Datum darf nicht nach dem "Bis"-Datum liegen', 'error');
                $button.html(originalText).prop('disabled', false);
                return;
            }
        }

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_danger_action',
                danger_action: action,
                species: species,
                date_from: dateFrom,
                date_to: dateTo,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data || 'Aktion erfolgreich ausgeführt', 'success');
                    // Refresh page after successful action
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'Aktion fehlgeschlagen', 'error');
                }
            },
            error: function() {
                showNotification('Aktion fehlgeschlagen', 'error');
            },
            complete: function() {
                $button.html(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Handle add jagdbezirk form
     */
    function handleAddJagdbezirk($form) {
        const $submitBtn = $form.find('[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Show loading state
        $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Speichere...');
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&action=ahgmh_add_jagdbezirk&nonce=' + ahgmh_admin.nonce,
            success: function(response) {
                if (response.success) {
                    showNotification('Jagdbezirk hinzugefügt', 'success');
                    $form[0].reset();
                    // Refresh page to show new jagdbezirk
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'Hinzufügen fehlgeschlagen', 'error');
                }
            },
            error: function() {
                showNotification('Hinzufügen fehlgeschlagen', 'error');
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Handle edit jagdbezirk
     */
    function handleEditJagdbezirk(id, $button) {
        const $row = $button.closest('tr');
        const currentJagdbezirk = $row.find('td:nth-child(1)').text().trim();
        const currentMeldegruppe = $row.find('td:nth-child(2)').text().trim();
        const currentBemerkung = $row.find('td:nth-child(3)').text().trim();
        
        // Create inline edit form
        const editHtml = `
            <td colspan="4" class="ahgmh-edit-row">
                <form class="ahgmh-edit-jagdbezirk-form" data-id="${id}">
                    <table class="ahgmh-edit-table">
                        <tr>
                            <td>
                                <label>Jagdbezirk:</label>
                                <input type="text" name="jagdbezirk" value="${currentJagdbezirk}" required class="regular-text">
                            </td>
                            <td>
                                <label>Meldegruppe:</label>
                                <input type="text" name="meldegruppe" value="${currentMeldegruppe}" required class="regular-text">
                            </td>
                            <td>
                                <label>Bemerkung:</label>
                                <textarea name="bemerkung" rows="2" class="large-text">${currentBemerkung}</textarea>
                            </td>
                            <td>
                                <button type="submit" class="button button-primary">Speichern</button>
                                <button type="button" class="button button-secondary ahgmh-cancel-edit">Abbrechen</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        `;
        
        // Hide current row and show edit form
        $row.hide().after(`<tr class="ahgmh-edit-row-container">${editHtml}</tr>`);
        
        // Get the newly created form element and add event handlers
        const $editRow = $('.ahgmh-edit-row-container').last();
        const $editForm = $editRow.find('.ahgmh-edit-jagdbezirk-form');
        
        // Handle form submission - use one() to ensure it only fires once
        $editForm.on('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted!'); // Debug
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Prevent multiple submissions
            if ($submitBtn.prop('disabled')) {
                return false;
            }
            
            $submitBtn.prop('disabled', true).text('Speichere...');
            
            const formData = {
                action: 'ahgmh_edit_jagdbezirk',
                id: id,
                nonce: ahgmh_admin.nonce,
                jagdbezirk: $form.find('input[name="jagdbezirk"]').val(),
                meldegruppe: $form.find('input[name="meldegruppe"]').val(),
                bemerkung: $form.find('textarea[name="bemerkung"]').val()
            };
            
            console.log('Sending data:', formData); // Debug
            
            $.ajax({
                url: ahgmh_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Response:', response); // Debug
                    if (response.success) {
                        showNotification('Jagdbezirk aktualisiert', 'success');
                        // Refresh page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(response.data || 'Speichern fehlgeschlagen', 'error');
                        $submitBtn.prop('disabled', false).text('Speichern');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr, status, error); // Debug
                    showNotification('Speichern fehlgeschlagen', 'error');
                    $submitBtn.prop('disabled', false).text('Speichern');
                }
            });
        });
        
        // Handle cancel button
        $editRow.find('.ahgmh-cancel-edit').on('click', function() {
            $editRow.remove();
            $row.show();
        });
    }

    /**
     * Handle delete jagdbezirk
     */
    function handleDeleteJagdbezirk(id, $button) {
        const $row = $button.closest('tr');
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_delete_jagdbezirk',
                id: id,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotification('Jagdbezirk gelöscht', 'success');
                } else {
                    showNotification(response.data || 'Löschen fehlgeschlagen', 'error');
                }
            },
            error: function() {
                showNotification('Löschen fehlgeschlagen', 'error');
            }
        });
    }

    /**
     * Initialize tab switching functionality
     */
    function initTabSwitching() {
        $('.ahgmh-tab').on('click', function(e) {
            const href = $(this).attr('href');
            if (href && href.indexOf('#') === -1) {
                // Let normal navigation happen for server-side tabs
                return true;
            }
            
            e.preventDefault();
            const tabId = href.replace('#', '');
            switchTab(tabId);
        });
    }

    /**
     * Switch between tabs (for client-side tabs)
     */
    function switchTab(tabId) {
        // Remove active class from all tabs
        $('.ahgmh-tab').removeClass('active');
        $('.ahgmh-tab-content > div').hide();

        // Add active class to clicked tab
        $(`[href="#${tabId}"]`).addClass('active');
        $(`#${tabId}`).show();

        // Update URL without page refresh
        if (history.pushState) {
            const newUrl = window.location.pathname + window.location.search + '#' + tabId;
            history.pushState(null, null, newUrl);
        }
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            const $element = $(this);
            const tooltip = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                showTooltip($element, tooltip);
            }).on('mouseleave', function() {
                hideTooltip();
            });
        });
    }

    /**
     * Show tooltip
     */
    function showTooltip($element, text) {
        const $tooltip = $('<div class="ahgmh-tooltip">' + text + '</div>');
        $('body').append($tooltip);

        const offset = $element.offset();
        const elementHeight = $element.outerHeight();
        const tooltipWidth = $tooltip.outerWidth();
        const tooltipHeight = $tooltip.outerHeight();

        $tooltip.css({
            top: offset.top - tooltipHeight - 5,
            left: offset.left + ($element.outerWidth() / 2) - (tooltipWidth / 2)
        }).fadeIn(200);
    }

    /**
     * Hide tooltip
     */
    function hideTooltip() {
        $('.ahgmh-tooltip').fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Initialize progress bar animations
     */
    function initProgressAnimations() {
        $('.progress-fill').each(function() {
            const $fill = $(this);
            const width = $fill.css('width');
            
            // Start from 0 and animate to target width
            $fill.css('width', '0');
            setTimeout(function() {
                $fill.css('width', width);
            }, 500);
        });
    }

    /**
     * Initialize auto-refresh for dashboard stats
     */
    function initAutoRefresh() {
        if ($('.ahgmh-dashboard-stats').length === 0) {
            return; // Only on dashboard
        }

        // Refresh stats every 5 minutes
        setInterval(function() {
            refreshDashboardStats();
        }, 300000);
        
        // Trigger immediate refresh on page load (if dashboard is visible)
        if ($('.ahgmh-dashboard-stats').is(':visible')) {
            refreshDashboardStats();
        }
    }

    /**
     * Refresh dashboard statistics
     */
    function refreshDashboardStats() {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_dashboard_stats',
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data);
                }
            },
            error: function() {
                console.log('Failed to refresh dashboard stats');
            }
        });
    }

    /**
     * Update stats display with new data
     */
    function updateStatsDisplay(stats) {
        // Update stat numbers with animation
        $('.ahgmh-stat-card').each(function(index) {
            const $card = $(this);
            const $number = $card.find('.stat-number');
            const currentValue = parseInt($number.text()) || 0;
            
            let newValue;
            switch(index) {
                case 0:
                    newValue = stats.total_submissions;
                    break;
                case 1:
                    newValue = stats.submissions_this_month;
                    break;
                case 2:
                    newValue = stats.active_users;
                    break;
                case 3:
                    newValue = stats.species_count;
                    break;
            }

            if (newValue !== currentValue) {
                animateNumber($number, currentValue, newValue);
            }
        });

        // Update species progress if data is available
        if (stats.species_progress) {
            updateSpeciesProgress(stats.species_progress);
        }

        // Update last WUS info if available
        if (stats.last_wus) {
            updateLastWusInfo(stats.last_wus);
        }
    }

    /**
     * Update species progress bars and percentages
     */
    function updateSpeciesProgress(speciesProgress) {
        $('.species-progress-item').each(function() {
            const $item = $(this);
            const species = $item.find('.species-name').text().trim();
            
            if (speciesProgress[species]) {
                const progress = speciesProgress[species];
                const $progressFill = $item.find('.progress-fill');
                const $numbersText = $item.find('.species-numbers');
                const $progressPercent = $item.find('.progress-percentage');
                
                // Update progress bar width with animation
                $progressFill.animate({
                    width: progress.percentage + '%'
                }, 800);
                
                // Update numbers text
                $numbersText.text(`${progress.current}/${progress.limit}`);
                $progressPercent.text(`${progress.percentage}%`);
                
                // Update color based on percentage
                $progressFill.removeClass('low medium high');
                if (progress.percentage >= 90) {
                    $progressFill.addClass('high');
                } else if (progress.percentage >= 70) {
                    $progressFill.addClass('medium');
                } else {
                    $progressFill.addClass('low');
                }
            }
        });
    }

    /**
     * Update last WUS information
     */
    function updateLastWusInfo(lastWus) {
        const $lastWusCard = $('.stat-card.highlight');
        if ($lastWusCard.length && lastWus) {
            $lastWusCard.find('.stat-number').text(lastWus.wus_number || '-');
            $lastWusCard.find('.stat-details').text(
                `${lastWus.species} - ${lastWus.submitted_at}`
            );
        }
    }

    /**
     * Animate number changes
     */
    function animateNumber($element, start, end) {
        const duration = 1000;
        const startTime = Date.now();
        
        function update() {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.round(start + (end - start) * progress);
            
            $element.text(current);
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        update();
    }

    /**
     * Show notification message
     */
    function showNotification(message, type) {
        const $notification = $(`
            <div class="ahgmh-notification ahgmh-${type}">
                <span class="dashicons dashicons-${type === 'success' ? 'yes-alt' : 'warning'}"></span>
                <span class="notification-text">${message}</span>
                <button class="notification-close" aria-label="Close">
                    <span class="dashicons dashicons-dismiss"></span>
                </button>
            </div>
        `);

        // Add to page
        if ($('.ahgmh-notifications').length === 0) {
            $('body').append('<div class="ahgmh-notifications"></div>');
        }
        
        $('.ahgmh-notifications').append($notification);

        // Show notification
        $notification.slideDown(300);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            hideNotification($notification);
        }, 5000);

        // Handle close button
        $notification.find('.notification-close').on('click', function() {
            hideNotification($notification);
        });
    }

    /**
     * Hide notification
     */
    function hideNotification($notification) {
        $notification.slideUp(300, function() {
            $notification.remove();
        });
    }

    /**
     * Initialize data tables (for future use)
     */
    function initDataTables() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.ahgmh-data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: 'Suchen:',
                    lengthMenu: 'Zeige _MENU_ Einträge',
                    info: 'Zeige _START_ bis _END_ von _TOTAL_ Einträgen',
                    paginate: {
                        first: 'Erste',
                        last: 'Letzte',
                        next: 'Nächste',
                        previous: 'Vorherige'
                    },
                    emptyTable: 'Keine Daten verfügbar'
                }
            });
        }
    }

    /**
     * Handle form submissions with AJAX
     */
    function initFormHandlers() {
        $('.ahgmh-ajax-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('[type="submit"]');
            const originalText = $submitBtn.val();
            
            // Show loading state
            $submitBtn.val(ahgmh_admin.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: ahgmh_admin.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        showNotification(ahgmh_admin.strings.success, 'success');
                        
                        // Optionally refresh page or update UI
                        if ($form.data('refresh') === 'true') {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showNotification(response.data || ahgmh_admin.strings.error, 'error');
                    }
                },
                error: function() {
                    showNotification(ahgmh_admin.strings.error, 'error');
                },
                complete: function() {
                    $submitBtn.val(originalText).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize drag and drop functionality (for future use)
     */
    function initDragDrop() {
        $('.ahgmh-sortable').sortable({
            handle: '.drag-handle',
            placeholder: 'sort-placeholder',
            update: function(event, ui) {
                // Handle sort update
                const order = $(this).sortable('toArray');
                saveSortOrder(order);
            }
        });
    }

    /**
     * Save sort order via AJAX
     */
    function saveSortOrder(order) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_order',
                order: order,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Reihenfolge gespeichert', 'success');
                }
            }
        });
    }

    /**
     * Initialize modal dialogs
     */
    function initModals() {
        // Open modal
        $('[data-modal]').on('click', function(e) {
            e.preventDefault();
            const modalId = $(this).data('modal');
            openModal(modalId);
        });

        // Close modal
        $(document).on('click', '.modal-close, .modal-overlay', function() {
            closeModal();
        });

        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) {
                closeModal();
            }
        });
    }

    /**
     * Open modal dialog
     */
    function openModal(modalId) {
        const $modal = $('#' + modalId);
        if ($modal.length) {
            $modal.addClass('active');
            $('body').addClass('modal-open');
        }
    }

    /**
     * Close modal dialog
     */
    function closeModal() {
        $('.ahgmh-modal').removeClass('active');
        $('body').removeClass('modal-open');
    }

    // Initialize everything when document is ready
    $(document).ready(function() {
        initAdmin();
        initFormHandlers();
        initModals();
        initDataTables();
        initDragDrop();
    });

    /**
     * Handle add species
     */
    function handleAddSpecies($form) {
        const formData = new FormData($form[0]);
        formData.append('action', 'ahgmh_add_species');
        formData.append('nonce', ahgmh_admin.nonce);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('Wildart erfolgreich hinzugefügt', 'success');
                    location.reload(); // Reload to show updated list
                } else {
                    showNotification(response.data || 'Fehler beim Hinzufügen der Wildart', 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Hinzufügen der Wildart', 'error');
            }
        });
    }

    /**
     * Handle delete species
     */
    function handleDeleteSpecies(species, index, $button) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_delete_species',
                species: species,
                index: index,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Wildart erfolgreich entfernt', 'success');
                    $button.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    showNotification(response.data || 'Fehler beim Entfernen der Wildart', 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Entfernen der Wildart', 'error');
            }
        });
    }

    /**
     * Handle add category
     */
    function handleAddCategory($form) {
        const formData = new FormData($form[0]);
        formData.append('action', 'ahgmh_add_category');
        formData.append('nonce', ahgmh_admin.nonce);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('Kategorie erfolgreich hinzugefügt', 'success');
                    location.reload(); // Reload to show updated list
                } else {
                    showNotification(response.data || 'Fehler beim Hinzufügen der Kategorie', 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Hinzufügen der Kategorie', 'error');
            }
        });
    }

    /**
     * Handle edit category
     */
    function handleEditCategory(species, category, index, $button) {
        const $row = $button.closest('tr');
        const currentCategory = $row.find('td:nth-child(1)').text().trim();
        
        // Create inline edit form
        const editHtml = `
            <td colspan="4" class="ahgmh-edit-row">
                <form class="ahgmh-edit-category-form" data-species="${species}" data-index="${index}">
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px;">
                        <label>Kategorie Name:</label>
                        <input type="text" name="new_category" value="${currentCategory}" required class="regular-text" style="flex: 1;">
                        <button type="submit" class="button button-primary">Speichern</button>
                        <button type="button" class="button button-secondary ahgmh-cancel-edit-category">Abbrechen</button>
                    </div>
                </form>
            </td>
        `;
        
        // Hide current row and show edit form
        $row.hide().after(`<tr class="ahgmh-edit-category-row-container">${editHtml}</tr>`);
        
        // Get the newly created form element and add event handlers
        const $editRow = $('.ahgmh-edit-category-row-container').last();
        const $editForm = $editRow.find('.ahgmh-edit-category-form');
        
        // Handle form submission
        $editForm.on('submit', function(e) {
            e.preventDefault();
            console.log('Category edit form submitted!'); // Debug
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Prevent multiple submissions
            if ($submitBtn.prop('disabled')) {
                return false;
            }
            
            $submitBtn.prop('disabled', true).text('Speichere...');
            
            const formData = {
                action: 'ahgmh_edit_category',
                nonce: ahgmh_admin.nonce,
                species: species,
                old_category: category,
                new_category: $form.find('input[name="new_category"]').val(),
                index: index
            };
            
            console.log('Sending category edit data:', formData); // Debug
            
            $.ajax({
                url: ahgmh_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Category edit response:', response); // Debug
                    if (response.success) {
                        showNotification('Kategorie aktualisiert', 'success');
                        // Refresh page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(response.data || 'Speichern fehlgeschlagen', 'error');
                        $submitBtn.prop('disabled', false).text('Speichern');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Category edit AJAX Error:', xhr, status, error); // Debug
                    showNotification('Speichern fehlgeschlagen', 'error');
                    $submitBtn.prop('disabled', false).text('Speichern');
                }
            });
        });
        
        // Handle cancel button
        $editRow.find('.ahgmh-cancel-edit-category').on('click', function() {
            $editRow.remove();
            $row.show();
        });
    }

    /**
     * Handle delete category
     */
    function handleDeleteCategory(species, category, index, $button) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_delete_category',
                species: species,
                category: category,
                index: index,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Kategorie erfolgreich entfernt', 'success');
                    $button.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    showNotification(response.data || 'Fehler beim Entfernen der Kategorie', 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Entfernen der Kategorie', 'error');
            }
        });
    }

    /**
     * Handle save category settings (including allow exceeding)
     */
    function handleSaveCategorySettings($form) {
        const formData = new FormData($form[0]);
        formData.append('action', 'ahgmh_save_category_settings');
        formData.append('nonce', ahgmh_admin.nonce);

        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Show loading state
        $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Speichere...');
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('Kategorien-Einstellungen erfolgreich gespeichert', 'success');
                } else {
                    showNotification(response.data || 'Fehler beim Speichern der Einstellungen', 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Speichern der Einstellungen', 'error');
            },
            complete: function() {
                // Restore button state
                $submitBtn.html(originalText);
                $submitBtn.prop('disabled', false);
            }
        });
    }

// Add CSS for notifications
const notificationCSS = `
<style>
.ahgmh-notifications {
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.ahgmh-notification {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    margin-bottom: 10px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideInRight 0.3s ease;
}

.ahgmh-success {
    background: #d1e7dd;
    border: 1px solid #badbcc;
    color: #0f5132;
}

.ahgmh-error {
    background: #f8d7da;
    border: 1px solid #f5c2c7;
    color: #842029;
}

.notification-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    margin-left: auto;
    opacity: 0.7;
}

.notification-close:hover {
    opacity: 1;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.ahgmh-tooltip {
    position: absolute;
    background: #1d2327;
    color: white;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 10000;
    pointer-events: none;
}

.ahgmh-tooltip:before {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: #1d2327;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
`;

// Inject notification CSS
document.head.insertAdjacentHTML('beforeend', notificationCSS);

    /**
     * Initialize wildart-specific meldegruppen configuration
     */
    function initMeldegruppenConfig() {
        // Toggle wildart-specific mode
        $('#use_wildart_specific_meldegruppen').on('change', function() {
            const enabled = $(this).is(':checked');
            
            if (enabled) {
                if (!confirm('⚠️ Alle bestehenden Abschussmeldungen werden gelöscht! Fortfahren?')) {
                    $(this).prop('checked', false);
                    return;
                }
            }
            
            handleToggleWildartSpecific(enabled);
        });

        // Wildart selector change
        $('#wildart_selector').on('change', function() {
            const selectedWildart = $(this).val();
            
            if (selectedWildart) {
                loadWildartMeldegruppen(selectedWildart);
                loadMeldegruppenLimitsConfig(selectedWildart);
            } else {
                $('#wildart_meldegruppen_config_container').hide();
                $('#limits_config_container').hide();
                $('#limits_config_notice').show();
            }
        });

        // Meldegruppen config form submit
        $('#ahgmh-meldegruppen-config-form').on('submit', function(e) {
            e.preventDefault();
            
            const selectedWildart = $('#wildart_selector').val();
            const meldegruppen = $('#meldegruppen_input').val();
            
            if (!selectedWildart) {
                showNotification('Bitte wählen Sie eine Wildart aus.', 'error');
                return;
            }
            
            saveWildartMeldegruppen(selectedWildart, meldegruppen);
        });

        // Clear meldegruppen button
        $('#clear_meldegruppen').on('click', function() {
            if (confirm('Alle Meldegruppen für diese Wildart löschen?')) {
                $('#meldegruppen_input').val('');
                const selectedWildart = $('#wildart_selector').val();
                if (selectedWildart) {
                    saveWildartMeldegruppen(selectedWildart, '');
                }
            }
        });
    }

    /**
     * Handle toggle of wildart-specific mode
     */
    function handleToggleWildartSpecific(enabled) {
        const $checkbox = $('#use_wildart_specific_meldegruppen');
        $checkbox.prop('disabled', true);
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ahgmh_toggle_wildart_specific',
                enabled: enabled,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Toggle visibility of configuration sections
                    if (enabled) {
                        $('#global-meldegruppen-config').hide();
                        $('#wildart-specific-meldegruppen-config').show();
                    } else {
                        $('#global-meldegruppen-config').show();
                        $('#wildart-specific-meldegruppen-config').hide();
                        $('#wildart_meldegruppen_config_container').hide();
                    }
                } else {
                    showNotification(response.data, 'error');
                    $checkbox.prop('checked', !enabled); // Revert checkbox
                }
            },
            error: function() {
                showNotification('Fehler beim Ändern des Modus.', 'error');
                $checkbox.prop('checked', !enabled); // Revert checkbox
            },
            complete: function() {
                $checkbox.prop('disabled', false);
            }
        });
    }

    /**
     * Load meldegruppen for selected wildart
     */
    function loadWildartMeldegruppen(wildart) {
        $('#selected_wildart_title').text('Meldegruppen für ' + wildart);
        $('#wildart_meldegruppen_config_container').show();
        
        // Show loading state
        $('#meldegruppen_input').prop('disabled', true).val('Laden...');
        $('#current_meldegruppen_list').html('<p class="description">Laden...</p>');
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ahgmh_load_wildart_meldegruppen',
                wildart: wildart,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#meldegruppen_input').val(response.data.meldegruppen_text);
                    
                    // Update current meldegruppen display
                    let meldegruppen = response.data.meldegruppen;
                    if (meldegruppen.length > 0) {
                        let html = '<ul>';
                        meldegruppen.forEach(function(gruppe) {
                            html += '<li><code>' + gruppe + '</code></li>';
                        });
                        html += '</ul>';
                        $('#current_meldegruppen_list').html(html);
                    } else {
                        $('#current_meldegruppen_list').html('<p class="description">Keine Meldegruppen konfiguriert.</p>');
                    }
                } else {
                    showNotification(response.data, 'error');
                    $('#current_meldegruppen_list').html('<p class="description">Fehler beim Laden.</p>');
                }
            },
            error: function() {
                showNotification('Fehler beim Laden der Meldegruppen.', 'error');
                $('#current_meldegruppen_list').html('<p class="description">Fehler beim Laden.</p>');
            },
            complete: function() {
                $('#meldegruppen_input').prop('disabled', false);
            }
        });
    }

    /**
     * Save meldegruppen configuration for wildart
     */
    function saveWildartMeldegruppen(wildart, meldegruppen) {
        const $form = $('#ahgmh-meldegruppen-config-form');
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Speichern...');
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ahgmh_save_wildart_meldegruppen',
                wildart: wildart,
                meldegruppen: meldegruppen,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Update current meldegruppen display
                    let meldegruppen = response.data.meldegruppen;
                    if (meldegruppen.length > 0) {
                        let html = '<ul>';
                        meldegruppen.forEach(function(gruppe) {
                            html += '<li><code>' + gruppe + '</code></li>';
                        });
                        html += '</ul>';
                        $('#current_meldegruppen_list').html(html);
                    } else {
                        $('#current_meldegruppen_list').html('<p class="description">Keine Meldegruppen konfiguriert.</p>');
                    }
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Speichern der Meldegruppen.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Load meldegruppen limits configuration for selected wildart
     */
    function loadMeldegruppenLimitsConfig(wildart) {
        $('#limits_wildart_title').text('Abschuss-Limits für ' + wildart);
        $('#limits_config_notice').hide();
        $('#limits_config_container').show();
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ahgmh_load_meldegruppen_limits',
                species: wildart,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Render species default limits
                    renderSpeciesDefaultLimits(data.species, data.categories, data.default_limits, data.default_exceeding);
                    
                    // Render meldegruppen-specific limits
                    renderMeldegruppenLimits(data.species, data.categories, data.meldegruppen, data.meldegruppen_config);
                } else {
                    showNotification('Fehler beim Laden der Limits-Konfiguration: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotification('AJAX-Fehler beim Laden der Limits-Konfiguration.', 'error');
            }
        });
    }

    /**
     * Render species default limits form
     */
    function renderSpeciesDefaultLimits(species, categories, limits, exceeding) {
        let html = '<table class="form-table">';
        
        categories.forEach(function(category) {
            const limit = limits[category] || 0;
            const allowExceed = exceeding[category] || false;
            
            html += `
                <tr>
                    <th scope="row">${category}</th>
                    <td>
                        <input type="number" name="limits[${category}]" value="${limit}" min="0" class="small-text"> 
                        <label>
                            <input type="checkbox" name="allow_exceeding[${category}]" ${allowExceed ? 'checked' : ''}> 
                            Überschießen möglich
                        </label>
                    </td>
                </tr>
            `;
        });
        
        html += '</table>';
        $('#species_default_limits_inputs').html(html);
        
        // Bind form submit
        $('#species_default_limits_form').off('submit').on('submit', function(e) {
            e.preventDefault();
            saveSpeciesDefaultLimits(species, $(this));
        });
    }

    /**
     * Render meldegruppen-specific limits configuration
     */
    function renderMeldegruppenLimits(species, categories, meldegruppen, config) {
        let html = '';
        
        meldegruppen.forEach(function(meldegruppe) {
            const gruppeConfig = config[meldegruppe] || {};
            const hasCustom = gruppeConfig.has_custom_limits || false;
            const limits = gruppeConfig.limits || {};
            const exceeding = gruppeConfig.allow_exceeding || {};
            
            html += `
                <div class="meldegruppe-limits-section" data-meldegruppe="${meldegruppe}">
                    <h6>${meldegruppe}</h6>
                    <label>
                        <input type="checkbox" class="custom-limits-checkbox" 
                               data-species="${species}" data-meldegruppe="${meldegruppe}" 
                               ${hasCustom ? 'checked' : ''}> 
                        Eigene Limits verwenden
                    </label>
                    
                    <div class="custom-limits-inputs" style="${hasCustom ? '' : 'display:none;'}">
                        <form class="meldegruppe-limits-form" data-species="${species}" data-meldegruppe="${meldegruppe}">
                            <table class="form-table">
            `;
            
            categories.forEach(function(category) {
                const limit = limits[category] || 0;
                const allowExceed = exceeding[category] || false;
                
                html += `
                    <tr>
                        <th scope="row">${category}</th>
                        <td>
                            <input type="number" name="limits[${category}]" value="${limit}" min="0" class="small-text"> 
                            <label>
                                <input type="checkbox" name="allow_exceeding[${category}]" ${allowExceed ? 'checked' : ''}> 
                                Überschießen möglich
                            </label>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                            </table>
                            <p class="submit">
                                <button type="submit" class="button button-secondary">
                                    Limits für ${meldegruppe} speichern
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            `;
        });
        
        $('#meldegruppen_limits_container').html(html);
        
        // Bind event handlers
        $('.custom-limits-checkbox').on('change', function() {
            const species = $(this).data('species');
            const meldegruppe = $(this).data('meldegruppe');
            const hasCustom = $(this).is(':checked');
            
            toggleMeldegruppeCustomLimits(species, meldegruppe, hasCustom);
            
            // Toggle visibility
            $(this).closest('.meldegruppe-limits-section').find('.custom-limits-inputs')
                .toggle(hasCustom);
        });
        
        $('.meldegruppe-limits-form').on('submit', function(e) {
            e.preventDefault();
            const species = $(this).data('species');
            const meldegruppe = $(this).data('meldegruppe');
            saveMeldegruppeLimits(species, meldegruppe, $(this));
        });
    }

    /**
     * Save species default limits
     */
    function saveSpeciesDefaultLimits(species, $form) {
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Speichern...');
        
        const formData = $form.serializeArray();
        const limits = {};
        const allowExceeding = {};
        
        formData.forEach(function(field) {
            if (field.name.startsWith('limits[')) {
                const category = field.name.match(/limits\[(.+)\]/)[1];
                limits[category] = field.value;
            } else if (field.name.startsWith('allow_exceeding[')) {
                const category = field.name.match(/allow_exceeding\[(.+)\]/)[1];
                allowExceeding[category] = true;
            }
        });
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ahgmh_save_species_default_limits',
                species: species,
                limits: limits,
                allow_exceeding: allowExceeding,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Speichern der Standard-Limits.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Toggle custom limits for meldegruppe
     */
    function toggleMeldegruppeCustomLimits(species, meldegruppe, hasCustom) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ahgmh_toggle_meldegruppe_custom_limits',
                species: species,
                meldegruppe: meldegruppe,
                has_custom_limits: hasCustom,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Ändern der Limits-Einstellung.', 'error');
            }
        });
    }

    /**
     * Save meldegruppe limits
     */
    function saveMeldegruppeLimits(species, meldegruppe, $form) {
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Speichern...');
        
        const formData = $form.serializeArray();
        const limits = {};
        const allowExceeding = {};
        
        formData.forEach(function(field) {
            if (field.name.startsWith('limits[')) {
                const category = field.name.match(/limits\[(.+)\]/)[1];
                limits[category] = field.value;
            } else if (field.name.startsWith('allow_exceeding[')) {
                const category = field.name.match(/allow_exceeding\[(.+)\]/)[1];
                allowExceeding[category] = true;
            }
        });
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ahgmh_save_meldegruppe_limits',
                species: species,
                meldegruppe: meldegruppe,
                limits: limits,
                allow_exceeding: allowExceeding,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
            showNotification('Fehler beim Speichern der Meldegruppen-Limits.', 'error');
            },
            complete: function() {
            $submitBtn.prop('disabled', false).text(originalText);
            }
            });
            }

    /**
     * Initialize Jagdbezirke Interface
     */
    function initJagdbezirkeInterface() {
        // Initialize wildart dropdown state on page load
        initWildartDropdownState();
        
        // Manage limits button click handler
        $(document).on('click', '.ahgmh-manage-limits', function(e) {
            e.preventDefault();
            const meldegruppe = $(this).data('meldegruppe');
            const jagdbezirk = $(this).data('jagdbezirk');
            
            // Check if modal exists
            if ($('#limits-config-modal').length === 0) {
                alert('Fehler: Modal nicht gefunden. Bitte Seite neu laden.');
                return;
            }
            
            // Check if required data is available
            if (!meldegruppe || !jagdbezirk) {
                alert('Fehler: Unvollständige Daten. Bitte Seite neu laden.');
                return;
            }
            
            openLimitsModal(meldegruppe, jagdbezirk);
        });

        // Modal close handlers
        $(document).on('click', '.ahgmh-modal-close', function() {
            $('#limits-config-modal').hide();
        });

        // Save limits config button
        $(document).on('click', '#save-limits-config', function() {
            saveLimitsFromModal();
        });

        // Close modal when clicking outside
        $(document).on('click', '#limits-config-modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });

        // Wildart-specific checkbox change handler
        $(document).on('change', '#use_wildart_specific_meldegruppen', function() {
            const isChecked = $(this).is(':checked');
            const $checkbox = $(this);
            const $dropdown = $('#wildart_selector');
            const $dropdownContainer = $dropdown.closest('div');
            
            // Update dropdown state immediately for better UX
            if (isChecked) {
                $dropdown.prop('disabled', false);
                $dropdownContainer.css('opacity', '1');
            } else {
                $dropdown.prop('disabled', true);
                $dropdownContainer.css('opacity', '0.5');
            }
            
            // Disable checkbox during request
            $checkbox.prop('disabled', true);
            
            const currentWildart = $dropdown.val();
            
            $.ajax({
                url: ahgmh_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ahgmh_toggle_wildart_specific',
                    enabled: isChecked ? 1 : 0,
                    current_wildart: currentWildart,
                    nonce: ahgmh_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                        // Reload page to update interface
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(response.data, 'error');
                        // Revert checkbox and dropdown state on error
                        $checkbox.prop('checked', !isChecked);
                        if (!isChecked) {
                            $dropdown.prop('disabled', false);
                            $dropdownContainer.css('opacity', '1');
                        } else {
                            $dropdown.prop('disabled', true);
                            $dropdownContainer.css('opacity', '0.5');
                        }
                    }
                },
                error: function() {
                    showNotification('Fehler beim Ändern des Meldegruppen-Modus.', 'error');
                    // Revert checkbox and dropdown state on error
                    $checkbox.prop('checked', !isChecked);
                    if (!isChecked) {
                        $dropdown.prop('disabled', false);
                        $dropdownContainer.css('opacity', '1');
                    } else {
                        $dropdown.prop('disabled', true);
                        $dropdownContainer.css('opacity', '0.5');
                    }
                },
                complete: function() {
                    $checkbox.prop('disabled', false);
                }
            });
        });

        // Wildart dropdown change handler
        $(document).on('change', '#wildart_selector', function() {
            const selectedWildart = $(this).val();
            const $dropdown = $(this);
            
            // Disable dropdown during request
            $dropdown.prop('disabled', true);
            
            // Save current wildart selection and reload jagdbezirke
            $.ajax({
                url: ahgmh_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ahgmh_change_wildart',
                    wildart: selectedWildart,
                    nonce: ahgmh_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload jagdbezirke table
                        loadJagdbezirkeForWildart(selectedWildart);
                    } else {
                        showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    showNotification('Fehler beim Wechseln der Wildart.', 'error');
                },
                complete: function() {
                    $dropdown.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize wildart dropdown state based on checkbox
     */
    function initWildartDropdownState() {
        const $checkbox = $('#use_wildart_specific_meldegruppen');
        const $dropdown = $('#wildart_selector');
        const $dropdownContainer = $dropdown.closest('div');
        
        if ($checkbox.length && $dropdown.length) {
            const isChecked = $checkbox.is(':checked');
            
            if (isChecked) {
                $dropdown.prop('disabled', false);
                $dropdownContainer.css('opacity', '1');
            } else {
                $dropdown.prop('disabled', true);
                $dropdownContainer.css('opacity', '0.5');
            }
        }
    }

    /**
     * Load jagdbezirke for selected wildart
     */
    function loadJagdbezirkeForWildart(wildart) {
        const $jagdbezirkeSection = $('.ahgmh-settings-section').has('table.wp-list-table');
        const $tableContainer = $jagdbezirkeSection.find('table').parent();
        
        // Show loading state
        $tableContainer.html('<div style="text-align: center; padding: 40px;"><div class="spinner is-active"></div><p>Lade Jagdbezirke für ' + wildart + '...</p></div>');
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_load_wildart_jagdbezirke',
                wildart: wildart,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $tableContainer.html(response.data.html);
                } else {
                    $tableContainer.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $tableContainer.html('<div class="notice notice-error"><p>Fehler beim Laden der Jagdbezirke.</p></div>');
            }
        });
    }

    /**
     * Open limits configuration modal
     */
    function openLimitsModal(meldegruppe, jagdbezirk) {
        const $modal = $('#limits-config-modal');
        const $content = $('#limits-config-content');
        
        if ($modal.length === 0) {
            alert('Fehler: Modal-Element nicht gefunden');
            return;
        }
        
        // Store current meldegruppe and jagdbezirk for saving
        $modal.data('meldegruppe', meldegruppe);
        $modal.data('jagdbezirk', jagdbezirk);
        
        // Set modal title
        $modal.find('.ahgmh-modal-header h3').text('Limits für Meldegruppe "' + meldegruppe + '" (Jagdbezirk: ' + jagdbezirk + ') konfigurieren');
        
        // Show loading state
        $content.html('<div style="text-align: center; padding: 20px;"><div class="spinner is-active"></div><p>Lade Konfiguration...</p></div>');
        $modal.show();

        // Load current configuration
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_load_jagdbezirk_limits',
                meldegruppe: meldegruppe,
                jagdbezirk: jagdbezirk,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderLimitsModal(response.data, meldegruppe);
                } else {
                    $content.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                $content.html('<div class="notice notice-error"><p>Fehler beim Laden der Konfiguration. Bitte erneut versuchen.</p></div>');
            }
        });
    }

    /**
     * Render limits configuration in modal
     */
    function renderLimitsModal(data, meldegruppe) {
        const $content = $('#limits-config-content');
        let html = '<div class="limits-config-wrapper">';
        
        // Species tabs
        html += '<div class="nav-tab-wrapper">';
        Object.keys(data.species).forEach(function(species, index) {
            const activeClass = index === 0 ? 'nav-tab-active' : '';
            html += '<a href="#" class="nav-tab ' + activeClass + '" data-species="' + species + '">' + species + '</a>';
        });
        html += '</div>';

        // Species content
        Object.keys(data.species).forEach(function(species, index) {
            const categories = data.species[species];
            const limits = data.limits[species] || {};
            const allowExceeding = data.allow_exceeding[species] || {};
            const hasCustomLimits = data.has_custom_limits[species] || false;
            const displayStyle = index === 0 ? 'block' : 'none';

            html += '<div class="species-limits-tab" data-species="' + species + '" style="display: ' + displayStyle + ';">';
            html += '<div style="margin: 20px 0;">';
            html += '<label><input type="checkbox" class="custom-limits-toggle" data-species="' + species + '" ' + (hasCustomLimits ? 'checked' : '') + '> Eigene Limits für ' + species + ' verwenden</label>';
            html += '</div>';

            html += '<div class="limits-config-table" style="' + (hasCustomLimits ? '' : 'opacity: 0.5; pointer-events: none;') + '">';
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>Kategorie</th><th>Limit</th><th>Überschreitung erlauben</th></tr></thead>';
            html += '<tbody>';

            categories.forEach(function(category) {
                const currentLimit = limits[category] || 0;
                const allowExceed = allowExceeding[category] || false;
                html += '<tr>';
                html += '<td>' + category + '</td>';
                html += '<td><input type="number" class="small-text" name="limit_' + species + '_' + category + '" value="' + currentLimit + '" min="0"></td>';
                html += '<td><input type="checkbox" name="allow_' + species + '_' + category + '" ' + (allowExceed ? 'checked' : '') + '></td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        $content.html(html);

        // Initialize tab switching
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.species-limits-tab').hide();
            $('.species-limits-tab[data-species="' + $(this).data('species') + '"]').show();
        });

        // Initialize custom limits toggle
        $('.custom-limits-toggle').on('change', function() {
            const $table = $(this).closest('.species-limits-tab').find('.limits-config-table');
            if ($(this).is(':checked')) {
                $table.css({'opacity': '1', 'pointer-events': 'auto'});
            } else {
                $table.css({'opacity': '0.5', 'pointer-events': 'none'});
            }
        });
    }

    /**
     * Save limits configuration from modal
     */
    function saveLimitsFromModal() {
        const $button = $('#save-limits-config');
        const originalText = $button.text();
        $button.prop('disabled', true).text('Speichere...');

        // Get stored meldegruppe and jagdbezirk
        const $modal = $('#limits-config-modal');
        const meldegruppe = $modal.data('meldegruppe');
        const jagdbezirk = $modal.data('jagdbezirk');

        // Collect all data
        const configData = {};
        
        $('.species-limits-tab').each(function() {
            const species = $(this).data('species');
            const hasCustomLimits = $(this).find('.custom-limits-toggle').is(':checked');
            
            configData[species] = {
                has_custom_limits: hasCustomLimits,
                limits: {},
                allow_exceeding: {}
            };

            if (hasCustomLimits) {
                $(this).find('input[name^="limit_' + species + '_"]').each(function() {
                    const category = $(this).attr('name').replace('limit_' + species + '_', '');
                    configData[species].limits[category] = parseInt($(this).val()) || 0;
                });

                $(this).find('input[name^="allow_' + species + '_"]').each(function() {
                    const category = $(this).attr('name').replace('allow_' + species + '_', '');
                    configData[species].allow_exceeding[category] = $(this).is(':checked');
                });
            }
        });

        // Send data to server
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_jagdbezirk_limits',
                meldegruppe: meldegruppe,
                jagdbezirk: jagdbezirk,
                config_data: configData,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $('#limits-config-modal').hide();
                    // Refresh the page to update the limits status
                    location.reload();
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Speichern der Limits.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Initialize Wildart Configuration Interface
     */
    function initWildartConfig() {
        // Wildart navigation click handler
        $(document).on('click', '.wildart-item', function(e) {
            // Don't trigger if delete button was clicked
            if ($(e.target).closest('.wildart-delete').length) {
                return;
            }
            
            const wildart = $(this).data('wildart');
            const $item = $(this);
            
            // Update active state
            $('.wildart-item').removeClass('active');
            $item.addClass('active');
            
            // Load wildart configuration
            loadWildartConfig(wildart);
        });
        
        // Add new wildart button handler
        $(document).on('click', '#add-new-wildart', function(e) {
            e.preventDefault();
            toggleNewWildartForm(true);
        });
        
        // Cancel new wildart button handler
        $(document).on('click', '#cancel-new-wildart', function(e) {
            e.preventDefault();
            toggleNewWildartForm(false);
        });
        
        // Save new wildart button handler
        $(document).on('click', '#save-new-wildart', function(e) {
            e.preventDefault();
            saveNewWildart();
        });
        
        // Delete wildart button handler
        $(document).on('click', '.wildart-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const wildart = $(this).data('wildart');
            
            if (confirm(`Wildart "${wildart}" wirklich löschen? Alle zugehörigen Daten gehen verloren!`)) {
                deleteWildart(wildart);
            }
        });
        
        // Add category button handler
        $(document).on('click', '#add-category', function(e) {
            e.preventDefault();
            addNewConfigItem('category');
        });
        
        // Add meldegruppe button handler
        $(document).on('click', '#add-meldegruppe', function(e) {
            e.preventDefault();
            addNewConfigItem('meldegruppe');
        });
        
        // Remove item button handler
        $(document).on('click', '.remove-item', function(e) {
            e.preventDefault();
            const $item = $(this).closest('.config-item');
            $item.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Save categories button handler
        $(document).on('click', '.save-categories', function(e) {
            e.preventDefault();
            const wildart = $(this).data('wildart');
            saveWildartCategories(wildart);
        });
        
        // Save meldegruppen button handler
        $(document).on('click', '.save-meldegruppen', function(e) {
            e.preventDefault();
            const wildart = $(this).data('wildart');
            saveWildartMeldegruppen(wildart);
        });
        
        // Enter key handler for new item inputs
        $(document).on('keypress', '#new-category-input, #new-meldegruppe-input, #new-wildart-name', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                
                const inputId = $(this).attr('id');
                if (inputId === 'new-category-input') {
                    addNewConfigItem('category');
                } else if (inputId === 'new-meldegruppe-input') {
                    addNewConfigItem('meldegruppe');
                } else if (inputId === 'new-wildart-name') {
                    saveNewWildart();
                }
            }
        });
        
        // === LIMITS FUNCTIONALITY ===
        
        // Limit mode radio button change handler
        $(document).on('change', '.limit-mode-radio', function(e) {
            var wildart = $(this).data('wildart');
            var mode = $(this).val();
            toggleLimitMode(wildart, mode);
        });
        
        // Limit input change handler for auto-calculation
        $(document).on('change', '.limit-input', function(e) {
            var $input = $(this);
            var category = $input.closest('tr').find('td:first-child').text().trim();
            updateGesamt(category);
        });
        
        // Save limits button handler
        $(document).on('click', '.save-limits-btn', function(e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            saveLimits(wildart);
        });
    }
    
    /**
     * Toggle new wildart form visibility
     */
    function toggleNewWildartForm(show) {
        const $form = $('#new-wildart-form');
        const $button = $('#add-new-wildart');
        
        if (show) {
            $form.slideDown(300);
            $button.hide();
            $('#new-wildart-name').focus();
        } else {
            $form.slideUp(300);
            $button.show();
            $('#new-wildart-name').val('');
        }
    }
    
    /**
     * Save new wildart
     */
    function saveNewWildart() {
        const wildartName = $('#new-wildart-name').val().trim();
        const $button = $('#save-new-wildart');
        const originalText = $button.text();
        
        if (!wildartName) {
            showNotification('Bitte geben Sie einen Namen für die Wildart ein.', 'error');
            return;
        }
        
        $button.prop('disabled', true).text('Speichere...');
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_create_wildart',
                wildart_name: wildartName,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    // Reload page to show new wildart
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotification('Fehler beim Erstellen der Wildart.', 'error');
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Delete wildart
     */
    function deleteWildart(wildartName) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_delete_wildart',
                wildart_name: wildartName,
                confirm_delete_data: true,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    // Reload page to update wildart list
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Löschen der Wildart.', 'error');
            }
        });
    }
    
    /**
     * Load wildart configuration
     */
    function loadWildartConfig(wildart) {
        const $detailContent = $('#wildart-detail-content');
        
        // Show loading state
        $detailContent.html('<div class="wildart-config-loading">Lade Konfiguration für ' + wildart + '...</div>');
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_load_wildart_config',
                wildart: wildart,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $detailContent.html(response.data.html);
                } else {
                    $detailContent.html('<div class="config-empty">Fehler beim Laden der Konfiguration: ' + response.data + '</div>');
                }
            },
            error: function() {
                $detailContent.html('<div class="config-empty">Fehler beim Laden der Konfiguration.</div>');
            }
        });
    }
    
    /**
     * Add new config item (category or meldegruppe)
     */
    function addNewConfigItem(type) {
        const inputId = type === 'category' ? '#new-category-input' : '#new-meldegruppe-input';
        const listId = type === 'category' ? '#categories-list' : '#meldegruppen-list';
        const $input = $(inputId);
        const $list = $(listId);
        const value = $input.val().trim();
        
        if (!value) {
            showNotification('Bitte geben Sie einen Namen ein.', 'error');
            return;
        }
        
        // Check for duplicates
        let exists = false;
        $list.find('.config-item input[type="text"]').each(function() {
            if ($(this).val().toLowerCase() === value.toLowerCase()) {
                exists = true;
                return false;
            }
        });
        
        if (exists) {
            showNotification('Ein Eintrag mit diesem Namen existiert bereits.', 'error');
            return;
        }
        
        // Create new item HTML
        const itemHtml = `
            <div class="config-item" style="display: none;">
                <input type="text" value="${value}" class="${type}-input" data-original="${value}">
                <button type="button" class="remove-item" data-type="${type}" data-value="${value}">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `;
        
        // Add to list with animation
        $list.append(itemHtml);
        $list.find('.config-item:last').slideDown(300);
        
        // Clear input
        $input.val('');
    }
    
    /**
     * Save wildart categories
     */
    function saveWildartCategories(wildart) {
        const $button = $('.save-categories[data-wildart="' + wildart + '"]');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Speichere...');
        
        // Collect all categories
        const categories = [];
        $('#categories-list .config-item input[type="text"]').each(function() {
            const value = $(this).val().trim();
            if (value) {
                categories.push(value);
            }
        });
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_wildart_categories',
                wildart: wildart,
                categories: categories,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    // Update overview stats
                    setTimeout(function() {
                        loadWildartConfig(wildart);
                    }, 1000);
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Speichern der Kategorien.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Save wildart meldegruppen
     */
    function saveWildartMeldegruppen(wildart) {
        const $button = $('.save-meldegruppen[data-wildart="' + wildart + '"]');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Speichere...');
        
        // Collect all meldegruppen
        const meldegruppen = [];
        $('#meldegruppen-list .config-item input[type="text"]').each(function() {
            const value = $(this).val().trim();
            if (value) {
                meldegruppen.push(value);
            }
        });
        
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_wildart_meldegruppen',
                wildart: wildart,
                meldegruppen: meldegruppen,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    // Update overview stats
                    setTimeout(function() {
                        loadWildartConfig(wildart);
                    }, 1000);
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Speichern der Meldegruppen.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Toggle limit mode between jagdbezirk-specific and hegegemeinschaft-total
     */
    function toggleLimitMode(wildart, mode) {
        // Show/hide appropriate matrix containers
        var $jagdbezirk_matrix = $('#jagdbezirk-limits-' + wildart);
        var $hegegemeinschaft_matrix = $('#hegegemeinschaft-limits-' + wildart);
        
        if (mode === 'jagdbezirk_specific') {
            $jagdbezirk_matrix.show();
            $hegegemeinschaft_matrix.hide();
        } else {
            $jagdbezirk_matrix.hide();
            $hegegemeinschaft_matrix.show();
        }
        
        // Send AJAX to save mode preference
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_toggle_limit_mode',
                wildart: wildart,
                mode: mode,
                nonce: ahgmh_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data || 'Fehler beim Ändern des Limit-Modus.', 'error');
                }
            },
            error: function() {
                showNotification('Fehler beim Ändern des Limit-Modus.', 'error');
            }
        });
    }
    
    /**
     * Update gesamt (total) calculation for a category row
     */
    function updateGesamt(category) {
        // Use the same logic as PHP sanitize_title(): lowercase, replace non-alphanumeric with dashes
        var categoryKey = category.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        var $gesamtCell = $('#gesamt_' + categoryKey);
        
        if ($gesamtCell.length === 0) {
            return;
        }
        
        var total = 0;
        var $row = $gesamtCell.closest('tr');
        
        // Sum up all limit inputs in this row (excluding first and last columns)
        $row.find('.limit-input').each(function() {
            var value = parseInt($(this).val()) || 0;
            total += value;
        });
        
        $gesamtCell.text(total);
    }
    
    /**
     * Save limits for a wildart
     */
    function saveLimits(wildart) {
        var $statusDiv = $('#limits-save-status-' + wildart);
        $statusDiv.removeClass('success error').text('Speichern...');
        
        // Collect all form data
        var formData = new FormData();
        formData.append('action', 'ahgmh_save_limits');
        formData.append('wildart', wildart);
        formData.append('nonce', ahgmh_admin.nonce);
        
        // Get current limit mode
        var mode = $('input[name="limit_mode_' + wildart + '"]:checked').val();
        
        if (mode === 'meldegruppen_specific') {
            // Collect meldegruppen-specific limits
            var limits = {};
            $('.limit-input').each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                
                if (name && name.includes('[' + wildart + ']')) {
                    var value = $input.val();
                    if (value !== '') {
                        // Parse name attribute: limit[Rotwild][Meldegruppe1][Männlich]
                        var match = name.match(/limit\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]/);
                        if (match) {
                            var w = match[1], meldegruppe = match[2], kategorie = match[3];
                            if (w === wildart) {
                                if (!limits[w]) limits[w] = {};
                                if (!limits[w][meldegruppe]) limits[w][meldegruppe] = {};
                                limits[w][meldegruppe][kategorie] = value;
                            }
                        }
                    }
                }
            });
            
            // Add to form data
            for (var w in limits) {
                if (limits.hasOwnProperty(w)) {
                    for (var meldegruppe in limits[w]) {
                        if (limits[w].hasOwnProperty(meldegruppe)) {
                            for (var kategorie in limits[w][meldegruppe]) {
                                if (limits[w][meldegruppe].hasOwnProperty(kategorie)) {
                                    var value = limits[w][meldegruppe][kategorie];
                                    formData.append('limit[' + w + '][' + meldegruppe + '][' + kategorie + ']', value);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Collect hegegemeinschaft total limits
            $('.hegegemeinschaft-limit-input').each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                
                if (name && name.includes('[' + wildart + ']')) {
                    var value = $input.val();
                    if (value !== '') {
                        formData.append(name, value);
                    }
                }
            });
        }
        
        // Send AJAX request
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $statusDiv.addClass('success').text(response.data.message);
                    showNotification(response.data.message, 'success');
                } else {
                    $statusDiv.addClass('error').text(response.data || 'Fehler beim Speichern der Limits.');
                    showNotification(response.data || 'Fehler beim Speichern der Limits.', 'error');
                }
            },
            error: function() {
                var errorMsg = 'Fehler beim Speichern der Limits.';
                $statusDiv.addClass('error').text(errorMsg);
                showNotification(errorMsg, 'error');
            },
            complete: function() {
                // Clear status after 3 seconds
                setTimeout(function() {
                    $statusDiv.removeClass('success error').text('');
                }, 3000);
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Check if ahgmh_admin object is available
        if (typeof ahgmh_admin === 'undefined') {
            return;
        }

        initAdmin();
    });

})(jQuery);
