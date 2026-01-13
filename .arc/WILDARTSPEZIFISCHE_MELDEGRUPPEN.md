# Wildartspezifische Meldegruppen - Feature Dokumentation

## Überblick

Das Abschussplan HGMH Plugin wurde um die Funktionalität **wildartspezifischer Meldegruppen** erweitert. Diese Erweiterung ermöglicht es, für verschiedene Wildarten (Rotwild, Damwild, etc.) unterschiedliche Meldegruppen zu konfigurieren.

## Funktionsweise

### Zwei Modi verfügbar:

1. **Globaler Modus** (Standard)
   - Meldegruppen gelten für alle Wildarten gleichermaßen
   - Verwaltung über Jagdbezirke-Tab
   - Bestehende Funktionalität bleibt unverändert

2. **Wildartspezifischer Modus** (Neu)
   - Separate Meldegruppen pro Wildart konfigurierbar
   - Verwaltung über neuen "Meldegruppen"-Tab
   - Wildart-Dropdown zur Auswahl der zu konfigurierenden Wildart

## Admin-Interface

### Neuer "Meldegruppen"-Tab
- **Navigation:** Einstellungen → Meldegruppen
- **Checkbox:** "Wildartspezifische Meldegruppen verwenden"
- **Wildart-Dropdown:** Auswahl der zu konfigurierenden Wildart
- **Textarea:** Eingabe der Meldegruppen (eine pro Zeile)

### Sicherheitsmaßnahmen
- ⚠️ **Warnung:** Beim Wechsel zwischen den Modi werden alle Abschussmeldungen gelöscht
- **Bestätigung:** Nutzer muss explizit bestätigen
- **Berechtigung:** Nur Benutzer mit `manage_options` können Änderungen vornehmen

## Technische Implementierung

### Datenbank-Schema

#### Neue Tabelle: `wp_ahgmh_meldegruppen_config`
```sql
CREATE TABLE wp_ahgmh_meldegruppen_config (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    wildart varchar(50) NULL,
    meldegruppe varchar(100) NOT NULL,
    jagdbezirke text,
    is_wildart_specific tinyint(1) NOT NULL DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    KEY wildart_idx (wildart),
    KEY meldegruppe_idx (meldegruppe)
);
```

#### Settings-Option
- `ahgmh_use_wildart_specific_meldegruppen`: Boolean für Modus-Toggle

### Code-Erweiterungen

#### Datenbank-Handler (`class-database-handler.php`)
- `create_meldegruppen_config_table()`: Erstellt neue Tabelle
- `is_wildart_specific_enabled()`: Prüft aktiven Modus
- `get_meldegruppen_for_wildart($wildart)`: Holt wildartspezifische Meldegruppen
- `save_meldegruppen_config()`: Speichert Meldegruppen-Konfiguration
- `delete_all_submissions()`: Löscht alle Abschussmeldungen

#### Admin-Interface (`class-admin-page-modern.php`)
- Neuer "Meldegruppen"-Tab in Navigation
- `render_meldegruppen_settings()`: Rendert Konfigurationsoberfläche
- AJAX-Handler für dynamische Interaktionen:
  - `ajax_toggle_wildart_specific()`: Modus-Wechsel
  - `ajax_load_wildart_meldegruppen()`: Lädt Meldegruppen für Wildart
  - `ajax_save_wildart_meldegruppen()`: Speichert Meldegruppen

#### Frontend-Logic (`class-form-handler.php`)
- `get_available_meldegruppen($species)`: Erweitert um Species-Parameter
- Angepasste Shortcode-Logik für wildartspezifische Filterung

#### JavaScript (`admin-modern.js`)
- `initMeldegruppenConfig()`: Initialisiert Meldegruppen-Interface
- Dynamische UI-Updates bei Modus-Wechsel
- AJAX-basierte Formularbearbeitung

## Shortcode-Verwendung

### Bestehende Shortcodes bleiben kompatibel:
```php
[abschuss_summary species="Rotwild"]
[abschuss_summary species="Damwild"]
```

### Erweiterte Meldegruppen-Filterung:
```php
[abschuss_summary species="Rotwild" meldegruppe="Gruppe_A"]
[abschuss_summary meldegruppe="Gruppe_B"]
```

## Fallback-Verhalten

### Wildart ohne Meldegruppen
- Zeigt "Alle" als einzige Option im Dropdown
- Keine Filterung wird angewendet

### Modus-Wechsel
- **Global → Wildartspezifisch:** Alle Abschussmeldungen werden gelöscht
- **Wildartspezifisch → Global:** Rückkehr zur Jagdbezirke-basierten Verwaltung

## Migration & Rückwärtskompatibilität

### Automatische Migration
- Plugin-Aktivierung erstellt neue Tabelle automatisch
- Bestehende Daten bleiben im globalen Modus erhalten

### Rückwärtskompatibilität
- Alle bestehenden Shortcodes funktionieren unverändert
- API-Methoden erweitert aber nicht gebrochen
- Fallback auf globale Meldegruppen wenn wildartspezifisch nicht konfiguriert

## Testing

### Manuelle Tests empfohlen:
1. **Modus-Wechsel:** Global ↔ Wildartspezifisch
2. **Meldegruppen-Konfiguration:** Pro Wildart separat
3. **Shortcode-Funktionalität:** Mit beiden Modi
4. **Frontend-Filterung:** Dropdown-Verhalten
5. **Datenintegrität:** Nach Modus-Wechsel

### Akzeptanzkriterien ✅
- [x] Checkbox "Wildartspezifische Meldegruppen" verfügbar
- [x] Dynamisches Toggle zwischen Modi
- [x] Wildart-Dropdown lädt verfügbare Species
- [x] Meldegruppen-Konfiguration pro Wildart möglich
- [x] Automatische Löschung bei Modus-Wechsel
- [x] Frontend-Shortcodes respektieren Konfiguration
- [x] Bestehende Funktionalität bleibt im globalen Modus
- [x] Responsive Admin-Interface

## Zukünftige Erweiterungen

### Mögliche Verbesserungen:
- **Import/Export:** Meldegruppen-Konfiguration als JSON
- **Bulk-Operations:** Meldegruppen für mehrere Wildarten gleichzeitig
- **Validation:** Einschränkung auf gültige Meldegruppen-Namen
- **History:** Versionierung der Konfigurationsänderungen

---

**Version:** 2.1.0+  
**Entwickler:** Basierend auf Abschussplan HGMH v2.0.0  
**Kompatibilität:** WordPress 5.0+, PHP 7.4+  
