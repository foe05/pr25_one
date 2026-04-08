# Security Audit Report -- Abschussplan HGMH Plugin

**Date:** 2026-04-08
**Scope:** All PHP files in `wp-content/plugins/abschussplan-hgmh/`
**Categories audited:** SQL Injection, Missing Nonce Checks, Missing Capability Checks, Unsanitized Input, Unescaped Output, File Inclusion Vulnerabilities, Insecure AJAX Handlers

---

## Summary

- **15+ vulnerabilities found and fixed** across 10 PHP files
- **No file inclusion vulnerabilities** found -- all `include`/`require` use `AHGMH_PLUGIN_DIR` constant
- **No unescaped output vulnerabilities** in production code -- templates use `esc_html()`/`esc_attr()` consistently
- **REST API endpoints** are properly secured with `permission_callback` and `sanitize_callback`
- **Centralized security service** (`AHGMH_Validation_Service::verify_ajax_request()`) is well-designed and used by most admin controllers

---

## Findings and Fixes

### 1. SQL Injection Vulnerabilities

#### 1.1 `admin/class-admin-page-modern.php` -- `ajax_rename_table()` (~line 3489)
**Severity:** Critical
**Issue:** Raw variable interpolation in `SHOW TABLES LIKE` and `RENAME TABLE` queries.
```php
// BEFORE:
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_table'");
$result = $wpdb->query("RENAME TABLE `$current_table` TO `$new_table`");
```
**Fix:**
```php
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $new_table));
$result = $wpdb->query(sprintf("RENAME TABLE `%s` TO `%s`", esc_sql($current_table), esc_sql($new_table)));
```

#### 1.2 `admin/class-admin-page-modern.php` -- `get_available_years()` (~line 1970)
**Severity:** Medium
**Issue:** Table name interpolated without escaping.
**Fix:** Wrapped table name with `esc_sql()`:
```php
"FROM `" . esc_sql($table_name) . "`"
```

#### 1.3 `admin/class-admin-page-modern.php` -- `ajax_reset_all_assignments()` (~line 5121)
**Severity:** Medium
**Issue:** LIKE queries on `wp_usermeta` without `$wpdb->prepare()`.
**Fix:** Both COUNT and DELETE queries now use `$wpdb->prepare()` with `LIKE %s` parameter.

#### 1.4 `admin/class-admin-page-modern.php` -- `get_obmann_assignments()` (~line 5215)
**Severity:** Medium
**Issue:** Raw LIKE query pattern in complex JOIN query.
**Fix:** Wrapped entire query in `$wpdb->prepare()` with `LIKE %s` parameter.

#### 1.5 `includes/class-database-handler.php` -- `get_jagdbezirke()` and `get_active_jagdbezirke()`
**Severity:** Critical
**Issue:** `SHOW TABLES LIKE` with string interpolation (`'$table_name'`).
**Fix:**
```php
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
```

#### 1.6 `includes/class-database-handler.php` -- `get_jagdbezirke_by_meldegruppe()`
**Severity:** Critical
**Issue:** Three `SHOW TABLES LIKE` queries and one legacy table check with string interpolation.
**Fix:** All changed to `$wpdb->prepare("SHOW TABLES LIKE %s", $var)`.

#### 1.7 `admin/services/class-dashboard-service.php` -- `calculate_dashboard_stats()`
**Severity:** Medium
**Issue:** Three raw SQL queries using `$table_name` without escaping (total count, species stats, top meldegruppen).
**Fix:** All queries changed to use `esc_sql($table_name)` in backtick-quoted table references.

#### 1.8 `admin/services/class-import-validator.php` -- `wus_number_exists()` (~line 388)
**Severity:** Medium
**Issue:** Raw table name interpolation in SELECT query.
**Fix:**
```php
"SELECT wus_nummer FROM `" . esc_sql($table_name) . "` WHERE..."
```

#### 1.9 `uninstall.php` -- Options and usermeta cleanup queries
**Severity:** Low (only runs during plugin deletion)
**Issue:** LIKE queries without `$wpdb->prepare()`.
**Fix:** Wrapped in `$wpdb->prepare()` with `LIKE %s` parameters.

---

### 2. Missing Nonce Checks

#### 2.1 `includes/class-form-handler.php` -- `export_csv()` (~line 1282)
**Severity:** Critical
**Issue:** This `nopriv` AJAX handler had NO nonce check and NO login verification. Any anonymous visitor could export all submission data.
**Fix:** Added login check (`is_user_logged_in()`) and nonce verification at the top of the function.

#### 2.2 `includes/class-form-handler.php` -- `ajax_refresh_table()` (~line 1440)
**Severity:** High
**Issue:** Weak nonce check pattern -- only verified nonce *if* one was provided, allowing bypass by omitting the nonce.
```php
// BEFORE (bypassable):
if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'ahgmh_form_nonce'))
```
**Fix:**
```php
if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'ahgmh_form_nonce'))
```

#### 2.3 `admin/controllers/class-page-views-controller.php` -- `ajax_get_stats()`
**Severity:** High
**Issue:** Missing nonce verification on AJAX handler. Only had capability check.
**Fix:** Added `check_ajax_referer('ahgmh_admin_nonce', 'nonce');` at the top of the handler.

#### 2.4 `admin/controllers/class-page-views-controller.php` -- `ajax_export_csv()`
**Severity:** High
**Issue:** Missing nonce verification on CSV export handler.
**Fix:** Added nonce verification:
```php
if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'ahgmh_admin_nonce')) {
    wp_die(__('Sicherheitscheck fehlgeschlagen', 'abschussplan-hgmh'));
}
```

---

### 3. Unsanitized Input

#### 3.1 `includes/class-permissions-service.php` -- `get_login_form()` (~line 315)
**Severity:** Medium
**Issue:** Raw `$_SERVER['HTTP_HOST']` and `$_SERVER['REQUEST_URI']` used in redirect URL construction.
**Fix:**
```php
$redirect_to = (is_ssl() ? 'https://' : 'http://') .
    sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) .
    esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
```

#### 3.2 `includes/class-verification-service.php` -- `get_client_ip()` (~line 364)
**Severity:** Medium
**Issue:** Raw `$_SERVER` header values used without sanitization for IP address extraction.
**Fix:** All `$_SERVER` accesses wrapped in `sanitize_text_field(wp_unslash())`. `X-Forwarded-For` header now correctly extracts only the first IP from comma-separated list.

#### 3.3 `includes/class-activity-logger.php` -- `get_ip_address()` (private method)
**Severity:** Medium
**Issue:** Raw `$_SERVER[$key]` values iterated without sanitization.
**Fix:**
```php
$value = sanitize_text_field(wp_unslash($_SERVER[$key]));
```

---

## Files Verified as Secure (No Changes Needed)

| File | Notes |
|------|-------|
| `admin/ajax-handlers-emergency.php` | All handlers use `verify_ajax_request()` |
| `includes/class-public-form-handler.php` | Nonce check + rate limiting + input sanitization |
| `includes/class-rest-api.php` | `permission_callback` and `sanitize_callback` on all endpoints |
| `includes/class-rate-limiter.php` | Properly validates IPs |
| `frontend/shortcodes/class-table-shortcode.php` | Uses `verify_ajax_request()` with custom nonce |
| `admin/services/class-validation-service.php` | Central security service -- well-designed |
| All admin controllers (`bulk-operations`, `compliance`, `dashboard`, `data`, `export`, `feature-flags`, `import`, `jagdbezirk`, `limits`, `reports`, `schedule`, `settings`, `undo`, `wildart`) | All use `verify_ajax_request()` or manual nonce + capability checks |
| `includes/repositories/*` | All use `$wpdb->prepare()` or have no user input |
| `includes/services/class-email-service.php` | Uses `prepare()` properly |
| `admin/services/class-undo-service.php` | Uses `prepare()` properly |
| `admin/services/class-report-service.php` | Uses `prepare()` properly |
| `admin/services/class-bulk-operations-service.php` | Uses `prepare()` with placeholders |
| `templates/form-template.php`, `table-template.php`, `summary-template.php` | Use `esc_html()`/`esc_attr()` properly |
| `templates/email/*` | Use `esc_html__()` and placeholder replacement |
| `templates/pdf/*` | Internal CSS echo is server-generated; numeric outputs use `intval()`/`number_format()` |
| `includes/class-table-display.php` | Uses `intval()` on GET params, delegates to DB handler |
| `includes/class-page-view-logger.php` | Sanitizes `$_SERVER` values, uses `$wpdb->prepare()` |
| `admin/class-admin-controller.php` | Coordinator only -- no SQL or user input |

---

## Recommendations

1. **Standardize nonce action names** -- The plugin uses both `ahgmh_admin_nonce` and `ahgmh_page_views_nonce` for admin handlers. Consolidating to a single convention would reduce confusion.
2. **Sanitize nonce values before verification** -- Several nonce checks pass `$_POST['nonce']` directly to `wp_verify_nonce()`. While WordPress handles this internally, applying `sanitize_text_field()` is a best practice (done in the fixes above where applicable).
3. **Consider replacing `esc_sql()` table name escaping** -- For dynamic table names, WordPress recommends using `$wpdb->prepare()` where possible. Where table names cannot use placeholders (DDL statements like `RENAME TABLE`), `esc_sql()` is the accepted alternative.
4. **Test file** (`tests/test-helper-public-form.php` line 75) contains one unfixed `SHOW TABLES LIKE '$table_name'` -- acceptable for test code but noted for completeness.
