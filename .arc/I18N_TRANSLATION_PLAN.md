# Internationalization (i18n) Plan - Abschussplan HGMH v2.4.0
# Master-Detail Backend & Hegegemeinschafts-Terminologie

## OVERVIEW

### Primary Language: German (de_DE)
Das Plugin ist primär für deutsche Hegegemeinschaften entwickelt, aber mit internationaler Struktur für andere Jagdverwaltungssysteme.

### Text Domain: `abschussplan-hgmh`
Konsistente Verwendung in allen neuen Features.

## 1. NEW STRINGS INVENTORY (v2.2.0)

### Master-Detail Backend Interface
```php
// Wildart Configuration
__('Wildart konfigurieren', 'abschussplan-hgmh')
__('Wildart auswählen', 'abschussplan-hgmh')
__('Neue Wildart hinzufügen', 'abschussplan-hgmh')
__('Wildart löschen', 'abschussplan-hgmh')
__('Standard-Wildarten können nicht gelöscht werden', 'abschussplan-hgmh')

// Meldegruppen Management  
__('Meldegruppen für %s', 'abschussplan-hgmh') // %s = Wildart name
__('Neue Meldegruppe hinzufügen', 'abschussplan-hgmh')
__('Meldegruppe bearbeiten', 'abschussplan-hgmh')
__('Meldegruppe löschen', 'abschussplan-hgmh')
__('Meldegruppe umbenennen', 'abschussplan-hgmh')

// Kategorien Management
__('Kategorien verwalten', 'abschussplan-hgmh')
__('Neue Kategorie hinzufügen', 'abschussplan-hgmh')
__('Kategorie bearbeiten', 'abschussplan-hgmh')
__('Standard-Kategorien', 'abschussplan-hgmh')

// Limits System
__('Limits-Modus', 'abschussplan-hgmh')  
__('Meldegruppen-spezifische Limits', 'abschussplan-hgmh')
__('Hegegemeinschaft Total-Limits', 'abschussplan-hgmh')
__('SOLL-Werte', 'abschussplan-hgmh')
__('IST-Werte', 'abschussplan-hgmh')
__('Limit-Status', 'abschussplan-hgmh')
```

### Permission System
```php  
// User Roles
__('Besucher', 'abschussplan-hgmh')          // Public visitors
__('Obmann', 'abschussplan-hgmh')            // Group leader  
__('Vorstand', 'abschussplan-hgmh')          // Board member

// Permission Messages
__('Zugriff verweigert. Anmeldung erforderlich.', 'abschussplan-hgmh')
__('Zugriff verweigert. Nur für Vorstände.', 'abschussplan-hgmh')
__('Sie sind als Obmann für %s in %s zugewiesen', 'abschussplan-hgmh') // %s = Meldegruppe, Wildart

// Obmann Management
__('Obmann-Verwaltung', 'abschussplan-hgmh')
__('Obmann zuweisen', 'abschussplan-hgmh')
__('Zuweisung entfernen', 'abschussplan-hgmh')
__('Benutzer auswählen', 'abschussplan-hgmh')
__('Meldegruppe auswählen', 'abschussplan-hgmh')
__('Wildart auswählen', 'abschussplan-hgmh')
```

### Admin Interface
```php
// Tabbed Interface
__('Dashboard', 'abschussplan-hgmh')
__('Daten-Verwaltung', 'abschussplan-hgmh')
__('Obleute', 'abschussplan-hgmh')
__('Kategorien', 'abschussplan-hgmh')
__('Datenbank', 'abschussplan-hgmh')
__('Wildarten-Konfiguration', 'abschussplan-hgmh')
__('CSV Export', 'abschussplan-hgmh')

// Status Messages
__('Erfolgreich gespeichert', 'abschussplan-hgmh')
__('Konfiguration aktualisiert', 'abschussplan-hgmh')
__('Obmann erfolgreich zugewiesen', 'abschussplan-hgmh')
__('Zuweisung erfolgreich entfernt', 'abschussplan-hgmh')
__('Fehler beim Speichern', 'abschussplan-hgmh')

// CSV Export Admin
__('Export URL generieren', 'abschussplan-hgmh')
__('Direkter Download', 'abschussplan-hgmh')
__('URL kopieren', 'abschussplan-hgmh')
__('Diese URLs sind öffentlich zugänglich ohne Anmeldung', 'abschussplan-hgmh')
```

## 2. EXISTING STRINGS AUDIT

### ✅ Already Translated (v2.1.0)
```php
// Form Elements
__('Wildart', 'abschussplan-hgmh')
__('Kategorie', 'abschussplan-hgmh')  
__('Jagdbezirk', 'abschussplan-hgmh')
__('Datum', 'abschussplan-hgmh')
__('Meldung abgeben', 'abschussplan-hgmh')

// Table Headers
__('ID', 'abschussplan-hgmh')
__('Erlegung', 'abschussplan-hgmh')
__('Fallwild', 'abschussplan-hgmh')
__('Erstellt am', 'abschussplan-hgmh')

// Export
__('CSV Export', 'abschussplan-hgmh')
__('Export erfolgreich', 'abschussplan-hgmh')
```

### ⚠️ Missing Translations (Need i18n)
```php
// CRITICAL: Hardcoded strings in new features  
echo '<h3>Wildart konfigurieren</h3>';                    // ❌ Not translatable
echo '<button>Speichern</button>';                         // ❌ Not translatable
alert('Obmann erfolgreich zugewiesen!');                  // ❌ JS not translatable

// Should be:
echo '<h3>' . esc_html__('Wildart konfigurieren', 'abschussplan-hgmh') . '</h3>';
echo '<button>' . esc_html__('Speichern', 'abschussplan-hgmh') . '</button>';
// JS needs wp_localize_script() integration
```

## 3. POT FILE GENERATION

### WordPress i18n Tools Setup
```bash
# Install WordPress i18n tools
npm install @wordpress/scripts --global

# Generate POT file from plugin source
wp i18n make-pot wp-content/plugins/abschussplan-hgmh wp-content/plugins/abschussplan-hgmh/languages/abschussplan-hgmh.pot

# Validate POT file
wp i18n make-json wp-content/plugins/abschussplan-hgmh/languages/
```

### POT File Structure (abschussplan-hgmh.pot)
```pot
# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the Abschussplan HGMH package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
msgid ""
msgstr ""
"Project-Id-Version: Abschussplan HGMH 2.2.0\n"
"Report-Msgid-Bugs-To: https://github.com/foe05/pr25_one\n"
"POT-Creation-Date: 2024-08-19 12:00+0000\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Language-Team: German <de@li.org>\n"

#: includes/class-permissions-service.php:45
msgid "Zugriff verweigert. Anmeldung erforderlich."
msgstr ""

#: includes/class-permissions-service.php:52  
msgid "Zugriff verweigert. Nur für Vorstände."
msgstr ""

#: admin/class-admin-page-modern.php:123
msgid "Wildart konfigurieren"
msgstr ""

#: admin/class-admin-page-modern.php:156
msgid "Meldegruppen für %s"
msgstr ""
```

## 4. GERMAN TRANSLATION (de_DE.po)

### Primary Translation File: de_DE.po
```po
# German translation for Abschussplan HGMH
# Copyright (C) 2024 foe05
# This file is distributed under GPLv3.
#
msgid ""
msgstr ""
"Project-Id-Version: Abschussplan HGMH 2.2.0\n"
"Report-Msgid-Bugs-To: https://github.com/foe05/pr25_one\n"
"POT-Creation-Date: 2024-08-19 12:00+0000\n"
"PO-Revision-Date: 2024-08-19 12:00+0000\n"
"Last-Translator: Developer <dev@example.com>\n"
"Language-Team: German <de@li.org>\n"
"Language: de_DE\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

# Permission System
msgid "Zugriff verweigert. Anmeldung erforderlich."
msgstr "Zugriff verweigert. Anmeldung erforderlich."

msgid "Zugriff verweigert. Nur für Vorstände."  
msgstr "Zugriff verweigert. Nur für Vorstände."

msgid "Sie sind als Obmann für %s in %s zugewiesen"
msgstr "Sie sind als Obmann für %s in %s zugewiesen"

# Master-Detail Interface
msgid "Wildart konfigurieren"
msgstr "Wildart konfigurieren"

msgid "Meldegruppen für %s"
msgstr "Meldegruppen für %s"

msgid "Neue Meldegruppe hinzufügen"
msgstr "Neue Meldegruppe hinzufügen"

# User Roles (German hunting terminology)
msgid "Besucher"
msgstr "Besucher"

msgid "Obmann"
msgstr "Obmann"

msgid "Vorstand" 
msgstr "Vorstand"

# Limits System
msgid "Meldegruppen-spezifische Limits"
msgstr "Meldegruppen-spezifische Limits"

msgid "Hegegemeinschaft Total-Limits"
msgstr "Hegegemeinschaft Total-Limits"

msgid "SOLL-Werte"
msgstr "SOLL-Werte"

msgid "IST-Werte"
msgstr "IST-Werte"
```

## 5. ENGLISH TRANSLATION (en_US.po) 

### For International Use
```po
# English translation for Abschussplan HGMH
msgid "Wildart konfigurieren"
msgstr "Configure Species"

msgid "Meldegruppen für %s"  
msgstr "Report Groups for %s"

msgid "Obmann"
msgstr "Group Leader"

msgid "Vorstand"
msgstr "Board Member"

msgid "Besucher"
msgstr "Visitor"

msgid "Meldegruppen-spezifische Limits"
msgstr "Group-Specific Limits"

msgid "Hegegemeinschaft Total-Limits"
msgstr "Association Total Limits"

msgid "SOLL-Werte"
msgstr "Target Values"

msgid "IST-Werte"
msgstr "Actual Values"

msgid "Zugriff verweigert. Anmeldung erforderlich."
msgstr "Access denied. Login required."

msgid "Zugriff verweigert. Nur für Vorstände."
msgstr "Access denied. Board members only."
```

## 6. JAVASCRIPT INTERNATIONALIZATION

### wp_localize_script() Implementation
```php
// In admin/class-admin-page-modern.php
public function enqueue_admin_assets() {
    wp_enqueue_script(
        'ahgmh-admin-modern',
        plugin_dir_url(__FILE__) . 'assets/admin-modern.js',
        array('jquery'),
        '2.2.0',
        true
    );
    
    // Localize script for JavaScript translations
    wp_localize_script('ahgmh-admin-modern', 'ahgmhL10n', array(
        'obmannAssignedSuccess'  => __('Obmann erfolgreich zugewiesen!', 'abschussplan-hgmh'),
        'assignmentRemovedSuccess' => __('Zuweisung erfolgreich entfernt!', 'abschussplan-hgmh'),
        'configurationSaved'     => __('Konfiguration gespeichert!', 'abschussplan-hgmh'),
        'errorOccurred'          => __('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'),
        'confirmDelete'          => __('Sind Sie sicher, dass Sie diese Aktion durchführen möchten?', 'abschussplan-hgmh'),
        'pleaseSelectWildart'    => __('Bitte wählen Sie eine Wildart aus.', 'abschussplan-hgmh'),
        'pleaseSelectMeldegruppe' => __('Bitte wählen Sie eine Meldegruppe aus.', 'abschussplan-hgmh'),
        'ajaxUrl'                => admin_url('admin-ajax.php'),
        'nonce'                  => wp_create_nonce('ahgmh_admin_nonce')
    ));
}
```

### JavaScript Usage
```javascript
// In admin/assets/admin-modern.js
function assignObmann() {
    // Use localized strings instead of hardcoded text
    if (response.success) {
        showSuccessMessage(ahgmhL10n.obmannAssignedSuccess); // ✅ Translatable
    } else {
        showErrorMessage(ahgmhL10n.errorOccurred); // ✅ Translatable
    }
}

function confirmDelete() {
    return confirm(ahgmhL10n.confirmDelete); // ✅ Translatable
}
```

## 7. CONTEXTUAL TRANSLATIONS

### Hunting-Specific Terminology Context
```php
// Context for translators - use comments
/* translators: Wildart refers to game species (Rotwild, Damwild, etc.) in German hunting */
__('Wildart auswählen', 'abschussplan-hgmh')

/* translators: Obmann is a German hunting term for group leader of a Meldegruppe */
__('Obmann zuweisen', 'abschussplan-hgmh')

/* translators: Hegegemeinschaft is a German hunting association managing multiple districts */
__('Hegegemeinschaft Total-Limits', 'abschussplan-hgmh')

/* translators: SOLL means target/quota in German hunting harvest planning */
__('SOLL-Werte', 'abschussplan-hgmh')

/* translators: IST means actual harvest numbers in German hunting */
__('IST-Werte', 'abschussplan-hgmh')
```

### Pluralization Handling
```php
// Handle German pluralization rules
$submission_count = 5;
printf(
    /* translators: %d is the number of submissions */
    _n(
        '%d Meldung gefunden',
        '%d Meldungen gefunden', 
        $submission_count,
        'abschussplan-hgmh'
    ),
    number_format_i18n($submission_count)
);
```

## 8. LANGUAGE FILE LOADING

### Plugin Text Domain Loading
```php
// In main plugin file: abschussplan-hgmh.php
add_action('plugins_loaded', 'ahgmh_load_textdomain');

function ahgmh_load_textdomain() {
    load_plugin_textdomain(
        'abschussplan-hgmh',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
```

### Languages Directory Structure
```
wp-content/plugins/abschussplan-hgmh/languages/
├── abschussplan-hgmh.pot              # Template file
├── abschussplan-hgmh-de_DE.po         # German source
├── abschussplan-hgmh-de_DE.mo         # German binary
├── abschussplan-hgmh-en_US.po         # English source  
├── abschussplan-hgmh-en_US.mo         # English binary
└── abschussplan-hgmh-de_DE.json       # JavaScript translations
```

## 9. TRANSLATION VALIDATION

### Automated Testing
```php
// Test script for translation completeness
function test_i18n_completeness() {
    $pot_strings = extract_pot_strings();
    $po_strings = extract_po_strings('de_DE');
    
    $missing_translations = array_diff($pot_strings, $po_strings);
    
    if (!empty($missing_translations)) {
        error_log('Missing translations: ' . implode(', ', $missing_translations));
        return false;
    }
    
    return true;
}
```

### Manual Translation Review
- ✅ **German terminology**: Accurate hunting-specific terms
- ✅ **Context appropriateness**: Terms fit German hunting culture  
- ✅ **Consistency**: Same terms used consistently throughout
- ✅ **Formality level**: Appropriate formal tone for administrative interface

## 10. DEPLOYMENT CHECKLIST

### Pre-Release i18n Validation
- [ ] All new strings wrapped in translation functions
- [ ] POT file generated and validated
- [ ] German translation complete (de_DE.po/mo)
- [ ] English translation provided for international use
- [ ] JavaScript localization implemented
- [ ] Text domain loaded correctly
- [ ] Contextual comments added for translators
- [ ] Pluralization handled correctly
- [ ] RTL languages considered (if applicable)

### WordPress.org Repository Requirements
- [ ] POT file included in plugin package
- [ ] Text domain matches plugin slug exactly
- [ ] All translatable strings use same text domain
- [ ] Languages directory structure correct
- [ ] load_plugin_textdomain() called properly

### Testing Scenarios
- [ ] Switch WordPress language to German → UI updates
- [ ] Switch to English → Interface translated appropriately
- [ ] Test with WPLANG constant override
- [ ] Verify JavaScript translations work in admin interface
- [ ] Test pluralization with different numbers

This comprehensive i18n plan ensures the plugin is fully translatable while maintaining its focus on German Hegegemeinschaften, making it accessible to international hunting management systems when needed.
