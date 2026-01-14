<?php
/**
 * Test Email Template
 * Simple template for testing email configuration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$site_name = isset($site_name) ? $site_name : get_option('blogname');
$timestamp = isset($timestamp) ? $timestamp : current_time('mysql');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('Test Email', 'abschussplan-hgmh'); ?></title>
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
        .success-box {
            background-color: #d1e7dd;
            border-left: 4px solid #0f5132;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #0f5132;
        }
        .info-item {
            margin: 10px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>✉️ <?php echo __('Test Email', 'abschussplan-hgmh'); ?></h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="success-box">
                <p style="margin: 0; font-weight: 600; font-size: 16px;">
                    ✓ <?php echo __('E-Mail-Konfiguration erfolgreich!', 'abschussplan-hgmh'); ?>
                </p>
            </div>

            <p><?php echo __('Dies ist eine Test-E-Mail vom Abschussplan HGMH Plugin.', 'abschussplan-hgmh'); ?></p>

            <p><?php echo __('Wenn Sie diese E-Mail erhalten, bedeutet das, dass Ihre E-Mail-Konfiguration korrekt funktioniert und bereit ist, Berichte zu versenden.', 'abschussplan-hgmh'); ?></p>

            <div class="info-item">
                <strong><?php echo __('Website:', 'abschussplan-hgmh'); ?></strong><br>
                <?php echo esc_html($site_name); ?>
            </div>

            <div class="info-item">
                <strong><?php echo __('Zeitstempel:', 'abschussplan-hgmh'); ?></strong><br>
                <?php echo esc_html(date_i18n('d.m.Y H:i:s', strtotime($timestamp))); ?>
            </div>

            <div class="info-item">
                <strong><?php echo __('Plugin-Version:', 'abschussplan-hgmh'); ?></strong><br>
                <?php echo defined('AHGMH_VERSION') ? esc_html(AHGMH_VERSION) : 'Unknown'; ?>
            </div>

            <p style="margin-top: 30px; color: #6c757d; font-size: 14px;">
                <?php echo __('Diese Test-E-Mail wurde manuell vom Administrator angefordert.', 'abschussplan-hgmh'); ?>
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p style="margin: 0 0 10px 0;">
                <?php echo esc_html($site_name); ?>
            </p>
            <p style="margin: 0; color: #adb5bd; font-size: 11px;">
                <?php echo __('Abschussplan HGMH - Hegering Management System', 'abschussplan-hgmh'); ?>
            </p>
        </div>
    </div>
</body>
</html>
