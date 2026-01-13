# Moderation Service Tests

This directory contains end-to-end verification tests for the HGMH Moderation Service.

## Test Files

### test-moderation-service-e2e.php

Comprehensive end-to-end test script that verifies:

1. **Approve Flow**: Tests submission approval including status updates, timestamp recording, time_to_approval calculation, history logging, and activity triggers
2. **Reject Flow**: Tests submission rejection with and without comments, including history logging
3. **Edit Flow**: Tests submission editing with allowed fields (art, kategorie, anzahl, datum, meldegruppe, bemerkung)
4. **Security**: Verifies forbidden fields (status, approved_by, email, user_id) cannot be modified via edit()
5. **Error Handling**: Tests invalid submission IDs and invalid moderator IDs
6. **Activity Log Integration**: Verifies WordPress action hooks are triggered correctly

## Running the Tests

### Prerequisites

- WordPress environment with the plugin activated
- Database tables created (wp_ahgmh_submissions, wp_ahgmh_moderation_history)
- PHP CLI or WordPress admin access

### Method 1: PHP CLI

```bash
cd wp-content/plugins/abschussplan-hgmh/tests
php test-moderation-service-e2e.php
```

### Method 2: WordPress Admin

Create a temporary admin page that includes and runs the test:

```php
// In a temporary admin page or using WP CLI
require_once('wp-content/plugins/abschussplan-hgmh/tests/test-moderation-service-e2e.php');
```

### Method 3: WP-CLI (if available)

```bash
wp eval-file wp-content/plugins/abschussplan-hgmh/tests/test-moderation-service-e2e.php
```

## Test Output

The test script provides detailed output for each test case:

```
========================================
MODERATION SERVICE E2E VERIFICATION
========================================

Test Setup:
  Moderator ID: 1

TEST 1: Approve Flow (Happy Path)
-----------------------------------
[✓ PASS] 1.1 Create test submission
    → Submission ID: 123
[✓ PASS] 1.2 approve() returns success
[✓ PASS] 1.3 Status changed to 'approved'
    → Status: approved
[✓ PASS] 1.4 approved_by field set
    → approved_by: 1
...

========================================
TEST SUMMARY
========================================

Total Tests: 45
Passed: 43
Failed: 2
Pass Rate: 95.6%
```

## Test Data Cleanup

The test script automatically cleans up all test data:
- Test submissions are deleted from wp_ahgmh_submissions
- Related history entries are deleted from wp_ahgmh_moderation_history
- Test moderator user is removed (if created)

## Known Issues

### Schema Mismatch

The database schema uses generic field names (`field1`, `field2`, etc.) but the Moderation Service's `sanitize_submission_data()` method uses semantic names (`art`, `kategorie`, `anzahl`, etc.). This creates a mismatch that needs to be resolved:

**Option 1**: Update Submission Repository to map semantic names to database fields
**Option 2**: Update Moderation Service to use generic field names
**Option 3**: Migrate database schema to use semantic field names

The current test accounts for this discrepancy and documents the expected behavior.

### Missing Reason Validation

Per the spec, `reject()` should require a non-empty reason, but the current implementation allows empty comments. Consider adding validation:

```php
public function reject($submission_id, $obmann_user_id, $comment = '') {
    if (empty(trim($comment))) {
        return new WP_Error('reason_required',
            __('Ablehnungsgrund ist erforderlich.', 'abschussplan-hgmh'));
    }
    // ... rest of method
}
```

## Coverage

### What is Tested ✅

- ✅ approve() happy path with all side effects
- ✅ reject() with comment
- ✅ reject() without comment (documents spec discrepancy)
- ✅ edit() with allowed fields
- ✅ edit() with forbidden fields (security test)
- ✅ Error handling for invalid submission ID
- ✅ Error handling for invalid moderator ID
- ✅ Activity log hook triggering
- ✅ Moderation history logging
- ✅ Time to approval calculation

### What is NOT Tested ❌

- ❌ Email notifications (Email Service is a stub)
- ❌ Permission/capability checks (should be in controller layer)
- ❌ Database transaction rollback on errors
- ❌ Performance under load
- ❌ Concurrent modification handling
- ❌ XSS/SQL injection attempts (basic sanitization tested via field filtering)

## Future Improvements

1. Add unit tests using WordPress testing framework
2. Add integration tests with real Email Service
3. Add performance/load testing
4. Add security scanning for XSS/SQL injection
5. Add UI-level tests for admin interface
6. Implement database transaction support for atomic operations
7. Add webhook/API integration tests

## Documentation

For detailed test scenarios, see:
- `../MODERATION_TEST_SCENARIOS.md` - Manual test scenarios with SQL queries and expected results

For implementation details, see:
- `../includes/services/class-moderation-service.php` - Main service implementation
- `../includes/services/class-submission-repository.php` - Data access layer
- `../includes/services/class-email-service.php` - Email notification service

## Support

For issues or questions about the tests, see the implementation plan:
`.auto-claude/specs/005-moderation-service/implementation_plan.json`
