# Abschussplan HGMH - Hunting Management System

**Version:** 1.5.0  
**Status:** Vollständig implementiert mit CSV Export  
**Typ:** Flask Web Application & WordPress Plugin

## 🎯 Überblick

Das **Abschussplan HGMH** System ist eine spezialisierte Lösung für die digitale Verwaltung von Jagdabschussmeldungen in deutschen Jagdrevieren. Verfügbar als Flask-Webanwendung und WordPress-Plugin.

### ✨ Kernfunktionen
- ✅ **Digitale Abschussmeldungen** - Webformular für Jäger
- ✅ **Limitverwaltung** - Konfigurierbare Soll-Werte pro Kategorie  
- ✅ **CSV Export** - Vollständige Datenexporte mit Filteroptionen
- ✅ **Responsive Design** - Mobile-optimierte Bedienung
- ✅ **Multi-Database** - SQLite, MySQL, PostgreSQL Unterstützung

### 🎮 Demo & Screenshots
Live-Demo der Flask-Version verfügbar. Screenshots aller Funktionen im [Screenshots-Verzeichnis](screenshots/).

---

## 🚀 Quick Start

### Flask Version (Empfohlen für lokale Tests)
```bash
# Repository klonen
git clone https://github.com/foe05/pr25_one.git
cd pr25_one

# Dependencies installieren
uv sync

# Entwicklungsserver starten
python main.py
```
➡️ **Öffne:** http://localhost:5000

### WordPress Plugin
1. Upload `wp-content/plugins/abschussplan-hgmh/` nach WordPress
2. Plugin in Admin-Panel aktivieren
3. Shortcodes verwenden (siehe [Shortcode-Referenz](#-shortcode-referenz))

---

## 📊 CSV Export Funktionen

### 🔗 Export URLs
| Funktion | URL Format | Beispiel |
|----------|------------|----------|
| **Alle Einträge** | `/export_csv` | `yourdomain.com/export_csv` |
| **Nach Wildart** | `/export_csv?category=Rotwild` | Filter nach spezifischer Wildart |
| **Datumsbereich** | `/export_csv?from=2024-01-01&to=2024-12-31` | Zeitraum-Filter |
| **Kombiniert** | `/export_csv?category=Damwild&from=2024-01-01` | Wildart + Datum |

### 📋 Exportierte Spalten
1. **ID** - Eindeutige Datensatz-ID
2. **Wildart** - Game Species (Rotwild, Damwild, etc.)
3. **Abschussdatum** - Hunting Date
4. **Abschuss** - Category (Wildkalb, Schmaltier, etc.)
5. **WUS** - Wildlife Identification Number (1000000-9999999)
6. **Bemerkung** - Remarks/Comments
7. **Erstellt von** - Created by User
8. **Erstellt am** - Creation Timestamp

### ⚙️ Export-Konfiguration
- **Dateiname**: Konfigurierbar im Admin-Backend
- **Format**: Standard CSV (kommagetrennt, UTF-8)
- **Zugriff**: Öffentlich (keine Authentifizierung erforderlich)
- **Automatisierung**: Geeignet für Scripts und externe Systeme

---

## 🎨 Frontend Funktionen

### 📝 Abschussformular
```html
[abschuss_form species="Rotwild"]
```
- **WUS Validierung**: 7-stellige Nummer (1000000-9999999)
- **Limitprüfung**: Automatische Validierung gegen Soll-Werte
- **AJAX Submission**: Echtzeitvalidierung ohne Seitenneuladung

### 📊 Datenübersicht
```html
[abschuss_table species="Rotwild" limit="10"]
```
- **Export Button**: Direkter CSV-Download mit aktuellen Filtern
- **Paginierung**: Effiziente Darstellung großer Datenmengen
- **Responsive**: Mobile-optimierte Tabellendarstellung

### 📈 Zusammenfassung
```html
[abschuss_summary species="Rotwild"]
```
- **Status-Badges**: Farbkodierte Limit-Auslastung (🟢 🟡 🔴)
- **Prozentanzeige**: Live-Kalkulation der Zielerreichung

---

## ⚙️ Administration

### 🎛️ Konfigurationsbereiche

#### **Datenbank & Export**
- **Multi-DB Support**: SQLite, MySQL, PostgreSQL
- **Export-Dateiname**: Anpassbarer CSV-Dateiname
- **Verbindungstest**: Validierung der DB-Einstellungen

#### **Wildarten & Kategorien**
- **Dynamische Verwaltung**: CRUD-Operationen für alle Wildarten
- **Globale Kategorien**: Verfügbar für alle Wildarten
- **Live-Updates**: Sofortige Übernahme in Frontend

#### **Limit-Management**
- **Spezies-spezifisch**: Separate Limits pro Wildart
- **Überschreitungslogik**: "Überschießen möglich?" Option
- **Live-Vorschau**: Aktuelle Auslastung in Echtzeit

### 🔗 Export URL Generator
Automatisch generierte URLs mit Copy-to-Clipboard Funktionalität:
```
Basis-Export:     /export_csv
Rotwild-Export:   /export_csv?category=Rotwild  
Jahres-Export:    /export_csv?from=2024-01-01&to=2024-12-31
Kombiniert:       /export_csv?category=Damwild&from=2024-01-01&to=2024-12-31
```

---

## 🛠️ Technische Details

### 📦 Systemanforderungen
- **Flask Version**: Python 3.11+, Flask 2.0+
- **WordPress Version**: 5.0+, PHP 7.4+
- **Databases**: SQLite (default), MySQL 5.6+, PostgreSQL 9.0+

### 🏗️ Architektur
```
├── 🌐 Frontend Layer
│   ├── Flask Templates (Jinja2)
│   ├── WordPress Shortcodes
│   └── Responsive Bootstrap UI
│
├── 🔧 Application Layer  
│   ├── Form Validation & Processing
│   ├── CSV Export Engine
│   ├── AJAX API Endpoints
│   └── Authentication & Authorization
│
└── 💾 Data Layer
    ├── Multi-Database Abstraction
    ├── Settings Management
    └── Migration Support
```

### 🔐 Sicherheitsfeatures
- **Input Validation**: Server & Client-side validation
- **SQL Injection Protection**: Prepared statements
- **XSS Prevention**: Output escaping
- **CSRF Protection**: Nonce verification (WordPress)

### 📊 Datenmodell
```sql
-- Haupttabelle: Abschussmeldungen
custom_form_submissions:
  - id (PRIMARY KEY)
  - field1 (Abschussdatum)
  - field2 (Kategorie) 
  - field3 (WUS, 1000000-9999999)
  - field4 (Bemerkung)
  - created_at (Timestamp)

-- Konfiguration (Key-Value Store)
settings:
  - key (export_filename, db_config, etc.)
  - value (JSON/Text)

-- Kategorielimits
category_limits:
  - category (PRIMARY KEY)
  - max_count (INTEGER)
```

---

## 📖 API Referenz

### 🎯 Export Endpoints

#### `GET /export_csv`
Exportiert Abschussmeldungen als CSV-Datei.

**Parameter:**
- `category` (optional): Filtert nach Wildart (z.B. "Rotwild")
- `from` (optional): Start-Datum (YYYY-MM-DD)
- `to` (optional): End-Datum (YYYY-MM-DD)

**Response:**
- **Content-Type**: `text/csv`
- **Headers**: `Content-Disposition: attachment; filename=export.csv`

**Beispiele:**
```bash
# Alle Daten exportieren
curl "http://localhost:5000/export_csv" -o export.csv

# Rotwild des Jahres 2024
curl "http://localhost:5000/export_csv?category=Rotwild&from=2024-01-01&to=2024-12-31" -o rotwild_2024.csv

# Automatisiertes Backup-Script
#!/bin/bash
DATE=$(date +%Y-%m-%d)
curl "http://localhost:5000/export_csv" -o "backup_${DATE}.csv"
```

### 🔧 Admin Endpoints (Flask)
- `POST /admin/save_db_config` - Datenbank-Konfiguration speichern
- `POST /admin/save_limits` - Kategorie-Limits aktualisieren
- `GET /admin` - Admin-Panel anzeigen

---

## 🎮 Shortcode-Referenz (WordPress)

### `[abschuss_form]`
```html
[abschuss_form species="Rotwild"]
```
**Parameter:**
- `species` (required): Wildart-Name

**Features:**
- ✅ Benutzer-Authentifizierung erforderlich
- ✅ WUS-Validierung (1000000-9999999)
- ✅ Limit-basierte Kategorie-Deaktivierung
- ✅ AJAX-Submission mit Echtzeit-Feedback

### `[abschuss_table]` 
```html
[abschuss_table species="Rotwild" limit="20" page="1"]
```
**Parameter:**
- `species` (optional): Wildart-Filter
- `limit` (optional): Einträge pro Seite (default: 10)
- `page` (optional): Aktuelle Seite (default: 1)

**Features:**
- ✅ **CSV Export Button** mit aktuellen Filtern
- ✅ Paginierte Anzeige mit Navigation
- ✅ Responsive Tabellenlayout

### `[abschuss_summary]`
```html
[abschuss_summary species="Rotwild"]
```
**Parameter:**
- `species` (required): Wildart-Name

**Features:**
- ✅ Ist/Soll-Vergleich mit Prozentanzeige
- ✅ Status-Badges: 🟢 (<90%) 🟡 (90-99%) 🔴 (≥100%)
- ✅ Live-Kalkulation der Zielerreichung

### `[abschuss_limits]`
```html
[abschuss_limits species="Rotwild"]
```
**Parameter:**
- `species` (required): Wildart-Name

**Features:**
- ✅ Nur für `manage_options` Benutzer
- ✅ AJAX-basierte Konfiguration
- ✅ "Überschießen möglich?" Checkboxen

---

## 🔧 Entwicklung & Deployment

### 🛠️ Lokale Entwicklung
```bash
# Repository Setup
git clone https://github.com/foe05/pr25_one.git
cd pr25_one

# Python Dependencies (uv empfohlen)
uv sync
# oder mit pip:
pip install -r requirements.txt

# Development Server
python main.py

# Production Server (Gunicorn)
gunicorn --bind 0.0.0.0:5000 main:app
```

### 🐳 Docker Deployment
```dockerfile
FROM python:3.11-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
EXPOSE 5000
CMD ["gunicorn", "--bind", "0.0.0.0:5000", "main:app"]
```

### ☁️ Replit Deployment
1. **Import Repository**: `https://github.com/foe05/pr25_one`
2. **Auto-Configuration**: `.replit` wird automatisch erkannt
3. **Run**: Click "Run" - Gunicorn startet automatisch

### 🌐 WordPress Installation
1. **Upload**: `wp-content/plugins/abschussplan-hgmh/`
2. **Aktivierung**: WordPress Admin → Plugins → Aktivieren
3. **Konfiguration**: Abschussplan → Datenbankeinstellungen
4. **Shortcodes**: In Seiten/Posts verwenden

---

## 📂 Projektstruktur

```
pr25_one/
├── 📄 main.py                 # Flask Hauptanwendung
├── 📁 templates/              # Jinja2 Templates
│   ├── index.html            # Hauptformular
│   ├── admin.html            # Admin-Panel mit Export-URLs
│   └── submissions.html      # Datentabelle mit Export-Button
├── 📁 wp-content/plugins/     # WordPress Plugin
│   └── abschussplan-hgmh/    # Plugin-Verzeichnis
├── 📁 screenshots/           # UI Screenshots
├── 📊 form_submissions.db     # SQLite Datenbank
├── ⚙️ pyproject.toml          # Python Dependencies (uv)
├── 🐳 .replit                 # Replit Konfiguration
├── 📖 README.md               # Diese Dokumentation
├── 🤖 AGENT.md                # KI-Assistent Notizen
└── 📋 ANFORDERUNGEN.md        # Deutsche Spezifikation
```

---

## 🆕 Changelog

### Version 1.5.0 (Aktuell) - CSV Export Update
- ✅ **CSV Export Engine** - Vollständige Datenexporte
- ✅ **Export-Button** - Direkter Download aus Datentabelle
- ✅ **URL-basierter Export** - Automatisierung & externe Zugriffe
- ✅ **Datumsfilter** - from/to Parameter für Zeitraum-Exporte
- ✅ **Admin Export-URLs** - Copy-to-Clipboard Generator
- ✅ **WUS Validierung** - Aktualisierte Constraints (1000000-9999999)
- ✅ **Bootstrap Icons** - Verbesserte UI mit Icons

### Version 1.0 - Basis Implementation
- ✅ **Multi-Database Support** (SQLite/MySQL/PostgreSQL)
- ✅ **WordPress Shortcodes** (4 Shortcodes)
- ✅ **Admin Backend** (5 Konfigurationsbereiche)
- ✅ **Responsive Design** & Mobile Support
- ✅ **AJAX Forms** & Real-time Validation
- ✅ **Limit Management** mit Überschreitungslogik

---

## 🤝 Support & Kontakt

### 📋 Bug Reports & Feature Requests
- **GitHub Issues**: https://github.com/foe05/pr25_one/issues
- **Dokumentation**: Siehe diese README.md
- **Screenshots**: [screenshots/](screenshots/) Verzeichnis

### 🏷️ Labels & Kategorien
- `enhancement` - Neue Features
- `bug` - Fehlerbehebungen  
- `documentation` - Dokumentation
- `csv-export` - CSV Export Features
- `wordpress` - WordPress-spezifische Issues

### 📞 Support-Kanäle
1. **GitHub Issues** (bevorzugt)
2. **Code Review** via Pull Requests
3. **Dokumentation** in README.md

---

## 📜 Lizenz

**MIT License** - Siehe [LICENSE](LICENSE) Datei.

**Entwickelt für:** Deutsche Jagdreviere & Wildtiermanagement  
**Sprache:** Deutsch (UI) + Englisch (Code/Docs)  
**Status:** Production Ready ✅

---

*⭐ Star dieses Repository wenn es hilfreich war!*
