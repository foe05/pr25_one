/**
 * Core Admin Module
 * Provides essential functionality for notifications, tooltips, and tab switching
 */
(function ($) {
    'use strict';

    /**
     * Show notification
     * @param {string} message - Notification message
     * @param {string} type - Notification type (info, success, error, warning)
     */
    function showNotification(message, type) {
        type = type || 'info';

        var notification = $('<div>', {
            'class': 'ahgmh-notification ahgmh-notification-' + type,
            'role': 'alert',
            'aria-live': 'assertive',
            text: message
        });
        $('body').append(notification);

        setTimeout(function () {
            notification.addClass('show');
        }, 100);

        setTimeout(function () {
            notification.removeClass('show');
            setTimeout(function () {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Hide notification
     */
    function hideNotification() {
        $('.ahgmh-notification').removeClass('show');
        setTimeout(function () {
            $('.ahgmh-notification').remove();
        }, 300);
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[title]').each(function () {
            $(this).attr('data-title', $(this).attr('title'));
            $(this).removeAttr('title');
        });

        $('[data-title]').hover(
            function () {
                var title = $(this).attr('data-title');
                var tooltip = $('<div>', {
                    'class': 'ahgmh-tooltip',
                    'role': 'tooltip',
                    text: title
                });
                $('body').append(tooltip);

                var pos = $(this).offset();
                tooltip.css({
                    top: pos.top - tooltip.outerHeight() - 10,
                    left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
                });
            },
            function () {
                $('.ahgmh-tooltip').remove();
            }
        );
    }

    /**
     * Initialize tab switching with keyboard support
     */
    function initTabSwitching() {
        $('.ahgmh-tab').on('click', function (e) {
            // Let natural link navigation handle tab switching
            // This is just for visual feedback
            $('.ahgmh-tab').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');
        });

        // Keyboard navigation for tabs (Arrow keys)
        $('.ahgmh-tab').on('keydown', function (e) {
            var $tabs = $('.ahgmh-tab');
            var currentIndex = $tabs.index(this);
            var $targetTab = null;

            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                $targetTab = $tabs.eq((currentIndex + 1) % $tabs.length);
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                $targetTab = $tabs.eq((currentIndex - 1 + $tabs.length) % $tabs.length);
            } else if (e.key === 'Home') {
                e.preventDefault();
                $targetTab = $tabs.first();
            } else if (e.key === 'End') {
                e.preventDefault();
                $targetTab = $tabs.last();
            }

            if ($targetTab) {
                $targetTab.focus();
            }
        });
    }

    // Export functions to global scope for use by other modules
    window.AHGMH = window.AHGMH || {};
    window.AHGMH.showNotification = showNotification;
    window.AHGMH.hideNotification = hideNotification;
    window.AHGMH.initTooltips = initTooltips;
    window.AHGMH.initTabSwitching = initTabSwitching;

    // Auto-initialize core functionality on all admin pages
    $(document).ready(function () {
        initTooltips();
        initTabSwitching();
    });

})(jQuery);
