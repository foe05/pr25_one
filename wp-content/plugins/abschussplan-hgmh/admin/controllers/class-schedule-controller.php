<?php
/**
 * Schedule Controller
 * Handles scheduled report configuration and management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schedule Controller Class
 */
class AHGMH_Schedule_Controller {

    private $scheduler_service;
    private $view;

    /**
     * Constructor
     */
    public function __construct() {
        // Load scheduler service
        require_once AHGMH_PLUGIN_DIR . 'admin/services/class-scheduler-service.php';
        $this->scheduler_service = new AHGMH_Scheduler_Service();

        // Load view
        if (file_exists(AHGMH_PLUGIN_DIR . 'admin/views/class-schedule-settings-view.php')) {
            require_once AHGMH_PLUGIN_DIR . 'admin/views/class-schedule-settings-view.php';
            $this->view = new AHGMH_Schedule_Settings_View();
        }

        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_create_schedule', array($this, 'ajax_create_schedule'));
        add_action('wp_ajax_ahgmh_update_schedule', array($this, 'ajax_update_schedule'));
        add_action('wp_ajax_ahgmh_delete_schedule', array($this, 'ajax_delete_schedule'));
        add_action('wp_ajax_ahgmh_toggle_schedule', array($this, 'ajax_toggle_schedule'));
        add_action('wp_ajax_ahgmh_test_schedule', array($this, 'ajax_test_schedule'));
        add_action('wp_ajax_ahgmh_get_schedule_history', array($this, 'ajax_get_schedule_history'));
        add_action('wp_ajax_ahgmh_send_test_email', array($this, 'ajax_send_test_email'));
    }

    /**
     * Render schedule settings page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get all schedules
        $schedules = $this->scheduler_service->get_all_schedules();

        // Get filter options
        $species_list = get_option('ahgmh_species', ['Rotwild', 'Damwild']);
        $meldegruppen = get_option('ahgmh_meldegruppen', []);

        // Get statistics
        $stats = $this->scheduler_service->get_statistics();

        $data = [
            'schedules' => $schedules,
            'species_list' => $species_list,
            'meldegruppen' => $meldegruppen,
            'stats' => $stats
        ];

        // Render the page using the view if available
        if ($this->view) {
            $this->view->render_settings_page($data);
        } else {
            // Fallback rendering if view not yet implemented
            $this->render_fallback($data);
        }
    }

    /**
     * AJAX handler for creating a schedule
     */
    public function ajax_create_schedule() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get schedule configuration from request
            $config = $this->get_config_from_request();

            // Create schedule
            $result = $this->scheduler_service->create_schedule($config);

            if ($result['success']) {
                wp_send_json_success([
                    'message' => __('Zeitplan erfolgreich erstellt', 'abschussplan-hgmh'),
                    'schedule' => $result['schedule']
                ]);
            } else {
                throw new Exception($result['error']);
            }

        } catch (Exception $e) {
            error_log('AHGMH Schedule Creation Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for updating a schedule
     */
    public function ajax_update_schedule() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get schedule ID
            $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';
            if (empty($schedule_id)) {
                throw new Exception(__('Schedule ID ist erforderlich', 'abschussplan-hgmh'));
            }

            // Get updated configuration from request
            $config = $this->get_config_from_request();

            // Update schedule
            $result = $this->scheduler_service->update_schedule($schedule_id, $config);

            if ($result['success']) {
                wp_send_json_success([
                    'message' => __('Zeitplan erfolgreich aktualisiert', 'abschussplan-hgmh'),
                    'schedule' => $result['schedule']
                ]);
            } else {
                throw new Exception($result['error']);
            }

        } catch (Exception $e) {
            error_log('AHGMH Schedule Update Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for deleting a schedule
     */
    public function ajax_delete_schedule() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get schedule ID
            $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';
            if (empty($schedule_id)) {
                throw new Exception(__('Schedule ID ist erforderlich', 'abschussplan-hgmh'));
            }

            // Delete schedule
            $result = $this->scheduler_service->delete_schedule($schedule_id);

            if ($result['success']) {
                wp_send_json_success([
                    'message' => __('Zeitplan erfolgreich gelöscht', 'abschussplan-hgmh')
                ]);
            } else {
                throw new Exception($result['error']);
            }

        } catch (Exception $e) {
            error_log('AHGMH Schedule Deletion Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for toggling schedule enabled/disabled
     */
    public function ajax_toggle_schedule() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get schedule ID
            $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : '';
            if (empty($schedule_id)) {
                throw new Exception(__('Schedule ID ist erforderlich', 'abschussplan-hgmh'));
            }

            // Get enabled status
            $enabled = isset($_POST['enabled']) ? filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN) : false;

            // Update schedule
            if ($enabled) {
                $result = $this->scheduler_service->enable_schedule($schedule_id);
            } else {
                $result = $this->scheduler_service->disable_schedule($schedule_id);
            }

            if ($result['success']) {
                $message = $enabled ?
                    __('Zeitplan aktiviert', 'abschussplan-hgmh') :
                    __('Zeitplan deaktiviert', 'abschussplan-hgmh');
                wp_send_json_success([
                    'message' => $message,
                    'schedule' => $result['schedule']
                ]);
            } else {
                throw new Exception($result['error']);
            }

        } catch (Exception $e) {
            error_log('AHGMH Schedule Toggle Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for testing a schedule configuration
     */
    public function ajax_test_schedule() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get schedule configuration from request
            $config = $this->get_config_from_request();

            // Test schedule
            $result = $this->scheduler_service->test_schedule($config);

            if ($result['success']) {
                wp_send_json_success([
                    'message' => __('Zeitplan-Konfiguration ist gültig', 'abschussplan-hgmh'),
                    'test_results' => $result
                ]);
            } else {
                throw new Exception($result['error']);
            }

        } catch (Exception $e) {
            error_log('AHGMH Schedule Test Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for getting schedule history
     */
    public function ajax_get_schedule_history() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get schedule ID (optional)
            $schedule_id = isset($_POST['schedule_id']) ? sanitize_text_field($_POST['schedule_id']) : null;
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;

            // Get history
            $history = $this->scheduler_service->get_history($schedule_id, $limit);

            wp_send_json_success([
                'history' => $history
            ]);

        } catch (Exception $e) {
            error_log('AHGMH Schedule History Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for sending test email
     */
    public function ajax_send_test_email() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get recipient email
            $recipient = isset($_POST['recipient']) ? sanitize_email($_POST['recipient']) : '';
            if (empty($recipient) || !is_email($recipient)) {
                throw new Exception(__('Gültige E-Mail-Adresse ist erforderlich', 'abschussplan-hgmh'));
            }

            // Load email service
            require_once AHGMH_PLUGIN_DIR . 'admin/services/class-email-service.php';
            $email_service = new AHGMH_Email_Service();

            // Send test email
            $result = $email_service->send_test_email($recipient);

            if ($result) {
                wp_send_json_success([
                    'message' => sprintf(
                        __('Test-E-Mail erfolgreich an %s gesendet', 'abschussplan-hgmh'),
                        $recipient
                    )
                ]);
            } else {
                throw new Exception(__('Fehler beim Senden der Test-E-Mail', 'abschussplan-hgmh'));
            }

        } catch (Exception $e) {
            error_log('AHGMH Test Email Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Get schedule configuration from POST request
     *
     * @return array Schedule configuration
     */
    private function get_config_from_request() {
        $config = array();

        // Required fields
        $config['name'] = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $config['report_type'] = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
        $config['frequency'] = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : '';

        // Recipients (comma-separated or array)
        if (isset($_POST['recipients'])) {
            if (is_array($_POST['recipients'])) {
                $config['recipients'] = array_map('sanitize_email', $_POST['recipients']);
            } else {
                // Split by comma and trim
                $recipients = explode(',', $_POST['recipients']);
                $config['recipients'] = array_map('trim', array_map('sanitize_email', $recipients));
            }
            // Remove empty values
            $config['recipients'] = array_filter($config['recipients']);
        } else {
            $config['recipients'] = array();
        }

        // Optional fields
        if (isset($_POST['enabled'])) {
            $config['enabled'] = filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($_POST['time'])) {
            $config['time'] = sanitize_text_field($_POST['time']);
        }

        if (isset($_POST['day_of_week'])) {
            $config['day_of_week'] = intval($_POST['day_of_week']);
        }

        if (isset($_POST['day_of_month'])) {
            $config['day_of_month'] = intval($_POST['day_of_month']);
        }

        // Filters
        $config['filters'] = array();
        if (isset($_POST['species'])) {
            $config['filters']['species'] = sanitize_text_field($_POST['species']);
        }
        if (isset($_POST['meldegruppe'])) {
            $config['filters']['meldegruppe'] = sanitize_text_field($_POST['meldegruppe']);
        }

        return $config;
    }

    /**
     * Fallback rendering if view is not available
     *
     * @param array $data Page data
     */
    private function render_fallback($data) {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Geplante Berichte', 'abschussplan-hgmh'); ?></h1>
            <div class="notice notice-info">
                <p><?php echo esc_html__('Schedule Settings View wird geladen...', 'abschussplan-hgmh'); ?></p>
            </div>
            <pre><?php print_r($data['schedules']); ?></pre>
        </div>
        <?php
    }
}
