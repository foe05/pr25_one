<?php
/**
 * Feature Flags Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission check
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle AJAX save
add_action('wp_ajax_hgmh_save_feature_flags', function() {
    check_ajax_referer('hgmh_feature_flags_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
    }

    $flags = $_POST['flags'] ?? [];

    foreach (HGMH_Feature_Flags::get_all_flags() as $flag_name => $flag_meta) {
        if (isset($flags[$flag_name])) {
            HGMH_Feature_Flags::enable($flag_name);
        } else {
            HGMH_Feature_Flags::disable($flag_name);
        }
    }

    wp_send_json_success(['message' => 'Feature Flags gespeichert']);
});

$all_flags = HGMH_Feature_Flags::get_all_flags();
?>

<div class="wrap ahgmh-admin-modern">
    <h1 class="ahgmh-page-title">
        <span class="dashicons dashicons-flag"></span>
        <?php echo esc_html__('Feature Flags', 'abschussplan-hgmh'); ?>
    </h1>

    <div class="notice notice-warning">
        <p><strong>⚠️ <?php echo esc_html__('Achtung:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Änderungen an Feature Flags können die Plugin-Funktionalität beeinflussen!', 'abschussplan-hgmh'); ?></p>
    </div>

    <form id="feature-flags-form">
        <?php wp_nonce_field('hgmh_feature_flags_nonce', 'nonce'); ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Feature', 'abschussplan-hgmh'); ?></th>
                    <th><?php echo esc_html__('Beschreibung', 'abschussplan-hgmh'); ?></th>
                    <th><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_flags as $flag_name => $flag_meta): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($flag_meta['label']); ?></strong>
                        <?php if ($flag_meta['critical']): ?>
                            <span class="dashicons dashicons-warning" style="color: red;" title="<?php echo esc_attr__('Kritisches Flag', 'abschussplan-hgmh'); ?>"></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($flag_meta['description']); ?></td>
                    <td>
                        <label class="hgmh-switch">
                            <input type="checkbox"
                                   name="flags[<?php echo esc_attr($flag_name); ?>]"
                                   <?php checked(HGMH_Feature_Flags::is_enabled($flag_name)); ?>>
                            <span class="hgmh-slider"></span>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php echo esc_html__('Änderungen speichern', 'abschussplan-hgmh'); ?></button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#feature-flags-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=hgmh_save_feature_flags',
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            },
            error: function() {
                alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
            }
        });
    });
});
</script>

<style>
.hgmh-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}
.hgmh-switch input {
    display: none;
}
.hgmh-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}
.hgmh-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .hgmh-slider {
    background-color: #2196F3;
}
input:checked + .hgmh-slider:before {
    transform: translateX(26px);
}
</style>
