<?php
/**
 * Import Validator Service Class
 * Validates imported data against database constraints and business rules
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import Validator Service for data validation
 */
class AHGMH_Import_Validator {

    /**
     * Wildart service instance
     */
    private $wildart_service;

    /**
     * Cache for existing WUS numbers
     */
    private $existing_wus_numbers = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->wildart_service = new AHGMH_Wildart_Service();
    }

    /**
     * Validate mapping has all required fields
     *
     * @param array $mapping Column mapping (field => column_name)
     * @return array Validation result with errors
     */
    public function validate_required_fields($mapping) {
        $errors = array();
        $required_fields = array('wildart', 'kategorie');

        foreach ($required_fields as $field) {
            if (empty($mapping[$field])) {
                $errors[] = sprintf(
                    __('Erforderliches Feld "%s" ist nicht zugeordnet.', 'abschussplan-hgmh'),
                    $this->get_field_label($field)
                );
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Validate a single row of import data
     *
     * @param array $row_data Mapped row data (field => value)
     * @param int $row_number Row number for error reporting (1-based)
     * @return array Validation result with errors and warnings
     */
    public function validate_row($row_data, $row_number) {
        $errors = array();
        $warnings = array();

        // Validate required fields are not empty
        $required_result = $this->validate_required_row_fields($row_data, $row_number);
        if (!empty($required_result['errors'])) {
            $errors = array_merge($errors, $required_result['errors']);
        }

        // Validate wildart against configured species
        $wildart_result = $this->validate_wildart($row_data['wildart'] ?? '', $row_number);
        if (!empty($wildart_result['errors'])) {
            $errors = array_merge($errors, $wildart_result['errors']);
        }
        if (!empty($wildart_result['warnings'])) {
            $warnings = array_merge($warnings, $wildart_result['warnings']);
        }

        // Validate kategorie against wildart configuration
        if (!empty($row_data['wildart']) && !empty($row_data['kategorie'])) {
            $kategorie_result = $this->validate_kategorie(
                $row_data['wildart'],
                $row_data['kategorie'],
                $row_number
            );
            if (!empty($kategorie_result['errors'])) {
                $errors = array_merge($errors, $kategorie_result['errors']);
            }
            if (!empty($kategorie_result['warnings'])) {
                $warnings = array_merge($warnings, $kategorie_result['warnings']);
            }
        }

        // Validate meldegruppe if provided
        if (!empty($row_data['wildart']) && !empty($row_data['meldegruppe'])) {
            $meldegruppe_result = $this->validate_meldegruppe(
                $row_data['wildart'],
                $row_data['meldegruppe'],
                $row_number
            );
            if (!empty($meldegruppe_result['warnings'])) {
                $warnings = array_merge($warnings, $meldegruppe_result['warnings']);
            }
        }

        // Validate date format
        $date_result = $this->validate_date($row_data['datum'] ?? '', $row_number);
        if (!empty($date_result['errors'])) {
            $errors = array_merge($errors, $date_result['errors']);
        }
        if (!empty($date_result['warnings'])) {
            $warnings = array_merge($warnings, $date_result['warnings']);
        }

        // Validate WUS number uniqueness
        if (!empty($row_data['wus_nummer'])) {
            $wus_result = $this->validate_wus_number($row_data['wus_nummer'], $row_number);
            if (!empty($wus_result['errors'])) {
                $errors = array_merge($errors, $wus_result['errors']);
            }
            if (!empty($wus_result['warnings'])) {
                $warnings = array_merge($warnings, $wus_result['warnings']);
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }

    /**
     * Validate required row fields are not empty
     *
     * @param array $row_data Row data
     * @param int $row_number Row number for error reporting
     * @return array Validation result
     */
    private function validate_required_row_fields($row_data, $row_number) {
        $errors = array();
        $required_fields = array('wildart', 'kategorie');

        foreach ($required_fields as $field) {
            if (empty($row_data[$field])) {
                $errors[] = sprintf(
                    __('Zeile %d: Feld "%s" ist erforderlich und darf nicht leer sein.', 'abschussplan-hgmh'),
                    $row_number,
                    $this->get_field_label($field)
                );
            }
        }

        return array('errors' => $errors);
    }

    /**
     * Validate wildart against configured species
     *
     * @param string $wildart Wildart value
     * @param int $row_number Row number for error reporting
     * @return array Validation result with errors and warnings
     */
    private function validate_wildart($wildart, $row_number) {
        $errors = array();
        $warnings = array();

        if (empty($wildart)) {
            return array('errors' => $errors, 'warnings' => $warnings);
        }

        $configured_species = $this->wildart_service->get_all_wildarten();
        $wildart_trimmed = trim($wildart);

        // Exact match check
        if (!in_array($wildart_trimmed, $configured_species, true)) {
            // Try case-insensitive match
            $wildart_lower = mb_strtolower($wildart_trimmed, 'UTF-8');
            $found_case_insensitive = false;

            foreach ($configured_species as $species) {
                if (mb_strtolower($species, 'UTF-8') === $wildart_lower) {
                    $found_case_insensitive = true;
                    $warnings[] = sprintf(
                        __('Zeile %d: Wildart "%s" verwendet nicht die konfigurierte Schreibweise "%s".', 'abschussplan-hgmh'),
                        $row_number,
                        esc_html($wildart_trimmed),
                        esc_html($species)
                    );
                    break;
                }
            }

            if (!$found_case_insensitive) {
                $errors[] = sprintf(
                    __('Zeile %d: Wildart "%s" ist nicht in den konfigurierten Wildarten. Verfügbare Wildarten: %s', 'abschussplan-hgmh'),
                    $row_number,
                    esc_html($wildart_trimmed),
                    implode(', ', array_map('esc_html', $configured_species))
                );
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Validate kategorie against wildart configuration
     *
     * @param string $wildart Wildart value
     * @param string $kategorie Kategorie value
     * @param int $row_number Row number for error reporting
     * @return array Validation result with errors and warnings
     */
    private function validate_kategorie($wildart, $kategorie, $row_number) {
        $errors = array();
        $warnings = array();

        $configured_categories = $this->wildart_service->get_categories($wildart);
        $kategorie_trimmed = trim($kategorie);

        // Exact match check
        if (!in_array($kategorie_trimmed, $configured_categories, true)) {
            // Try case-insensitive match
            $kategorie_lower = mb_strtolower($kategorie_trimmed, 'UTF-8');
            $found_case_insensitive = false;

            foreach ($configured_categories as $category) {
                if (mb_strtolower($category, 'UTF-8') === $kategorie_lower) {
                    $found_case_insensitive = true;
                    $warnings[] = sprintf(
                        __('Zeile %d: Kategorie "%s" verwendet nicht die konfigurierte Schreibweise "%s".', 'abschussplan-hgmh'),
                        $row_number,
                        esc_html($kategorie_trimmed),
                        esc_html($category)
                    );
                    break;
                }
            }

            if (!$found_case_insensitive) {
                $errors[] = sprintf(
                    __('Zeile %d: Kategorie "%s" ist nicht für Wildart "%s" konfiguriert. Verfügbare Kategorien: %s', 'abschussplan-hgmh'),
                    $row_number,
                    esc_html($kategorie_trimmed),
                    esc_html($wildart),
                    implode(', ', array_map('esc_html', $configured_categories))
                );
            }
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Validate meldegruppe against wildart configuration
     *
     * @param string $wildart Wildart value
     * @param string $meldegruppe Meldegruppe value
     * @param int $row_number Row number for error reporting
     * @return array Validation result with warnings
     */
    private function validate_meldegruppe($wildart, $meldegruppe, $row_number) {
        $warnings = array();

        $configured_meldegruppen = $this->wildart_service->get_meldegruppen($wildart);
        $meldegruppe_trimmed = trim($meldegruppe);

        // Exact match check
        if (!in_array($meldegruppe_trimmed, $configured_meldegruppen, true)) {
            // Try case-insensitive match
            $meldegruppe_lower = mb_strtolower($meldegruppe_trimmed, 'UTF-8');
            $found_case_insensitive = false;

            foreach ($configured_meldegruppen as $mg) {
                if (mb_strtolower($mg, 'UTF-8') === $meldegruppe_lower) {
                    $found_case_insensitive = true;
                    $warnings[] = sprintf(
                        __('Zeile %d: Meldegruppe "%s" verwendet nicht die konfigurierte Schreibweise "%s".', 'abschussplan-hgmh'),
                        $row_number,
                        esc_html($meldegruppe_trimmed),
                        esc_html($mg)
                    );
                    break;
                }
            }

            if (!$found_case_insensitive) {
                $warnings[] = sprintf(
                    __('Zeile %d: Meldegruppe "%s" ist nicht für Wildart "%s" konfiguriert. Verfügbare Meldegruppen: %s', 'abschussplan-hgmh'),
                    $row_number,
                    esc_html($meldegruppe_trimmed),
                    esc_html($wildart),
                    implode(', ', array_map('esc_html', $configured_meldegruppen))
                );
            }
        }

        return array('warnings' => $warnings);
    }

    /**
     * Validate date format and convertibility to MySQL format
     *
     * @param string $date_string Date string
     * @param int $row_number Row number for error reporting
     * @return array Validation result with errors and warnings
     */
    private function validate_date($date_string, $row_number) {
        $errors = array();
        $warnings = array();

        // Empty date is allowed (will use current date)
        if (empty($date_string)) {
            $warnings[] = sprintf(
                __('Zeile %d: Kein Datum angegeben, aktuelles Datum wird verwendet.', 'abschussplan-hgmh'),
                $row_number
            );
            return array('errors' => $errors, 'warnings' => $warnings);
        }

        $date_string_trimmed = trim($date_string);
        $parsed_date = $this->parse_date($date_string_trimmed);

        // Check if date was successfully parsed
        if ($parsed_date === null) {
            $errors[] = sprintf(
                __('Zeile %d: Ungültiges Datumsformat "%s". Unterstützte Formate: DD.MM.YYYY, DD.MM.YY, YYYY-MM-DD, DD/MM/YYYY', 'abschussplan-hgmh'),
                $row_number,
                esc_html($date_string_trimmed)
            );
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Validate WUS number uniqueness
     *
     * @param string $wus_nummer WUS number
     * @param int $row_number Row number for error reporting
     * @return array Validation result with errors and warnings
     */
    private function validate_wus_number($wus_nummer, $row_number) {
        $errors = array();
        $warnings = array();

        $wus_nummer_trimmed = trim($wus_nummer);

        // Validate format (basic check)
        if (strlen($wus_nummer_trimmed) > 100) {
            $errors[] = sprintf(
                __('Zeile %d: WUS-Nummer ist zu lang (maximal 100 Zeichen).', 'abschussplan-hgmh'),
                $row_number
            );
        }

        // Check for duplicate in database
        if ($this->wus_number_exists($wus_nummer_trimmed)) {
            $errors[] = sprintf(
                __('Zeile %d: WUS-Nummer "%s" existiert bereits in der Datenbank.', 'abschussplan-hgmh'),
                $row_number,
                esc_html($wus_nummer_trimmed)
            );
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Check if WUS number exists in database
     *
     * @param string $wus_nummer WUS number to check
     * @return bool True if exists, false otherwise
     */
    private function wus_number_exists($wus_nummer) {
        global $wpdb;

        // Load all existing WUS numbers on first call (cache for performance)
        if ($this->existing_wus_numbers === null) {
            $table_name = $wpdb->prefix . 'ahgmh_submissions';
            $results = $wpdb->get_col("SELECT wus_nummer FROM $table_name WHERE wus_nummer != '' AND wus_nummer IS NOT NULL");
            $this->existing_wus_numbers = array_map('trim', $results);
        }

        return in_array(trim($wus_nummer), $this->existing_wus_numbers, true);
    }

    /**
     * Parse date from various formats to MySQL format
     *
     * @param string $date_string Date string in various formats
     * @return string|null Date in MySQL format (YYYY-MM-DD HH:MM:SS) or null if invalid
     */
    public function parse_date($date_string) {
        if (empty($date_string)) {
            return null;
        }

        // Try common German date formats
        $formats = array(
            'd.m.Y',      // 31.12.2024
            'd.m.y',      // 31.12.24
            'd/m/Y',      // 31/12/2024
            'Y-m-d',      // 2024-12-31 (ISO)
            'd-m-Y',      // 31-12-2024
            'm/d/Y',      // 12/31/2024 (US)
        );

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, trim($date_string));
            if ($date !== false) {
                // Additional validation: check if parsed date makes sense
                $errors = DateTime::getLastErrors();
                if ($errors['warning_count'] === 0 && $errors['error_count'] === 0) {
                    return $date->format('Y-m-d H:i:s');
                }
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return null;
    }

    /**
     * Convert date string to MySQL format with fallback to current date
     *
     * @param string $date_string Date string
     * @return string Date in MySQL format
     */
    public function parse_date_with_fallback($date_string) {
        $parsed = $this->parse_date($date_string);

        if ($parsed === null) {
            return current_time('mysql', false);
        }

        return $parsed;
    }

    /**
     * Get human-readable label for field
     *
     * @param string $field Field name
     * @return string Human-readable label
     */
    private function get_field_label($field) {
        $labels = array(
            'datum' => __('Datum', 'abschussplan-hgmh'),
            'wildart' => __('Wildart', 'abschussplan-hgmh'),
            'kategorie' => __('Kategorie', 'abschussplan-hgmh'),
            'meldegruppe' => __('Meldegruppe', 'abschussplan-hgmh'),
            'jagdbezirk' => __('Jagdbezirk', 'abschussplan-hgmh'),
            'wus_nummer' => __('WUS-Nummer', 'abschussplan-hgmh'),
            'bemerkung' => __('Bemerkung', 'abschussplan-hgmh')
        );

        return isset($labels[$field]) ? $labels[$field] : $field;
    }

    /**
     * Reset WUS number cache (call this when importing in batches)
     */
    public function reset_wus_cache() {
        $this->existing_wus_numbers = null;
    }

    /**
     * Validate entire import dataset
     *
     * @param array $data_rows Array of row data
     * @param array $mapping Column mapping
     * @return array Validation results with summary
     */
    public function validate_import($data_rows, $mapping) {
        $results = array(
            'valid' => true,
            'total_rows' => count($data_rows),
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'warnings_count' => 0,
            'row_results' => array()
        );

        // Validate mapping first
        $mapping_validation = $this->validate_required_fields($mapping);
        if (!$mapping_validation['valid']) {
            $results['valid'] = false;
            $results['mapping_errors'] = $mapping_validation['errors'];
            return $results;
        }

        // Validate each row
        foreach ($data_rows as $index => $row) {
            $row_number = $index + 2; // +2 for header row and 0-index
            $row_result = $this->validate_row($row, $row_number);

            $results['row_results'][$row_number] = $row_result;

            if ($row_result['valid']) {
                $results['valid_rows']++;
            } else {
                $results['invalid_rows']++;
                $results['valid'] = false;
            }

            if (!empty($row_result['warnings'])) {
                $results['warnings_count'] += count($row_result['warnings']);
            }
        }

        return $results;
    }
}
