/**
 * Dashboard Module
 * Handles dashboard-specific functionality including progress animations and auto-refresh
 */
(function ($) {
    'use strict';

    /**
     * Initialize all dashboard functionality
     */
    function init() {
        initProgressAnimations();
        initAutoRefresh();
    }

    /**
     * Initialize progress animations
     */
    function initProgressAnimations() {
        $('.progress-fill, .ahgmh-progress-bar').each(function () {
            const $bar = $(this);
            const targetWidth = $bar.attr('data-width') || $bar.data('width');

            if (targetWidth) {
                setTimeout(function () {
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
            setInterval(function () {
                $('.ahgmh-stat-number').each(function () {
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

    // Export public API
    window.AHGMH_Dashboard = {
        init: init,
        initProgressAnimations: initProgressAnimations,
        initAutoRefresh: initAutoRefresh,
        animateNumber: animateNumber
    };

    // Auto-initialize on document ready
    $(document).ready(function () {
        // Check if we're on a page that needs dashboard functionality
        if ($('.ahgmh-dashboard').length > 0 || $('.ahgmh-stat-card').length > 0 || $('.progress-fill').length > 0) {
            init();
        }
    });

})(jQuery);
