# Konzept: Backend-Refactoring & Optimierung "Abschussplan HGMH"

## 1. Ausgangslage & Ziele
Das Plugin leidet unter "Wachstumsschmerzen": Die Admin-Oberfläche basiert auf Frontend-Technologie (Shortcodes), die Datenstruktur enthält Redundanzen, und Kernfunktionen wie die Obmann-Zuweisung sind fehleranfällig. 
Ziel ist ein **professionelles, natives WordPress-Backend**, das stabil, performant und intuitiv ist.

---

## 2. Neues natives Admin-Backend
Wir verabschieden uns vom `[abschuss_admin]` Shortcode als primäres Verwaltungstool und nutzen die WordPress Admin API.

### 2.1 Menü-Struktur (Linke Sidebar)
Statt Tabs auf einer Seite nutzen wir WordPress-Submenüs:

*   **Abschussplan** (Hauptmenü) -> *Dashboard & Übersicht*
*   **Meldungen** -> *Tabelle aller Abschüsse (filterbar, exportierbar)*
*   **Stammdaten** -> *Jagdbezirke & Meldegruppen verwalten*
*   **Obleute** -> *Benutzer-Zuweisung (Bugfix hier!)*
*   **Konfiguration** -> *Wildarten, Kategorien & Limits*

### 2.2 Dashboard Widget (Status-Ampel)
Ein Widget auf dem Haupt-Dashboard (`wp-admin/index.php`), das sofortige Übersicht bietet:
*   **Titel**: "Abschussplan Status"
*   **Inhalt**: 
    1.  **Warn-Liste**: Zeigt nur Wildarten/Kategorien an, die >90% (Rot) oder >100% (Feuer) erfüllt sind.
    2.  **Letzte Meldungen**: Die 3 aktuellsten Einträge (Datum, Revier, Wildart, Klasse) als Miniliste.
*   **Nutzen**: Der Vorstand sieht sofort Handlungsbedarf ohne Klicks.

---

## 3. Bugfix: Obmann-Zuordnung
**Problem**: Doppelte Einträge und unzuverlässige Speicherung.
**Ursache**: Historisch gewachsene Meta-Keys (`ahgmh_assigned_meldegruppe_Rotwild` vs. `..._rotwild`) und inkonsistente Lösch-Logik.

**Lösung**:
1.  **Standardisierung**: Wir definieren *einen* strikten Key-Standard: `ahgmh_assignment_{wildart_slug}` (alles lowercase).
2.  **Migration**: Einmaliges Script bereinigt alle alten Keys bei Plugin-Update.
3.  **Logik-Härtung**: Die Zuweisungs-Funktion wird umgeschrieben:
    *   *Vorher*: Lösche ALLE Varianten des Keys für diesen User.
    *   *Dann*: Setze den neuen standardisierten Key.
    *   *Unique*: Sicherstellen, dass pro Wildart nur *ein* Eintrag existiert.

---

## 4. Bereinigung Datenmodell (Legacy)
**Problem**: "Doppelte Spalten" / Redundanz.
**Analyse**: 
*   In `wp_ahgmh_submissions` wird der Jagdbezirk-Name (`field5`) gespeichert.
*   In `wp_ahgmh_meldegruppen_config` gibt es eine Spalte `jagdbezirke` (Text), die redundant zur Tabelle `wp_ahgmh_jagdbezirke` ist.

**Lösung**:
1.  **Entfernen**: Die Spalte `jagdbezirke` in `wp_ahgmh_meldegruppen_config` wird als "deprecated" markiert und nicht mehr befüllt/gelesen. Die "Wahrheit" liegt nur noch in der Tabelle `wp_ahgmh_jagdbezirke`.
2.  **Normalisierung (Perspektivisch)**: Langfristig sollten wir in der `submissions` Tabelle die `jagdbezirk_id` speichern statt dem Namen. Für Phase 1 belassen wir es beim Namen, fixen aber die Referenztabellen.

---

## 5. Umsetzungsplan

### Phase 1: Fundament & Fixes (Sofort)
1.  **Obmann-Fix**: `AHGMH_Permissions_Service` überarbeiten & Bereinigungs-Script.
2.  **Admin-Controller**: Neue Klasse `AHGMH_Admin_Controller` für natives Menü.
3.  **Dashboard-Widget**: Registrierung des WP-Widgets.

### Phase 2: Migration UI (Nächste Schritte)
1.  Portierung der Tabellen-Views in native WP-List-Tables.
2.  Portierung der Konfigurations-Formulare.

---

## Rückmeldung zur Frage 1 (Datenmodell)
*   **Aktuell gefüllt**: Wenn man im Formular wählt, wird `field5` (Text) in `submissions` gefüllt.
*   **Legacy**: Die Spalte `jagdbezirke` in `wp_ahgmh_meldegruppen_config` ist das Überbleibsel. Wir werden diese ignorieren und uns auf `wp_ahgmh_jagdbezirke` verlassen.
