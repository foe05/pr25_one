/**
 * Wildart Configuration Module
 *
 * Handles wildart configuration including master-detail UI, sortable lists, and CRUD operations
 */
(function ($) {
    'use strict';

    /**
     * Initialize Wildart Configuration (Master-Detail UI)
     */
    function initWildartConfig() {
        if ($('.ahgmh-wildart-config').length === 0) return;

        // Initialize sortable on wildart list
        initWildartSortable();

        // Create new wildart
        $(document).on('click', '#add-new-wildart', function (e) {
            e.preventDefault();
            var name = prompt('Name der neuen Wildart:');
            if (name && name.trim()) {
                createWildart(name.trim());
            }
        });

        // Delete wildart
        $(document).on('click', '.wildart-delete', function (e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            if (confirm('Wildart "' + wildart + '" wirklich löschen?')) {
                deleteWildart(wildart);
            }
        });

        // Wildart navigation
        $(document).on('click', '.wildart-item', function (e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            $('.wildart-item').removeClass('active');
            $(this).addClass('active');
            loadWildartConfig(wildart);
        });

        // Save categories
        $(document).on('click', '.save-categories', function (e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            saveWildartCategories(wildart);
        });

        // Save meldegruppen
        $(document).on('click', '.save-meldegruppen', function (e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            saveWildartMeldegruppen(wildart);
        });

        // Toggle limit mode
        $(document).on('change', '.limit-mode-radio', function () {
            var wildart = $(this).data('wildart');
            var mode = $(this).val();
            toggleLimitMode(wildart, mode);
        });

        // Save limits
        $(document).on('click', '.save-limits-btn', function (e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            saveLimits(wildart);
        });

        // Update totals when individual limits change + validate
        $(document).on('input', '.limit-input, .hegegemeinschaft-limit-input', function () {
            updateGesamt();
            validateLimitInputs();
        });

        // Category CRUD handlers
        $(document).on('click', '#add-category', function (e) {
            e.preventDefault();
            var newCategoryValue = $('#new-category-input').val().trim();
            if (newCategoryValue) {
                var newCategoryHtml = '<div class="config-item">' +
                    '<input type="text" value="' + newCategoryValue + '" class="category-input" data-original="' + newCategoryValue + '">' +
                    '<button type="button" class="remove-item" data-type="category" data-value="' + newCategoryValue + '">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                    '</div>';
                $('#categories-list').append(newCategoryHtml);
                $('#new-category-input').val('');
            }
        });

        // Remove category handler
        $(document).on('click', '.remove-item[data-type="category"]', function (e) {
            e.preventDefault();
            $(this).closest('.config-item').remove();
        });

        // Meldegruppe CRUD handlers
        $(document).on('click', '#add-meldegruppe', function (e) {
            e.preventDefault();
            var newMeldegruppeValue = $('#new-meldegruppe-input').val().trim();
            if (newMeldegruppeValue) {
                var newMeldegruppeHtml = '<div class="config-item">' +
                    '<input type="text" value="' + newMeldegruppeValue + '" class="meldegruppe-input" data-original="' + newMeldegruppeValue + '">' +
                    '<button type="button" class="remove-item" data-type="meldegruppe" data-value="' + newMeldegruppeValue + '">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                    '</div>';
                $('#meldegruppen-list').append(newMeldegruppeHtml);
                $('#new-meldegruppe-input').val('');
            }
        });

        // Remove meldegruppe handler
        $(document).on('click', '.remove-item[data-type="meldegruppe"]', function (e) {
            e.preventDefault();
            $(this).closest('.config-item').remove();
        });

        // Enter key support for add inputs
        $(document).on('keypress', '#new-category-input', function (e) {
            if (e.which === 13) {
                $('#add-category').click();
            }
        });

        $(document).on('keypress', '#new-meldegruppe-input', function (e) {
            if (e.which === 13) {
                $('#add-meldegruppe').click();
            }
        });

        // Load first wildart if available
        var firstWildart = $('.wildart-item').first();
        if (firstWildart.length > 0) {
            firstWildart.click();
        }
    }

    /**
     * Initialize drag & drop sorting for wildart list
     */
    function initWildartSortable() {
        var $sortableList = $('#sortable-wildart-list');

        if ($sortableList.length === 0 || $('.wildart-item').length === 0) {
            return;
        }

        // Initialize jQuery UI Sortable
        $sortableList.sortable({
            items: '.wildart-item',
            handle: '.wildart-drag-handle',
            axis: 'y',
            cursor: 'move',
            opacity: 0.8,
            placeholder: 'wildart-item-placeholder',
            helper: 'clone',
            tolerance: 'pointer',
            start: function(event, ui) {
                ui.item.addClass('wildart-dragging');
                ui.placeholder.height(ui.item.height());
            },
            stop: function(event, ui) {
                ui.item.removeClass('wildart-dragging');
            },
            update: function(event, ui) {
                // Show save button when order changes
                $('#save-wildart-order').fadeIn();
            }
        });

        // Save wildart order button
        $(document).on('click', '#save-wildart-order', function(e) {
            e.preventDefault();
            saveWildartOrder();
        });
    }

    /**
     * Save wildart sort order
     */
    function saveWildartOrder() {
        var order = [];
        $('.wildart-item').each(function(index) {
            order.push($(this).data('wildart'));
        });

        var $btn = $('#save-wildart-order');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update ahgmh-spinning"></span> Speichern...');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_wildart_order',
                nonce: ahgmh_admin.nonce,
                order: order
            },
            success: function(response) {
                if (response.success) {
                    window.AHGMH.showNotification('Reihenfolge gespeichert!', 'success');
                    $btn.fadeOut();

                    // Update data-order attributes
                    $('.wildart-item').each(function(index) {
                        $(this).attr('data-order', index);
                    });
                } else {
                    window.AHGMH.showNotification('Fehler beim Speichern: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                window.AHGMH.showNotification('Netzwerkfehler beim Speichern.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Create new wildart
     */
    function createWildart(name) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_create_wildart',
                nonce: ahgmh_admin.nonce,
                name: name
            },
            success: function (response) {
                if (response.success) {
                    window.AHGMH.showNotification('Wildart erfolgreich erstellt!', 'success');
                    // Reload to show new wildart and refresh all lists
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                } else {
                    window.AHGMH.showNotification('Fehler beim Erstellen der Wildart: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim Erstellen der Wildart.', 'error');
            }
        });
    }

    /**
     * Delete wildart
     */
    function deleteWildart(wildart) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_delete_wildart',
                nonce: ahgmh_admin.nonce,
                wildart: wildart
            },
            success: function (response) {
                if (response.success) {
                    location.reload(); // Reload to remove deleted wildart
                } else {
                    window.AHGMH.showNotification('Fehler beim Löschen der Wildart: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim Löschen der Wildart.', 'error');
            }
        });
    }

    /**
     * Load wildart configuration
     */
    function loadWildartConfig(wildart) {
        var $detailPanel = $('.ahgmh-detail-panel');
        $detailPanel.html('<div class="loading">Lade Konfiguration...</div>');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_load_wildart_config',
                nonce: ahgmh_admin.nonce,
                wildart: wildart
            },
            success: function (response) {
                if (response.success) {
                    $detailPanel.html(response.data);
                } else {
                    $detailPanel.html('<div class="error">Fehler beim Laden: ' + (response.data || 'Unbekannter Fehler') + '</div>');
                }
            },
            error: function () {
                $detailPanel.html('<div class="error">Netzwerkfehler beim Laden der Konfiguration.</div>');
            }
        });
    }

    /**
     * Save wildart categories
     */
    function saveWildartCategories(wildart) {
        var categories = [];
        $('.category-input').each(function () {
            var value = $(this).val().trim();
            if (value) {
                categories.push(value);
            }
        });

        var $btn = $('.save-categories[data-wildart="' + wildart + '"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Speichern...');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_wildart_categories',
                nonce: ahgmh_admin.nonce,
                wildart: wildart,
                categories: categories
            },
            success: function (response) {
                if (response.success) {
                    window.AHGMH.showNotification('Kategorien gespeichert!', 'success');
                    // Reload the wildart config to update limits section
                    setTimeout(function () {
                        loadWildartConfig(wildart);
                    }, 500);
                } else {
                    window.AHGMH.showNotification('Fehler beim Speichern: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim Speichern.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Save wildart meldegruppen
     */
    function saveWildartMeldegruppen(wildart) {
        var meldegruppen = [];
        $('.meldegruppe-input').each(function () {
            var value = $(this).val().trim();
            if (value) {
                meldegruppen.push(value);
            }
        });

        var $btn = $('.save-meldegruppen[data-wildart="' + wildart + '"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Speichern...');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_wildart_meldegruppen',
                nonce: ahgmh_admin.nonce,
                wildart: wildart,
                meldegruppen: meldegruppen
            },
            success: function (response) {
                if (response.success) {
                    window.AHGMH.showNotification('Meldegruppen gespeichert!', 'success');
                    // TEMP FIX: Reload entire page instead of just config
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                } else {
                    window.AHGMH.showNotification('Fehler beim Speichern: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim Speichern.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Toggle limit mode and update display
     */
    function toggleLimitMode(wildart, mode) {
        // Immediately update display based on mode
        if (mode === 'meldegruppen_specific') {
            $('#meldegruppen-limits-' + wildart).show();
            $('#hegegemeinschaft-limits-' + wildart).hide();
        } else {
            $('#meldegruppen-limits-' + wildart).hide();
            $('#hegegemeinschaft-limits-' + wildart).show();
        }

        // Save mode to database
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_toggle_limit_mode',
                nonce: ahgmh_admin.nonce,
                wildart: wildart,
                mode: mode
            },
            success: function (response) {
                if (response.success) {
                    window.AHGMH.showNotification('Limit-Modus geändert!', 'success');
                    // Reload the entire wildart config to rebuild limits section
                    setTimeout(function () {
                        loadWildartConfig(wildart);
                    }, 500);
                } else {
                    window.AHGMH.showNotification('Fehler beim Ändern des Modus: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim Ändern des Modus.', 'error');
            }
        });
    }

    /**
     * Update gesamt (total) calculations
     */
    function updateGesamt() {
        // Find all categories and calculate totals
        var categories = {};

        $('.limit-input').each(function () {
            var kategorie = $(this).data('kategorie');
            var value = parseInt($(this).val()) || 0;

            if (!categories[kategorie]) {
                categories[kategorie] = 0;
            }
            categories[kategorie] += value;
        });

        // Update all gesamt cells
        for (var kategorie in categories) {
            var gesamtId = 'gesamt_' + kategorie.toLowerCase().replace(/[^a-z0-9]/g, '_');
            $('#' + gesamtId).text(categories[kategorie]);
        }
    }

    /**
     * Validate all limit inputs in real-time
     */
    function validateLimitInputs() {
        var hasNegativeValues = false;
        var negativeFields = [];

        $('.limit-input, .hegegemeinschaft-limit-input').each(function() {
            var $input = $(this);
            var value = parseInt($input.val()) || 0;

            if (value < 0) {
                hasNegativeValues = true;
                negativeFields.push($input);
                $input.addClass('error-field');
                $input.css('border-color', '#dc3232');
            } else {
                $input.removeClass('error-field');
                $input.css('border-color', '');
            }
        });

        return { hasNegativeValues: hasNegativeValues, negativeFields: negativeFields };
    }

    /**
     * Save limits with enhanced validation
     */
    function saveLimits(wildart) {
        var limits = {};

        // Validate all inputs first
        var validation = validateLimitInputs();

        if (validation.hasNegativeValues) {
            var fieldCount = validation.negativeFields.length;
            window.AHGMH.showNotification('Fehler: ' + fieldCount + ' Feld(er) enthalten negative Werte. Bitte korrigieren Sie diese vor dem Speichern.', 'error');

            // Focus on first negative field
            if (validation.negativeFields.length > 0) {
                validation.negativeFields[0].focus();
            }
            return false; // Stop saving
        }

        $('.limit-input, .hegegemeinschaft-limit-input').each(function () {
            var $input = $(this);
            var meldegruppe = $input.data('meldegruppe') || 'gesamt';
            var kategorie = $input.data('kategorie');
            var value = Math.max(0, parseInt($input.val()) || 0); // Ensure non-negative

            // Handle both meldegruppen-specific and total limits
            if (!limits[meldegruppe]) {
                limits[meldegruppe] = {};
            }
            limits[meldegruppe][kategorie] = value;
        });

        var $btn = $('.save-limits-btn[data-wildart="' + wildart + '"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Speichern...');

        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_save_limits',
                nonce: ahgmh_admin.nonce,
                wildart: wildart,
                limits: limits
            },
            success: function (response) {
                if (response.success) {
                    window.AHGMH.showNotification('Limits erfolgreich gespeichert!', 'success');
                    // Clear any error styling
                    $('.limit-input, .hegegemeinschaft-limit-input').removeClass('error-field').css('border-color', '');
                } else {
                    window.AHGMH.showNotification('Fehler beim Speichern der Limits: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function () {
                window.AHGMH.showNotification('Netzwerkfehler beim Speichern der Limits.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            }
        });

        return true;
    }

    // Initialize when document is ready
    $(document).ready(function () {
        initWildartConfig();
    });

    // Export functions to global scope for use by other modules
    window.AHGMH_WildartConfig = {
        initWildartConfig: initWildartConfig,
        loadWildartConfig: loadWildartConfig,
        saveWildartCategories: saveWildartCategories,
        saveWildartMeldegruppen: saveWildartMeldegruppen,
        saveLimits: saveLimits
    };

})(jQuery);
