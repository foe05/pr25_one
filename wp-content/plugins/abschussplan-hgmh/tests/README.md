# AHGMH Public Form Integration Tests

This directory contains comprehensive integration testing resources for the public form submission and email verification feature.

## 📋 Test Files

### 1. `integration-test-public-form.md`
**Comprehensive Manual Testing Guide**

- 35+ detailed test cases covering all aspects of the public form workflow
- Step-by-step instructions with expected results
- SQL verification queries for each test
- Troubleshooting guide
- Test checklist for tracking progress
- Sign-off template

**When to use:**
- Manual testing before release
- QA validation
- Acceptance testing with stakeholders
- Reproducing reported issues

**Test Coverage:**
- ✅ Shortcode display and configuration
- ✅ Form submission and validation
- ✅ Email verification workflow
- ✅ Rate limiting (5 submissions/hour/IP)
- ✅ Token expiry (48 hours)
- ✅ Security (SQL injection, XSS prevention)
- ✅ Admin panel integration
- ✅ Multi-species support

---

### 2. `test-helper-public-form.php`
**Automated Test Helper Script**

PHP script providing automated tests and helper functions for development and debugging.

**Features:**
- Automated unit tests for all components
- Database schema validation
- Service class verification
- Rate limiter testing
- Token generation testing
- Helper functions for manual testing
- WP-CLI integration

**Usage:**

**Via WP-CLI:**
```bash
wp eval-file wp-content/plugins/abschussplan-hgmh/tests/test-helper-public-form.php
```

**Via Code Snippets Plugin:**
```php
require_once AHGMH_PLUGIN_DIR . 'tests/test-helper-public-form.php';

// Run all automated tests
AHGMH_Public_Form_Test_Helper::run_all_tests();

// Display recent submissions
AHGMH_Public_Form_Test_Helper::display_recent_submissions(10);

// Count by status
AHGMH_Public_Form_Test_Helper::count_by_status();

// Create test submission
$id = AHGMH_Public_Form_Test_Helper::create_test_submission('test@example.com');

// Manually verify submission
AHGMH_Public_Form_Test_Helper::verify_submission_by_id($id);

// Reset rate limit for testing
AHGMH_Public_Form_Test_Helper::reset_rate_limit_for_ip('192.168.1.1');

// Check rate limit info
AHGMH_Public_Form_Test_Helper::get_rate_limit_info();

// Clean up expired tokens
AHGMH_Public_Form_Test_Helper::cleanup_expired();
```

**Available Test Functions:**
- `run_all_tests()` - Execute all automated tests
- `test_database_schema()` - Verify database structure
- `test_verification_service()` - Check service methods
- `test_rate_limiter()` - Test rate limiting logic
- `test_email_configuration()` - Verify email setup
- `test_token_generation()` - Test token security
- `test_ip_detection()` - Check IP detection

**Available Helper Functions:**
- `get_recent_submissions($limit)` - Fetch recent submissions
- `display_recent_submissions($limit)` - Display submissions
- `count_by_status()` - Count submissions by verification status
- `create_test_submission($email)` - Create test submission
- `verify_submission_by_id($id)` - Manually verify a submission
- `expire_pending_tokens()` - Mark tokens as expired (testing)
- `cleanup_expired()` - Run cleanup function
- `reset_rate_limit_for_ip($ip)` - Reset rate limit
- `get_rate_limit_info($ip)` - Get rate limit details

---

### 3. `sql-queries-reference.sql`
**SQL Query Reference**

Collection of useful SQL queries for manual testing and debugging.

**Categories:**
1. Schema Verification
2. View Submissions
3. Search Submissions
4. Token Expiry Checks
5. Rate Limiting Data
6. Testing Helpers
7. Verification Workflow Tracking
8. Statistics & Reporting
9. Data Integrity Checks
10. Cleanup Operations
11. Debugging Queries

**Usage:**
Copy queries into your MySQL client (phpMyAdmin, MySQL Workbench, or command line) and modify as needed.

**Important:**
- Replace `wp_` prefix with your actual WordPress table prefix
- Replace placeholder values (YOUR_SUBMISSION_ID, YOUR_EMAIL_HERE, etc.)
- Use DELETE queries with caution - always backup first

---

## 🚀 Quick Start Testing Guide

### Prerequisites
1. WordPress installation with AHGMH plugin activated
2. Email testing configured (WP Mail SMTP or MailHog)
3. Database access (phpMyAdmin or MySQL client)
4. Browser with developer tools

### Step 1: Run Automated Tests
```bash
# Via WP-CLI
wp eval-file wp-content/plugins/abschussplan-hgmh/tests/test-helper-public-form.php

# Or via Code Snippets plugin in WordPress admin
```

**Expected Output:**
```
=== AHGMH Public Form Integration Tests ===

Test 1: Database Schema Verification
=====================================
✓ Table exists: YES
  - verification_status: ✓
  - verification_token: ✓
  - token_expires_at: ✓
  - submitter_email: ✓
  - submitter_ip: ✓
✅ PASS

[... more tests ...]

=== Test Summary ===
database_schema                ✅ PASS
verification_service           ✅ PASS
rate_limiter                   ✅ PASS
email_sending                  ✅ PASS
token_generation               ✅ PASS
ip_detection                   ✅ PASS

Total: 6 | Passed: 6 | Failed: 0
```

### Step 2: Manual Testing
Follow the guide in `integration-test-public-form.md`:

1. **Create Test Page:**
   - Add shortcode: `[abschuss_form_public species="Rotwild"]`
   - Publish page

2. **Test Form Submission:**
   - Open page in incognito window (logged out)
   - Fill form with test data
   - Submit and verify success message

3. **Verify Email:**
   - Check email inbox or MailHog
   - Click verification link
   - Verify success message appears

4. **Test Rate Limiting:**
   - Submit 5 forms quickly
   - 6th submission should fail with rate limit message

5. **Verify Database:**
   - Run SQL queries from `sql-queries-reference.sql`
   - Check submission status, token, expiry time

### Step 3: Database Verification
```sql
-- Quick check
SELECT id, submitter_email, verification_status,
       token_expires_at, created_at
FROM wp_ahgmh_submissions
ORDER BY created_at DESC
LIMIT 10;

-- Status counts
SELECT verification_status, COUNT(*) as count
FROM wp_ahgmh_submissions
GROUP BY verification_status;
```

---

## 🧪 Testing Scenarios

### Scenario 1: Happy Path (Full Workflow)
1. User submits public form
2. Receives verification email
3. Clicks link within 48 hours
4. Status changes to 'verified'

**Test:** Follow Test Cases 2.1, 3.1, 3.2, 3.3 in integration-test-public-form.md

### Scenario 2: Rate Limiting
1. User submits 5 forms from same IP
2. 6th submission rejected
3. After 1 hour, user can submit again

**Test:** Follow Test Case 4.1, 4.2 in integration-test-public-form.md

### Scenario 3: Token Expiry
1. User submits form
2. Waits more than 48 hours (or manipulate database)
3. Clicks verification link
4. Link rejected as expired
5. Status changes to 'expired'

**Test:** Follow Test Case 5.1 in integration-test-public-form.md

### Scenario 4: Validation Errors
1. User tries to submit with invalid data
2. Form validation catches errors
3. User corrects and resubmits successfully

**Test:** Follow Test Cases 2.2 - 2.6 in integration-test-public-form.md

---

## 🛠️ Troubleshooting

### Issue: Automated tests fail
**Solution:** Check database schema was properly updated
```bash
wp eval-file wp-content/plugins/abschussplan-hgmh/tests/test-helper-public-form.php
```
Look for specific test failures and check prerequisites.

### Issue: Email not received
**Solutions:**
1. Check WordPress email configuration
2. Enable WP_DEBUG and check error log:
   ```bash
   tail -f wp-content/debug.log | grep "AHGMH"
   ```
3. Test wp_mail() directly:
   ```php
   wp_mail('test@example.com', 'Test', 'Test message');
   ```
4. Use MailHog or similar for local testing

### Issue: Rate limit not working
**Solutions:**
1. Check transients are enabled
2. Verify IP detection:
   ```php
   $ip = AHGMH_Verification_Service::get_client_ip();
   echo "Your IP: " . $ip;
   ```
3. Check transient storage:
   ```sql
   SELECT * FROM wp_options
   WHERE option_name LIKE '_transient_ahgmh_rate_limit_%';
   ```
4. Reset rate limit for testing:
   ```php
   AHGMH_Rate_Limiter::reset_rate_limit($your_ip);
   ```

### Issue: Verification link doesn't work
**Solutions:**
1. Check URL structure in email
2. Verify token matches database
3. Check token_expires_at is in future
4. Verify verification_status is 'pending'
5. Check WordPress permalinks are enabled

---

## 📊 Test Coverage Matrix

| Feature | Automated | Manual | SQL Verification |
|---------|-----------|--------|------------------|
| Database Schema | ✅ | ✅ | ✅ |
| Shortcode Display | ❌ | ✅ | ❌ |
| Form Validation | ❌ | ✅ | ❌ |
| Submission Creation | ✅ | ✅ | ✅ |
| Email Sending | ⚠️ | ✅ | ❌ |
| Token Generation | ✅ | ✅ | ✅ |
| Token Validation | ✅ | ✅ | ✅ |
| Email Verification | ⚠️ | ✅ | ✅ |
| Rate Limiting | ✅ | ✅ | ✅ |
| Token Expiry | ⚠️ | ✅ | ✅ |
| Security (SQL/XSS) | ❌ | ✅ | ✅ |
| IP Detection | ✅ | ✅ | ❌ |
| Status Transitions | ⚠️ | ✅ | ✅ |

Legend:
- ✅ Fully covered
- ⚠️ Partially covered
- ❌ Not covered (manual only)

---

## 📝 Acceptance Criteria Verification

From Spec 007-public-form:

- [x] Shortcode `[abschuss_form_public species="Rotwild"]` works
- [x] Functions without WordPress login
- [x] Email field is mandatory
- [x] Submission created with status `pending_email` → `verified`
- [x] Verification email sent with token link
- [x] Link validity: 48 hours
- [x] Status transition: pending → verified
- [x] Rate limiting: Max 5 submissions/IP/hour

All acceptance criteria can be verified using the tests in this directory.

---

## 🔐 Security Testing Checklist

- [ ] SQL injection prevention (Test Case 6.1)
- [ ] XSS prevention (Test Case 6.2)
- [ ] Input sanitization (all form fields)
- [ ] Output escaping (email template, admin display)
- [ ] CSRF protection (nonce verification)
- [ ] Rate limiting (prevents DoS)
- [ ] Token security (64-char random hex)
- [ ] Email validation (proper sanitization)
- [ ] IP validation (proper detection and storage)

---

## 📚 Additional Resources

- **Plugin Documentation:** `../README.md`
- **Spec:** `.auto-claude/specs/007-public-form/spec.md`
- **Implementation Plan:** `.auto-claude/specs/007-public-form/implementation_plan.json`
- **WordPress Coding Standards:** https://developer.wordpress.org/coding-standards/

---

## 🤝 Contributing

When adding new features:
1. Update `integration-test-public-form.md` with new test cases
2. Add automated tests to `test-helper-public-form.php` if applicable
3. Include relevant SQL queries in `sql-queries-reference.sql`
4. Update this README with new testing scenarios

---

## ✅ Sign-Off Template

After completing all tests, use this template:

```
=== AHGMH Public Form Integration Tests - Sign-Off ===

Test Date: YYYY-MM-DD
Tested By: [Name]
Environment: [Production/Staging/Development]

Automated Tests: [PASS/FAIL] (X/6 passed)
Manual Tests: [PASS/FAIL] (XX/35 passed)
Security Tests: [PASS/FAIL]

Critical Issues: [None/List issues]
Minor Issues: [None/List issues]

Overall Status: ☐ APPROVED ☐ NEEDS REVISION ☐ REJECTED

Notes:
[Add any additional notes or observations]

Signature: ___________________
```

---

**Last Updated:** 2026-01-13
**Version:** 1.0.0
**Status:** Active
