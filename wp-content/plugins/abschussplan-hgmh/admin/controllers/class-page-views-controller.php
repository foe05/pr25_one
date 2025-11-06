<?php
/**
 * Page Views Statistics Controller
 *
 * Handles admin interface for page view statistics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for managing page view statistics in admin area
 */
class AHGMH_Page_Views_Controller {
    /**
     * Page view logger instance
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new AHGMH_Page_View_Logger();

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);

        // Register AJAX handlers
        add_action('wp_ajax_ahgmh_get_page_view_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_ahgmh_export_page_views', array($this, 'ajax_export_csv'));
        add_action('wp_ajax_ahgmh_cleanup_page_views', array($this, 'ajax_cleanup_logs'));
        add_action('wp_ajax_ahgmh_delete_all_page_views', array($this, 'ajax_delete_all_logs'));
        add_action('wp_ajax_ahgmh_save_page_view_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'abschussplan-hgmh-settings',
            __('Seitenaufrufe', 'abschussplan-hgmh'),
            __('Seitenaufrufe', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-page-views',
            array($this, 'render_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine Berechtigung für diese Seite.', 'abschussplan-hgmh'));
        }

        // Get filters from request
        $filters = array();
        if (!empty($_GET['shortcode_name'])) {
            $filters['shortcode_name'] = sanitize_text_field($_GET['shortcode_name']);
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }

        // Get pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        // Get data
        $summary = $this->logger->get_summary($filters);
        $page_views = $this->logger->get_statistics($filters, $per_page, $offset);
        $total_count = $this->logger->get_total_count($filters);
        $total_pages = ceil($total_count / $per_page);

        // Get settings
        $log_ip_addresses = get_option('ahgmh_log_ip_addresses', true);
        $anonymize_ip = get_option('ahgmh_anonymize_ip', false);
        $auto_cleanup_enabled = get_option('ahgmh_auto_cleanup_enabled', false);
        $auto_cleanup_days = get_option('ahgmh_auto_cleanup_days', 90);

        // Include view
        include AHGMH_PLUGIN_DIR . 'admin/views/page-views-statistics.php';
    }

    /**
     * AJAX handler for getting statistics
     */
    public function ajax_get_stats() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'abschussplan-hgmh')));
        }

        // Get filters
        $filters = array();
        if (!empty($_POST['shortcode_name'])) {
            $filters['shortcode_name'] = sanitize_text_field($_POST['shortcode_name']);
        }
        if (!empty($_POST['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_POST['date_from']);
        }
        if (!empty($_POST['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_POST['date_to']);
        }

        $summary = $this->logger->get_summary($filters);

        wp_send_json_success($summary);
    }

    /**
     * AJAX handler for exporting CSV
     */
    public function ajax_export_csv() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'abschussplan-hgmh'));
        }

        // Get filters
        $filters = array();
        if (!empty($_GET['shortcode_name'])) {
            $filters['shortcode_name'] = sanitize_text_field($_GET['shortcode_name']);
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }

        // Export CSV
        $csv_content = $this->logger->export_csv($filters);

        // Send headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="page-views-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv_content;
        exit;
    }

    /**
     * AJAX handler for cleanup
     */
    public function ajax_cleanup_logs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'abschussplan-hgmh')));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ahgmh_page_views_nonce')) {
            wp_send_json_error(array('message' => __('Sicherheitscheck fehlgeschlagen', 'abschussplan-hgmh')));
        }

        $days = isset($_POST['days']) ? intval($_POST['days']) : 90;

        $deleted = $this->logger->cleanup_old_logs($days);

        if ($deleted !== false) {
            wp_send_json_success(array(
                'message' => sprintf(__('%d Einträge wurden gelöscht', 'abschussplan-hgmh'), $deleted),
                'deleted' => $deleted
            ));
        } else {
            wp_send_json_error(array('message' => __('Fehler beim Löschen', 'abschussplan-hgmh')));
        }
    }

    /**
     * AJAX handler for deleting all logs
     */
    public function ajax_delete_all_logs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'abschussplan-hgmh')));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ahgmh_page_views_nonce')) {
            wp_send_json_error(array('message' => __('Sicherheitscheck fehlgeschlagen', 'abschussplan-hgmh')));
        }

        $deleted = $this->logger->delete_all_logs();

        if ($deleted !== false) {
            wp_send_json_success(array(
                'message' => __('Alle Einträge wurden gelöscht', 'abschussplan-hgmh')
            ));
        } else {
            wp_send_json_error(array('message' => __('Fehler beim Löschen', 'abschussplan-hgmh')));
        }
    }

    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'abschussplan-hgmh')));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ahgmh_page_views_nonce')) {
            wp_send_json_error(array('message' => __('Sicherheitscheck fehlgeschlagen', 'abschussplan-hgmh')));
        }

        // Save settings
        $log_ip = isset($_POST['log_ip_addresses']) ? (bool) $_POST['log_ip_addresses'] : false;
        $anonymize_ip = isset($_POST['anonymize_ip']) ? (bool) $_POST['anonymize_ip'] : false;
        $auto_cleanup = isset($_POST['auto_cleanup_enabled']) ? (bool) $_POST['auto_cleanup_enabled'] : false;
        $cleanup_days = isset($_POST['auto_cleanup_days']) ? max(1, intval($_POST['auto_cleanup_days'])) : 90;

        update_option('ahgmh_log_ip_addresses', $log_ip);
        update_option('ahgmh_anonymize_ip', $anonymize_ip);
        update_option('ahgmh_auto_cleanup_enabled', $auto_cleanup);
        update_option('ahgmh_auto_cleanup_days', $cleanup_days);

        wp_send_json_success(array(
            'message' => __('Einstellungen gespeichert', 'abschussplan-hgmh')
        ));
    }
}
