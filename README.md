# Abschussplan HGMH - WordPress Plugin

**Version:** 1.5.0  
**Status:** Production Ready  
**Type:** WordPress Plugin for German Hunting Management

## 🎯 Overview

The **Abschussplan HGMH** plugin is a specialized WordPress solution for digital management of hunting reports in German hunting districts. It provides a complete system for hunters to submit game harvest data and administrators to manage hunting limits and export data.

### ✨ Core Features
- ✅ **Digital Harvest Reports** - Web forms for hunters
- ✅ **Limit Management** - Configurable target values per category  
- ✅ **CSV Export** - Complete data exports with filter options
- ✅ **Responsive Design** - Mobile-optimized interface
- ✅ **Multi-Database** - WordPress MySQL, SQLite, PostgreSQL support
- ✅ **Shortcode Integration** - Easy WordPress integration

---

## 🚀 Installation

### WordPress Plugin Installation
1. Upload `wp-content/plugins/abschussplan-hgmh/` to your WordPress installation
2. Activate plugin in WordPress Admin Panel
3. Configure database settings (if needed)
4. Use shortcodes in pages/posts (see [Shortcode Reference](#-shortcode-reference))

### Requirements
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Database**: MySQL 5.6+ (default), SQLite, or PostgreSQL 9.0+

---

## 📊 CSV Export Features

### 🔗 Export URLs
Access CSV exports via WordPress AJAX endpoints:

| Function | URL Format | Example |
|----------|------------|----------|
| **All Entries** | `wp-admin/admin-ajax.php?action=export_abschuss_csv` | Export all harvest data |
| **By Species** | `?action=export_abschuss_csv&species=Rotwild` | Filter by specific game species |
| **Date Range** | `?action=export_abschuss_csv&from=2024-01-01&to=2024-12-31` | Time period filter |
| **Combined** | `?action=export_abschuss_csv&species=Damwild&from=2024-01-01` | Species + date filter |

### 📋 Export Columns
1. **ID** - Unique record ID
2. **User ID** - WordPress user ID
3. **Game Species** - Wildart (Rotwild, Damwild, etc.)
4. **Field1-5** - Custom form fields
5. **Created At** - Submission timestamp

### ⚙️ Export Configuration
- **Access**: WordPress AJAX (authentication via nonce)
- **Format**: Standard CSV (comma-separated, UTF-8)
- **Security**: WordPress nonce verification
- **Integration**: Works with WordPress user permissions

---

## 🎨 Shortcode Reference

### `[abschuss_form]`
Display the harvest submission form.
```html
[abschuss_form species="Rotwild"]
```
**Parameters:**
- `species` (optional): Pre-select game species

**Features:**
- ✅ WordPress user authentication required
- ✅ AJAX form submission
- ✅ Real-time validation
- ✅ Limit-based category management

### `[abschuss_table]`
Display harvest data table with pagination.
```html
[abschuss_table species="Rotwild" limit="20" page="1"]
```
**Parameters:**
- `species` (optional): Filter by game species
- `limit` (optional): Entries per page (default: 10)
- `page` (optional): Current page (default: 1)

**Features:**
- ✅ **CSV Export Button** with current filters
- ✅ Paginated display with navigation
- ✅ Responsive table layout
- ✅ WordPress user permissions integration

### `[abschuss_summary]`
Show harvest summary and statistics.
```html
[abschuss_summary species="Rotwild"]
```
**Parameters:**
- `species` (required): Game species name

**Features:**
- ✅ Current vs. target comparison with percentages
- ✅ Status badges: 🟢 (<90%) 🟡 (90-99%) 🔴 (≥100%)
- ✅ Live calculation of target achievement

### `[abschuss_admin]`
Admin configuration panel (requires `manage_options` capability).
```html
[abschuss_admin]
```

**Features:**
- ✅ Database configuration
- ✅ Species and category management
- ✅ Limit configuration
- ✅ Export settings

### `[abschuss_limits]`
Limit configuration interface.
```html
[abschuss_limits species="Rotwild"]
```
**Parameters:**
- `species` (required): Game species name

**Features:**
- ✅ Admin-only access (`manage_options`)
- ✅ AJAX-based configuration
- ✅ "Overshoot allowed" checkboxes

---

## ⚙️ Administration

### 🎛️ Configuration Areas

#### **Database Settings**
- **Multi-DB Support**: WordPress MySQL (default), SQLite, PostgreSQL
- **Connection Testing**: Validate database settings
- **Migration Support**: Switch between database types

#### **Species & Categories**
- **Dynamic Management**: CRUD operations for all game species
- **Global Categories**: Available across all species
- **Live Updates**: Immediate frontend integration

#### **Limit Management**
- **Species-specific**: Separate limits per game species
- **Overshoot Logic**: "Overshoot allowed" configuration
- **Live Preview**: Current usage in real-time

#### **Export Configuration**
- **CSV Settings**: Configure export parameters
- **User Permissions**: WordPress role-based access
- **Automation**: Suitable for external scripts

---

## 🛠️ Technical Details

### 🏗️ Plugin Architecture
```
wp-content/plugins/abschussplan-hgmh/
├── 📄 abschussplan-hgmh.php        # Main plugin file
├── 📁 includes/                    # Core classes
│   ├── class-database-handler.php  # Database operations
│   ├── class-form-handler.php      # Form processing & AJAX
│   └── class-table-display.php     # Data presentation
├── 📁 templates/                   # Frontend templates
│   ├── form-template.php          # Submission form
│   ├── table-template.php         # Data table
│   ├── summary-template.php       # Statistics display
│   └── admin-template-modern.php  # Admin interface
├── 📁 admin/                      # Admin functionality
│   └── class-admin-page.php       # Admin page handler
├── 📁 assets/                     # Frontend assets
│   ├── css/style.css              # Custom styles
│   └── js/form-validation.js      # Form validation
└── 📄 uninstall.php               # Cleanup on uninstall
```

### 🔐 Security Features
- **WordPress Integration**: Uses WordPress security standards
- **Nonce Verification**: CSRF protection for all AJAX requests
- **User Capabilities**: Role-based access control
- **Input Sanitization**: WordPress sanitization functions
- **SQL Injection Protection**: WordPress $wpdb prepared statements

### 📊 Database Schema
```sql
-- Main table: Harvest submissions
wp_ahgmh_submissions:
  - id (PRIMARY KEY, AUTO_INCREMENT)
  - user_id (WordPress user ID)
  - game_species (VARCHAR, default 'Rotwild')
  - field1-5 (TEXT, custom form fields)
  - created_at (DATETIME, auto timestamp)

-- Jagdreviere table
wp_ahgmh_jagdbezirke:
  - id (PRIMARY KEY, AUTO_INCREMENT)
  - jagdbezirk (VARCHAR, hunting district name)
  - meldegruppe (VARCHAR, reporting group)
  - ungueltig (BOOLEAN, active/inactive)
  - bemerkung (TEXT, remarks)
```

---

## 🔧 Development

### 🛠️ Local Development Setup
1. **WordPress Installation**: Set up local WordPress environment
2. **Plugin Installation**: Copy plugin to `wp-content/plugins/`
3. **Database Setup**: Configure database connection in plugin settings
4. **Testing**: Use WordPress shortcodes in test pages

### 🔍 Debugging
- **Debug Mode**: Enable `WP_DEBUG` in wp-config.php
- **Debug Files**: `debug.php` and `debug-db.php` included
- **Error Logging**: WordPress error logging integration

### 🚀 Deployment
1. **Upload Plugin**: FTP/SFTP to WordPress installation
2. **Database Migration**: Plugin handles table creation automatically
3. **Configuration**: Set up database and limits via admin interface
4. **Go Live**: Add shortcodes to WordPress pages/posts

---

## 📂 Plugin Structure

```
abschussplan-hgmh/
├── 📄 Main Plugin
│   ├── abschussplan-hgmh.php      # Plugin bootstrap
│   ├── uninstall.php              # Cleanup procedures
│   ├── debug.php                  # Development debugging
│   └── debug-db.php               # Database debugging
├── 📁 Core Classes
│   ├── includes/class-database-handler.php    # DB operations
│   ├── includes/class-form-handler.php        # Forms & AJAX
│   └── includes/class-table-display.php       # Data display
├── 📁 Admin Interface
│   └── admin/class-admin-page.php             # Admin functionality
├── 📁 Frontend Templates
│   ├── templates/form-template.php            # Submission form
│   ├── templates/table-template.php           # Data table
│   ├── templates/summary-template.php         # Statistics
│   ├── templates/admin-template-modern.php    # Modern admin UI
│   └── templates/admin-template-old.php       # Legacy admin UI
└── 📁 Assets
    ├── assets/css/style.css                   # Custom styles
    └── assets/js/form-validation.js           # Frontend validation
```

---

## 🆕 Version History

### Version 1.5.0 (Current)
- ✅ **WordPress Plugin Architecture** - Complete WordPress integration
- ✅ **Shortcode System** - 5 configurable shortcodes
- ✅ **AJAX Integration** - Real-time form processing
- ✅ **CSV Export System** - WordPress AJAX-based exports
- ✅ **Multi-Database Support** - MySQL, SQLite, PostgreSQL
- ✅ **Bootstrap UI** - Modern responsive interface
- ✅ **Security Integration** - WordPress security standards
- ✅ **User Management** - WordPress user roles and capabilities

---

## 🤝 Support

### 📋 Issues & Feature Requests
- **GitHub Issues**: https://github.com/foe05/pr25_one/issues
- **Documentation**: This README.md
- **WordPress Codex**: Follow WordPress development standards

### 🏷️ Issue Labels
- `wordpress` - WordPress-specific issues
- `enhancement` - New features
- `bug` - Bug fixes
- `documentation` - Documentation improvements
- `csv-export` - Export functionality

---

## 📜 License

**MIT License** - See [LICENSE](LICENSE) file.

**Developed for:** German hunting districts & wildlife management  
**Language:** German UI + English code/documentation  
**Status:** Production Ready ✅

---

*⭐ Star this repository if it helped you manage your hunting district data!*
