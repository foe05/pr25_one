<?php
/**
 * PDF Report Header Template
 * Reusable header for all PDF reports with professional formatting
 *
 * Variables:
 * @var string $report_title Report title
 * @var string $association_name Association name
 * @var string $period_start Start date (Y-m-d)
 * @var string $period_end End date (Y-m-d)
 * @var string $generated_at Generation timestamp
 * @var string $generated_by User name
 * @var array $filters Applied filters (optional)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Format dates in German format (dd.mm.yyyy)
$period_start_formatted = !empty($period_start) ? date('d.m.Y', strtotime($period_start)) : '';
$period_end_formatted = !empty($period_end) ? date('d.m.Y', strtotime($period_end)) : '';
$generated_at_formatted = !empty($generated_at) ? date('d.m.Y H:i', strtotime($generated_at)) : date('d.m.Y H:i');

// Default association name if not provided
$association_name = !empty($association_name) ? $association_name : 'Hegegemeinschaft Mittelholstein';
?>

<div class="pdf-header">
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <!-- Logo placeholder - can be replaced with actual logo path -->
                <div class="logo-placeholder">
                    <div class="logo-text"><?php echo esc_html(substr($association_name, 0, 3)); ?></div>
                </div>
            </td>
            <td class="header-info">
                <h1 class="association-name"><?php echo esc_html($association_name); ?></h1>
                <p class="header-subtitle">Jagdbezirk Mittelholstein</p>
            </td>
        </tr>
    </table>

    <div class="report-title-section">
        <h2 class="report-title"><?php echo esc_html($report_title); ?></h2>
    </div>

    <div class="report-metadata">
        <table class="metadata-table">
            <tr>
                <td class="meta-label">Berichtszeitraum:</td>
                <td class="meta-value">
                    <?php if (!empty($period_start) && !empty($period_end)): ?>
                        <?php echo esc_html($period_start_formatted); ?> bis <?php echo esc_html($period_end_formatted); ?>
                    <?php else: ?>
                        Aktueller Zeitraum
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="meta-label">Erstellt am:</td>
                <td class="meta-value"><?php echo esc_html($generated_at_formatted); ?> Uhr</td>
            </tr>
            <?php if (!empty($generated_by)): ?>
            <tr>
                <td class="meta-label">Erstellt von:</td>
                <td class="meta-value"><?php echo esc_html($generated_by); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($filters['species'])): ?>
            <tr>
                <td class="meta-label">Wildart:</td>
                <td class="meta-value"><?php echo esc_html($filters['species']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($filters['meldegruppe'])): ?>
            <tr>
                <td class="meta-label">Meldegruppe:</td>
                <td class="meta-value"><?php echo esc_html($filters['meldegruppe']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="header-divider"></div>
</div>
