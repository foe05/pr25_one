<?php
/**
 * Modern Admin panel template with WordPress styling
 *
 * Uses WordPress-native postbox/metabox patterns for consistent UI.
 *
 * Expected variables:
 *   $db_config   - Database configuration array
 *   $categories  - Array of category names
 *   $counts      - Array of category => count
 *   $limits      - Array of category => limit
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Abschussplan-Verwaltung', 'abschussplan-hgmh'); ?></h1>

    <div id="ahgmh-response" class="notice" style="display: none;"></div>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div id="post-body-content">

                <!-- Database Configuration Section -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <span class="dashicons dashicons-database" style="margin-right: 5px;"></span>
                            <?php echo esc_html__('Datenbank-Konfiguration', 'abschussplan-hgmh'); ?>
                        </h2>
                        <div class="handle-actions hide-if-no-js">
                            <button type="button" class="handlediv" aria-expanded="true">
                                <span class="screen-reader-text"><?php echo esc_html__('Umschalten', 'abschussplan-hgmh'); ?></span>
                                <span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                    <div class="inside">
                        <form id="db-config-form">
                            <?php wp_nonce_field('db_config_nonce', 'db_nonce'); ?>

                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="db_type"><?php echo esc_html__('Datenbanktyp', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <select id="db_type" name="db_type" class="regular-text">
                                            <option value="sqlite" <?php selected($db_config['type'], 'sqlite'); ?>>
                                                <?php echo esc_html__('SQLite (Standard)', 'abschussplan-hgmh'); ?>
                                            </option>
                                            <option value="postgresql" <?php selected($db_config['type'], 'postgresql'); ?>>
                                                <?php echo esc_html__('PostgreSQL', 'abschussplan-hgmh'); ?>
                                            </option>
                                            <option value="mysql" <?php selected($db_config['type'], 'mysql'); ?>>
                                                <?php echo esc_html__('MySQL', 'abschussplan-hgmh'); ?>
                                            </option>
                                        </select>
                                        <p class="description">
                                            <?php echo esc_html__('Waehlen Sie den Datenbanktyp fuer die Speicherung der Abschussmeldungen.', 'abschussplan-hgmh'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr id="sqlite-config" <?php echo ($db_config['type'] !== 'sqlite') ? 'style="display: none;"' : ''; ?>>
                                    <th scope="row">
                                        <label for="sqlite_file"><?php echo esc_html__('SQLite Datei-Pfad', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="sqlite_file" name="sqlite_file" value="<?php echo esc_attr($db_config['sqlite_file'] ?? 'abschuss_db.sqlite'); ?>" class="regular-text" />
                                        <p class="description">
                                            <?php echo esc_html__('Relativer oder absoluter Pfad zur Datenbank-Datei', 'abschussplan-hgmh'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="export_filename"><?php echo esc_html__('Export-Dateiname', 'abschussplan-hgmh'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="export_filename" name="export_filename" value="<?php echo esc_attr(get_option('abschuss_export_filename', 'abschuss_export')); ?>" class="regular-text" />
                                        <p class="description">
                                            <?php echo esc_html__('Dateiname fuer CSV-Exporte (ohne .csv Endung)', 'abschussplan-hgmh'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr id="postgresql-config" <?php echo ($db_config['type'] !== 'postgresql') ? 'style="display: none;"' : ''; ?>>
                                    <th scope="row"><?php echo esc_html__('PostgreSQL-Einstellungen', 'abschussplan-hgmh'); ?></th>
                                    <td>
                                        <fieldset>
                                            <p>
                                                <label for="pg_host"><?php echo esc_html__('Host', 'abschussplan-hgmh'); ?></label><br>
                                                <input type="text" id="pg_host" name="pg_host" value="localhost" class="regular-text" />
                                            </p>
                                            <p>
                                                <label for="pg_port"><?php echo esc_html__('Port', 'abschussplan-hgmh'); ?></label><br>
                                                <input type="number" id="pg_port" name="pg_port" value="5432" class="small-text" />
                                            </p>
                                            <p>
                                                <label for="pg_dbname"><?php echo esc_html__('Datenbankname', 'abschussplan-hgmh'); ?></label><br>
                                                <input type="text" id="pg_dbname" name="pg_dbname" value="" class="regular-text" />
                                            </p>
                                            <p>
                                                <label for="pg_user"><?php echo esc_html__('Benutzername', 'abschussplan-hgmh'); ?></label><br>
                                                <input type="text" id="pg_user" name="pg_user" value="" class="regular-text" />
                                            </p>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php echo esc_html__('Konfiguration speichern', 'abschussplan-hgmh'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Category Limits Section -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <span class="dashicons dashicons-chart-bar" style="margin-right: 5px;"></span>
                            <?php echo esc_html__('Abschuss-Kontingente (Soll) verwalten', 'abschussplan-hgmh'); ?>
                        </h2>
                        <div class="handle-actions hide-if-no-js">
                            <button type="button" class="handlediv" aria-expanded="true">
                                <span class="screen-reader-text"><?php echo esc_html__('Umschalten', 'abschussplan-hgmh'); ?></span>
                                <span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                    <div class="inside">
                        <p class="description" style="margin-bottom: 15px;">
                            <?php echo esc_html__('Legen Sie die Soll-Werte (Kontingente) fuer jede Kategorie fest und vergleichen Sie sie mit den aktuellen Ist-Werten.', 'abschussplan-hgmh'); ?>
                        </p>
                        <form id="limits-form">
                            <?php wp_nonce_field('limits_nonce', 'limits_nonce_field'); ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col" class="manage-column" style="width: 35%;"><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                                        <th scope="col" class="manage-column" style="width: 15%; text-align: center;"><?php echo esc_html__('Ist-Wert', 'abschussplan-hgmh'); ?></th>
                                        <th scope="col" class="manage-column" style="width: 20%;"><?php echo esc_html__('Soll-Wert', 'abschussplan-hgmh'); ?></th>
                                        <th scope="col" class="manage-column" style="width: 30%; text-align: center;"><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category):
                                        $current_count = isset($counts[$category]) ? $counts[$category] : 0;
                                        $limit = isset($limits[$category]) ? $limits[$category] : 0;
                                        $percentage = $limit > 0 ? ($current_count / $limit) * 100 : 0;

                                        if ($percentage >= 100) {
                                            $status_bg = '#d63638'; $status_color = '#fff';
                                        } elseif ($percentage >= 80) {
                                            $status_bg = '#dba617'; $status_color = '#fff';
                                        } else {
                                            $status_bg = '#00a32a'; $status_color = '#fff';
                                        }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($category); ?></strong></td>
                                        <td style="text-align: center;">
                                            <span style="display: inline-block; background: #2271b1; color: #fff; padding: 3px 10px; border-radius: 3px; font-weight: 600; min-width: 30px; text-align: center;">
                                                <?php echo esc_html($current_count); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number"
                                                   name="limits[<?php echo esc_attr($category); ?>]"
                                                   value="<?php echo esc_attr($limit); ?>"
                                                   min="0"
                                                   max="999"
                                                   class="small-text" />
                                        </td>
                                        <td style="text-align: center;">
                                            <span style="display: inline-block; background: <?php echo esc_attr($status_bg); ?>; color: <?php echo esc_attr($status_color); ?>; padding: 3px 10px; border-radius: 3px; font-weight: 600; font-size: 12px;">
                                                <?php if ($limit > 0): ?>
                                                    <?php echo esc_html(round($percentage, 1)); ?>%
                                                <?php else: ?>
                                                    <?php echo esc_html__('Unbegrenzt', 'abschussplan-hgmh'); ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php echo esc_html__('Kontingente speichern', 'abschussplan-hgmh'); ?>
                                </button>
                                <button type="button" class="button" id="reset-limits">
                                    <?php echo esc_html__('Alle zuruecksetzen', 'abschussplan-hgmh'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>

            </div><!-- /post-body-content -->
        </div><!-- /post-body -->
    </div><!-- /poststuff -->
</div>

<script>
jQuery(document).ready(function($) {
    // Database type change handler
    $('#db_type').change(function() {
        var type = $(this).val();
        $('#sqlite-config, #postgresql-config').hide();
        $('#' + type + '-config').show();
    });

    // Database configuration form
    $('#db-config-form').on('submit', function(e) {
        e.preventDefault();

        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Speichern...', 'abschussplan-hgmh')); ?>');

        var formData = new FormData(this);
        formData.append('action', 'save_db_config');
        formData.append('nonce', $('#db_nonce').val());

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#ahgmh-response')
                        .removeClass('notice-error')
                        .addClass('notice notice-success')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                } else {
                    $('#ahgmh-response')
                        .removeClass('notice-success')
                        .addClass('notice notice-error')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                }
            },
            error: function() {
                $('#ahgmh-response')
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p><?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'abschussplan-hgmh')); ?></p>')
                    .show();
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                $('html, body').animate({ scrollTop: 0 }, 500);
            }
        });
    });

    // Limits form
    $('#limits-form').on('submit', function(e) {
        e.preventDefault();

        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Speichern...', 'abschussplan-hgmh')); ?>');

        var formData = new FormData(this);
        formData.append('action', 'save_limits');
        formData.append('nonce', $('#limits_nonce_field').val());

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#ahgmh-response')
                        .removeClass('notice-error')
                        .addClass('notice notice-success')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                } else {
                    $('#ahgmh-response')
                        .removeClass('notice-success')
                        .addClass('notice notice-error')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                }
            },
            error: function() {
                $('#ahgmh-response')
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p><?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'abschussplan-hgmh')); ?></p>')
                    .show();
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                $('html, body').animate({ scrollTop: 0 }, 500);
            }
        });
    });

    // Reset limits button
    $('#reset-limits').click(function() {
        if (confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie alle Kontingente auf 0 zuruecksetzen moechten?', 'abschussplan-hgmh')); ?>')) {
            $('#limits-form input[type="number"]').val('0');
        }
    });

    // Collapsible postboxes
    $('.postbox .handlediv').click(function() {
        var $postbox = $(this).closest('.postbox');
        var $inside = $postbox.find('.inside');
        var $button = $(this);

        if ($inside.is(':visible')) {
            $inside.hide();
            $postbox.addClass('closed');
            $button.attr('aria-expanded', 'false');
        } else {
            $inside.show();
            $postbox.removeClass('closed');
            $button.attr('aria-expanded', 'true');
        }
    });
});
</script>
