# 🎯 HGMH v2 - Auto-Claude Specs

**WordPress Plugin Refactoring: Hegegemeinschaftsmanagement**

Dieses Paket enthält **10 Auto-Claude Specs** für das komplette Refactoring des Plugins von Prototyp zu Production-Grade Software.

---

## 📦 Inhalt

### **Phase 1: Foundation (Specs 001-003)**
Grundlegende Infrastruktur, Datenbank-Migration

| Spec | Name | Story Points | Priorität | Abhängigkeiten |
|------|------|--------------|-----------|----------------|
| 001 | Feature Flags System | 3 (S) | 🔴 Critical | Keine |
| 002 | Migration Manager & Schema | 5 (M) | 🔴 Critical | 001 |
| 003 | Daten-Migration v1 → v2 | 8 (L) | 🔴 Critical | 002 |

**Nach Phase 1:**
- ✅ Neues Schema existiert
- ✅ Alle Daten migriert
- ✅ Feature Flags zum sicheren Umschalten

---

### **Phase 2: Architecture (Specs 004-005)**
Repository Pattern & Business Logic

| Spec | Name | Story Points | Priorität | Abhängigkeiten |
|------|------|--------------|-----------|----------------|
| 004 | Submission Repository | 5 (M) | 🔴 Critical | 002, 003 |
| 005 | Moderation Service | 5 (M) | 🔴 Critical | 004, 008 |

**Nach Phase 2:**
- ✅ Saubere Daten-Abstraktion
- ✅ Moderation Business-Logic zentral

---

### **Phase 3: Features (Specs 006-010)**
Neue Funktionen implementieren

| Spec | Name | Story Points | Priorität | Abhängigkeiten |
|------|------|--------------|-----------|----------------|
| 006 | Table Moderation (Approve/Edit/Reject) | 8 (L) | 🔴 Critical | 005 |
| 007 | Öffentliches Formular + Email-Verify | 5 (M) | 🔴 Critical | 004, 008 |
| 008 | Email Service | 5 (M) | 🔴 Critical | Keine |
| 009 | Activity Logging | 3 (S) | 🟡 Should | 002 |
| 010 | Wildarten/Meldegruppen Admin-UI | 5 (M) | 🔴 Must | 002 |

**Nach Phase 3:**
- ✅ Obmänner können moderieren (Approve/Edit/Reject)
- ✅ Öffentliches Formular funktioniert
- ✅ Email-System läuft
- ✅ Analytics vorhanden

---

## 🚀 Nutzung mit Auto-Claude

### **Option 1: Desktop App (Empfohlen)**

1. **Auto-Claude App öffnen**
2. **Projekt öffnen:** Dein Plugin-Verzeichnis auswählen
3. **Specs importieren:**
   ```bash
   # Kopiere alle Specs ins Projekt
   cp -r auto-claude-specs/.auto-claude .
   ```
4. **In der App:** Kanban Board öffnen
5. **Tasks erscheinen automatisch!**
6. **Starten:** Task auswählen → "Start" klicken
7. **Auto-Claude arbeitet:**
   - Planner Agent erstellt Subtasks
   - Coder Agent implementiert
   - QA Agent testet
   - Du reviewst am Ende

### **Option 2: CLI (für Profis)**

```bash
cd dein-plugin-verzeichnis
cp -r /pfad/zu/auto-claude-specs/.auto-claude .

cd .auto-claude
# Spec 001 starten
python ../../apps/backend/run.py --spec 001

# Während Ausführung: Pause für Instruktionen
Ctrl+C (einmal)
echo "Fokus auf Security" > specs/001-*/HUMAN_INPUT.md

# Review
python ../../apps/backend/run.py --spec 001 --review

# Merge
python ../../apps/backend/run.py --spec 001 --merge
```

---

## 📋 Empfohlene Reihenfolge

### **Woche 1: Foundation**
1. ✅ Spec 001 (Feature Flags) - **3 Points**
2. ✅ Spec 002 (Schema) - **5 Points**
3. ✅ Spec 003 (Migration) - **8 Points**
**→ Gesamt: 16 Points**

### **Woche 2: Architecture + Core**
4. ✅ Spec 008 (Email Service) - **5 Points**
5. ✅ Spec 004 (Repository) - **5 Points**
6. ✅ Spec 005 (Moderation Service) - **5 Points**
**→ Gesamt: 15 Points**

### **Woche 3: Features**
7. ✅ Spec 006 (Table Moderation) - **8 Points**
8. ✅ Spec 007 (Public Form) - **5 Points**
9. ✅ Spec 009 (Activity Log) - **3 Points**
**→ Gesamt: 16 Points**

### **Woche 4: Admin UI + Polish**
10. ✅ Spec 010 (Wildarten Admin) - **5 Points**
**→ Gesamt: 5 Points**

**TOTAL: 52 Story Points ≈ 4 Wochen**

---

## 🎯 Abhängigkeiten-Graph

```
001 (Feature Flags)
 └─→ 002 (Schema)
      ├─→ 003 (Migration)
      │    └─→ 004 (Repository)
      │         ├─→ 005 (Moderation)
      │         │    └─→ 006 (Table Moderation)
      │         └─→ 007 (Public Form)
      ├─→ 009 (Activity Log)
      └─→ 010 (Admin UI)

008 (Email) ─→ [005, 007]
```

**Kritischer Pfad:** 001 → 002 → 003 → 004 → 005 → 006

---

## ✅ Akzeptanzkriterien pro Phase

### **Phase 1 Done wenn:**
- [ ] Migration läuft ohne Fehler
- [ ] Alte Tabellen + Neue Tabellen existieren parallel
- [ ] Anzahl alter Submissions = Anzahl neuer Submissions
- [ ] Feature Flag `use_new_db_schema` kann umgeschaltet werden

### **Phase 2 Done wenn:**
- [ ] Repository kann Submissions lesen/schreiben
- [ ] Moderation Service kann approve/reject/edit
- [ ] Tests für Repository + Service grün

### **Phase 3 Done wenn:**
- [ ] [abschuss_table] zeigt Moderation-Buttons
- [ ] Approve/Edit/Reject funktioniert (AJAX)
- [ ] Öffentliches Formular sendet Verifizierungs-Email
- [ ] Email-Verifizierung ändert Status

---

## 🐛 Troubleshooting

### **Spec startet nicht**
```bash
# Prüfe ob spec.md existiert
ls -la .auto-claude/specs/001-*/spec.md

# Prüfe Python Environment
cd apps/backend
python --version  # Sollte 3.12+ sein

# Prüfe Claude Code OAuth
echo $CLAUDE_CODE_OAUTH_TOKEN
```

### **Migration schlägt fehl**
```bash
# Rollback
python run.py --spec 002 --discard

# Prüfe DB-Zugriff
mysql -u user -p database_name -e "SHOW TABLES;"
```

### **Agent macht nichts**
- Prüfe Claude Pro Subscription
- Prüfe API-Limits
- Schau in Backend-Logs: `apps/backend/logs/`

---

## 📖 Weitere Dokumentation

- **Auto-Claude Docs:** https://github.com/AndyMik90/Auto-Claude
- **WordPress Coding Standards:** https://developer.wordpress.org/coding-standards/
- **Plugin GitHub:** https://github.com/foe05/hgmh-v2

---

## 🤝 Support

Bei Fragen oder Problemen:
1. Check Auto-Claude Discord: https://discord.gg/KCXaPBr4Dj
2. GitHub Issues im Plugin-Repo
3. Review der CLAUDE.md im Auto-Claude Repo

---

## 📝 Notizen

- Jede Spec läuft in isoliertem Git Worktree
- Branch-Name: `auto-claude/001-feature-flags-system`
- Merge erst nach Review + Approval
- Parallel-Execution möglich (max 12 Agents)

**Viel Erfolg! 🚀**
