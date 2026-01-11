<?php
/**
 * Import Controller
 * Handles AJAX requests for file upload, column mapping, preview, and import execution
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import Controller Class
 */
class AHGMH_Import_Controller {

    private $import_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->import_service = new AHGMH_Import_Service();
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_ahgmh_get_preview', array($this, 'ajax_get_preview'));
        add_action('wp_ajax_ahgmh_execute_import', array($this, 'ajax_execute_import'));
    }

    /**
     * AJAX: Upload and parse import file
     *
     * Handles multipart file upload, validates file type and size,
     * and returns parsed data with detected columns
     */
    public function ajax_upload_file() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Check if file was uploaded
            if (!isset($_FILES['file'])) {
                throw new Exception(__('Keine Datei hochgeladen.', 'abschussplan-hgmh'));
            }

            // Handle file upload
            $upload_result = $this->import_service->handle_upload($_FILES['file']);

            // Parse file to detect columns
            $parse_result = $this->import_service->parse_file($upload_result['filepath']);

            // Return upload and parse results
            wp_send_json_success(array(
                'filepath' => $upload_result['filepath'],
                'filename' => $upload_result['filename'],
                'size' => $upload_result['size'],
                'headers' => $parse_result['headers'],
                'row_count' => $parse_result['row_count'],
                'column_count' => $parse_result['column_count'],
                'delimiter' => $parse_result['delimiter'],
                'message' => sprintf(
                    __('Datei erfolgreich hochgeladen: %d Zeilen, %d Spalten erkannt.', 'abschussplan-hgmh'),
                    $parse_result['row_count'],
                    $parse_result['column_count']
                )
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Fehler beim Datei-Upload: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }

    /**
     * AJAX: Get preview of import data
     *
     * Returns the first 5 rows of data for preview and validation
     */
    public function ajax_get_preview() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get and validate filepath
            if (!isset($_POST['filepath'])) {
                throw new Exception(__('Dateipfad fehlt.', 'abschussplan-hgmh'));
            }

            $filepath = sanitize_text_field($_POST['filepath']);

            // Validate filepath exists and is in upload directory
            if (!file_exists($filepath)) {
                throw new Exception(__('Datei nicht gefunden.', 'abschussplan-hgmh'));
            }

            $upload_dir = wp_upload_dir();
            if (strpos(realpath($filepath), realpath($upload_dir['path'])) !== 0) {
                throw new Exception(__('Ungültiger Dateipfad.', 'abschussplan-hgmh'));
            }

            // Get preview limit from request (default 5)
            $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 5;
            if ($limit < 1) $limit = 5;
            if ($limit > 20) $limit = 20; // Max 20 rows for preview

            // Get preview data
            $preview = $this->import_service->get_preview($filepath, $limit);

            wp_send_json_success(array(
                'headers' => $preview['headers'],
                'data' => $preview['data'],
                'total_rows' => $preview['total_rows'],
                'preview_rows' => $preview['preview_rows'],
                'column_count' => $preview['column_count'],
                'message' => sprintf(
                    __('Vorschau: %d von %d Zeilen angezeigt.', 'abschussplan-hgmh'),
                    $preview['preview_rows'],
                    $preview['total_rows']
                )
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Fehler beim Laden der Vorschau: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }

    /**
     * AJAX: Execute import with column mapping
     *
     * Processes the uploaded file with user-defined column mapping
     * and imports records into the database
     */
    public function ajax_execute_import() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Validate required parameters
            if (!isset($_POST['filepath']) || !isset($_POST['mapping'])) {
                throw new Exception(__('Erforderliche Parameter fehlen.', 'abschussplan-hgmh'));
            }

            $filepath = sanitize_text_field($_POST['filepath']);
            $mapping = $_POST['mapping']; // Will be sanitized during processing

            // Validate filepath
            if (!file_exists($filepath)) {
                throw new Exception(__('Datei nicht gefunden.', 'abschussplan-hgmh'));
            }

            $upload_dir = wp_upload_dir();
            if (strpos(realpath($filepath), realpath($upload_dir['path'])) !== 0) {
                throw new Exception(__('Ungültiger Dateipfad.', 'abschussplan-hgmh'));
            }

            // Validate mapping array
            if (!is_array($mapping)) {
                throw new Exception(__('Ungültige Spaltenzuordnung.', 'abschussplan-hgmh'));
            }

            // Sanitize mapping
            $sanitized_mapping = array();
            foreach ($mapping as $field => $column_index) {
                $sanitized_field = sanitize_text_field($field);
                $sanitized_index = sanitize_text_field($column_index);

                if (!empty($sanitized_field) && !empty($sanitized_index)) {
                    $sanitized_mapping[$sanitized_field] = $sanitized_index;
                }
            }

            if (empty($sanitized_mapping)) {
                throw new Exception(__('Keine gültige Spaltenzuordnung vorhanden.', 'abschussplan-hgmh'));
            }

            // Parse the file
            $parsed_data = $this->import_service->parse_file($filepath);

            // Process and import data
            $result = $this->process_import($parsed_data, $sanitized_mapping);

            // Clean up uploaded file after successful import
            AHGMH_Import_Service::cleanup_import_file($filepath);

            wp_send_json_success(array(
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'message' => sprintf(
                    __('Import abgeschlossen: %d Datensätze importiert, %d übersprungen.', 'abschussplan-hgmh'),
                    $result['imported'],
                    $result['skipped']
                )
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Fehler beim Import: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ));
        }
    }

    /**
     * Process import data with column mapping
     *
     * @param array $parsed_data Parsed file data
     * @param array $mapping Column mapping (field => column_index)
     * @return array Import results with counts
     */
    private function process_import($parsed_data, $mapping) {
        global $wpdb;

        $imported = 0;
        $skipped = 0;
        $errors = array();

        $table_name = $wpdb->prefix . 'ahgmh_submissions';

        // Process each row
        foreach ($parsed_data['data'] as $row_index => $row) {
            try {
                // Map row data to database fields
                $record = array();

                foreach ($mapping as $field => $column_name) {
                    // Find column index by name
                    $column_index = array_search($column_name, $parsed_data['headers']);

                    if ($column_index !== false && isset($row[$column_index])) {
                        $record[$field] = sanitize_text_field($row[$column_index]);
                    } else {
                        $record[$field] = '';
                    }
                }

                // Validate required fields
                if (empty($record['wildart']) || empty($record['kategorie'])) {
                    $skipped++;
                    $errors[] = sprintf(
                        __('Zeile %d: Wildart und Kategorie sind erforderlich.', 'abschussplan-hgmh'),
                        $row_index + 2 // +2 for header row and 0-index
                    );
                    continue;
                }

                // Prepare data for database insertion
                $data = array(
                    'wildart' => $record['wildart'],
                    'kategorie' => $record['kategorie'],
                    'meldegruppe' => !empty($record['meldegruppe']) ? $record['meldegruppe'] : '',
                    'jagdbezirk' => !empty($record['jagdbezirk']) ? $record['jagdbezirk'] : '',
                    'wus_nummer' => !empty($record['wus_nummer']) ? $record['wus_nummer'] : '',
                    'bemerkung' => !empty($record['bemerkung']) ? $record['bemerkung'] : '',
                    'datum' => $this->parse_date($record['datum'] ?? ''),
                    'submitted_at' => current_time('mysql'),
                    'submitted_by' => get_current_user_id()
                );

                // Check for duplicate WUS number if provided
                if (!empty($data['wus_nummer'])) {
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table_name WHERE wus_nummer = %s",
                        $data['wus_nummer']
                    ));

                    if ($existing) {
                        $skipped++;
                        $errors[] = sprintf(
                            __('Zeile %d: WUS-Nummer %s bereits vorhanden.', 'abschussplan-hgmh'),
                            $row_index + 2,
                            esc_html($data['wus_nummer'])
                        );
                        continue;
                    }
                }

                // Insert record
                $result = $wpdb->insert(
                    $table_name,
                    $data,
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
                );

                if ($result === false) {
                    $skipped++;
                    $errors[] = sprintf(
                        __('Zeile %d: Datenbankfehler beim Einfügen.', 'abschussplan-hgmh'),
                        $row_index + 2
                    );
                } else {
                    $imported++;
                }

            } catch (Exception $e) {
                $skipped++;
                $errors[] = sprintf(
                    __('Zeile %d: %s', 'abschussplan-hgmh'),
                    $row_index + 2,
                    esc_html($e->getMessage())
                );
            }
        }

        return array(
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }

    /**
     * Parse date from various formats to MySQL format
     *
     * @param string $date_string Date string in various formats
     * @return string Date in MySQL format (YYYY-MM-DD) or current date if invalid
     */
    private function parse_date($date_string) {
        if (empty($date_string)) {
            return current_time('mysql', false);
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
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        // Return current date if parsing failed
        return current_time('mysql', false);
    }
}
