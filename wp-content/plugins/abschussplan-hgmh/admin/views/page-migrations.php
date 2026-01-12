<?php
/**
 * Admin View: Database Migrations
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

<div class="wrap">
    <h1><?php _e('Datenbank Migrationen', 'abschussplan-hgmh'); ?></h1>

    <!-- Current Version Card -->
    <div class="ahgmh-version-card" style="margin: 20px 0;">
        <div style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: normal;"><?php _e('Aktuelle Schema Version', 'abschussplan-hgmh'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo esc_html($current_version); ?></p>
        </div>
    </div>

    <!-- Important Warning -->
    <div class="notice notice-warning" style="margin: 20px 0;">
        <p>
            <strong><?php _e('⚠️ Wichtiger Hinweis:', 'abschussplan-hgmh'); ?></strong><br>
            <?php _e('Erstellen Sie vor jeder Migration ein Backup Ihrer Datenbank. Migrationen können Datenbank-Strukturen ändern und sind nicht immer reversibel.', 'abschussplan-hgmh'); ?>
        </p>
    </div>

    <!-- Available Migrations -->
    <div class="ahgmh-migrations-list" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h2><?php _e('Verfügbare Migrationen', 'abschussplan-hgmh'); ?></h2>

        <?php if (empty($migrations)): ?>
            <p style="color: #666;"><?php _e('Keine Migrationen verfügbar.', 'abschussplan-hgmh'); ?></p>
        <?php else: ?>
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php _e('Version', 'abschussplan-hgmh'); ?></th>
                        <th><?php _e('Name', 'abschussplan-hgmh'); ?></th>
                        <th style="width: 120px;"><?php _e('Status', 'abschussplan-hgmh'); ?></th>
                        <th style="width: 150px;"><?php _e('Aktionen', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($migrations as $migration): ?>
                        <tr>
                            <td><?php echo esc_html($migration['version']); ?></td>
                            <td><?php echo esc_html($migration['name']); ?></td>
                            <td>
                                <?php if ($migration['applied']): ?>
                                    <span style="display: inline-block; background: #46b450; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                        <?php _e('Angewendet', 'abschussplan-hgmh'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; background: #f0f0f0; color: #666; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                        <?php _e('Ausstehend', 'abschussplan-hgmh'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$migration['applied']): ?>
                                    <button type="button"
                                            class="button button-primary ahgmh-migrate-to"
                                            data-version="<?php echo esc_attr($migration['version']); ?>"
                                            data-name="<?php echo esc_attr($migration['name']); ?>">
                                        <?php _e('Ausführen', 'abschussplan-hgmh'); ?>
                                    </button>
                                <?php elseif ($migration['version'] > 0): ?>
                                    <button type="button"
                                            class="button ahgmh-rollback-to"
                                            data-version="<?php echo esc_attr($migration['version'] - 1); ?>"
                                            data-name="<?php echo esc_attr($migration['name']); ?>">
                                        <?php _e('Zurückrollen', 'abschussplan-hgmh'); ?>
                                    </button>
                                <?php else: ?>
                                    <span style="color: #999;"><?php _e('Keine Aktion', 'abschussplan-hgmh'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="ahgmh-quick-actions" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3><?php _e('Schnellaktionen', 'abschussplan-hgmh'); ?></h3>

        <p>
            <button type="button" class="button button-primary button-large" id="ahgmh-migrate-latest">
                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                <?php _e('Zur neuesten Version migrieren', 'abschussplan-hgmh'); ?>
            </button>

            <button type="button" class="button button-large" id="ahgmh-rollback-all" style="margin-left: 10px; color: #b32d2e;">
                <span class="dashicons dashicons-undo" style="margin-top: 3px;"></span>
                <?php _e('Alle Migrationen zurückrollen', 'abschussplan-hgmh'); ?>
            </button>
        </p>

        <p class="description">
            <?php _e('Schnellaktionen führen mehrere Migrationen auf einmal aus. Stellen Sie sicher, dass Sie ein aktuelles Backup haben.', 'abschussplan-hgmh'); ?>
        </p>
    </div>

    <!-- Migration Log -->
    <div class="ahgmh-migration-log" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3><?php _e('Migrations-Protokoll', 'abschussplan-hgmh'); ?></h3>

        <div id="ahgmh-log-output" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; min-height: 100px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; line-height: 1.5;">
            <p style="color: #666; margin: 0;"><?php _e('Bereit. Führen Sie eine Migration aus, um das Protokoll zu sehen.', 'abschussplan-hgmh'); ?></p>
        </div>
    </div>

    <!-- Database Information -->
    <div class="ahgmh-db-info" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3><?php _e('Datenbank-Informationen', 'abschussplan-hgmh'); ?></h3>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Datenbank Name', 'abschussplan-hgmh'); ?></th>
                <td><code><?php echo esc_html(DB_NAME); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Tabellen Präfix', 'abschussplan-hgmh'); ?></th>
                <td><code><?php global $wpdb; echo esc_html($wpdb->prefix); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Schema Version Option', 'abschussplan-hgmh'); ?></th>
                <td><code>hgmh_db_schema_version</code></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Migrations Verzeichnis', 'abschussplan-hgmh'); ?></th>
                <td><code><?php echo esc_html(plugin_dir_path(__FILE__) . '../../migrations/'); ?></code></td>
            </tr>
        </table>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

    // Log helper function
    function addLog(message, type) {
        var $output = $('#ahgmh-log-output');
        var timestamp = new Date().toLocaleTimeString();
        var color = type === 'error' ? '#d63638' : (type === 'success' ? '#00a32a' : '#666');
        var icon = type === 'error' ? '❌' : (type === 'success' ? '✅' : '➜');

        var logEntry = '<div style="color: ' + color + '; margin-bottom: 5px;">' +
                      '<span style="color: #999;">[' + timestamp + ']</span> ' +
                      icon + ' ' + message +
                      '</div>';

        $output.append(logEntry);
        $output.scrollTop($output[0].scrollHeight);
    }

    // Clear log
    function clearLog() {
        $('#ahgmh-log-output').html('<p style="color: #666; margin: 0;"><?php _e('Migrations-Protokoll wird ausgeführt...', 'abschussplan-hgmh'); ?></p>');
    }

    // Migrate to specific version
    $('.ahgmh-migrate-to').on('click', function() {
        var $btn = $(this);
        var version = $btn.data('version');
        var name = $btn.data('name');

        if (!confirm('<?php _e('Migration ausführen?', 'abschussplan-hgmh'); ?>\n\n' +
                     '<?php _e('Version:', 'abschussplan-hgmh'); ?> ' + version + '\n' +
                     '<?php _e('Name:', 'abschussplan-hgmh'); ?> ' + name + '\n\n' +
                     '<?php _e('Stellen Sie sicher, dass Sie ein Backup haben!', 'abschussplan-hgmh'); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php _e('Starte Migration zu Version', 'abschussplan-hgmh'); ?> ' + version + '...', 'info');

        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_run_migration',
                target_version: version,
                nonce: '<?php echo wp_create_nonce('ahgmh_migration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php _e('Migration erfolgreich abgeschlossen!', 'abschussplan-hgmh'); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            var type = logEntry.includes('✅') ? 'success' : (logEntry.includes('❌') ? 'error' : 'info');
                            addLog(logEntry, type);
                        });
                    }

                    addLog('<?php _e('Finale Version:', 'abschussplan-hgmh'); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php _e('Migration fehlgeschlagen:', 'abschussplan-hgmh'); ?> ' + (response.data || '<?php _e('Unbekannter Fehler', 'abschussplan-hgmh'); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php _e('AJAX Fehler:', 'abschussplan-hgmh'); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Rollback to specific version
    $('.ahgmh-rollback-to').on('click', function() {
        var $btn = $(this);
        var version = $btn.data('version');
        var name = $btn.data('name');

        if (!confirm('<?php _e('Migration zurückrollen?', 'abschussplan-hgmh'); ?>\n\n' +
                     '<?php _e('Zurück zu Version:', 'abschussplan-hgmh'); ?> ' + version + '\n' +
                     '<?php _e('Betroffene Migration:', 'abschussplan-hgmh'); ?> ' + name + '\n\n' +
                     '<?php _e('Dies wird Datenbank-Änderungen rückgängig machen!', 'abschussplan-hgmh'); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php _e('Starte Rollback zu Version', 'abschussplan-hgmh'); ?> ' + version + '...', 'info');

        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_rollback_migration',
                target_version: version,
                nonce: '<?php echo wp_create_nonce('ahgmh_migration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php _e('Rollback erfolgreich abgeschlossen!', 'abschussplan-hgmh'); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            var type = logEntry.includes('✅') ? 'success' : (logEntry.includes('❌') ? 'error' : 'info');
                            addLog(logEntry, type);
                        });
                    }

                    addLog('<?php _e('Finale Version:', 'abschussplan-hgmh'); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php _e('Rollback fehlgeschlagen:', 'abschussplan-hgmh'); ?> ' + (response.data || '<?php _e('Unbekannter Fehler', 'abschussplan-hgmh'); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php _e('AJAX Fehler:', 'abschussplan-hgmh'); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Migrate to latest version
    $('#ahgmh-migrate-latest').on('click', function() {
        if (!confirm('<?php _e('Zur neuesten Version migrieren?', 'abschussplan-hgmh'); ?>\n\n' +
                     '<?php _e('Dies führt alle ausstehenden Migrationen aus.', 'abschussplan-hgmh'); ?>\n' +
                     '<?php _e('Stellen Sie sicher, dass Sie ein Backup haben!', 'abschussplan-hgmh'); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php _e('Starte Migration zur neuesten Version...', 'abschussplan-hgmh'); ?>', 'info');

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_run_migration',
                target_version: null,
                nonce: '<?php echo wp_create_nonce('ahgmh_migration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php _e('Migration erfolgreich abgeschlossen!', 'abschussplan-hgmh'); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            var type = logEntry.includes('✅') ? 'success' : (logEntry.includes('❌') ? 'error' : 'info');
                            addLog(logEntry, type);
                        });
                    }

                    addLog('<?php _e('Finale Version:', 'abschussplan-hgmh'); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php _e('Migration fehlgeschlagen:', 'abschussplan-hgmh'); ?> ' + (response.data || '<?php _e('Unbekannter Fehler', 'abschussplan-hgmh'); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php _e('AJAX Fehler:', 'abschussplan-hgmh'); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // Rollback all migrations
    $('#ahgmh-rollback-all').on('click', function() {
        if (!confirm('<?php _e('WARNUNG: Alle Migrationen zurückrollen?', 'abschussplan-hgmh'); ?>\n\n' +
                     '<?php _e('Dies macht ALLE Datenbank-Änderungen rückgängig!', 'abschussplan-hgmh'); ?>\n' +
                     '<?php _e('Stellen Sie sicher, dass Sie ein Backup haben!', 'abschussplan-hgmh'); ?>')) {
            return;
        }

        clearLog();
        addLog('<?php _e('Starte Rollback zu Version 0...', 'abschussplan-hgmh'); ?>', 'info');

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ahgmh_rollback_migration',
                target_version: 0,
                nonce: '<?php echo wp_create_nonce('ahgmh_migration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    addLog('<?php _e('Rollback erfolgreich abgeschlossen!', 'abschussplan-hgmh'); ?>', 'success');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            var type = logEntry.includes('✅') ? 'success' : (logEntry.includes('❌') ? 'error' : 'info');
                            addLog(logEntry, type);
                        });
                    }

                    addLog('<?php _e('Finale Version:', 'abschussplan-hgmh'); ?> ' + response.data.final_version, 'success');

                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    addLog('<?php _e('Rollback fehlgeschlagen:', 'abschussplan-hgmh'); ?> ' + (response.data || '<?php _e('Unbekannter Fehler', 'abschussplan-hgmh'); ?>'), 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                addLog('<?php _e('AJAX Fehler:', 'abschussplan-hgmh'); ?> ' + error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
