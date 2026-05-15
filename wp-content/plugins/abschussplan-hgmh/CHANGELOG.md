# Changelog - Abschussplan HGMH

## [26.4.1] - 2026-05-15

### Geändert

- **`[abschuss_summary_table]` – Spalte "Jagdbezirk" entfernt**: Die öffentliche Übersichtstabelle (`templates/summary-table-template.php`) zeigt nun 6 statt 7 Spalten. Render-Methode und Datenbankabfrage blieben unverändert.

### Bugfixes

- **CSV-Export `export_abschuss_csv` – Umlaute**: Der über den Admin-Link (`admin-ajax.php?action=export_abschuss_csv`) erzeugte CSV-Export schrieb keinen UTF-8-BOM. Excel interpretierte die Datei als Windows-1252, wodurch Umlaute (ä/ö/ü/ß) zerstört wurden. `export_csv()` in `includes/class-form-handler.php` schreibt jetzt den BOM `\xEF\xBB\xBF`. (Der zweite Export-Weg über `AHGMH_Export_Service` war bereits in 26.4.0 korrigiert.)
- **CSV-Export `export_abschuss_csv` – Spalte "Interne Notiz"**: Die Spalte "Interne Notiz" fehlte; stattdessen wurde der Wert von `internal_note` fälschlich unter der Überschrift "Bemerkung" ausgegeben. Die Query lädt jetzt zusätzlich `s.notes`, "Bemerkung" liefert wieder den korrekten Wert und "Interne Notiz" wurde als eigene Spalte ergänzt.

### Allgemein

- Version 26.4.1 (Browser-Cache-Buster für alle JS/CSS-Assets)
- Keine Datenmodell-Änderungen, keine Migrationen — `s.notes` und `s.internal_note` existieren bereits im Schema.

---

## [26.4.0] - 2026-04-13

### Admin-Backend: Meldungen-Editiermaske

- **Meldegruppen-Quelle korrigiert**: `ajax_get_submission_data()` liest Meldegruppen jetzt direkt aus `hgmh_meldegruppen JOIN hgmh_wildarten` (dieselbe Quelle wie der Jagdbezirk-Lookup) statt der alten `get_meldegruppen_for_wildart()`-Methode mit inkonsistenter Quelle
- **Erfasser-Löschung behoben**: `submitted_by_user_id` wird nur noch überschrieben wenn `user_id > 0` explizit gesendet wird
- **`buildSelect()` für User-Objekte repariert**: Wertet jetzt korrekt `{id, display_name}`-Objekte aus (vorher wurde `[object Object]` als Wert verwendet)
- **Layout vollständig überarbeitet**: CSS-Grid statt verschachtelter `<table>`, Speichern/Abbrechen-Buttons sowohl oben als auch unten (mobiltauglich)
- **Jagdbezirk-Vorauswahl beim Öffnen**: Nach der initialen Meldegruppen-AJAX-Anfrage wird der aktuelle Jagdbezirk automatisch vorausgewählt
- **`colspan` korrigiert**: Von 11 auf 12 (Tabelle hat 1 Checkbox-Spalte + 11 `<th>`-Spalten)

### Frontend: `[abschuss_table]` Inline-Editieren

- **Script-Ladegarantie**: `wp_enqueue_script()` wird jetzt direkt in `render_table()` aufgerufen, nicht nur im `wp_enqueue_scripts`-Hook — verhindert dass der Bearbeiten-Button ohne JS-Handler gerendert wird (tritt auf bei Page Buildern, Template-Dateien, Gutenberg Reusable Blocks)
- **Icon-Buttons statt Text**: Freigeben = `bi-check-lg` (grün), Ablehnen = `bi-x-lg` (rot), Bearbeiten = `bi-pencil` (grau) — jeweils `btn-sm` mit `title`-Attribut
- **Abschuss-Feld jetzt Dropdown**: Lädt Kategorien der Wildart via neuen AJAX-Endpoint `ahgmh_table_get_options`
- **Meldegruppe editierbar**: War `readonly`-Textfeld, ist jetzt ein `<select>` mit allen aktiven Meldegruppen für die Wildart
- **Jagdbezirk editierbar**: War `readonly`-Textfeld, ist jetzt ein `<select>` der per `ahgmh_table_get_jagdbezirke` in Abhängigkeit der Meldegruppe geladen wird; aktueller Wert wird vorausgewählt
- **`eigenjagdbezirk_id` beim Speichern**: Save-Handler sendet jetzt die numerische Jagdbezirk-ID; Zellen-Update nach Speichern aktualisiert auch Meldegruppe- und Jagdbezirk-Spalten
- **Zwei neue AJAX-Endpoints** in `AHGMH_Table_Shortcode`:
  - `ahgmh_table_get_options` — Kategorien + Meldegruppen für eine Wildart (Login erforderlich)
  - `ahgmh_table_get_jagdbezirke` — Jagdbezirke für eine Meldegruppe (Login erforderlich)

### Allgemein

- Version 26.4.0 (Browser-Cache-Buster für alle JS/CSS-Assets)

---

## [26.1.0] - 2026-04-08

### Sicherheit (Security)
- **Kritisch**: SQL Injection in `RENAME TABLE`, `SHOW TABLES LIKE` und raw Table-Name-Interpolation behoben (admin-page-modern, database-handler, dashboard-service, import-validator)
- **Kritisch**: `export_csv()` war ohne Authentifizierung öffentlich erreichbar (nopriv AJAX)
- Nonce-Verifizierung zu Page-Views AJAX-Handlern hinzugefügt
- `$_SERVER`-Werte in Permissions-Service und Verification-Service sanitized
- IP-Adress-Auflösung in Activity-Logger abgesichert
- LIKE-Queries ohne `prepare()` in uninstall.php behoben
- Schwache Nonce-Prüfung in `ajax_refresh_table()` behoben (war umgehbar)

### Admin UX
- Admin-Menüstruktur von 5 auf 7 Einträge umgebaut (Dashboard, Meldungen, Wildarten, Obleute, Berichte, Import/Export, Einstellungen)
- Emoji-Präfixe aus Menü-Labels entfernt
- Alle Admin-Views auf WordPress-native UI-Komponenten umgestellt (postbox, nav-tab-wrapper, form-table, wp-list-table)
- Bootstrap-Klassen aus Admin-Views entfernt
- ~775 Zeilen Inline-CSS/JS aus Schedule-Settings-View entfernt
- ~200 Zeilen Inline-CSS aus Reports-View entfernt
- Konsistente Farbpalette (WordPress-Standard: #2271b1, #00a32a, #dba617, #d63638)
- Verbesserte Labels, Beschreibungstexte und Hilfetexte (deutsch)
- Wildarten-Konfiguration als eigene Admin-Seite herausgelöst

### Code-Qualität
- 9 Test-/Debug-Dateien aus Plugin-Root entfernt
- Emergency AJAX-Handler entfernt (~530 Zeilen duplizierter Code)
- `AHGMH_Wildart_Controller` und `AHGMH_Limits_Controller` direkt aktiviert
- Verwaiste `ahgmh_activate_plugin()` in Klassenmethode konsolidiert
- 20+ fehlende i18n-Wrappings in Controller-AJAX-Responses nachgerüstet
- Feature-Flag-Labels und -Beschreibungen internationalisiert
- WPCS-Formatierung auf 10+ Dateien angewandt
- Duplizierte WHERE-Clause-Logik in Page-View-Logger extrahiert
- Error-Logging für Activity-Logger und Page-View-Logger verbessert
- `composer.json` Autoloading um `frontend/shortcodes/` ergänzt

### Frontend & Barrierefreiheit (WCAG 2.1 AA)
- `<fieldset>`/`<legend>` für alle Formulargruppen hinzugefügt
- `aria-required`, `aria-describedby`, `aria-busy`, `aria-invalid` Attribute ergänzt
- Fokus-Management für Modals (Fokus setzen bei Öffnung, zurückgeben bei Schließung)
- Tastatur-Navigation für Admin-Tabs (Pfeiltasten, Home, End)
- Pagination: HTML-Entity-Doppel-Escaping behoben, ARIA-Attribute ergänzt
- Alle `alert()`-Aufrufe durch Inline-Fehlermeldungen bzw. Notification-System ersetzt (20+)
- Touch-Targets auf mindestens 44x44px für mobile Nutzung
- Responsive Card-Layout für Datentabellen auf Mobilgeräten
- Screen-Reader-Announcer für Moderationsaktionen
- Englische Validierungsmeldungen durch deutsche ersetzt
- Hidden Radio-Inputs von `display:none` auf accessible clip-Pattern umgestellt
- `@media (prefers-reduced-motion)` für Animationen

## [3.0.1] - 2026-01-13

### 🐛 Bugfixes

#### Kritische Fehler (CRITICAL)
- **Bug #4**: Obleute-Zuweisung nach Jagdbezirk-Umstellung korrigiert
  - Doppeltes Speichersystem vereinheitlicht (User Meta als Primärspeicher)
  - Datenbanktabelle `hgmh_meldegruppen.obmann_user_id` wird jetzt synchronisiert
  - Submission-Repository: Falsche Tabellennamen korrigiert (`ahgmh_*` → `hgmh_*`)
  - Neue Migration 004 synchronisiert Legacy-Zuweisungen

- **Bug #6**: Migration 002 "Migrate existing data" repariert
  - Klassennamen korrigiert: `HGMH_Migration_002` → `AHGMH_Migration_002`
  - Git Merge-Konflikte aufgelöst
  - Fehlende `hgmh_email_log` Tabelle hinzugefügt

- **Bug #7**: Wildarten-Tab Fehler behoben
  - Doppelte UI-Elemente entfernt (separater Menüpunkt entfernt)
  - Doppelte AJAX-Handler-Registrierung behoben
  - Debug-Code aus Produktionsdateien entfernt
  - CSS-Klassen-Inkonsistenzen korrigiert

- **Bug #8**: Wildarten-Konfiguration unter Einstellungen repariert
  - Zentrale Wildarten-Verwaltung unter "Einstellungen > Wildart-Konfiguration"
  - Obmann-Zuweisung bleibt im separaten Tab
  - Limits-Speicherung funktioniert korrekt

- **Bug #10**: Jagdbezirk-Management komplett implementiert
  - Neues Admin-Tab "Jagdbezirke" unter Einstellungen
  - CRUD-Funktionen für Jagdbezirke
  - Many-to-Many-Zuordnung zu Meldegruppen
  - Repository/Service/Controller Architektur
  - Migration 003 erstellt Junction-Tabelle

- **Bug #11**: [abschuss_form_public] Shortcode repariert
  - Fehlende PHP-Dateien wiederhergestellt
  - Konstanten-Fehler behoben (`AHGMH_VERSION` → `AHGMH_PLUGIN_VERSION`)
  - Korrekter Shortcode-Name: `[abschuss_form_public]`

- **Bug #12**: Tabelle `wp_ahgmh_activity_log` wird jetzt erstellt
  - Behoben durch Migration 001 Fix

- **Bug #13b+14**: abschuss_summary zeigt nur genehmigte Meldungen
  - `approved_only` Parameter zu allen Datenbank-Methoden hinzugefügt
  - Öffentliche Shortcodes filtern auf `status = 'approved'`
  - Admin-Ansichten zeigen weiterhin alle Meldungen

#### Hohe Priorität (HIGH)
- **Bug #2**: Export-Funktionalität zentralisiert und korrigiert
  - Zentrale Export-Service-Klasse erstellt
  - 15 vollständige Spalten im CSV-Export
  - Filter-Unterstützung (Wildart, Datum, Meldegruppe, Status)
  - Deutsche Spaltenüberschriften und UTF-8 BOM

- **Bug #3**: Edit-Form vollständig gemacht und Layout angepasst
  - Fehlende Felder hinzugefügt: Meldegruppe, Erfasser, Erfassungsdatum
  - Dropdown-Beschränkungen implementiert
  - 2-Spalten-Layout mit responsivem Design
  - Max-height mit Scrollbar

- **Bug #9**: Jagdbezirk-Feld in [abschuss_form] hinzugefügt
  - Dynamisches Dropdown nach Meldegruppen-Auswahl
  - AJAX-Loading der verfügbaren Jagdbezirke
  - Validierung und Speicherung

- **Bug #13a**: [abschuss_table] zeigt beide Spalten (Meldegruppe UND Jagdbezirk)
  - Spalten-Anzeige korrigiert in allen Tabellen-Templates

#### Mittlere/Niedrige Priorität (MEDIUM/LOW)
- **Bug #1**: Dashboard aktualisiert mit allen Daten
  - "Dieser Monat" Statistiken mit Status-Aufschlüsselung
  - "Status nach Wildart" Übersicht aktualisiert
  - Shortcode-Referenz mit allen 9 Shortcodes vervollständigt
  - Versionsinformation und GitHub-Links hinzugefügt

- **Bug #5**: Migrationen-Tab unter Einstellungen verschoben
  - Von separatem Menüpunkt zu Unter-Tab in Einstellungen

### ✨ Neue Features
- Jagdbezirk-Management-System mit CRUD-Funktionen
- Zentrale Export-Funktionalität mit Filtern
- Verbessertes Dashboard mit Live-Statistiken
- Vollständige Shortcode-Referenz im Admin

### 🔧 Technische Verbesserungen
- Migration 003: Junction-Tabelle für Jagdbezirk-Meldegruppen-Zuordnung
- Migration 004: Synchronisation von Obleute-Zuweisungen
- Repository-Pattern für Jagdbezirke
- Konsistente Verwendung von `hgmh_*` Tabellenpräfix

### 📝 Dokumentation
- CHANGELOG.md erstellt
- Shortcode-Referenz im Admin-Dashboard

---

## [3.0.0] - 2026-01-10

### 🎉 Hauptversion - Komplette Architektur-Umstellung

- Enterprise-Features: Moderation Workflow, Email-Verifizierung, Activity Logging
- Migration Manager für Datenbank-Upgrades
- Neue Datenbank-Struktur mit 7 Tabellen
- Repository/Service/Controller Architektur
- Feature Flags System

