/**
 * Core Admin Module
 * Provides essential functionality for notifications, tooltips, and tab switching
 */
(function ($) {
    'use strict';

    /**
     * Show notification
     */
    function showNotification(message, type) {
        type = type || 'info';

        var notification = $('<div class="ahgmh-notification ahgmh-notification-' + type + '">' + message + '</div>');
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
                const title = $(this).attr('data-title');
                const tooltip = $('<div class="ahgmh-tooltip">' + title + '</div>');
                $('body').append(tooltip);

                const pos = $(this).offset();
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
     * Initialize tab switching
     */
    function initTabSwitching() {
        $('.ahgmh-tab').on('click', function (e) {
            // Let natural link navigation handle tab switching
            // This is just for visual feedback
            $('.ahgmh-tab').removeClass('active');
            $(this).addClass('active');
        });
    }

    // Export functions to global scope for use by other modules
    window.AHGMH = window.AHGMH || {};
    window.AHGMH.showNotification = showNotification;
    window.AHGMH.hideNotification = hideNotification;
    window.AHGMH.initTooltips = initTooltips;
    window.AHGMH.initTabSwitching = initTabSwitching;

})(jQuery);
