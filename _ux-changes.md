# Admin UX Refactor - Change Log

## Overview

Complete refactoring of the WordPress admin interface for the Abschussplan HGMH plugin.
All admin views now use WordPress-native UI components (postbox, metabox, nav-tab-wrapper,
form-table, wp-list-table) instead of custom CSS classes and Bootstrap patterns. German
labels use proper i18n escaping throughout. Breaking changes are intentional (fresh install).

---

## Menu Structure (class-admin-page-modern.php)

### Before (5 items with emoji prefixes)
1. Dashboard
2. Meldungen
3. Import
4. Einstellungen (contained Wildarten config, Jagdbezirke, Logging, Export, DB config)
5. Obleute

### After (7 items, flat, no emojis)
1. **Dashboard** - overview stats and charts
2. **Meldungen** - submission management (3 tabs: Alle Meldungen, Uebersicht, Auswertungen)
3. **Wildarten** - promoted from Settings sub-tab to its own page (2 tabs: Konfiguration, Jagdbezirke)
4. **Obleute** - warden/assignment management
5. **Berichte** - combined page (4 tabs: Abschussplan-Erfuellung, Berichte erstellen, Geplante Berichte, Seitenaufrufe)
6. **Import / Export** - tabbed page (Import tab, Export tab)
7. **Einstellungen** - slimmed to 4 tabs (Datenbank, Export-Konfiguration, Logging, Migrationen)

---

## New Render Methods Added (class-admin-page-modern.php)

| Method | Purpose |
|---|---|
| `render_wildarten_page()` | Renders Wildarten config page with 2-tab navigation |
| `render_reports_page()` | Renders Reports/Compliance page with 4-tab navigation |
| `render_compliance_tab()` | Builds compliance data, delegates to AHGMH_Compliance_View |
| `render_reports_tab()` | Builds hunting seasons, delegates to AHGMH_Reports_View |
| `render_schedules_tab()` | Delegates to AHGMH_Schedule_Settings_View |
| `render_statistics_tab()` | Loads page views data, includes page-views-statistics.php |

---

## View File Changes

### admin/views/class-dashboard-view.php
- **Pattern**: postbox + postbox-header + hndle for all panels
- **Layout**: CSS grid for stats cards using postbox elements
- **Charts**: Inline flexbox + progress bar pattern (no external CSS)
- **i18n**: All strings wrapped in `esc_html__()`

### admin/views/class-compliance-view.php
- **Removed**: All Bootstrap classes (col-md-3, bg-success, bg-warning, bg-danger, badge, table-striped, table-hover)
- **Added**: WordPress postbox, widefat striped tables
- **Progress bars**: Inline CSS with WP color scheme (#00a32a, #dba617, #d63638, #2271b1)
- **Methods renamed**: `get_bootstrap_color()` -> `get_bar_color()`, `get_bootstrap_badge()` -> `get_badge_bg()`/`get_badge_color()`

### admin/views/class-reports-view.php
- **Removed**: ~200 lines of inline `<style>` CSS block
- **Layout**: Postbox-based two-column grid (config sidebar + preview panel)
- **Forms**: Fieldset with radio buttons, WordPress form-table for inputs
- **i18n**: All strings properly wrapped in `esc_html__()`

### admin/views/class-import-view.php
- **Removed**: Outer `<div class="wrap">` wrapper (now provided by render_import)
- **Pattern**: Postbox for each import step
- **Dropzone**: Inline styles instead of CSS classes
- **Progress**: Inline CSS progress bars

### admin/views/class-wildart-view.php
- **Layout**: Postbox-based master-detail grid (280px sidebar + 1fr detail)
- **Master list**: Items styled with inline flexbox, active state uses #f0f6fc background
- **Detail panel**: Sections separated by border-bottom with clear headings
- **Limits**: wp-list-table widefat striped (replaced custom classes)
- **Limit mode**: Fieldset with descriptive radio labels explaining each option
- **Help text**: Added description paragraphs for Categories, Meldegruppen, and Limits sections

### admin/views/class-schedule-settings-view.php
- **Removed**: ~400 lines of inline `<style>` CSS block (lines 387-770)
- **Removed**: ~375 lines of inline `<script>` JS block (lines 777-1155)
- **Statistics**: Postbox cards with dashicons, inline grid layout
- **Schedules list**: Postbox container, items use inline border/padding styles
- **Modal form**: Uses WordPress form-table pattern inside fieldsets
- **Status badges**: Inline styles with WP color scheme (replaced badge-success/badge-secondary classes)

### admin/views/page-views-statistics.php
- **i18n**: Replaced all bare `_e()` calls with `echo esc_html__()` for proper output escaping
- **Removed**: `<div class="wrap">` wrapper (now embedded in tab context)
- **Cards**: Postbox pattern replaces custom ahgmh-stat-card with box-shadow
- **Tables**: widefat striped / wp-list-table widefat striped
- **Sections**: All content sections wrapped in postbox + postbox-header + hndle
- **Nonces**: Wrapped in `esc_js()` for safe JavaScript embedding

### admin/views/page-migrations.php
- **i18n**: Replaced all bare `_e()` calls with `echo esc_html__()` for proper output escaping
- **Removed**: `<div class="wrap">` wrapper (now embedded in tab context)
- **Layout**: Version card + warning notice in CSS grid
- **Sections**: All content in postbox + postbox-header + hndle
- **Tables**: wp-list-table widefat striped
- **Log output**: Replaced emoji indicators with text prefixes [OK]/[FEHLER]/[INFO]
- **Nonces**: Wrapped in `esc_js()` for safe JavaScript embedding

### templates/admin-template-modern.php
- **Pattern**: poststuff / post-body / metabox-holder wrapper for WP-standard layout
- **Labels**: Improved ("Abschuss (Soll)" -> "Abschuss-Kontingente (Soll) verwalten")
- **Status badges**: Inline styles with proper color coding
- **Description**: Added help text explaining Soll/Ist comparison

---

## Modified Render Methods (class-admin-page-modern.php)

| Method | Changes |
|---|---|
| `render_settings()` | Removed wildart-config and jagdbezirke tabs; now 4 tabs; uses nav-tab-wrapper |
| `render_data_management()` | Uses nav-tab-wrapper; default tab -> 'submissions'; removed 'export' tab; 3 tabs remain |
| `render_import()` | Tabbed page with Import + Export sub-tabs using nav-tab-wrapper |
| `render_obmann_management()` | Postbox pattern with form-table; all strings use esc_html__() |
| `render_obmann_assignments_table()` | Column headers translated with esc_html__(); button titles use esc_attr__() |

---

## Design Principles Applied

1. **WordPress-native components**: postbox, postbox-header, hndle, nav-tab-wrapper, form-table, widefat, wp-list-table
2. **No Bootstrap**: Removed all Bootstrap classes (col-md-*, bg-*, badge, table-striped)
3. **No custom CSS classes for layout**: Inline styles for spacing/grid; WP classes for structure
4. **Consistent color scheme**: #2271b1 (blue), #00a32a (green), #dba617 (yellow), #d63638 (red), #646970 (muted text), #1d2327 (dark text)
5. **Proper i18n**: All user-visible strings use `esc_html__()` / `esc_attr__()` / `esc_js()` with text domain `abschussplan-hgmh`
6. **No emoji in PHP**: Replaced emoji indicators with text alternatives

---

## Files NOT Modified (out of scope)

- `admin/class-admin-page-modern.php` AJAX handlers (lines ~860-5100)
- `includes/`, `controllers/`, `services/` directories
- `frontend/` directory
- `admin/assets/*.js`, `admin/assets/*.css` files
- Main plugin file (`abschussplan-hgmh.php`)
