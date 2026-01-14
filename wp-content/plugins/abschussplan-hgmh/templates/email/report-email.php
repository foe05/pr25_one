<?php
/**
 * Report Email Template
 * Email template for sending reports
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Set default values
$report_title = isset($report_title) ? $report_title : __('Bericht', 'abschussplan-hgmh');
$report_period = isset($report_period) ? $report_period : '';
$report_type = isset($report_type) ? $report_type : '';
$site_name = get_option('blogname');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($report_title); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color: #2c5f2d;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px 20px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #2c5f2d;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 5px 0;
        }
        .text-muted {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1><?php echo esc_html($report_title); ?></h1>
            <?php if (!empty($report_period)): ?>
                <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">
                    <?php echo esc_html($report_period); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p><?php echo __('Sehr geehrte Damen und Herren,', 'abschussplan-hgmh'); ?></p>

            <p><?php echo sprintf(
                __('anbei erhalten Sie den automatisch generierten %s für den Zeitraum %s.', 'abschussplan-hgmh'),
                '<strong>' . esc_html($report_type) . '</strong>',
                '<strong>' . esc_html($report_period) . '</strong>'
            ); ?></p>

            <?php if (isset($has_attachment) && $has_attachment): ?>
            <div class="info-box">
                <p><strong><?php echo __('📎 Anhang:', 'abschussplan-hgmh'); ?></strong></p>
                <p><?php echo __('Der vollständige Bericht ist als PDF-Datei im Anhang dieser E-Mail enthalten.', 'abschussplan-hgmh'); ?></p>
            </div>
            <?php endif; ?>

            <?php if (isset($report_summary) && !empty($report_summary)): ?>
            <div class="info-box">
                <p><strong><?php echo __('Zusammenfassung:', 'abschussplan-hgmh'); ?></strong></p>
                <?php echo wp_kses_post($report_summary); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($additional_info) && !empty($additional_info)): ?>
            <div style="margin-top: 20px;">
                <?php echo wp_kses_post($additional_info); ?>
            </div>
            <?php endif; ?>

            <p style="margin-top: 30px;" class="text-muted">
                <?php echo __('Bei Fragen oder Anmerkungen wenden Sie sich bitte an Ihren Administrator.', 'abschussplan-hgmh'); ?>
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p style="margin: 0 0 10px 0;">
                <?php echo esc_html($site_name); ?>
            </p>
            <p style="margin: 0; color: #adb5bd; font-size: 11px;">
                <?php echo __('Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.', 'abschussplan-hgmh'); ?>
            </p>
            <p style="margin: 10px 0 0 0; color: #adb5bd; font-size: 11px;">
                <?php echo sprintf(
                    __('Gesendet am %s', 'abschussplan-hgmh'),
                    date_i18n('d.m.Y H:i', current_time('timestamp'))
                ); ?>
            </p>
        </div>
    </div>
</body>
</html>
