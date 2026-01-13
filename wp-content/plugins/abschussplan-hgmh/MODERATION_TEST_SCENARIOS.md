# Moderation Service - Manual Test Scenarios

This document provides comprehensive test scenarios for the HGMH Moderation Service (`HGMH_Moderation_Service`), including approve, reject, and edit workflows.

## Test Environment Setup

Before running tests, ensure:

1. WordPress is installed and the plugin is activated
2. Database tables exist: `wp_ahgmh_submissions` and `wp_ahgmh_moderation_history`
3. Test data is available in the submissions table
4. You have access to WordPress admin and can execute PHP code

## Pre-Test Database Setup

Create test submissions for testing:

```sql
-- Insert test submission with status 'pending'
INSERT INTO wp_ahgmh_submissions (
    art, kategorie, anzahl, datum, meldegruppe, email, status, erstellt_am
) VALUES (
    'Rehbock', 'Jaehrling', 1, '2026-01-10', 'Gruppe A', 'jaeger@example.com', 'pending', NOW()
);

-- Note the inserted ID for use in tests
SELECT LAST_INSERT_ID();
```

---

## Test Scenario 1: Approve Flow (Happy Path)

### Objective
Verify that a submission can be successfully approved with all side effects working correctly.

### Prerequisites
- Submission exists with `status = 'pending'`
- Valid moderator user ID exists

### Test Steps

1. **Execute approval via PHP:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->approve(
    123,  // submission_id (replace with actual ID)
    1,    // obmann_user_id (replace with actual moderator ID)
    'Alle Daten korrekt. Genehmigt.' // comment
);

// Expected: $result === true
var_dump($result);
```

2. **Verify database changes:**
```sql
-- Check submission status updated
SELECT id, status, approved_by, approved_at, time_to_approval
FROM wp_ahgmh_submissions
WHERE id = 123;

-- Expected results:
-- status = 'approved'
-- approved_by = 1 (moderator user ID)
-- approved_at = current timestamp
-- time_to_approval > 0 (in minutes)
```

3. **Check moderation history logged:**
```sql
SELECT * FROM wp_ahgmh_moderation_history
WHERE submission_id = 123
ORDER BY created_at DESC
LIMIT 1;

-- Expected results:
-- action = 'approve'
-- previous_status = 'pending'
-- new_status = 'approved'
-- moderator_id = 1
-- moderator_name = (display name of moderator)
-- comment = 'Alle Daten korrekt. Genehmigt.'
-- created_at = current timestamp
```

4. **Verify activity log triggered:**
```php
// Add action hook listener to verify trigger
add_action('ahgmh_moderation_activity', function($data) {
    error_log('Activity log triggered: ' . print_r($data, true));
}, 10, 1);

// Expected in error log:
// action = 'approve'
// submission_id = 123
// user_id = 1
// data contains: previous_status, new_status, time_to_approval, comment
```

5. **Verify email would be sent:**
```php
// Add email filter to capture email without sending
add_filter('wp_mail', function($args) {
    error_log('Email would be sent to: ' . $args['to']);
    error_log('Subject: ' . $args['subject']);
    return $args;
}, 10, 1);

// Expected in error log:
// Email would be sent to: jaeger@example.com
// Subject contains approval notification
```

### Success Criteria
- ✅ `approve()` returns `true`
- ✅ Submission status changed to `'approved'`
- ✅ `approved_by`, `approved_at`, and `time_to_approval` fields populated
- ✅ History entry created with correct action, statuses, and moderator info
- ✅ Activity log action triggered with correct data
- ✅ Email notification sent to submitter

---

## Test Scenario 2: Reject Flow with Comment (Happy Path)

### Objective
Verify that a submission can be rejected with a rejection reason.

### Prerequisites
- Submission exists with `status = 'pending'`
- Valid moderator user ID exists

### Test Steps

1. **Execute rejection via PHP:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->reject(
    124,  // submission_id
    1,    // obmann_user_id
    'Datum ist nicht plausibel. Bitte korrigieren.' // rejection reason
);

// Expected: $result === true
var_dump($result);
```

2. **Verify database changes:**
```sql
-- Check submission status updated
SELECT id, status, rejected_by, rejected_at
FROM wp_ahgmh_submissions
WHERE id = 124;

-- Expected results:
-- status = 'rejected'
-- rejected_by = 1 (moderator user ID)
-- rejected_at = current timestamp
```

3. **Check moderation history logged:**
```sql
SELECT * FROM wp_ahgmh_moderation_history
WHERE submission_id = 124
ORDER BY created_at DESC
LIMIT 1;

-- Expected results:
-- action = 'reject'
-- previous_status = 'pending'
-- new_status = 'rejected'
-- comment = 'Datum ist nicht plausibel. Bitte korrigieren.'
```

4. **Verify rejection email sent with reason:**
```php
// Use wp_mail filter to verify email contains rejection reason
// Expected: Email contains the rejection comment
```

### Success Criteria
- ✅ `reject()` returns `true`
- ✅ Submission status changed to `'rejected'`
- ✅ `rejected_by` and `rejected_at` fields populated
- ✅ History entry created with rejection comment
- ✅ Email notification sent with rejection reason

---

## Test Scenario 3: Reject Flow without Comment

### Objective
Verify rejection works even when no comment is provided (Note: Spec says reason is required, but implementation allows empty comments).

### Test Steps

1. **Execute rejection without comment:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->reject(
    125,  // submission_id
    1,    // obmann_user_id
    ''    // empty comment
);

// Current implementation: $result === true (no validation for required reason)
// Per spec: Should throw Exception('Reason required')
var_dump($result);
```

2. **Verify behavior:**
```sql
-- Check if rejection was successful
SELECT id, status, rejected_by FROM wp_ahgmh_submissions WHERE id = 125;

-- Check history entry
SELECT comment FROM wp_ahgmh_moderation_history
WHERE submission_id = 125 AND action = 'reject';

-- Expected: comment field is empty
```

### Success Criteria
- ⚠️ **Note**: Current implementation allows empty comments, but spec requires reason
- ✅ Rejection completes successfully
- ✅ History logged with empty comment

### Recommendation
Consider adding validation in future:
```php
if (empty($reason)) {
    return new WP_Error('reason_required', __('Ablehnungsgrund ist erforderlich.', 'abschussplan-hgmh'));
}
```

---

## Test Scenario 4: Edit Flow with Allowed Fields

### Objective
Verify that submission data can be edited with allowed fields.

### Prerequisites
- Submission exists (any status)
- Valid moderator user ID exists

### Allowed Fields for Editing
Based on `sanitize_submission_data()`:
- `art` (text)
- `kategorie` (text)
- `anzahl` (integer)
- `datum` (date)
- `meldegruppe` (text)
- `bemerkung` (textarea)

### Test Steps

1. **Get original submission data:**
```sql
SELECT art, kategorie, anzahl, datum, meldegruppe, bemerkung
FROM wp_ahgmh_submissions
WHERE id = 126;
```

2. **Execute edit with allowed fields:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->edit(
    126,  // submission_id
    1,    // obmann_user_id
    [
        'art' => 'Rehbock',
        'kategorie' => 'Bock',
        'anzahl' => 2,
        'datum' => '2026-01-11',
        'bemerkung' => 'Korrigierte Anzahl'
    ],
    'Anzahl von 1 auf 2 korrigiert' // comment
);

// Expected: $result === true
var_dump($result);
```

3. **Verify database changes:**
```sql
-- Check updated fields
SELECT art, kategorie, anzahl, datum, bemerkung
FROM wp_ahgmh_submissions
WHERE id = 126;

-- Expected: All fields updated to new values
```

4. **Check moderation history:**
```sql
SELECT * FROM wp_ahgmh_moderation_history
WHERE submission_id = 126 AND action = 'edit'
ORDER BY created_at DESC
LIMIT 1;

-- Expected results:
-- action = 'edit'
-- previous_status = (unchanged)
-- new_status = (unchanged)
-- comment = 'Anzahl von 1 auf 2 korrigiert'
```

### Success Criteria
- ✅ `edit()` returns `true`
- ✅ Specified fields updated in database
- ✅ Status remains unchanged
- ✅ History entry created with edit action
- ✅ Activity log triggered with previous_data and updated_data

---

## Test Scenario 5: Edit Flow with Forbidden Fields

### Objective
Verify that attempting to edit fields not in the allowed list doesn't cause errors (fields are simply ignored during sanitization).

### Test Steps

1. **Attempt to edit with forbidden fields:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->edit(
    127,  // submission_id
    1,    // obmann_user_id
    [
        'art' => 'Rehbock',
        'email' => 'hacker@example.com',  // Forbidden field
        'status' => 'approved',           // Forbidden field
        'approved_by' => 999              // Forbidden field
    ],
    'Versuch mit verbotenen Feldern'
);

// Expected: $result === true
// Only 'art' will be updated; other fields ignored by sanitization
var_dump($result);
```

2. **Verify only allowed fields updated:**
```sql
-- Check that forbidden fields were NOT updated
SELECT art, email, status, approved_by
FROM wp_ahgmh_submissions
WHERE id = 127;

-- Expected:
-- art = 'Rehbock' (updated)
-- email = (original value, unchanged)
-- status = (original value, unchanged)
-- approved_by = (original value, unchanged)
```

### Success Criteria
- ✅ `edit()` returns `true`
- ✅ Only allowed field (`art`) updated
- ✅ Forbidden fields (`email`, `status`, `approved_by`) unchanged
- ✅ No errors thrown

### Note
The implementation silently ignores unknown fields via `sanitize_submission_data()`. This is a security feature that prevents unauthorized field modifications.

---

## Test Scenario 6: Error Case - Submission Not Found

### Objective
Verify proper error handling when submission doesn't exist.

### Test Steps

1. **Attempt to approve non-existent submission:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->approve(
    99999,  // Non-existent submission_id
    1,
    'Test comment'
);

// Expected: WP_Error object
var_dump($result);
var_dump(is_wp_error($result)); // Should be true
if (is_wp_error($result)) {
    echo $result->get_error_code();    // Expected: 'submission_not_found'
    echo $result->get_error_message(); // Expected: 'Meldung nicht gefunden.'
}
```

2. **Test with reject:**
```php
$result = $service->reject(99999, 1, 'Test');
// Expected: Same WP_Error with 'submission_not_found'
```

3. **Test with edit:**
```php
$result = $service->edit(99999, 1, ['art' => 'Test'], 'Test');
// Expected: Same WP_Error with 'submission_not_found'
```

### Success Criteria
- ✅ All methods return `WP_Error` object
- ✅ Error code is `'submission_not_found'`
- ✅ German error message displayed
- ✅ No database changes made
- ✅ No emails sent
- ✅ Error logged

---

## Test Scenario 7: Error Case - Invalid Moderator

### Objective
Verify proper error handling when moderator user doesn't exist.

### Test Steps

1. **Attempt to approve with invalid moderator:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->approve(
    128,    // Valid submission_id
    99999,  // Non-existent user_id
    'Test comment'
);

// Expected: WP_Error object
if (is_wp_error($result)) {
    echo $result->get_error_code();    // Expected: 'invalid_moderator'
    echo $result->get_error_message(); // Expected: 'Ungültiger Moderator.'
}
```

2. **Verify no changes made:**
```sql
-- Check submission unchanged
SELECT status, approved_by, approved_at
FROM wp_ahgmh_submissions
WHERE id = 128;

-- Expected: All fields unchanged
```

### Success Criteria
- ✅ Returns `WP_Error` with code `'invalid_moderator'`
- ✅ No status changes in database
- ✅ No history entries created
- ✅ Error logged

---

## Test Scenario 8: Error Case - Update Failed

### Objective
Verify error handling when database update fails.

### Test Steps

1. **Simulate database error (requires manual intervention):**
```php
// Temporarily make submissions table read-only or disconnect database
// Then attempt approval:
$service = new HGMH_Moderation_Service();
$result = $service->approve(129, 1, 'Test');

// Expected: WP_Error with 'update_failed' or 'approval_error'
```

2. **Check error log:**
```bash
tail -f /var/log/wordpress/debug.log
# Expected: Error logged with details
```

### Success Criteria
- ✅ Returns `WP_Error` object
- ✅ Error logged with details
- ✅ Transaction integrity maintained (no partial updates)

---

## Test Scenario 9: Time to Approval Calculation

### Objective
Verify that `time_to_approval` is calculated correctly in minutes.

### Test Steps

1. **Create submission with known timestamp:**
```sql
-- Insert submission 2 hours and 15 minutes ago
INSERT INTO wp_ahgmh_submissions (
    art, kategorie, anzahl, datum, meldegruppe, email, status, erstellt_am
) VALUES (
    'Rehbock', 'Jaehrling', 1, '2026-01-10', 'Gruppe A',
    'test@example.com', 'pending',
    DATE_SUB(NOW(), INTERVAL 135 MINUTE)  -- 2h 15min ago
);
SET @test_id = LAST_INSERT_ID();
```

2. **Approve the submission:**
```php
$service = new HGMH_Moderation_Service();
$result = $service->approve(@test_id, 1, 'Test');
```

3. **Verify time calculation:**
```sql
SELECT time_to_approval FROM wp_ahgmh_submissions WHERE id = @test_id;

-- Expected: value close to 135 minutes (allow ±1 minute for processing time)
```

### Success Criteria
- ✅ `time_to_approval` calculated accurately in minutes
- ✅ Calculation accounts for days, hours, and minutes
- ✅ Value stored as integer

---

## Test Scenario 10: Activity Log Integration

### Objective
Verify that WordPress action hooks are triggered correctly for external integrations.

### Test Steps

1. **Add action hook listener:**
```php
add_action('ahgmh_moderation_activity', function($activity_data) {
    // Log activity
    error_log('=== MODERATION ACTIVITY CAPTURED ===');
    error_log('Action: ' . $activity_data['action']);
    error_log('Submission ID: ' . $activity_data['submission_id']);
    error_log('User ID: ' . $activity_data['user_id']);
    error_log('Timestamp: ' . $activity_data['timestamp']);
    error_log('Data: ' . print_r($activity_data['data'], true));
}, 10, 1);
```

2. **Perform moderation actions:**
```php
$service = new HGMH_Moderation_Service();

// Test approve
$service->approve(130, 1, 'Test approve');

// Test reject
$service->reject(131, 1, 'Test reject');

// Test edit
$service->edit(132, 1, ['art' => 'Updated'], 'Test edit');
```

3. **Verify logged activity:**
```bash
tail -f /var/log/wordpress/debug.log
```

### Expected Activity Data Structure

**For approve:**
- `action`: 'approve'
- `data.previous_status`: 'pending'
- `data.new_status`: 'approved'
- `data.time_to_approval`: integer
- `data.comment`: string

**For reject:**
- `action`: 'reject'
- `data.previous_status`: 'pending'
- `data.new_status`: 'rejected'
- `data.comment`: string

**For edit:**
- `action`: 'edit'
- `data.previous_data`: array of old values
- `data.updated_data`: array of new values
- `data.comment`: string

### Success Criteria
- ✅ Action hook triggered for all three operations
- ✅ Correct action name passed
- ✅ All required data fields present
- ✅ Data properly sanitized
- ✅ Timestamp in MySQL format

---

## Test Scenario 11: Email Notification Content

### Objective
Verify that emails contain correct submission data.

### Test Steps

1. **Intercept email without sending:**
```php
add_filter('wp_mail', function($args) {
    error_log('=== EMAIL INTERCEPTED ===');
    error_log('To: ' . $args['to']);
    error_log('Subject: ' . $args['subject']);
    error_log('Message: ' . $args['message']);
    error_log('Headers: ' . print_r($args['headers'], true));

    // Return false to prevent actual sending during tests
    return false;
}, 10, 1);
```

2. **Trigger approval email:**
```php
$service = new HGMH_Moderation_Service();
$service->approve(133, 1, 'Genehmigt');
```

3. **Verify email data:**
Check error log for:
- ✅ Recipient email matches submission email
- ✅ Subject appropriate for approval
- ✅ Message contains submission details (art, kategorie, anzahl, etc.)
- ✅ Message properly formatted and escaped

4. **Trigger rejection email:**
```php
$service->reject(134, 1, 'Bitte Datum korrigieren');
```

5. **Verify rejection email includes reason:**
- ✅ Email contains rejection comment
- ✅ Rejection reason clearly displayed

### Success Criteria
- ✅ Email sent to correct recipient
- ✅ Email contains all submission data
- ✅ Data properly escaped (no HTML injection)
- ✅ Rejection emails include rejection reason
- ✅ Email format follows German language conventions

---

## Test Scenario 12: Moderation History Audit Trail

### Objective
Verify complete audit trail for a submission through multiple moderation actions.

### Test Steps

1. **Create test submission:**
```sql
INSERT INTO wp_ahgmh_submissions (
    art, kategorie, anzahl, datum, meldegruppe, email, status, erstellt_am
) VALUES (
    'Rehbock', 'Jaehrling', 1, '2026-01-10', 'Gruppe A',
    'test@example.com', 'pending', NOW()
);
SET @audit_test_id = LAST_INSERT_ID();
```

2. **Perform multiple moderation actions:**
```php
$service = new HGMH_Moderation_Service();

// 1. Edit submission
$service->edit(@audit_test_id, 1, ['anzahl' => 2], 'Anzahl korrigiert');

// 2. Reject submission
$service->reject(@audit_test_id, 1, 'Datum prüfen');

// 3. Edit again
$service->edit(@audit_test_id, 1, ['datum' => '2026-01-11'], 'Datum korrigiert');

// 4. Approve submission
$service->approve(@audit_test_id, 1, 'Jetzt korrekt');
```

3. **Query complete history:**
```sql
SELECT
    action,
    previous_status,
    new_status,
    moderator_name,
    comment,
    created_at
FROM wp_ahgmh_moderation_history
WHERE submission_id = @audit_test_id
ORDER BY created_at ASC;

-- Expected: 4 rows showing complete timeline
-- Row 1: action='edit', status unchanged
-- Row 2: action='reject', pending -> rejected
-- Row 3: action='edit', status unchanged
-- Row 4: action='approve', rejected -> approved
```

### Success Criteria
- ✅ All actions logged in chronological order
- ✅ Status transitions tracked correctly
- ✅ Moderator information captured for each action
- ✅ Comments preserved
- ✅ Timestamps accurate

---

## Data Sanitization Tests

### Test Scenario 13: XSS Prevention in Edit Data

### Objective
Verify that malicious input is sanitized.

### Test Steps

```php
$service = new HGMH_Moderation_Service();
$result = $service->edit(
    135,
    1,
    [
        'art' => '<script>alert("XSS")</script>Rehbock',
        'bemerkung' => '<img src=x onerror=alert("XSS")>Test'
    ],
    'XSS test'
);

// Verify sanitization
$submission = $wpdb->get_row("SELECT art, bemerkung FROM wp_ahgmh_submissions WHERE id = 135");

// Expected:
// art: HTML tags stripped or escaped
// bemerkung: HTML tags stripped or escaped (textarea field)
```

### Success Criteria
- ✅ Malicious scripts removed or escaped
- ✅ Data safely stored in database
- ✅ No JavaScript execution possible when displayed

---

## Performance Tests

### Test Scenario 14: Bulk Moderation Performance

### Objective
Verify performance with multiple moderation actions.

### Test Steps

```php
$service = new HGMH_Moderation_Service();
$start_time = microtime(true);

for ($i = 1; $i <= 50; $i++) {
    // Assumes submissions with IDs 200-249 exist
    $service->approve(200 + $i, 1, "Batch approval $i");
}

$end_time = microtime(true);
$duration = $end_time - $start_time;

echo "50 approvals completed in: " . $duration . " seconds\n";
echo "Average per approval: " . ($duration / 50) . " seconds\n";
```

### Success Criteria
- ✅ All 50 approvals complete without errors
- ✅ All history entries created
- ✅ Performance acceptable (< 1 second per approval)
- ✅ No memory leaks

---

## Regression Tests

### Test Scenario 15: Verify Existing Functionality Unchanged

### Objective
Ensure that adding moderation service doesn't break existing plugin functionality.

### Test Steps

1. **Verify plugin activates:**
```bash
wp plugin activate abschussplan-hgmh
```

2. **Check for PHP errors:**
```bash
tail -f /var/log/wordpress/debug.log
# Should show no errors during activation
```

3. **Verify tables created:**
```sql
SHOW TABLES LIKE '%ahgmh%';
-- Expected: All required tables exist
```

4. **Test existing shortcodes still work:**
```php
// Test that existing shortcodes render without errors
do_shortcode('[existing_shortcode]');
```

### Success Criteria
- ✅ Plugin activates without errors
- ✅ All database tables created
- ✅ Existing functionality unchanged
- ✅ No PHP warnings or notices

---

## Summary Checklist

Before marking the moderation service as complete, verify:

### Core Functionality
- [ ] Approve flow works correctly (status, timestamps, metrics)
- [ ] Reject flow works correctly (with and without comments)
- [ ] Edit flow works correctly (allowed fields only)
- [ ] All actions logged to moderation_history
- [ ] Activity log hooks triggered correctly
- [ ] Email notifications sent

### Error Handling
- [ ] Invalid submission ID handled gracefully
- [ ] Invalid moderator ID handled gracefully
- [ ] Database errors logged and returned as WP_Error
- [ ] All error messages in German

### Security
- [ ] Input data sanitized
- [ ] XSS attacks prevented
- [ ] Forbidden fields cannot be edited
- [ ] Only moderators can perform actions (implement permission checks in controllers)

### Data Integrity
- [ ] Status transitions tracked correctly
- [ ] Timestamps accurate
- [ ] Metrics calculated correctly
- [ ] Audit trail complete
- [ ] No partial updates on failures

### Integration
- [ ] Services registered in main plugin file
- [ ] Dependencies (Repository, Email Service) working
- [ ] No conflicts with existing plugin code
- [ ] WordPress hooks used correctly

---

## Notes for Developers

### Known Limitations

1. **Rejection Reason Not Required**: The spec states rejection reason is required, but the current implementation allows empty comments. Consider adding validation:
```php
if (empty(trim($comment))) {
    return new WP_Error('reason_required', __('Ablehnungsgrund ist erforderlich.', 'abschussplan-hgmh'));
}
```

2. **No Permission Checks**: The service layer doesn't check if the user has permission to moderate. This should be implemented in the controller layer that calls this service.

3. **Email Service is Stub**: Email functionality is implemented but the Email Service is a stub. Verify emails are actually sent when Email Service is fully implemented.

4. **No Transaction Support**: Multiple database operations are not wrapped in transactions. If one operation fails, previous operations are not rolled back.

### Future Enhancements

1. Add permission checks in controllers before calling service methods
2. Implement database transactions for atomic operations
3. Add unit tests using WordPress testing framework
4. Add integration tests for end-to-end workflows
5. Implement rate limiting for bulk operations
6. Add webhooks for external integrations
7. Create admin UI for viewing moderation history

---

## Test Report Template

When conducting tests, document results using this template:

```
Test Date: YYYY-MM-DD
Tester: [Name]
Environment: [Development/Staging/Production]

Test Scenario: [Number and Name]
Status: [PASS/FAIL]
Notes: [Any observations or issues]

[Repeat for each scenario]

Summary:
- Total Tests: X
- Passed: X
- Failed: X
- Blocked: X

Critical Issues Found:
1. [Issue description]

Non-Critical Issues:
1. [Issue description]

Recommendations:
1. [Recommendation]
```

---

**Document Version:** 1.0
**Last Updated:** 2026-01-13
**Author:** Auto-Claude
**Status:** Ready for Testing
