<?php
/**
 * Base Email Template
 * Reusable email template structure with header and footer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($email_title) ? esc_html($email_title) : 'Abschussplan HGMH'; ?></title>
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
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2c5f2d;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            margin: 15px 0;
        }
        .button:hover {
            background-color: #234d24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .text-muted {
            color: #6c757d;
            font-size: 14px;
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid #2c5f2d;
            background-color: #f8f9fa;
        }
        .alert-info {
            border-left-color: #0dcaf0;
            background-color: #cff4fc;
        }
        .alert-warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .alert-danger {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1><?php echo isset($header_title) ? esc_html($header_title) : 'Abschussplan HGMH'; ?></h1>
            <?php if (isset($header_subtitle)): ?>
                <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">
                    <?php echo esc_html($header_subtitle); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="email-body">
            <?php
            // Main content will be inserted here by child templates
            if (isset($email_content)) {
                echo wp_kses_post($email_content);
            }
            ?>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p style="margin: 0 0 10px 0;">
                <?php echo esc_html(get_option('blogname')); ?>
            </p>
            <p style="margin: 0; color: #adb5bd; font-size: 11px;">
                <?php echo sprintf(
                    __('Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.', 'abschussplan-hgmh')
                ); ?>
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
