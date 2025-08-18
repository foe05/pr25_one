# AGENT.md - WordPress Plugin Project

## Project Overview
- **Project Name**: Abschussplan HGMH (Hunting Harvest Tracking for German Hunting Districts)
- **Repository**: https://github.com/foe05/pr25_one
- **Type**: WordPress Plugin for hunting submission tracking
- **Version**: 2.0.0
- **Status**: Production Ready - Fully Implemented
- **License**: GPLv3
- **Target Audience**: German hunting associations (Hegegemeinschaften)

## WordPress Plugin Structure
- **Main Plugin File**: `wp-content/plugins/abschussplan-hgmh/abschussplan-hgmh.php`
- **Core Classes**: Database handler, form handler, modern admin interface, table display
- **Templates**: PHP templates for frontend rendering with Bootstrap 5.3 integration
- **Assets**: Bootstrap 5.3 + Bootstrap Icons, enhanced CSS/JS with AJAX functionality
- **Database**: WordPress MySQL (default), with SQLite/PostgreSQL support
- **Admin Interface**: Modern tabbed interface with comprehensive CRUD operations and Master-Detail wildart configuration

## Development Commands
- **Local WordPress**: Set up local WordPress development environment
- **Plugin Testing**: WordPress Admin → Plugins → Activate/Deactivate
- **Database Debug**: Access `debug.php` and `debug-db.php` files
- **Error Logging**: Enable `WP_DEBUG` in wp-config.php

## Plugin Architecture
- **Shortcodes**: 5 main shortcodes (`[abschuss_form]`, `[abschuss_table]`, `[abschuss_admin]`, `[abschuss_summary]`, `[abschuss_limits]`)
- **AJAX Handlers**: Form submission, admin config, CSV export via WordPress AJAX
- **Database Tables**: `wp_ahgmh_submissions`, `wp_ahgmh_jagdbezirke`, `wp_ahgmh_meldegruppen_config`, `wp_ahgmh_meldegruppen_limits`
- **User Integration**: WordPress user authentication and capabilities

## Core Functionality
- **Form Submission**: Hunting harvest data entry with advanced validation
- **Data Management**: Complete CRUD operations for harvest records with real-time updates
- **CSV Export**: Advanced WordPress AJAX-based exports with configurable filename patterns
- **Admin Interface**: Modern tabbed interface (Dashboard, Data Management, Categories, Database, Wildarten-Konfiguration, CSV Export)
- **Master-Detail Wildart Configuration**: Intuitive left-sidebar wildart navigation with right-panel detail editing
- **Category Management**: Full CRUD for species and categories with integrated limit controls and inline editing
- **Date Range Operations**: Custom date range deletion functionality with HTML5 datepickers
- **Real-time Updates**: AJAX-powered table refreshing after form submissions
- **Multi-Database**: Enhanced support for MySQL (default), SQLite, PostgreSQL
- **Public Summary Statistics**: Flexible [abschuss_summary] shortcode with optional parameters for public access

## Security Features
- **WordPress Standards**: Uses WP nonce verification, user capabilities
- **Input Sanitization**: WordPress sanitization functions
- **SQL Protection**: $wpdb prepared statements
- **Role-Based Access**: Admin features require `manage_options` capability

## File Structure
```
wp-content/plugins/abschussplan-hgmh/
├── abschussplan-hgmh.php (main plugin file)
├── includes/ (core classes)
│   ├── class-database-handler.php
│   ├── class-form-handler.php
│   └── class-table-display.php
├── templates/ (frontend templates)
│   ├── form-template.php
│   ├── table-template.php
│   ├── summary-template.php
│   ├── admin-template-modern.php
│   └── admin-template-old.php
├── admin/ (admin functionality)
│   ├── class-admin-page-modern.php (tabbed admin interface)
│   ├── class-admin-page-legacy.php (legacy interface)
│   └── assets/ (admin CSS/JS)
│       ├── admin-modern.css
│       └── admin-modern.js
├── assets/ (frontend CSS/JS files)
│   ├── css/style.css
│   └── js/form-validation.js
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
- **Version 2.0.0**: Major update with modern admin interface and enhanced functionality
- **Bootstrap 5.3**: Modern responsive design framework integration with mobile-first approach
- **Master-Detail UI**: Wildart-centric configuration with left-sidebar navigation and right-panel detail editing
- **Responsive Design**: Sidebar collapsing on tablets (≤968px), horizontal navigation on mobile (≤600px)
- **AJAX Integration**: Real-time updates without page reloads, including wildart configuration management
- **Enhanced Security**: WordPress security best practices implemented
- **Comprehensive CRUD**: Full Create, Read, Update, Delete operations for all entities
- **Inline Editing**: Category and meldegruppe management with inline editing capabilities
- **Auto-Save**: Automatic saving of configuration changes with user feedback
- **Public Access**: [abschuss_summary] shortcode supports public access without authentication
- **Flexible Parameters**: Enhanced parameter logic for species and meldegruppe filtering

## Documentation Files
- **README.md**: Comprehensive user and developer documentation
- **ANFORDERUNGEN.md**: Detailed requirements documentation (German)
- **wordpress_plugin_konzept.md**: Technical concept and architecture documentation (German)
- **AGENT.md**: This file - development guidelines and project overview

## Current Workspace Structure
- **Repository Root**: `/home/foe/9_sideprojects/GITHUB/pr25_one`
- **WordPress Plugin**: `wp-content/plugins/abschussplan-hgmh/`
- **Plugin Archive**: Multiple ZIP versions available for distribution
- **Git Repository**: Active Git repository with version control
