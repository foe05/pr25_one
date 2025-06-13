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

        // Species form
        $('#ahgmh-add-species-form').on('submit', function(e) {
            e.preventDefault();
            handleAddSpecies($(this));
        });

        // Delete species buttons
        $('.ahgmh-delete-species').on('click', function(e) {
            e.preventDefault();
            const species = $(this).data('species');
            const index = $(this).data('index');
            
            if (confirm(`Wildart "${species}" wirklich löschen?`)) {
                handleDeleteSpecies(species, index, $(this));
            }
        });

        // Category form
        $('#ahgmh-add-category-form').on('submit', function(e) {
            e.preventDefault();
            handleAddCategory($(this));
        });

        // Category settings form (for exceeding checkboxes)
        $('#ahgmh-category-settings-form').on('submit', function(e) {
            e.preventDefault();
            handleSaveCategorySettings($(this));
        });

        // Edit category buttons
        $('.ahgmh-edit-category').on('click', function(e) {
            e.preventDefault();
            const species = $(this).data('species');
            const category = $(this).data('category');
            const index = $(this).data('index');
            
            handleEditCategory(species, category, index, $(this));
        });

        // Delete category buttons
        $('.ahgmh-delete-category').on('click', function(e) {
            e.preventDefault();
            const species = $(this).data('species');
            const category = $(this).data('category');
            const index = $(this).data('index');
            
            if (confirm(`Kategorie "${category}" wirklich löschen?`)) {
                handleDeleteCategory(species, category, index, $(this));
            }
        });
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

})(jQuery);

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
