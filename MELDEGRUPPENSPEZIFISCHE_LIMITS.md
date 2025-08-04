# Meldegruppenspezifische Abschuss-Limits - Feature Dokumentation

## Überblick

Das Abschussplan HGMH Plugin wurde um **meldegruppenspezifische Abschuss-Limits** erweitert. Diese Funktionalität ermöglicht es, für verschiedene Meldegruppen innerhalb einer Wildart unterschiedliche oder gemeinsame Limits zu definieren.

## Funktionsweise

### Flexible Limits-Struktur:

1. **Species-Default-Limits** (Fallback)
   - Standard-Limits pro Wildart
   - Werden verwendet für Meldegruppen ohne eigene Limits
   - Zentrale Konfiguration im Admin-Interface

2. **Meldegruppen-spezifische Limits** (Optional)
   - Pro Meldegruppe individuell aktivierbar
   - Checkbox "Eigene Limits verwenden"
   - Überschreibt Species-Default-Limits bei Aktivierung

3. **Intelligenter Fallback-Mechanismus**
   - Automatische Verwendung der Standard-Limits wenn keine meldegruppenspezifischen vorhanden
   - Keine Dateninkonsistenzen durch fehlende Limits

## Admin-Interface

### Erweiterte Meldegruppen-Konfiguration
- **Navigation:** Einstellungen → Meldegruppen → Meldegruppenspezifische Abschuss-Limits
- **Species-Dropdown:** Auswahl der Wildart für Limits-Konfiguration
- **Species-Default-Limits:** Zentrale Standard-Limits als Fallback
- **Pro-Meldegruppe-Checkboxes:** "Eigene Limits verwenden"
- **Conditional Inputs:** Limits-Felder nur bei aktivierter Checkbox sichtbar

### Benutzerfreundlichkeit
- **Dynamische UI:** JavaScript-gesteuerte Ein-/Ausblendung
- **AJAX-basiert:** Sofortige Speicherung ohne Seitenreload
- **Visual Feedback:** Ladeindikatoren und Erfolgsmeldungen

## Technische Implementierung

### Datenbank-Schema

#### Neue Tabelle: `wp_ahgmh_meldegruppen_limits`
```sql
CREATE TABLE wp_ahgmh_meldegruppen_limits (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    species varchar(50) NOT NULL,
    meldegruppe varchar(100) NULL,              -- NULL = Species-Default
    category varchar(100) NOT NULL,
    max_count int(11) NOT NULL DEFAULT 0,
    allow_exceeding tinyint(1) NOT NULL DEFAULT 0,
    has_custom_limits tinyint(1) NOT NULL DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_species_meldegruppe_category (species, meldegruppe, category),
    KEY species_idx (species),
    KEY meldegruppe_idx (meldegruppe),
    KEY category_idx (category)
);
```

#### Beispiel-Daten
```sql
-- Species-Default für Rotwild
INSERT INTO wp_ahgmh_meldegruppen_limits 
(species, meldegruppe, category, max_count, allow_exceeding, has_custom_limits)
VALUES 
('Rotwild', NULL, 'Wildkalb', 60, 0, 0),
('Rotwild', NULL, 'Schmaltier', 35, 0, 0);

-- Meldegruppen-spezifisch für Gruppe_A
INSERT INTO wp_ahgmh_meldegruppen_limits 
(species, meldegruppe, category, max_count, allow_exceeding, has_custom_limits)
VALUES 
('Rotwild', 'Gruppe_A', 'Wildkalb', 50, 0, 1),
('Rotwild', 'Gruppe_A', 'Schmaltier', 30, 1, 1);
```

### Code-Erweiterungen

#### Datenbank-Handler (`class-database-handler.php`)
- `create_meldegruppen_limits_table()`: Erstellt Limits-Tabelle
- `meldegruppe_has_custom_limits()`: Prüft ob Meldegruppe eigene Limits hat
- `get_meldegruppen_limits()`: Holt Limits für Meldegruppe oder Species-Default
- `save_meldegruppen_limits()`: Speichert Limits-Konfiguration
- `get_applicable_limits()`: **Zentrale Fallback-Logik** für Limits-Ermittlung

#### Form-Handler (`class-form-handler.php`)
- `render_limits_config()`: Erweitert um meldegruppe-Parameter
- `get_meldegruppen_category_limits()`: Wrapper für anwendbare Limits
- **Shortcode-Erweiterung:** `[abschuss_limits meldegruppe="Gruppe_A"]`

#### Admin-Interface (`class-admin-page-modern.php`)
- Erweiterte Meldegruppen-Tab mit Limits-Sektion
- AJAX-Handler für Limits-Verwaltung:
  - `ajax_load_meldegruppen_limits()`: Lädt Konfiguration für Wildart
  - `ajax_toggle_meldegruppe_custom_limits()`: Toggle-Funktion für Checkboxes
  - `ajax_save_meldegruppe_limits()`: Speichert meldegruppenspezifische Limits
  - `ajax_save_species_default_limits()`: Speichert Species-Default-Limits

#### JavaScript (`admin-modern.js`)
- `loadMeldegruppenLimitsConfig()`: Lädt Limits-Konfiguration per AJAX
- `renderSpeciesDefaultLimits()`: Rendert Species-Default-Formular
- `renderMeldegruppenLimits()`: Rendert meldegruppenspezifische Checkboxes und Formulare
- Event-Handler für Checkbox-Toggle und Formular-Submit

## Shortcode-Verwendung

### Erweiterte Funktionalität:
```php
// Species-Default-Limits verwenden
[abschuss_limits species="Rotwild"]

// Meldegruppen-spezifische Limits (falls konfiguriert)
[abschuss_limits species="Rotwild" meldegruppe="Gruppe_A"]

// Summary mit korrekten Limits
[abschuss_summary species="Rotwild" meldegruppe="Gruppe_A"]
```

### Intelligente Limits-Ermittlung:
```php
// Wenn Gruppe_A eigene Limits hat: verwendet meldegruppenspezifische Limits
// Wenn Gruppe_A keine eigenen Limits hat: verwendet Species-Default-Limits
$applicable_limits = $database->get_applicable_limits('Rotwild', 'Gruppe_A', 'Wildkalb');
```

## Status-Badge-Berechnung

### Korrekte Limits-Anwendung:
- **Summary-Tabelle:** Verwendet automatisch die korrekten Limits (Default oder meldegruppenspezifisch)
- **Prozentberechnung:** `(Ist-Abschuss / Anwendbares-Limit) * 100`
- **Status-Badges:** 🟢 (<90%) 🟡 (90-99%) 🔴 (≥100%) basierend auf korrekten Limits

### Fallback-Garantie:
- Keine Fehlermeldungen bei fehlenden meldegruppenspezifischen Limits
- Automatische Verwendung der Species-Default-Limits
- Konsistente Status-Anzeige in allen Shortcodes

## Konfigurationsbeispiele

### Szenario 1: Gemeinsame Limits
```
Rotwild:
├── Gruppe_A: ☐ Eigene Limits (verwendet Species-Default)
├── Gruppe_B: ☐ Eigene Limits (verwendet Species-Default)
└── Species-Default: Wildkalb=60, Schmaltier=35
```

### Szenario 2: Gemischte Limits
```
Rotwild:
├── Gruppe_A: ☑ Eigene Limits → Wildkalb=50, Schmaltier=30
├── Gruppe_B: ☐ Eigene Limits (verwendet Species-Default)
└── Species-Default: Wildkalb=60, Schmaltier=35
```

### Szenario 3: Vollständig getrennte Limits
```
Rotwild:
├── Gruppe_A: ☑ Eigene Limits → Wildkalb=45, Schmaltier=25
├── Gruppe_B: ☑ Eigene Limits → Wildkalb=75, Schmaltier=40
└── Species-Default: Wildkalb=60, Schmaltier=35 (nur für neue Meldegruppen)
```

## Migration & Rückwärtskompatibilität

### Automatische Migration:
- Plugin-Update erstellt neue Limits-Tabelle automatisch
- Bestehende Options-basierte Limits bleiben funktional
- Fallback auf bestehende `get_category_limits()` Methoden

### Rückwärtskompatibilität:
- Alle bestehenden Shortcodes funktionieren unverändert
- `[abschuss_limits species="Rotwild"]` verwendet weiterhin Options-basierte Limits wenn keine DB-Limits vorhanden
- `[abschuss_summary]` zeigt korrekte Status auch ohne meldegruppenspezifische Konfiguration

## Performance-Optimierung

### Datenbankindizes:
- `species_idx`: Schnelle Wildart-Abfragen
- `meldegruppe_idx`: Effiziente Meldegruppen-Filterung  
- `category_idx`: Optimierte Kategorie-Suche
- `unique_species_meldegruppe_category`: Verhindert Duplikate

### AJAX-Optimierung:
- Lazy Loading der Limits-Konfiguration
- Einzelne AJAX-Calls für spezifische Aktionen
- Minimaler Datentransfer durch gezielte Abfragen

## Testing

### Manuelle Tests empfohlen:
1. **Checkbox-Toggle:** Ein-/Ausschalten meldegruppenspezifischer Limits
2. **Limits-Speicherung:** Species-Default und meldegruppenspezifische Limits
3. **Fallback-Verhalten:** Korrekte Limits-Anwendung bei verschiedenen Konfigurationen
4. **Shortcode-Funktionalität:** `[abschuss_limits]` mit/ohne meldegruppe-Parameter
5. **Status-Badge-Korrektheit:** Summary-Anzeige mit korrekten Prozentberechnungen

### Akzeptanzkriterien ✅
- [x] Pro Meldegruppe Checkbox "Eigene Limits verwenden" verfügbar
- [x] Limits-Inputs erscheinen nur bei aktivierter Checkbox  
- [x] Standard-Limits für Wildart als Fallback funktional
- [x] `[abschuss_limits]` Shortcode unterstützt meldegruppe-Parameter
- [x] Status-Badges basieren auf korrekten anwendbaren Limits
- [x] Dynamisches Admin-Interface mit AJAX-Funktionalität
- [x] Fallback-Mechanismen garantieren keine Fehlermeldungen
- [x] Performance bleibt bei komplexen Limit-Strukturen akzeptabel

## Zukünftige Erweiterungen

### Mögliche Verbesserungen:
- **Bulk-Operations:** Limits für mehrere Meldegruppen gleichzeitig setzen
- **Import/Export:** Limits-Konfiguration als CSV/JSON
- **Limits-Vorlagen:** Wiederverwendbare Limit-Sets für neue Meldegruppen
- **Audit-Log:** Nachverfolgung von Limits-Änderungen
- **Dashboard-Widget:** Übersicht über Limits-Auslastung pro Meldegruppe

---

**Version:** 2.2.0+  
**Entwickler:** Basierend auf Abschussplan HGMH v2.0.0  
**Kompatibilität:** WordPress 5.0+, PHP 7.4+  
**Abhängigkeiten:** Wildartspezifische Meldegruppen-Feature (v2.1.0+)
