# Quick Performance Verification Guide

## Quick Start

### Run Performance Test (Browser)
```
http://your-site.com/wp-content/plugins/abschussplan-hgmh/test-performance-verification.php
```
(Must be logged in as admin)

### Run Performance Test (WP-CLI)
```bash
wp eval-file wp-content/plugins/abschussplan-hgmh/test-performance-verification.php
```

## Expected Results

✅ **PASS Criteria:**
- 6+ out of 8 queries use indexes
- Average query time <100ms
- No full table scans

## Manual EXPLAIN Queries

### Test 1: Species Filter
```sql
EXPLAIN SELECT * FROM wp_ahgmh_submissions WHERE game_species = 'Rotwild';
```
Expected: Uses `game_species_idx`

### Test 2: Species + Category
```sql
EXPLAIN SELECT COUNT(*) FROM wp_ahgmh_submissions
WHERE game_species = 'Rotwild' AND field2 = 'Hirsch';
```
Expected: Uses `species_category_idx`

### Test 3: JOIN Query
```sql
EXPLAIN SELECT s.*, j.meldegruppe
FROM wp_ahgmh_submissions s
LEFT JOIN wp_ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk
WHERE s.game_species = 'Rotwild';
```
Expected: Both tables use indexes

## Quick Troubleshooting

### Indexes not used?
```sql
-- Check indexes exist
SHOW INDEX FROM wp_ahgmh_submissions;

-- Update statistics
ANALYZE TABLE wp_ahgmh_submissions;
ANALYZE TABLE wp_ahgmh_jagdbezirke;
```

### Performance still slow?
```sql
-- Optimize tables
OPTIMIZE TABLE wp_ahgmh_submissions;
OPTIMIZE TABLE wp_ahgmh_jagdbezirke;

-- Check table size
SELECT COUNT(*) FROM wp_ahgmh_submissions;
```

## Performance Targets

| Query Type | Target | Status |
|-----------|--------|--------|
| Species filtering | <100ms | [ ] |
| Composite filtering | <100ms | [ ] |
| JOIN operations | <100ms | [ ] |
| Date range queries | <100ms | [ ] |

## Success Checklist

- [ ] All 7 indexes exist on submissions table
- [ ] 1 index exists on jagdbezirke table
- [ ] EXPLAIN shows index usage
- [ ] Average query time <100ms
- [ ] No errors in WordPress admin
- [ ] Performance better than before

## Need Help?

See detailed documentation:
- `.auto-claude/specs/020-add-database-indexes-for-frequently-queried-column/PERFORMANCE_VERIFICATION.md`
- `DATABASE_PERFORMANCE_OPTIMIZATION.md`
- `TESTING.md`
