<?php
/**
 * Column Mapper Service Class
 * Intelligent column mapping with auto-detection for import files
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Column Mapper Service for auto-detecting and mapping import columns
 */
class AHGMH_Column_Mapper {

    /**
     * Database field definitions with their expected data types
     */
    private $field_definitions = array(
        'datum' => 'date',
        'wildart' => 'text',
        'kategorie' => 'text',
        'meldegruppe' => 'text',
        'jagdbezirk' => 'text',
        'wus_nummer' => 'text',
        'bemerkung' => 'text'
    );

    /**
     * Column name variations for auto-detection
     * Maps field names to possible column header variations
     */
    private $column_variations = array(
        'datum' => array(
            'datum',
            'date',
            'abschussdatum',
            'erlegungsdatum',
            'meldedatum',
            'tag'
        ),
        'wildart' => array(
            'wildart',
            'art',
            'tierart',
            'species',
            'wild'
        ),
        'kategorie' => array(
            'kategorie',
            'category',
            'kat',
            'altersklasse',
            'geschlecht'
        ),
        'meldegruppe' => array(
            'meldegruppe',
            'gruppe',
            'mg',
            'reporting_group'
        ),
        'jagdbezirk' => array(
            'jagdbezirk',
            'bezirk',
            'revier',
            'jb',
            'hunting_district'
        ),
        'wus_nummer' => array(
            'wus-nummer',
            'wus_nummer',
            'wusnummer',
            'wus',
            'wildursprungsschein',
            'wus-nr',
            'wus nr'
        ),
        'bemerkung' => array(
            'bemerkung',
            'bemerkungen',
            'notiz',
            'anmerkung',
            'comment',
            'note',
            'notes',
            'hinweis'
        )
    );

    /**
     * Auto-detect column mappings from headers
     *
     * @param array $headers Column headers from import file
     * @param array $sample_data Optional sample data rows for pattern analysis
     * @return array Mapping results with field => column name and confidence scores
     */
    public function auto_detect_mapping($headers, $sample_data = array()) {
        $mappings = array();
        $confidence_scores = array();

        // Normalize headers for comparison
        $normalized_headers = array_map(function($header) {
            return $this->normalize_string($header);
        }, $headers);

        // Try to match each required field
        foreach ($this->field_definitions as $field => $expected_type) {
            $best_match = $this->find_best_match($field, $normalized_headers, $headers);

            if ($best_match) {
                $mappings[$field] = $best_match['column'];
                $confidence_scores[$field] = $best_match['confidence'];

                // Verify match with data pattern if sample data available
                if (!empty($sample_data)) {
                    $column_index = array_search($best_match['column'], $headers);
                    if ($column_index !== false) {
                        $pattern_confidence = $this->analyze_data_pattern(
                            $sample_data,
                            $column_index,
                            $expected_type
                        );

                        // Adjust confidence based on pattern analysis
                        $confidence_scores[$field] = ($confidence_scores[$field] + $pattern_confidence) / 2;
                    }
                }
            } else {
                $mappings[$field] = '';
                $confidence_scores[$field] = 0.0;
            }
        }

        return array(
            'mappings' => $mappings,
            'confidence' => $confidence_scores,
            'overall_confidence' => $this->calculate_overall_confidence($confidence_scores)
        );
    }

    /**
     * Find best matching column for a field
     *
     * @param string $field Field name to match
     * @param array $normalized_headers Normalized column headers
     * @param array $original_headers Original column headers
     * @return array|null Best match with column name and confidence score
     */
    private function find_best_match($field, $normalized_headers, $original_headers) {
        if (!isset($this->column_variations[$field])) {
            return null;
        }

        $variations = $this->column_variations[$field];
        $best_match = null;
        $highest_confidence = 0.0;

        foreach ($normalized_headers as $index => $normalized_header) {
            $confidence = $this->calculate_match_confidence($normalized_header, $variations);

            if ($confidence > $highest_confidence) {
                $highest_confidence = $confidence;
                $best_match = array(
                    'column' => $original_headers[$index],
                    'confidence' => $confidence
                );
            }
        }

        // Only return matches with confidence > 0.5
        if ($highest_confidence > 0.5) {
            return $best_match;
        }

        return null;
    }

    /**
     * Calculate match confidence for a header against variations
     *
     * @param string $header Normalized header string
     * @param array $variations Array of possible variations
     * @return float Confidence score (0.0 to 1.0)
     */
    private function calculate_match_confidence($header, $variations) {
        $max_confidence = 0.0;

        foreach ($variations as $variation) {
            $normalized_variation = $this->normalize_string($variation);

            // Exact match
            if ($header === $normalized_variation) {
                return 1.0;
            }

            // Contains match
            if (strpos($header, $normalized_variation) !== false) {
                $confidence = 0.9;
                $max_confidence = max($max_confidence, $confidence);
                continue;
            }

            // Reverse contains (header is contained in variation)
            if (strpos($normalized_variation, $header) !== false) {
                $confidence = 0.85;
                $max_confidence = max($max_confidence, $confidence);
                continue;
            }

            // Similarity-based matching (Levenshtein distance)
            $similarity = $this->calculate_similarity($header, $normalized_variation);
            if ($similarity > 0.7) {
                $confidence = $similarity * 0.8; // Scale down similarity-based confidence
                $max_confidence = max($max_confidence, $confidence);
            }
        }

        return $max_confidence;
    }

    /**
     * Analyze data pattern in a column to verify type match
     *
     * @param array $sample_data Sample data rows
     * @param int $column_index Column index to analyze
     * @param string $expected_type Expected data type (date, text, number)
     * @return float Confidence score based on pattern match
     */
    private function analyze_data_pattern($sample_data, $column_index, $expected_type) {
        $matching_count = 0;
        $total_count = 0;

        foreach ($sample_data as $row) {
            if (!isset($row[$column_index])) {
                continue;
            }

            $value = trim($row[$column_index]);

            // Skip empty values
            if (empty($value)) {
                continue;
            }

            $total_count++;

            switch ($expected_type) {
                case 'date':
                    if ($this->is_date_pattern($value)) {
                        $matching_count++;
                    }
                    break;

                case 'number':
                    if (is_numeric($value)) {
                        $matching_count++;
                    }
                    break;

                case 'text':
                    // Text is default, always matches
                    $matching_count++;
                    break;
            }
        }

        if ($total_count === 0) {
            return 0.5; // Neutral confidence if no data to analyze
        }

        return $matching_count / $total_count;
    }

    /**
     * Check if a value matches common date patterns
     *
     * @param string $value Value to check
     * @return bool True if matches date pattern
     */
    private function is_date_pattern($value) {
        // Common date patterns
        $date_patterns = array(
            '/^\d{1,2}\.\d{1,2}\.\d{2,4}$/',           // DD.MM.YYYY or DD.MM.YY
            '/^\d{1,2}\/\d{1,2}\/\d{2,4}$/',           // DD/MM/YYYY or MM/DD/YYYY
            '/^\d{4}-\d{2}-\d{2}$/',                   // YYYY-MM-DD (ISO)
            '/^\d{1,2}-\d{1,2}-\d{2,4}$/',             // DD-MM-YYYY
            '/^\d{4}\/\d{2}\/\d{2}$/',                 // YYYY/MM/DD
        );

        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate string similarity using Levenshtein distance
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score (0.0 to 1.0)
     */
    private function calculate_similarity($str1, $str2) {
        $max_length = max(strlen($str1), strlen($str2));

        if ($max_length === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1.0 - ($distance / $max_length);
    }

    /**
     * Normalize string for comparison
     * Removes special characters, converts to lowercase, removes spaces
     *
     * @param string $str String to normalize
     * @return string Normalized string
     */
    private function normalize_string($str) {
        // Convert to lowercase
        $str = mb_strtolower($str, 'UTF-8');

        // Remove umlauts for better matching
        $replacements = array(
            'ä' => 'a',
            'ö' => 'o',
            'ü' => 'u',
            'ß' => 'ss'
        );
        $str = strtr($str, $replacements);

        // Remove special characters except spaces and hyphens
        $str = preg_replace('/[^a-z0-9\s\-]/', '', $str);

        // Remove spaces and hyphens
        $str = str_replace(array(' ', '-', '_'), '', $str);

        return trim($str);
    }

    /**
     * Calculate overall confidence score
     *
     * @param array $confidence_scores Individual field confidence scores
     * @return float Overall confidence (0.0 to 1.0)
     */
    private function calculate_overall_confidence($confidence_scores) {
        if (empty($confidence_scores)) {
            return 0.0;
        }

        // Weight required fields more heavily
        $required_fields = array('datum', 'wildart', 'kategorie');
        $total_weight = 0;
        $weighted_sum = 0;

        foreach ($confidence_scores as $field => $score) {
            $weight = in_array($field, $required_fields) ? 2.0 : 1.0;
            $weighted_sum += $score * $weight;
            $total_weight += $weight;
        }

        return $total_weight > 0 ? $weighted_sum / $total_weight : 0.0;
    }

    /**
     * Validate manual mapping
     * Ensures all required fields are mapped and column names exist
     *
     * @param array $mapping User-provided mapping (field => column name)
     * @param array $headers Available column headers
     * @return array Validation result with errors
     */
    public function validate_mapping($mapping, $headers) {
        $errors = array();
        $required_fields = array('wildart', 'kategorie');

        // Check required fields
        foreach ($required_fields as $field) {
            if (empty($mapping[$field])) {
                $errors[] = sprintf(
                    __('Erforderliches Feld "%s" ist nicht zugeordnet.', 'abschussplan-hgmh'),
                    $this->get_field_label($field)
                );
            }
        }

        // Validate column names exist in headers
        foreach ($mapping as $field => $column_name) {
            if (empty($column_name)) {
                continue; // Skip empty mappings for optional fields
            }

            if (!in_array($column_name, $headers)) {
                $errors[] = sprintf(
                    __('Spalte "%s" nicht in den Importdaten gefunden.', 'abschussplan-hgmh'),
                    esc_html($column_name)
                );
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
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
     * Get field definitions
     *
     * @return array Field definitions with labels and types
     */
    public function get_field_definitions() {
        $definitions = array();

        foreach ($this->field_definitions as $field => $type) {
            $definitions[$field] = array(
                'field' => $field,
                'label' => $this->get_field_label($field),
                'type' => $type,
                'required' => in_array($field, array('wildart', 'kategorie'))
            );
        }

        return $definitions;
    }

    /**
     * Merge auto-detected mapping with manual overrides
     *
     * @param array $auto_mapping Auto-detected mapping
     * @param array $manual_mapping User-provided overrides
     * @return array Merged mapping
     */
    public function merge_mappings($auto_mapping, $manual_mapping) {
        $merged = $auto_mapping;

        // Override with manual mappings where provided
        foreach ($manual_mapping as $field => $column_name) {
            if (!empty($column_name)) {
                $merged[$field] = $column_name;
            }
        }

        return $merged;
    }
}
