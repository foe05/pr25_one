<?php
/**
 * Quick Check: Activity Logs
 *
 * Rufe auf: /wp-content/plugins/abschussplan-hgmh/check-activity-logs.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Security check
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Admin login required.');
}

// Load Activity Logger
require_once('includes/class-activity-logger.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #0073aa; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0073aa; color: white; }
        tr:hover { background: #f5f5f5; }
        .status { padding: 4px 8px; border-radius: 4px; }
        .status.success { background: #46b450; color: white; }
        .status.error { background: #dc3232; color: white; }
        .json-view { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        pre { margin: 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Activity Logs Check - v3.0.0</h1>

    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'ahgmh_activity_log';

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

    echo "<h2>Tabellen-Status</h2>";
    if ($table_exists) {
        echo '<p><span class="status success">✓ Tabelle existiert: ' . $table_name . '</span></p>';

        // Get table structure
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        echo "<h3>Tabellen-Struktur:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col->Field}</td>";
            echo "<td>{$col->Type}</td>";
            echo "<td>{$col->Null}</td>";
            echo "<td>{$col->Key}</td>";
            echo "<td>{$col->Default}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Get row count
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "<p><strong>Anzahl Logs:</strong> $count</p>";

        // Get recent logs
        if ($count > 0) {
            $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 20");

            echo "<h3>Letzte 20 Activity Logs:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>Action</th><th>Context</th><th>IP Hash</th><th>Created At</th></tr>";

            foreach ($logs as $log) {
                echo "<tr>";
                echo "<td>{$log->id}</td>";
                echo "<td>{$log->user_id}</td>";
                echo "<td>{$log->action}</td>";
                echo "<td><div class='json-view'><pre>" . htmlspecialchars(substr($log->context, 0, 100)) . "...</pre></div></td>";
                echo "<td>" . substr($log->ip_hash, 0, 16) . "...</td>";
                echo "<td>{$log->created_at}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p><em>Keine Logs vorhanden. Teste das Logging:</em></p>";

            // Test logging
            $logger = new AHGMH_Activity_Logger();
            $test_result = $logger->log('test_action', get_current_user_id(), [
                'test' => 'Activity Logger Test',
                'timestamp' => current_time('mysql')
            ]);

            if ($test_result) {
                echo '<p><span class="status success">✓ Test-Log erfolgreich erstellt</span></p>';
                echo '<p>Lade die Seite neu um den Log zu sehen.</p>';
            } else {
                echo '<p><span class="status error">✗ Test-Log fehlgeschlagen</span></p>';
            }
        }

        // Test get_stats method
        echo "<h3>Logger-Statistiken Test:</h3>";
        try {
            $logger = new AHGMH_Activity_Logger();
            $stats = $logger->get_stats('user', get_current_user_id());
            echo "<div class='json-view'><pre>" . print_r($stats, true) . "</pre></div>";
        } catch (Exception $e) {
            echo '<p><span class="status error">✗ Fehler: ' . $e->getMessage() . '</span></p>';
        }

    } else {
        echo '<p><span class="status error">✗ Tabelle nicht gefunden: ' . $table_name . '</span></p>';
        echo '<p><strong>Problem:</strong> Die Activity Log Tabelle wurde nicht erstellt.</p>';
        echo '<p><strong>Lösung:</strong> Führe die Datenbank-Migration aus oder erstelle die Tabelle manuell:</p>';
        echo '<pre>CREATE TABLE ' . $table_name . ' (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    action varchar(50) NOT NULL,
    context longtext,
    ip_hash varchar(64),
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY  (id),
    KEY user_id_idx (user_id),
    KEY action_idx (action),
    KEY created_at_idx (created_at)
);</pre>';
    }
    ?>

    <hr>
    <p><a href="<?php echo admin_url(); ?>">← Zurück zum Dashboard</a></p>
</div>
</body>
</html>
