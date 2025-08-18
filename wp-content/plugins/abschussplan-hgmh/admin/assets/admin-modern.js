(function($) {
    'use strict';

    /**
     * Initialize admin functionality
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
     * Initialize quick actions
     */
    function initQuickActions() {
        // Quick CSV export - Fixed selector
        $(document).on('click', '#quick-export', function(e) {
            e.preventDefault();
            handleQuickExport($(this));
        });

        // Export buttons in tables
        $(document).on('click', '.ahgmh-export-btn', function(e) {
            e.preventDefault();
            var species = $(this).data('species') || '';
            var format = $(this).data('format') || 'csv';
            handleQuickExport($(this), species, format);
        });

        // Quick refresh
        $('.ahgmh-quick-refresh').on('click', function(e) {
            e.preventDefault();
            location.reload();
        });

        // Legacy event handlers removed - replaced by Master-Detail UI
    }

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
            success: function(response) {
                if (response.success && response.data.download_url) {
                    // Create temporary download link
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename || 'export.csv';
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotification('CSV Export erfolgreich!', 'success');
                } else {
                    showNotification('Fehler beim CSV Export: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim CSV Export.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Initialize tab switching
     */
    function initTabSwitching() {
        $('.ahgmh-tab').on('click', function(e) {
            // Let natural link navigation handle tab switching
            // This is just for visual feedback
            $('.ahgmh-tab').removeClass('active');
            $(this).addClass('active');
        });
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[title]').each(function() {
            $(this).attr('data-title', $(this).attr('title'));
            $(this).removeAttr('title');
        });
        
        $('[data-title]').hover(
            function() {
                const title = $(this).attr('data-title');
                const tooltip = $('<div class="ahgmh-tooltip">' + title + '</div>');
                $('body').append(tooltip);
                
                const pos = $(this).offset();
                tooltip.css({
                    top: pos.top - tooltip.outerHeight() - 10,
                    left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
                });
            },
            function() {
                $('.ahgmh-tooltip').remove();
            }
        );
    }

    /**
     * Initialize progress animations
     */
    function initProgressAnimations() {
        $('.progress-fill, .ahgmh-progress-bar').each(function() {
            const $bar = $(this);
            const targetWidth = $bar.attr('data-width') || $bar.data('width');
            
            if (targetWidth) {
                setTimeout(function() {
                    $bar.css('width', targetWidth + (targetWidth.toString().indexOf('%') > -1 ? '' : '%'));
                }, 500);
            }
        });
    }

    /**
     * Initialize auto refresh
     */
    function initAutoRefresh() {
        // Only enable auto-refresh on dashboard
        if ($('.ahgmh-dashboard').length > 0) {
            setInterval(function() {
                $('.ahgmh-stat-number').each(function() {
                    const $stat = $(this);
                    const currentValue = parseInt($stat.text());
                    // Simulate slight updates (this would normally come from AJAX)
                    animateNumber($stat, currentValue, currentValue);
                });
            }, 60000); // Every minute
        }
    }

    /**
     * Animate number changes
     */
    function animateNumber($element, start, end) {
        const duration = 1000;
        const startTime = Date.now();
        
        function update() {
            const now = Date.now();
            const progress = Math.min((now - startTime) / duration, 1);
            const value = Math.floor(start + (end - start) * progress);
            
            $element.text(value);
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        update();
    }

    /**
     * Initialize Wildart Configuration (Master-Detail UI)
     */
    function initWildartConfig() {
        if ($('.ahgmh-wildart-config').length === 0) return;

        // Create new wildart
        $(document).on('click', '#add-new-wildart', function(e) {
            e.preventDefault();
            var name = prompt('Name der neuen Wildart:');
            if (name && name.trim()) {
                createWildart(name.trim());
            }
        });

        // Delete wildart
        $(document).on('click', '.wildart-delete', function(e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            if (confirm('Wildart "' + wildart + '" wirklich löschen?')) {
                deleteWildart(wildart);
            }
        });

        // Wildart navigation
        $(document).on('click', '.wildart-item', function(e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            $('.wildart-item').removeClass('active');
            $(this).addClass('active');
            loadWildartConfig(wildart);
        });

        // Save categories
        $(document).on('click', '.save-categories', function(e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            saveWildartCategories(wildart);
        });

        // Save meldegruppen
        $(document).on('click', '.save-meldegruppen', function(e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            saveWildartMeldegruppen(wildart);
        });

        // Toggle limit mode
        $(document).on('change', '.limit-mode-radio', function() {
            var wildart = $(this).data('wildart');
            var mode = $(this).val();
            toggleLimitMode(wildart, mode);
        });

        // Save limits
        $(document).on('click', '.save-limits-btn', function(e) {
            e.preventDefault();
            var wildart = $(this).data('wildart');
            saveLimits(wildart);
        });

        // Update totals when individual limits change
        $(document).on('input', '.limit-input', function() {
            updateGesamt();
        });

        // Category CRUD handlers
        $(document).on('click', '#add-category', function(e) {
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
        $(document).on('click', '.remove-item[data-type="category"]', function(e) {
            e.preventDefault();
            $(this).closest('.config-item').remove();
        });

        // Meldegruppe CRUD handlers
        $(document).on('click', '#add-meldegruppe', function(e) {
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
        $(document).on('click', '.remove-item[data-type="meldegruppe"]', function(e) {
            e.preventDefault();
            $(this).closest('.config-item').remove();
        });

        // Enter key support for add inputs
        $(document).on('keypress', '#new-category-input', function(e) {
            if (e.which === 13) {
                $('#add-category').click();
            }
        });

        $(document).on('keypress', '#new-meldegruppe-input', function(e) {
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
            success: function(response) {
                if (response.success) {
                    showNotification('Wildart erfolgreich erstellt!', 'success');
                    // Reload to show new wildart and refresh all lists
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Fehler beim Erstellen der Wildart: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Erstellen der Wildart.', 'error');
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
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload to remove deleted wildart
                } else {
                    showNotification('Fehler beim Löschen der Wildart: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Löschen der Wildart.', 'error');
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
            success: function(response) {
                if (response.success) {
                    $detailPanel.html(response.data);
                } else {
                    $detailPanel.html('<div class="error">Fehler beim Laden: ' + (response.data || 'Unbekannter Fehler') + '</div>');
                }
            },
            error: function() {
                $detailPanel.html('<div class="error">Netzwerkfehler beim Laden der Konfiguration.</div>');
            }
        });
    }

    /**
     * Save wildart categories
     */
    function saveWildartCategories(wildart) {
        var categories = [];
        $('.category-input').each(function() {
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
            success: function(response) {
                if (response.success) {
                    showNotification('Kategorien gespeichert!', 'success');
                    // Reload the wildart config to update limits section
                    setTimeout(function() {
                        loadWildartConfig(wildart);
                    }, 500);
                } else {
                    showNotification('Fehler beim Speichern: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Speichern.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Save wildart meldegruppen
     */
    function saveWildartMeldegruppen(wildart) {
        var meldegruppen = [];
        $('.meldegruppe-input').each(function() {
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
            success: function(response) {
                if (response.success) {
                    showNotification('Meldegruppen gespeichert!', 'success');
                    // Reload the wildart config to update limits section
                    setTimeout(function() {
                        loadWildartConfig(wildart);
                    }, 500);
                } else {
                    showNotification('Fehler beim Speichern: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Speichern.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Toggle limit mode
     */
    function toggleLimitMode(wildart, mode) {
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_toggle_limit_mode',
                nonce: ahgmh_admin.nonce,
                wildart: wildart,
                mode: mode
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Limit-Modus geändert!', 'success');
                    // Reload the entire wildart config to rebuild limits section
                    setTimeout(function() {
                        loadWildartConfig(wildart);
                    }, 500);
                } else {
                    showNotification('Fehler beim Ändern des Modus: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Ändern des Modus.', 'error');
            }
        });
    }

    /**
     * Update gesamt (total) calculations
     */
    function updateGesamt() {
        // Find all categories and calculate totals
        var categories = {};
        
        $('.limit-input').each(function() {
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
     * Save limits
     */
    function saveLimits(wildart) {
        var limits = {};
        
        $('.limit-input').each(function() {
            var meldegruppe = $(this).data('meldegruppe');
            var kategorie = $(this).data('kategorie');
            var value = parseInt($(this).val()) || 0;
            
            // Handle both meldegruppen-specific and total limits
            if (!limits[meldegruppe]) {
                limits[meldegruppe] = {};
            }
            limits[meldegruppe][kategorie] = value;
        });
        
        // Debug info
        console.log('Saving limits for ' + wildart + ':', limits);

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
            success: function(response) {
                if (response.success) {
                    showNotification('Limits gespeichert!', 'success');
                } else {
                    showNotification('Fehler beim Speichern der Limits: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Speichern der Limits.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var notification = $('<div class="ahgmh-notification ahgmh-notification-' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Hide notification
     */
    function hideNotification() {
        $('.ahgmh-notification').removeClass('show');
        setTimeout(function() {
            $('.ahgmh-notification').remove();
        }, 300);
    }

// Add CSS for notifications
var notificationCSS = `
<style>
.ahgmh-notifications {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
}

.ahgmh-notification {
    background: #fff;
    border-left: 4px solid #0073aa;
    padding: 12px 20px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 4px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.ahgmh-notification.show {
    opacity: 1;
    transform: translateX(0);
}

.ahgmh-notification-success {
    border-left-color: #46b450;
}

.ahgmh-notification-error {
    border-left-color: #dc3232;
}

.ahgmh-notification-warning {
    border-left-color: #ffb900;
}

.ahgmh-tooltip {
    position: absolute;
    background: #333;
    color: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 10000;
    max-width: 200px;
    word-wrap: break-word;
}

.ahgmh-tooltip:before {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid #333;
}
</style>
`;

// Append notification CSS to head
if ($('#ahgmh-notification-styles').length === 0) {
    $('head').append('<style id="ahgmh-notification-styles">' + notificationCSS + '</style>');
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
