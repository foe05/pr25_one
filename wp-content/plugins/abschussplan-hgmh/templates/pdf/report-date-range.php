<?php
/**
 * PDF Date Range Report Template
 * Professional template for custom date range reports
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
$statistics = isset($data['statistics']) ? $data['statistics'] : array();
$submissions = isset($data['submissions']) ? $data['submissions'] : array();

// Prepare header variables
$report_title = isset($report_data['title']) ? $report_data['title'] : 'Zeitraumbericht';
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
        <h3 class="section-title">Statistiken</h3>

        <table class="summary-stats-table">
            <tr>
                <td class="stat-cell">
                    <div class="stat-box stat-box-primary">
                        <div class="stat-label">Meldungen</div>
                        <div class="stat-value"><?php echo number_format(absint($statistics['submission_count']), 0, ',', '.'); ?></div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-box stat-box-success">
                        <div class="stat-label">Abschüsse gesamt</div>
                        <div class="stat-value"><?php echo number_format(absint($statistics['total_harvests']), 0, ',', '.'); ?></div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-box stat-box-info">
                        <div class="stat-label">Wildarten</div>
                        <div class="stat-value"><?php echo absint($statistics['species_count']); ?></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Submissions Detail Table -->
    <?php if (!empty($submissions)): ?>
    <div class="report-section">
        <h3 class="section-title">Meldungen im Detail</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Datum</th>
                    <th style="width: 20%;">Wildart</th>
                    <th style="width: 18%;">Kategorie</th>
                    <th style="width: 25%;">Meldegruppe</th>
                    <th style="width: 10%; text-align: right;">Anzahl</th>
                    <th style="width: 15%;">WUS-Nr.</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_harvests = 0;
                foreach ($submissions as $submission):
                    $anzahl = absint($submission['anzahl']);
                    $total_harvests += $anzahl;
                    $datum_formatted = date('d.m.Y', strtotime($submission['datum']));
                ?>
                <tr>
                    <td><?php echo esc_html($datum_formatted); ?></td>
                    <td><?php echo esc_html($submission['art']); ?></td>
                    <td><?php echo esc_html($submission['kategorie']); ?></td>
                    <td><?php echo esc_html($submission['meldegruppe']); ?></td>
                    <td style="text-align: right;"><?php echo number_format($anzahl, 0, ',', '.'); ?></td>
                    <td><?php echo esc_html($submission['wus_nummer']); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4"><strong>Gesamt</strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($total_harvests, 0, ',', '.'); ?></strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <p style="font-size: 8pt; color: #666; margin-top: 10px;">
            <em>Hinweis: Die Tabelle zeigt alle Meldungen im angegebenen Zeitraum.<?php
            if (!empty($filters['species'])) {
                echo ' Gefiltert nach Wildart: ' . esc_html($filters['species']) . '.';
            }
            if (!empty($filters['meldegruppe'])) {
                echo ' Gefiltert nach Meldegruppe: ' . esc_html($filters['meldegruppe']) . '.';
            }
            ?></em>
        </p>
    </div>
    <?php else: ?>
    <div class="report-section">
        <p style="text-align: center; color: #666; padding: 20px;">
            Keine Meldungen im angegebenen Zeitraum gefunden.
        </p>
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
