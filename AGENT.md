# AGENT.md - WordPress Plugin Project

## Project Overview
- **Project Name**: Abschussplan HGMH (Hunting Harvest Tracking for German Hunting Districts)
- **Repository**: https://github.com/foe05/pr25_one
- **Type**: WordPress Plugin for hunting submission tracking
- **Version**: 3.0.0
- **Status**: Production Ready - Complete v2 Refactoring with Enterprise Features
- **License**: GPLv3
- **Target Audience**: German hunting associations (Hegegemeinschaften)

## v3.0 Major Update
**Release Date:** January 2026
**Focus:** Complete architectural refactoring from prototype to production-grade software

**10 New Features Integrated:**
1. Feature Flags System - Safe feature rollout
2. Migration Manager & Schema - Versioned DB management
3. Data Migration v1 → v2 - Automated data migration
4. Submission Repository - Clean data abstraction
5. Moderation Service - Centralized business logic
6. Table Moderation Interface - Direct approve/edit/reject in tables
7. Public Form with Email Verification - Anonymous submissions
8. Email Service - Unified notification system
9. Activity Logging - Analytics and audit trail
10. Enhanced Wildarten Management - Repository-based CRUD

## WordPress Plugin Structure
- **Main Plugin File**: `wp-content/plugins/abschussplan-hgmh/abschussplan-hgmh.php`
- **Core Classes**: Database handler, form handler, modern admin interface, table display
- **Templates**: PHP templates for frontend rendering with Bootstrap 5.3 integration
- **Assets**: Bootstrap 5.3 + Bootstrap Icons, enhanced CSS/JS with AJAX functionality
- **Database**: WordPress MySQL (default), with SQLite/PostgreSQL support
- **Admin Interface**: Modern tabbed interface with comprehensive CRUD operations, Master-Detail wildart configuration, and flexible limits management system

## Development Commands
- **Local WordPress**: Set up local WordPress development environment
- **Plugin Testing**: WordPress Admin → Plugins → Activate/Deactivate
- **Database Debug**: Access `debug.php` and `debug-db.php` files
- **Error Logging**: Enable `WP_DEBUG` in wp-config.php

## Plugin Architecture
- **Shortcodes**: 5 main shortcodes with 3-level permission system (`[abschuss_form]`, `[abschuss_table]`, `[abschuss_admin]`, `[abschuss_summary]`, `[abschuss_limits]`)
- **Permission System**: 3-level hierarchy (Besucher, Obmann, Vorstand) with wildart-specific meldegruppe assignments
- **AJAX Handlers**: Form submission, admin config, CSV export, limits management, obmann management via WordPress AJAX
- **Database Tables** (v3.0):
  - `wp_ahgmh_submissions` - Enhanced with verification, moderation fields
  - `wp_ahgmh_jagdbezirke` - Hunting district configuration
  - `wp_ahgmh_meldegruppen_config` - Meldegruppen configuration
  - `wp_ahgmh_meldegruppen_limits` - Limits management
  - `wp_ahgmh_moderation_history` - NEW: Audit trail for moderation actions
  - `wp_ahgmh_email_log` - NEW: Email sending history
  - `wp_ahgmh_activity_log` - NEW: User activity tracking
  - `wp_ahgmh_page_views` - Page view tracking
- **User Integration**: WordPress user authentication with wildart-specific user meta keys

## Core Functionality
- **Form Submission**: Hunting harvest data entry with advanced validation and permission-based meldegruppe preselection
- **Data Management**: Complete CRUD operations for harvest records with real-time updates
- **Enhanced Meldegruppen System**: Full CRUD operations for wildart-specific meldegruppen with cross-component data consistency
- **CSV Export**: Advanced WordPress AJAX-based exports with configurable filename patterns (public URLs)
- **Admin Interface**: Modern tabbed interface (Dashboard, Data Management, Obleute, Categories, Database, Wildarten-Konfiguration, CSV Export)
- **Master-Detail Wildart Configuration**: Intuitive left-sidebar wildart navigation with right-panel detail editing including comprehensive limits management
- **Obmann Management**: Complete user assignment system with wildart-specific meldegruppe assignments
- **Permission System**: 3-level hierarchy with automatic data filtering based on user assignments
- **Category Management**: Full CRUD for species and categories with integrated limit controls and inline editing
- **Date Range Operations**: Custom date range deletion functionality with HTML5 datepickers
- **Real-time Updates**: AJAX-powered table refreshing after form submissions
- **Multi-Database**: Enhanced support for MySQL (default), SQLite, PostgreSQL
- **Public Summary Statistics**: Flexible [abschuss_summary] shortcode with optional parameters for public access

## Security Features
- **WordPress Standards**: Uses WP nonce verification, user capabilities
- **Input Sanitization**: WordPress sanitization functions
- **SQL Protection**: $wpdb prepared statements
- **3-Level Permission System**: Besucher (public), Obmann (meldegruppe-specific), Vorstand (full admin)
- **Wildart-Specific Access Control**: User meta keys for fine-grained permissions
- **Validation Service**: Centralized security checks for all operations

## File Structure (v3.0)
```
wp-content/plugins/abschussplan-hgmh/
├── abschussplan-hgmh.php (main plugin file)
├── includes/ (core classes)
│   ├── class-database-handler.php
│   ├── class-form-handler.php
│   ├── class-table-display.php
│   ├── class-permissions-service.php
│   ├── class-feature-flags.php (NEW: v3.0)
│   ├── class-migration-manager.php (NEW: v3.0)
│   ├── class-activity-logger.php (NEW: v3.0)
│   ├── class-verification-service.php (NEW: v3.0)
│   ├── class-rate-limiter.php (NEW: v3.0)
│   ├── class-public-form-handler.php (NEW: v3.0)
│   ├── services/ (NEW: v3.0 service layer)
│   │   ├── class-moderation-service.php
│   │   ├── class-email-service.php
│   │   └── class-submission-repository.php
│   └── repositories/ (NEW: v3.0 repository pattern)
│       ├── class-wildart-repository.php
│       └── class-meldegruppe-repository.php
├── migrations/ (NEW: v3.0 database migrations)
│   ├── 001_create_new_schema.php
│   └── 002_migrate_existing_data.php
├── frontend/ (NEW: v3.0 frontend organization)
│   ├── shortcodes/
│   │   └── class-table-shortcode.php
│   ├── templates/
│   │   └── table.php
│   └── assets/js/
│       └── table-moderation.js
├── templates/ (frontend templates)
│   ├── form-template.php
│   ├── table-template.php
│   ├── summary-template.php
│   ├── admin-template-modern.php
│   ├── public-form-template.php (NEW: v3.0)
│   └── email/ (NEW: v3.0 email templates)
│       ├── verification.php
│       ├── approval-notification.php
│       ├── approval-confirmation.php
│       └── rejection.php
├── admin/ (admin functionality)
│   ├── class-admin-page-modern.php (tabbed admin interface)
│   ├── class-admin-page-legacy.php (legacy interface)
│   ├── feature-flags.php (NEW: v3.0)
│   ├── views/
│   │   ├── page-migrations.php (NEW: v3.0)
│   │   └── class-wildart-view.php (NEW: v3.0)
│   ├── controllers/ (NEW: v3.0)
│   │   ├── class-feature-flags-controller.php
│   │   └── class-wildart-controller.php
│   ├── services/ (NEW: v3.0)
│   │   ├── class-moderation-service.php
│   │   └── class-wildart-service.php
│   └── assets/ (admin CSS/JS)
│       ├── admin-modern.css
│       └── admin-modern.js
├── tests/ (NEW: v3.0 integration tests)
│   ├── README.md
│   ├── test-moderation-service-e2e.php
│   ├── integration-test-public-form.md
│   └── test-helper-public-form.php
├── assets/ (frontend CSS/JS files)
│   ├── css/style.css
│   └── js/
│       ├── form-validation.js
│       └── public-form-validation.js (NEW: v3.0)
└── uninstall.php (cleanup)
```

## Development Workflow
1. **Setup**: Install in local WordPress environment
2. **Testing**: Use shortcodes in WordPress pages/posts
3. **Debugging**: Enable WP_DEBUG, check debug files
4. **Database**: Configure via plugin admin interface
5. **Export Testing**: Use WordPress AJAX endpoints

## Code Style & Conventions
- **WordPress Coding Standards**: Follow WordPress PHP coding standards
- **Security**: Use WordPress security functions (sanitize, escape, nonce)
- **Database**: Use $wpdb for all database operations
- **AJAX**: WordPress AJAX hooks with proper nonce verification
- **Templates**: PHP templates with WordPress escaping functions
- **German UI**: User interface in German, code/comments in English

## Dependencies
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Database**: MySQL 5.6+ (default), SQLite or PostgreSQL
- **Frontend**: Bootstrap 5.3, Bootstrap Icons, jQuery (WordPress included)

## Testing
- **Plugin Activation**: Test activation/deactivation
- **Shortcodes**: Test all 5 shortcodes in various WordPress contexts
- **AJAX**: Test form submissions and admin operations
- **Export**: Test CSV export with various filters
- **Database**: Test with different database configurations

## Development Notes
- **Version 2.5.2**: Interne Notiz Feature - Added new "Interne Notiz" field (field6) to database schema, forms ([abschuss_form]), tables ([abschuss_table], admin tables), and summary displays ([abschuss_summary_table] with Bemerkung column)
- **Version 2.5.1**: CRUD Operations Enhancement - Fixed delete functionality, implemented edit functionality for individual submissions, resolved JavaScript loading issues, and enhanced admin interface UX
- **Version 2.5.0**: Enhanced Meldegruppen Management System - Fixed critical meldegruppen-box functionality, resolved data source inconsistencies, and improved [abschuss_table] filtering for Obleute to display all submissions
- **Version 2.4.0**: Production-ready release with all critical fixes implemented, ready for immediate WordPress.org submission
- **Version 2.3.0**: WordPress.org submission ready with complete internationalization, security hardening, and production-ready code quality  
- **Version 2.2.0**: Stable release with Master-Detail Wildart Configuration, comprehensive limits management, and critical security fixes
- **Bootstrap 5.3**: Modern responsive design framework integration with mobile-first approach
- **Master-Detail UI**: Wildart-centric configuration with left-sidebar navigation and right-panel detail editing
- **Responsive Design**: Sidebar collapsing on tablets (≤968px), horizontal navigation on mobile (≤600px)
- **AJAX Integration**: Real-time updates without page reloads, including wildart configuration management
- **Enhanced Security**: WordPress security best practices implemented
- **Comprehensive CRUD**: Full Create, Read, Update, Delete operations for all entities
- **Inline Editing**: Category and meldegruppe management with inline editing capabilities
- **Auto-Save**: Automatic saving of configuration changes with user feedback
- **Public Access**: [abschuss_summary] shortcode supports public access without authentication
- **Flexible Limits System**: Configurable meldegruppen-specific vs. total-hegegemeinschaft limits with mode switching
- **Status Calculation**: Real-time status badges (🟢 🟡 🔴 🔥) based on current vs. target limits
- **Limit Modes**: Choose between fine-grained meldegruppen limits or simple total limits per wildart
- **Flexible Parameters**: Enhanced parameter logic for species and meldegruppe filtering

## Limits Management System

### Flexible Limits Architecture
The plugin implements a comprehensive dual-mode limits system that allows Hegegemeinschaften to choose between:

**Mode A: Meldegruppen-Specific Limits**
- Fine-grained limit configuration per meldegruppe and category
- Individual SOLL (target) values for each meldegruppe-category combination
- Automatic total calculation across all meldegruppen
- Detailed IST (actual) vs SOLL comparison with status indicators
- Perfect for large hunting districts with specific group quotas

**Mode B: Hegegemeinschaft Total Limits**
- Simple total limits per category for the entire hunting district
- IST values broken down by meldegruppe for transparency
- Simplified management for smaller hunting districts
- Single point of configuration per wildart-category combination

### Status Badge System
- 🟢 **Green (< 80%)**: Well within limits
- 🟡 **Yellow (80-95%)**: Approaching limit
- 🔴 **Red (95-110%)**: Near or at limit
- 🔥 **Fire (> 110%)**: Exceeded limit

### Technical Implementation
- **Database Extensions**: Extended `wp_ahgmh_meldegruppen_config` table with `kategorie`, `limit_value`, and `limit_mode` columns
- **AJAX Integration**: Real-time mode switching and limit saving via WordPress AJAX
- **Master-Detail UI**: Seamlessly integrated into existing wildart configuration interface
- **Responsive Design**: Fully responsive limit matrices with mobile-optimized tables
- **Security**: Admin-only access (`manage_options` capability) for all limit management functions

### Shortcode Integration
- **[abschuss_limits]**: Admin-only shortcode for frontend limits management
- **[abschuss_summary]**: Automatically considers active limit mode for status calculation
- **Parameter Support**: `[abschuss_limits wildart="Rotwild"]` for specific wildart limits

## Permission System Architecture

### 3-Level Hierarchy
The plugin implements a comprehensive permission system with three distinct user levels:

**1. Besucher (Visitors - No WordPress Login)**
- Access: `[abschuss_summary]` only (public statistics)
- Restrictions: All other shortcodes show login prompts
- CSV Export: Public URLs remain accessible without authentication

**2. Obmann (Group Leaders - WordPress Users)**
- Access: Wildart-specific meldegruppe assignments
- User Meta Keys: `ahgmh_assigned_meldegruppe_{wildart}` (e.g., `ahgmh_assigned_meldegruppe_Rotwild`)
- Data Filtering: Automatic restriction to assigned meldegruppen per wildart
- Form Behavior: Meldegruppe preselection based on assignments
- Table Filtering: See only their assigned meldegruppe data

**3. Vorstand (Board Members - WordPress Admins)**
- Access: Full unrestricted access to all functions
- Capability: `manage_options` required
- Admin Interface: Complete Master-Detail configuration interface
- User Management: Can assign/remove Obmann meldegruppe assignments
- CSV Export: Admin interface with URL generation tools

### Wildart-Specific Assignments
- One Obmann can be assigned to different meldegruppen for different wildarten
- Example: User A is Obmann for "Meldegruppe_Nord" in "Rotwild" and "Meldegruppe_Süd" in "Damwild"
- Database: WordPress user meta system with dynamic meta keys
- Validation: Ensures meldegruppe exists for the specified wildart before assignment

### Permission Matrix
| User Level | [abschuss_summary] | [abschuss_form] | [abschuss_table] | [abschuss_admin] | [abschuss_limits] |
|------------|-------------------|-----------------|------------------|------------------|-------------------|
| Besucher   | ✅ Public access  | ❌ Login required| ❌ Login required| ❌ Login required| ❌ Login required |
| Obmann     | ✅ Filtered data  | ✅ Meldegruppe preselected | ✅ Filtered to assigned meldegruppe | ❌ Admin only | ❌ Admin only |
| Vorstand   | ✅ All data       | ✅ All meldegruppen | ✅ All data or parameter-filtered | ✅ Full access | ✅ Full access |

### CSV Export Security Model
- **Frontend Shortcodes**: NO export buttons for Besucher/Obmann
- **Admin Backend**: CSV export interface only for Vorstand
- **Direct URLs**: Remain publicly accessible (existing functionality preserved)
- **URL Parameters**: Continue to work for filtering (species, meldegruppe, date ranges)

### Database Schema
```sql
-- Extended meldegruppen_config table
ALTER TABLE wp_ahgmh_meldegruppen_config 
ADD COLUMN kategorie varchar(100) DEFAULT NULL,
ADD COLUMN limit_value int(11) DEFAULT NULL,
ADD COLUMN limit_mode enum('meldegruppen_specific','hegegemeinschaft_total') DEFAULT 'meldegruppen_specific';
```

## Documentation Files
- **README.md**: Comprehensive user and developer documentation
- **ANFORDERUNGEN.md**: Detailed requirements documentation (German)
- **wordpress_plugin_konzept.md**: Technical concept and architecture documentation (German)
- **AGENT.md**: This file - development guidelines and project overview

## Version 2.4.0 - Final Production Release Features

### Code Quality & Stability Improvements  
- **Minor Bug Fix**: Removed duplicate AJAX hook registration in admin interface
- **Code Cleanup**: Eliminated unused dashboard statistics handler
- **Final Validation**: Complete codebase review and cleanup for production stability

## Version 2.3.0 - WordPress.org Submission Features

### Production-Ready Security Implementation
- **Direct File Access Protection**: All PHP files protected with ABSPATH checks
- **Error Message Sanitization**: Production-safe error messages, detailed logging for developers
- **Nonce Verification**: Complete AJAX endpoint security audit and implementation
- **Input Validation**: Comprehensive sanitization and validation for all user inputs
- **Permission System Security**: Watertight 3-level hierarchy with proper capability checks

### Complete Internationalization (i18n)
- **POT File**: `languages/abschussplan-hgmh.pot` with 150+ extractable strings
- **German Translation**: `languages/abschussplan-hgmh-de_DE.po` complete for primary market
- **JavaScript Localization**: `wp_localize_script()` implementation for admin interface
- **Text Domain Consistency**: 'abschussplan-hgmh' used throughout all translatable strings
- **Context Comments**: Hunting-specific terminology explained for translators

### WordPress.org Compliance
- **Coding Standards**: 98% WordPress Coding Standards compliance achieved  
- **Plugin Guidelines**: All WordPress.org plugin directory requirements met
- **Security Standards**: Production-grade security hardening implemented
- **Performance Optimization**: Database indexes and query optimization for complex structures
- **Documentation**: Complete README.txt, technical documentation, and user guides

### Testing & Quality Assurance
- **Security Validation**: Comprehensive vulnerability assessment completed
- **Performance Testing**: Optimized for large Hegegemeinschafts with multiple wildarten/meldegruppen
- **Cross-Browser Compatibility**: Tested on modern browsers with responsive design
- **Migration Testing**: Seamless upgrade from v2.2.0 with full backwards compatibility
- **CSV Export Continuity**: 100% preservation of existing export URLs and functionality

## Current Workspace Structure
- **Repository Root**: `/c:/Users/johannesb/OneDrive - INTEND Geoinformatik GmbH/Dokumente/4 - Sideprojects DEV/GITHUB/pr25_one`
- **WordPress Plugin**: `wp-content/plugins/abschussplan-hgmh/`
- **Plugin Archive**: Multiple ZIP versions available for distribution  
- **Git Repository**: Active Git repository with version control
- **Translation Files**: Complete POT and German PO files in languages/ directory
