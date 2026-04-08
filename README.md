# Abschussplan HGMH - WordPress Plugin

**Version:** 26.1.0
**Status:** Production Ready - Quality & Security Sprint Complete
**Type:** WordPress Plugin for German Hegegemeinschaften Management

## 🎯 Overview

The **Abschussplan HGMH** plugin is a comprehensive WordPress solution for digital management of hunting reports in German hunting districts. Version 3.0 represents a complete architectural refactoring from prototype to production-grade software with enterprise features.

### ✨ Core Features (v2.x)
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

### 🛡️ NEW in v26.1.0 - Quality & Security Sprint

- ✅ **Security Hardening** - 15+ vulnerabilities fixed (SQL injection, missing nonce checks, unsanitized input, unauthenticated export)
- ✅ **Admin UX Overhaul** - Restructured menu (7 pages), WordPress-native UI components, no Bootstrap in admin
- ✅ **WCAG 2.1 AA Accessibility** - ARIA labels, focus management, keyboard navigation, screen reader support, 44px touch targets
- ✅ **Code Quality** - Dead code removed (11 files), WPCS formatting, i18n consistency, error handling improvements
- ✅ **Frontend Improvements** - Responsive card layouts, eliminated alert() dialogs, fixed HTML entity escaping

### 🚀 NEW in v3.0 - Enterprise Features

**Phase 1: Foundation**
- ✅ **Feature Flags System** - Safe, gradual rollout of new features with admin UI
- ✅ **Migration Manager** - Versioned database schema management with automated migrations
- ✅ **Data Migration v1 → v2** - Seamless migration of existing data to new normalized schema

**Phase 2: Architecture**
- ✅ **Repository Pattern** - Clean data abstraction layer with Submission Repository
- ✅ **Moderation Service** - Centralized business logic for approve/reject/edit workflows
- ✅ **Email Service** - Unified email notification system with templating

**Phase 3: Advanced Features**
- ✅ **Table Moderation Interface** - Direct approve/edit/reject actions in [abschuss_table] shortcode
- ✅ **Public Form with Verification** - Anonymous submissions with email verification workflow
- ✅ **Activity Logging** - Comprehensive user activity tracking for analytics and compliance
- ✅ **Enhanced Wildarten Management** - Repository-based CRUD with drag & drop sorting

**Technical Improvements:**
- 🏗️ **Clean Architecture** - Service layer, repository pattern, separation of concerns
- 🔐 **Enhanced Security** - Rate limiting, verification tokens, moderation workflows
- 📊 **Audit Trail** - Complete activity and moderation history logging
- 📧 **Email System** - Transactional emails for verification, approval, rejection
- 🎯 **Feature Toggles** - Safe deployment with feature flags
- 🗄️ **Normalized Schema** - 6 new tables with semantic columns replacing generic field1-6

---

## 📦 Installation

### WordPress Plugin Installation
1. Upload `wp-content/plugins/abschussplan-hgmh/` to your WordPress installation
2. Activate plugin in WordPress Admin Panel
3. Database migrations run automatically on activation
4. Configure feature flags in Admin → Feature Flags (if needed)
5. Use shortcodes in pages/posts (see [Shortcode Reference](#-shortcode-reference))

### Requirements
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Database**: MySQL 5.6+ (default), SQLite, or PostgreSQL 9.0+

---

## 🏗️ Architecture (v3.0)

### Database Schema
**New Tables (v3.0):**
- `wp_ahgmh_submissions` - Enhanced with status tracking, verification, moderation fields
- `wp_ahgmh_moderation_history` - Complete audit trail for moderation actions
- `wp_ahgmh_email_log` - Email sending history for debugging and compliance
- `wp_ahgmh_activity_log` - User activity tracking for analytics
- `wp_ahgmh_meldegruppen_config` - Enhanced meldegruppen configuration
- `wp_ahgmh_jagdbezirke` - Hunting district management

### Service Layer
- **Moderation Service** - Business logic for submission workflows
- **Email Service** - Centralized email notifications with templates
- **Verification Service** - Token-based email verification
- **Activity Logger** - User action tracking with GDPR-compliant cleanup

### Repository Pattern
- **Submission Repository** - Data access abstraction for submissions
- **Wildart Repository** - CRUD operations for wildarten
- **Meldegruppe Repository** - CRUD operations for meldegruppen

---

## 📊 Advanced Export System

### 🔗 Export URLs & Parameters
Access CSV exports via WordPress AJAX endpoints with extensive configuration:

**Base URL:**
```
https://your-domain.com/wp-admin/admin-ajax.php?action=ahgmh_export_csv
```

**Parameters:**
- `species` - Filter by game species (e.g., "Rotwild", "Rehwild")
- `meldegruppe` - Filter by specific meldegruppe
- `category` - Filter by category
- `start_date` - Start date (YYYY-MM-DD)
- `end_date` - End date (YYYY-MM-DD)
- `filename` - Custom filename pattern (supports {species}, {date}, {meldegruppe} placeholders)

**Example URLs:**
```bash
# All Rotwild submissions
/wp-admin/admin-ajax.php?action=ahgmh_export_csv&species=Rotwild

# Specific meldegruppe with date range
/wp-admin/admin-ajax.php?action=ahgmh_export_csv&species=Rehwild&meldegruppe=Gruppe_A&start_date=2024-01-01&end_date=2024-12-31

# Custom filename
/wp-admin/admin-ajax.php?action=ahgmh_export_csv&species=Rotwild&filename=Abschuss_{species}_{date}.csv
```

---

## 🔧 Shortcode Reference

### [abschuss_form]
**Digital submission form for hunters**
- Auto-detects user role and pre-selects meldegruppe (for Obmänner)
- Full validation with real-time feedback
- Responsive Bootstrap 5 design

**Parameters:** None
**Permissions:** Logged-in users only

---

### [abschuss_table]
**Interactive data table with moderation (v3.0 NEW)**
- Real-time AJAX updates
- Direct approve/edit/reject actions for Obmänner
- Sorting, filtering by species/meldegruppe
- Export to CSV functionality

**Parameters:**
- `species` (optional) - Filter by species
- `meldegruppe` (optional) - Filter by meldegruppe

**Permissions:**
- Vorstand: Full access to all data
- Obmann: Access to assigned meldegruppen only

**Example:**
```
[abschuss_table species="Rotwild" meldegruppe="Gruppe_A"]
```

---

### [abschuss_admin]
**Comprehensive admin interface**
- Multi-tab navigation (Dashboard, Data, Obmänner, Categories, Wildarten)
- Full CRUD operations
- Obmann assignment management
- CSV export configuration

**Parameters:** None
**Permissions:** Administrators only (`manage_options` capability)

---

### [abschuss_summary]
**Public statistics display**
- Aggregate data by species and meldegruppe
- No authentication required (public access)
- Real-time status indicators

**Parameters:**
- `show_meldegruppen` (optional, default: false) - Show meldegruppe breakdown

**Example:**
```
[abschuss_summary show_meldegruppen="true"]
```

---

### [abschuss_limits]
**Limits configuration interface**
- Dual-mode: Meldegruppen-specific vs. Total limits
- Visual limit/current comparison
- Real-time updates via AJAX

**Parameters:** None
**Permissions:** Administrators only

---

### [abschuss_public_form] (v3.0 NEW)
**Anonymous submission form with email verification**
- No login required
- Email verification workflow
- Rate limiting for abuse prevention
- Captcha integration

**Parameters:** None
**Permissions:** Public access

---

## 🔐 Security Features

### WordPress Standards
- WP nonce verification for all AJAX requests
- User capability checks (`manage_options`, role-based)
- WordPress sanitization/escaping functions

### v3.0 Enhanced Security
- **Rate Limiting** - Prevent abuse of public forms
- **Email Verification** - Token-based verification for anonymous submissions
- **Moderation Workflow** - Multi-stage approval process
- **Activity Logging** - Complete audit trail for compliance
- **Input Validation** - Enhanced server-side validation for all inputs

---

## 🆕 v3.0 Migration Guide

### Automatic Migration
The plugin automatically migrates your data on first activation of v3.0:
1. Backup performed automatically
2. New schema tables created
3. Existing data migrated to new structure
4. Feature flags initialized (all OFF by default)

### Manual Migration (if needed)
```bash
# Check migration status
wp eval 'echo get_option("ahgmh_db_version");'

# Force migration
wp eval 'do_action("ahgmh_run_migrations");'
```

### Rollback (Emergency)
```bash
# Deactivate plugin
wp plugin deactivate abschussplan-hgmh

# Restore database backup
mysql -u user -p database < backup.sql

# Reinstall v2.x
```

---

## 🎛️ Feature Flags (v3.0)

Access via **Admin → Feature Flags**

Available Flags:
- `use_new_db_schema` - Enable new database schema (required for v3.0 features)
- `use_public_form` - Enable anonymous submissions
- `use_moderation` - Enable moderation workflow
- `use_activity_log` - Enable activity tracking

**Best Practice:** Enable flags gradually to test each feature independently.

---

## 📈 Activity Logging (v3.0)

### Tracked Events
- User logins
- Submission creation/editing
- Moderation actions (approve/reject)
- Configuration changes
- Export operations

### GDPR Compliance
- Automatic cleanup after 90 days
- IP addresses hashed (SHA-256)
- User consent integration

### Access Logs
```php
// In your theme/plugin
$logger = new AHGMH_Activity_Logger();
$stats = $logger->get_stats('user', $user_id);
```

---

## 🧪 Testing

### Manual Testing
1. Activate plugin in test environment
2. Run migrations (automatic)
3. Test each shortcode
4. Verify permissions (Besucher/Obmann/Vorstand)
5. Test moderation workflow
6. Verify email notifications

### Integration Tests
Located in `wp-content/plugins/abschussplan-hgmh/tests/`
- Moderation service tests
- Public form integration tests
- Email service tests

---

## 📚 Documentation

- **AGENT.md** - Technical architecture and development guide
- **ANFORDERUNGEN.md** - Complete requirements documentation (German)
- **CHANGELOG.md** - Version history and release notes
- **wordpress_plugin_konzept.md** - Original concept document

---

## 🐛 Troubleshooting

### Common Issues

**Migration fails:**
```bash
# Check PHP version
php -v  # Must be 7.4+

# Check database permissions
SHOW GRANTS FOR 'your_user'@'localhost';

# Enable WordPress debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

**Feature flags not working:**
- Clear WordPress object cache
- Verify admin permissions (`manage_options`)
- Check `.auto-claude-security.json` exists

**Emails not sending:**
- Verify WordPress email configuration
- Check `wp_ahgmh_email_log` table for errors
- Test with WP Mail SMTP plugin

---

## 🤝 Contributing

This plugin follows WordPress Coding Standards:
- PSR-4 autoloading
- WPCS formatting
- Security best practices
- Comprehensive documentation

---

## 📄 License

GPLv3 or later
https://www.gnu.org/licenses/gpl-3.0.html

---

## 👥 Credits

**Developer:** Johannes (Förster & Software Engineer)
**Auto-Claude Integration:** AI-powered development workflow
**WordPress Community:** For excellent documentation and standards

---

**Version 26.1.0** - Quality & Security Sprint: Critical security fixes, admin UX overhaul, WCAG 2.1 AA accessibility, code quality improvements (April 2026)
**Version 3.0.0** - Major release with complete architectural refactoring (January 2026)
**Version 2.5.2** - Enhanced Meldegruppen Management System (September 2025)
