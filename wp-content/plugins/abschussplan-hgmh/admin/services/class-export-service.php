<?php
/**
 * Export Service Class
 * Zentrale Export-Funktionalität für alle Abschussmeldungen
 *
 * Bug #2 Fix: Zentralisiert alle Export-Operationen mit vollständigen Daten
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export Service - Zentrale Klasse für alle Export-Operationen
 *
 * Verwendet von:
 * - Admin Export-Button
 * - URL-basierter Export
 * - AJAX Export-Handler
 */
class AHGMH_Export_Service {

    /**
     * WordPress Upload-Verzeichnis
     */
    private $upload_dir;

    /**
     * Datenbankpräfix
     */
    private $wpdb;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->upload_dir = wp_upload_dir();
    }

    /**
     * Zentrale Export-Methode für Abschussmeldungen
     *
     * @param array $filters Filter-Parameter:
     *   - wildart: string - Wildart-Name
     *   - date_from: string - Startdatum (YYYY-MM-DD)
     *   - date_to: string - Enddatum (YYYY-MM-DD)
     *   - meldegruppe: string - Meldegruppe-Name
     *   - status: string - Status (pending, approved, rejected)
     *   - format: string - Export-Format (csv, excel)
     * @return array Export-Ergebnis mit download_url, filename, size, records
     * @throws Exception Bei Fehlern
     */
    public function export_submissions($filters = array()) {
        // Standardwerte für Filter
        $filters = wp_parse_args($filters, array(
            'wildart' => '',
            'date_from' => '',
            'date_to' => '',
            'meldegruppe' => '',
            'status' => '',
            'format' => 'csv'
        ));

        // Validierung des Formats
        $format = in_array($filters['format'], array('csv', 'excel')) ? $filters['format'] : 'csv';

        // Sichere Dateinamensgenerierung
        $filename = $this->generate_secure_filename('abschuss_export', $format);
        $filepath = $this->upload_dir['path'] . '/' . $filename;

        // Daten abrufen
        $data = $this->get_submissions_data($filters);

        // Datei generieren
        switch ($format) {
            case 'csv':
                $this->create_csv_file($filepath, $data);
                break;
            case 'excel':
                // Excel wird als CSV mit .xls Endung erstellt (Kompatibilität)
                $this->create_csv_file($filepath, $data);
                break;
            default:
                throw new Exception(__('Nicht unterstütztes Format', 'abschussplan-hgmh'));
        }

        // Aufräumung planen
        $this->schedule_cleanup($filepath);

        // Dateigröße ermitteln (mit Fehlerbehandlung)
        $file_size = file_exists($filepath) ? filesize($filepath) : 0;

        return array(
            'download_url' => esc_url($this->upload_dir['url'] . '/' . $filename),
            'filename' => esc_html($filename),
            'size' => $this->format_file_size($file_size),
            'records' => count($data)
        );
    }

    /**
     * Abrufen der Abschussmeldungen mit Filtern
     *
     * @param array $filters Filter-Parameter
     * @return array Array von Submissions mit allen Feldern
     */
    private function get_submissions_data($filters) {
        $table_name = $this->wpdb->prefix . 'ahgmh_submissions';
        $jagdbezirke_table = $this->wpdb->prefix . 'ahgmh_jagdbezirke';
        $users_table = $this->wpdb->users;

        // Basis-Query mit JOINs für Meldegruppe und Benutzer
        $query = "SELECT
                    s.id,
                    s.game_species AS wildart,
                    s.field2 AS kategorie,
                    s.field1 AS datum,
                    s.field3 AS wus_nummer,
                    j.meldegruppe,
                    s.field5 AS jagdbezirk,
                    u.display_name AS erfasser,
                    s.created_at AS erfassungsdatum,
                    s.status,
                    s.field4 AS bemerkung,
                    s.field6 AS interne_notiz,
                    s.submitter_email,
                    s.approved_by,
                    s.approved_at,
                    s.rejected_by,
                    s.rejected_at,
                    s.rejection_reason
                FROM {$table_name} s
                LEFT JOIN {$jagdbezirke_table} j ON s.field5 = j.jagdbezirk
                LEFT JOIN {$users_table} u ON s.user_id = u.ID";

        // WHERE-Bedingungen aufbauen
        $where_conditions = array();
        $where_values = array();

        // Filter: Wildart
        if (!empty($filters['wildart'])) {
            $where_conditions[] = 's.game_species = %s';
            $where_values[] = sanitize_text_field($filters['wildart']);
        }

        // Filter: Datum von
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'DATE(s.created_at) >= %s';
            $where_values[] = sanitize_text_field($filters['date_from']);
        }

        // Filter: Datum bis
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'DATE(s.created_at) <= %s';
            $where_values[] = sanitize_text_field($filters['date_to']);
        }

        // Filter: Meldegruppe
        if (!empty($filters['meldegruppe'])) {
            $where_conditions[] = 'j.meldegruppe = %s';
            $where_values[] = sanitize_text_field($filters['meldegruppe']);
        }

        // Filter: Status
        if (!empty($filters['status'])) {
            $where_conditions[] = 's.status = %s';
            $where_values[] = sanitize_text_field($filters['status']);
        }

        // WHERE-Klausel zusammensetzen
        if (!empty($where_conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $where_conditions);
        }

        // Sortierung
        $query .= ' ORDER BY s.created_at DESC';

        // Query vorbereiten und ausführen
        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }

        $results = $this->wpdb->get_results($query, ARRAY_A);

        if (!$results) {
            return array();
        }

        // Daten aufbereiten und bereinigen
        $sanitized = array();
        foreach ($results as $row) {
            // Erfasser-Namen ermitteln (falls nicht per JOIN verfügbar)
            $erfasser_name = !empty($row['erfasser']) ? $row['erfasser'] : '';
            if (empty($erfasser_name) && !empty($row['submitter_email'])) {
                $erfasser_name = $row['submitter_email'];
            }

            // Status übersetzen
            $status_translated = $this->translate_status($row['status']);

            // Genehmiger-Namen ermitteln
            $genehmiger_name = '';
            if (!empty($row['approved_by'])) {
                $approver = get_userdata($row['approved_by']);
                if ($approver) {
                    $genehmiger_name = $approver->display_name;
                }
            }

            $sanitized[] = array(
                'id' => absint($row['id']),
                'wildart' => esc_html($row['wildart'] ?? ''),
                'kategorie' => esc_html($row['kategorie'] ?? ''),
                'datum' => esc_html($row['datum'] ?? ''),
                'wus_nummer' => esc_html($row['wus_nummer'] ?? ''),
                'meldegruppe' => esc_html($row['meldegruppe'] ?? ''),
                'jagdbezirk' => esc_html($row['jagdbezirk'] ?? ''),
                'erfasser' => esc_html($erfasser_name),
                'erfassungsdatum' => esc_html($row['erfassungsdatum'] ?? ''),
                'status' => esc_html($status_translated),
                'bemerkung' => esc_html($row['bemerkung'] ?? ''),
                'interne_notiz' => esc_html($row['interne_notiz'] ?? ''),
                'genehmigt_von' => esc_html($genehmiger_name),
                'genehmigt_am' => esc_html($row['approved_at'] ?? ''),
                'ablehnungsgrund' => esc_html($row['rejection_reason'] ?? '')
            );
        }

        return $sanitized;
    }

    /**
     * Status-Wert übersetzen
     *
     * @param string $status Status-Wert
     * @return string Übersetzter Status
     */
    private function translate_status($status) {
        $status_map = array(
            'pending' => __('Ausstehend', 'abschussplan-hgmh'),
            'pending_email' => __('E-Mail-Bestätigung ausstehend', 'abschussplan-hgmh'),
            'pending_approval' => __('Genehmigung ausstehend', 'abschussplan-hgmh'),
            'approved' => __('Genehmigt', 'abschussplan-hgmh'),
            'rejected' => __('Abgelehnt', 'abschussplan-hgmh'),
            'verified' => __('Verifiziert', 'abschussplan-hgmh')
        );

        return isset($status_map[$status]) ? $status_map[$status] : $status;
    }

    /**
     * CSV-Datei erstellen
     *
     * @param string $filepath Dateipfad
     * @param array $data Exportdaten
     * @throws Exception Bei Fehlern
     */
    private function create_csv_file($filepath, $data) {
        $fp = fopen($filepath, 'w');

        if (!$fp) {
            throw new Exception(__('Export-Datei konnte nicht erstellt werden', 'abschussplan-hgmh'));
        }

        // BOM für UTF-8 Encoding (Excel-Kompatibilität)
        fwrite($fp, "\xEF\xBB\xBF");

        // Deutsche Spaltenüberschriften (vollständig)
        $headers = array(
            'ID',
            'Wildart',
            'Kategorie',
            'Datum',
            'WUS-Nummer',
            'Meldegruppe',
            'Jagdbezirk',
            'Erfasser',
            'Erfassungsdatum',
            'Status',
            'Bemerkung',
            'Interne Notiz',
            'Genehmigt von',
            'Genehmigt am',
            'Ablehnungsgrund'
        );

        fputcsv($fp, $headers, ';'); // Semikolon als Trennzeichen für deutsche Excel-Versionen

        // Datenzeilen
        foreach ($data as $row) {
            $csv_row = array(
                $row['id'],
                $row['wildart'],
                $row['kategorie'],
                $row['datum'],
                $row['wus_nummer'],
                $row['meldegruppe'],
                $row['jagdbezirk'],
                $row['erfasser'],
                $this->format_date($row['erfassungsdatum']),
                $row['status'],
                $row['bemerkung'],
                $row['interne_notiz'],
                $row['genehmigt_von'],
                $this->format_date($row['genehmigt_am']),
                $row['ablehnungsgrund']
            );

            fputcsv($fp, $csv_row, ';');
        }

        fclose($fp);
    }

    /**
     * Datum formatieren
     *
     * @param string $date MySQL-Datum
     * @return string Formatiertes Datum
     */
    private function format_date($date) {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '';
        }

        return mysql2date('d.m.Y H:i', $date);
    }

    /**
     * Sicheren Dateinamen generieren
     *
     * @param string $prefix Dateinamenpräfix
     * @param string $extension Dateiendung
     * @return string Sicherer Dateiname
     */
    private function generate_secure_filename($prefix, $extension) {
        $prefix = sanitize_file_name($prefix);
        $extension = ($extension === 'excel') ? 'xls' : 'csv';
        $timestamp = date('Y-m-d_H-i-s');
        $random = wp_generate_password(8, false);

        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Aufräumung für Export-Datei planen
     *
     * @param string $filepath Dateipfad
     */
    private function schedule_cleanup($filepath) {
        // Datei nach 1 Stunde löschen
        wp_schedule_single_event(time() + 3600, 'ahgmh_cleanup_export_file', array($filepath));
    }

    /**
     * Dateigröße formatieren
     *
     * @param int $bytes Größe in Bytes
     * @return string Formatierte Größe
     */
    private function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' Bytes';
        }
    }

    /**
     * Export-Datei aufräumen (statische Methode für Cron)
     *
     * @param string $filepath Dateipfad
     */
    public static function cleanup_export_file($filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Schnell-Export für eine Wildart erstellen
     * Kompatibilitätsmethode für bestehenden Code
     *
     * @param string $species Wildart
     * @param string $format Format (csv/excel)
     * @return array Export-Ergebnis
     */
    public function create_export($species = '', $format = 'csv') {
        return $this->export_submissions(array(
            'wildart' => $species,
            'format' => $format
        ));
    }

    /**
     * Gefilterter Export erstellen
     * Kompatibilitätsmethode für bestehenden Code
     *
     * @param array $filters Filter-Parameter
     * @return array Export-Ergebnis
     */
    public function create_filtered_export($filters) {
        // Altes Format in neues Format konvertieren
        $new_filters = array(
            'wildart' => isset($filters['species']) ? $filters['species'] : '',
            'meldegruppe' => isset($filters['meldegruppe']) ? $filters['meldegruppe'] : '',
            'date_from' => isset($filters['date_from']) ? $filters['date_from'] : '',
            'date_to' => isset($filters['date_to']) ? $filters['date_to'] : '',
            'status' => isset($filters['status']) ? $filters['status'] : '',
            'format' => isset($filters['format']) ? $filters['format'] : 'csv'
        );

        return $this->export_submissions($new_filters);
    }

    /**
     * Verfügbare Meldegruppen für Filter abrufen
     *
     * @return array Liste der Meldegruppen
     */
    public function get_available_meldegruppen() {
        $table_name = $this->wpdb->prefix . 'ahgmh_jagdbezirke';

        $results = $this->wpdb->get_col(
            "SELECT DISTINCT meldegruppe FROM {$table_name} WHERE meldegruppe != '' ORDER BY meldegruppe"
        );

        return $results ? $results : array();
    }

    /**
     * Verfügbare Wildarten für Filter abrufen
     *
     * @return array Liste der Wildarten
     */
    public function get_available_wildarten() {
        return get_option('ahgmh_species', array('Rotwild', 'Damwild'));
    }

    /**
     * Verfügbare Status-Werte für Filter abrufen
     *
     * @return array Liste der Status-Werte mit Übersetzungen
     */
    public function get_available_statuses() {
        return array(
            'pending' => __('Ausstehend', 'abschussplan-hgmh'),
            'pending_email' => __('E-Mail-Bestätigung ausstehend', 'abschussplan-hgmh'),
            'pending_approval' => __('Genehmigung ausstehend', 'abschussplan-hgmh'),
            'approved' => __('Genehmigt', 'abschussplan-hgmh'),
            'rejected' => __('Abgelehnt', 'abschussplan-hgmh')
        );
    }
}

// Cleanup-Hook registrieren
add_action('ahgmh_cleanup_export_file', array('AHGMH_Export_Service', 'cleanup_export_file'));
