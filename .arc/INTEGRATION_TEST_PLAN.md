# Integration Test Plan - Abschussplan HGMH v2.2.0
# Hegegemeinschafts-Verwaltung mit CSV-Export-Kontinuität

## KRITISCHE ANFORDERUNGEN

### ✅ CSV-Export-System: 100% Backward Compatible
**DIESE FUNKTIONALITÄTEN DÜRFEN NICHT GEÄNDERT WERDEN:**

```php
// Bestehende AJAX-Handler (UNANTASTBAR)
wp_ajax_export_abschuss_csv         // Logged-in users
wp_ajax_nopriv_export_abschuss_csv  // Public access (KRITISCH)

// Bestehende URL-Patterns (UNANTASTBAR)
wp-admin/admin-ajax.php?action=export_abschuss_csv
?action=export_abschuss_csv&species=Rotwild
?action=export_abschuss_csv&meldegruppe=Gruppe_A
?action=export_abschuss_csv&from=2024-01-01&to=2024-12-31
?action=export_abschuss_csv&species=Rotwild&meldegruppe=Gruppe_A&from=2024-01-01

// Parameter-Verarbeitung (UNANTASTBAR)
$_GET['species'] -> sanitize_text_field()
$_GET['from'] -> sanitize_text_field() 
$_GET['to'] -> sanitize_text_field()
$_GET['filename'] -> sanitize_text_field()

// Dateiname-Templates (UNANTASTBAR)
get_option('ahgmh_export_filename_pattern', 'abschussplan_{species}_{date}')
```

## FEATURE-INTEGRATION MATRIX

### 1. Master-Detail Backend + User-Permissions
```php
// ✅ Obmann-Zuweisungen berücksichtigen Master-Detail Konfiguration
AHGMH_Permissions_Service::get_user_meldegruppe($user_id, $wildart);

// ✅ Master-Detail UI nur für Vorstand
if (!current_user_can('manage_options')) {
    // Kein Zugriff auf Master-Detail Interface
}

// ✅ Wildart-spezifische Meldegruppen-Konfiguration
$database->get_meldegruppen_by_species($species);
```

### 2. Limits-System + User-Permissions + CSV-Export
```php
// ✅ [abschuss_summary] mit korrekten Limits basierend auf User + Limit-Modus
$limit_mode = $database->get_wildart_limit_mode($species);
$permissions = AHGMH_Permissions_Service::filter_data_for_user($user_id, $data, $species);

// ✅ CSV-Export berücksichtigt User-Permissions NICHT (bleibt öffentlich)
// CSV-Export-Handler arbeitet OHNE Permission-Checks (wie bisher)
public function export_csv() {
    // KEINE Permission-Checks
    // KEINE User-Filterung 
    // KEINE Änderungen an bestehender Logik
}
```

### 3. Shortcode-Integration + Permission-System
```php
// ✅ [abschuss_form]: Meldegruppe-Preselection für Obmann
if (AHGMH_Permissions_Service::is_obmann($user_id)) {
    $preselected_meldegruppe = AHGMH_Permissions_Service::get_user_meldegruppe($user_id, $species);
}

// ✅ [abschuss_table]: User-basierte Filterung
$filtered_data = AHGMH_Permissions_Service::filter_table_data($user_id, $submissions, $species);

// ✅ [abschuss_admin]: Nur Vorstand
if (!current_user_can('manage_options')) {
    return '<p>Zugriff verweigert. Nur für Vorstände.</p>';
}

// ✅ [abschuss_limits]: Nur Vorstand
if (!current_user_can('manage_options')) {
    return '<p>Zugriff verweigert. Nur für Vorstände.</p>';
}
```

## COMPREHENSIVE TEST CASES

### A. CSV-Export-Regressionstests (KRITISCH)
```bash
# Test 1: Basis-Export ohne Parameter
curl "http://localhost/wp-admin/admin-ajax.php?action=export_abschuss_csv"
# Expected: CSV-Download mit allen Daten, Dateiname: abschussplan_alle_2024-08-19.csv

# Test 2: Species-Filter 
curl "http://localhost/wp-admin/admin-ajax.php?action=export_abschuss_csv&species=Rotwild"
# Expected: CSV-Download nur Rotwild, Dateiname: abschussplan_rotwild_2024-08-19.csv

# Test 3: Date-Range-Filter
curl "http://localhost/wp-admin/admin-ajax.php?action=export_abschuss_csv&from=2024-01-01&to=2024-06-30"
# Expected: CSV-Download Date-Range, Dateiname: abschussplan_alle_2024-08-19_2024-01-01_to_2024-06-30.csv

# Test 4: Kombinierte Filter
curl "http://localhost/wp-admin/admin-ajax.php?action=export_abschuss_csv&species=Rotwild&meldegruppe=Gruppe_A&from=2024-01-01"
# Expected: CSV-Download gefiltert, Dateiname entsprechend

# Test 5: Custom Filename
curl "http://localhost/wp-admin/admin-ajax.php?action=export_abschuss_csv&filename=custom_export_name"
# Expected: CSV-Download, Dateiname: custom_export_name.csv

# Test 6: Public Access (ohne Login)
curl "http://localhost/wp-admin/admin-ajax.php?action=export_abschuss_csv" # Kein Cookie
# Expected: CSV-Download funktioniert (wp_ajax_nopriv_export_abschuss_csv)
```

### B. Permission-System Tests
```php
// Test 1: Besucher (nicht angemeldet)
// [abschuss_summary] -> ✅ Funktioniert
// [abschuss_form] -> ❌ Login-Aufforderung
// [abschuss_table] -> ❌ Login-Aufforderung  
// [abschuss_admin] -> ❌ Login-Aufforderung
// [abschuss_limits] -> ❌ Login-Aufforderung

// Test 2: Obmann (WordPress User, assigned to Meldegruppe_Nord für Rotwild)
$user_id = 5;
update_user_meta($user_id, 'ahgmh_assigned_meldegruppe_Rotwild', 'Meldegruppe_Nord');

// [abschuss_summary] -> ✅ Alle Daten sichtbar
// [abschuss_form] -> ✅ Meldegruppe_Nord vorausgewählt für Rotwild
// [abschuss_table] -> ✅ Nur Meldegruppe_Nord Daten für Rotwild
// [abschuss_admin] -> ❌ Admin-only
// [abschuss_limits] -> ❌ Admin-only

// Test 3: Vorstand (WordPress Admin)
$admin_user = wp_create_user('admin', 'password', 'admin@hegegemeinschaft.de');
$user = new WP_User($admin_user);
$user->set_role('administrator');

// Alle Shortcodes -> ✅ Vollzugriff
```

### C. Master-Detail Backend Tests
```php
// Test 1: Wildart-Navigation
// Klick auf "Rotwild" -> Meldegruppen für Rotwild laden
// Klick auf "Damwild" -> Meldegruppen für Damwild laden
// Default Wildarten (Rotwild, Damwild) -> Nicht löschbar

// Test 2: Meldegruppen-Management per Wildart
// Neue Meldegruppe für Rotwild hinzufügen -> Nur in Rotwild verfügbar
// Meldegruppe für Damwild löschen -> Nur aus Damwild entfernt
// Meldegruppe umbenennen -> Nur für spezifische Wildart

// Test 3: Kategorie-Management mit Limits
// Kategorie hinzufügen -> In allen Wildarten verfügbar
// Limit-Modus wechseln -> Zwischen meldegruppen_specific/hegegemeinschaft_total
// Limit-Werte speichern -> Auto-Save mit User-Feedback
```

### D. Limits-System Tests
```php
// Test 1: Meldegruppen-spezifische Limits (Mode A)
$database->set_wildart_limit_mode('Rotwild', 'meldegruppen_specific');
$database->set_meldegruppe_limit('Rotwild', 'Meldegruppe_Nord', 'Böcke', 10);
$database->set_meldegruppe_limit('Rotwild', 'Meldegruppe_Süd', 'Böcke', 8);

// Expected: Status-Badges basierend auf individuellen Meldegruppe-Limits
// [abschuss_summary] zeigt: 
// Meldegruppe_Nord: IST 7/10 SOLL -> 🟢 (70%)
// Meldegruppe_Süd: IST 9/8 SOLL -> 🔥 (112.5%)

// Test 2: Hegegemeinschaft Total Limits (Mode B)  
$database->set_wildart_limit_mode('Damwild', 'hegegemeinschaft_total');
$database->set_wildart_total_limit('Damwild', 'Alttiere', 25);

// Expected: Status-Badge basierend auf Gesamt-Limit
// [abschuss_summary] zeigt:
// Damwild Alttiere: IST 22/25 SOLL (alle Meldegruppen zusammen) -> 🟡 (88%)
```

### E. Database Performance Tests
```sql
-- Test 1: User-Permission-Queries mit Indexierung
EXPLAIN SELECT * FROM wp_ahgmh_submissions s
LEFT JOIN wp_ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk
WHERE s.game_species = 'Rotwild' 
AND j.meldegruppe = 'Meldegruppe_Nord';

-- Expected: Query-Time < 100ms auch bei 10k+ Submissions

-- Test 2: Limits-Berechnung für komplexe Hegegemeinschaften
-- 5 Wildarten x 10 Meldegruppen x 15 Kategorien = 750 Limit-Kombinationen
-- Expected: [abschuss_summary] Render-Time < 500ms

-- Test 3: CSV-Export-Performance (unverändert)
-- 50k+ Submissions Export
-- Expected: Export-Time wie bisher (keine Verschlechterung)
```

### F. Migration Tests v2.1.0 -> v2.2.0
```php
// Test 1: Bestehende Installation Update
// Alte Meldegruppen-Konfiguration -> Neue Master-Detail Struktur
// User ohne Assignments -> Standard-Permission beibehalten
// Limit-Modus -> Default 'hegegemeinschaft_total' für Backward Compatibility

// Test 2: CSV-Export-URLs nach Migration
// Alle bestehenden URLs -> Müssen unverändert funktionieren
// Externe Scripts/Automatisierungen -> Keine Breaking Changes

// Test 3: Bestehende Shortcodes nach Migration
// [abschuss_form] ohne Parameter -> Funktioniert wie bisher
// [abschuss_table] ohne Parameter -> Zeigt alle Daten (Vorstand)
// [abschuss_summary] -> Berücksichtigt neue Limits-System
```

## PERFORMANCE BENCHMARKS

### Database Query Optimizations
```sql
-- Index für Permission-basierte Queries
ALTER TABLE wp_ahgmh_jagdbezirke 
ADD INDEX idx_meldegruppe_species (meldegruppe, wildart);

-- Index für Submissions-Filterung
ALTER TABLE wp_ahgmh_submissions 
ADD INDEX idx_species_date (game_species, field1);

-- Index für Limits-Queries
ALTER TABLE wp_ahgmh_meldegruppen_config
ADD INDEX idx_wildart_meldegruppe (wildart, meldegruppe_name);
```

### Frontend Load-Time Targets
```
[abschuss_form]: < 300ms (mit Permission-Check + Meldegruppe-Preselection)
[abschuss_table]: < 500ms (mit User-Filterung für Obleute)  
[abschuss_summary]: < 400ms (mit Limits-Berechnung)
[abschuss_admin]: < 800ms (Master-Detail Interface)
[abschuss_limits]: < 600ms (Limits-Matrix)
```

### CSV-Export Performance (UNCHANGED)
```
Export < 1000 Submissions: < 2 Sekunden
Export 1000-10k Submissions: < 10 Sekunden  
Export > 10k Submissions: < 30 Sekunden
Memory Usage: < 128MB per Export
```

## SECURITY VALIDATION

### Permission-Bypass Prevention
```php
// Test 1: Direct AJAX-Call Versuche
// Obmann versucht Admin-Functions -> Blocked
wp_ajax_save_wildart_config -> require manage_options
wp_ajax_assign_obmann -> require manage_options

// Test 2: Parameter-Manipulation
// [abschuss_table wildart="Rotwild"] als Obmann für Damwild -> Nur eigene Meldegruppe
// [abschuss_form] mit manipulierten Meldegruppe-Options -> Validierung

// Test 3: CSV-Export bleibt öffentlich (UNCHANGED)
// wp_ajax_nopriv_export_abschuss_csv -> Weiterhin öffentlich zugänglich
```

## HEGEGEMEINSCHAFTS-SPEZIFISCHE SCENARIOS

### Scenario 1: Kleine Hegegemeinschaft (2 Wildarten, 3 Meldegruppen)
```
Rotwild: Meldegruppe_Nord, Meldegruppe_Süd, Meldegruppe_Ost
Damwild: Meldegruppe_Gesamt

Limit-Modus: hegegemeinschaft_total (einfacher)
Obleute: 2 (ein Obmann für 2 Meldegruppen bei verschiedenen Wildarten)

Tests:
- Obmann-Assignment: Multi-Wildart-Zuweisungen
- [abschuss_summary]: Hegegemeinschaft Total Limits
- CSV-Export: Funktioniert unverändert für alle Automatisierungen
```

### Scenario 2: Große Hegegemeinschaft (5 Wildarten, 10+ Meldegruppen)
```
Rotwild: 5 Meldegruppen (Meldegruppe_Nord, Süd, Ost, West, Zentral)
Damwild: 4 Meldegruppen  
Rehwild: 8 Meldegruppen
Schwarzwild: 6 Meldegruppen
Raubwild: 2 Meldegruppen

Limit-Modus: meldegruppen_specific (detailliert)
Obleute: 15+ (spezialisierte Zuweisungen)

Tests:
- Performance: Master-Detail UI mit 100+ Meldegruppen-Kombinationen
- Memory: Limits-Matrix-Rendering
- User-Experience: Navigation bei komplexer Struktur
```

### Scenario 3: Migration einer bestehenden v2.1.0 Installation
```
Bestehende Daten:
- 50k+ Submissions über 3 Jahre
- 20+ Automatisierte CSV-Export-Scripts
- 8 WordPress-User mit verschiedenen Rollen
- Individuell konfigurierte Export-Filename-Patterns

Migration-Tests:
- CSV-Export-URLs: 100% funktionsfähig nach Update
- User-Permissions: Alle User erhalten Standard-Permission, Admin muss Obleute zuweisen
- Performance: Keine Verschlechterung bei bestehenden Queries
```

## SUCCESS CRITERIA

### ✅ KRITISCH: CSV-Export-Kontinuität
- [ ] Alle bestehenden CSV-Export-URLs funktionieren unverändert
- [ ] wp_ajax_nopriv_export_abschuss_csv bleibt öffentlich zugänglich  
- [ ] Parameter-Filterung (species, meldegruppe, from, to) unverändert
- [ ] Dateiname-Templates funktionieren wie bisher
- [ ] Export-Performance keine Verschlechterung
- [ ] Externe Scripts/Automatisierungen funktionieren weiterhin

### ✅ FEATURE-INTEGRATION
- [ ] Master-Detail Backend für alle Wildart-Meldegruppen-Kombinationen
- [ ] 3-Level Permission System funktioniert für alle Shortcodes
- [ ] Limits-System (beide Modi) integriert mit User-Permissions
- [ ] User-basierte Filterung in allen relevanten Shortcodes
- [ ] Admin CSV-Export-Sektion für Vorstand hinzugefügt

### ✅ PERFORMANCE
- [ ] Frontend-Shortcode Load-Times innerhalb Targets
- [ ] Database-Query-Performance optimiert für Permission-Checks
- [ ] Memory-Usage bei komplexen Hegegemeinschafts-Strukturen < 256MB
- [ ] Migration v2.1.0 -> v2.2.0 ohne Downtime möglich

### ✅ SECURITY & RELIABILITY
- [ ] Permission-Bypass-Versuche erfolgreich blockiert
- [ ] Input-Validation für alle neuen Parameter  
- [ ] XSS/SQL-Injection Prevention in allen neuen Features
- [ ] Graceful Degradation bei unvollständiger Konfiguration

### ✅ BACKWARD COMPATIBILITY
- [ ] Bestehende Shortcodes funktionieren ohne Parameter-Änderungen
- [ ] WordPress-Plugins-API unverändert für Extensions
- [ ] Database-Schema-Migration ohne Datenverlust
- [ ] User-Experience für bestehende Hegegemeinschaften nahtlos
