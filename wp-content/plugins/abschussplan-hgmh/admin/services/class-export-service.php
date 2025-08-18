<?php
/**
 * Export Service Class
 * Secure file generation and export handling
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export Service for secure file operations
 */
class AHGMH_Export_Service {
    
    private $upload_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->upload_dir = wp_upload_dir();
    }
    
    /**
     * Create export file
     */
    public function create_export($species = '', $format = 'csv') {
        $filename = AHGMH_Validation_Service::generate_secure_filename('abschuss_export', $format);
        $filepath = $this->upload_dir['path'] . '/' . $filename;
        
        // Get data
        $data = $this->get_export_data($species);
        
        // Generate file
        switch ($format) {
            case 'csv':
                $this->create_csv_file($filepath, $data);
                break;
            case 'excel':
                $this->create_excel_file($filepath, $data);
                break;
            default:
                throw new Exception('Unsupported format');
        }
        
        // Schedule cleanup
        $this->schedule_cleanup($filepath);
        
        return [
            'download_url' => esc_url($this->upload_dir['url'] . '/' . $filename),
            'filename' => esc_html($filename),
            'size' => $this->format_file_size(filesize($filepath)),
            'records' => count($data)
        ];
    }
    
    /**
     * Create filtered export
     */
    public function create_filtered_export($filters) {
        $filename = AHGMH_Validation_Service::generate_secure_filename('filtered_export', $filters['format']);
        $filepath = $this->upload_dir['path'] . '/' . $filename;
        
        // Get filtered data
        $data = $this->get_filtered_data($filters);
        
        // Generate file
        $this->create_csv_file($filepath, $data);
        
        // Schedule cleanup
        $this->schedule_cleanup($filepath);
        
        return [
            'download_url' => esc_url($this->upload_dir['url'] . '/' . $filename),
            'filename' => esc_html($filename),
            'size' => $this->format_file_size(filesize($filepath)),
            'records' => count($data)
        ];
    }
    
    /**
     * Get export data from database
     */
    private function get_export_data($species = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';
        
        if (!empty($species)) {
            $query = $wpdb->prepare(
                "SELECT datum, art, kategorie, meldegruppe, anzahl, wus_nummer, created_at 
                FROM $table_name 
                WHERE art = %s 
                ORDER BY datum DESC",
                $species
            );
        } else {
            $query = "SELECT datum, art, kategorie, meldegruppe, anzahl, wus_nummer, created_at 
                     FROM $table_name 
                     ORDER BY datum DESC";
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        // Sanitize data
        $sanitized = [];
        foreach ($results as $row) {
            $sanitized[] = [
                'datum' => esc_html($row['datum']),
                'art' => esc_html($row['art']),
                'kategorie' => esc_html($row['kategorie']),
                'meldegruppe' => esc_html($row['meldegruppe']),
                'anzahl' => absint($row['anzahl']),
                'wus_nummer' => esc_html($row['wus_nummer'] ?? ''),
                'created_at' => esc_html($row['created_at'])
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Get filtered data
     */
    private function get_filtered_data($filters) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_submissions';
        
        $where_conditions = [];
        $where_values = [];
        
        if (!empty($filters['species'])) {
            $where_conditions[] = 'art = %s';
            $where_values[] = $filters['species'];
        }
        
        if (!empty($filters['meldegruppe'])) {
            $where_conditions[] = 'meldegruppe = %s';
            $where_values[] = $filters['meldegruppe'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'datum >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'datum <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $query = "SELECT datum, art, kategorie, meldegruppe, anzahl, wus_nummer, created_at FROM $table_name";
        
        if (!empty($where_conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query .= ' ORDER BY datum DESC';
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        // Sanitize data
        $sanitized = [];
        foreach ($results as $row) {
            $sanitized[] = [
                'datum' => esc_html($row['datum']),
                'art' => esc_html($row['art']),
                'kategorie' => esc_html($row['kategorie']),
                'meldegruppe' => esc_html($row['meldegruppe']),
                'anzahl' => absint($row['anzahl']),
                'wus_nummer' => esc_html($row['wus_nummer'] ?? ''),
                'created_at' => esc_html($row['created_at'])
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Create CSV file
     */
    private function create_csv_file($filepath, $data) {
        $fp = fopen($filepath, 'w');
        
        if (!$fp) {
            throw new Exception('Could not create export file');
        }
        
        // Add BOM for proper UTF-8 encoding in Excel
        fwrite($fp, "\xEF\xBB\xBF");
        
        // Header row
        fputcsv($fp, [
            'Datum',
            'Wildart', 
            'Kategorie',
            'Meldegruppe',
            'Anzahl',
            'WUS-Nummer',
            'Erstellt am'
        ]);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($fp, [
                $row['datum'],
                $row['art'],
                $row['kategorie'],
                $row['meldegruppe'],
                $row['anzahl'],
                $row['wus_nummer'],
                $row['created_at']
            ]);
        }
        
        fclose($fp);
    }
    
    /**
     * Create Excel file (basic implementation)
     */
    private function create_excel_file($filepath, $data) {
        // For now, create CSV with .xls extension
        // In a real implementation, you'd use a library like PHPSpreadsheet
        $this->create_csv_file($filepath, $data);
    }
    
    /**
     * Schedule file cleanup
     */
    private function schedule_cleanup($filepath) {
        // Delete file after 1 hour
        wp_schedule_single_event(time() + 3600, 'ahgmh_cleanup_export_file', [$filepath]);
    }
    
    /**
     * Format file size
     */
    private function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Clean up old export files
     */
    public static function cleanup_export_file($filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}

// Register cleanup hook
add_action('ahgmh_cleanup_export_file', ['AHGMH_Export_Service', 'cleanup_export_file']);
