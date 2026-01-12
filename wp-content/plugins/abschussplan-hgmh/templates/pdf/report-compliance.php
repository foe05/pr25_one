<?php
/**
 * PDF Compliance Report Template
 * Professional template for compliance status reports
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
$compliance = isset($data['compliance']) ? $data['compliance'] : array();
$summary = isset($data['summary']) ? $data['summary'] : array();

// Prepare header variables
$report_title = isset($report_data['title']) ? $report_data['title'] : 'Compliance-Bericht';
$association_name = 'Hegegemeinschaft Mittelholstein';
$period_start = '';
$period_end = '';
$generated_at = isset($metadata['generated_at']) ? $metadata['generated_at'] : current_time('mysql');
$generated_by = isset($metadata['generated_by']) ? $metadata['generated_by'] : '';
$filters = isset($metadata['filters']) ? $metadata['filters'] : array();

// Load CSS
$css = '';
if (!empty($css_file) && file_exists($css_file)) {
    $css = file_get_contents($css_file);
}

// Helper function to get status badge class
function get_status_class($status) {
    $status_map = array(
        'good' => 'status-good',
        'warning' => 'status-warning',
        'critical' => 'status-critical',
        'exceeded' => 'status-exceeded',
    );
    return isset($status_map[$status]) ? $status_map[$status] : 'status-neutral';
}

// Helper function to get status text in German
function get_status_text($status) {
    $status_text = array(
        'good' => 'Gut',
        'warning' => 'Warnung',
        'critical' => 'Kritisch',
        'exceeded' => 'Überschritten',
    );
    return isset($status_text[$status]) ? $status_text[$status] : $status;
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

    <!-- Overall Summary -->
    <?php if (!empty($summary)): ?>
    <div class="report-section">
        <h3 class="section-title">Gesamtübersicht</h3>

        <table class="summary-stats-table">
            <tr>
                <?php foreach ($summary as $species_name => $species_summary): ?>
                <td class="stat-cell">
                    <div class="stat-box <?php echo get_status_class($species_summary['overall_status']); ?>">
                        <div class="stat-label"><?php echo esc_html($species_name); ?></div>
                        <div class="stat-value">
                            <?php echo number_format($species_summary['overall_percentage'], 1, ',', '.'); ?>%
                        </div>
                        <div class="stat-sublabel">
                            <?php echo absint($species_summary['total_current']); ?> / <?php echo absint($species_summary['total_limit']); ?>
                        </div>
                    </div>
                </td>
                <?php endforeach; ?>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Detailed Compliance by Species -->
    <?php if (!empty($compliance)): ?>
        <?php foreach ($compliance as $species_data): ?>
        <div class="report-section">
            <h3 class="section-title"><?php echo esc_html($species_data['species']); ?></h3>

            <table class="data-table compliance-table">
                <thead>
                    <tr>
                        <th class="col-category">Kategorie</th>
                        <th class="col-number">Aktuell</th>
                        <th class="col-number">Limit</th>
                        <th class="col-number">Verbleibend</th>
                        <th class="col-percent">Prozent</th>
                        <th class="col-status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($species_data['categories'] as $category => $cat_data): ?>
                    <tr class="<?php echo get_status_class($cat_data['status']); ?>">
                        <td class="col-category"><?php echo esc_html($category); ?></td>
                        <td class="col-number"><?php echo number_format(absint($cat_data['current']), 0, ',', '.'); ?></td>
                        <td class="col-number"><?php echo number_format(absint($cat_data['limit']), 0, ',', '.'); ?></td>
                        <td class="col-number">
                            <?php
                            $remaining = absint($cat_data['limit']) - absint($cat_data['current']);
                            echo $remaining >= 0 ? number_format($remaining, 0, ',', '.') : '0';
                            ?>
                        </td>
                        <td class="col-percent"><?php echo number_format(floatval($cat_data['percentage']), 1, ',', '.'); ?>%</td>
                        <td class="col-status">
                            <span class="status-badge <?php echo get_status_class($cat_data['status']); ?>">
                                <?php echo esc_html(get_status_text($cat_data['status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Total Row -->
                    <?php if (isset($species_data['total'])): ?>
                    <?php $total = $species_data['total']; ?>
                    <tr class="total-row <?php echo get_status_class($total['status']); ?>">
                        <td class="col-category"><strong>Gesamt</strong></td>
                        <td class="col-number"><strong><?php echo number_format(absint($total['current']), 0, ',', '.'); ?></strong></td>
                        <td class="col-number"><strong><?php echo number_format(absint($total['limit']), 0, ',', '.'); ?></strong></td>
                        <td class="col-number">
                            <strong>
                            <?php
                            $remaining = absint($total['limit']) - absint($total['current']);
                            echo $remaining >= 0 ? number_format($remaining, 0, ',', '.') : '0';
                            ?>
                            </strong>
                        </td>
                        <td class="col-percent"><strong><?php echo number_format(floatval($total['percentage']), 1, ',', '.'); ?>%</strong></td>
                        <td class="col-status">
                            <span class="status-badge <?php echo get_status_class($total['status']); ?>">
                                <strong><?php echo esc_html(get_status_text($total['status'])); ?></strong>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Meldegruppe Breakdown -->
    <?php if (!empty($data['meldegruppe_breakdown'])): ?>
    <div class="report-section">
        <h3 class="section-title">Aufschlüsselung nach Meldegruppe</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-meldegruppe">Meldegruppe</th>
                    <th class="col-number">Aktuell</th>
                    <th class="col-number">Limit</th>
                    <th class="col-percent">Prozent</th>
                    <th class="col-status">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['meldegruppe_breakdown'] as $mg_data): ?>
                <tr class="<?php echo get_status_class($mg_data['status']); ?>">
                    <td class="col-meldegruppe"><?php echo esc_html($mg_data['meldegruppe']); ?></td>
                    <td class="col-number"><?php echo number_format(absint($mg_data['current']), 0, ',', '.'); ?></td>
                    <td class="col-number"><?php echo number_format(absint($mg_data['limit']), 0, ',', '.'); ?></td>
                    <td class="col-percent"><?php echo number_format(floatval($mg_data['percentage']), 1, ',', '.'); ?>%</td>
                    <td class="col-status">
                        <span class="status-badge <?php echo get_status_class($mg_data['status']); ?>">
                            <?php echo esc_html(get_status_text($mg_data['status'])); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Status Legend -->
    <div class="report-section legend-section">
        <h4 class="legend-title">Legende</h4>
        <table class="legend-table">
            <tr>
                <td><span class="status-badge status-good">Gut</span></td>
                <td>0-70% der Limit erreicht</td>
            </tr>
            <tr>
                <td><span class="status-badge status-warning">Warnung</span></td>
                <td>71-90% der Limit erreicht</td>
            </tr>
            <tr>
                <td><span class="status-badge status-critical">Kritisch</span></td>
                <td>91-100% der Limit erreicht</td>
            </tr>
            <tr>
                <td><span class="status-badge status-exceeded">Überschritten</span></td>
                <td>Über 100% der Limit erreicht</td>
            </tr>
        </table>
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
