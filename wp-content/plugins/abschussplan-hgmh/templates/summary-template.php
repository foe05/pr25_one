<?php
/**
 * Summary template - displays the current hunting counts and limits
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="abschuss-summary-container">
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0"><?php echo esc_html__('Aktuelle Abschusszahlen', 'abschussplan-hgmh'); ?></h3>
            <?php if (!empty($available_meldegruppen)) : ?>
            <div class="mt-2">
                <label for="meldegruppe-filter" class="form-label"><?php echo esc_html__('Filter nach Meldegruppe:', 'abschussplan-hgmh'); ?></label>
                <select id="meldegruppe-filter" class="form-select" style="max-width: 200px;">
                    <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                    <?php foreach ($available_meldegruppen as $meldegruppe) : ?>
                        <option value="<?php echo esc_attr($meldegruppe); ?>" <?php selected($selected_meldegruppe, $meldegruppe); ?>>
                            <?php echo esc_html($meldegruppe); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Abschuss (Ist)', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Abschuss (Soll)', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Frei', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) : ?>
                            <?php 
                            $current = isset($counts[$category]) ? $counts[$category] : 0;
                            $limit = isset($limits[$category]) ? $limits[$category] : 0;
                            $remaining = max(0, $limit - $current);
                            
                            // Calculate percentage and status
                            $percentage = $limit > 0 ? ($current / $limit) * 100 : 0;
                            $exceeding_allowed = isset($allow_exceeding[$category]) ? $allow_exceeding[$category] : false;
                            $status_class = '';
                            $status_text = '';
                            
                            if ($limit == 0) {
                                // No limit set: "Unbegrenzt", grey background
                                $status_class = 'bg-secondary text-white';
                                $status_text = __('Unbegrenzt', 'abschussplan-hgmh');
                            } elseif ($percentage < 100) {
                                // Under limit: percentage on green background
                                $status_class = 'bg-success text-white';
                                $status_text = sprintf(__('%.1f%%', 'abschussplan-hgmh'), $percentage);
                            } elseif ($exceeding_allowed) {
                                // Over limit + overshoot allowed: percentage on green background
                                $status_class = 'bg-success text-white';
                                $status_text = sprintf(__('%.1f%%', 'abschussplan-hgmh'), $percentage);
                            } else {
                                // Over limit + overshoot not allowed: percentage on red background
                                $status_class = 'bg-danger text-white';
                                $status_text = sprintf(__('%.1f%%', 'abschussplan-hgmh'), $percentage);
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html($category); ?></td>
                                <td><strong><?php echo esc_html($current); ?></strong></td>
                                <td><?php echo esc_html($limit); ?></td>
                                <td>
                                    <?php if ($limit > 0) : ?>
                                        <?php echo esc_html($remaining); ?>
                                    <?php else : ?>
                                        <span class="text-muted"><?php echo esc_html__('Unbegrenzt', 'abschussplan-hgmh'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo esc_attr($status_class); ?>" style="padding: 6px 12px;">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($available_meldegruppen)) : ?>
<script>
jQuery(document).ready(function($) {
    // Handle meldegruppe filter change
    $('#meldegruppe-filter').on('change', function() {
        var selectedMeldegruppe = $(this).val();
        var currentUrl = new URL(window.location.href);
        
        // Update or remove meldegruppe parameter from current shortcode
        var pageContent = $('body').html();
        var shortcodeRegex = /\[abschuss_summary([^\]]*)\]/g;
        var currentShortcode = pageContent.match(shortcodeRegex);
        
        if (currentShortcode) {
            var newShortcode = currentShortcode[0];
            
            // Remove existing meldegruppe parameter
            newShortcode = newShortcode.replace(/\s*meldegruppe="[^"]*"/g, '');
            
            // Add new meldegruppe parameter if selected
            if (selectedMeldegruppe) {
                newShortcode = newShortcode.replace(']', ' meldegruppe="' + selectedMeldegruppe + '"]');
            }
            
            // Reload page with new parameter in URL for deep linking
            if (selectedMeldegruppe) {
                currentUrl.searchParams.set('meldegruppe', selectedMeldegruppe);
            } else {
                currentUrl.searchParams.delete('meldegruppe');
            }
            
            window.location.href = currentUrl.toString();
        }
    });
    
    // Set filter from URL parameter on page load
    var urlParams = new URLSearchParams(window.location.search);
    var urlMeldegruppe = urlParams.get('meldegruppe');
    if (urlMeldegruppe) {
        $('#meldegruppe-filter').val(urlMeldegruppe);
    }
});
</script>
<?php endif; ?>
