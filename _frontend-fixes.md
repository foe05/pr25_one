# Frontend & Accessibility Fixes

## Summary

Comprehensive audit and fix of all frontend-facing templates, CSS, JS for HTML validity, WCAG 2.1 AA accessibility, responsive design, and code consistency.

---

## 1. HTML Validity Fixes

### Templates: form-template.php, public-form-template.php
- Added `<fieldset>` and `<legend class="visually-hidden">` wrapping all form fields for proper form structure
- Added `novalidate` to `<form>` elements (JS handles validation)
- Added `aria-required="true"` on all required fields
- Added visual required indicators with `<span class="text-danger" aria-hidden="true">*</span>`
- Added `aria-describedby` linking WUS input to its help text
- Added `autocomplete="email"` on email field (public form)

### Templates: table-template.php, summary-table-template.php, frontend/templates/table.php
- Added `<caption class="visually-hidden">` to all data tables
- Fixed pagination: `esc_html__()` was used on strings containing HTML entities (`&laquo;`, `&raquo;`), causing literal `&laquo;` to appear instead of chevrons. Split into `esc_html__()` for text + raw HTML entities outside
- Added `aria-current="page"` on active pagination items
- Added `aria-disabled="true"` on disabled pagination items
- Added `aria-hidden="true"` on ellipsis spans in pagination
- Added `aria-label` for previous/next page links

### Templates: summary-template.php
- Added `scope="col"` to all `<th>` elements
- Moved inline `style="padding: 6px 12px;"` on status badges to CSS class `.ahgmh-status-badge`

### Frontend table.php (moderation)
- Replaced emoji characters in moderation buttons with HTML entities + visible text labels for better accessibility and cross-platform support
- Added `aria-label` with dynamic submission ID to each moderation button
- Added `<div aria-live="polite" id="moderation-status-announcer">` for screen reader announcements
- Added `novalidate` to edit and reject modal forms
- Changed export button from `onclick="exportCSV()"` inline handler to `data-species` attribute with vanilla JS event listener

---

## 2. Accessibility (WCAG 2.1 AA) Fixes

### ARIA Attributes
- Added `aria-live="polite"` on all response/notification containers (`#abschuss-form-response`, `#edit-form-response`, `#reject-form-response`)
- Changed info alert `role="alert"` to `role="status"` for non-urgent informational messages
- Added `aria-busy="true/false"` on submit buttons during AJAX requests
- Added `aria-busy` on select elements during dynamic content loading (Jagdbezirk)
- Added `aria-invalid="true"` on invalid form fields (set/removed dynamically in JS)

### Focus Management
- Modal open: focus moves to first form field (`shown.bs.modal` event)
- Modal close after edit: focus returns to the edit button that triggered it
- Reject modal: focus moves to comment textarea on open
- Admin schedule modal: focus moves to name field on open, returns to trigger on close
- Added Escape key handler for admin schedule modal

### Keyboard Support
- Added keyboard event handling (`Enter`/`Space`) on moderation buttons
- Added arrow key, Home, End navigation for admin tab switching
- All interactive elements are natively focusable (buttons, links, inputs)

### Screen Reader Improvements
- Added `role="tooltip"` on admin tooltips
- Added `role="alert"` and `aria-live="assertive"` on admin notifications
- Added `role="status"` on empty-state messages
- Added screen-reader-only announcer div for moderation actions

### Color & Contrast
- All status badges use Bootstrap's built-in high-contrast color pairs (bg-success/text-white, bg-danger/text-white, bg-secondary/text-white)
- Focus indicators use #2271b1 with sufficient contrast ratios (documented in CSS)

---

## 3. Responsive Design Fixes

### CSS: style.css
- Added responsive card-based layout for `.abschuss-table` (same pattern as `.submissions-table`)
- Added `min-height: 44px` on table cells in mobile card view for touch targets
- Moderation button group stacks vertically on mobile with `min-height: 44px` per button
- Pagination items have `min-width: 44px; min-height: 44px` for touch-friendly tap targets
- Submit button goes full-width on mobile
- Added `flex-wrap: wrap` on pagination for small screens
- Added `.abschuss-empty-cta` with `min-height: 44px` for touch target compliance

### CSS: admin-reports.css
- Focus styles added for radio button cards (`:focus-visible + .report-type-card`)
- Focus styles added for toggle switch (`:focus-visible + .toggle-slider`)
- Focus styles added for modal close button

### CSS: import.css
- Replaced `!important` on mobile mapping table styles with more specific selectors

---

## 4. CSS Cleanup

### style.css
- Consolidated `.submissions-table` and `.abschuss-table` selectors (shared styles)
- Added `.abschuss-empty-*` utility classes for empty state styling
- Moved inline badge padding to `.ahgmh-status-badge` class
- Added `@media (prefers-reduced-motion: reduce)` to disable skeleton animations
- Added `.ahgmh-skip-link` styles (for future skip-link implementation)
- Added `.moderation-notification` margin class
- Removed duplicated dark-theme selectors, consolidated into single block

### admin-reports.css
- Changed `display: none` on hidden radio inputs to accessible visually-hidden pattern (`position: absolute; clip: rect(0,0,0,0)`)

---

## 5. JS Improvements

### form-validation.js
- Fixed English validation message "This field is required" changed to German "Dieses Feld ist erforderlich."
- Extracted `resetSubmitState()` helper to avoid code duplication
- Added `aria-busy` attribute management on submit button
- Added `aria-invalid` attribute management on invalid fields
- Added `aria-busy` on table body during skeleton loading
- Added timeout-specific error message
- Changed `const` to `var` for broader browser compatibility

### public-form-validation.js
- Replaced `alert()` calls in WUS validation with inline error messages (`.form-error`)
- Fixed validation message: "Bitte wählen Sie einen Jagdbezirk aus" changed to "Bitte wählen Sie eine Meldegruppe aus" (was mislabeled)
- Added `aria-busy`, `aria-invalid` attribute management
- Extracted `resetSubmitState()` helper
- Changed `const` to `var` for broader browser compatibility

### table-moderation.js
- Added `announceStatus()` function for screen reader live announcements
- Added focus management: modal returns focus to triggering button on close
- Added `shown.bs.modal` focus handler for edit and reject modals
- Added keyboard event handler for moderation buttons (Enter/Space)
- Changed delegated event handlers (`$(document).on()`) for dynamic button support
- Replaced emoji button content with HTML entities + text labels
- Added `aria-busy` on buttons during AJAX

### admin/assets/modules/core.js
- Added `role="alert"` and `aria-live="assertive"` on notification elements
- Added `role="tooltip"` on tooltip elements
- Used `$('<div>', {...})` jQuery constructor instead of HTML string concatenation for safer output
- Added keyboard navigation for tabs: Arrow keys, Home, End
- Added `aria-selected` management on tabs

### admin/assets/admin-reports.js
- Replaced all `alert()` calls (20+) with `notify()` helper that uses `AHGMH.showNotification()` with `alert()` fallback
- Added Escape key handler to close schedule modal
- Added focus management: store trigger element and return focus on modal close
- Focus moves to first input when schedule modal opens

---

## Files Modified

### Templates
- `templates/form-template.php`
- `templates/public-form-template.php`
- `templates/table-template.php`
- `templates/summary-template.php`
- `templates/summary-table-template.php`
- `frontend/templates/table.php`

### CSS
- `assets/css/style.css`
- `admin/assets/admin-reports.css`
- `admin/assets/import.css`

### JS
- `assets/js/form-validation.js`
- `assets/js/public-form-validation.js`
- `frontend/assets/js/table-moderation.js`
- `admin/assets/modules/core.js`
- `admin/assets/admin-reports.js`

### Not Modified (no issues found)
- `assets/css/pdf-styles.css` - PDF-only styles, no accessibility impact
- `admin/assets/admin-modern.css` - Already had comprehensive focus styles
- `admin/assets/admin-modern-extracted.css` - No issues found
- `templates/email/*.php` - Email templates (HTML email, limited a11y scope)
- `templates/pdf/*.php` - PDF templates (print output, not interactive)
- `admin/assets/modules/dashboard.js` - No interactive a11y issues
- `admin/assets/modules/obmann-management.js` - No changes needed
- `admin/assets/modules/quick-actions.js` - No changes needed
- `admin/assets/modules/wildart-config.js` - No changes needed
- `admin/assets/import.js` - No changes needed (CSS-only fix)
- `admin/assets/admin-modern-legacy.js` - No changes needed
- `frontend/shortcodes/class-table-shortcode.php` - PHP class (not in scope per constraints)
