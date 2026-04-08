# Sprint Log: Quality & Security Sprint

## Phase 0: Bestandsaufnahme

### Plugin-Info
- **Name**: Abschussplan HGMH
- **Version**: 3.0.4 (Plugin Header + Konstante `AHGMH_PLUGIN_VERSION`)
- **DB Version**: 10 (Konstante `AHGMH_DB_VERSION`)
- **Text Domain**: `abschussplan-hgmh`
- **Haupt-PHP-Datei**: `wp-content/plugins/abschussplan-hgmh/abschussplan-hgmh.php`
- **Dateien**: 102 PHP, 11 JS, 6 CSS

### Architektur-Überblick
- **Singleton-Pattern**: `Abschussplan_HGMH` Hauptklasse
- **MVC-ähnlich**: Controllers, Services, Views, Repositories im `admin/` Ordner
- **Legacy + Modern parallel**: `class-admin-page-modern.php` (5623 Zeilen!) ist die aktive Admin-Seite, der modulare `class-admin-controller.php` ist deaktiviert
- **Service Layer**: 15 Services (Dashboard, Wildart, Jagdbezirk, Export, Import, Limits, Moderation, Email, etc.)
- **Repository Pattern**: Wildart, Meldegruppe, Jagdbezirk, Submission Repositories
- **Frontend**: Bootstrap 5.3 via CDN

### Admin-Menüstruktur (aktuell)
1. **Dashboard** (`abschussplan-hgmh`) - Übersicht
2. **Meldungen** (`abschussplan-hgmh-data`) - Datenverwaltung
3. **Obleute** (`abschussplan-hgmh-obleute`) - Obmann-Management
4. **Import** (`abschussplan-hgmh-import`) - Datenimport
5. **Einstellungen** (`abschussplan-hgmh-settings`) - Konfiguration

### Shortcodes
- `[abschuss_form species="..."]` - Meldeformular (eingeloggt)
- `[abschuss_table]` - Datentabelle mit Moderation
- `[abschuss_admin]` - Admin-Interface
- `[abschuss_summary]` - Öffentliche Statistik
- `[abschuss_summary_table]` - Zusammenfassungstabelle
- `[abschuss_limits]` - Limits-Konfiguration
- `[abschuss_form_public species="..."]` - Öffentliches Formular mit Email-Verifizierung

### Custom Post Types
- Keine CPTs - Plugin nutzt eigene DB-Tabellen

### REST API
- Namespace: `ahgmh/v1`
- Endpoints: `/info`, `/species`, `/summary`, `/submissions`, `/submissions/{id}` (CRUD)

### Hooks/Actions
- Cron: `ahgmh_page_views_cleanup_hook`, `ahgmh_activity_log_cleanup_hook`
- ~40 AJAX-Handler in `class-admin-page-modern.php` + weitere in Controllers

### Datenbank-Tabellen
- `wp_ahgmh_submissions` - Haupttabelle
- `wp_ahgmh_moderation_history` - Moderations-Audit-Trail
- `wp_ahgmh_email_log` - Email-Log
- `wp_ahgmh_activity_log` - Aktivitäts-Log
- `wp_ahgmh_meldegruppen_config` - Meldegruppen-Konfiguration
- `wp_ahgmh_jagdbezirke` - Jagdbezirke

### Bekannte Probleme
- `class-admin-page-modern.php` ist eine 5623-Zeilen Monolith-Datei
- Modularer Admin-Controller ist deaktiviert ("issues")
- Emergency AJAX handlers existieren (`ajax-handlers-emergency.php`)
- Legacy + Modern Code parallel → Redundanzen
- Test-Dateien im Plugin-Root (`test-*.php`, `syntax-test.php`)

---

## Sprint-Fortschritt
- [ ] Agent 1: Security Audit
- [ ] Agent 2: Admin UX Refactor
- [ ] Agent 3: Code Quality & Testing
- [ ] Agent 4: Frontend & Accessibility
