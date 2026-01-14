<?php
/**
 * PDF Trend Analysis Report Template
 * Professional template for trend analysis reports
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
$yoy = isset($data['year_over_year']) ? $data['year_over_year'] : array();
$monthly = isset($data['monthly_trends']) ? $data['monthly_trends'] : array();
$seasonal = isset($data['seasonal_patterns']) ? $data['seasonal_patterns'] : array();

// Prepare header variables
$report_title = isset($report_data['title']) ? $report_data['title'] : 'Trendanalyse';
$association_name = 'Hegegemeinschaft Mittelholstein';
$period_start = isset($metadata['current_period']['start']) ? $metadata['current_period']['start'] : '';
$period_end = isset($metadata['current_period']['end']) ? $metadata['current_period']['end'] : '';
$generated_at = isset($metadata['generated_at']) ? $metadata['generated_at'] : current_time('mysql');
$generated_by = isset($metadata['generated_by']) ? $metadata['generated_by'] : '';
$filters = isset($metadata['filters']) ? $metadata['filters'] : array();

// Load CSS
$css = '';
if (!empty($css_file) && file_exists($css_file)) {
    $css = file_get_contents($css_file);
}

// Helper function to format trend direction
function format_trend($trend) {
    $icons = array(
        'increasing' => '↑',
        'decreasing' => '↓',
        'stable' => '→',
    );
    $labels = array(
        'increasing' => 'Steigend',
        'decreasing' => 'Fallend',
        'stable' => 'Stabil',
    );
    $icon = isset($icons[$trend]) ? $icons[$trend] : '';
    $label = isset($labels[$trend]) ? $labels[$trend] : $trend;
    return $icon . ' ' . $label;
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

    <!-- Year-over-Year Comparison -->
    <?php if (!empty($yoy)): ?>
    <div class="report-section">
        <h3 class="section-title">Jahresvergleich</h3>

        <?php if (isset($yoy['changes'])): ?>
        <?php $changes = $yoy['changes']; ?>
        <table class="summary-stats-table">
            <tr>
                <td class="stat-cell">
                    <div class="stat-box stat-box-primary">
                        <div class="stat-label">Abschussänderung</div>
                        <div class="stat-value">
                            <?php
                            $change = intval($changes['harvest_change']);
                            $sign = $change >= 0 ? '+' : '';
                            echo $sign . number_format($change, 0, ',', '.');
                            ?>
                        </div>
                        <div class="stat-sublabel">
                            <?php
                            $percent = floatval($changes['harvest_change_percent']);
                            $sign = $percent >= 0 ? '+' : '';
                            echo $sign . number_format($percent, 1, ',', '.') . '%';
                            ?>
                        </div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-box stat-box-success">
                        <div class="stat-label">Meldungsänderung</div>
                        <div class="stat-value">
                            <?php
                            $change = intval($changes['submission_change']);
                            $sign = $change >= 0 ? '+' : '';
                            echo $sign . number_format($change, 0, ',', '.');
                            ?>
                        </div>
                        <div class="stat-sublabel">
                            <?php
                            $percent = floatval($changes['submission_change_percent']);
                            $sign = $percent >= 0 ? '+' : '';
                            echo $sign . number_format($percent, 1, ',', '.') . '%';
                            ?>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <?php if (isset($yoy['current_period']) && isset($yoy['previous_period'])): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Zeitraum</th>
                    <th style="text-align: right;">Meldungen</th>
                    <th style="text-align: right;">Abschüsse</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Vorjahr (<?php echo date('Y', strtotime($metadata['previous_period']['start'])); ?>)</td>
                    <td style="text-align: right;"><?php echo number_format(absint($yoy['previous_period']['submission_count']), 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format(absint($yoy['previous_period']['harvest_count']), 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>Aktuell (<?php echo date('Y', strtotime($metadata['current_period']['start'])); ?>)</td>
                    <td style="text-align: right;"><?php echo number_format(absint($yoy['current_period']['submission_count']), 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format(absint($yoy['current_period']['harvest_count']), 0, ',', '.'); ?></td>
                </tr>
                <tr class="total-row">
                    <td><strong>Änderung</strong></td>
                    <td style="text-align: right;">
                        <strong>
                        <?php
                        $change = intval($changes['submission_change']);
                        $sign = $change >= 0 ? '+' : '';
                        echo $sign . number_format($change, 0, ',', '.');
                        ?>
                        </strong>
                    </td>
                    <td style="text-align: right;">
                        <strong>
                        <?php
                        $change = intval($changes['harvest_change']);
                        $sign = $change >= 0 ? '+' : '';
                        echo $sign . number_format($change, 0, ',', '.');
                        ?>
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Monthly Trends -->
    <?php if (!empty($monthly['monthly_trends'])): ?>
    <div class="report-section">
        <h3 class="section-title">Monatliche Trends</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Monat</th>
                    <th style="width: 20%; text-align: right;">Meldungen</th>
                    <th style="width: 20%; text-align: right;">Abschüsse</th>
                    <th style="width: 20%; text-align: right;">Änderung</th>
                    <th style="width: 15%; text-align: center;">Trend</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly['monthly_trends'] as $trend): ?>
                <tr>
                    <td><?php echo esc_html($trend['month']); ?></td>
                    <td style="text-align: right;"><?php echo number_format(absint($trend['submission_count']), 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format(absint($trend['harvest_count']), 0, ',', '.'); ?></td>
                    <td style="text-align: right;">
                        <?php
                        if (isset($trend['change_percent'])) {
                            $change = floatval($trend['change_percent']);
                            $sign = $change >= 0 ? '+' : '';
                            echo $sign . number_format($change, 1, ',', '.') . '%';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td style="text-align: center;"><?php echo format_trend($trend['trend']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Seasonal Patterns -->
    <?php if (!empty($seasonal['peak_months']) || !empty($seasonal['peak_weekdays'])): ?>
    <div class="report-section">
        <h3 class="section-title">Saisonale Muster</h3>

        <?php if (!empty($seasonal['peak_months'])): ?>
        <h4 style="font-size: 10pt; margin-top: 10px;">Aktivste Monate</h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Monat</th>
                    <th style="width: 30%; text-align: right;">Meldungen</th>
                    <th style="width: 20%; text-align: right;">Anteil</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($seasonal['peak_months'] as $month): ?>
                <tr>
                    <td><?php echo esc_html($month['month']); ?></td>
                    <td style="text-align: right;"><?php echo number_format(absint($month['count']), 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format(floatval($month['percentage']), 1, ',', '.'); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($seasonal['peak_weekdays'])): ?>
        <h4 style="font-size: 10pt; margin-top: 15px;">Aktivste Wochentage</h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Wochentag</th>
                    <th style="width: 30%; text-align: right;">Meldungen</th>
                    <th style="width: 20%; text-align: right;">Anteil</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($seasonal['peak_weekdays'] as $weekday): ?>
                <tr>
                    <td><?php echo esc_html($weekday['weekday']); ?></td>
                    <td style="text-align: right;"><?php echo number_format(absint($weekday['count']), 0, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format(floatval($weekday['percentage']), 1, ',', '.'); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Analysis Note -->
    <div class="report-section legend-section">
        <h4 class="legend-title">Hinweise zur Interpretation</h4>
        <p style="font-size: 9pt; line-height: 1.5; margin: 5px 0;">
            <strong>Trends:</strong> Steigend (↑) bedeutet eine Zunahme gegenüber dem Vormonat,
            Fallend (↓) eine Abnahme, und Stabil (→) bedeutet weniger als 5% Veränderung.
        </p>
        <p style="font-size: 9pt; line-height: 1.5; margin: 5px 0;">
            <strong>Saisonale Muster:</strong> Zeigt die aktivsten Zeiten basierend auf
            einem Schwellenwert von 80% der durchschnittlichen Aktivität.
        </p>
    </div>

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
