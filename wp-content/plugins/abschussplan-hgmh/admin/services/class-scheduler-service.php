<?php
/**
 * Scheduler Service Class
 * Manages scheduled report jobs using WP Cron
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scheduler Service for managing scheduled report generation
 */
class AHGMH_Scheduler_Service {

    /**
     * Schedules option key
     */
    const SCHEDULES_OPTION_KEY = 'ahgmh_report_schedules';

    /**
     * Schedule history option key
     */
    const HISTORY_OPTION_KEY = 'ahgmh_schedule_history';

    /**
     * Maximum history items to keep
     */
    const MAX_HISTORY_ITEMS = 100;

    /**
     * Report Generator Service instance
     * @var AHGMH_Report_Generator_Service
     */
    private $report_generator;

    /**
     * Email Service instance
     * @var AHGMH_Email_Service
     */
    private $email_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->report_generator = new AHGMH_Report_Generator_Service();
        $this->email_service = new AHGMH_Email_Service();
    }

    /**
     * Create a new scheduled report
     *
     * @param array $config Schedule configuration
     * @return array Result with schedule_id or error
     */
    public function create_schedule($config) {
        try {
            // Validate configuration
            $validation = $this->validate_schedule_config($config);
            if (!$validation['valid']) {
                return array(
                    'success' => false,
                    'error' => $validation['error']
                );
            }

            // Generate unique schedule ID
            $schedule_id = 'ahgmh_schedule_' . uniqid();

            // Prepare schedule data
            $schedule = array(
                'id' => $schedule_id,
                'name' => sanitize_text_field($config['name']),
                'report_type' => sanitize_text_field($config['report_type']),
                'frequency' => sanitize_text_field($config['frequency']),
                'recipients' => array_map('sanitize_email', $config['recipients']),
                'filters' => isset($config['filters']) ? $config['filters'] : array(),
                'enabled' => isset($config['enabled']) ? (bool) $config['enabled'] : true,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'last_run' => null,
                'next_run' => null
            );

            // Add custom time settings if provided
            if (isset($config['time'])) {
                $schedule['time'] = sanitize_text_field($config['time']); // Format: HH:MM
            }

            if (isset($config['day_of_week'])) {
                $schedule['day_of_week'] = intval($config['day_of_week']); // 0-6 (Sunday-Saturday)
            }

            if (isset($config['day_of_month'])) {
                $schedule['day_of_month'] = intval($config['day_of_month']); // 1-28
            }

            // Save schedule
            $schedules = $this->get_all_schedules();
            $schedules[$schedule_id] = $schedule;
            update_option(self::SCHEDULES_OPTION_KEY, $schedules);

            // Register WP Cron hook if enabled
            if ($schedule['enabled']) {
                $this->register_cron_hook($schedule);
            }

            error_log('AHGMH Scheduler: Created schedule - ' . $schedule_id);

            return array(
                'success' => true,
                'schedule_id' => $schedule_id,
                'schedule' => $schedule
            );

        } catch (Exception $e) {
            error_log('AHGMH Scheduler: Error creating schedule - ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Update an existing schedule
     *
     * @param string $schedule_id Schedule ID
     * @param array $config Updated configuration
     * @return array Result
     */
    public function update_schedule($schedule_id, $config) {
        try {
            $schedules = $this->get_all_schedules();

            if (!isset($schedules[$schedule_id])) {
                return array(
                    'success' => false,
                    'error' => 'Schedule not found'
                );
            }

            // Get existing schedule
            $schedule = $schedules[$schedule_id];

            // Unregister old cron hook
            $this->unregister_cron_hook($schedule_id);

            // Update fields
            if (isset($config['name'])) {
                $schedule['name'] = sanitize_text_field($config['name']);
            }

            if (isset($config['report_type'])) {
                $schedule['report_type'] = sanitize_text_field($config['report_type']);
            }

            if (isset($config['frequency'])) {
                $schedule['frequency'] = sanitize_text_field($config['frequency']);
            }

            if (isset($config['recipients'])) {
                $schedule['recipients'] = array_map('sanitize_email', $config['recipients']);
            }

            if (isset($config['filters'])) {
                $schedule['filters'] = $config['filters'];
            }

            if (isset($config['enabled'])) {
                $schedule['enabled'] = (bool) $config['enabled'];
            }

            if (isset($config['time'])) {
                $schedule['time'] = sanitize_text_field($config['time']);
            }

            if (isset($config['day_of_week'])) {
                $schedule['day_of_week'] = intval($config['day_of_week']);
            }

            if (isset($config['day_of_month'])) {
                $schedule['day_of_month'] = intval($config['day_of_month']);
            }

            $schedule['updated_at'] = current_time('mysql');

            // Validate updated configuration
            $validation = $this->validate_schedule_config($schedule);
            if (!$validation['valid']) {
                // Re-register old hook
                if ($schedules[$schedule_id]['enabled']) {
                    $this->register_cron_hook($schedules[$schedule_id]);
                }
                return array(
                    'success' => false,
                    'error' => $validation['error']
                );
            }

            // Save updated schedule
            $schedules[$schedule_id] = $schedule;
            update_option(self::SCHEDULES_OPTION_KEY, $schedules);

            // Register new cron hook if enabled
            if ($schedule['enabled']) {
                $this->register_cron_hook($schedule);
            }

            error_log('AHGMH Scheduler: Updated schedule - ' . $schedule_id);

            return array(
                'success' => true,
                'schedule' => $schedule
            );

        } catch (Exception $e) {
            error_log('AHGMH Scheduler: Error updating schedule - ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Delete a schedule
     *
     * @param string $schedule_id Schedule ID
     * @return array Result
     */
    public function delete_schedule($schedule_id) {
        try {
            $schedules = $this->get_all_schedules();

            if (!isset($schedules[$schedule_id])) {
                return array(
                    'success' => false,
                    'error' => 'Schedule not found'
                );
            }

            // Unregister cron hook
            $this->unregister_cron_hook($schedule_id);

            // Remove schedule
            unset($schedules[$schedule_id]);
            update_option(self::SCHEDULES_OPTION_KEY, $schedules);

            error_log('AHGMH Scheduler: Deleted schedule - ' . $schedule_id);

            return array(
                'success' => true
            );

        } catch (Exception $e) {
            error_log('AHGMH Scheduler: Error deleting schedule - ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Enable a schedule
     *
     * @param string $schedule_id Schedule ID
     * @return array Result
     */
    public function enable_schedule($schedule_id) {
        return $this->update_schedule($schedule_id, array('enabled' => true));
    }

    /**
     * Disable a schedule
     *
     * @param string $schedule_id Schedule ID
     * @return array Result
     */
    public function disable_schedule($schedule_id) {
        return $this->update_schedule($schedule_id, array('enabled' => false));
    }

    /**
     * Get all schedules
     *
     * @return array Array of schedules
     */
    public function get_all_schedules() {
        $schedules = get_option(self::SCHEDULES_OPTION_KEY, array());
        if (!is_array($schedules)) {
            $schedules = array();
        }
        return $schedules;
    }

    /**
     * Get a specific schedule
     *
     * @param string $schedule_id Schedule ID
     * @return array|null Schedule data or null if not found
     */
    public function get_schedule($schedule_id) {
        $schedules = $this->get_all_schedules();
        return isset($schedules[$schedule_id]) ? $schedules[$schedule_id] : null;
    }

    /**
     * Execute a scheduled report
     *
     * @param string $schedule_id Schedule ID
     * @return array Result
     */
    public function execute_schedule($schedule_id) {
        try {
            $schedule = $this->get_schedule($schedule_id);

            if (!$schedule) {
                error_log('AHGMH Scheduler: Schedule not found - ' . $schedule_id);
                return array(
                    'success' => false,
                    'error' => 'Schedule not found'
                );
            }

            if (!$schedule['enabled']) {
                error_log('AHGMH Scheduler: Schedule is disabled - ' . $schedule_id);
                return array(
                    'success' => false,
                    'error' => 'Schedule is disabled'
                );
            }

            error_log('AHGMH Scheduler: Executing schedule - ' . $schedule_id);

            // Calculate date range based on report type and frequency
            $date_range = $this->calculate_date_range($schedule);

            // Generate report
            $report = $this->generate_report_for_schedule($schedule, $date_range);

            if (!$report || !isset($report['data'])) {
                throw new Exception('Failed to generate report');
            }

            // Send email to recipients
            $results = $this->send_scheduled_report($schedule, $report, $date_range);

            // Update schedule with last run time
            $this->update_schedule_run_time($schedule_id);

            // Add to history
            $this->add_to_history($schedule_id, $results);

            error_log('AHGMH Scheduler: Schedule executed successfully - ' . $schedule_id);

            return array(
                'success' => true,
                'results' => $results
            );

        } catch (Exception $e) {
            error_log('AHGMH Scheduler: Error executing schedule - ' . $e->getMessage());

            // Add failed execution to history
            $this->add_to_history($schedule_id, array(
                'success' => false,
                'error' => $e->getMessage()
            ));

            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Register WP Cron hook for a schedule
     *
     * @param array $schedule Schedule data
     * @return bool Success status
     */
    private function register_cron_hook($schedule) {
        $hook_name = 'ahgmh_scheduled_report_' . $schedule['id'];

        // Clear existing schedule if any
        $timestamp = wp_next_scheduled($hook_name, array($schedule['id']));
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook_name, array($schedule['id']));
        }

        // Calculate next run timestamp
        $next_run_timestamp = $this->calculate_next_run_timestamp($schedule);

        // Get recurrence
        $recurrence = $this->get_wp_cron_recurrence($schedule['frequency']);

        // Schedule event
        $result = wp_schedule_event($next_run_timestamp, $recurrence, $hook_name, array($schedule['id']));

        // Update next_run in schedule
        $schedules = $this->get_all_schedules();
        if (isset($schedules[$schedule['id']])) {
            $schedules[$schedule['id']]['next_run'] = date('Y-m-d H:i:s', $next_run_timestamp);
            update_option(self::SCHEDULES_OPTION_KEY, $schedules);
        }

        error_log('AHGMH Scheduler: Registered cron hook - ' . $hook_name . ' at ' . date('Y-m-d H:i:s', $next_run_timestamp));

        return $result !== false;
    }

    /**
     * Unregister WP Cron hook for a schedule
     *
     * @param string $schedule_id Schedule ID
     * @return bool Success status
     */
    private function unregister_cron_hook($schedule_id) {
        $hook_name = 'ahgmh_scheduled_report_' . $schedule_id;

        $timestamp = wp_next_scheduled($hook_name, array($schedule_id));
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook_name, array($schedule_id));
            error_log('AHGMH Scheduler: Unregistered cron hook - ' . $hook_name);
            return true;
        }

        return false;
    }

    /**
     * Calculate next run timestamp based on schedule configuration
     *
     * @param array $schedule Schedule data
     * @return int Unix timestamp
     */
    private function calculate_next_run_timestamp($schedule) {
        $current_time = current_time('timestamp');
        $frequency = $schedule['frequency'];

        // Parse time if provided (format: HH:MM)
        $hour = 0;
        $minute = 0;
        if (isset($schedule['time']) && !empty($schedule['time'])) {
            $time_parts = explode(':', $schedule['time']);
            $hour = isset($time_parts[0]) ? intval($time_parts[0]) : 0;
            $minute = isset($time_parts[1]) ? intval($time_parts[1]) : 0;
        }

        switch ($frequency) {
            case 'daily':
                // Run daily at specified time
                $next_run = strtotime('today ' . sprintf('%02d:%02d:00', $hour, $minute));
                if ($next_run <= $current_time) {
                    $next_run = strtotime('tomorrow ' . sprintf('%02d:%02d:00', $hour, $minute));
                }
                return $next_run;

            case 'weekly':
                // Run weekly on specified day at specified time
                $day_of_week = isset($schedule['day_of_week']) ? intval($schedule['day_of_week']) : 1; // Default Monday
                $day_names = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
                $day_name = $day_names[$day_of_week];

                $next_run = strtotime('next ' . $day_name . ' ' . sprintf('%02d:%02d:00', $hour, $minute));
                // If today is the target day and time hasn't passed, use today
                $today_day = intval(date('w', $current_time));
                if ($today_day === $day_of_week) {
                    $today_time = strtotime('today ' . sprintf('%02d:%02d:00', $hour, $minute));
                    if ($today_time > $current_time) {
                        $next_run = $today_time;
                    }
                }
                return $next_run;

            case 'monthly':
                // Run monthly on specified day at specified time
                $day_of_month = isset($schedule['day_of_month']) ? intval($schedule['day_of_month']) : 1;
                $day_of_month = max(1, min(28, $day_of_month)); // Limit to 1-28 to ensure valid in all months

                // Try current month first
                $next_run = strtotime(date('Y-m-' . sprintf('%02d', $day_of_month) . ' ' . sprintf('%02d:%02d:00', $hour, $minute)));

                // If time has passed this month, use next month
                if ($next_run <= $current_time) {
                    $next_month = strtotime('first day of next month');
                    $next_run = strtotime(date('Y-m-' . sprintf('%02d', $day_of_month) . ' ' . sprintf('%02d:%02d:00', $hour, $minute), $next_month));
                }
                return $next_run;

            default:
                // Default to daily
                return strtotime('+1 day', $current_time);
        }
    }

    /**
     * Get WP Cron recurrence string for frequency
     *
     * @param string $frequency Frequency (daily, weekly, monthly)
     * @return string WP Cron recurrence
     */
    private function get_wp_cron_recurrence($frequency) {
        switch ($frequency) {
            case 'daily':
                return 'daily';
            case 'weekly':
                return 'weekly';
            case 'monthly':
                return 'monthly';
            default:
                return 'daily';
        }
    }

    /**
     * Calculate date range for report based on schedule configuration
     *
     * @param array $schedule Schedule data
     * @return array Date range with start and end dates
     */
    private function calculate_date_range($schedule) {
        $frequency = $schedule['frequency'];
        $current_date = current_time('Y-m-d');

        switch ($frequency) {
            case 'daily':
                // Previous day
                return array(
                    'start' => date('Y-m-d', strtotime('-1 day')),
                    'end' => date('Y-m-d', strtotime('-1 day'))
                );

            case 'weekly':
                // Previous 7 days
                return array(
                    'start' => date('Y-m-d', strtotime('-7 days')),
                    'end' => date('Y-m-d', strtotime('-1 day'))
                );

            case 'monthly':
                // Previous month (full month)
                $first_day_last_month = date('Y-m-01', strtotime('first day of last month'));
                $last_day_last_month = date('Y-m-t', strtotime('last day of last month'));
                return array(
                    'start' => $first_day_last_month,
                    'end' => $last_day_last_month
                );

            default:
                // Default to previous day
                return array(
                    'start' => date('Y-m-d', strtotime('-1 day')),
                    'end' => date('Y-m-d', strtotime('-1 day'))
                );
        }
    }

    /**
     * Generate report for a schedule
     *
     * @param array $schedule Schedule data
     * @param array $date_range Date range
     * @return array|false Report data or false on failure
     */
    private function generate_report_for_schedule($schedule, $date_range) {
        $report_type = $schedule['report_type'];
        $filters = isset($schedule['filters']) ? $schedule['filters'] : array();

        try {
            switch ($report_type) {
                case 'seasonal':
                    return $this->report_generator->generate_seasonal_report(
                        $date_range['start'],
                        $date_range['end'],
                        $filters,
                        'pdf' // Generate PDF-ready HTML
                    );

                case 'date_range':
                    return $this->report_generator->generate_date_range_report(
                        $date_range['start'],
                        $date_range['end'],
                        $filters,
                        'pdf'
                    );

                case 'compliance':
                    return $this->report_generator->generate_compliance_report(
                        $filters,
                        'pdf'
                    );

                case 'trend':
                    return $this->report_generator->generate_trend_report(
                        $date_range['start'],
                        $date_range['end'],
                        $filters,
                        'pdf'
                    );

                default:
                    error_log('AHGMH Scheduler: Unknown report type - ' . $report_type);
                    return false;
            }
        } catch (Exception $e) {
            error_log('AHGMH Scheduler: Error generating report - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send scheduled report via email
     *
     * @param array $schedule Schedule data
     * @param array $report Report data
     * @param array $date_range Date range
     * @return array Send results
     */
    private function send_scheduled_report($schedule, $report, $date_range) {
        $recipients = $schedule['recipients'];
        $schedule_name = $schedule['name'];
        $report_title = isset($report['title']) ? $report['title'] : 'Report';

        // Prepare email subject
        $subject = sprintf(
            '[Abschussplan] %s - %s',
            $schedule_name,
            $report_title
        );

        // Prepare template variables
        $template_vars = array(
            'schedule_name' => $schedule_name,
            'report_title' => $report_title,
            'period_start' => date('d.m.Y', strtotime($date_range['start'])),
            'period_end' => date('d.m.Y', strtotime($date_range['end'])),
            'report_type' => $schedule['report_type'],
            'frequency' => $schedule['frequency'],
            'generated_at' => current_time('mysql')
        );

        // Add report summary if available
        if (isset($report['data']['summary'])) {
            $template_vars['report_summary'] = $report['data']['summary'];
        }

        // Load email template
        $body = $this->email_service->send_with_template(
            $recipients[0], // Dummy recipient for template loading
            $subject,
            'report-email',
            $template_vars,
            array()
        );

        // If template loading fails, use simple body
        if ($body === false) {
            $body = sprintf(
                '<p>Anbei finden Sie den automatisch generierten Bericht "%s" für den Zeitraum %s bis %s.</p>',
                $schedule_name,
                $template_vars['period_start'],
                $template_vars['period_end']
            );
        }

        // Prepare email options with PDF attachment
        $options = array(
            'pdf_html' => isset($report['pdf_html']) ? $report['pdf_html'] : null,
            'pdf_filename' => $this->generate_pdf_filename($schedule, $date_range)
        );

        // Send to all recipients
        return $this->email_service->send_to_multiple($recipients, $subject, $body, $options);
    }

    /**
     * Generate PDF filename for scheduled report
     *
     * @param array $schedule Schedule data
     * @param array $date_range Date range
     * @return string Filename
     */
    private function generate_pdf_filename($schedule, $date_range) {
        $name = sanitize_file_name($schedule['name']);
        $date_str = date('Ymd', strtotime($date_range['start']));
        return sprintf('%s-%s', $name, $date_str);
    }

    /**
     * Update schedule last run and next run times
     *
     * @param string $schedule_id Schedule ID
     * @return bool Success status
     */
    private function update_schedule_run_time($schedule_id) {
        $schedules = $this->get_all_schedules();

        if (!isset($schedules[$schedule_id])) {
            return false;
        }

        $schedule = $schedules[$schedule_id];
        $schedule['last_run'] = current_time('mysql');

        // Calculate next run
        $next_run_timestamp = $this->calculate_next_run_timestamp($schedule);
        $schedule['next_run'] = date('Y-m-d H:i:s', $next_run_timestamp);

        $schedules[$schedule_id] = $schedule;
        return update_option(self::SCHEDULES_OPTION_KEY, $schedules);
    }

    /**
     * Add execution to history
     *
     * @param string $schedule_id Schedule ID
     * @param array $results Execution results
     * @return bool Success status
     */
    private function add_to_history($schedule_id, $results) {
        $history = get_option(self::HISTORY_OPTION_KEY, array());
        if (!is_array($history)) {
            $history = array();
        }

        $history[] = array(
            'schedule_id' => $schedule_id,
            'executed_at' => current_time('mysql'),
            'results' => $results
        );

        // Keep only last N items
        if (count($history) > self::MAX_HISTORY_ITEMS) {
            $history = array_slice($history, -self::MAX_HISTORY_ITEMS);
        }

        return update_option(self::HISTORY_OPTION_KEY, $history);
    }

    /**
     * Get schedule history
     *
     * @param string $schedule_id Optional schedule ID to filter by
     * @param int $limit Limit number of results
     * @return array History items
     */
    public function get_history($schedule_id = null, $limit = 50) {
        $history = get_option(self::HISTORY_OPTION_KEY, array());
        if (!is_array($history)) {
            $history = array();
        }

        // Filter by schedule ID if provided
        if ($schedule_id) {
            $history = array_filter($history, function($item) use ($schedule_id) {
                return isset($item['schedule_id']) && $item['schedule_id'] === $schedule_id;
            });
        }

        // Sort by executed_at descending (most recent first)
        usort($history, function($a, $b) {
            return strcmp($b['executed_at'], $a['executed_at']);
        });

        // Limit results
        if ($limit > 0) {
            $history = array_slice($history, 0, $limit);
        }

        return array_values($history);
    }

    /**
     * Clear schedule history
     *
     * @param string $schedule_id Optional schedule ID to clear specific schedule history
     * @return bool Success status
     */
    public function clear_history($schedule_id = null) {
        if ($schedule_id) {
            $history = get_option(self::HISTORY_OPTION_KEY, array());
            if (!is_array($history)) {
                return true;
            }

            $history = array_filter($history, function($item) use ($schedule_id) {
                return !isset($item['schedule_id']) || $item['schedule_id'] !== $schedule_id;
            });

            return update_option(self::HISTORY_OPTION_KEY, array_values($history));
        } else {
            return delete_option(self::HISTORY_OPTION_KEY);
        }
    }

    /**
     * Validate schedule configuration
     *
     * @param array $config Schedule configuration
     * @return array Validation result
     */
    private function validate_schedule_config($config) {
        // Required fields
        $required_fields = array('name', 'report_type', 'frequency', 'recipients');
        foreach ($required_fields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return array(
                    'valid' => false,
                    'error' => sprintf('Missing required field: %s', $field)
                );
            }
        }

        // Validate report type
        $valid_report_types = array('seasonal', 'date_range', 'compliance', 'trend');
        if (!in_array($config['report_type'], $valid_report_types)) {
            return array(
                'valid' => false,
                'error' => 'Invalid report type'
            );
        }

        // Validate frequency
        $valid_frequencies = array('daily', 'weekly', 'monthly');
        if (!in_array($config['frequency'], $valid_frequencies)) {
            return array(
                'valid' => false,
                'error' => 'Invalid frequency'
            );
        }

        // Validate recipients
        if (!is_array($config['recipients']) || empty($config['recipients'])) {
            return array(
                'valid' => false,
                'error' => 'Recipients must be a non-empty array'
            );
        }

        foreach ($config['recipients'] as $recipient) {
            if (!is_email($recipient)) {
                return array(
                    'valid' => false,
                    'error' => sprintf('Invalid email address: %s', $recipient)
                );
            }
        }

        // Validate time format if provided
        if (isset($config['time']) && !empty($config['time'])) {
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $config['time'])) {
                return array(
                    'valid' => false,
                    'error' => 'Invalid time format (expected HH:MM)'
                );
            }
        }

        // Validate day_of_week if provided
        if (isset($config['day_of_week'])) {
            $day = intval($config['day_of_week']);
            if ($day < 0 || $day > 6) {
                return array(
                    'valid' => false,
                    'error' => 'Invalid day_of_week (must be 0-6)'
                );
            }
        }

        // Validate day_of_month if provided
        if (isset($config['day_of_month'])) {
            $day = intval($config['day_of_month']);
            if ($day < 1 || $day > 28) {
                return array(
                    'valid' => false,
                    'error' => 'Invalid day_of_month (must be 1-28)'
                );
            }
        }

        return array('valid' => true);
    }

    /**
     * Get schedule statistics
     *
     * @return array Statistics
     */
    public function get_statistics() {
        $schedules = $this->get_all_schedules();
        $history = get_option(self::HISTORY_OPTION_KEY, array());

        $stats = array(
            'total_schedules' => count($schedules),
            'enabled_schedules' => 0,
            'disabled_schedules' => 0,
            'total_executions' => count($history),
            'successful_executions' => 0,
            'failed_executions' => 0,
            'by_frequency' => array(
                'daily' => 0,
                'weekly' => 0,
                'monthly' => 0
            ),
            'by_report_type' => array(
                'seasonal' => 0,
                'date_range' => 0,
                'compliance' => 0,
                'trend' => 0
            )
        );

        // Count by status and type
        foreach ($schedules as $schedule) {
            if (isset($schedule['enabled']) && $schedule['enabled']) {
                $stats['enabled_schedules']++;
            } else {
                $stats['disabled_schedules']++;
            }

            if (isset($schedule['frequency'])) {
                $freq = $schedule['frequency'];
                if (isset($stats['by_frequency'][$freq])) {
                    $stats['by_frequency'][$freq]++;
                }
            }

            if (isset($schedule['report_type'])) {
                $type = $schedule['report_type'];
                if (isset($stats['by_report_type'][$type])) {
                    $stats['by_report_type'][$type]++;
                }
            }
        }

        // Count execution results
        foreach ($history as $item) {
            if (isset($item['results']['success']) && $item['results']['success']) {
                $stats['successful_executions']++;
            } else {
                $stats['failed_executions']++;
            }
        }

        return $stats;
    }

    /**
     * Test schedule configuration without saving
     *
     * @param array $config Schedule configuration
     * @return array Test results
     */
    public function test_schedule($config) {
        // Validate configuration
        $validation = $this->validate_schedule_config($config);
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'error' => $validation['error']
            );
        }

        // Calculate what the next run would be
        $test_schedule = array(
            'frequency' => $config['frequency'],
            'time' => isset($config['time']) ? $config['time'] : '00:00',
            'day_of_week' => isset($config['day_of_week']) ? $config['day_of_week'] : 1,
            'day_of_month' => isset($config['day_of_month']) ? $config['day_of_month'] : 1
        );

        $next_run_timestamp = $this->calculate_next_run_timestamp($test_schedule);
        $date_range = $this->calculate_date_range($test_schedule);

        return array(
            'success' => true,
            'next_run' => date('Y-m-d H:i:s', $next_run_timestamp),
            'date_range' => $date_range,
            'recipients_count' => count($config['recipients']),
            'report_type' => $config['report_type']
        );
    }
}
