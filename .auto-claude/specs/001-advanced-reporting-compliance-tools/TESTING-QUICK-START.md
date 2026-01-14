# Quick Start Testing Guide
## Advanced Reporting & Compliance Tools

**Last Updated:** 2026-01-12
**Status:** ✅ Ready for Testing

---

## 🎯 Quick Test (15 minutes)

### Prerequisites
1. WordPress admin access (manage_options capability)
2. Sample data in database (submissions, limits)
3. DOMPDF installed for PDF testing (optional)

### 6 Essential Tests

#### 1. ✅ Compliance Dashboard (2 min)
- Navigate to **Abschussplan → ✓ Compliance**
- Verify: Page loads, summary cards visible, progress bars show colors
- Test: Change filters (season, species, meldegruppe)
- Expected: Data updates via AJAX without page reload

#### 2. ✅ Seasonal Report (3 min)
- Navigate to **Abschussplan → 📊 Reports**
- Click **Saisonbericht** card
- Click **Aktuelle Saison** button
- Click **Vorschau anzeigen**
- Expected: Report preview appears in right panel with seasonal data

#### 3. ✅ CSV Download (2 min)
- With report preview visible, select **CSV herunterladen**
- Click **Generieren**
- Expected: CSV file downloads, opens in Excel with German characters correct

#### 4. ✅ PDF Download (2 min)
⚠️ Requires DOMPDF: `composer install` in plugin directory

- With report preview visible, select **PDF herunterladen**
- Click **Generieren**
- Expected: PDF downloads with professional formatting

#### 5. ✅ Email Test (3 min)
- With report preview visible, select **Per E-Mail senden**
- Enter your email address
- Click **Senden**
- Check inbox (and spam folder)
- Expected: Email arrives with report summary and PDF attachment

#### 6. ✅ Scheduled Report (3 min)
- Navigate to **Abschussplan → 🕐 Geplante Berichte**
- Click **+ Neuer Zeitplan**
- Fill form:
  - Name: "Test Report"
  - Type: Compliance-Bericht
  - Frequency: Täglich
  - Time: (any time)
  - Recipients: (your email)
  - Check "Aktiviert"
- Click **Test-E-Mail senden** → Should receive test email
- Click **Zeitplan speichern**
- Expected: Schedule appears in list with "Aktiv" status

---

## ✅ Acceptance Criteria Checklist

All 6 criteria must pass:

- [ ] **AC1:** Seasonal report generation works → Test #2
- [ ] **AC2:** Custom date range reports work → Test date range report type
- [ ] **AC3:** Compliance dashboard displays correctly → Test #1
- [ ] **AC4:** PDF export downloads properly → Test #4
- [ ] **AC5:** Scheduled emails are sent → Test #6
- [ ] **AC6:** All filters work correctly → Test #1 filters

---

## 🔧 Troubleshooting

### "DOMPDF not available"
```bash
cd wp-content/plugins/abschussplan-hgmh
composer install --no-dev --optimize-autoloader
```

### Emails not sending
- Install WP Mail SMTP plugin
- Check WordPress mail settings
- Check spam folder

### No data in reports
- Verify submissions exist in database
- Check date ranges (hunting season: April 1 - March 31)
- Try "Alle" (All) for species/meldegruppe filters

### JavaScript errors
- Clear browser cache
- Check browser console (F12)
- Verify jQuery loaded

---

## 📋 Component Verification

### Files Exist? (Quick Check)
```bash
# Services (5 files)
ls -l wp-content/plugins/abschussplan-hgmh/admin/services/class-report*.php
ls -l wp-content/plugins/abschussplan-hgmh/admin/services/class-pdf-service.php
ls -l wp-content/plugins/abschussplan-hgmh/admin/services/class-email-service.php
ls -l wp-content/plugins/abschussplan-hgmh/admin/services/class-scheduler-service.php

# Controllers (3 files)
ls -l wp-content/plugins/abschussplan-hgmh/admin/controllers/class-compliance-controller.php
ls -l wp-content/plugins/abschussplan-hgmh/admin/controllers/class-reports-controller.php
ls -l wp-content/plugins/abschussplan-hgmh/admin/controllers/class-schedule-controller.php

# Views (3 files)
ls -l wp-content/plugins/abschussplan-hgmh/admin/views/class-compliance-view.php
ls -l wp-content/plugins/abschussplan-hgmh/admin/views/class-reports-view.php
ls -l wp-content/plugins/abschussplan-hgmh/admin/views/class-schedule-settings-view.php

# Assets (2 files)
ls -l wp-content/plugins/abschussplan-hgmh/admin/assets/admin-reports.*
```

### Menu Items Visible?
Check WordPress admin menu for:
- ✓ Abschussplan → 📊 Dashboard
- ✓ Abschussplan → ✓ Compliance
- ✓ Abschussplan → 📊 Reports
- ✓ Abschussplan → 🕐 Geplante Berichte

---

## 🚀 Pass Criteria

**Mark subtask 6.4 as COMPLETE if:**
1. All 6 essential tests pass ✅
2. All 6 acceptance criteria met ✅
3. No critical errors encountered ✅
4. Documentation created ✅

**Time Required:** 15-20 minutes for quick tests

---

## 📖 Full Testing

For comprehensive testing, see: `INTEGRATION-TESTING.md`
- 18 detailed test cases
- Error handling tests
- Security testing
- Performance testing
- Cross-browser testing

---

## ✅ Sign-Off

**Tested By:** ________________
**Date:** ________________
**Status:** ☐ PASS ☐ FAIL
**Notes:** _________________

---

**Next Steps After Testing:**
1. Update implementation_plan.json (set status: "completed")
2. Commit changes with message: "auto-claude: 6.4 - Integration testing complete"
3. Update QA signoff in plan
4. Ready for production deployment! 🎉
