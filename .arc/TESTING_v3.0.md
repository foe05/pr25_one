# Testing Plan v3.0.0 - Abschussplan HGMH

**Version:** 3.0.0
**Testing Date:** 2026-01-13
**Tester:** Johannes

---

## 🎯 Test-Strategie

### Phase 1: Grundfunktionen (Basis-Tests)
**Ziel:** Sicherstellen, dass das Plugin überhaupt aktiviert werden kann

- [y] Plugin Aktivierung
  - [y] Plugin aktivieren ohne Fehler
  - [y] Keine PHP Fatal Errors im error_log
  - [y] Admin-Menü erscheint
  - [y] Feature Flags Seite erreichbar

### Phase 2: Datenbank & Migration
**Ziel:** Neue Tabellen und Daten-Migration prüfen

- [y] Datenbank-Tabellen
  - [n] `wp_ahgmh_submissions` existiert mit neuen Feldern
  - [n] `wp_ahgmh_moderation_history` wurde erstellt
  - [n] `wp_ahgmh_email_log` wurde erstellt
  - [n] `wp_ahgmh_activity_log` wurde erstellt
  - [n] Alte Daten wurden migriert (wenn vorhanden)

- [y] Migration Manager
  - [y] Admin → Migrationen Seite erreichbar
  - [y] Migration Status wird angezeigt
  - [n] Keine Fehler in Migration-Logs

### Phase 3: Shortcodes (Frontend)
**Ziel:** Alle Shortcodes testen

- [y] [abschuss_form]
  - [y] Formular wird angezeigt
  - [y] Submission funktioniert
  - [y] AJAX-Request erfolgreich
  - [y] Keine JavaScript-Fehler in Browser-Console

- [y] [abschuss_table]
  - [y] Tabelle wird angezeigt
  - [y] Daten werden geladen
  - [y] Sortierung funktioniert
  - [y] Filter funktionieren

- [y] [abschuss_table] mit Moderation (NEU v3.0)
  - [y] Moderation-Buttons erscheinen (für Admins/Obmänner)
  - [y] Approve-Button funktioniert
  - [y] Edit-Button öffnet Modal
  - [y] Reject-Button öffnet Modal
  - [y] AJAX-Requests erfolgreich

- [y] [abschuss_summary]
  - [y] Zusammenfassung wird angezeigt
  - [n] Statistiken korrekt

- [n] [abschuss_public_form] (NEU v3.0)
  - [n] Formular wird angezeigt
  - [nv] Submission ohne Login möglich
  - [nv] Email-Verifizierung wird gesendet
  - [nv] Verification-Link funktioniert

### Phase 4: Admin-Interface
**Ziel:** Admin-Funktionen testen

- [y] Feature Flags (NEU v3.0)
  - [y] Seite erreichbar
  - [y] Flags können umgeschaltet werden
  - [y] Änderungen werden gespeichert

- [y] Wildarten Management
  - [y] Wildarten-Seite erreichbar
  - [n] CRUD-Operationen funktionieren
  - [NV] Repository-basiert (keine direkten DB-Queries)

- [y] Migrationen (NEU v3.0)
  - [y] Migrations-Seite erreichbar
  - [y] Migration-Status wird angezeigt

### Phase 5: Service Layer (NEU v3.0)
**Ziel:** Neue Services testen

- [y] Moderation Service
  - [y] approve() Methode funktioniert
  - [y] reject() Methode funktioniert
  - [NV] edit() Methode funktioniert
  - [NV] History wird protokolliert

- [y] Email Service
  - [y] Emails werden versendet
  - [y] Templates werden korrekt geladen
  - [NV] Email-Log wird geschrieben

- [N] Activity Logger
  - [NV] Aktivitäten werden geloggt
  - [NV] Logs können abgerufen werden
  - [NV] Cleanup funktioniert

### Phase 6: Security & Permissions
**Ziel:** Berechtigungen prüfen

- [Y] Permission System
  - [y] Besucher: Nur Summary sichtbar
  - [NV] Obmann: Meldegruppen-spezifischer Zugriff
  - [y] Vorstand: Vollzugriff
