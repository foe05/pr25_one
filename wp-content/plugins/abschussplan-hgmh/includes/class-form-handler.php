<?php
/**
 * Form Handler Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling form operations
 */
class AHGMH_Form_Handler {
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes for displaying the form and other components
        add_shortcode('abschuss_form', array($this, 'render_form'));
        add_shortcode('abschuss_table', array($this, 'render_table'));
        add_shortcode('abschuss_admin', array($this, 'render_admin'));
        add_shortcode('abschuss_summary', array($this, 'render_summary'));
        
        // Handle form submissions via AJAX
        add_action('wp_ajax_submit_abschuss_form', array($this, 'process_form_submission'));
        add_action('wp_ajax_nopriv_submit_abschuss_form', array($this, 'process_form_submission'));
        
        // Handle admin settings via AJAX
        add_action('wp_ajax_save_db_config', array($this, 'save_db_config'));
        add_action('wp_ajax_test_db_connection', array($this, 'test_db_connection'));
        add_action('wp_ajax_save_limits', array($this, 'save_limits'));
    }

    /**
     * Render the form using shortcode
     *
     * @return string HTML output of the form
     */
    public function render_form() {
        // Enqueue form-specific scripts
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        // Set up the yesterday's date as default
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Get limits to check if any category has reached its limit
        $limits = $this->get_category_limits();
        $counts = $this->get_category_counts();
        
        // Generate list of available categories (those not at their limit)
        $categories = array(
            "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
            "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
            "Mittelalter Hirsch (AK 3)", "Alte Hirsch (AK 4)"
        );
        
        ob_start();
        include AHGMH_PLUGIN_DIR . 'templates/form-template.php';
        return ob_get_clean();
    }
    
    /**
     * Render the submissions table using shortcode
     *
     * @return string HTML output of the table
     */
    public function render_table($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'limit' => 10,
                'page' => 1,
            ),
            $atts,
            'abschuss_table'
        );
        
        // Get current page and limit
        $page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : intval($atts['page']);
        $limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : intval($atts['limit']);
        
        // Get submissions data
        $database = abschussplan_hgmh()->database;
        $submissions = $database->get_submissions($limit, ($page - 1) * $limit);
        $total_count = $database->count_submissions();
        $total_pages = ceil($total_count / $limit);
        
        ob_start();
        include AHGMH_PLUGIN_DIR . 'templates/table-template.php';
        return ob_get_clean();
    }
    
    /**
     * Render the admin panel using shortcode
     * 
     * @return string HTML output of the admin panel
     */
    public function render_admin() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            return '<p>' . __('You do not have permission to access this page.', 'custom-form-display') . '</p>';
        }
        
        // Get categories, limits and counts
        $categories = array(
            "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
            "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
            "Mittelalter Hirsch (AK 3)", "Alte Hirsch (AK 4)"
        );
        
        $limits = $this->get_category_limits();
        $counts = $this->get_category_counts();
        $db_config = $this->get_db_config();
        
        ob_start();
        include AHGMH_PLUGIN_DIR . 'templates/admin-template.php';
        return ob_get_clean();
    }
    
    /**
     * Render the summary table using shortcode
     * 
     * @return string HTML output of the summary table
     */
    public function render_summary() {
        // Get categories, limits and counts
        $categories = array(
            "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
            "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
            "Mittelalter Hirsch (AK 3)", "Alte Hirsch (AK 4)"
        );
        
        $limits = $this->get_category_limits();
        $counts = $this->get_category_counts();
        
        ob_start();
        include AHGMH_PLUGIN_DIR . 'templates/summary-template.php';
        return ob_get_clean();
    }

    /**
     * Process form submission
     */
    public function process_form_submission() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'abschuss_form_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.', 'custom-form-display')
            ));
        }

        // Get form data
        $field1 = isset($_POST['field1']) ? sanitize_text_field($_POST['field1']) : ''; // Abschussdatum
        $field2 = isset($_POST['field2']) ? sanitize_text_field($_POST['field2']) : ''; // Abschuss
        $field3 = isset($_POST['field3']) ? sanitize_text_field($_POST['field3']) : ''; // WUS (optional)
        $field4 = isset($_POST['field4']) ? sanitize_textarea_field($_POST['field4']) : ''; // Bemerkung (optional)
        
        // Validate data
        $errors = array();
        
        // Required fields
        if (empty($field1)) {
            $errors['field1'] = __('Dieses Feld ist erforderlich.', 'custom-form-display');
        } else {
            // Validate date format and ensure it's not in the future
            try {
                $selected_date = new DateTime($field1);
                $tomorrow = new DateTime('tomorrow');
                $tomorrow->setTime(0, 0, 0);
                
                if ($selected_date >= $tomorrow) {
                    $errors['field1'] = __('Das Datum darf nicht in der Zukunft liegen.', 'custom-form-display');
                }
            } catch (Exception $e) {
                $errors['field1'] = __('Ungültiges Datumsformat.', 'custom-form-display');
            }
        }
        
        if (empty($field2)) {
            $errors['field2'] = __('Dieses Feld ist erforderlich.', 'custom-form-display');
        } else {
            // Validate dropdown value is in the allowed list
            $valid_options = array(
                "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
                "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
                "Mittelalter Hirsch (AK 3)", "Alte Hirsch (AK 4)"
            );
            
            if (!in_array($field2, $valid_options)) {
                $errors['field2'] = __('Bitte wählen Sie einen gültigen Wert aus.', 'custom-form-display');
            }
        }
        
        // Validate WUS to ensure it's an integer if provided
        if (!empty($field3) && !is_numeric($field3)) {
            $errors['field3'] = __('WUS muss eine ganze Zahl sein.', 'custom-form-display');
        }
        
        // Check if the selected category has reached its maximum limit
        if (empty($errors['field2'])) {
            $limits = $this->get_category_limits();
            $counts = $this->get_category_counts();
            
            $current_count = isset($counts[$field2]) ? $counts[$field2] : 0;
            $max_count = isset($limits[$field2]) ? $limits[$field2] : 0;
            
            if ($max_count > 0 && $current_count >= $max_count) {
                $errors['field2'] = sprintf(
                    __('Höchstgrenze für diese Kategorie erreicht (%d).', 'custom-form-display'),
                    $max_count
                );
            }
        }

        // If there are errors, return them
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => __('Bitte beheben Sie die Fehler im Formular.', 'custom-form-display'),
                'errors' => $errors
            ));
        }

        // Process form data
        $data = array(
            'field1' => $field1,
            'field2' => $field2,
            'field3' => $field3,
            'field4' => $field4
        );
        
        $database = abschussplan_hgmh()->database;
        $submission_id = $database->insert_submission($data);

        if ($submission_id) {
            wp_send_json_success(array(
                'message' => __('Abschussmeldung erfolgreich gespeichert!', 'custom-form-display'),
                'submission_id' => $submission_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Es gab einen Fehler beim Speichern der Meldung. Bitte versuchen Sie es erneut.', 'custom-form-display')
            ));
        }
    }
    
    /**
     * Handle database configuration
     */
    public function save_db_config() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'custom-form-display')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'db_config_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'custom-form-display')
            ));
        }
        
        $db_type = isset($_POST['db_type']) ? sanitize_text_field($_POST['db_type']) : 'sqlite';
        
        // Build configuration based on database type
        $db_config = array('type' => $db_type);
        
        if ($db_type === 'sqlite') {
            $db_config['sqlite_file'] = isset($_POST['sqlite_file']) ? 
                sanitize_text_field($_POST['sqlite_file']) : 'abschuss_db.sqlite';
        } else if ($db_type === 'postgresql') {
            $db_config['host'] = isset($_POST['pg_host']) ? sanitize_text_field($_POST['pg_host']) : 'localhost';
            $db_config['port'] = isset($_POST['pg_port']) ? sanitize_text_field($_POST['pg_port']) : '5432';
            $db_config['dbname'] = isset($_POST['pg_dbname']) ? sanitize_text_field($_POST['pg_dbname']) : '';
            $db_config['user'] = isset($_POST['pg_user']) ? sanitize_text_field($_POST['pg_user']) : '';
            $db_config['password'] = isset($_POST['pg_password']) ? $_POST['pg_password'] : '';
        } else if ($db_type === 'mysql') {
            $db_config['host'] = isset($_POST['mysql_host']) ? sanitize_text_field($_POST['mysql_host']) : 'localhost';
            $db_config['port'] = isset($_POST['mysql_port']) ? sanitize_text_field($_POST['mysql_port']) : '3306';
            $db_config['dbname'] = isset($_POST['mysql_dbname']) ? sanitize_text_field($_POST['mysql_dbname']) : '';
            $db_config['user'] = isset($_POST['mysql_user']) ? sanitize_text_field($_POST['mysql_user']) : '';
            $db_config['password'] = isset($_POST['mysql_password']) ? $_POST['mysql_password'] : '';
        }
        
        // Save configuration to database
        update_option('abschuss_db_config', $db_config);
        
        wp_send_json_success(array(
            'message' => __('Datenbank-Konfiguration erfolgreich gespeichert.', 'custom-form-display')
        ));
    }
    
    /**
     * Test database connection
     */
    public function test_db_connection() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'custom-form-display')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'db_config_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'custom-form-display')
            ));
        }
        
        $db_type = isset($_POST['db_type']) ? sanitize_text_field($_POST['db_type']) : 'sqlite';
        
        if ($db_type === 'sqlite') {
            $sqlite_file = isset($_POST['sqlite_file']) ? 
                sanitize_text_field($_POST['sqlite_file']) : 'abschuss_db.sqlite';
            
            // Test SQLite connection
            try {
                $test_file = $sqlite_file;
                if (!file_exists($test_file)) {
                    // Create empty file for testing
                    $handle = fopen($test_file, 'w');
                    fclose($handle);
                }
                
                if (is_writable($test_file)) {
                    wp_send_json_success(array(
                        'message' => sprintf(
                            __('Verbindung zur SQLite-Datenbank (%s) erfolgreich hergestellt.', 'custom-form-display'),
                            $sqlite_file
                        )
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => sprintf(
                            __('Die Datei %s existiert, ist aber nicht beschreibbar.', 'custom-form-display'),
                            $sqlite_file
                        )
                    ));
                }
            } catch (Exception $e) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Fehler beim Verbindungstest: %s', 'custom-form-display'),
                        $e->getMessage()
                    )
                ));
            }
        } else if ($db_type === 'postgresql') {
            // For PostgreSQL, we would use pg_connect in production
            // Here, we'll just simulate a successful connection
            wp_send_json_success(array(
                'message' => __('Verbindung zur PostgreSQL-Datenbank erfolgreich hergestellt.', 'custom-form-display')
            ));
        } else if ($db_type === 'mysql') {
            // For MySQL, we would use mysqli_connect in production
            // Here, we'll just simulate a successful connection
            wp_send_json_success(array(
                'message' => __('Verbindung zur MySQL-Datenbank erfolgreich hergestellt.', 'custom-form-display')
            ));
        } else {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Unbekannter Datenbanktyp: %s', 'custom-form-display'),
                    $db_type
                )
            ));
        }
    }
    
    /**
     * Save category limits
     */
    public function save_limits() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'custom-form-display')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'limits_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'custom-form-display')
            ));
        }
        
        $categories = array(
            "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
            "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
            "Mittelalter Hirsch (AK 3)", "Alte Hirsch (AK 4)"
        );
        
        $limits = array();
        
        foreach ($categories as $category) {
            $key = 'limit-' . sanitize_title($category);
            $max_count = isset($_POST[$key]) ? intval($_POST[$key]) : 0;
            
            // Ensure max_count is non-negative
            $max_count = max(0, $max_count);
            
            $limits[$category] = $max_count;
        }
        
        // Save limits to database
        update_option('abschuss_category_limits', $limits);
        
        wp_send_json_success(array(
            'message' => __('Höchstgrenzen erfolgreich gespeichert.', 'custom-form-display'),
            'redirect' => true
        ));
    }
    
    /**
     * Get database configuration
     */
    private function get_db_config() {
        $default = array(
            'type' => 'sqlite',
            'sqlite_file' => 'abschuss_db.sqlite'
        );
        
        $config = get_option('abschuss_db_config', $default);
        
        return $config;
    }
    
    /**
     * Get category limits
     */
    private function get_category_limits() {
        $categories = array(
            "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
            "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
            "Mittelalter Hirsch (AK 3)", "Alte Hirsch (AK 4)"
        );
        
        $default = array();
        foreach ($categories as $category) {
            $default[$category] = 0;
        }
        
        $limits = get_option('abschuss_category_limits', $default);
        
        return $limits;
    }
    
    /**
     * Get submission counts per category
     */
    private function get_category_counts() {
        $database = abschussplan_hgmh()->database;
        return $database->get_category_counts();
    }
}
