<?php
/**
 * PDF Service Class
 * Wrapper for DOMPDF library to generate professional PDF reports
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader for DOMPDF
$composer_autoload = AHGMH_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDF Service for generating PDF documents
 */
class AHGMH_PDF_Service {

    /**
     * Default PDF options
     * @var array
     */
    private $default_options = array(
        'page_size' => 'A4',
        'orientation' => 'portrait', // portrait or landscape
        'enable_html5_parser' => true,
        'enable_remote' => false,
        'enable_php' => false,
        'default_font' => 'DejaVu Sans',
        'enable_css_float' => true,
        'is_html5_parser_enabled' => true,
        'dpi' => 96,
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
    );

    /**
     * DOMPDF instance
     * @var Dompdf
     */
    private $dompdf;

    /**
     * Constructor - Initialize PDF service
     *
     * @param array $options Optional configuration options
     */
    public function __construct($options = array()) {
        // Check if DOMPDF is available
        if (!class_exists('Dompdf\Dompdf')) {
            error_log('AHGMH PDF Service: DOMPDF library not found. Please run: composer install');
            return;
        }

        // Merge provided options with defaults
        $config = array_merge($this->default_options, $options);

        // Configure DOMPDF options
        $dompdf_options = new Options();
        $dompdf_options->set('isHtml5ParserEnabled', $config['enable_html5_parser']);
        $dompdf_options->set('isRemoteEnabled', $config['enable_remote']);
        $dompdf_options->set('isPhpEnabled', $config['enable_php']);
        $dompdf_options->set('defaultFont', $config['default_font']);
        $dompdf_options->set('dpi', $config['dpi']);

        // Set temp directory for font cache
        $temp_dir = sys_get_temp_dir();
        $dompdf_options->set('fontDir', $temp_dir);
        $dompdf_options->set('fontCache', $temp_dir);
        $dompdf_options->set('tempDir', $temp_dir);

        // Enable CSS float for better layout support
        $dompdf_options->set('enable_css_float', $config['enable_css_float']);

        // Initialize DOMPDF
        $this->dompdf = new Dompdf($dompdf_options);

        // Set default paper size and orientation
        $this->dompdf->setPaper($config['page_size'], $config['orientation']);
    }

    /**
     * Generate PDF from HTML content
     *
     * @param string $html HTML content to convert to PDF
     * @param array $options Optional configuration (page_size, orientation)
     * @return bool|string PDF content as string or false on failure
     */
    public function generate_pdf($html, $options = array()) {
        if (!$this->dompdf) {
            error_log('AHGMH PDF Service: DOMPDF not initialized');
            return false;
        }

        try {
            // Set paper size and orientation if provided
            if (isset($options['page_size']) || isset($options['orientation'])) {
                $page_size = isset($options['page_size']) ? $options['page_size'] : 'A4';
                $orientation = isset($options['orientation']) ? $options['orientation'] : 'portrait';
                $this->dompdf->setPaper($page_size, $orientation);
            }

            // Ensure proper UTF-8 encoding for German characters
            if (!mb_check_encoding($html, 'UTF-8')) {
                $html = mb_convert_encoding($html, 'UTF-8', 'auto');
            }

            // Load HTML content
            $this->dompdf->loadHtml($html);

            // Render the PDF
            $this->dompdf->render();

            // Return PDF as string
            return $this->dompdf->output();

        } catch (Exception $e) {
            error_log('AHGMH PDF Service: Error generating PDF - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate and save PDF to file
     *
     * @param string $html HTML content to convert to PDF
     * @param string $filepath Full path where PDF should be saved
     * @param array $options Optional configuration (page_size, orientation)
     * @return bool True on success, false on failure
     */
    public function save_pdf($html, $filepath, $options = array()) {
        $pdf_content = $this->generate_pdf($html, $options);

        if ($pdf_content === false) {
            return false;
        }

        try {
            // Ensure directory exists
            $directory = dirname($filepath);
            if (!is_dir($directory)) {
                wp_mkdir_p($directory);
            }

            // Save PDF to file
            $result = file_put_contents($filepath, $pdf_content);

            if ($result === false) {
                error_log('AHGMH PDF Service: Failed to write PDF to ' . $filepath);
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log('AHGMH PDF Service: Error saving PDF - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate and stream PDF for download
     *
     * @param string $html HTML content to convert to PDF
     * @param string $filename Filename for download (without extension)
     * @param array $options Optional configuration (page_size, orientation)
     * @return bool True on success, false on failure
     */
    public function stream_pdf($html, $filename, $options = array()) {
        if (!$this->dompdf) {
            error_log('AHGMH PDF Service: DOMPDF not initialized');
            return false;
        }

        try {
            // Set paper size and orientation if provided
            if (isset($options['page_size']) || isset($options['orientation'])) {
                $page_size = isset($options['page_size']) ? $options['page_size'] : 'A4';
                $orientation = isset($options['orientation']) ? $options['orientation'] : 'portrait';
                $this->dompdf->setPaper($page_size, $orientation);
            }

            // Ensure proper UTF-8 encoding for German characters
            if (!mb_check_encoding($html, 'UTF-8')) {
                $html = mb_convert_encoding($html, 'UTF-8', 'auto');
            }

            // Sanitize filename
            $filename = $this->sanitize_filename($filename);

            // Load HTML content
            $this->dompdf->loadHtml($html);

            // Render the PDF
            $this->dompdf->render();

            // Stream the PDF to browser for download
            $this->dompdf->stream($filename . '.pdf', array(
                'Attachment' => true,
                'compress' => true
            ));

            return true;

        } catch (Exception $e) {
            error_log('AHGMH PDF Service: Error streaming PDF - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get PDF as base64 encoded string (useful for email attachments)
     *
     * @param string $html HTML content to convert to PDF
     * @param array $options Optional configuration (page_size, orientation)
     * @return string|false Base64 encoded PDF or false on failure
     */
    public function get_pdf_base64($html, $options = array()) {
        $pdf_content = $this->generate_pdf($html, $options);

        if ($pdf_content === false) {
            return false;
        }

        return base64_encode($pdf_content);
    }

    /**
     * Set page size and orientation
     *
     * @param string $size Page size (A4, Letter, Legal, etc.)
     * @param string $orientation Page orientation (portrait or landscape)
     */
    public function set_paper($size = 'A4', $orientation = 'portrait') {
        if ($this->dompdf) {
            $this->dompdf->setPaper($size, $orientation);
        }
    }

    /**
     * Sanitize filename for safe file operations
     *
     * @param string $filename Input filename
     * @return string Sanitized filename
     */
    private function sanitize_filename($filename) {
        // Remove any path components
        $filename = basename($filename);

        // Remove .pdf extension if present
        $filename = preg_replace('/\.pdf$/i', '', $filename);

        // Sanitize for filesystem
        $filename = sanitize_file_name($filename);

        // Replace spaces with hyphens
        $filename = str_replace(' ', '-', $filename);

        // Remove any remaining special characters except hyphens and underscores
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '', $filename);

        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'report-' . time();
        }

        return $filename;
    }

    /**
     * Get DOMPDF version
     *
     * @return string|null DOMPDF version or null if not available
     */
    public function get_version() {
        if (defined('Dompdf\VERSION')) {
            return constant('Dompdf\VERSION');
        }
        return null;
    }

    /**
     * Check if DOMPDF is available and properly configured
     *
     * @return array Status information
     */
    public function check_status() {
        $status = array(
            'available' => false,
            'version' => null,
            'message' => '',
        );

        if (!class_exists('Dompdf\Dompdf')) {
            $status['message'] = 'DOMPDF library not found. Please run: composer install';
            return $status;
        }

        if (!$this->dompdf) {
            $status['message'] = 'DOMPDF not initialized properly';
            return $status;
        }

        $status['available'] = true;
        $status['version'] = $this->get_version();
        $status['message'] = 'DOMPDF is available and ready';

        return $status;
    }

    /**
     * Load HTML template and replace placeholders
     *
     * @param string $template_path Path to HTML template file
     * @param array $variables Associative array of variables to replace
     * @return string|false Processed HTML or false on failure
     */
    public function load_template($template_path, $variables = array()) {
        if (!file_exists($template_path)) {
            error_log('AHGMH PDF Service: Template not found - ' . $template_path);
            return false;
        }

        // Load template content
        $html = file_get_contents($template_path);

        if ($html === false) {
            error_log('AHGMH PDF Service: Failed to read template - ' . $template_path);
            return false;
        }

        // Replace placeholders with variables
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $html = str_replace($placeholder, $value, $html);
        }

        return $html;
    }

    /**
     * Wrap content in basic HTML structure with UTF-8 charset
     *
     * @param string $content HTML content body
     * @param string $title Document title
     * @param string $css Optional CSS styles
     * @return string Complete HTML document
     */
    public function wrap_html($content, $title = 'Report', $css = '') {
        $html = '<!DOCTYPE html>';
        $html .= '<html lang="de">';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $html .= '<title>' . esc_html($title) . '</title>';

        if (!empty($css)) {
            $html .= '<style>' . $css . '</style>';
        }

        $html .= '</head>';
        $html .= '<body>';
        $html .= $content;
        $html .= '</body>';
        $html .= '</html>';

        return $html;
    }
}
