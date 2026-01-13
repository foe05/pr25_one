# Page View Tracking - Dokumentation

## Übersicht

Das Page View Tracking System protokolliert die Aufrufe der wichtigsten Shortcodes des Abschussplan HGMH Plugins und bietet detaillierte Statistiken über die Nutzung der Webseiten.

## Features

### 1. Automatisches Logging

Das System protokolliert automatisch jeden Aufruf der folgenden Shortcodes:
- `[abschuss_summary_table]` - Öffentliche Zusammenfassungstabelle
- `[abschuss_summary]` - Zusammenfassung/Statistik
- `[abschuss_form]` - Eingabeformular
- `[abschuss_table]` - Submissions-Tabelle

### 2. Erfasste Daten

Für jeden Seitenaufruf werden folgende Informationen gespeichert:
- **Shortcode Name** - Welcher Shortcode wurde aufgerufen
- **User ID** - WordPress User-ID (0 für nicht angemeldete Besucher)
- **Benutzername** - Anzeigename des Benutzers
- **IP-Adresse** - Optional, kann in den Einstellungen deaktiviert werden
- **Shortcode-Parameter** - Die verwendeten Attribute (z.B. species="Rotwild")
- **Seiten-URL** - Die vollständige URL der aufgerufenen Seite
- **Referer** - Von welcher Seite der Besucher kam
- **User Agent** - Browser-Information
- **Zeitstempel** - Datum und Uhrzeit des Aufrufs

### 3. Admin-Interface

#### Zugriff
Das Admin-Interface ist erreichbar unter:
**WordPress Admin → Abschussplan HGMH → Seitenaufrufe**

#### Dashboard mit Statistiken

**Übersichtskarten:**
- Gesamt Aufrufe
- Anzahl angemeldeter Benutzer
- Authentifizierte Aufrufe
- Anonyme Aufrufe

**Diagramme:**
- Aufrufe nach Shortcode
- Top Benutzer (meiste Aufrufe)
- Aufrufe nach Tag (letzte 30 Tage mit Balkendiagramm)

**Filter:**
- Nach Shortcode filtern
- Zeitraum eingrenzen (Von/Bis Datum)
- Kombinierbare Filter

**Detaillierte Aufrufliste:**
- Tabellarische Ansicht aller Page Views
- Pagination (50 Einträge pro Seite)
- Zeigt: Datum/Zeit, Shortcode, Benutzer, Parameter, Seiten-URL

### 4. Export-Funktion

- **CSV-Export** mit allen gefilterten Daten
- Dateiname: `page-views-YYYY-MM-DD.csv`
- Enthält alle Spalten der Datenbank

### 5. Datenschutz (DSGVO-konform)

#### Einstellungen:

**IP-Adressen speichern**
- Aktivieren/Deaktivieren der IP-Speicherung
- Wenn deaktiviert, wird keine IP-Adresse gespeichert

**IP-Adressen anonymisieren**
- Entfernt das letzte Oktett von IPv4-Adressen (z.B. 192.168.1.0)
- Entfernt die letzten 80 Bits von IPv6-Adressen
- DSGVO-konform

**Automatische Bereinigung**
- Alte Einträge automatisch löschen
- Standard: 90 Tage
- Konfigurierbare Aufbewahrungsdauer (1-365 Tage)
- Läuft täglich per WordPress Cron

### 6. Datenverwaltung

**Alte Einträge löschen**
- Manuelles Löschen von Einträgen älter als 90 Tage
- Button im Admin-Interface

**Alle Einträge löschen**
- Löscht die komplette Page Views Datenbank
- Sicherheitsabfrage vor Ausführung

## Technische Details

### Datenbanktabelle

**Tabellenname:** `wp_ahgmh_page_views`

**Struktur:**
```sql
CREATE TABLE wp_ahgmh_page_views (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    shortcode_name varchar(100) NOT NULL,
    user_id bigint(20) NOT NULL DEFAULT 0,
    user_display_name varchar(255) DEFAULT '',
    ip_address varchar(100) DEFAULT NULL,
    shortcode_attributes text,
    page_url text,
    referer text,
    user_agent text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    KEY shortcode_name (shortcode_name),
    KEY user_id (user_id),
    KEY created_at (created_at)
);
```

### Klassen

**AHGMH_Page_View_Logger** (`includes/class-page-view-logger.php`)
- Service-Klasse für das Logging
- Methoden:
  - `log_page_view($shortcode_name, $attributes)` - Erstellt einen Log-Eintrag
  - `get_statistics($filters, $limit, $offset)` - Holt Statistiken
  - `get_summary($filters)` - Holt Zusammenfassungen
  - `cleanup_old_logs($days)` - Löscht alte Logs
  - `export_csv($filters)` - Exportiert als CSV

**AHGMH_Page_Views_Controller** (`admin/controllers/class-page-views-controller.php`)
- Admin-Controller für die Verwaltung
- AJAX-Handler für alle Aktionen
- Registriert Admin-Menü

### Cron Job

**Hook:** `ahgmh_page_views_cleanup_hook`
**Häufigkeit:** Täglich
**Funktion:** `ahgmh_page_views_cleanup_cron()`

Wird bei Plugin-Aktivierung registriert und bei Deaktivierung entfernt.

### Integration

Das Logging erfolgt automatisch in den Shortcode-Methoden:
```php
// Log page view
$logger = new AHGMH_Page_View_Logger();
$logger->log_page_view('abschuss_summary_table', $atts);
```

## WordPress-Optionen

Das System nutzt folgende WordPress-Optionen:

- `ahgmh_log_ip_addresses` (bool) - IP-Speicherung aktiviert
- `ahgmh_anonymize_ip` (bool) - IP-Anonymisierung aktiviert
- `ahgmh_auto_cleanup_enabled` (bool) - Auto-Cleanup aktiviert
- `ahgmh_auto_cleanup_days` (int) - Aufbewahrungsdauer in Tagen

## Installation & Update

### Automatische Installation bei Plugin-Aktivierung

Die Datenbanktabelle wird automatisch erstellt bei:
1. Plugin-Aktivierung
2. Plugin-Update (DB-Version-Check)

### Manuelle Installation

Falls die Tabelle nicht erstellt wurde:
1. WordPress Admin → Plugins
2. Abschussplan HGMH deaktivieren
3. Abschussplan HGMH wieder aktivieren

## Performance

- Logging erfolgt asynchron und blockiert nicht die Seitenausgabe
- Indizes auf wichtigen Spalten für schnelle Abfragen
- Pagination für große Datenmengen
- Auto-Cleanup verhindert unbegrenztes Wachstum der Datenbank

## Sicherheit

- Alle Eingaben werden sanitiert
- Nonce-Prüfung für alle AJAX-Requests
- Nur Administratoren haben Zugriff auf die Statistiken
- IP-Adressen können anonymisiert oder komplett deaktiviert werden

## Verwendungsbeispiele

### Beispiel 1: Welche Seiten werden am häufigsten besucht?
1. Admin → Seitenaufrufe öffnen
2. "Aufrufe nach Shortcode" Tabelle ansehen
3. Sortierung zeigt beliebteste Shortcodes

### Beispiel 2: Wie viele Besucher waren anonym?
1. Übersichtskarte "Anonyme Aufrufe" betrachten
2. Vergleich mit "Authentifizierte Aufrufe"

### Beispiel 3: Export für externe Analyse
1. Filter setzen (z.B. nur "abschuss_summary_table")
2. Zeitraum wählen
3. "Als CSV exportieren" klicken
4. CSV in Excel/Google Sheets öffnen

### Beispiel 4: DSGVO-konforme Konfiguration
1. Admin → Seitenaufrufe
2. Einstellungen scrollen
3. "IP-Adressen anonymisieren" aktivieren
4. "Automatische Bereinigung" auf 30 Tage setzen
5. Einstellungen speichern

## Fehlerbehebung

### Tabelle nicht erstellt
**Problem:** Datenbanktabelle fehlt
**Lösung:** Plugin deaktivieren und wieder aktivieren

### Keine Logs erscheinen
**Problem:** Shortcodes werden aufgerufen, aber nichts wird geloggt
**Lösung:**
1. Prüfen ob Plugin korrekt geladen ist
2. PHP-Fehlerlog überprüfen
3. Datenbankberechtigungen prüfen

### Cron läuft nicht
**Problem:** Auto-Cleanup funktioniert nicht
**Lösung:**
1. WordPress Cron überprüfen: `wp cron event list`
2. WP-Cron manuell triggern: `wp cron event run ahgmh_page_views_cleanup_hook`

## Changelog

### Version 2.5.3
- ✅ Page View Tracking System implementiert
- ✅ Neue Datenbanktabelle `wp_ahgmh_page_views`
- ✅ Admin-Interface mit Statistiken und Diagrammen
- ✅ DSGVO-konforme IP-Anonymisierung
- ✅ Automatische Bereinigung alter Logs
- ✅ CSV-Export-Funktion
- ✅ Integration in alle wichtigen Shortcodes

## Support

Bei Fragen oder Problemen:
1. Plugin-Dokumentation lesen
2. WordPress Debug-Log aktivieren und prüfen
3. GitHub Issues öffnen: https://github.com/foe05/pr25_one/issues
