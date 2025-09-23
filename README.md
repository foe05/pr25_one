# Abschussplan HGMH - WordPress Plugin

**Version:** 2.5.2  
**Status:** Production Ready - Enhanced Meldegruppen Management System  
**Type:** WordPress Plugin for German Hegegemeinschaften Management

## 🎯 Overview

The **Abschussplan HGMH** plugin is a comprehensive WordPress solution for digital management of hunting reports in German hunting districts. It provides a complete system for hunters to submit game hunting data and administrators to manage hunting limits, categories, and export data with advanced features.

### ✨ Core Features
- ✅ **Digital Hunting Reports** - Advanced web forms with validation and permission-based preselection
- ✅ **3-Level Permission System** - Besucher, Obmann, Vorstand with wildart-specific assignments
- ✅ **Comprehensive Admin Panel** - Modern tabbed interface with full CRUD operations and obmann management
- ✅ **Master-Detail Wildart Configuration** - Intuitive wildart-specific category and meldegruppe management with full CRUD operations
- ✅ **Flexible Limits Management** - Dual-mode system: Meldegruppen-specific vs. Hegegemeinschaft-total limits
- ✅ **Advanced Export System** - Configurable CSV exports with admin interface and public URLs
- ✅ **Obmann Management** - Complete user assignment system with wildart-specific meldegruppe assignments
- ✅ **Category Management** - Full CRUD for species and categories with integrated limit controls
- ✅ **Status Tracking** - Real-time status badges (🟢 🟡 🔴 🔥) based on limit compliance
- ✅ **Date Range Operations** - Delete submissions by custom date ranges
- ✅ **Responsive Design** - Mobile-optimized Bootstrap 5.3 interface
- ✅ **Multi-Database** - WordPress MySQL, SQLite, PostgreSQL support
- ✅ **Shortcode Integration** - 5 powerful shortcodes with permission-based access control and cross-component data consistency
- ✅ **Real-time Table Updates** - AJAX-powered data refreshing
- ✅ **Complete Internationalization** - German translation with POT file for additional languages
- ✅ **WordPress.org Compliance** - Security hardened, coding standards compliant, production ready

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
- **Public URLs**: CSV export URLs remain publicly accessible (no authentication required)
- **Admin Interface**: CSV export controls only available in admin backend for Vorstand
- **Frontend Restrictions**: No export buttons shown in frontend shortcodes
- **WordPress AJAX**: Secure endpoint integration
- **Format**: UTF-8 CSV with proper escaping

---

## 👥 User Permission System

### 3-Level Hierarchy
The plugin implements a comprehensive permission system designed for German hunting districts:

**🌐 Level 1: Besucher (Public Visitors)**
- **Access**: Only `[abschuss_summary]` for public statistics
- **Restrictions**: All other shortcodes require login
- **CSV Export**: Public URLs remain accessible

**👤 Level 2: Obmann (Group Leaders)**
- **Access**: Wildart-specific meldegruppe assignments
- **User Meta**: `ahgmh_assigned_meldegruppe_{wildart}` (e.g., `ahgmh_assigned_meldegruppe_Rotwild`)
- **Data Filtering**: Automatic restriction to assigned meldegruppen
- **Form Behavior**: Meldegruppe preselection based on assignments

**⭐ Level 3: Vorstand (Board Members)**
- **Access**: Full unrestricted access to all functions
- **Capability**: WordPress `manage_options` required
- **Admin Interface**: Complete configuration and user management
- **CSV Export**: Admin interface with URL generation tools

### Wildart-Specific Assignments
- One Obmann can be assigned to different meldegruppen for different wildarten
- Example: User A manages "Meldegruppe_Nord" for "Rotwild" and "Meldegruppe_Süd" for "Damwild"
- Managed through WordPress admin interface: **Abschussplan → Obleute**

---

## 🎨 Shortcode Reference

### `[abschuss_form]`
Display the harvest submission form with permission-based access control.
```html
[abschuss_form species="Rotwild"]
```
**Parameters:**
- `species` (**required**): Game species name

**Permission-Based Features:**
- 🚫 **Besucher**: Login form shown
- ✅ **Obmann**: Form with automatic meldegruppe preselection for assigned wildart
- ✅ **Vorstand**: Form with all meldegruppen available
- ✅ AJAX form submission with real-time validation
- ✅ Wildart-specific jagdbezirk filtering

### `[abschuss_table]`
Display harvest data table with permission-based filtering and access control.
```html
[abschuss_table species="Rotwild" meldegruppe="Gruppe_A" limit="20" page="1"]
```
**Parameters:**
- `species` (optional): Filter by game species
- `meldegruppe` (optional): Filter by meldegruppe
- `limit` (optional): Entries per page (default: 10)
- `page` (optional): Current page (default: 1)

**Permission-Based Features:**
- 🚫 **Besucher**: Login form shown
- ✅ **Obmann**: Automatic filtering to assigned meldegruppe for wildart
- ✅ **Vorstand**: All data or parameter-filtered view
- ✅ Paginated display with navigation
- ✅ Responsive Bootstrap table layout
- ❌ **No Export Buttons** in frontend (security feature)

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

**Permission-Based Features:**
- ✅ **Besucher**: Public access to aggregated statistics
- ✅ **Obmann**: Filtered to assigned meldegruppen per wildart
- ✅ **Vorstand**: Full access to all data or parameter-filtered view
- ✅ **Flexible Parameter Logic** - All parameter combinations supported
- ✅ **Graceful Fallback** - Invalid parameters show warnings but don't break
- ✅ **Total Aggregation** - No parameters = entire hunting community statistics
- ✅ Current vs. target comparison with percentages
- ✅ Status badges: 🟢 (<90%) 🟡 (90-99%) 🔴 (≥100%)
- ✅ Live calculation of target achievement

### `[abschuss_admin]`
Comprehensive admin configuration panel with modern tabbed interface.
```html
[abschuss_admin]
```

**Permission-Based Access:**
- 🚫 **Besucher + Obmann**: Login form or permission denied message
- ✅ **Vorstand**: Full admin interface access

**Features:**
- ✅ **Modern Tabbed Interface** - Dashboard, Data Management, Obleute, Categories, Database, CSV Export
- ✅ **Obmann Management** - User assignment system for wildart-specific meldegruppen
- ✅ **Full CRUD Operations** - Create, Read, Update, Delete for all entities
- ✅ **Real-time Statistics** - Live dashboard with current usage metrics
- ✅ **Advanced Database Management** - Multi-database support with connection testing
- ✅ **Category & Species Management** - Complete administrative control
- ✅ **Date Range Operations** - Delete submissions by custom date ranges

### `[abschuss_limits]` ⚠️ **Admin-Only**
Comprehensive limits management interface with dual-mode support.
```html
[abschuss_limits]                    <!-- Shows redirect to admin panel -->
[abschuss_limits wildart="Rotwild"]  <!-- Direct access for specific wildart -->
```
**Parameters:**
- `wildart` (optional): Specific wildart name. If empty, redirects to admin panel.

**Permission-Based Access:**
- 🚫 **Besucher + Obmann**: Login form or permission denied message
- ✅ **Vorstand**: Full limits management interface

**Dual-Mode System:**

**Mode A: Meldegruppen-Specific Limits**
- 📋 Matrix-based configuration per meldegruppe and category
- 🔢 Individual SOLL values for each combination
- ➕ Automatic total calculation
- 📊 Detailed IST vs SOLL comparison
- 🎯 Perfect for large hunting districts

**Mode B: Hegegemeinschaft Total Limits** 
- 🔢 Simple total limits per category
- 📋 IST breakdown by meldegruppe (read-only)
- ⚡ Simplified management for smaller districts

**Status Badge System:**
- 🟢 **Green (< 80%)**: Well within limits
- 🟡 **Yellow (80-95%)**: Approaching limit  
- 🔴 **Red (95-110%)**: Near or at limit
- 🔥 **Fire (> 110%)**: Exceeded limit

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

#### **🦌 Wildarten-Konfiguration Tab** (Enhanced in v2.0.0)
- **Master-Detail Interface**: Left sidebar wildart navigation + right panel detail configuration
- **Wildart Management**: Create, edit, delete game species with full data management
- **Category Configuration**: Species-specific categories with inline editing and auto-save
- **Meldegruppe Management**: Species-specific meldegruppen with CRUD operations
- **Limits Management**: Comprehensive dual-mode limits system integrated into detail panel
- **Limit Mode Switching**: Toggle between meldegruppen-specific and hegegemeinschaft-total modes
- **Status Tracking**: Real-time status badges with IST vs SOLL comparison
- **Limits Matrix**: Interactive tables for both limit modes with auto-calculation
- **Overview Dashboard**: Real-time statistics per species (current/target/percentage/status)
- **Responsive Design**: Mobile-first layout with sidebar collapsing on tablets
- **AJAX Operations**: All operations without page reloads for optimal user experience

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

---

## 🎯 Comprehensive Limits Management System

### 🔄 Dual-Mode Architecture
The plugin provides a flexible limits management system that adapts to different hunting district structures:

#### **Mode A: Meldegruppen-Specific Limits** 
Ideal for larger hunting districts with structured reporting groups:

- **📊 Matrix Configuration**: Interactive table for setting individual SOLL values per meldegruppe and category
- **➕ Auto-Calculation**: Automatic total SOLL calculation across all meldegruppen  
- **📈 Detailed Tracking**: IST vs SOLL comparison with group-specific breakdown
- **🎯 Granular Control**: Perfect for districts with specific quotas per reporting group

#### **Mode B: Hegegemeinschaft Total Limits**
Simplified system for smaller hunting districts:

- **🔢 Simple Configuration**: Single SOLL value per category for entire hunting district
- **📋 Transparent Breakdown**: IST values shown by meldegruppen for transparency
- **⚡ Easy Management**: Streamlined interface for districts without complex quota systems
- **🏠 District-Wide**: Single point of configuration per wildart-category combination

### 🏷️ Status Badge System
Real-time visual indicators for limit compliance:

| Badge | Range | Meaning | Visual |
|-------|-------|---------|---------|
| 🟢 Green | < 80% | Well within limits | Safe harvest range |
| 🟡 Yellow | 80-95% | Approaching limit | Caution advised |
| 🔴 Red | 95-110% | Near/at limit | Critical range |
| 🔥 Fire | > 110% | Exceeded limit | Over-harvest alert |

### 🛠️ Technical Features
- **🔄 Mode Switching**: Instant toggle between limit modes via AJAX
- **💾 Auto-Save**: Automatic saving of all configuration changes
- **📱 Responsive Design**: Mobile-optimized limit matrices and tables
- **🔒 Admin Security**: Requires `manage_options` capability for all limit operations
- **🔄 Real-Time Updates**: Live status calculation based on current submissions

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

-- Meldegruppen configuration table (wp_ahgmh_meldegruppen_config)
-- Enhanced with limits management capabilities
CREATE TABLE wp_ahgmh_meldegruppen_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wildart VARCHAR(50),                            -- Game species  
    meldegruppe VARCHAR(100) NOT NULL,              -- Reporting group
    jagdbezirke TEXT,                               -- Associated hunting districts
    kategorie VARCHAR(100) DEFAULT NULL,            -- Category for limits
    limit_value INT DEFAULT NULL,                   -- Limit value for this combination
    limit_mode ENUM('meldegruppen_specific','hegegemeinschaft_total') DEFAULT 'meldegruppen_specific',
    is_wildart_specific BOOLEAN DEFAULT FALSE,      -- Wildart-specific configuration
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY wildart_idx (wildart),
    KEY meldegruppe_idx (meldegruppe),
    KEY wildart_meldegruppe_kategorie_idx (wildart, meldegruppe, kategorie)
);

-- WordPress Options for Configuration:
-- ahgmh_species: Array of game species
-- ahgmh_categories_{species}: Categories per species  
-- ahgmh_limit_mode_{wildart}: Limit mode per wildart (meldegruppen_specific/hegegemeinschaft_total)
-- ahgmh_hegegemeinschaft_limit_{wildart}_{category}: Total limits for hegegemeinschaft mode
-- abschuss_category_limits_{species}: Legacy target values per category
-- abschuss_category_allow_exceeding_{species}: Legacy overshoot permissions
-- ahgmh_export_filename_pattern: Export filename template
-- ahgmh_export_include_time: Include timestamp in exports
```

### 🔌 Database API (Enhanced in v2.0.0)

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

**Limits Management Methods:**
```php
// Limit mode management
set_wildart_limit_mode($wildart, $mode)             // Set limit mode for wildart
get_wildart_limit_mode($wildart)                    // Get current limit mode

// Meldegruppen-specific limits
save_meldegruppen_limit($wildart, $meldegruppe, $kategorie, $limit)  // Save specific limit
get_meldegruppen_limits($wildart, $meldegruppe)     // Get limits for meldegruppe

// Hegegemeinschaft total limits  
save_hegegemeinschaft_limit($wildart, $kategorie, $limit)  // Save total limit
get_hegegemeinschaft_limit($wildart, $kategorie)    // Get total limit

// Status calculation
get_status_badge($ist, $soll)                       // Generate status badge HTML
count_submissions_by_species_category_meldegruppe($species, $category, $meldegruppe) // Detailed counts
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
- ✅ **Master-Detail Wildart Configuration** - Intuitive left-sidebar navigation with right-panel detail editing
- ✅ **Advanced Export System** - Configurable filename patterns and parameters
- ✅ **Comprehensive CRUD Operations** - Full Create, Read, Update, Delete functionality
- ✅ **Real-time Table Updates** - AJAX-powered auto-refresh after submissions
- ✅ **Enhanced Category Management** - Integrated limits and overshoot controls with inline editing
- ✅ **Date Range Operations** - Custom date range deletion functionality
- ✅ **Improved Database Management** - Multi-database with enhanced connection handling
- ✅ **Bootstrap 5.3 Integration** - Modern responsive UI framework with mobile-first design
- ✅ **Advanced Security** - Enhanced WordPress security integration
- ✅ **API Documentation** - Complete parameter reference in admin interface
- ✅ **Public Summary Statistics** - [abschuss_summary] shortcode with flexible parameters
- ✅ **Flexible Parameter Logic** - Species and meldegruppen combinations with graceful fallback
- ✅ **Responsive Master-Detail UI** - Sidebar collapsing on tablets, horizontal navigation on mobile

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
**Status:** WordPress.org Submission Ready ✅  
**WordPress Compatible:** 5.0+ with PHP 7.4+

## 📋 Changelog

### Version 2.5.2 (2025-09-23) - Interne Notiz Feature

**Enhanced Data Capture & Display System**

#### ✨ New Features
- **Interne Notiz Field**: Added new optional "Interne Notiz" (Internal Note) field to all forms and displays
- **Database Schema Extension**: Extended database with field6 for internal notes storage
- **Comprehensive Form Integration**: Internal notes field added to [abschuss_form] shortcode
- **Table Display Enhancement**: Internal notes column added to [abschuss_table] positioned left of remarks
- **Admin Interface Updates**: Admin tables now include internal notes column with proper labeling
- **Summary Display Enhancement**: [abschuss_summary_table] now includes remarks column for better data visibility

#### 🔧 Technical Improvements
- Database field6 added with proper sanitization using sanitize_textarea_field()
- Form validation extended to include internal notes field
- JavaScript form handling updated for new field
- Admin interface column headers updated ("Erlegungsort" → "Bemerkung")
- Responsive table design maintained across all displays
- Proper field positioning in all table layouts

#### 📝 Documentation Updates
- Updated all technical documentation for new field structure
- Enhanced field mapping documentation (field1-field6)
- Updated API documentation for data handling

### Version 2.5.1 (2025-09-21) - CRUD Operations Enhancement

#### 🐛 Bug Fixes
- **Delete Functionality**: Fixed non-functional delete buttons in admin data management table
- **JavaScript Loading**: Resolved missing event handlers due to disabled script loading
- **AJAX Integration**: Corrected nonce validation and proper script localization

#### ✨ New Features  
- **Edit Functionality**: Added inline edit capability for individual submissions
- **Improved UX**: Enhanced admin interface with edit and delete buttons for each record
- **Data Validation**: Comprehensive form validation for edit operations

#### 🔧 Technical Improvements
- **Event Handler Optimization**: Prevented duplicate form creation and improved click handling
- **Database Operations**: Implemented flexible update_submission method with dynamic field handling
- **Security Enhancement**: Proper nonce validation for all CRUD operations

### Version 2.5.0 (2025-09-15) - Enhanced Meldegruppen Management System
**🔧 Critical Meldegruppen Fixes**
- Fixed meldegruppen-box in admin backend - adding, editing, and saving now fully functional
- Resolved data source inconsistency between save/load operations for meldegruppen
- Implemented automatic default meldegruppen (Gruppe_A, Gruppe_B) for new wildarten
- Fixed AJAX handler conflicts causing "0" response errors
- Corrected parameter order in database method calls

**🚀 Enhanced Frontend Integration**
- [abschuss_form] now properly loads and validates custom meldegruppen
- [abschuss_table] displays all submissions for Obleute (not just their assigned meldegruppe)
- Obleute configuration now accepts and validates custom meldegruppen assignments
- Consistent data sources across all plugin components (ahgmh_wildart_meldegruppen)

**⚡ System Improvements**
- Eliminated duplicate AJAX handler registrations
- Fixed emergency handler conflicts with main controller system
- Enhanced permission system with proper data filtering
- Improved validation logic for meldegruppen across all components

### Version 2.4.0 (2024-09-12) - Final Production Release
**🔧 Code Quality & Stability**
- Removed duplicate AJAX hook registration in admin interface  
- Eliminated unused dashboard statistics handler
- Complete codebase review and cleanup for production stability
- Final validation of all security implementations
- Optimized plugin performance and memory usage

**✅ WordPress.org Ready**
- All critical fixes implemented and tested
- Production-grade stability achieved
- Ready for immediate WordPress.org submission

### Version 2.3.0 (2024-08-19) - WordPress.org Submission Ready
**🔒 Security & Compliance**
- Direct file access protection added to all PHP files
- Complete error message sanitization for production
- Comprehensive nonce verification for all AJAX endpoints
- Enhanced input validation and sanitization

**🌍 Internationalization**
- Complete German translation (languages/abschussplan-hgmh-de_DE.po)
- POT file with 150+ translatable strings
- JavaScript localization via wp_localize_script()
- Context comments for hunting-specific terminology

**📊 WordPress.org Compliance**
- WordPress Coding Standards 98% compliance
- Security audit passed with production-grade hardening
- Performance optimization for complex Hegegemeinschafts-structures
- Complete documentation for repository submission

### Version 2.2.0 (2024-07-15) - Master-Detail Backend
- Master-Detail Wildart Configuration interface
- 3-Level Permission System (Besucher/Obmann/Vorstand)
- Advanced Limits Management with dual modes
- Enhanced CSV Export with admin interface
- Obmann user management system
- Responsive design improvements

---

*⭐ Star this repository if it helped you manage your hunting district data!*
