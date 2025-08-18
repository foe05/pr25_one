# Abschussplan HGMH - WordPress Plugin

**Version:** 2.0.0  
**Status:** Production Ready  
**Type:** WordPress Plugin for German Hunting Management

## 🎯 Overview

The **Abschussplan HGMH** plugin is a comprehensive WordPress solution for digital management of hunting reports in German hunting districts. It provides a complete system for hunters to submit game hunting data and administrators to manage hunting limits, categories, and export data with advanced features.

### ✨ Core Features
- ✅ **Digital Hunting Reports** - Advanced web forms with validation
- ✅ **Comprehensive Admin Panel** - Modern tabbed interface with full CRUD operations
- ✅ **Advanced Export System** - Configurable CSV exports with custom filename patterns  
- ✅ **Category Management** - Full CRUD for species and categories with limit controls
- ✅ **Date Range Operations** - Delete submissions by custom date ranges
- ✅ **Responsive Design** - Mobile-optimized Bootstrap 5.3 interface
- ✅ **Multi-Database** - WordPress MySQL, SQLite, PostgreSQL support
- ✅ **Shortcode Integration** - 5 powerful shortcodes for complete functionality
- ✅ **Real-time Table Updates** - AJAX-powered data refreshing

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

## 📊 Advanced Export System

### 🔗 Export URLs & Parameters
Access CSV exports via WordPress AJAX endpoints with extensive configuration:

| Function | URL Format | Example |
|----------|------------|----------|
| **All Entries** | `wp-admin/admin-ajax.php?action=export_abschuss_csv` | Export all harvest data |
| **By Species** | `?action=export_abschuss_csv&species=Rotwild` | Filter by specific game species |
| **Date Range** | `?action=export_abschuss_csv&from=2024-01-01&to=2024-12-31` | Time period filter |
| **Custom Filename** | `?action=export_abschuss_csv&filename=custom_export` | Set custom filename |
| **Combined** | `?action=export_abschuss_csv&species=Damwild&from=2024-01-01&filename=damwild_2024` | Multiple parameters |

### 🎛️ Export Configuration (Admin Panel)
- **Filename Patterns**: `{species}`, `{date}`, `{datetime}` placeholders
- **Time Integration**: Optional timestamp inclusion in filenames
- **Parameter Documentation**: Complete API reference in admin interface
- **Real-time Preview**: See export URLs with current settings

### 📋 Export Columns
1. **ID** - Unique record ID
2. **Wildart** - Game species (Rotwild, Damwild, etc.)
3. **Abschussdatum** - Harvest date and time
4. **Abschuss** - Harvest details/category
5. **WUS** - Wildursprungsschein number
6. **Jagdbezirk** - Hunting district
7. **Meldegruppe** - Reporting group
8. **Bemerkung** - Additional remarks
9. **Erstellt von** - Created by (WordPress user)
10. **Erstellt am** - Creation timestamp

### ⚙️ Export Security & Access
- **WordPress AJAX**: Secure endpoint integration
- **Nonce Verification**: CSRF protection
- **Role-based Access**: WordPress capability checks
- **Format**: UTF-8 CSV with proper escaping

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
Display harvest data table with real-time updates and advanced features.
```html
[abschuss_table species="Rotwild" limit="20" page="1"]
```
**Parameters:**
- `species` (optional): Filter by game species
- `limit` (optional): Entries per page (default: 10)
- `page` (optional): Current page (default: 1)

**Features:**
- ✅ **AJAX Auto-Refresh** - Updates after form submissions
- ✅ **Advanced CSV Export** with current filters and custom filenames
- ✅ Paginated display with navigation
- ✅ Responsive Bootstrap table layout
- ✅ WordPress user permissions integration
- ✅ Real-time data synchronization

### `[abschuss_summary]`
Show harvest summary and statistics with flexible parameter combinations.
```html
[abschuss_summary]                                           # Entire hunting community
[abschuss_summary species="Rotwild"]                         # Only Rotwild (all groups)
[abschuss_summary meldegruppe="Gruppe_A"]                    # Only Group_A (all species)
[abschuss_summary species="Rotwild" meldegruppe="Gruppe_A"]  # Rotwild + Group_A
```
**Parameters:**
- `species` (optional): Game species name - empty shows all species aggregated
- `meldegruppe` (optional): Reporting group name - empty shows all groups aggregated

**Features:**
- ✅ **Public Access** - Available for unauthenticated users (public statistics)
- ✅ **Flexible Parameter Logic** - All parameter combinations supported
- ✅ **Graceful Fallback** - Invalid parameters show warnings but don't break
- ✅ **Total Aggregation** - No parameters = entire hunting community statistics
- ✅ Current vs. target comparison with percentages
- ✅ Status badges: 🟢 (<90%) 🟡 (90-99%) 🔴 (≥100%)
- ✅ Live calculation of target achievement

### `[abschuss_admin]`
Comprehensive admin configuration panel with modern tabbed interface (requires `manage_options` capability).
```html
[abschuss_admin]
```

**Features:**
- ✅ **Modern Tabbed Interface** - Dashboard, Data Management, Categories, Database, CSV Export
- ✅ **Full CRUD Operations** - Create, Read, Update, Delete for all entities
- ✅ **Real-time Statistics** - Live dashboard with current usage metrics
- ✅ **Advanced Database Management** - Multi-database support with connection testing
- ✅ **Category & Species Management** - Complete administrative control
- ✅ **Date Range Operations** - Delete submissions by custom date ranges
- ✅ **Export Configuration** - Filename patterns and parameter documentation

### `[abschuss_limits]`
Advanced limit configuration interface with integrated category management.
```html
[abschuss_limits species="Rotwild"]
```
**Parameters:**
- `species` (required): Game species name

**Features:**
- ✅ **Integrated Category Management** - Edit categories and limits in one interface
- ✅ **Target Value Configuration** - Set Abschuss Soll (harvest targets) per category
- ✅ **Overshoot Controls** - "Overshoot allowed" configuration per category
- ✅ Admin-only access (`manage_options`)
- ✅ AJAX-based real-time updates
- ✅ Synchronized with admin panel settings

---

## ⚙️ Advanced Administration

### 🏛️ Modern Admin Interface
The plugin provides a comprehensive, tabbed admin interface with the following sections:

#### **📊 Dashboard Tab**
- **Real-time Statistics**: Current submissions, monthly activity, species breakdown
- **Quick Actions**: Fast access to common operations
- **System Status**: Database connection, plugin version, WordPress compatibility

#### **📋 Data Management Tab**
- **Submission Overview**: Paginated table with all harvest submissions
- **CRUD Operations**: Edit, delete individual submissions
- **Batch Operations**: Mass operations with filters
- **Search & Filter**: Advanced filtering by date, species, user

#### **🏷️ Categories Tab**
- **Species Management**: Add, edit, delete game species
- **Category Management**: Full CRUD for harvest categories per species
- **Limit Configuration**: Set target values (Abschuss Soll) directly in table
- **Overshoot Settings**: Configure overshoot permissions per category

#### **🗄️ Database Tab**
- **Multi-DB Support**: WordPress MySQL (default), SQLite, PostgreSQL
- **Connection Testing**: Real-time database validation
- **Migration Tools**: Switch between database types safely
- **Date Range Deletion**: Remove submissions by custom date ranges
- **Backup/Restore**: Data management utilities

#### **📤 CSV Export Tab**
- **Export Configuration**: Filename patterns with placeholders
- **Parameter Documentation**: Complete API reference
- **Real-time Preview**: See generated URLs and examples
- **Security Settings**: Access control and authentication options

---

## 🛠️ Technical Details

### 🏗️ Plugin Architecture
```
wp-content/plugins/abschussplan-hgmh/
├── 📄 abschussplan-hgmh.php        # Main plugin file (v2.0.0)
├── 📁 includes/                    # Core classes
│   ├── class-database-handler.php  # Multi-database operations
│   ├── class-form-handler.php      # Form processing, AJAX & Export
│   └── class-table-display.php     # Data presentation
├── 📁 admin/                      # Advanced admin functionality  
│   ├── class-admin-page-modern.php # Modern tabbed admin interface
│   ├── class-admin-page-legacy.php # Legacy admin interface
│   └── assets/                     # Admin-specific assets
│       ├── admin-modern.js         # AJAX handlers & UI logic
│       └── admin-modern.css        # Modern admin styling
├── 📁 templates/                   # Frontend templates
│   ├── form-template.php          # Submission form with validation
│   ├── table-template.php         # Auto-refreshing data table
│   ├── summary-template.php       # Statistics display
│   ├── admin-template.php         # Admin form template
│   └── limits-template.php        # Limits configuration
├── 📁 assets/                     # Frontend assets
│   ├── css/style.css              # Bootstrap 5.3 integration
│   └── js/form-validation.js      # Enhanced form validation & AJAX
└── 📄 uninstall.php               # Complete cleanup on uninstall
```

### 🔐 Security Features
- **WordPress Integration**: Uses WordPress security standards
- **Nonce Verification**: CSRF protection for all AJAX requests
- **User Capabilities**: Role-based access control
- **Input Sanitization**: WordPress sanitization functions
- **SQL Injection Protection**: WordPress $wpdb prepared statements

### 📊 Database Schema
```sql
-- Main table: Harvest submissions (wp_ahgmh_submissions)
CREATE TABLE wp_ahgmh_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,                    -- WordPress user ID
    game_species VARCHAR(50) DEFAULT 'Rotwild', -- Species (Rotwild, Damwild, etc.)
    field1 VARCHAR(255),                        -- Abschussdatum (harvest date)
    field2 VARCHAR(255),                        -- Abschuss (harvest category)
    field3 VARCHAR(255),                        -- WUS (Wildursprungsschein number)
    field4 TEXT,                                -- Bemerkung (remarks)
    field5 VARCHAR(255),                        -- Jagdbezirk (hunting district)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hunting districts table (wp_ahgmh_jagdbezirke)  
CREATE TABLE wp_ahgmh_jagdbezirke (
    id INT PRIMARY KEY AUTO_INCREMENT,
    jagdbezirk VARCHAR(255) NOT NULL,           -- Hunting district name
    meldegruppe VARCHAR(255),                   -- Reporting group
    ungueltig BOOLEAN DEFAULT FALSE,            -- Active/inactive status
    bemerkung TEXT,                             -- Remarks
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- WordPress Options for Configuration:
-- ahgmh_species: Array of game species
-- ahgmh_categories_{species}: Categories per species  
-- abschuss_category_limits_{species}: Target values per category
-- abschuss_category_allow_exceeding_{species}: Overshoot permissions
-- ahgmh_export_filename_pattern: Export filename template
-- ahgmh_export_include_time: Include timestamp in exports
```

### 🔌 Database API (New in v2.0.0)

**Public Summary Data Methods:**
```php
// Main method for flexible summary data retrieval
get_public_summary_data($species = '', $meldegruppe = '')

// Helper methods for validation and data aggregation
get_all_meldegruppen()                              // Get available meldegruppen
get_total_summary_data($species_list)               // Aggregate all species/groups  
get_species_summary_data($species)                  // Species-specific data
get_meldegruppe_summary_data($meldegruppe, $species_list)  // Group-specific data
get_specific_summary_data($species, $meldegruppe)   // Specific combination
```

**Parameter Combinations:**
- Empty parameters → Total hunting community statistics
- Species only → All groups for specific species
- Meldegruppe only → All species for specific group  
- Both parameters → Specific species + group combination

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

### Version 2.0.0 (Current)
- ✅ **Modern Admin Interface** - Complete redesign with tabbed navigation
- ✅ **Advanced Export System** - Configurable filename patterns and parameters
- ✅ **Comprehensive CRUD Operations** - Full Create, Read, Update, Delete functionality
- ✅ **Real-time Table Updates** - AJAX-powered auto-refresh after submissions
- ✅ **Enhanced Category Management** - Integrated limits and overshoot controls
- ✅ **Date Range Operations** - Custom date range deletion functionality
- ✅ **Improved Database Management** - Multi-database with enhanced connection handling
- ✅ **Bootstrap 5.3 Integration** - Modern responsive UI framework
- ✅ **Advanced Security** - Enhanced WordPress security integration
- ✅ **API Documentation** - Complete parameter reference in admin interface
- ✅ **Public Summary Statistics** - [abschuss_summary] shortcode with flexible parameters
- ✅ **Flexible Parameter Logic** - Species and meldegruppe combinations with graceful fallback

### Version 1.5.0
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

**GPLv3 License** - See [LICENSE](LICENSE) file.

**Developed for:** German hunting districts & wildlife management  
**Language:** German UI + English code/documentation  
**Status:** Production Ready ✅  
**WordPress Compatible:** 5.0+ with PHP 7.4+

---

*⭐ Star this repository if it helped you manage your hunting district data!*
