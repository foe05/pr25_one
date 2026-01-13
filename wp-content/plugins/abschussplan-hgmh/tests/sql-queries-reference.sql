-- ============================================
-- AHGMH Public Form - SQL Reference Queries
-- ============================================
-- Use these queries for manual testing and verification

-- ============================================
-- 1. SCHEMA VERIFICATION
-- ============================================

-- Check if table exists
SHOW TABLES LIKE 'wp_ahgmh_submissions';

-- View table structure
DESCRIBE wp_ahgmh_submissions;

-- Check verification columns specifically
SHOW COLUMNS FROM wp_ahgmh_submissions
WHERE Field LIKE 'verification%' OR Field LIKE 'submitter%' OR Field LIKE 'token%';

-- View table indexes
SHOW INDEX FROM wp_ahgmh_submissions;

-- ============================================
-- 2. VIEW SUBMISSIONS
-- ============================================

-- Get all recent submissions with verification info
SELECT
    id,
    user_id,
    game_species,
    field1 AS abschussdatum,
    field2 AS kategorie,
    field5 AS meldegruppe,
    verification_status,
    submitter_email,
    submitter_ip,
    token_expires_at,
    created_at
FROM wp_ahgmh_submissions
ORDER BY created_at DESC
LIMIT 20;

-- Get submissions by verification status
SELECT * FROM wp_ahgmh_submissions
WHERE verification_status = 'pending'
ORDER BY created_at DESC;

SELECT * FROM wp_ahgmh_submissions
WHERE verification_status = 'verified'
ORDER BY created_at DESC;

SELECT * FROM wp_ahgmh_submissions
WHERE verification_status = 'expired'
ORDER BY created_at DESC;

-- Count submissions by status
SELECT
    verification_status,
    COUNT(*) as count
FROM wp_ahgmh_submissions
GROUP BY verification_status;

-- ============================================
-- 3. SEARCH SUBMISSIONS
-- ============================================

-- Find submission by email
SELECT * FROM wp_ahgmh_submissions
WHERE submitter_email = 'test@example.com';

-- Find submissions by IP
SELECT * FROM wp_ahgmh_submissions
WHERE submitter_ip = '192.168.1.1'
ORDER BY created_at DESC;

-- Find submission by token
SELECT * FROM wp_ahgmh_submissions
WHERE verification_token = 'YOUR_TOKEN_HERE';

-- Find submissions from last hour (for rate limit testing)
SELECT
    submitter_ip,
    COUNT(*) as submission_count,
    GROUP_CONCAT(submitter_email SEPARATOR ', ') as emails
FROM wp_ahgmh_submissions
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY submitter_ip
HAVING submission_count >= 5;

-- ============================================
-- 4. TOKEN EXPIRY CHECKS
-- ============================================

-- Find expired tokens that are still pending
SELECT
    id,
    submitter_email,
    verification_status,
    token_expires_at,
    created_at,
    TIMESTAMPDIFF(HOUR, NOW(), token_expires_at) as hours_until_expiry
FROM wp_ahgmh_submissions
WHERE verification_status = 'pending'
AND token_expires_at < NOW();

-- Find tokens expiring soon (within 1 hour)
SELECT
    id,
    submitter_email,
    verification_status,
    token_expires_at,
    TIMESTAMPDIFF(MINUTE, NOW(), token_expires_at) as minutes_until_expiry
FROM wp_ahgmh_submissions
WHERE verification_status = 'pending'
AND token_expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
ORDER BY token_expires_at ASC;

-- Check token expiry time is 48 hours from creation
SELECT
    id,
    submitter_email,
    created_at,
    token_expires_at,
    TIMESTAMPDIFF(HOUR, created_at, token_expires_at) as token_validity_hours
FROM wp_ahgmh_submissions
WHERE verification_status = 'pending'
ORDER BY created_at DESC
LIMIT 10;

-- ============================================
-- 5. RATE LIMITING DATA
-- ============================================

-- Check rate limit transients
SELECT
    option_name,
    option_value,
    autoload
FROM wp_options
WHERE option_name LIKE '_transient_ahgmh_rate_limit_%'
   OR option_name LIKE '_transient_timeout_ahgmh_rate_limit_%';

-- Count submissions per IP in last hour
SELECT
    submitter_ip,
    COUNT(*) as submission_count,
    MIN(created_at) as first_submission,
    MAX(created_at) as last_submission,
    GROUP_CONCAT(id ORDER BY created_at) as submission_ids
FROM wp_ahgmh_submissions
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY submitter_ip
ORDER BY submission_count DESC;

-- ============================================
-- 6. TESTING HELPERS
-- ============================================

-- Manually expire a token (for testing)
UPDATE wp_ahgmh_submissions
SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR)
WHERE id = YOUR_SUBMISSION_ID;

-- Manually expire all pending tokens (for bulk testing)
UPDATE wp_ahgmh_submissions
SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR)
WHERE verification_status = 'pending'
LIMIT 10;

-- Mark expired tokens as expired status (simulates cleanup)
UPDATE wp_ahgmh_submissions
SET verification_status = 'expired'
WHERE verification_status = 'pending'
AND token_expires_at < NOW();

-- Reset verification status (for re-testing)
UPDATE wp_ahgmh_submissions
SET verification_status = 'pending',
    token_expires_at = DATE_ADD(NOW(), INTERVAL 48 HOUR)
WHERE id = YOUR_SUBMISSION_ID;

-- Delete test submissions (use with caution!)
DELETE FROM wp_ahgmh_submissions
WHERE submitter_email LIKE 'test%@example.com';

-- ============================================
-- 7. VERIFICATION WORKFLOW TRACKING
-- ============================================

-- Track a specific submission through its lifecycle
SELECT
    id,
    submitter_email,
    verification_status,
    verification_token,
    created_at,
    token_expires_at,
    CASE
        WHEN verification_status = 'verified' THEN 'Completed'
        WHEN verification_status = 'expired' THEN 'Failed - Expired'
        WHEN token_expires_at < NOW() THEN 'Pending but Expired'
        ELSE 'Pending - Active'
    END as status_description,
    TIMESTAMPDIFF(HOUR, created_at, COALESCE(token_expires_at, NOW())) as age_hours
FROM wp_ahgmh_submissions
WHERE submitter_email = 'YOUR_EMAIL_HERE'
ORDER BY created_at DESC;

-- ============================================
-- 8. STATISTICS & REPORTING
-- ============================================

-- Overall verification success rate
SELECT
    COUNT(*) as total_submissions,
    SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified_count,
    SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN verification_status = 'expired' THEN 1 ELSE 0 END) as expired_count,
    ROUND(SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as verification_rate_percent
FROM wp_ahgmh_submissions
WHERE user_id = 0;  -- Only public submissions

-- Submissions per day (last 7 days)
SELECT
    DATE(created_at) as date,
    COUNT(*) as total,
    SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending
FROM wp_ahgmh_submissions
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
AND user_id = 0
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Top submitting IPs (for spam detection)
SELECT
    submitter_ip,
    COUNT(*) as submission_count,
    COUNT(DISTINCT submitter_email) as unique_emails,
    MIN(created_at) as first_seen,
    MAX(created_at) as last_seen
FROM wp_ahgmh_submissions
WHERE user_id = 0
GROUP BY submitter_ip
HAVING submission_count >= 3
ORDER BY submission_count DESC
LIMIT 20;

-- Average time to verification (for verified submissions)
-- Note: This is an approximation as we don't track exact verification time
SELECT
    AVG(TIMESTAMPDIFF(HOUR, created_at, token_expires_at)) as avg_token_validity_hours,
    MIN(created_at) as oldest_submission,
    MAX(created_at) as newest_submission,
    COUNT(*) as total_verified
FROM wp_ahgmh_submissions
WHERE verification_status = 'verified';

-- ============================================
-- 9. DATA INTEGRITY CHECKS
-- ============================================

-- Find submissions with null or empty emails (should not exist for public forms)
SELECT * FROM wp_ahgmh_submissions
WHERE user_id = 0
AND (submitter_email IS NULL OR submitter_email = '');

-- Find submissions without tokens (should not exist)
SELECT * FROM wp_ahgmh_submissions
WHERE user_id = 0
AND (verification_token IS NULL OR verification_token = '');

-- Find submissions with invalid token length
SELECT
    id,
    submitter_email,
    LENGTH(verification_token) as token_length
FROM wp_ahgmh_submissions
WHERE user_id = 0
AND LENGTH(verification_token) != 64;

-- Find submissions with expiry time in the past but still pending
SELECT
    id,
    submitter_email,
    verification_status,
    token_expires_at,
    TIMESTAMPDIFF(HOUR, token_expires_at, NOW()) as hours_overdue
FROM wp_ahgmh_submissions
WHERE verification_status = 'pending'
AND token_expires_at < NOW()
ORDER BY token_expires_at ASC;

-- ============================================
-- 10. CLEANUP OPERATIONS
-- ============================================

-- Clean up old verified submissions (older than 1 year) - USE WITH CAUTION
-- DELETE FROM wp_ahgmh_submissions
-- WHERE verification_status = 'verified'
-- AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Clean up expired submissions (older than 30 days) - USE WITH CAUTION
-- DELETE FROM wp_ahgmh_submissions
-- WHERE verification_status = 'expired'
-- AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Archive old submissions to backup table (create backup table first)
-- CREATE TABLE wp_ahgmh_submissions_archive LIKE wp_ahgmh_submissions;
-- INSERT INTO wp_ahgmh_submissions_archive
-- SELECT * FROM wp_ahgmh_submissions
-- WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- ============================================
-- 11. DEBUGGING QUERIES
-- ============================================

-- Get detailed info for a specific submission ID
SELECT * FROM wp_ahgmh_submissions
WHERE id = YOUR_SUBMISSION_ID;

-- Check if email was used before
SELECT
    id,
    submitter_email,
    verification_status,
    created_at
FROM wp_ahgmh_submissions
WHERE submitter_email = 'YOUR_EMAIL_HERE'
ORDER BY created_at DESC;

-- Find most recent submission from your IP
SELECT * FROM wp_ahgmh_submissions
WHERE submitter_ip = 'YOUR_IP_HERE'
ORDER BY created_at DESC
LIMIT 5;

-- Check for duplicate WUS numbers
SELECT
    field3 as wus,
    COUNT(*) as count,
    GROUP_CONCAT(id) as submission_ids
FROM wp_ahgmh_submissions
WHERE field3 != ''
GROUP BY field3
HAVING count > 1;

-- ============================================
-- NOTES:
-- ============================================
-- Replace 'wp_' prefix with your actual WordPress table prefix
-- Replace YOUR_SUBMISSION_ID, YOUR_EMAIL_HERE, etc. with actual values
-- Use DELETE queries with extreme caution - always backup first
-- For production, consider adding WHERE clauses to limit results
-- ============================================
