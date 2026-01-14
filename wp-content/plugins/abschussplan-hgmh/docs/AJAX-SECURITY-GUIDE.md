# AJAX Security Best Practices Guide

**Plugin:** Abschussplan HGMH
**Last Updated:** 2026-01-14
**Audience:** Plugin developers and maintainers

---

## Table of Contents

1. [Introduction](#introduction)
2. [Security Fundamentals](#security-fundamentals)
3. [Quick Start Guide](#quick-start-guide)
4. [Centralized Validation Service](#centralized-validation-service)
5. [Creating Secure AJAX Handlers](#creating-secure-ajax-handlers)
6. [Input Validation & Sanitization](#input-validation--sanitization)
7. [Error Handling](#error-handling)
8. [Testing Security](#testing-security)
9. [Common Security Pitfalls](#common-security-pitfalls)
10. [Migration from Legacy Patterns](#migration-from-legacy-patterns)
11. [Checklist & Reference](#checklist--reference)

---

## Introduction

This guide provides security best practices for developing AJAX handlers in the Abschussplan HGMH WordPress plugin. Following these guidelines ensures consistent, secure, and maintainable AJAX operations.

### Why This Matters

AJAX handlers are a common attack vector in WordPress plugins. Improper security can lead to:

- **CSRF (Cross-Site Request Forgery)** attacks: Unauthorized actions performed on behalf of authenticated users
- **Privilege escalation**: Users performing actions beyond their permission level
- **XSS (Cross-Site Scripting)**: Injection of malicious scripts through unsanitized input
- **Data breaches**: Unauthorized access to sensitive information

### Goals of This Guide

1. **Standardize security checks** across all AJAX handlers
2. **Eliminate code duplication** through centralized validation
3. **Reduce security vulnerabilities** through consistent patterns
4. **Simplify maintenance** with a single source of truth

---

## Security Fundamentals

### The Three Pillars of AJAX Security

Every AJAX handler **MUST** implement these three security layers:

#### 1. Nonce Verification (Anti-CSRF)

**Purpose:** Prevent Cross-Site Request Forgery attacks

**What it does:**
- Validates that the request originated from your plugin
- Ensures the request is fresh (nonces expire)
- Prevents replay attacks

**WordPress function:** `check_ajax_referer()`

#### 2. Capability Checks (Authorization)

**Purpose:** Verify user has permission to perform the action

**What it does:**
- Checks if user is logged in
- Verifies user has required capabilities (e.g., `manage_options`)
- Prevents privilege escalation

**WordPress function:** `current_user_can()`

#### 3. Input Sanitization (Anti-XSS)

**Purpose:** Prevent injection attacks through malicious input

**What it does:**
- Removes or escapes dangerous characters
- Validates input format and structure
- Prevents XSS and SQL injection

**WordPress functions:** `sanitize_text_field()`, `sanitize_email()`, `absint()`, etc.

---

## Quick Start Guide

### For New AJAX Handlers

When creating a new AJAX handler, follow this template:

```php
/**
 * AJAX: Description of what this handler does
 *
 * Expected POST data:
 * - nonce: AJAX nonce (automatically validated)
 * - param1: Description
 * - param2: Description
 */
public function ajax_my_handler() {
    // 1. SECURITY: Always first line - validates nonce and capability
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        // 2. SANITIZE: Clean all input data
        $param1 = sanitize_text_field($_POST['param1'] ?? '');

        // 3. VALIDATE: Check business logic requirements
        if (empty($param1)) {
            wp_send_json_error(array(
                'message' => __('Parameter fehlt', 'abschussplan-hgmh')
            ));
            return;
        }

        // 4. EXECUTE: Perform the operation
        $result = $this->service->perform_operation($param1);

        // 5. RESPOND: Send success response
        wp_send_json_success(array(
            'message' => __('Erfolgreich gespeichert', 'abschussplan-hgmh'),
            'data' => $result
        ));

    } catch (Exception $e) {
        // 6. ERROR HANDLING: Log error and return user-friendly message
        error_log('AHGMH Error in ' . __METHOD__ . ': ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => __('Ein Fehler ist aufgetreten', 'abschussplan-hgmh')
        ));
    }
}
```

### JavaScript Side

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'ahgmh_my_handler',
        nonce: ahgmh_admin.nonce,  // ⚠️ Must be named 'nonce'
        param1: value1
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.message);
        } else {
            console.error(response.data.message);
        }
    },
    error: function() {
        console.error('Network error');
    }
});
```

---

## Centralized Validation Service

### Overview

The `AHGMH_Validation_Service` class provides centralized security validation and input sanitization. **Always use this service instead of writing custom security checks.**

**Location:** `admin/services/class-validation-service.php`

### Benefits

✅ **Single source of truth** - Security logic in one place
✅ **Consistent error messages** - Standardized German messages
✅ **Reduced boilerplate** - 5 lines → 1 line (80% reduction)
✅ **Easier updates** - Change once, applies everywhere
✅ **Fewer bugs** - Tested, proven security implementation

### Core Method: verify_ajax_request()

This is the **most important method** you'll use. It replaces all manual security checks.

#### Basic Usage

```php
public function ajax_handler() {
    // Validates nonce and capability in one call
    AHGMH_Validation_Service::verify_ajax_request();

    // Your code here - only executes if validation passes
}
```

#### What It Does

1. Verifies nonce using `check_ajax_referer('ahgmh_admin_nonce', 'nonce')`
2. Checks user capability using `current_user_can('manage_options')`
3. Sends JSON error response and terminates if either check fails
4. Returns control to your handler if both checks pass

#### Parameters

```php
verify_ajax_request($action = 'ahgmh_admin_nonce', $capability = 'manage_options')
```

- **$action** (string): Nonce action name. Default: `'ahgmh_admin_nonce'`
- **$capability** (string): Required WordPress capability. Default: `'manage_options'`

#### Custom Nonce Action

```php
// For handlers using a different nonce action
AHGMH_Validation_Service::verify_ajax_request('custom_nonce_action');
```

#### Custom Capability

```php
// For handlers requiring different permissions
AHGMH_Validation_Service::verify_ajax_request('ahgmh_admin_nonce', 'edit_posts');
```

### Error Responses

When validation fails, `verify_ajax_request()` automatically sends:

```json
{
  "success": false,
  "data": {
    "message": "Sicherheitsprüfung fehlgeschlagen."
  }
}
```

Or for insufficient permissions:

```json
{
  "success": false,
  "data": {
    "message": "Unzureichende Berechtigungen."
  }
}
```

**Important:** After sending the error, `verify_ajax_request()` terminates execution. Your handler code won't run.

---

## Creating Secure AJAX Handlers

### Step-by-Step Process

#### Step 1: Register the AJAX Action

```php
// In your controller or main class __construct() or init()
add_action('wp_ajax_ahgmh_my_action', array($this, 'ajax_my_handler'));
```

#### Step 2: Create the Handler Method

```php
public function ajax_my_handler() {
    // Security verification - ALWAYS FIRST
    AHGMH_Validation_Service::verify_ajax_request();

    // Handler logic here
}
```

#### Step 3: Add Input Sanitization

```php
public function ajax_my_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    // Sanitize all input
    $wildart = sanitize_text_field($_POST['wildart'] ?? '');
    $category_id = absint($_POST['category_id'] ?? 0);
    $categories = AHGMH_Validation_Service::sanitize_text_array($_POST['categories'] ?? []);
}
```

#### Step 4: Add Business Logic Validation

```php
public function ajax_my_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    $wildart = sanitize_text_field($_POST['wildart'] ?? '');

    // Validate business rules
    if (empty($wildart)) {
        wp_send_json_error(array(
            'message' => __('Wildart nicht angegeben', 'abschussplan-hgmh')
        ));
        return;
    }
}
```

#### Step 5: Add Error Handling

```php
public function ajax_my_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');

        if (empty($wildart)) {
            wp_send_json_error(array(
                'message' => __('Wildart nicht angegeben', 'abschussplan-hgmh')
            ));
            return;
        }

        // Perform operation
        $result = $this->service->save_wildart($wildart);

        wp_send_json_success(array(
            'message' => __('Erfolgreich gespeichert', 'abschussplan-hgmh'),
            'data' => $result
        ));

    } catch (Exception $e) {
        error_log('AHGMH Error: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => __('Fehler beim Speichern', 'abschussplan-hgmh')
        ));
    }
}
```

#### Step 6: Add Documentation

```php
/**
 * AJAX: Save wildart configuration
 *
 * Saves the species name and associated configuration to the database.
 *
 * Expected POST data:
 * - nonce: AJAX nonce for security validation
 * - wildart: Species name (string, required)
 * - categories: Array of category IDs (optional)
 *
 * @return void Sends JSON response and terminates
 */
public function ajax_my_handler() {
    AHGMH_Validation_Service::verify_ajax_request();
    // ... implementation
}
```

---

## Input Validation & Sanitization

### Available Validation Methods

#### 1. sanitize_text_array()

**Purpose:** Recursively sanitize array of text fields

**When to use:** For arrays of strings (categories, meldegruppen, etc.)

```php
$categories = AHGMH_Validation_Service::sanitize_text_array($_POST['categories'] ?? []);
```

**Handles nested arrays:**
```php
$data = [
    'names' => ['John', 'Jane'],
    'nested' => ['level1' => ['level2' => 'value']]
];
$clean = AHGMH_Validation_Service::sanitize_text_array($data);
```

#### 2. validate_wildart_data()

**Purpose:** Validate complete wildart configuration structure

**When to use:** When saving wildart configuration with categories and limits

```php
$data = $_POST['wildart_data'] ?? [];
$validated = AHGMH_Validation_Service::validate_wildart_data($data);
// Returns sanitized data or sends error if validation fails
```

**Validates:**
- `name` - Required, non-empty
- `categories` - Array, sanitized
- `meldegruppen` - Array, sanitized
- `limits` - Nested structure, validated

#### 3. validate_species_name()

**Purpose:** Validate species/wildart name with strict rules

**When to use:** For species name input fields

```php
$name = AHGMH_Validation_Service::validate_species_name($_POST['name'] ?? '');
if (!$name) {
    wp_send_json_error(array('message' => 'Ungültiger Name'));
    return;
}
```

**Validation rules:**
- Not empty
- Max 100 characters
- Only letters (including äöüÄÖÜß), numbers, spaces, hyphens, parentheses

#### 4. validate_pagination()

**Purpose:** Validate and normalize pagination parameters

**When to use:** For list endpoints with pagination

```php
[$page, $per_page] = AHGMH_Validation_Service::validate_pagination(
    $_GET['page'] ?? 1,
    $_GET['per_page'] ?? 20
);
```

**Enforces:**
- Page >= 1
- Per page: 1-100 (prevents excessive queries)
- Returns normalized integer values

#### 5. validate_export_params()

**Purpose:** Validate export parameters (format and species filter)

**When to use:** For export operations

```php
[$species, $format] = AHGMH_Validation_Service::validate_export_params(
    $_POST['species'] ?? '',
    $_POST['format'] ?? 'csv'
);
```

**Allowed formats:** `csv`, `excel`

#### 6. safe_output()

**Purpose:** Escape output for safe HTML rendering

**When to use:** When outputting user-supplied data

```php
// HTML context
echo AHGMH_Validation_Service::safe_output($user_input);

// Attribute context
echo '<div data-name="' . AHGMH_Validation_Service::safe_output($name, 'attr') . '">';

// URL context
echo '<a href="' . AHGMH_Validation_Service::safe_output($url, 'url') . '">';

// JavaScript context
echo '<script>var name = "' . AHGMH_Validation_Service::safe_output($name, 'js') . '";</script>';
```

**Contexts:** `html`, `attr`, `url`, `js`

### Common Sanitization Functions

Use WordPress core functions for basic sanitization:

```php
// Text fields
$text = sanitize_text_field($_POST['text'] ?? '');

// Email addresses
$email = sanitize_email($_POST['email'] ?? '');

// URLs
$url = esc_url_raw($_POST['url'] ?? '');

// Integers
$id = absint($_POST['id'] ?? 0);

// Floats
$price = floatval($_POST['price'] ?? 0);

// HTML content (use with caution)
$html = wp_kses_post($_POST['content'] ?? '');

// SQL LIKE pattern
$search = '%' . $wpdb->esc_like($_POST['search'] ?? '') . '%';
```

---

## Error Handling

### Error Handling Strategy

**Rule:** Always wrap business logic in try-catch blocks

#### Standard Pattern

```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        // Sanitize input
        $data = sanitize_text_field($_POST['data'] ?? '');

        // Validate business rules
        if (empty($data)) {
            wp_send_json_error(array(
                'message' => __('Daten fehlen', 'abschussplan-hgmh')
            ));
            return;
        }

        // Perform operation (may throw exception)
        $result = $this->service->risky_operation($data);

        // Success response
        wp_send_json_success(array(
            'message' => __('Erfolgreich', 'abschussplan-hgmh'),
            'data' => $result
        ));

    } catch (Exception $e) {
        // Log technical details
        error_log('AHGMH Error in ' . __METHOD__ . ': ' . $e->getMessage());

        // Return user-friendly message
        wp_send_json_error(array(
            'message' => __('Ein Fehler ist aufgetreten', 'abschussplan-hgmh')
        ));
    }
}
```

### Error Response Format

**Always use this format:**

```php
wp_send_json_error(array(
    'message' => __('Error message in German', 'abschussplan-hgmh')
));
```

**Benefits:**
- Consistent structure across all handlers
- JavaScript can rely on `response.data.message`
- Translatable messages
- German language (plugin standard)

### Success Response Format

**Always use this format:**

```php
wp_send_json_success(array(
    'message' => __('Success message', 'abschussplan-hgmh'),
    'data' => $result_data  // Optional
));
```

### Logging Best Practices

**Do:**
```php
// ✅ Log technical details with context
error_log('AHGMH Error in ' . __METHOD__ . ': ' . $e->getMessage());
error_log('AHGMH Debug: Wildart=' . $wildart . ', Categories=' . count($categories));
```

**Don't:**
```php
// ❌ Expose technical details to user
wp_send_json_error(array('message' => $e->getMessage()));

// ❌ Log sensitive data
error_log('Password: ' . $password);  // Never!
```

---

## Testing Security

### Required Tests for Every Handler

#### Test 1: Valid Request (Happy Path)

**Purpose:** Verify handler works correctly with valid data

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'ahgmh_test_handler',
        nonce: ahgmh_admin.nonce,
        param: 'valid_value'
    },
    success: function(response) {
        console.assert(response.success === true, 'Should succeed with valid data');
    }
});
```

**Expected:** Success response with status 200

#### Test 2: Invalid Nonce

**Purpose:** Verify CSRF protection works

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'ahgmh_test_handler',
        nonce: 'invalid_nonce_12345',  // ⚠️ Invalid nonce
        param: 'value'
    },
    success: function(response) {
        console.assert(response.success === false, 'Should fail with invalid nonce');
        console.assert(response.data.message === 'Sicherheitsprüfung fehlgeschlagen.', 'Should return security error');
    }
});
```

**Expected:** Error response with "Sicherheitsprüfung fehlgeschlagen"

#### Test 3: No Authentication (Logged Out)

**Purpose:** Verify authentication requirement

**Method:** Open browser console in incognito/logged-out session

```javascript
jQuery.ajax({
    url: '/wp-admin/admin-ajax.php',
    method: 'POST',
    data: {
        action: 'ahgmh_test_handler',
        nonce: 'any_nonce',
        param: 'value'
    },
    success: function(response) {
        console.assert(response.success === false, 'Should fail when not logged in');
    }
});
```

**Expected:** Error response with "Sicherheitsprüfung fehlgeschlagen" or "Unzureichende Berechtigungen"

#### Test 4: Insufficient Permissions

**Purpose:** Verify capability check works

**Method:** Log in as a user without `manage_options` capability (e.g., Subscriber)

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'ahgmh_test_handler',
        nonce: ahgmh_admin.nonce,
        param: 'value'
    },
    success: function(response) {
        console.assert(response.success === false, 'Should fail without manage_options');
        console.assert(response.data.message === 'Unzureichende Berechtigungen.', 'Should return permission error');
    }
});
```

**Expected:** Error response with "Unzureichende Berechtigungen"

#### Test 5: Invalid Input

**Purpose:** Verify input validation works

```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'ahgmh_test_handler',
        nonce: ahgmh_admin.nonce,
        param: ''  // ⚠️ Empty/invalid value
    },
    success: function(response) {
        console.assert(response.success === false, 'Should fail with invalid input');
    }
});
```

**Expected:** Error response with specific validation error

### Automated Security Testing

For comprehensive security testing, see:
- `.auto-claude/specs/.../security-test-unauthenticated.md`
- `.auto-claude/specs/.../security-test-invalid-nonce.md`

---

## Common Security Pitfalls

### ❌ Pitfall 1: Forgetting Security Check

**Wrong:**
```php
public function ajax_handler() {
    // No security check - VULNERABLE!
    $data = $_POST['data'];
    $this->service->update($data);
    wp_send_json_success();
}
```

**Correct:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();  // ✅ Always first

    $data = sanitize_text_field($_POST['data'] ?? '');
    $this->service->update($data);
    wp_send_json_success();
}
```

### ❌ Pitfall 2: Security Check in Wrong Order

**Wrong:**
```php
public function ajax_handler() {
    $data = $_POST['data'];
    $this->service->update($data);  // ⚠️ Executes before security check!

    AHGMH_Validation_Service::verify_ajax_request();
}
```

**Correct:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();  // ✅ First line

    $data = sanitize_text_field($_POST['data'] ?? '');
    $this->service->update($data);
}
```

### ❌ Pitfall 3: Not Sanitizing Input

**Wrong:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    $name = $_POST['name'];  // ⚠️ Not sanitized - XSS vulnerability
    update_option('species_name', $name);
}
```

**Correct:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    $name = sanitize_text_field($_POST['name'] ?? '');  // ✅ Sanitized
    update_option('species_name', $name);
}
```

### ❌ Pitfall 4: Wrong Nonce Parameter Name

**Wrong:**
```javascript
// JavaScript sends 'security' but PHP expects 'nonce'
jQuery.ajax({
    data: {
        security: ahgmh_admin.nonce  // ⚠️ Wrong parameter name
    }
});
```

**Correct:**
```javascript
jQuery.ajax({
    data: {
        nonce: ahgmh_admin.nonce  // ✅ Correct parameter name
    }
});
```

### ❌ Pitfall 5: Exposing Technical Details

**Wrong:**
```php
catch (Exception $e) {
    // ⚠️ Exposes stack traces, file paths, database errors to user
    wp_send_json_error(array('message' => $e->getMessage()));
}
```

**Correct:**
```php
catch (Exception $e) {
    // ✅ Log details, return generic message
    error_log('AHGMH Error: ' . $e->getMessage());
    wp_send_json_error(array(
        'message' => __('Ein Fehler ist aufgetreten', 'abschussplan-hgmh')
    ));
}
```

### ❌ Pitfall 6: SQL Injection via Unsanitized Input

**Wrong:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    global $wpdb;
    $name = $_POST['name'];  // ⚠️ Not sanitized
    $wpdb->query("DELETE FROM table WHERE name = '$name'");  // SQL injection!
}
```

**Correct:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    global $wpdb;
    $name = sanitize_text_field($_POST['name'] ?? '');  // ✅ Sanitized
    $wpdb->delete('table', array('name' => $name), array('%s'));  // ✅ Prepared statement
}
```

### ❌ Pitfall 7: Not Using Try-Catch

**Wrong:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    $result = $this->service->risky_operation();  // May throw exception
    wp_send_json_success($result);  // Never reached if exception thrown
}
```

**Correct:**
```php
public function ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $result = $this->service->risky_operation();
        wp_send_json_success($result);
    } catch (Exception $e) {
        error_log('AHGMH Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'Ein Fehler ist aufgetreten'));
    }
}
```

---

## Migration from Legacy Patterns

### Identifying Legacy Code

Legacy AJAX handlers use manual security checks:

```php
// Old pattern - needs migration
function old_ajax_handler() {
    check_ajax_referer('ahgmh_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    // Business logic...
}
```

### Migration Steps

#### Step 1: Replace Security Checks

**Before:**
```php
function old_ajax_handler() {
    check_ajax_referer('ahgmh_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $wildart = sanitize_text_field($_POST['wildart']);
    // Rest of code...
}
```

**After:**
```php
function old_ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    $wildart = sanitize_text_field($_POST['wildart'] ?? '');
    // Rest of code... (unchanged)
}
```

**Changes:**
- 5 lines → 1 line
- Consistent error messages
- No behavior change

#### Step 2: Add Try-Catch if Missing

```php
function old_ajax_handler() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        // Business logic...
        wp_send_json_success($result);

    } catch (Exception $e) {
        error_log('AHGMH Error: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => __('Ein Fehler ist aufgetreten', 'abschussplan-hgmh')
        ));
    }
}
```

#### Step 3: Update Error Messages to German

**Before:**
```php
wp_send_json_error('Species not found');
```

**After:**
```php
wp_send_json_error(array(
    'message' => __('Wildart nicht gefunden', 'abschussplan-hgmh')
));
```

#### Step 4: Add Default Values to $_POST Access

**Before:**
```php
$wildart = $_POST['wildart'];  // ⚠️ May trigger undefined index notice
```

**After:**
```php
$wildart = $_POST['wildart'] ?? '';  // ✅ Safe with default value
```

#### Step 5: Test Thoroughly

Run all security tests (see [Testing Security](#testing-security)):
- Valid request → Success
- Invalid nonce → Error
- No authentication → Error
- Insufficient permissions → Error
- Invalid input → Error

### Custom Nonce Actions

Some handlers use custom nonce actions:

**Before:**
```php
check_ajax_referer('custom_nonce_action', 'nonce');
if (!current_user_can('manage_options')) {
    wp_send_json_error('Insufficient permissions');
}
```

**After:**
```php
AHGMH_Validation_Service::verify_ajax_request('custom_nonce_action');
```

### Complete Migration Example

**Before (Legacy):**
```php
public function ajax_delete_species() {
    check_ajax_referer('ahgmh_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'abschussplan-hgmh'));
    }

    $species_id = intval($_POST['species_id']);

    if (!$species_id) {
        wp_send_json_error('Invalid species ID');
        return;
    }

    global $wpdb;
    $result = $wpdb->delete($wpdb->prefix . 'ahgmh_species', array('id' => $species_id));

    if ($result) {
        wp_send_json_success('Species deleted');
    } else {
        wp_send_json_error('Failed to delete species');
    }
}
```

**After (Migrated):**
```php
/**
 * AJAX: Delete species
 *
 * Expected POST data:
 * - nonce: AJAX nonce
 * - species_id: Species ID to delete (integer)
 */
public function ajax_delete_species() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $species_id = absint($_POST['species_id'] ?? 0);

        if (!$species_id) {
            wp_send_json_error(array(
                'message' => __('Ungültige Wildart-ID', 'abschussplan-hgmh')
            ));
            return;
        }

        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'ahgmh_species',
            array('id' => $species_id),
            array('%d')
        );

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Wildart erfolgreich gelöscht', 'abschussplan-hgmh')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Fehler beim Löschen der Wildart', 'abschussplan-hgmh')
            ));
        }

    } catch (Exception $e) {
        error_log('AHGMH Error in ' . __METHOD__ . ': ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => __('Ein Fehler ist aufgetreten', 'abschussplan-hgmh')
        ));
    }
}
```

**Improvements:**
- ✅ Single-line security check
- ✅ Try-catch error handling
- ✅ German error messages with translation
- ✅ PHPDoc documentation
- ✅ Default values for $_POST access
- ✅ Consistent response format
- ✅ Error logging

---

## Checklist & Reference

### New AJAX Handler Checklist

When creating a new AJAX handler, verify:

- [ ] Security validation is the **first line** of the handler
- [ ] Using `AHGMH_Validation_Service::verify_ajax_request()`
- [ ] All input is sanitized using appropriate functions
- [ ] Default values provided for `$_POST` access (use `??` operator)
- [ ] Business logic wrapped in try-catch block
- [ ] Errors are logged with `error_log()`
- [ ] User-friendly error messages in German
- [ ] Success/error responses use consistent format
- [ ] PHPDoc comment describes expected POST data
- [ ] JavaScript uses `nonce` parameter name (not `security`)
- [ ] Tested with valid credentials → Success
- [ ] Tested with invalid nonce → Error
- [ ] Tested without authentication → Error
- [ ] Tested with insufficient permissions → Error
- [ ] Tested with invalid input → Proper validation error

### Security Quick Reference

| Security Layer | Method | When |
|----------------|--------|------|
| **Nonce + Capability** | `AHGMH_Validation_Service::verify_ajax_request()` | First line of every handler |
| **Text input** | `sanitize_text_field()` | Single text fields |
| **Text arrays** | `AHGMH_Validation_Service::sanitize_text_array()` | Arrays of strings |
| **Integer** | `absint()` | Positive integers (IDs) |
| **Email** | `sanitize_email()` | Email addresses |
| **URL** | `esc_url_raw()` | URLs |
| **Species name** | `AHGMH_Validation_Service::validate_species_name()` | Wildart names |
| **Output escaping** | `AHGMH_Validation_Service::safe_output()` | User data in responses |

### Error Message Templates

```php
// Generic error
wp_send_json_error(array(
    'message' => __('Ein Fehler ist aufgetreten', 'abschussplan-hgmh')
));

// Missing parameter
wp_send_json_error(array(
    'message' => __('Erforderliche Parameter fehlen', 'abschussplan-hgmh')
));

// Invalid input
wp_send_json_error(array(
    'message' => __('Ungültige Eingabe', 'abschussplan-hgmh')
));

// Not found
wp_send_json_error(array(
    'message' => __('Eintrag nicht gefunden', 'abschussplan-hgmh')
));

// Database error
wp_send_json_error(array(
    'message' => __('Fehler beim Speichern in der Datenbank', 'abschussplan-hgmh')
));
```

### Success Message Templates

```php
// Generic success
wp_send_json_success(array(
    'message' => __('Erfolgreich gespeichert', 'abschussplan-hgmh')
));

// Created
wp_send_json_success(array(
    'message' => __('Erfolgreich erstellt', 'abschussplan-hgmh'),
    'id' => $new_id
));

// Updated
wp_send_json_success(array(
    'message' => __('Erfolgreich aktualisiert', 'abschussplan-hgmh')
));

// Deleted
wp_send_json_success(array(
    'message' => __('Erfolgreich gelöscht', 'abschussplan-hgmh')
));
```

---

## Additional Resources

### Related Documentation

- **Validation Service API Reference:** `admin/services/class-validation-service.php` (PHPDoc comments)
- **Usage Patterns:** `.auto-claude/specs/.../validation-service-usage-patterns.md`
- **Audit Report:** `.auto-claude/specs/.../audit-report.md`
- **Security Testing:** `.auto-claude/specs/.../security-test-*.md`

### WordPress Security Resources

- [Plugin Security Handbook](https://developer.wordpress.org/plugins/security/)
- [Data Validation](https://developer.wordpress.org/plugins/security/data-validation/)
- [Nonces](https://developer.wordpress.org/plugins/security/nonces/)
- [Checking User Capabilities](https://developer.wordpress.org/plugins/security/checking-user-capabilities/)

### WordPress Coding Standards

- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)

---

## Maintenance & Updates

### When to Update This Guide

- New validation methods added to `AHGMH_Validation_Service`
- New security requirements identified
- Common security issues discovered in code reviews
- WordPress security best practices change

### Document History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-14 | Initial version - comprehensive AJAX security guide |

---

## Questions & Support

For questions about AJAX security or this guide:

1. Review the examples in this document
2. Check existing handlers in `admin/controllers/` for patterns
3. Consult the validation service source code with PHPDoc comments
4. Refer to WordPress Plugin Security Handbook

**Remember:** Security is not optional. Always follow these guidelines for every AJAX handler.

---

**Document Status:** Complete
**Last Reviewed:** 2026-01-14
**Next Review:** After any major security updates
