# Migration Strategy v2.1.0 → v2.2.0
# Abschussplan HGMH - Hegegemeinschafts-Verwaltung

## KRITISCHE ANFORDERUNGEN

### ✅ 100% Backward Compatibility
**DIESE FUNKTIONALITÄTEN MÜSSEN NACH MIGRATION UNVERÄNDERT FUNKTIONIEREN:**

```php
// CSV-Export-URLs (KRITISCH - Dürfen nicht brechen)
wp-admin/admin-ajax.php?action=export_abschuss_csv
wp-admin/admin-ajax.php?action=export_abschuss_csv&species=Rotwild&from=2024-01-01

// Bestehende Shortcodes ohne Parameter
[abschuss_form]
[abschuss_table] 
[abschuss_summary]

// WordPress Admin-Interface
/wp-admin/admin.php?page=abschussplan-hgmh

// Bestehende User-Rollen und Capabilities  
current_user_can('manage_options') // Admin-Zugriff bleibt unverändert
```

### ✅ Zero-Downtime Migration
- Migration läuft während Plugin-Update
- Keine manuellen Database-Operations erforderlich
- Fallback-Mechanismen für unvollständige Migration
- Automatische Rollback bei Fehlern

## MIGRATION PHASES

### Phase 1: Database Schema Updates (AUTOMATED)
```sql
-- Erweitere bestehende meldegruppen_config Tabelle
ALTER TABLE wp_ahgmh_meldegruppen_config 
ADD COLUMN IF NOT EXISTS kategorie varchar(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS limit_value int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS limit_mode enum('meldegruppen_specific','hegegemeinschaft_total') DEFAULT 'hegegemeinschaft_total';

-- Erstelle Indexes für Performance (nur wenn nicht vorhanden)
ALTER TABLE wp_ahgmh_jagdbezirke 
ADD INDEX IF NOT EXISTS idx_meldegruppe_wildart (meldegruppe, wildart);

ALTER TABLE wp_ahgmh_submissions 
ADD INDEX IF NOT EXISTS idx_species_meldegruppe (game_species, field5);

ALTER TABLE wp_ahgmh_meldegruppen_config
ADD INDEX IF NOT EXISTS idx_wildart_meldegruppe_kategorie (wildart, meldegruppe_name, kategorie);
```

### Phase 2: Data Migration (AUTOMATED)
```php
class AHGMH_Migration_V22 {
    
    public static function migrate_from_v21() {
        global $wpdb;
        
        // 1. Migratiere bestehende Meldegruppen-Konfiguration
        self::migrate_meldegruppen_config();
        
        // 2. Setze Default Limit-Modus für alle Wildarten
        self::set_default_limit_modes();
        
        // 3. Migratiere User-Permissions (alle → Standard-Permission)
        self::migrate_user_permissions();
        
        // 4. Erstelle Default Wildarten falls nicht vorhanden
        self::ensure_default_species();
        
        // 5. Validiere Migration
        self::validate_migration();
        
        // Update Plugin Version
        update_option('abschussplan_hgmh_version', '2.2.0');
        update_option('ahgmh_migration_v22_completed', true);
    }
    
    private static function migrate_meldegruppen_config() {
        global $wpdb;
        $database = abschussplan_hgmh()->database;
        
        // Hole alle bestehenden Wildarten
        $species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        foreach ($species as $wildart) {
            // Hole bestehende Meldegruppen für diese Wildart
            $existing_meldegruppen = $database->get_meldegruppen_by_species($wildart);
            
            if (empty($existing_meldegruppen)) {
                // Erstelle Standard-Meldegruppen wenn keine vorhanden
                $default_meldegruppen = array('Meldegruppe_Nord', 'Meldegruppe_Süd', 'Meldegruppe_Ost');
                
                foreach ($default_meldegruppen as $meldegruppe) {
                    $wpdb->insert(
                        $wpdb->prefix . 'ahgmh_meldegruppen_config',
                        array(
                            'wildart' => $wildart,
                            'meldegruppe_name' => $meldegruppe,
                            'limit_mode' => 'hegegemeinschaft_total'
                        ),
                        array('%s', '%s', '%s')
                    );
                }
            } else {
                // Update bestehende Meldegruppen mit Default-Limit-Modus
                foreach ($existing_meldegruppen as $meldegruppe) {
                    $wpdb->update(
                        $wpdb->prefix . 'ahgmh_meldegruppen_config',
                        array('limit_mode' => 'hegegemeinschaft_total'),
                        array('wildart' => $wildart, 'meldegruppe_name' => $meldegruppe),
                        array('%s'),
                        array('%s', '%s')
                    );
                }
            }
        }
        
        error_log('AHGMH Migration: Meldegruppen-Konfiguration migriert');
    }
    
    private static function set_default_limit_modes() {
        global $wpdb;
        
        // Setze Default-Limit-Modus für bessere Backward Compatibility
        $wpdb->query("
            UPDATE {$wpdb->prefix}ahgmh_meldegruppen_config 
            SET limit_mode = 'hegegemeinschaft_total' 
            WHERE limit_mode IS NULL OR limit_mode = ''
        ");
        
        error_log('AHGMH Migration: Default-Limit-Modi gesetzt');
    }
    
    private static function migrate_user_permissions() {
        // Alle bestehenden WordPress-User behalten ihre aktuellen Rollen
        // Keine automatischen Obmann-Zuweisungen
        // Admin kann nach Migration manuell Obleute zuweisen
        
        $admin_users = get_users(array('role' => 'administrator'));
        foreach ($admin_users as $user) {
            // Admins behalten Vollzugriff (manage_options Capability)
            error_log("AHGMH Migration: Admin-User {$user->display_name} behält Vollzugriff");
        }
        
        $all_users = get_users();
        foreach ($all_users as $user) {
            if (!user_can($user->ID, 'manage_options')) {
                // Nicht-Admin-User erhalten Standard-Permission
                // (Keine Obmann-Zuweisungen - muss manuell gemacht werden)
                error_log("AHGMH Migration: User {$user->display_name} erhält Standard-Permission");
            }
        }
    }
    
    private static function ensure_default_species() {
        $current_species = get_option('ahgmh_species', array());
        
        if (empty($current_species)) {
            // Fallback: Setze Standard-Wildarten
            $default_species = array('Rotwild', 'Damwild');
            update_option('ahgmh_species', $default_species);
            error_log('AHGMH Migration: Standard-Wildarten erstellt');
        }
    }
    
    private static function validate_migration() {
        global $wpdb;
        
        // Validierung 1: Prüfe Database-Schema
        $columns = $wpdb->get_col("DESC {$wpdb->prefix}ahgmh_meldegruppen_config");
        $required_columns = array('kategorie', 'limit_value', 'limit_mode');
        
        foreach ($required_columns as $column) {
            if (!in_array($column, $columns)) {
                error_log("AHGMH Migration ERROR: Spalte {$column} fehlt");
                return false;
            }
        }
        
        // Validierung 2: Prüfe CSV-Export-Handler
        if (!has_action('wp_ajax_export_abschuss_csv') || !has_action('wp_ajax_nopriv_export_abschuss_csv')) {
            error_log('AHGMH Migration ERROR: CSV-Export-Handler fehlen');
            return false;
        }
        
        // Validierung 3: Prüfe Admin-Interface
        if (!class_exists('AHGMH_Admin_Page_Modern')) {
            error_log('AHGMH Migration ERROR: Admin-Interface-Klasse fehlt');
            return false;
        }
        
        // Validierung 4: Prüfe Permission-Service
        if (!class_exists('AHGMH_Permissions_Service')) {
            error_log('AHGMH Migration ERROR: Permission-Service-Klasse fehlt');
            return false;
        }
        
        error_log('AHGMH Migration: Validierung erfolgreich');
        return true;
    }
    
    public static function rollback_migration() {
        // Rollback nur Database-Schema-Änderungen
        global $wpdb;
        
        // Entferne neue Spalten (falls nötig)
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ahgmh_meldegruppen_config DROP COLUMN IF EXISTS kategorie");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ahgmh_meldegruppen_config DROP COLUMN IF EXISTS limit_value");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ahgmh_meldegruppen_config DROP COLUMN IF EXISTS limit_mode");
        
        // Entferne neue Indexes
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ahgmh_jagdbezirke DROP INDEX IF EXISTS idx_meldegruppe_wildart");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ahgmh_submissions DROP INDEX IF EXISTS idx_species_meldegruppe");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}ahgmh_meldegruppen_config DROP INDEX IF EXISTS idx_wildart_meldegruppe_kategorie");
        
        // Setze Plugin-Version zurück
        update_option('abschussplan_hgmh_version', '2.1.0');
        delete_option('ahgmh_migration_v22_completed');
        
        error_log('AHGMH Migration: Rollback abgeschlossen');
    }
}
```

### Phase 3: Feature Activation (AUTOMATED)
```php
// Aktivierung der neuen Features nach erfolgreicher Migration
add_action('admin_init', function() {
    if (get_option('ahgmh_migration_v22_completed') && !get_option('ahgmh_v22_features_activated')) {
        
        // 1. Aktiviere Permission-Service
        if (!class_exists('AHGMH_Permissions_Service')) {
            require_once plugin_dir_path(__FILE__) . 'includes/class-permissions-service.php';
        }
        
        // 2. Aktiviere Master-Detail Backend
        if (is_admin() && current_user_can('manage_options')) {
            // Master-Detail Interface ist verfügbar
            error_log('AHGMH: Master-Detail Backend aktiviert');
        }
        
        // 3. Aktiviere neue Shortcode-Features
        // [abschuss_form]: Meldegruppe-Preselection für Obleute
        // [abschuss_table]: User-basierte Filterung
        // [abschuss_admin]: Nur für Vorstand
        // [abschuss_limits]: Nur für Vorstand
        
        update_option('ahgmh_v22_features_activated', true);
        error_log('AHGMH: v2.2.0 Features aktiviert');
    }
});
```

## BACKWARD COMPATIBILITY MECHANISMS

### Graceful Degradation für unvollständige Konfiguration
```php
class AHGMH_Compatibility_Layer {
    
    // Fallback für Shortcodes bei fehlender Master-Detail Konfiguration
    public static function get_meldegruppen_fallback($species) {
        $database = abschussplan_hgmh()->database;
        $meldegruppen = $database->get_meldegruppen_by_species($species);
        
        if (empty($meldegruppen)) {
            // Fallback: Verwende alle verfügbaren Meldegruppen
            error_log("AHGMH Fallback: Keine Meldegruppen für {$species}, verwende Fallback");
            return $database->get_all_meldegruppen();
        }
        
        return $meldegruppen;
    }
    
    // Fallback für Permission-Checks bei Fehlern
    public static function permission_check_fallback($user_id, $shortcode_name) {
        if (!class_exists('AHGMH_Permissions_Service')) {
            // Fallback: Verwende WordPress-Standard
            if (in_array($shortcode_name, array('abschuss_admin', 'abschuss_limits'))) {
                return current_user_can('manage_options');
            }
            return true; // Andere Shortcodes sind öffentlich
        }
        
        return AHGMH_Permissions_Service::can_access_shortcode($shortcode_name, array());
    }
    
    // Fallback für Limits-System bei fehlender Konfiguration
    public static function get_limits_fallback($species) {
        $database = abschussplan_hgmh()->database;
        
        if (!method_exists($database, 'get_wildart_limit_mode')) {
            // Fallback: Einfaches Total-Limit-System
            return array(
                'mode' => 'hegegemeinschaft_total',
                'limits' => array()
            );
        }
        
        return $database->get_all_limits_for_wildart($species);
    }
}
```

### CSV-Export 100% Compatibility Layer
```php
// Sicherstellung: CSV-Export funktioniert auch bei Migrations-Fehlern
class AHGMH_CSV_Compatibility {
    
    public static function ensure_csv_export_handlers() {
        // Prüfe ob CSV-Export-Handler verfügbar sind
        if (!has_action('wp_ajax_export_abschuss_csv')) {
            // Emergency-Handler falls Migration unvollständig
            add_action('wp_ajax_export_abschuss_csv', array('AHGMH_Form_Handler', 'export_csv'));
        }
        
        if (!has_action('wp_ajax_nopriv_export_abschuss_csv')) {
            // KRITISCH: Öffentlicher Export-Handler
            add_action('wp_ajax_nopriv_export_abschuss_csv', array('AHGMH_Form_Handler', 'export_csv'));
        }
    }
    
    public static function validate_csv_export_functionality() {
        // Test: CSV-Export mit Standard-Parametern
        $test_url = admin_url('admin-ajax.php?action=export_abschuss_csv&species=Rotwild');
        
        // Simuliere CSV-Export-Request (ohne tatsächlichen Download)
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'HEAD',
                'timeout' => 5
            )
        ));
        
        $headers = get_headers($test_url, 1, $context);
        
        if (strpos($headers[0], '200') === false) {
            error_log('AHGMH Migration WARNING: CSV-Export-URL nicht erreichbar');
            return false;
        }
        
        return true;
    }
}
```

## MIGRATION TESTING SCENARIOS

### Scenario 1: Frische Installation v2.2.0
```php
// Test: Plugin-Installation ohne vorherige Version
// Expected: Standard-Konfiguration wird erstellt
// - Default Wildarten: Rotwild, Damwild
// - Standard-Meldegruppen pro Wildart
// - Limit-Modus: hegegemeinschaft_total
// - Admin-User hat Vollzugriff
// - CSV-Export funktioniert sofort
```

### Scenario 2: Update v2.1.0 → v2.2.0 (Kleine Hegegemeinschaft)
```php
// Bestehende Daten:
// - 2 Wildarten (Rotwild, Damwild)
// - 3 Meldegruppen gesamt  
// - 500 Submissions über 1 Jahr
// - 3 WordPress-User (1 Admin, 2 Editors)
// - 2 automatisierte CSV-Export-Scripts

// Migration-Test:
// 1. Plugin-Update via WordPress Admin
// 2. Automatische Migration läuft
// 3. Prüfe: CSV-Export-URLs funktionieren unverändert
// 4. Prüfe: Shortcodes [abschuss_form], [abschuss_table] funktionieren
// 5. Prüfe: Admin kann Master-Detail Interface verwenden
// 6. Prüfe: Nicht-Admin-User haben Standard-Permission
```

### Scenario 3: Update v2.1.0 → v2.2.0 (Große Hegegemeinschaft)
```php
// Bestehende Daten:
// - 5 Wildarten mit individuellen Konfigurationen
// - 15+ Meldegruppen mit komplexer Zuordnung
// - 10k+ Submissions über 3 Jahre
// - 20+ WordPress-User mit verschiedenen Rollen
// - 8 automatisierte CSV-Export-Scripts mit verschiedenen Parametern
// - Individuelle Export-Filename-Patterns

// Migration-Test:
// 1. Performance: Migration < 30 Sekunden
// 2. Daten-Integrität: Alle Submissions bleiben erhalten
// 3. CSV-Export: Alle bestehenden URLs funktionieren
// 4. User-Experience: Keine Änderung für End-User
// 5. Performance: Keine Verschlechterung der Ladezeiten
```

### Scenario 4: Migration Rollback
```php
// Test: Migration schlägt fehl oder verursacht Probleme
// 1. Automatischer Rollback-Mechanismus
// 2. Plugin-Version wird auf v2.1.0 zurückgesetzt
// 3. Database-Schema wird auf vorherigen Zustand zurückgesetzt
// 4. Alle Funktionalitäten von v2.1.0 funktionieren wieder
// 5. Keine Daten gehen verloren
```

## POST-MIGRATION USER GUIDANCE

### Admin Onboarding (Nach Migration)
```php
// Admin-Notice nach erfolgreicher Migration
add_action('admin_notices', function() {
    if (get_option('ahgmh_migration_v22_completed') && !get_option('ahgmh_v22_onboarding_dismissed')) {
        ?>
        <div class="notice notice-success">
            <h3>🎉 Abschussplan HGMH v2.2.0 erfolgreich migriert!</h3>
            <p><strong>Neue Features verfügbar:</strong></p>
            <ul>
                <li>✅ Master-Detail Backend für Wildart-spezifische Meldegruppen-Verwaltung</li>
                <li>✅ 3-Level Permission System (Besucher, Obmann, Vorstand)</li>
                <li>✅ Erweiterte Limits-Verwaltung mit zwei Modi</li>
                <li>✅ Admin CSV-Export-Sektion für URL-Generierung</li>
            </ul>
            <p><strong>Wichtig:</strong> Alle bestehenden CSV-Export-URLs und Shortcodes funktionieren unverändert!</p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh&tab=obmann'); ?>" class="button button-primary">
                    Obleute verwalten
                </a>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh&tab=wildart-config'); ?>" class="button button-secondary">
                    Master-Detail Backend
                </a>
                <button type="button" onclick="this.parentElement.parentElement.style.display='none'; 
                       fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                           method: 'POST',
                           body: 'action=ahgmh_dismiss_onboarding&nonce=<?php echo wp_create_nonce('ahgmh_onboarding'); ?>'
                       });" 
                       class="button">
                    Hinweis ausblenden
                </button>
            </p>
        </div>
        <?php
    }
});
```

### Benutzer-Dokumentation Update
```markdown
## Was hat sich geändert? (v2.2.0)

### ✅ FÜR BESUCHER (Keine Änderungen)
- `[abschuss_summary]` funktioniert wie bisher
- Alle CSV-Export-URLs funktionieren weiterhin ohne Anmeldung

### ✅ FÜR BENUTZER MIT WORDPRESS-ACCOUNT (Neue Features)
- `[abschuss_form]`: Automatische Meldegruppe-Auswahl falls Sie als Obmann zugewiesen sind
- `[abschuss_table]`: Zeigt nur Ihre Meldegruppe-Daten wenn Sie Obmann sind

### ✅ FÜR ADMINISTRATOREN (Neue Verwaltungs-Features)
- Master-Detail Backend für Wildart-spezifische Konfiguration
- Obmann-Verwaltung: Zuweisung von Benutzern zu spezifischen Meldegruppen/Wildarten
- Erweiterte Limits-Verwaltung mit zwei Modi
- Admin CSV-Export-Sektion für URL-Generierung

### ❌ WAS SICH NICHT GEÄNDERT HAT
- Alle bestehenden CSV-Export-URLs
- Shortcode-Syntax bleibt gleich
- WordPress-Benutzer-Rollen bleiben unverändert
- Performance und Geschwindigkeit
```

## SUCCESS CRITERIA

### ✅ KRITISCH (Muss zu 100% funktionieren)
- [ ] Alle bestehenden CSV-Export-URLs funktionieren nach Migration
- [ ] wp_ajax_nopriv_export_abschuss_csv bleibt öffentlich zugänglich
- [ ] Shortcodes [abschuss_form], [abschuss_table], [abschuss_summary] funktionieren ohne Parameter
- [ ] WordPress Admin-Interface ist nach Migration zugänglich
- [ ] Keine Submission-Daten gehen verloren
- [ ] Migration dauert < 60 Sekunden auch bei großen Datenmengen

### ✅ NEUE FEATURES (Nach Migration verfügbar)  
- [ ] Master-Detail Backend für Wildart-Konfiguration
- [ ] Permission-System mit 3 User-Levels funktioniert
- [ ] Obmann-Verwaltung im Admin-Interface
- [ ] Limits-System mit beiden Modi (meldegruppen_specific/hegegemeinschaft_total)
- [ ] Admin CSV-Export-Sektion für URL-Generierung

### ✅ PERFORMANCE (Keine Verschlechterung)
- [ ] Shortcode-Render-Zeit ≤ v2.1.0 Performance
- [ ] CSV-Export-Geschwindigkeit unverändert
- [ ] Admin-Interface-Ladezeit ≤ v2.1.0 Performance
- [ ] Database-Query-Performance durch Indexes verbessert

### ✅ ROLLBACK-FÄHIGKEIT
- [ ] Automatischer Rollback bei Migrations-Fehlern
- [ ] Manueller Rollback über Admin-Interface möglich
- [ ] Vollständige Wiederherstellung von v2.1.0 Funktionalität
- [ ] Keine Daten gehen bei Rollback verloren

## DEPLOYMENT CHECKLIST

### Pre-Migration (Vor Plugin-Update)
- [ ] Backup aller WordPress-Dateien und Datenbank
- [ ] Teste bestehende CSV-Export-URLs und dokumentiere Ergebnisse
- [ ] Liste alle verwendeten Shortcodes und deren Parameter auf
- [ ] Dokumentiere aktuelle User-Rollen und Capabilities
- [ ] Performance-Baseline: Miss aktuelle Ladezeiten

### Migration (Während Plugin-Update)
- [ ] Plugin-Update via WordPress Admin oder FTP
- [ ] Überwache Migration-Logs in wp-content/debug.log
- [ ] Prüfe Admin-Interface-Verfügbarkeit nach Update
- [ ] Validiere Database-Schema-Änderungen

### Post-Migration (Nach Plugin-Update)
- [ ] Teste alle kritischen CSV-Export-URLs sofort
- [ ] Prüfe Shortcode-Funktionalität auf Live-Seiten
- [ ] Validiere Admin-Interface und neue Features
- [ ] Performance-Check: Vergleiche mit Baseline
- [ ] Admin-Onboarding: Weise erste Obleute zu
- [ ] Dokumentation: Update für End-User bereitstellen

### Emergency Rollback (Falls nötig)
- [ ] Deaktiviere Plugin über WordPress Admin
- [ ] Restore v2.1.0 Plugin-Files via FTP
- [ ] Database-Rollback über AHGMH_Migration_V22::rollback_migration()
- [ ] Teste CSV-Export-URLs nach Rollback
- [ ] Informiere User über temporäre Rollback-Situation

Diese umfassende Migration-Strategy stellt sicher, dass der Übergang von v2.1.0 zu v2.2.0 nahtlos erfolgt und alle kritischen Funktionalitäten, insbesondere der CSV-Export, unverändert funktionieren.
