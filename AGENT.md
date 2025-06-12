# AGENT.md - WordPress Plugin Project

## Project Type
- **Type**: WordPress Plugin for hunting submission tracking (German: Abschussplan HGMH)
- **Version**: 1.5.0
- **Status**: Production Ready

## WordPress Plugin Structure
- **Main Plugin File**: `wp-content/plugins/abschussplan-hgmh/abschussplan-hgmh.php`
- **Core Classes**: Database handler, form handler, table display, admin page
- **Templates**: Jinja-style PHP templates for frontend rendering
- **Assets**: Bootstrap 5.3 + Bootstrap Icons, custom CSS/JS
- **Database**: WordPress MySQL (default), with SQLite/PostgreSQL support

## Development Commands
- **Local WordPress**: Set up local WordPress development environment
- **Plugin Testing**: WordPress Admin → Plugins → Activate/Deactivate
- **Database Debug**: Access `debug.php` and `debug-db.php` files
- **Error Logging**: Enable `WP_DEBUG` in wp-config.php

## Plugin Architecture
- **Shortcodes**: 5 main shortcodes (`[abschuss_form]`, `[abschuss_table]`, `[abschuss_admin]`, `[abschuss_summary]`, `[abschuss_limits]`)
- **AJAX Handlers**: Form submission, admin config, CSV export via WordPress AJAX
- **Database Tables**: `wp_ahgmh_submissions`, `wp_ahgmh_jagdbezirke`
- **User Integration**: WordPress user authentication and capabilities

## Core Functionality
- **Form Submission**: Hunting harvest data entry with validation
- **Data Management**: CRUD operations for harvest records
- **CSV Export**: WordPress AJAX-based data exports with filters
- **Admin Interface**: Database config, species management, limits configuration
- **Multi-Database**: Support for MySQL (default), SQLite, PostgreSQL

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
├── templates/ (frontend templates)  
├── admin/ (admin functionality)
├── assets/ (CSS/JS files)
├── debug.php (development debugging)
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
