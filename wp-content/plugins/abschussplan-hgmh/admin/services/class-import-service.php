<?php
/**
 * Import Service Class
 * Handles file upload, CSV/Excel parsing, and data extraction
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import Service for file parsing and data extraction
 */
class AHGMH_Import_Service {

    private $upload_dir;
    private $allowed_mime_types;
    private $max_file_size;

    /**
     * Constructor
     */
    public function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->allowed_mime_types = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        $this->max_file_size = 5 * 1024 * 1024; // 5MB
    }

    /**
     * Handle file upload
     *
     * @param array $file The uploaded file from $_FILES
     * @return array Upload result with filepath and metadata
     * @throws Exception On upload or validation failure
     */
    public function handle_upload($file) {
        // Validate file upload
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception(__('Ungültige Datei-Parameter.', 'abschussplan-hgmh'));
        }

        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception(__('Datei überschreitet die maximale Dateigröße.', 'abschussplan-hgmh'));
            case UPLOAD_ERR_NO_FILE:
                throw new Exception(__('Keine Datei hochgeladen.', 'abschussplan-hgmh'));
            default:
                throw new Exception(__('Upload-Fehler aufgetreten.', 'abschussplan-hgmh'));
        }

        // Validate file size
        if ($file['size'] > $this->max_file_size) {
            throw new Exception(sprintf(
                __('Datei ist zu groß. Maximum: %s', 'abschussplan-hgmh'),
                $this->format_file_size($this->max_file_size)
            ));
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $this->allowed_mime_types)) {
            throw new Exception(__('Dateityp nicht unterstützt. Bitte CSV oder Excel-Dateien verwenden.', 'abschussplan-hgmh'));
        }

        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = AHGMH_Validation_Service::generate_secure_filename('import', $extension);
        $filepath = $this->upload_dir['path'] . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception(__('Datei konnte nicht gespeichert werden.', 'abschussplan-hgmh'));
        }

        // Schedule cleanup after 1 hour
        wp_schedule_single_event(time() + 3600, 'ahgmh_cleanup_import_file', [$filepath]);

        return [
            'filepath' => $filepath,
            'filename' => sanitize_file_name($file['name']),
            'size' => $file['size'],
            'mime_type' => $mime_type,
            'extension' => sanitize_text_field($extension)
        ];
    }

    /**
     * Parse uploaded file and extract data
     *
     * @param string $filepath Path to the uploaded file
     * @return array Parsed data with headers and rows
     * @throws Exception On parsing failure
     */
    public function parse_file($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception(__('Datei nicht gefunden.', 'abschussplan-hgmh'));
        }

        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'csv':
            case 'txt':
                return $this->parse_csv($filepath);

            case 'xlsx':
            case 'xls':
                return $this->parse_excel($filepath);

            default:
                throw new Exception(__('Nicht unterstütztes Dateiformat.', 'abschussplan-hgmh'));
        }
    }

    /**
     * Parse CSV file with automatic delimiter and encoding detection
     *
     * @param string $filepath Path to CSV file
     * @return array Parsed data with headers and rows
     * @throws Exception On parsing failure
     */
    private function parse_csv($filepath) {
        // Read file and detect encoding
        $content = file_get_contents($filepath);

        if ($content === false) {
            throw new Exception(__('Datei konnte nicht gelesen werden.', 'abschussplan-hgmh'));
        }

        // Remove UTF-8 BOM if present
        $content = $this->remove_bom($content);

        // Detect and convert encoding
        $content = $this->convert_encoding($content);

        // Detect delimiter (comma or semicolon for German Excel exports)
        $delimiter = $this->detect_delimiter($content);

        // Parse CSV content
        $lines = str_getcsv($content, "\n");
        $data = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue; // Skip empty lines
            }
            $data[] = str_getcsv($line, $delimiter);
        }

        if (empty($data)) {
            throw new Exception(__('Datei enthält keine Daten.', 'abschussplan-hgmh'));
        }

        // Extract headers (first row)
        $headers = array_shift($data);

        // Sanitize headers
        $headers = array_map(function($header) {
            return sanitize_text_field(trim($header));
        }, $headers);

        // Sanitize data rows
        $sanitized_data = [];
        foreach ($data as $row) {
            if (count($row) === count($headers)) {
                $sanitized_row = array_map(function($cell) {
                    return sanitize_text_field(trim($cell));
                }, $row);
                $sanitized_data[] = $sanitized_row;
            }
        }

        return [
            'headers' => $headers,
            'data' => $sanitized_data,
            'row_count' => count($sanitized_data),
            'column_count' => count($headers),
            'delimiter' => $delimiter === ';' ? 'semicolon' : 'comma'
        ];
    }

    /**
     * Parse Excel file
     *
     * @param string $filepath Path to Excel file
     * @return array Parsed data with headers and rows
     * @throws Exception On parsing failure
     */
    private function parse_excel($filepath) {
        // Check if PhpSpreadsheet is available
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return $this->parse_excel_with_library($filepath);
        }

        // Fallback: Try to read as CSV if Excel library not available
        // Many .xls files exported from Excel are actually CSV with different extension
        try {
            return $this->parse_csv($filepath);
        } catch (Exception $e) {
            throw new Exception(__('Excel-Unterstützung nicht verfügbar. Bitte konvertieren Sie die Datei zu CSV oder installieren Sie PhpSpreadsheet.', 'abschussplan-hgmh'));
        }
    }

    /**
     * Parse Excel file using PhpSpreadsheet library
     *
     * @param string $filepath Path to Excel file
     * @return array Parsed data with headers and rows
     * @throws Exception On parsing failure
     */
    private function parse_excel_with_library($filepath) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                throw new Exception(__('Excel-Datei enthält keine Daten.', 'abschussplan-hgmh'));
            }

            // Extract headers
            $headers = array_shift($rows);

            // Sanitize headers
            $headers = array_map(function($header) {
                return sanitize_text_field(trim($header));
            }, $headers);

            // Sanitize data rows
            $sanitized_data = [];
            foreach ($rows as $row) {
                if (count($row) === count($headers)) {
                    $sanitized_row = array_map(function($cell) {
                        return sanitize_text_field(trim($cell));
                    }, $row);
                    $sanitized_data[] = $sanitized_row;
                }
            }

            return [
                'headers' => $headers,
                'data' => $sanitized_data,
                'row_count' => count($sanitized_data),
                'column_count' => count($headers),
                'delimiter' => 'excel'
            ];

        } catch (Exception $e) {
            throw new Exception(__('Fehler beim Parsen der Excel-Datei: ', 'abschussplan-hgmh') . esc_html($e->getMessage()));
        }
    }

    /**
     * Remove UTF-8 BOM from content
     *
     * @param string $content File content
     * @return string Content without BOM
     */
    private function remove_bom($content) {
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        return $content;
    }

    /**
     * Detect and convert character encoding to UTF-8
     *
     * @param string $content File content
     * @return string UTF-8 encoded content
     */
    private function convert_encoding($content) {
        // Detect encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'], true);

        // Convert to UTF-8 if needed
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    /**
     * Detect CSV delimiter (comma or semicolon)
     *
     * @param string $content CSV content
     * @return string Detected delimiter
     */
    private function detect_delimiter($content) {
        // Get first line for analysis
        $first_line = strtok($content, "\n");

        // Count occurrences of potential delimiters
        $comma_count = substr_count($first_line, ',');
        $semicolon_count = substr_count($first_line, ';');

        // German Excel exports typically use semicolon
        return ($semicolon_count > $comma_count) ? ';' : ',';
    }

    /**
     * Get preview of imported data (first N rows)
     *
     * @param string $filepath Path to the file
     * @param int $limit Number of preview rows
     * @return array Preview data with headers and limited rows
     * @throws Exception On parsing failure
     */
    public function get_preview($filepath, $limit = 5) {
        $parsed = $this->parse_file($filepath);

        // Limit data rows for preview
        $preview_data = array_slice($parsed['data'], 0, $limit);

        return [
            'headers' => $parsed['headers'],
            'data' => $preview_data,
            'total_rows' => $parsed['row_count'],
            'preview_rows' => count($preview_data),
            'column_count' => $parsed['column_count'],
            'delimiter' => $parsed['delimiter']
        ];
    }

    /**
     * Format file size in human readable format
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function format_file_size($bytes) {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Clean up uploaded import file
     *
     * @param string $filepath Path to file to delete
     */
    public static function cleanup_import_file($filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}

// Register cleanup hook
add_action('ahgmh_cleanup_import_file', ['AHGMH_Import_Service', 'cleanup_import_file']);
