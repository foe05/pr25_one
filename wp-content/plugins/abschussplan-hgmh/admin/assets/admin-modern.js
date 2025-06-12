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

        // Other quick action buttons can be added here
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
                    newValue = stats.this_month;
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
