# Import Test Fixtures

This directory contains sample CSV files for testing the import functionality of the Abschussplan HGMH plugin.

## Standard Test Files

### sample-import-standard.csv
Standard CSV file with comma delimiter (`,`). Contains all fields:
- Datum (Date)
- Wildart (Species)
- Kategorie (Category)
- Meldegruppe (Reporting Group)
- Jagdbezirk (Hunting District)
- WUS-Nummer (WUS Number)
- Bemerkung (Remark)

**Use case:** Testing basic import functionality with standard CSV format.

### sample-import-semicolon.csv
CSV file with semicolon delimiter (`;`) as commonly exported by German Excel.

**Use case:** Testing German Excel export format support.

### sample-import-utf8.csv
CSV file containing German special characters (umlauts: ä, ö, ü, ß).

**Use case:** Testing UTF-8 encoding detection and proper handling of German characters.

### sample-import-missing-fields.csv
CSV file with only required fields (Datum, Wildart, Kategorie, Bemerkung).
Optional fields (Meldegruppe, Jagdbezirk, WUS-Nummer) are not included.

**Use case:** Testing import with minimal data and default value handling.

### sample-import-errors.csv
CSV file intentionally containing validation errors:
- Invalid date format
- Unknown species (Wildart)
- Invalid category for species
- Missing required field (Wildart)
- Missing required field (Kategorie)

**Use case:** Testing validation error detection and error message display.

## LJV Template Test Files

### sample-ljv-hessen.csv
Landesjagdverband Hessen template format with headers:
- Abschussdatum
- Wildart
- Alter/Geschlecht
- Revier
- WUS-Nr.
- Bemerkungen

**Use case:** Testing LJV Hessen template auto-detection and column mapping.

### sample-ljv-bayern.csv
Landesjagdverband Bayern template format with headers:
- Erlegungsdatum
- Tierart
- Geschlecht/Alter
- Jagdrevier
- Wildursprungsschein
- Anmerkung

**Use case:** Testing LJV Bayern template auto-detection and column mapping.

### sample-ljv-nrw.csv
Landesjagdverband NRW template format with headers:
- Datum
- Wild
- Altersklasse
- Bezirk
- WUS
- Notiz

**Use case:** Testing LJV NRW template auto-detection and column mapping.

## Performance Test Files

### sample-large-import.csv
CSV file with 50 rows for performance testing.

**Use case:** Testing import performance with larger datasets and batch processing.

## Field Descriptions

### Required Fields
- **Wildart** (Species): Must be one of the configured species (Rotwild, Damwild, Rehwild)
- **Kategorie** (Category): Must be a valid category for the selected species

### Optional Fields
- **Datum** (Date): Date of harvest. If empty, current date is used. Supported formats:
  - DD.MM.YYYY (e.g., 15.01.2024)
  - DD.MM.YY (e.g., 15.01.24)
  - YYYY-MM-DD (e.g., 2024-01-15)
  - DD/MM/YYYY (e.g., 15/01/2024)
- **Meldegruppe** (Reporting Group): Optional group identifier
- **Jagdbezirk** (Hunting District): Optional district identifier
- **WUS-Nummer** (WUS Number): Wildursprungsschein number. Must be unique if provided.
- **Bemerkung** (Remark): Free text notes

## Testing Checklist

Use these files to verify:
- [x] CSV import with comma delimiter works
- [x] CSV import with semicolon delimiter works
- [x] UTF-8 special characters are handled correctly
- [x] Column auto-detection identifies German headers
- [x] LJV template detection works for Hessen, Bayern, and NRW formats
- [x] Preview shows correct data
- [x] Validation catches errors (invalid dates, unknown species, missing required fields)
- [x] Import handles missing optional fields correctly
- [x] Large imports (50+ rows) process without timeout
- [x] Duplicate WUS numbers are detected and prevented

## Manual Testing Instructions

1. Log in to WordPress admin as a user with `manage_options` capability
2. Navigate to Abschussplan → Import
3. Upload one of the test CSV files
4. Verify column mapping is auto-detected correctly
5. Check preview shows first 5 rows with correct data
6. For error files, verify validation errors are displayed
7. For valid files, execute import and verify records are created
8. Test undo functionality to revert the import

## Adding New Test Files

When adding new test files:
1. Use descriptive filenames starting with `sample-`
2. Include header row with column names
3. Use realistic test data (valid species, categories, dates)
4. Document the file purpose in this README
5. Ensure WUS numbers are unique across all test files
