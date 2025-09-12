# Anforderungsdokumentation: Abschussplan HGMH WordPress Plugin

**Version:** 2.4.0 - Vollständige Implementierung  
**Status:** ✅ Alle Anforderungen implementiert und getestet  
**WordPress.org:** Submission-ready  

## 1. Überblick

Das **Abschussplan HGMH** Plugin ist eine spezialisierte WordPress-Erweiterung für die Verwaltung von Jagdabschussmeldungen in deutschen Hegegemeinschaften. Das Plugin ermöglicht es Jägern, ihre Abschüsse digital zu melden und Administratoren, diese zu verwalten und auszuwerten.

### 1.1 Zweck
- Digitale Erfassung von Jagdabschüssen
- Verwaltung von Abschusslimits (Soll-Werte)
- Überwachung und Auswertung der Abschussstatistiken
- Compliance mit behördlichen Meldepflichten

### 1.2 Zielgruppe
- **Jäger**: Melden ihre Abschüsse über Webformulare
- **Obleute**: Verwalten meldegruppen-spezifische Daten für ihre Wildarten
- **Vorstand**: Vollzugriff auf Administration, Konfiguration und Berichte
- **Behörden**: Erhalten strukturierte Abschussberichte

## 2. Benutzerrollen und Berechtigungen ✅ **IMPLEMENTIERT**

### 2.1 Besucher (Nicht angemeldete Benutzer) ✅ **IMPLEMENTIERT**
- **Berechtigung**: Öffentlicher Zugriff auf `[abschuss_summary]`
- **Zugriff**: Nur Statistiken und Übersichten
- **Einschränkungen**: Alle anderen Funktionen erfordern Login

### 2.2 Obmann (WordPress Users mit Meldegruppen-Zuweisung) ✅ **IMPLEMENTIERT**
- **Berechtigung**: Wildart-spezifische Meldegruppen-Zuweisung
- **Zugriff**: Form-Zugang mit Meldegruppen-Vorauswahl für zugewiesene Wildarten
- **User Meta**: `ahgmh_assigned_meldegruppe_{wildart}` System implementiert
- **Datenfilterung**: Automatische Beschränkung auf zugewiesene Meldegruppen

### 2.3 Vorstand (WordPress Administratoren) ✅ **IMPLEMENTIERT**
- **Berechtigung**: Vollzugriff auf alle Funktionen
- **Capability**: WordPress `manage_options` erforderlich
- **Admin Interface**: Master-Detail Wildart-Konfiguration verfügbar
- **User Management**: Obmann-Zuweisungen verwalten

## 3. Kernanforderungen ✅ **VOLLSTÄNDIG IMPLEMENTIERT**

### 3.1 Wildartenverwaltung ✅ **IMPLEMENTIERT**
- **Master-Detail UI**: Moderne Wildarten-Konfiguration mit Left-Sidebar Navigation
- **CRUD Operations**: Vollständiges Erstellen, Bearbeiten, Löschen von Wildarten
- **Persistenz**: Wildarten-Synchronisation über alle Systemteile
- **Real-time Updates**: AJAX-basierte Konfiguration ohne Page-Reload

### 3.2 Kategorienverwaltung ✅ **IMPLEMENTIERT**  
- **Wildart-spezifische Kategorien**: Individuelle Kategorien pro Wildart
- **Inline Editing**: Direkte Bearbeitung in Master-Detail Interface
- **Auto-Save**: Automatisches Speichern von Konfigurationsänderungen
- **Integration**: Kategorien verfügbar in allen Frontend-Formularen

### 3.3 Flexibles Limits-Management ✅ **ERWEITERT IMPLEMENTIERT**
- **Dual-Mode System**: Meldegruppen-spezifische vs. Hegegemeinschaft-Total Limits
- **Mode A**: Fine-grained Limits pro Meldegruppe und Kategorie
- **Mode B**: Vereinfachte Total-Limits pro Kategorie für gesamte Hegegemeinschaft
- **Status-Badges**: Real-time Anzeige (🟢🟡🔴🔥) basierend auf IST vs. SOLL
- **Live-Berechnung**: Automatische Limit-Überwachung bei Meldungserfassung

## 4. Frontend-Funktionalität (Shortcodes)

### 4.1 Abschussformular `[abschuss_form]`
```
[abschuss_form species="Rotwild"]
```

**Parameter:**
- `species`: Wildart (Pflicht)

**Funktionalität:**
- Authentifizierung erforderlich
- Felder:
  - **Abschussdatum**: Datumswähler (nicht in der Zukunft)
  - **Abschuss**: Dropdown mit verfügbaren Kategorien
  - **WUS**: Numerisches Feld (optional)
  - **Bemerkung**: Textfeld (optional)
- **Validierung**:
  - Limitprüfung mit Überschreitungslogik
  - Datumsvalidierung
  - AJAX-basierte Echtzeitvalidierung
- **Verhalten**: Kategorien werden automatisch deaktiviert wenn Limit erreicht und Überschreitung nicht erlaubt

### 4.2 Abschusstabelle `[abschuss_table]`
```
[abschuss_table species="Rotwild" limit="10" page="1"]
```

**Parameter:**
- `species`: Wildart (optional, zeigt alle wenn leer)
- `limit`: Anzahl Einträge pro Seite (Standard: 10)
- `page`: Seitennummer (Standard: 1)

**Funktionalität:**
- Paginierte Anzeige aller Abschussmeldungen
- Filterung nach Wildart
- Sortierung nach Datum (neueste zuerst)
- Responsive Tabellendarstellung

### 4.3 Zusammenfassung `[abschuss_summary]`
```
[abschuss_summary species="Rotwild"]
```

**Parameter:**
- `species`: Wildart (Pflicht)

**Funktionalität:**
- Übersichtstabelle: Kategorie, Ist-Werte, Soll-Werte, Status
- Prozentuale Auslastung der Limits
- Farbkodierte Statusanzeige:
  - Grün: < 90% des Limits
  - Gelb: 90-99% des Limits
  - Rot: ≥ 100% des Limits

### 4.4 Limitkonfiguration `[abschuss_limits]`
```
[abschuss_limits species="Rotwild"]
```

**Parameter:**
- `species`: Wildart (Pflicht)

**Funktionalität:**
- **Administrationstool** (nur für Benutzer mit `manage_options`)
- Konfiguration von Soll-Werten pro Kategorie
- "Überschießen möglich?" Checkbox pro Kategorie
- AJAX-basiertes Speichern
- Echtzeit-Statusanzeige der aktuellen Auslastung

## 5. Backend-Administration

### 5.1 Hauptnavigation
Das Plugin fügt ein Hauptmenü "Abschussplan" mit folgenden Unterseiten hinzu:

### 5.2 Übersicht
- **Wildartauswahl**: Dropdown zur Auswahl der anzuzeigenden Wildart
- **Zusammenfassungsstatistiken**: Gesamtanzahl Meldungen, aktuelle Zählstände
- **Kategorienübersicht**: Tabelle aller Kategorien mit:
  - Aktueller Zählerstand (Ist)
  - Konfiguriertes Limit (Soll)
  - Überschreitungsstatus
  - Prozentuale Auslastung
  - Farbkodierte Statusbadges

### 5.3 Abschussplanung
- **Wildartspezifische Konfiguration**: Dropdown zur Wildartauswahl
- **Limitverwaltung**: 
  - Numerische Eingabe für Soll-Werte pro Kategorie
  - Checkbox "Überschießen möglich?" pro Kategorie
  - Live-Vorschau der aktuellen Auslastung
- **Speicherfunktion**: AJAX-basiert mit Erfolgsmeldungen
- **Validierung**: Numerische Limits, Sicherheitschecks

### 5.4 Wildarten
- **CRUD-Operationen**: Erstellen, Lesen, Bearbeiten, Löschen von Wildarten
- **Dynamische Liste**: Eingabefelder mit Hinzufügen/Entfernen-Buttons
- **Persistierung**: Änderungen werden sofort in allen anderen Bereichen übernommen

### 5.5 Kategorien
- **CRUD-Operationen**: Erstellen, Lesen, Bearbeiten, Löschen von Kategorien
- **Dynamische Liste**: Eingabefelder mit Hinzufügen/Entfernen-Buttons
- **Globale Verfügbarkeit**: Kategorien stehen für alle Wildarten zur Verfügung

### 5.6 Datenbankeinstellungen
- **Multi-Datenbank-Unterstützung**:
  - SQLite (Standard)
  - MySQL
  - PostgreSQL
- **Konfigurationsoptionen**:
  - Datenbanktyp-Auswahl
  - Verbindungsparameter (Host, Port, Benutzername, Passwort)
  - Dateiname für SQLite
- **Verbindungstest**: Test-Button zur Validierung der Datenbankverbindung

## 6. Datenmodell

### 6.1 Abschussmeldungen
```sql
- ID: Eindeutige Kennung
- user_id: WordPress Benutzer-ID
- game_species: Wildart
- field1: Abschussdatum (DATUM)
- field2: Kategorie (TEXT)
- field3: WUS (INTEGER, optional)
- field4: Bemerkung (TEXT, optional)
- created_at: Erstellungszeitpunkt
```

### 6.2 WordPress Options
- `ahgmh_species`: Array der verfügbaren Wildarten
- `ahgmh_categories`: Array der verfügbaren Kategorien
- `abschuss_category_limits_{species}`: Limits pro Wildart
- `abschuss_category_allow_exceeding_{species}`: Überschreitungseinstellungen pro Wildart
- `abschuss_db_config`: Datenbankkonfiguration

## 7. Technische Anforderungen

### 7.1 WordPress-Anforderungen
- **WordPress Version**: 5.0 oder höher
- **PHP Version**: 7.4 oder höher
- **MySQL Version**: 5.6 oder höher (falls MySQL verwendet)

### 7.2 Abhängigkeiten
- **jQuery**: Für Frontend-Interaktivität
- **jQuery UI**: Für Datepicker-Funktionalität
- **Bootstrap CSS**: Für responsive Layouts (optional)

### 7.3 Datenbankunterstützung
- **SQLite**: Standard, keine zusätzliche Konfiguration erforderlich
- **MySQL**: WordPress-Standard-Datenbank
- **PostgreSQL**: Erweiterte Option für große Installationen

## 8. Sicherheitsanforderungen

### 8.1 Authentifizierung
- WordPress-native Authentifizierung erforderlich
- Capability-basierte Zugriffskontrollen (`manage_options` für Administration)

### 8.2 Datenvalidierung
- **Nonce-Verifikation**: Alle AJAX-Calls und Formulare
- **Sanitization**: Alle Benutzereingaben werden bereinigt
- **Validation**: Datentyp- und Wertebereichsprüfungen

### 8.3 SQL-Injection-Schutz
- Prepared Statements für alle Datenbankoperationen
- WordPress Database Abstraction Layer

### 8.4 Cross-Site-Scripting (XSS) Schutz
- `esc_html()`, `esc_attr()` für alle Ausgaben
- Validierung von HTML-Inhalten

## 9. Benutzerfreundlichkeit

### 9.1 Responsive Design
- Mobile-optimierte Formulare und Tabellen
- Touch-freundliche Bedienelemente
- Adaptive Layouts für verschiedene Bildschirmgrößen

### 9.2 Internationalisierung
- Deutsche Übersetzungen für alle Texte
- Verwendung von WordPress `__()` Funktionen
- Textdomain: `abschussplan-hgmh`

### 9.3 Barrierefreiheit
- Semantic HTML-Struktur
- Tastaturnavigation möglich
- Screen-Reader-kompatible Labels

## 10. Performance-Anforderungen

### 10.1 Ladezeiten
- AJAX-basierte Interaktionen für schnelle Responsivität
- Paginierung für große Datenmengen
- Optimierte Datenbankabfragen

### 10.2 Skalierbarkeit
- Unterstützung für mehrere tausend Abschussmeldungen
- Effiziente Indexierung der Datenbank
- Caching-freundliche Implementierung

## 11. Wartung und Support

### 11.1 Logging
- Detaillierte Fehlerprotokollierung
- Debug-Modi für Entwicklung
- Console-Logging für AJAX-Operationen

### 11.2 Backup-Kompatibilität
- Unterstützung für WordPress-Backup-Plugins
- Exportierbare Konfiguration
- Datenbank-agnostische Datenstruktur

### 11.3 Update-Sicherheit
- Rückwärtskompatible Datenstrukturen
- Migrationsroutinen für Datenbankänderungen
- Konfigurationssicherung bei Updates

## 12. Compliance und Rechtskonformität

### 12.1 Datenschutz (DSGVO)
- Minimale Datenerfassung
- Benutzer-ID-Verknüpfung zu WordPress-Konten
- Löschbarkeit von Benutzerdaten

### 12.2 Jagdrechtliche Anforderungen
- Vollständige Erfassung aller relevanten Abschussdaten
- Nachvollziehbare Dokumentation
- Exportierbare Berichte für Behörden

### 12.3 Audit-Trail
- Zeitstempel für alle Änderungen
- Benutzer-ID-Verknüpfung für Nachverfolgbarkeit
- Unveränderlichkeit gespeicherter Meldungen

---

**Version:** 1.5.0  
**Erstellt:** 2025  
**Zielgruppe:** Entwickler, Systemadministratoren, Jagdrevierverwalter  
**Status:** Vollständig implementiert mit CSV Export