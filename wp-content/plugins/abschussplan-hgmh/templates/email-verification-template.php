<?php
/**
 * Email Verification Template
 * Used to send verification email to public form submitters
 *
 * Available variables:
 * @var string $verification_url The URL to verify the email
 * @var string $species Game species (Wildart)
 * @var string $category Category (Abschuss type)
 * @var int $token_expiry_hours Token expiry time in hours
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Default token expiry if not provided
if (!isset($token_expiry_hours)) {
    $token_expiry_hours = 48;
}
?>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">

        <!-- Header -->
        <h2 style="color: #2c5f2d;"><?php echo esc_html__('Email-Verifizierung erforderlich', 'abschussplan-hgmh'); ?></h2>

        <!-- Thank you message -->
        <p><?php echo esc_html__('Vielen Dank für Ihre Abschuss-Meldung.', 'abschussplan-hgmh'); ?></p>

        <!-- Submission details -->
        <?php if (!empty($species) || !empty($category)) : ?>
            <p><strong><?php echo esc_html__('Ihre Meldung:', 'abschussplan-hgmh'); ?></strong></p>
            <ul>
                <?php if (!empty($species)) : ?>
                    <li><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?> <?php echo esc_html($species); ?></li>
                <?php endif; ?>
                <?php if (!empty($category)) : ?>
                    <li><?php echo esc_html__('Kategorie:', 'abschussplan-hgmh'); ?> <?php echo esc_html($category); ?></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <!-- Verification instruction -->
        <p><?php echo esc_html__('Bitte bestätigen Sie Ihre Email-Adresse durch Klicken auf den folgenden Link:', 'abschussplan-hgmh'); ?></p>

        <!-- Verification button -->
        <p style="text-align: center; margin: 30px 0;">
            <a href="<?php echo esc_url($verification_url); ?>"
               style="background-color: #2c5f2d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                <?php echo esc_html__('Email-Adresse bestätigen', 'abschussplan-hgmh'); ?>
            </a>
        </p>

        <!-- Expiry notice -->
        <p style="font-size: 12px; color: #666;">
            <?php
            echo esc_html(
                sprintf(
                    __('Dieser Link ist %d Stunden gültig.', 'abschussplan-hgmh'),
                    $token_expiry_hours
                )
            );
            ?>
        </p>

        <!-- Disclaimer -->
        <p style="font-size: 12px; color: #666;">
            <?php echo esc_html__('Falls Sie diese Meldung nicht erstellt haben, können Sie diese Email ignorieren.', 'abschussplan-hgmh'); ?>
        </p>

    </div>
</body>
</html>
