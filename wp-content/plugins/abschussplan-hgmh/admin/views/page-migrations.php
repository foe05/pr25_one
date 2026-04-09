<?php
/**
 * Admin View: Database Migrations
 *
 * Uses WordPress-native postbox and form-table patterns with proper i18n escaping.
 *
 * @var AHGMH_Migration_Manager $migration_manager Migration manager instance
 * @var int $current_version Current database schema version
 * @var array $migrations List of available migrations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Current Version -->
<div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; margin-bottom: 20px;">
    <div class="postbox" style="margin: 0; padding: 20px;">
        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Aktuelle Schema-Version', 'abschussplan-hgmh'); ?></div>
        <div style="font-size: 32px; font-weight: 600; color: #2271b1;"><?php echo esc_html($current_version); ?></div>
    </div>

    <!-- Important Warning -->
    <div class="notice notice-warning inline" style="margin: 0; display: flex; align-items: center;">
        <p>
            <strong><?php echo esc_html__('Wichtiger Hinweis:', 'abschussplan-hgmh'); ?></strong><br>
            <?php echo esc_html__('Erstellen Sie vor jeder Migration ein Backup Ihrer Datenbank. Migrationen koennen Datenbank-Strukturen aendern und sind nicht immer reversibel.', 'abschussplan-hgmh'); ?>
        </p>
    </div>
</div>

<!-- Available Migrations -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Verfuegbare Migrationen', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <?php if (empty($migrations)): ?>
            <p style="color: #646970;"><?php echo esc_html__('Keine Migrationen verfuegbar.', 'abschussplan-hgmh'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php echo esc_html__('Version', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Name', 'abschussplan-hgmh'); ?></th>
                        <th style="width: 130px;"><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                        <th style="width: 150px;"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($migrations as $migration): ?>
                        <tr>
                            <td><strong><?php echo esc_html($migration['version']); ?></strong></td>
                            <td><?php echo esc_html($migration['name']); ?></td>
                            <td>
                                <?php if ($migration['applied']): ?>
                                    <span style="display: inline-block; background: #00a32a; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                        <?php echo esc_html__('Angewendet', 'abschussplan-hgmh'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; background: #f0f0f1; color: #646970; padding: 3px 10px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                        <?php echo esc_html__('Ausstehend', 'abschussplan-hgmh'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$migration['applied']): ?>
                                    <button type="button"
                                            class="button button-primary button-small ahgmh-migrate-to"
                                            data-version="<?php echo esc_attr($migration['version']); ?>"
                                            data-name="<?php echo esc_attr($migration['name']); ?>">
                                        <?php echo esc_html__('Ausfuehren', 'abschussplan-hgmh'); ?>
                                    </button>
                                <?php elseif ($migration['version'] > 0): ?>
                                    <button type="button"
                                            class="button button-small ahgmh-rollback-to"
                                            data-version="<?php echo esc_attr($migration['version'] - 1); ?>"
                                            data-name="<?php echo esc_attr($migration['name']); ?>">
                                        <?php echo esc_html__('Zurueckrollen', 'abschussplan-hgmh'); ?>
                                    </button>
                                <?php else: ?>
                                    <span style="color: #c3c4c7;"><?php echo esc_html__('Keine Aktion', 'abschussplan-hgmh'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Schnellaktionen', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <p>
            <button type="button" class="button button-primary" id="ahgmh-migrate-latest">
                <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                <?php echo esc_html__('Zur neuesten Version migrieren', 'abschussplan-hgmh'); ?>
            </button>

            <button type="button" class="button" id="ahgmh-rollback-all" style="margin-left: 10px; color: #b32d2e;">
                <span class="dashicons dashicons-undo" style="margin-top: 4px;"></span>
                <?php echo esc_html__('Alle Migrationen zurueckrollen', 'abschussplan-hgmh'); ?>
            </button>
        </p>
        <p class="description">
            <?php echo esc_html__('Schnellaktionen fuehren mehrere Migrationen auf einmal aus. Stellen Sie sicher, dass Sie ein aktuelles Backup haben.', 'abschussplan-hgmh'); ?>
        </p>
    </div>
</div>

<!-- Migration Log -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Migrations-Protokoll', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <div id="ahgmh-log-output" style="background: #f6f7f7; border: 1px solid #ddd; border-radius: 3px; padding: 15px; min-height: 80px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; line-height: 1.6;">
            <p style="color: #646970; margin: 0;"><?php echo esc_html__('Bereit. Fuehren Sie eine Migration aus, um das Protokoll zu sehen.', 'abschussplan-hgmh'); ?></p>
        </div>
    </div>
</div>

<!-- Database Information -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Datenbank-Informationen', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php echo esc_html__('Datenbank-Name', 'abschussplan-hgmh'); ?></th>
                <td><code><?php echo esc_html(DB_NAME); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Tabellen-Praefix', 'abschussplan-hgmh'); ?></th>
                <td><code><?php global $wpdb; echo esc_html($wpdb->prefix); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Schema-Version Option', 'abschussplan-hgmh'); ?></th>
                <td><code>hgmh_db_schema_version</code></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Migrations-Verzeichnis', 'abschussplan-hgmh'); ?></th>
                <td><code><?php echo esc_html(plugin_dir_path(__FILE__) . '../../migrations/'); ?></code></td>
            </tr>
        </table>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

    function addLog(message, type) {
        var $output = $('#ahgmh-log-output');
        var timestamp = new Date().toLocaleTimeString();
        var color = type === 'error' ? '#d63638' : (type === 'success' ? '#00a32a' : '#646970');
        var prefix = type === 'error' ? '[FEHLER]' : (type === 'success' ? '[OK]' : '[INFO]');

        var logEntry = '<div style="color: ' + color + '; margin-bottom: 4px;">' +
                      '<span style="color: #c3c4c7;">[' + timestamp + ']</span> ' +
                      prefix + ' ' + message +
                      '</div>';

        $output.append(logEntry);
        $output.scrollTop($output[0].scrollHeight);
    }

    function clearLog() {
        $('#ahgmh-log-output').html('<p style="color: #646970; margin: 0;"><?php echo esc_js(__('Migrations-Protokoll wird ausgefuehrt...', 'abschussplan-hgmh')); ?></p>');
    }

    // Migrate to specific version
    $('.ahgmh-migrate-to').on('click', function() {
        var $btn = $(this);
        var version = $btn.data('version');
        var name = $btn.data('name');

        if (!confirm('<?php echo esc_js(__('Migration ausfuehren?', 'abschussplan-hgmh')); ?>\n\n' +
                     '<?php echo esc_js(__('Version:', 'abschussplan-hgmh')); ?> ' + version + '\n' +
                     '<?php echo esc_js(__('Name:', 'abschussplan-hgmh')); ?> ' + name + '\n\n' +
                     '<?php echo esc_js(__('Stellen Sie sicher, dass Sie ein Backup haben!', 'abschussplan-hgmh')); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php echo esc_js(__('Starte Migration zu Version', 'abschussplan-hgmh')); ?> ' + version + '...', 'info');

        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_run_migration',
                target_version: version,
                nonce: '<?php echo esc_js(wp_create_nonce('ahgmh_admin_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php echo esc_js(__('Migration erfolgreich abgeschlossen!', 'abschussplan-hgmh')); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            addLog(logEntry, 'info');
                        });
                    }

                    addLog('<?php echo esc_js(__('Finale Version:', 'abschussplan-hgmh')); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php echo esc_js(__('Migration fehlgeschlagen:', 'abschussplan-hgmh')); ?> ' + (response.data || '<?php echo esc_js(__('Unbekannter Fehler', 'abschussplan-hgmh')); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php echo esc_js(__('AJAX Fehler:', 'abschussplan-hgmh')); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Rollback to specific version
    $('.ahgmh-rollback-to').on('click', function() {
        var $btn = $(this);
        var version = $btn.data('version');
        var name = $btn.data('name');

        if (!confirm('<?php echo esc_js(__('Migration zurueckrollen?', 'abschussplan-hgmh')); ?>\n\n' +
                     '<?php echo esc_js(__('Zurueck zu Version:', 'abschussplan-hgmh')); ?> ' + version + '\n' +
                     '<?php echo esc_js(__('Betroffene Migration:', 'abschussplan-hgmh')); ?> ' + name + '\n\n' +
                     '<?php echo esc_js(__('Dies wird Datenbank-Aenderungen rueckgaengig machen!', 'abschussplan-hgmh')); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php echo esc_js(__('Starte Rollback zu Version', 'abschussplan-hgmh')); ?> ' + version + '...', 'info');

        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_rollback_migration',
                target_version: version,
                nonce: '<?php echo esc_js(wp_create_nonce('ahgmh_admin_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php echo esc_js(__('Rollback erfolgreich abgeschlossen!', 'abschussplan-hgmh')); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            addLog(logEntry, 'info');
                        });
                    }

                    addLog('<?php echo esc_js(__('Finale Version:', 'abschussplan-hgmh')); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php echo esc_js(__('Rollback fehlgeschlagen:', 'abschussplan-hgmh')); ?> ' + (response.data || '<?php echo esc_js(__('Unbekannter Fehler', 'abschussplan-hgmh')); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php echo esc_js(__('AJAX Fehler:', 'abschussplan-hgmh')); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Migrate to latest version
    $('#ahgmh-migrate-latest').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Zur neuesten Version migrieren?', 'abschussplan-hgmh')); ?>\n\n' +
                     '<?php echo esc_js(__('Dies fuehrt alle ausstehenden Migrationen aus.', 'abschussplan-hgmh')); ?>\n' +
                     '<?php echo esc_js(__('Stellen Sie sicher, dass Sie ein Backup haben!', 'abschussplan-hgmh')); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php echo esc_js(__('Starte Migration zur neuesten Version...', 'abschussplan-hgmh')); ?>', 'info');

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_run_migration',
                target_version: null,
                nonce: '<?php echo esc_js(wp_create_nonce('ahgmh_admin_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php echo esc_js(__('Migration erfolgreich abgeschlossen!', 'abschussplan-hgmh')); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            addLog(logEntry, 'info');
                        });
                    }

                    addLog('<?php echo esc_js(__('Finale Version:', 'abschussplan-hgmh')); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php echo esc_js(__('Migration fehlgeschlagen:', 'abschussplan-hgmh')); ?> ' + (response.data || '<?php echo esc_js(__('Unbekannter Fehler', 'abschussplan-hgmh')); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php echo esc_js(__('AJAX Fehler:', 'abschussplan-hgmh')); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Rollback all migrations
    $('#ahgmh-rollback-all').on('click', function() {
        if (!confirm('<?php echo esc_js(__('WARNUNG: Alle Migrationen zurueckrollen?', 'abschussplan-hgmh')); ?>\n\n' +
                     '<?php echo esc_js(__('Dies macht ALLE Datenbank-Aenderungen rueckgaengig!', 'abschussplan-hgmh')); ?>\n' +
                     '<?php echo esc_js(__('Stellen Sie sicher, dass Sie ein Backup haben!', 'abschussplan-hgmh')); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php echo esc_js(__('Starte Rollback zu Version 0...', 'abschussplan-hgmh')); ?>', 'info');

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_rollback_migration',
                target_version: 0,
                nonce: '<?php echo esc_js(wp_create_nonce('ahgmh_admin_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php echo esc_js(__('Rollback erfolgreich abgeschlossen!', 'abschussplan-hgmh')); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            addLog(logEntry, 'info');
                        });
                    }

                    addLog('<?php echo esc_js(__('Finale Version:', 'abschussplan-hgmh')); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php echo esc_js(__('Rollback fehlgeschlagen:', 'abschussplan-hgmh')); ?> ' + (response.data || '<?php echo esc_js(__('Unbekannter Fehler', 'abschussplan-hgmh')); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php echo esc_js(__('AJAX Fehler:', 'abschussplan-hgmh')); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
