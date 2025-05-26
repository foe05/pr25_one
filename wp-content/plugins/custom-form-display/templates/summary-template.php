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
                            <th><?php echo esc_html__('Kategorie', 'custom-form-display'); ?></th>
                            <th><?php echo esc_html__('Abschüsse', 'custom-form-display'); ?></th>
                            <th><?php echo esc_html__('Höchstgrenze', 'custom-form-display'); ?></th>
                            <th><?php echo esc_html__('Verbleibend', 'custom-form-display'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) : ?>
                            <tr>
                                <td><?php echo esc_html($category); ?></td>
                                <td><?php echo isset($counts[$category]) ? esc_html($counts[$category]) : '0'; ?></td>
                                <td><?php echo isset($limits[$category]) ? esc_html($limits[$category]) : '0'; ?></td>
                                <td>
                                    <?php if (isset($limits[$category]) && $limits[$category] > 0) : ?>
                                        <?php 
                                        $remaining = $limits[$category] - (isset($counts[$category]) ? $counts[$category] : 0);
                                        echo esc_html($remaining);
                                        ?>
                                    <?php else : ?>
                                        <span class="text-muted"><?php echo esc_html__('Keine Grenze', 'custom-form-display'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>