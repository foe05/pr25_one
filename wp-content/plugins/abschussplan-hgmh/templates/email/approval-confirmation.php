<?php
/**
 * Email template for approval confirmation notification
 *
 * This template is used when a submission has been approved by an Obmann.
 * Supports variable replacement for: {{name}}, {{wildart}}, {{date}}, {{link}}
 *
 * @package AbschussplanHGMH
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Determine if we're rendering HTML or plain text
// The email service will call this template and capture output
$is_html = isset($is_html) ? $is_html : true;

if ($is_html) {
    // HTML version
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('Abschussmeldung genehmigt', 'abschussplan-hgmh'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
            font-weight: normal;
        }
        .email-body {
            padding: 30px 20px;
        }
        .email-body p {
            margin: 0 0 15px 0;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #2c5f2d;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box strong {
            color: #2c5f2d;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2c5f2d;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .button:hover {
            background-color: #1f4420;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
        }
        .email-footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1><?php echo esc_html__('Abschussmeldung genehmigt', 'abschussplan-hgmh'); ?></h1>
        </div>

        <div class="email-body">
            <p><?php echo esc_html__('Hallo', 'abschussplan-hgmh'); ?> {{name}},</p>

            <p><?php echo esc_html__('Ihre Abschussmeldung wurde erfolgreich von einem Obmann geprüft und genehmigt.', 'abschussplan-hgmh'); ?></p>

            <div class="info-box">
                <p><strong><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?></strong> {{wildart}}</p>
                <p><strong><?php echo esc_html__('Abschussdatum:', 'abschussplan-hgmh'); ?></strong> {{date}}</p>
            </div>

            <p><?php echo esc_html__('Die Meldung wurde in den Abschussplan aufgenommen und ist nun offiziell registriert.', 'abschussplan-hgmh'); ?></p>

            <div class="button-container">
                <a href="{{link}}" class="button"><?php echo esc_html__('Meldung ansehen', 'abschussplan-hgmh'); ?></a>
            </div>

            <p><?php echo esc_html__('Vielen Dank für Ihre Meldung!', 'abschussplan-hgmh'); ?></p>
        </div>

        <div class="email-footer">
            <p><?php echo esc_html__('Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.', 'abschussplan-hgmh'); ?></p>
            <p><?php echo esc_html__('Hegering Großes Moor und Heide', 'abschussplan-hgmh'); ?></p>
        </div>
    </div>
</body>
</html>
<?php
} else {
    // Plain text version
    ?>
<?php echo esc_html__('Abschussmeldung genehmigt', 'abschussplan-hgmh'); ?>

================================================================================

<?php echo esc_html__('Hallo', 'abschussplan-hgmh'); ?> {{name}},

<?php echo esc_html__('Ihre Abschussmeldung wurde erfolgreich von einem Obmann geprüft und genehmigt.', 'abschussplan-hgmh'); ?>

<?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?> {{wildart}}
<?php echo esc_html__('Abschussdatum:', 'abschussplan-hgmh'); ?> {{date}}

<?php echo esc_html__('Die Meldung wurde in den Abschussplan aufgenommen und ist nun offiziell registriert.', 'abschussplan-hgmh'); ?>

<?php echo esc_html__('Meldung ansehen:', 'abschussplan-hgmh'); ?>

{{link}}

<?php echo esc_html__('Vielen Dank für Ihre Meldung!', 'abschussplan-hgmh'); ?>

================================================================================

<?php echo esc_html__('Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.', 'abschussplan-hgmh'); ?>

<?php echo esc_html__('Hegering Großes Moor und Heide', 'abschussplan-hgmh'); ?>

<?php
}
