<?php
/**
 * Table template for displaying Abschussmeldungen
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current page from URL or use the one from shortcode attributes
$current_page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : $page;

// Get limit from URL or use the one from shortcode attributes
$current_limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : $limit;
?>

<div class="abschuss-table-container" id="abschuss-table-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?php echo esc_html__('Abschussmeldungen', 'custom-form-display'); ?></h2>
        <button type="button" class="btn btn-secondary btn-sm" id="refresh-table-btn">
            <i class="fas fa-sync-alt"></i> <?php echo esc_html__('Aktualisieren', 'abschussplan-hgmh'); ?>
        </button>
    </div>
    <div class="table-content" id="table-content">
        <?php include AHGMH_PLUGIN_DIR . 'templates/table-content.php'; ?>
    </div> <!-- End table-content -->
</div>

<script>
jQuery(document).ready(function($) {
    // Store table configuration
    const tableConfig = {
        species: '<?php echo esc_js($species ?? ''); ?>',
        limit: <?php echo intval($current_limit); ?>,
        page: <?php echo intval($current_page); ?>
    };
    
    // Function to refresh table
    function refreshTable(showLoading = true) {
        const $container = $('#table-content');
        const $refreshBtn = $('#refresh-table-btn');
        
        if (showLoading) {
            $refreshBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php echo esc_js(__('Lädt...', 'abschussplan-hgmh')); ?>');
            $container.css('opacity', '0.6');
        }
        
        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'refresh_abschuss_table',
                species: tableConfig.species,
                limit: tableConfig.limit,
                page: tableConfig.page
            },
            success: function(response) {
                if (response.success) {
                    $container.html(response.data.table_html);
                    
                    // Show brief success indicator for auto-refreshes
                    if (!showLoading) {
                        $container.css('position', 'relative');
                        $container.prepend('<div class="alert alert-success auto-refresh-indicator" style="position: absolute; top: 10px; right: 10px; z-index: 1000; padding: 5px 10px; font-size: 12px;">Aktualisiert</div>');
                        setTimeout(() => {
                            $('.auto-refresh-indicator').fadeOut(() => {
                                $('.auto-refresh-indicator').remove();
                            });
                        }, 2000);
                    }
                }
            },
            error: function() {
                console.error('Failed to refresh table');
            },
            complete: function() {
                if (showLoading) {
                    $refreshBtn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> <?php echo esc_js(__('Aktualisieren', 'abschussplan-hgmh')); ?>');
                    $container.css('opacity', '1');
                }
            }
        });
    }
    
    // Manual refresh button
    $('#refresh-table-btn').on('click', function() {
        refreshTable(true);
    });
    
    // Make refreshTable function globally available for form submissions
    window.abschussRefreshTable = refreshTable;
});
</script>