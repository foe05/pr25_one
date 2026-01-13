# Public Form Integration Testing Guide

## Overview
This document provides a comprehensive guide for testing the public form submission and email verification workflow for the Abschussplan HGMH plugin.

## Prerequisites
- WordPress installation with Abschussplan HGMH plugin activated
- Email testing capability (WP Mail SMTP, MailHog, or similar)
- Database access for verification queries
- Browser with developer tools

---

## Test 1: Shortcode Display (Unauthenticated User)

### Test Case 1.1: Add Shortcode to Page
**Objective:** Verify shortcode renders the public form

**Steps:**
1. Log out of WordPress (or open incognito browser window)
2. Navigate to WordPress admin, create a new page
3. Add shortcode: `[abschuss_form_public species="Rotwild"]`
4. Publish the page and note the URL

**Expected Result:**
- Page is created successfully
- Shortcode is saved in the page content

---

### Test Case 1.2: Load Page Without Login
**Objective:** Confirm form displays for anonymous users

**Steps:**
1. Ensure you are logged out
2. Navigate to the page created in Test Case 1.1
3. Inspect the page content

**Expected Result:**
- ✅ Form displays without requiring login
- ✅ Email field is visible and marked as required (*)
- ✅ Form contains fields: E-Mail-Adresse, Abschussdatum, Abschuss, WUS, Meldegruppe, Bemerkung, Interne Notiz
- ✅ "Speichern" (Save) button is visible
- ✅ Help text under email field: "Sie erhalten eine Bestätigungs-E-Mail zur Verifizierung Ihrer Meldung"
- ✅ No user information is displayed (since not logged in)

**Verification SQL:**
```sql
-- Check page exists with shortcode
SELECT * FROM wp_posts
WHERE post_content LIKE '%abschuss_form_public%'
AND post_status = 'publish';
```

---

### Test Case 1.3: Invalid Species Parameter
**Objective:** Verify error handling for invalid species

**Steps:**
1. Create a new page with shortcode: `[abschuss_form_public species="InvalidSpecies"]`
2. Load the page

**Expected Result:**
- ✅ Warning message displays: "Unbekannte Wildart..."
- ✅ Lists available species (Rotwild, Damwild)

---

### Test Case 1.4: Missing Species Parameter
**Objective:** Verify error handling for missing required parameter

**Steps:**
1. Create a new page with shortcode: `[abschuss_form_public]`
2. Load the page

**Expected Result:**
- ✅ Warning message displays: 'Parameter "species" ist erforderlich...'
- ✅ Shows example usage

---

## Test 2: Form Submission

### Test Case 2.1: Submit Valid Form
**Objective:** Test successful form submission with email verification

**Test Data:**
- Email: test-public-form@example.com
- Abschussdatum: (yesterday's date - auto-filled)
- Abschuss: Select any category (e.g., "Hirsch")
- WUS: 1234567 (optional)
- Meldegruppe: Select any group
- Bemerkung: "Test submission for integration testing"
- Interne Notiz: "Internal test note"

**Steps:**
1. Fill out the form with the test data above
2. Click "Speichern"
3. Wait for response

**Expected Result:**
- ✅ Success message displays: "Ihre Meldung wurde erfolgreich übermittelt! Bitte prüfen Sie Ihr E-Mail-Postfach und bestätigen Sie Ihre E-Mail-Adresse innerhalb von 48 Stunden."
- ✅ Form is cleared or disabled
- ✅ No errors in browser console
- ✅ AJAX request completes successfully (check Network tab)

**Verification SQL:**
```sql
-- Check submission was created
SELECT id, game_species, field1, field2, field5,
       verification_status, verification_token,
       token_expires_at, submitter_email, submitter_ip,
       created_at
FROM wp_ahgmh_submissions
WHERE submitter_email = 'test-public-form@example.com'
ORDER BY created_at DESC LIMIT 1;
```

**Expected Database State:**
- ✅ New record exists
- ✅ `verification_status` = 'pending'
- ✅ `verification_token` is 64-character hex string
- ✅ `token_expires_at` is approximately 48 hours in future
- ✅ `submitter_email` = test email
- ✅ `submitter_ip` contains valid IP address
- ✅ `user_id` = 0 (anonymous submission)

---

### Test Case 2.2: Form Validation - Missing Required Fields
**Objective:** Test client-side and server-side validation

**Steps:**
1. Leave email field empty, try to submit
2. Fill email, leave date empty, try to submit
3. Fill email and date, leave category empty, try to submit
4. Fill email, date, and category, leave meldegruppe empty, try to submit

**Expected Result:**
- ✅ Form validation prevents submission
- ✅ Error messages display for empty required fields
- ✅ Fields are highlighted with error state

---

### Test Case 2.3: Form Validation - Invalid Email
**Objective:** Test email validation

**Steps:**
1. Enter invalid email: "not-an-email"
2. Fill other required fields
3. Try to submit

**Expected Result:**
- ✅ Client-side validation catches invalid email
- ✅ Error message: "Bitte geben Sie eine gültige E-Mail-Adresse ein"

---

### Test Case 2.4: Form Validation - Future Date
**Objective:** Test date validation

**Steps:**
1. Fill all required fields
2. Set Abschussdatum to tomorrow's date
3. Submit form

**Expected Result:**
- ✅ Server-side validation rejects future dates
- ✅ Error message: "Das Datum darf nicht in der Zukunft liegen"

---

### Test Case 2.5: Form Validation - Invalid WUS
**Objective:** Test WUS number validation

**Steps:**
1. Fill required fields
2. Enter WUS: 123 (too short)
3. Submit form
4. Enter WUS: 99999999 (too long)
5. Submit form

**Expected Result:**
- ✅ Validation error: "WUS muss zwischen 1000000 und 9999999 liegen"

---

### Test Case 2.6: Form Validation - Duplicate WUS
**Objective:** Test WUS uniqueness check

**Steps:**
1. Submit a form with WUS: 1111111
2. Submit another form with the same WUS: 1111111

**Expected Result:**
- ✅ Second submission fails
- ✅ Error message: "Diese WUS-Nummer ist bereits vergeben..."

---

## Test 3: Email Verification

### Test Case 3.1: Verification Email Sent
**Objective:** Confirm verification email is sent

**Steps:**
1. After successful form submission (Test Case 2.1)
2. Check email inbox for test-public-form@example.com
3. If using MailHog, check MailHog web interface
4. If using WP Mail SMTP, check configured mail server

**Expected Result:**
- ✅ Email received within 30 seconds
- ✅ Subject: "Bitte bestätigen Sie Ihre Abschuss-Meldung"
- ✅ From: Site name and admin email
- ✅ Email contains:
  - German text "Email-Verifizierung erforderlich"
  - Submission details (Wildart, Kategorie)
  - Verification button "Email-Adresse bestätigen"
  - Expiry notice: "Dieser Link ist 48 Stunden gültig"
  - Disclaimer text
- ✅ Email is HTML formatted with inline CSS

---

### Test Case 3.2: Verification Link Format
**Objective:** Verify token URL structure

**Steps:**
1. Open verification email
2. Inspect verification link (hover over button or view email source)

**Expected Result:**
- ✅ URL format: `http://yoursite.com/?verify_email=TOKEN`
- ✅ TOKEN is 64-character hexadecimal string
- ✅ Token matches database record

**Verification SQL:**
```sql
SELECT verification_token, token_expires_at
FROM wp_ahgmh_submissions
WHERE submitter_email = 'test-public-form@example.com'
ORDER BY created_at DESC LIMIT 1;
```

---

### Test Case 3.3: Click Verification Link
**Objective:** Test email verification flow

**Steps:**
1. Click verification link in email
2. Observe page redirect and message

**Expected Result:**
- ✅ Page redirects to home page (query parameter removed)
- ✅ Success message displays: "Email erfolgreich verifiziert! Ihre Meldung wurde bestätigt."
- ✅ Message styled as green Bootstrap alert
- ✅ Message includes check-circle icon

**Verification SQL:**
```sql
SELECT id, verification_status, verification_token
FROM wp_ahgmh_submissions
WHERE submitter_email = 'test-public-form@example.com'
ORDER BY created_at DESC LIMIT 1;
```

**Expected Database State:**
- ✅ `verification_status` changed from 'pending' to 'verified'
- ✅ `verification_token` remains unchanged
- ✅ Record still exists in database

---

### Test Case 3.4: Click Verification Link Twice
**Objective:** Test idempotency - already verified token

**Steps:**
1. After successful verification (Test Case 3.3)
2. Click the same verification link again

**Expected Result:**
- ✅ Error message displays: "Ungültiger oder abgelaufener Verifizierungslink"
- ✅ Message styled as red Bootstrap alert
- ✅ Database status remains 'verified' (no change)

---

## Test 4: Rate Limiting

### Test Case 4.1: Submit Multiple Forms from Same IP
**Objective:** Test rate limiting (max 5 submissions per IP per hour)

**Setup:**
- Use same browser/IP address
- Use different email addresses for each submission

**Steps:**
1. Submit form with email1@example.com
2. Submit form with email2@example.com
3. Submit form with email3@example.com
4. Submit form with email4@example.com
5. Submit form with email5@example.com
6. Submit form with email6@example.com (6th submission)

**Expected Result:**
- ✅ First 5 submissions succeed
- ✅ 6th submission fails with error: "Sie haben das Limit von 5 Meldungen pro Stunde erreicht. Bitte versuchen Sie es später erneut."
- ✅ Error message displayed before validation
- ✅ No database record created for 6th submission

**Verification SQL:**
```sql
-- Check rate limit transient
SELECT option_name, option_value
FROM wp_options
WHERE option_name LIKE '%ahgmh_rate_limit%';

-- Check submission count from same IP
SELECT COUNT(*) as submission_count, submitter_ip
FROM wp_ahgmh_submissions
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY submitter_ip;
```

**Debug Check (if WP_DEBUG enabled):**
```bash
# Check error log for rate limit messages
tail -f wp-content/debug.log | grep "AHGMH: Rate limit"
```

---

### Test Case 4.2: Rate Limit Reset After 1 Hour
**Objective:** Verify automatic rate limit reset

**Steps:**
1. After rate limit triggered (Test Case 4.1)
2. Wait 1 hour (or manipulate WordPress transients)
3. Try submitting again

**Expected Result:**
- ✅ Submission succeeds after 1-hour window expires
- ✅ Rate limit counter resets to 1

**Manual Reset (for testing):**
```php
// In wp-admin/tools.php or via code snippets plugin
$ip = 'YOUR_TEST_IP';
AHGMH_Rate_Limiter::reset_rate_limit($ip);
echo "Rate limit reset for IP: " . $ip;
```

---

### Test Case 4.3: Different IP Addresses Not Rate Limited
**Objective:** Verify rate limiting is per-IP

**Steps:**
1. Submit 5 forms from IP A (triggers rate limit)
2. Submit 1 form from different IP B (use VPN or different device)

**Expected Result:**
- ✅ IP A is rate limited
- ✅ IP B submission succeeds
- ✅ Each IP has independent rate limit counter

---

## Test 5: Token Expiry

### Test Case 5.1: Expired Token Rejected
**Objective:** Test 48-hour token expiry

**Option A: Wait 48 Hours (Real Test)**
1. Submit form and get verification email
2. Wait 48 hours
3. Click verification link

**Option B: Manipulate Database (Quick Test)**
1. Submit form and note submission ID
2. Manually update token_expires_at to past date:
   ```sql
   UPDATE wp_ahgmh_submissions
   SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR)
   WHERE id = [SUBMISSION_ID];
   ```
3. Click verification link from email

**Expected Result:**
- ✅ Error message: "Ungültiger oder abgelaufener Verifizierungslink"
- ✅ Database status changes from 'pending' to 'expired'

**Verification SQL:**
```sql
SELECT id, verification_status, token_expires_at
FROM wp_ahgmh_submissions
WHERE id = [SUBMISSION_ID];
```

---

### Test Case 5.2: Cleanup Expired Tokens
**Objective:** Test automated cleanup function

**Steps:**
1. Create several expired tokens (use database manipulation)
   ```sql
   UPDATE wp_ahgmh_submissions
   SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR)
   WHERE verification_status = 'pending'
   LIMIT 3;
   ```
2. Run cleanup function:
   ```php
   // Via code snippets or direct PHP execution
   $cleaned = AHGMH_Verification_Service::cleanup_expired_tokens();
   echo "Cleaned up: " . $cleaned . " expired tokens";
   ```

**Expected Result:**
- ✅ All expired pending tokens updated to 'expired' status
- ✅ Function returns count of updated records

**Verification SQL:**
```sql
-- Check expired tokens updated
SELECT COUNT(*)
FROM wp_ahgmh_submissions
WHERE verification_status = 'expired';
```

---

## Test 6: Security & Edge Cases

### Test Case 6.1: SQL Injection Prevention
**Objective:** Verify input sanitization

**Steps:**
1. Try submitting form with SQL injection payloads:
   - Email: `test@example.com'; DROP TABLE wp_ahgmh_submissions; --`
   - Bemerkung: `'; DELETE FROM wp_ahgmh_submissions WHERE '1'='1`
   - WUS: `1234567 OR 1=1`

**Expected Result:**
- ✅ All inputs are sanitized
- ✅ No SQL errors occur
- ✅ Database remains intact
- ✅ Data stored exactly as entered (escaped)

---

### Test Case 6.2: XSS Prevention
**Objective:** Verify output escaping

**Steps:**
1. Submit form with XSS payloads:
   - Bemerkung: `<script>alert('XSS')</script>`
   - Interne Notiz: `<img src=x onerror=alert('XSS')>`
2. View submission in admin panel

**Expected Result:**
- ✅ Scripts do not execute
- ✅ HTML is escaped/sanitized
- ✅ Data displays safely

---

### Test Case 6.3: Invalid Token Format
**Objective:** Test token validation

**Steps:**
1. Manually construct URL with invalid token: `/?verify_email=invalid`
2. Visit URL

**Expected Result:**
- ✅ Error message: "Ungültiger oder abgelaufener Verifizierungslink"
- ✅ No PHP errors

---

### Test Case 6.4: Empty Token
**Objective:** Test token validation

**Steps:**
1. Visit URL: `/?verify_email=`
2. Visit URL: `/?verify_email`

**Expected Result:**
- ✅ No error messages (parameter ignored)
- ✅ Home page displays normally

---

## Test 7: Integration with Admin Panel

### Test Case 7.1: View Submissions in Admin
**Objective:** Verify submissions appear in WordPress admin

**Steps:**
1. Log in to WordPress admin
2. Navigate to Abschussplan HGMH admin page
3. Look for public form submissions

**Expected Result:**
- ✅ Public submissions visible in list
- ✅ Verification status indicated (pending/verified/expired)
- ✅ Email and IP shown (if admin has permission)
- ✅ User ID = 0 for anonymous submissions

---

### Test Case 7.2: Filter by Verification Status
**Objective:** Test admin filtering capabilities

**Steps:**
1. In admin panel, filter by verification_status
2. View pending submissions
3. View verified submissions
4. View expired submissions

**Expected Result:**
- ✅ Filtering works correctly
- ✅ Each status shows appropriate submissions

---

## Test 8: Multi-Species Support

### Test Case 8.1: Different Species Forms
**Objective:** Test multiple species configurations

**Steps:**
1. Create page with: `[abschuss_form_public species="Rotwild"]`
2. Create page with: `[abschuss_form_public species="Damwild"]`
3. Submit forms from each page
4. Check category dropdowns differ per species

**Expected Result:**
- ✅ Each species shows its configured categories
- ✅ Submissions stored with correct game_species value
- ✅ Email verification works for all species

---

## Test Summary Checklist

Use this checklist to track test completion:

### Basic Functionality
- [ ] Test 1.1: Shortcode added to page
- [ ] Test 1.2: Form displays without login
- [ ] Test 1.3: Invalid species error handling
- [ ] Test 1.4: Missing species parameter error
- [ ] Test 2.1: Valid form submission
- [ ] Test 3.1: Verification email sent
- [ ] Test 3.3: Verification link works
- [ ] Test 3.4: Duplicate verification rejected

### Validation
- [ ] Test 2.2: Required field validation
- [ ] Test 2.3: Email validation
- [ ] Test 2.4: Date validation (no future dates)
- [ ] Test 2.5: WUS format validation
- [ ] Test 2.6: WUS uniqueness check

### Rate Limiting
- [ ] Test 4.1: 5 submissions succeed, 6th fails
- [ ] Test 4.2: Rate limit resets after 1 hour
- [ ] Test 4.3: Different IPs independent

### Token Expiry
- [ ] Test 5.1: Expired token rejected (48h)
- [ ] Test 5.2: Cleanup function works

### Security
- [ ] Test 6.1: SQL injection prevention
- [ ] Test 6.2: XSS prevention
- [ ] Test 6.3: Invalid token handling
- [ ] Test 6.4: Empty token handling

### Integration
- [ ] Test 7.1: Admin panel displays submissions
- [ ] Test 7.2: Filtering by verification status
- [ ] Test 8.1: Multi-species support

---

## Troubleshooting

### Email Not Received
1. Check WordPress email configuration (WP Mail SMTP)
2. Check spam folder
3. Enable WP_DEBUG and check error log:
   ```bash
   tail -f wp-content/debug.log | grep "AHGMH"
   ```
4. Test wp_mail() function:
   ```php
   wp_mail('test@example.com', 'Test', 'Test message');
   ```

### Rate Limit Not Working
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

### Verification Link Not Working
1. Check URL structure in email
2. Verify token in database matches URL
3. Check token_expires_at is in future
4. Check verification_status is 'pending'
5. Enable WP_DEBUG for detailed errors

### Database Queries Not Working
1. Verify table exists:
   ```sql
   SHOW TABLES LIKE 'wp_ahgmh_submissions';
   ```
2. Check table structure:
   ```sql
   DESCRIBE wp_ahgmh_submissions;
   ```
3. Verify columns exist:
   ```sql
   SHOW COLUMNS FROM wp_ahgmh_submissions
   LIKE 'verification%';
   ```

---

## Test Environment Details

**Test Environment:**
- WordPress Version: ______
- PHP Version: ______
- Plugin Version: ______
- Database: MySQL/MariaDB ______
- Email Provider: ______
- Test Date: ______
- Tester: ______

**Test Results Summary:**
- Total Tests: 35
- Passed: ___
- Failed: ___
- Skipped: ___

**Notes:**
_Add any additional observations or issues encountered during testing_

---

## Sign-Off

✅ All critical tests passed
✅ Email verification workflow works correctly
✅ Rate limiting prevents spam (5/hour/IP)
✅ Token expiry enforced (48 hours)
✅ Security measures in place
✅ Database schema correct

**Tested By:** ________________
**Date:** ________________
**Status:** ☐ PASS ☐ FAIL ☐ NEEDS REVISION
