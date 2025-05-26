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
            <h3 class="mb-0"><?php echo esc_html__('Aktuelle Abschusszahlen', 'custom-form-display'); ?></h3>
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
                            $status_class = '';
                            $status_text = '';
                            
                            if ($limit == 0) {
                                $status_class = 'bg-secondary text-white';
                                $status_text = __('Unbegrenzt', 'abschussplan-hgmh');
                            } elseif ($percentage >= 100) {
                                $status_class = 'bg-danger text-white';
                                $status_text = sprintf(__('%.1f%%', 'abschussplan-hgmh'), $percentage);
                            } elseif ($percentage >= 90) {
                                $status_class = 'bg-warning text-dark';
                                $status_text = sprintf(__('%.1f%%', 'abschussplan-hgmh'), $percentage);
                            } else {
                                $status_class = 'bg-success text-white';
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