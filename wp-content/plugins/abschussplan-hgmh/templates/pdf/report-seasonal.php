<?php
/**
 * PDF Seasonal Report Template
 * Professional template for seasonal summary reports
 *
 * Variables:
 * @var array $report_data Report data structure
 * @var string $css_file Path to CSS file (optional)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Extract report data
$metadata = isset($report_data['metadata']) ? $report_data['metadata'] : array();
$data = isset($report_data['data']) ? $report_data['data'] : array();
$summary = isset($data['summary']) ? $data['summary'] : array();

// Prepare header variables
$report_title = isset($report_data['title']) ? $report_data['title'] : 'Saisonbericht';
$association_name = 'Hegegemeinschaft Mittelholstein';
$period_start = isset($metadata['period']['start']) ? $metadata['period']['start'] : '';
$period_end = isset($metadata['period']['end']) ? $metadata['period']['end'] : '';
$generated_at = isset($metadata['generated_at']) ? $metadata['generated_at'] : current_time('mysql');
$generated_by = isset($metadata['generated_by']) ? $metadata['generated_by'] : '';
$filters = isset($metadata['filters']) ? $metadata['filters'] : array();

// Load CSS
$css = '';
if (!empty($css_file) && file_exists($css_file)) {
    $css = file_get_contents($css_file);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo esc_html($report_title); ?></title>
    <?php if (!empty($css)): ?>
    <style><?php echo $css; ?></style>
    <?php endif; ?>
</head>
<body>

<?php
// Include header
$header_path = dirname(__FILE__) . '/report-header.php';
if (file_exists($header_path)) {
    include $header_path;
}
?>

<div class="report-content">
    <!-- Summary Statistics -->
    <div class="report-section">
        <h3 class="section-title">Zusammenfassung</h3>

        <table class="summary-stats-table">
            <tr>
                <td class="stat-cell">
                    <div class="stat-box stat-box-primary">
                        <div class="stat-label">Meldungen gesamt</div>
                        <div class="stat-value"><?php echo number_format(absint($summary['total_submissions']), 0, ',', '.'); ?></div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-box stat-box-success">
                        <div class="stat-label">Abschüsse gesamt</div>
                        <div class="stat-value"><?php echo number_format(absint($summary['total_harvests']), 0, ',', '.'); ?></div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-box stat-box-info">
                        <div class="stat-label">Wildarten</div>
                        <div class="stat-value"><?php echo absint(count($data['species_breakdown'])); ?></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Species Breakdown -->
    <?php if (!empty($data['species_breakdown'])): ?>
    <div class="report-section">
        <h3 class="section-title">Aufschlüsselung nach Wildart</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-name">Wildart</th>
                    <th class="col-number">Anzahl Abschüsse</th>
                    <th class="col-percent">Anteil</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = absint($summary['total_harvests']);
                foreach ($data['species_breakdown'] as $item):
                    $count = absint($item['count']);
                    $percentage = $total > 0 ? ($count / $total * 100) : 0;
                ?>
                <tr>
                    <td class="col-name"><?php echo esc_html($item['name']); ?></td>
                    <td class="col-number"><?php echo number_format($count, 0, ',', '.'); ?></td>
                    <td class="col-percent"><?php echo number_format($percentage, 1, ',', '.'); ?>%</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td class="col-name"><strong>Gesamt</strong></td>
                    <td class="col-number"><strong><?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                    <td class="col-percent"><strong>100,0%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Category Breakdown -->
    <?php if (!empty($data['category_breakdown'])): ?>
    <div class="report-section">
        <h3 class="section-title">Aufschlüsselung nach Kategorie</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-name">Kategorie</th>
                    <th class="col-number">Anzahl Abschüsse</th>
                    <th class="col-percent">Anteil</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = absint($summary['total_harvests']);
                foreach ($data['category_breakdown'] as $item):
                    $count = absint($item['count']);
                    $percentage = $total > 0 ? ($count / $total * 100) : 0;
                ?>
                <tr>
                    <td class="col-name"><?php echo esc_html($item['name']); ?></td>
                    <td class="col-number"><?php echo number_format($count, 0, ',', '.'); ?></td>
                    <td class="col-percent"><?php echo number_format($percentage, 1, ',', '.'); ?>%</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td class="col-name"><strong>Gesamt</strong></td>
                    <td class="col-number"><strong><?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                    <td class="col-percent"><strong>100,0%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Meldegruppe Breakdown -->
    <?php if (!empty($data['meldegruppe_breakdown'])): ?>
    <div class="report-section">
        <h3 class="section-title">Aufschlüsselung nach Meldegruppe</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-name">Meldegruppe</th>
                    <th class="col-number">Anzahl Abschüsse</th>
                    <th class="col-percent">Anteil</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = absint($summary['total_harvests']);
                foreach ($data['meldegruppe_breakdown'] as $item):
                    $count = absint($item['count']);
                    $percentage = $total > 0 ? ($count / $total * 100) : 0;
                ?>
                <tr>
                    <td class="col-name"><?php echo esc_html($item['name']); ?></td>
                    <td class="col-number"><?php echo number_format($count, 0, ',', '.'); ?></td>
                    <td class="col-percent"><?php echo number_format($percentage, 1, ',', '.'); ?>%</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td class="col-name"><strong>Gesamt</strong></td>
                    <td class="col-number"><strong><?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                    <td class="col-percent"><strong>100,0%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="pdf-footer">
    <table class="footer-table">
        <tr>
            <td class="footer-left">
                <span class="footer-text">Hegegemeinschaft Mittelholstein</span>
            </td>
            <td class="footer-center">
                <span class="footer-text">Seite <span class="pagenum"></span></span>
            </td>
            <td class="footer-right">
                <span class="footer-text">Erstellt am <?php echo date('d.m.Y', strtotime($generated_at)); ?></span>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
