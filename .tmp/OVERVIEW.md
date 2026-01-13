# 📊 HGMH v2 Refactoring - Spec Overview

**Projekt:** WordPress Plugin für Hegegemeinschaftsmanagement
**Status:** Bereit für Auto-Claude Execution
**Gesamt:** 10 Specs, 52 Story Points, ~4 Wochen

---

## 🎯 Quick Stats

| Kategorie | Anzahl | Story Points |
|-----------|--------|--------------|
| **Critical** | 8 Specs | 44 Points |
| **Must-Have** | 1 Spec | 5 Points |
| **Should-Have** | 1 Spec | 3 Points |
| **GESAMT** | **10 Specs** | **52 Points** |

---

## 📋 Alle Specs im Überblick

### 001 - Feature Flags System
**Story:** Als Entwickler möchte ich Features schrittweise aktivieren  
**Points:** 3 (S)  
**Priority:** 🔴 Critical  
**Dependencies:** Keine  
**Files:** `includes/class-feature-flags.php`, `admin/feature-flags.php`  
**Delivers:** Sicheres Rollout-System für neue Features

---

### 002 - Migration Manager & Schema
**Story:** Als Entwickler möchte ich DB-Änderungen versioniert ausführen  
**Points:** 5 (M)  
**Priority:** 🔴 Critical  
**Dependencies:** 001  
**Files:** `includes/class-migration-manager.php`, `migrations/001_create_new_schema.php`  
**Delivers:** 6 neue Tabellen mit semantischen Spalten

---

### 003 - Daten-Migration v1 → v2
**Story:** Als Entwickler möchte ich alte Daten ins neue Schema migrieren  
**Points:** 8 (L)  
**Priority:** 🔴 Critical  
**Dependencies:** 002  
**Files:** `migrations/002_migrate_existing_data.php`  
**Delivers:** Alle alten Submissions in neuer Struktur

---

### 004 - Submission Repository
**Story:** Als Entwickler möchte ich saubere DB-Abstraktion  
**Points:** 5 (M)  
**Priority:** 🔴 Critical  
**Dependencies:** 002, 003  
**Files:** `includes/repositories/class-submission-repository.php`  
**Delivers:** Repository Pattern für Data Access

---

### 005 - Moderation Service
**Story:** Als Obmann möchte ich Meldungen approve/reject/edit  
**Points:** 5 (M)  
**Priority:** 🔴 Critical  
**Dependencies:** 004, 008  
**Files:** `includes/services/class-moderation-service.php`  
**Delivers:** Zentralisierte Moderation Business-Logic

---

### 006 - Table Moderation
**Story:** Als Obmann möchte ich direkt in Tabelle moderieren  
**Points:** 8 (L)  
**Priority:** 🔴 Critical  
**Dependencies:** 005  
**Files:** `frontend/shortcodes/class-table-shortcode.php`, `frontend/templates/table.php`, `frontend/assets/js/table-moderation.js`  
**Delivers:** Moderation-Buttons in [abschuss_table]

---

### 007 - Öffentliches Formular
**Story:** Als anonymer Jäger möchte ich ohne Login melden  
**Points:** 5 (M)  
**Priority:** 🔴 Critical  
**Dependencies:** 004, 008  
**Files:** `frontend/shortcodes/class-public-form-shortcode.php`, `includes/services/class-verification-service.php`  
**Delivers:** Public Form mit Email-Verifizierung

---

### 008 - Email Service
**Story:** Als System möchte ich zentrales Email-System  
**Points:** 5 (M)  
**Priority:** 🔴 Critical  
**Dependencies:** Keine  
**Files:** `includes/services/class-email-service.php`, `frontend/templates/email/*.php`  
**Delivers:** Einheitliche Email-Benachrichtigungen

---

### 009 - Activity Logging
**Story:** Als Vorstand möchte ich Nutzungs-Statistiken  
**Points:** 3 (S)  
**Priority:** 🟡 Should  
**Dependencies:** 002  
**Files:** `includes/services/class-activity-logger.php`  
**Delivers:** User Activity Tracking für Analytics

---

### 010 - Wildarten/Meldegruppen Admin
**Story:** Als Vorstand möchte ich Wildarten verwalten  
**Points:** 5 (M)  
**Priority:** 🟡 Must  
**Dependencies:** 002  
**Files:** `admin/wildarten/index.php`, `includes/repositories/class-wildart-repository.php`, `includes/repositories/class-meldegruppe-repository.php`  
**Delivers:** CRUD-UI für Wildarten & Meldegruppen

---

## 🔄 Execution Flow

```
START
  ↓
[001] Feature Flags ────────────────────┐
  ↓                                     │
[002] Migration Manager & Schema        │
  ↓                                     │
[003] Data Migration                    │
  ↓                                     │
[004] Submission Repository             │
  ↓                     ↓                │
[008] Email Service  [005] Moderation   │
  ↓        ↘            ↓                │
[007] Public Form   [006] Table Mod     │
  ↓                     ↓                │
[009] Activity Log  [010] Admin UI      │
  ↓                     ↓                │
  └─────────────────────┴────────────> DONE
```

---

## 📊 Velocity-Planung

### **Sprint 1 (Woche 1): Foundation**
- 001: Feature Flags (3)
- 002: Schema (5)
- 003: Migration (8)
**→ 16 Points**

### **Sprint 2 (Woche 2): Core Logic**
- 008: Email (5)
- 004: Repository (5)
- 005: Moderation (5)
**→ 15 Points**

### **Sprint 3 (Woche 3): Features**
- 006: Table Moderation (8)
- 007: Public Form (5)
- 009: Activity Log (3)
**→ 16 Points**

### **Sprint 4 (Woche 4): Admin UI**
- 010: Wildarten Admin (5)
**→ 5 Points**

**Average Velocity:** 13 Points/Woche

---

## 🎯 Definition of Done

### **Spec ist DONE wenn:**
- [ ] All Acceptance Criteria ✅
- [ ] Code passes WordPress Coding Standards
- [ ] Manual testing completed
- [ ] No regressions in existing features
- [ ] Git Worktree merged to main
- [ ] Documentation updated

### **Phase ist DONE wenn:**
- [ ] All Specs in Phase = DONE
- [ ] Integration testing passed
- [ ] Feature Flag enabled successfully
- [ ] Stakeholder demo completed

---

## 🚦 Risk Management

### **High Risk Specs:**
- **003 (Migration):** Datenverlust-Risiko
  - **Mitigation:** Backup vor Migration, Validation-Scripts
- **006 (Table Mod):** Complex UI/UX
  - **Mitigation:** Prototyping, User Testing

### **Dependencies Bottlenecks:**
- **002 → 003 → 004:** Sequential, kein Parallel
  - **Mitigation:** Starte früh, dedizierte Focus-Time
- **008 (Email):** Blocker für 005 & 007
  - **Mitigation:** Priorisiere 008 early in Sprint 2

---

## 📈 Progress Tracking

```
[001] ▓▓▓▓▓▓▓▓▓▓ 100% ✅
[002] ▓▓▓▓▓▓▓▓▓▓ 100% ✅
[003] ▓▓▓▓▓▓░░░░  60% 🔄
[004] ░░░░░░░░░░   0% ⏳
[005] ░░░░░░░░░░   0% ⏳
[006] ░░░░░░░░░░   0% ⏳
[007] ░░░░░░░░░░   0% ⏳
[008] ░░░░░░░░░░   0% ⏳
[009] ░░░░░░░░░░   0% ⏳
[010] ░░░░░░░░░░   0% ⏳

Completion: 26/52 Points (50%)
```

---

## 🎓 Lessons Learned (Template)

Nach jedem Sprint:
- **What went well?**
- **What could be improved?**
- **Action items for next sprint?**

---

**Last Updated:** 2026-01-12  
**Version:** 1.0  
**Maintained by:** Auto-Claude AI + Johannes (Förster & Developer)
