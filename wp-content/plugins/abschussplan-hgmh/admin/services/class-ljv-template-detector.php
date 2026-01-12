<?php
/**
 * LJV Template Detector Class
 * Detects and maps Landesjagdverband (state hunting association) Excel templates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LJV Template Detector for specialized state hunting association formats
 * Supports common formats from Hessen, Bavaria (Bayern), and NRW
 */
class AHGMH_LJV_Template_Detector {

    /**
     * Known LJV template definitions
     * Each template includes signature headers and pre-configured column mappings
     */
    private $template_definitions = array(
        'ljv_hessen' => array(
            'name' => 'LJV Hessen',
            'signature_headers' => array(
                'abschussdatum',
                'wildart',
                'alter_geschlecht',
                'revier',
                'wus-nr'
            ),
            'column_mapping' => array(
                'datum' => 'Abschussdatum',
                'wildart' => 'Wildart',
                'kategorie' => 'Alter/Geschlecht',
                'jagdbezirk' => 'Revier',
                'wus_nummer' => 'WUS-Nr.',
                'bemerkung' => 'Bemerkungen'
            ),
            'required_matches' => 3
        ),
        'ljv_bayern' => array(
            'name' => 'LJV Bayern',
            'signature_headers' => array(
                'erlegungsdatum',
                'tierart',
                'geschlecht',
                'jagdrevier',
                'wildursprungsschein'
            ),
            'column_mapping' => array(
                'datum' => 'Erlegungsdatum',
                'wildart' => 'Tierart',
                'kategorie' => 'Geschlecht/Alter',
                'jagdbezirk' => 'Jagdrevier',
                'wus_nummer' => 'Wildursprungsschein',
                'bemerkung' => 'Anmerkung'
            ),
            'required_matches' => 3
        ),
        'ljv_nrw' => array(
            'name' => 'LJV NRW',
            'signature_headers' => array(
                'datum',
                'wild',
                'altersklasse',
                'bezirk',
                'wus'
            ),
            'column_mapping' => array(
                'datum' => 'Datum',
                'wildart' => 'Wild',
                'kategorie' => 'Altersklasse',
                'jagdbezirk' => 'Bezirk',
                'wus_nummer' => 'WUS',
                'bemerkung' => 'Notiz'
            ),
            'required_matches' => 3
        ),
        'ljv_generic' => array(
            'name' => 'LJV Allgemein',
            'signature_headers' => array(
                'ljv',
                'jagdverband',
                'landesjagdverband'
            ),
            'column_mapping' => array(
                'datum' => 'Datum',
                'wildart' => 'Wildart',
                'kategorie' => 'Kategorie',
                'jagdbezirk' => 'Jagdbezirk',
                'wus_nummer' => 'WUS-Nummer',
                'bemerkung' => 'Bemerkung'
            ),
            'required_matches' => 1
        )
    );

    /**
     * Detect LJV template from headers
     *
     * @param array $headers Column headers from import file
     * @return array Detection result with template type, confidence, and mapping
     */
    public function detect_template($headers) {
        if (empty($headers)) {
            return $this->get_no_match_result();
        }

        // Normalize headers for comparison
        $normalized_headers = array_map(function($header) {
            return $this->normalize_header($header);
        }, $headers);

        // Try to match each template definition
        $best_match = null;
        $highest_confidence = 0.0;

        foreach ($this->template_definitions as $template_id => $template) {
            $match_result = $this->match_template($template, $normalized_headers, $headers);

            if ($match_result['confidence'] > $highest_confidence) {
                $highest_confidence = $match_result['confidence'];
                $best_match = array(
                    'template_id' => $template_id,
                    'template_name' => $template['name'],
                    'confidence' => $match_result['confidence'],
                    'matched_headers' => $match_result['matched_headers'],
                    'column_mapping' => $this->build_column_mapping($template['column_mapping'], $headers)
                );
            }
        }

        // Only return match if confidence is high enough (> 0.6)
        if ($best_match && $best_match['confidence'] > 0.6) {
            $this->log_detection($best_match);
            return $best_match;
        }

        return $this->get_no_match_result();
    }

    /**
     * Match headers against a template definition
     *
     * @param array $template Template definition
     * @param array $normalized_headers Normalized column headers
     * @param array $original_headers Original column headers
     * @return array Match result with confidence and matched headers
     */
    private function match_template($template, $normalized_headers, $original_headers) {
        $matched_count = 0;
        $matched_headers = array();
        $signature_headers = $template['signature_headers'];

        // Count how many signature headers are found
        foreach ($signature_headers as $signature) {
            $normalized_signature = $this->normalize_header($signature);

            foreach ($normalized_headers as $index => $header) {
                // Check for exact match or contains
                if ($header === $normalized_signature ||
                    strpos($header, $normalized_signature) !== false ||
                    strpos($normalized_signature, $header) !== false) {
                    $matched_count++;
                    $matched_headers[] = $original_headers[$index];
                    break; // Move to next signature
                }
            }
        }

        // Calculate confidence based on match rate
        $required_matches = $template['required_matches'];
        $total_signatures = count($signature_headers);

        if ($matched_count < $required_matches) {
            return array(
                'confidence' => 0.0,
                'matched_headers' => array()
            );
        }

        // Confidence is match rate with bonus for exceeding requirements
        $confidence = min(1.0, ($matched_count / $total_signatures) * 1.2);

        return array(
            'confidence' => $confidence,
            'matched_headers' => $matched_headers
        );
    }

    /**
     * Build column mapping from template mapping and actual headers
     *
     * @param array $template_mapping Template's column mapping
     * @param array $headers Actual column headers from file
     * @return array Column mapping with actual header names
     */
    private function build_column_mapping($template_mapping, $headers) {
        $mapping = array();

        foreach ($template_mapping as $field => $expected_header) {
            $normalized_expected = $this->normalize_header($expected_header);

            // Find matching header in actual headers
            foreach ($headers as $header) {
                $normalized_header = $this->normalize_header($header);

                if ($normalized_header === $normalized_expected ||
                    strpos($normalized_header, $normalized_expected) !== false ||
                    strpos($normalized_expected, $normalized_header) !== false) {
                    $mapping[$field] = $header;
                    break;
                }
            }

            // If not found, leave empty
            if (!isset($mapping[$field])) {
                $mapping[$field] = '';
            }
        }

        return $mapping;
    }

    /**
     * Normalize header for comparison
     *
     * @param string $header Header string to normalize
     * @return string Normalized header
     */
    private function normalize_header($header) {
        // Convert to lowercase
        $header = mb_strtolower($header, 'UTF-8');

        // Remove umlauts
        $replacements = array(
            'ä' => 'a',
            'ö' => 'o',
            'ü' => 'u',
            'ß' => 'ss'
        );
        $header = strtr($header, $replacements);

        // Remove special characters, spaces, hyphens, underscores
        $header = preg_replace('/[^a-z0-9]/', '', $header);

        return trim($header);
    }

    /**
     * Get result when no template matches
     *
     * @return array No match result
     */
    private function get_no_match_result() {
        return array(
            'template_id' => null,
            'template_name' => null,
            'confidence' => 0.0,
            'matched_headers' => array(),
            'column_mapping' => array()
        );
    }

    /**
     * Log detected template for debugging
     *
     * @param array $detection_result Detection result to log
     */
    private function log_detection($detection_result) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[AHGMH Import] LJV Template detected: %s (Confidence: %.2f)',
                $detection_result['template_name'],
                $detection_result['confidence']
            ));
        }
    }

    /**
     * Get list of supported templates
     *
     * @return array List of supported template names
     */
    public function get_supported_templates() {
        $templates = array();

        foreach ($this->template_definitions as $template_id => $template) {
            $templates[$template_id] = $template['name'];
        }

        return $templates;
    }

    /**
     * Get template definition by ID
     *
     * @param string $template_id Template ID
     * @return array|null Template definition or null if not found
     */
    public function get_template_definition($template_id) {
        if (isset($this->template_definitions[$template_id])) {
            return $this->template_definitions[$template_id];
        }

        return null;
    }

    /**
     * Check if headers likely contain LJV data
     * Quick check for presence of LJV-related terms
     *
     * @param array $headers Column headers
     * @return bool True if headers suggest LJV format
     */
    public function is_likely_ljv_format($headers) {
        $ljv_indicators = array(
            'ljv',
            'landesjagdverband',
            'jagdverband',
            'wildursprungsschein',
            'wus-nr',
            'erlegungsdatum',
            'abschussdatum'
        );

        $normalized_headers = array_map(function($header) {
            return $this->normalize_header($header);
        }, $headers);

        // Check if any indicator is found in headers
        foreach ($ljv_indicators as $indicator) {
            $normalized_indicator = $this->normalize_header($indicator);

            foreach ($normalized_headers as $header) {
                if (strpos($header, $normalized_indicator) !== false ||
                    strpos($normalized_indicator, $header) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
