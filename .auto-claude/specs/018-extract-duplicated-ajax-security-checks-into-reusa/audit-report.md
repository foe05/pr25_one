# AJAX Security Checks Audit Report

**Date:** 2026-01-14
**Purpose:** Identify all duplicated AJAX security checks for refactoring to centralized validation
**Scope:** WordPress plugin `abschussplan-hgmh`

---

## Executive Summary

This audit identified **56 duplicated instances** of `check_ajax_referer()` calls and **85 instances** of `current_user_can('manage_options')` checks across the codebase. A centralized validation service (`AHGMH_Validation_Service::verify_ajax_request()`) already exists and is being used in **19 locations**, but **54+ AJAX handlers still use the old duplicated pattern**.

### Key Findings

- **Total `check_ajax_referer()` calls:** 56 instances
- **Total `current_user_can('manage_options')` calls:** 85 instances
- **Files with duplicated patterns:** 2 main files (14+ total files with capability checks)
- **Existing centralized usage:** 19 instances already migrated
- **Pending migration:** 54+ instances requiring refactoring

---

## 1. Files with Duplicated `check_ajax_referer()` Calls

### 1.1 ajax-handlers-emergency.php
**Location:** `wp-content/plugins/abschussplan-hgmh/admin/ajax-handlers-emergency.php`
**Total instances:** 14
**Status:** ❌ Not using centralized validation

#### Affected Functions:
| Line | Function | Nonce Action | Pattern |
|------|----------|--------------|---------|
| 15 | `ahgmh_emergency_create_wildart` | `ahgmh_admin_nonce` | Standard |
| 66 | `ahgmh_emergency_delete_wildart` | `ahgmh_admin_nonce` | Standard |
| 115 | `ahgmh_emergency_load_wildart_config` | `ahgmh_admin_nonce` | Standard |
| 162 | `ahgmh_emergency_save_categories` | `ahgmh_admin_nonce` | Standard |
| 187 | `ahgmh_emergency_save_meldegruppen` | `ahgmh_admin_nonce` | Standard |
| 214 | `ahgmh_emergency_toggle_limit_mode` | `ahgmh_admin_nonce` | Standard |
| 239 | `ahgmh_emergency_load_wildart_meldegruppen` | `ahgmh_admin_nonce` | Standard |
| 291 | `ahgmh_emergency_load_meldegruppen_limits` | `ahgmh_admin_nonce` | Standard |
| 313 | `ahgmh_emergency_toggle_meldegruppe_custom_limits` | `ahgmh_admin_nonce` | Standard |
| 329 | `ahgmh_emergency_save_meldegruppe_limits` | `ahgmh_admin_nonce` | Standard |
| 376 | `ahgmh_emergency_add_jagdbezirk` | `ahgmh_admin_nonce` | Standard |
| 417 | `ahgmh_emergency_edit_jagdbezirk` | `ahgmh_admin_nonce` | Standard |
| 450 | `ahgmh_emergency_delete_jagdbezirk` | `ahgmh_admin_nonce` | Standard |
| 480 | `ahgmh_emergency_save_limits` | `ahgmh_admin_nonce` | Standard |

**Typical Pattern:**
```php
check_ajax_referer('ahgmh_admin_nonce', 'nonce');

if (!current_user_can('manage_options')) {
    wp_send_json_error('Insufficient permissions');
    return;
}
```

---

### 1.2 class-admin-page-modern.php
**Location:** `wp-content/plugins/abschussplan-hgmh/admin/class-admin-page-modern.php`
**Total instances:** 40
**Status:** ⚠️ Partially migrated (1 handler uses centralized validation)

#### Affected Methods by Category:

**Export Handlers (8 instances):**
| Line | Method | Nonce Action | Status |
|------|--------|--------------|--------|
| 1057 | `ajax_dashboard_stats` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 1081 | `ajax_quick_export` | N/A | ✅ Uses centralized |
| 2248 | `ajax_export_data` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2845 | `ajax_save_category_settings` (case 1) | `ahgmh_category_settings` | ❌ Duplicated |
| 2847 | `ajax_save_category_settings` (case 2) | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4352 | `ajax_export_settings` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4374 | `ajax_save_export_config` | `ahgmh_admin_nonce` | ❌ Duplicated |

**Data Management Handlers (5 instances):**
| Line | Method | Nonce Action | Status |
|------|--------|--------------|--------|
| 2292 | `ajax_delete_submission` | `ahgmh_delete_submission` | ❌ Duplicated |
| 2317 | `ajax_edit_submission` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4459 | `ajax_danger_action` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4504 | `ajax_clear_all_data` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4560 | `ajax_reset_to_defaults` | `ahgmh_admin_nonce` | ❌ Duplicated |

**Species/Category Management Handlers (9 instances):**
| Line | Method | Nonce Action | Status |
|------|--------|--------------|--------|
| 2365 | `ajax_add_species` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2432 | `ajax_delete_species` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2466 | `ajax_add_category` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2504 | `ajax_edit_category` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2529 | `ajax_delete_category` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2564 | `ajax_rename_table` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2605 | `ajax_toggle_wildart_specific` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2638 | `ajax_save_species_default_limits` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2933 | `ajax_import_data` | `ahgmh_admin_nonce` | ❌ Duplicated |

**Limits Management Handlers (7 instances):**
| Line | Method | Nonce Action | Status |
|------|--------|--------------|--------|
| 2693 | `ajax_save_jagdbezirk_limits` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2728 | `ajax_load_jagdbezirk_limits` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2752 | `ajax_change_wildart` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 2984 | `ajax_load_wildart_jagdbezirke` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 3013 | `ajax_get_meldegruppen_for_wildart` | `ahgmh_admin_nonce` | ❌ Duplicated |

**Obmann Management Handlers (5 instances):**
| Line | Method | Nonce Action | Status |
|------|--------|--------------|--------|
| 3059 | `ajax_assign_obmann_meldegruppe` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 3112 | `ajax_remove_obmann_assignment` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 3154 | `ajax_edit_obmann_assignment` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 3206 | `ajax_get_obmann_assignments` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 3257 | `ajax_reset_all_assignments` | `ahgmh_admin_nonce` | ❌ Duplicated |

**Meldegruppe Handlers (3 instances):**
| Line | Method | Nonce Action | Status |
|------|--------|--------------|--------|
| 3323 | `ajax_add_meldegruppe` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 3384 | `ajax_delete_meldegruppe` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 3412 | `ajax_rename_meldegruppe` | `ahgmh_admin_nonce` | ❌ Duplicated |

**UI/Settings Handlers (3 instances):**
| Line | Method | Nonce Action | Status |
|------|--------|--------------|--------|
| 4003 | `ajax_save_ui_preferences` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4052 | `ajax_load_table_config` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4102 | `ajax_save_dashboard_state` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4142 | `ajax_test_export` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4185 | `ajax_get_export_preview` | `ahgmh_admin_nonce` | ❌ Duplicated |
| 4592 | `ajax_run_migration` | `ahgmh_admin_nonce` | ❌ Duplicated |

**Typical Pattern:**
```php
check_ajax_referer('ahgmh_admin_nonce', 'nonce');

if (!current_user_can('manage_options')) {
    wp_die(__('Insufficient permissions', 'abschussplan-hgmh'));
}
```

---

### 1.3 class-validation-service.php (Reference Implementation)
**Location:** `wp-content/plugins/abschussplan-hgmh/admin/services/class-validation-service.php`
**Total instances:** 1
**Status:** ✅ This is the centralized service itself

**Line 22:** Used internally by `verify_ajax_request()` method

---

### 1.4 syntax-test.php (Test File)
**Location:** `wp-content/plugins/abschussplan-hgmh/syntax-test.php`
**Total instances:** 1
**Status:** ℹ️ Mock function for testing - can be ignored

---

## 2. Files with `current_user_can('manage_options')` Checks

### Files Using Duplicated Pattern:

| File | Instances | Context |
|------|-----------|---------|
| `class-admin-page-modern.php` | 41 | AJAX handlers |
| `ajax-handlers-emergency.php` | 14 | AJAX handlers |
| `includes/class-form-handler.php` | 11 | Form submission handlers |
| `admin/controllers/class-page-views-controller.php` | 7 | Page view methods |
| `admin/controllers/class-settings-controller.php` | 1 | Settings page |
| `admin/controllers/class-dashboard-controller.php` | 1 | Dashboard render (non-AJAX) |
| `admin/controllers/class-data-controller.php` | 1 | Data operations |
| `admin/controllers/class-admin-controller.php` | 1 | Admin page render |
| `admin/wildarten/index.php` | 1 | Admin page |
| `templates/table-template.php` | 1 | UI conditional |

**Total:** 85 instances across 10+ files

---

## 3. Files Already Using Centralized Validation

### Files Successfully Migrated:

| File | Instances | Methods |
|------|-----------|---------|
| `admin/controllers/class-wildart-controller.php` | 13 | All AJAX handlers |
| `admin/controllers/class-export-controller.php` | 2 | Export operations |
| `admin/controllers/class-dashboard-controller.php` | 1 | Dashboard stats AJAX |
| `admin/controllers/class-limits-controller.php` | 2 | Limits operations |
| `admin/class-admin-page-modern.php` | 1 | `ajax_quick_export` only |

**Total:** 19 instances successfully using `AHGMH_Validation_Service::verify_ajax_request()`

**Example (Best Practice):**
```php
public function ajax_dashboard_stats() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $stats = $this->dashboard_service->get_dashboard_stats();
        wp_send_json_success($stats);
    } catch (Exception $e) {
        error_log('AHGMH Dashboard Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Laden der Statistiken. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}
```

---

## 4. Pattern Variations Identified

### 4.1 Standard Pattern (Most Common - 52 instances)
```php
check_ajax_referer('ahgmh_admin_nonce', 'nonce');

if (!current_user_can('manage_options')) {
    wp_send_json_error('Insufficient permissions');
    return;
}
```

### 4.2 Alternative Nonce Action (2 instances)
```php
check_ajax_referer('ahgmh_delete_submission', 'nonce');  // Different action

if (!current_user_can('manage_options')) {
    wp_send_json_error(...);
}
```

**Found in:**
- Line 2292 in `class-admin-page-modern.php` (ajax_delete_submission)
- Line 2845 in `class-admin-page-modern.php` (ajax_save_category_settings - first check)

### 4.3 Dual Nonce Check Pattern (1 instance)
```php
if (!empty($_POST['ahgmh_category_settings_nonce'])) {
    check_ajax_referer('ahgmh_category_settings', 'ahgmh_category_settings_nonce');
} else {
    check_ajax_referer('ahgmh_admin_nonce', 'nonce');
}

if (!current_user_can('manage_options')) {
    wp_send_json_error(...);
}
```

**Found in:**
- Line 2845-2847 in `class-admin-page-modern.php` (ajax_save_category_settings)

### 4.4 wp_die() vs wp_send_json_error() Variations
- **wp_send_json_error():** Used in `ajax-handlers-emergency.php` (14 instances)
- **wp_die():** Used in most of `class-admin-page-modern.php` (39 instances)

### 4.5 Centralized Pattern (Target - 19 instances)
```php
AHGMH_Validation_Service::verify_ajax_request();

// Business logic follows...
```

---

## 5. Migration Priority & Risk Assessment

### High Priority (14 handlers)
**File:** `ajax-handlers-emergency.php`
**Risk:** Medium
**Reason:** Critical wildart management operations, actively used in UI

### High Priority (40 handlers)
**File:** `class-admin-page-modern.php`
**Risk:** High
**Reason:** Core admin functionality, many different operations, high usage

### Low Priority (11 handlers)
**File:** `includes/class-form-handler.php`
**Risk:** Low
**Reason:** Frontend form submissions, different context (not AJAX admin)

### Low Priority (7 handlers)
**File:** `admin/controllers/class-page-views-controller.php`
**Risk:** Low
**Reason:** Page rendering, not AJAX operations

---

## 6. Recommendations

### 6.1 Migration Strategy
1. ✅ **Phase 1:** Migrate `ajax-handlers-emergency.php` (14 handlers)
2. ✅ **Phase 2:** Migrate `class-admin-page-modern.php` (40 handlers, split into 4 subtasks)
3. **Phase 3:** Consider `includes/class-form-handler.php` (separate project)
4. **Phase 4:** Review non-AJAX capability checks (separate project)

### 6.2 Special Considerations
- **Dual nonce check** in `ajax_save_category_settings` needs custom handling
- **Different nonce actions** in `ajax_delete_submission` may need custom action parameter
- **Error message consistency:** Centralized service uses German messages, matches codebase

### 6.3 Benefits of Migration
- **Single source of truth** for security checks
- **Consistent error messages** in German
- **Easier to update** security requirements (change once, apply everywhere)
- **Reduced code duplication** (3-6 lines → 1 line per handler)
- **Better maintainability** and reduced risk of security bugs

### 6.4 Testing Requirements
Each migrated handler must be tested:
- ✅ Valid nonce + valid permissions → Success
- ✅ Invalid nonce → Error response
- ✅ Valid nonce + insufficient permissions → Error response
- ✅ Handler business logic unchanged

---

## 7. Summary Statistics

| Metric | Count |
|--------|-------|
| **Total `check_ajax_referer()` calls** | 56 |
| **Already centralized** | 19 |
| **Pending migration** | 54 |
| **Files to refactor** | 2 main files |
| **Total `current_user_can('manage_options')` calls** | 85 |
| **Nonce action variations** | 3 unique patterns |
| **Error message variations** | 2 (wp_die vs wp_send_json_error) |

---

## 8. Next Steps

1. ✅ **Audit Complete** - This report
2. ⏳ **Phase 2:** Migrate `ajax-handlers-emergency.php`
3. ⏳ **Phase 3:** Migrate `class-admin-page-modern.php`
4. ⏳ **Phase 4:** Comprehensive testing
5. ⏳ **Phase 5:** Documentation update

---

**Report Generated:** 2026-01-14
**Auditor:** Claude (auto-claude)
**Review Status:** Pending human review
