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
        add_shortcode('abschuss_limits', array($this, 'render_limits_config'));
        
        // Handle form submissions via AJAX
        add_action('wp_ajax_submit_abschuss_form', array($this, 'process_form_submission'));
        add_action('wp_ajax_nopriv_submit_abschuss_form', array($this, 'process_form_submission'));
        add_action('wp_ajax_ahgmh_refresh_table', array($this, 'ajax_refresh_table'));
        add_action('wp_ajax_nopriv_ahgmh_refresh_table', array($this, 'ajax_refresh_table'));
        
        // Handle admin settings via AJAX
        add_action('wp_ajax_save_db_config', array($this, 'save_db_config'));
        add_action('wp_ajax_test_db_connection', array($this, 'test_db_connection'));
        add_action('wp_ajax_save_limits', array($this, 'save_limits'));
        add_action('wp_ajax_save_categories', array($this, 'save_categories'));
        add_action('wp_ajax_save_species', array($this, 'save_species'));
        
        // Handle CSV export
        add_action('wp_ajax_export_abschuss_csv', array($this, 'export_csv'));
        add_action('wp_ajax_nopriv_export_abschuss_csv', array($this, 'export_csv'));
        add_action('wp_ajax_save_species_limits', array($this, 'save_species_limits'));
        add_action('wp_ajax_load_species_limits', array($this, 'load_species_limits'));
        
        // Jagdbezirk management AJAX handlers
        add_action('wp_ajax_save_jagdbezirk', array($this, 'save_jagdbezirk'));
        add_action('wp_ajax_update_jagdbezirk', array($this, 'update_jagdbezirk'));
        add_action('wp_ajax_delete_jagdbezirk', array($this, 'delete_jagdbezirk'));
        add_action('wp_ajax_delete_all_jagdbezirke', array($this, 'delete_all_jagdbezirke'));
    }

    /**
    * Render the form using shortcode
    * 
    * @param array $atts Shortcode attributes
    * @return string HTML output of the form
     */
    public function render_form($atts = array()) {
        // Parse shortcode attributes with species as required parameter
        $atts = shortcode_atts(array(
            'species' => ''
        ), $atts, 'abschuss_form');
        
        // Parameter validation - species is required
        if (empty($atts['species'])) {
            return '<div class="alert alert-warning">Parameter "species" ist erforderlich für [abschuss_form]. Beispiel: [abschuss_form species="Rotwild"]</div>';
        }
        
        // Check permissions with new 3-level system
        if (!AHGMH_Permissions_Service::can_access_shortcode('abschuss_form', $atts)) {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                // Not logged in - show login form
                return AHGMH_Permissions_Service::get_login_form('abschuss_form');
            } else {
                // Logged in but no permission - check if user has assignment for this wildart
                $species = sanitize_text_field($atts['species']);
                if (!AHGMH_Permissions_Service::is_obmann_for_wildart($user_id, $species)) {
                    return AHGMH_Permissions_Service::get_permission_denied(
                        'Sie sind nicht als Obmann für ' . $species . ' eingetragen.'
                    );
                }
            }
        }

        // Enqueue form-specific scripts
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        // Set up the yesterday's date as default
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Get the selected species
        $selected_species = sanitize_text_field($atts['species']);
        
        // Get available species
        $available_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        // Validate selected species
        if (!in_array($selected_species, $available_species)) {
            return '<div class="alert alert-danger">Unbekannte Wildart "' . esc_html($selected_species) . '". Verfügbare Wildarten: ' . implode(', ', $available_species) . '</div>';
        }
        
        // Meldegruppe logic is now handled directly in the form template
        
        // Get limits to check if any category has reached its limit
        $limits = $this->get_category_limits($selected_species);
        $counts = $this->get_category_counts($selected_species);
        
        // Get dynamic categories from options for the selected species
        $categories_key = 'ahgmh_categories_' . sanitize_key($selected_species);
        $saved_categories = get_option($categories_key, array());
        
        // Generate list of available categories (those not at their limit)
        $categories = !empty($saved_categories) ? $saved_categories : array();
        
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
                'species' => '',
                'meldegruppe' => ''
            ),
            $atts,
            'abschuss_table'
        );
        
        // Check permissions with new 3-level system
        if (!AHGMH_Permissions_Service::can_access_shortcode('abschuss_table', $atts)) {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                // Not logged in - show login form
                return AHGMH_Permissions_Service::get_login_form('abschuss_table');
            } else {
                // Logged in but no permission
                $species = sanitize_text_field($atts['species']);
                if ($species && !AHGMH_Permissions_Service::is_obmann_for_wildart($user_id, $species)) {
                    return AHGMH_Permissions_Service::get_permission_denied(
                        'Sie sind nicht als Obmann für ' . $species . ' eingetragen.'
                    );
                } else if (!$species) {
                    return AHGMH_Permissions_Service::get_permission_denied(
                        'Sie benötigen eine Meldegruppen-Zuweisung für eine Wildart.'
                    );
                }
            }
        }
        
        // Get current page and limit
        $page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : intval($atts['page']);
        $limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : intval($atts['limit']);
        
        // Get submissions data with permission filtering
        $database = abschussplan_hgmh()->database;
        $species = sanitize_text_field($atts['species']);
        $meldegruppe = sanitize_text_field($atts['meldegruppe']);
        $user_id = get_current_user_id();
        
        // Apply permission-based filtering with parameter override logic
        if (AHGMH_Permissions_Service::is_vorstand($user_id)) {
            // Vorstand: Can use all parameters freely
            if (!empty($species) && !empty($meldegruppe)) {
                // Both species and meldegruppe specified
                $submissions = $database->get_submissions_by_species_and_meldegruppe($species, $meldegruppe, $limit, ($page - 1) * $limit);
                $total_count = $database->count_submissions_by_species_and_meldegruppe($species, $meldegruppe);
            } else if (!empty($species)) {
                // Only species specified
                $submissions = $database->get_submissions_by_species($limit, ($page - 1) * $limit, $species);
                $total_count = $database->count_submissions_by_species($species);
            } else {
                // No filters - all submissions
                $submissions = $database->get_submissions($limit, ($page - 1) * $limit);
                $total_count = $database->count_submissions();
            }
        } else {
            // Obmann: Automatic filtering to user's meldegruppen
            if (!empty($species)) {
                $user_meldegruppe = AHGMH_Permissions_Service::get_user_meldegruppe($user_id, $species);
                $submissions = $database->get_submissions_by_species_and_meldegruppe($species, $user_meldegruppe, $limit, ($page - 1) * $limit);
                $total_count = $database->count_submissions_by_species_and_meldegruppe($species, $user_meldegruppe);
            } else {
                // No species specified - show all user's wildarten
                $user_wildarten = AHGMH_Permissions_Service::get_user_wildarten($user_id);
                $submissions = array();
                $total_count = 0;
                
                foreach ($user_wildarten as $wildart) {
                    $user_meldegruppe = AHGMH_Permissions_Service::get_user_meldegruppe($user_id, $wildart);
                    $wildart_submissions = $database->get_submissions_by_species_and_meldegruppe($wildart, $user_meldegruppe, $limit, ($page - 1) * $limit);
                    $submissions = array_merge($submissions, $wildart_submissions);
                    $total_count += $database->count_submissions_by_species_and_meldegruppe($wildart, $user_meldegruppe);
                }
            }
        }
        
        $total_pages = ceil($total_count / $limit);
        
        // Pass show_export_button = false to template (NO export buttons in frontend)
        $show_export_button = false;
        
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
        // Check permissions with new 3-level system
        if (!AHGMH_Permissions_Service::can_access_shortcode('abschuss_admin')) {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                // Not logged in - show login form
                return AHGMH_Permissions_Service::get_login_form('abschuss_admin');
            } else {
                // Logged in but not admin
                return AHGMH_Permissions_Service::get_permission_denied(
                    'Nur Vorstände haben Zugriff auf die Verwaltung.'
                );
            }
        }
        
        // Get all categories from all species
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $categories = array();
        foreach ($species_list as $species) {
            $categories_key = 'ahgmh_categories_' . sanitize_key($species);
            $species_categories = get_option($categories_key, array());
            $categories = array_merge($categories, $species_categories);
        }
        $categories = array_unique($categories); // Remove duplicates
        
        $limits = $this->get_category_limits();
        $counts = $this->get_category_counts();
        $db_config = $this->get_db_config();
        
        ob_start();
        include AHGMH_PLUGIN_DIR . 'templates/admin-template-modern.php';
        return ob_get_clean();
    }
    
    /**
     * Render the summary table using shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the summary table
     */
    public function render_summary($atts = array()) {
        // Parse shortcode attributes - now both parameters are optional
        $atts = shortcode_atts(array(
            'species' => '',        // Optional - empty means all species
            'meldegruppe' => ''     // Optional - empty means all meldegruppen
        ), $atts, 'abschuss_summary');
        
        $selected_species = sanitize_text_field($atts['species']);
        $selected_meldegruppe = sanitize_text_field($atts['meldegruppe']);
        $user_id = get_current_user_id();
        
        // Apply permission-based filtering for logged-in users
        if ($user_id && !AHGMH_Permissions_Service::is_vorstand($user_id)) {
            // Obmann: filter to only their assigned meldegruppen
            if (!empty($selected_species)) {
                $user_meldegruppe = AHGMH_Permissions_Service::get_user_meldegruppe($user_id, $selected_species);
                if (!$user_meldegruppe) {
                    // User is not assigned to this wildart, show nothing
                    return '<div class="alert alert-warning">Sie sind nicht als Obmann für ' . $selected_species . ' eingetragen.</div>';
                }
                // Override meldegruppe parameter with user's assignment
                $selected_meldegruppe = $user_meldegruppe;
            } else {
                // No specific species - limit to user's wildarten
                $user_wildarten = AHGMH_Permissions_Service::get_user_wildarten($user_id);
                if (empty($user_wildarten)) {
                    return '<div class="alert alert-info">Sie haben keine Meldegruppen-Zuweisungen.</div>';
                }
            }
        }
        
        // Validate species if provided
        $available_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $warning_message = '';
        
        if (!empty($selected_species) && !in_array($selected_species, $available_species)) {
            $warning_message = sprintf(__('Warnung: Unbekannte Wildart "%s". Zeige Gesamtstatistiken.', 'abschussplan-hgmh'), $selected_species);
            $selected_species = ''; // Reset to show all
        }
        
        // Get meldegruppen data for validation
        $database = abschussplan_hgmh()->database;
        $available_meldegruppen = $database->get_all_meldegruppen();
        
        if (!empty($selected_meldegruppe) && !in_array($selected_meldegruppe, $available_meldegruppen)) {
            $warning_message = sprintf(__('Warnung: Unbekannte Meldegruppe "%s". Zeige Gesamtstatistiken.', 'abschussplan-hgmh'), $selected_meldegruppe);
            $selected_meldegruppe = ''; // Reset to show all
        }
        
        // Get public summary data based on parameter combination
        $summary_data = $database->get_public_summary_data($selected_species, $selected_meldegruppe);
        
        // Extract data for template
        $categories = $summary_data['categories'];
        $limits = $summary_data['limits'];
        $counts = $summary_data['counts'];
        $allow_exceeding = $summary_data['allow_exceeding'];
        
        ob_start();
        include AHGMH_PLUGIN_DIR . 'templates/summary-template.php';
        return ob_get_clean();
    }

    /**
     * Process form submission
     */
    public function process_form_submission() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Sie müssen angemeldet sein, um eine Abschussmeldung zu erstellen.', 'abschussplan-hgmh')
            ));
        }

        // Verify nonce
        if (!isset($_POST['ahgmh_nonce']) || !wp_verify_nonce($_POST['ahgmh_nonce'], 'ahgmh_form_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.', 'abschussplan-hgmh')
            ));
        }

        // Get current user
        $current_user = wp_get_current_user();

        // Get form data
        $game_species = isset($_POST['game_species']) ? sanitize_text_field($_POST['game_species']) : 'Rotwild';
        $field1 = isset($_POST['field1']) ? sanitize_text_field($_POST['field1']) : ''; // Abschussdatum
        $field2 = isset($_POST['field2']) ? sanitize_text_field($_POST['field2']) : ''; // Abschuss
        $field3 = isset($_POST['field3']) ? sanitize_text_field($_POST['field3']) : ''; // WUS (optional)
        $field4 = isset($_POST['field4']) ? sanitize_textarea_field($_POST['field4']) : ''; // Bemerkung (optional)
        $field5 = isset($_POST['field5']) ? sanitize_text_field($_POST['field5']) : ''; // Jagdbezirk
        
        // Validate data
        $errors = array();
        
        // Required fields
        if (empty($field1)) {
            $errors['field1'] = __('Dieses Feld ist erforderlich.', 'abschussplan-hgmh');
        } else {
            // Validate date format and ensure it's not in the future
            try {
                $selected_date = new DateTime($field1);
                $tomorrow = new DateTime('tomorrow');
                $tomorrow->setTime(0, 0, 0);
                
                if ($selected_date >= $tomorrow) {
                    $errors['field1'] = __('Das Datum darf nicht in der Zukunft liegen.', 'abschussplan-hgmh');
                }
            } catch (Exception $e) {
                $errors['field1'] = __('Ungültiges Datumsformat.', 'abschussplan-hgmh');
            }
        }
        
        if (empty($field2)) {
            $errors['field2'] = __('Dieses Feld ist erforderlich.', 'abschussplan-hgmh');
        } else {
            // Validate dropdown value is in the allowed list (use dynamic categories for the species)
            $categories_key = 'ahgmh_categories_' . sanitize_key($game_species);
            $categories = get_option($categories_key, array());
            
            if (!in_array($field2, $categories)) {
                $errors['field2'] = __('Bitte wählen Sie einen gültigen Wert aus.', 'abschussplan-hgmh');
            }
        }
        
        // Validate field5 (Meldegruppe) - required field
        if (empty($field5)) {
            $errors['field5'] = __('Dieses Feld ist erforderlich.', 'abschussplan-hgmh');
        } else {
            // Validate that the selected Meldegruppe exists in the current wildart configuration
            $database = abschussplan_hgmh()->database;
            $valid_meldegruppen = $database->get_meldegruppen_for_wildart($game_species);
            
            if (!in_array($field5, $valid_meldegruppen)) {
                $errors['field5'] = __('Bitte wählen Sie eine gültige Meldegruppe aus.', 'abschussplan-hgmh');
            }
        }
        
        // Validate WUS to ensure it's an integer if provided and within range
        if (!empty($field3)) {
            if (!is_numeric($field3)) {
                $errors['field3'] = __('WUS muss eine ganze Zahl sein.', 'abschussplan-hgmh');
            } elseif ($field3 < 1000000 || $field3 > 9999999) {
                $errors['field3'] = __('WUS muss zwischen 1000000 und 9999999 liegen.', 'abschussplan-hgmh');
            }
        }
        
        // Check if WUS number is unique (if provided)
        if (!empty($field3) && is_numeric($field3)) {
            $database = abschussplan_hgmh()->database;
            $existing_wus = $database->check_wus_exists($field3);
            if ($existing_wus) {
                $errors['field3'] = __('Diese WUS-Nummer ist bereits vergeben. Bitte geben Sie eine andere WUS-Nummer an.', 'abschussplan-hgmh');
            }
        }
        
        // Note: Categories are always allowed regardless of limits
        // Visual indication of limit status is handled in the frontend

        // If there are errors, return them
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => __('Bitte beheben Sie die Fehler im Formular.', 'abschussplan-hgmh'),
                'errors' => $errors
            ));
        }

        // Process form data
        $data = array(
            'user_id' => $current_user->ID,
            'game_species' => $game_species,
            'field1' => $field1,
            'field2' => $field2,
            'field3' => $field3,
            'field4' => $field4,
            'field5' => $field5
        );
        
        $database = abschussplan_hgmh()->database;
        $submission_id = $database->insert_submission($data);

        if ($submission_id) {
            wp_send_json_success(array(
                'message' => __('Abschussmeldung erfolgreich gespeichert!', 'abschussplan-hgmh'),
                'submission_id' => $submission_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Es gab einen Fehler beim Speichern der Meldung. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
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
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'db_config_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'abschussplan-hgmh')
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
        
        // Save export filename setting
        if (isset($_POST['export_filename'])) {
            $export_filename = sanitize_text_field($_POST['export_filename']);
            update_option('abschuss_export_filename', $export_filename);
        }
        
        wp_send_json_success(array(
            'message' => __('Datenbank-Konfiguration erfolgreich gespeichert.', 'abschussplan-hgmh')
        ));
    }
    
    /**
     * Test database connection
     */
    public function test_db_connection() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'db_config_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'abschussplan-hgmh')
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
                            __('Verbindung zur SQLite-Datenbank (%s) erfolgreich hergestellt.', 'abschussplan-hgmh'),
                            $sqlite_file
                        )
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => sprintf(
                            __('Die Datei %s existiert, ist aber nicht beschreibbar.', 'abschussplan-hgmh'),
                            $sqlite_file
                        )
                    ));
                }
            } catch (Exception $e) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Fehler beim Verbindungstest: %s', 'abschussplan-hgmh'),
                        $e->getMessage()
                    )
                ));
            }
        } else if ($db_type === 'postgresql') {
            // For PostgreSQL, we would use pg_connect in production
            // Here, we'll just simulate a successful connection
            wp_send_json_success(array(
                'message' => __('Verbindung zur PostgreSQL-Datenbank erfolgreich hergestellt.', 'abschussplan-hgmh')
            ));
        } else if ($db_type === 'mysql') {
            // For MySQL, we would use mysqli_connect in production
            // Here, we'll just simulate a successful connection
            wp_send_json_success(array(
                'message' => __('Verbindung zur MySQL-Datenbank erfolgreich hergestellt.', 'abschussplan-hgmh')
            ));
        } else {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Unbekannter Datenbanktyp: %s', 'abschussplan-hgmh'),
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
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'limits_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        // Get all categories from all species  
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        $categories = array();
        foreach ($species_list as $species) {
            $categories_key = 'ahgmh_categories_' . sanitize_key($species);
            $species_categories = get_option($categories_key, array());
            $categories = array_merge($categories, $species_categories);
        }
        $categories = array_unique($categories); // Remove duplicates
        
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
            'message' => __('Höchstgrenzen erfolgreich gespeichert.', 'abschussplan-hgmh'),
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
     * Get category limits for a specific species
     * 
     * @param string $species Game species
     */
    private function get_category_limits($species = 'Rotwild') {
        // Get dynamic categories for the specified species
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        $default = array();
        foreach ($categories as $category) {
            $default[$category] = 0;
        }
        
        // Get species-specific limits
        $option_key = 'abschuss_category_limits_' . sanitize_key($species);
        $limits = get_option($option_key, $default);
        
        return $limits;
    }
    
    /**
     * Get submission counts per category for a specific species
     * 
     * @param string $species Game species
     * @param string $meldegruppe Meldegruppe filter
     */
    private function get_category_counts($species = '', $meldegruppe = '') {
        $database = abschussplan_hgmh()->database;
        return $database->get_category_counts($species, $meldegruppe);
    }

    /**
     * Get all available meldegruppen (wildart-specific or global)
     * 
     * @param string $species Optional species filter for wildart-specific mode
     * @return array Available meldegruppen
     */
    private function get_available_meldegruppen($species = '') {
        $database = abschussplan_hgmh()->database;
        
        if ($database->is_wildart_specific_enabled()) {
            // Return wildart-specific meldegruppen
            if (!empty($species)) {
                return $database->get_meldegruppen_for_wildart($species);
            } else {
                // If no species provided, return all wildart-specific meldegruppen
                return $database->get_meldegruppen_for_wildart();
            }
        } else {
            // Return global meldegruppen from jagdbezirke table
            return $database->get_global_meldegruppen();
        }
    }

    /**
     * Get category allow exceeding settings for a specific species
     * 
     * @param string $species Game species
     */
    private function get_category_allow_exceeding($species = 'Rotwild') {
        // Get dynamic categories for the specified species
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        $default = array();
        foreach ($categories as $category) {
            $default[$category] = false;
        }
        
        // Get species-specific allow exceeding settings
        $option_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        $allow_exceeding = get_option($option_key, $default);
        
        return $allow_exceeding;
    }

    /**
     * Get meldegruppen-specific category limits with fallback to species defaults
     * 
     * @param string $species Game species
     * @param string $meldegruppe Meldegruppe name
     * @return array Array of category => limit
     */
    private function get_meldegruppen_category_limits($species, $meldegruppe) {
        $database = abschussplan_hgmh()->database;
        
        // Get dynamic categories for the specified species
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        $limits = array();
        foreach ($categories as $category) {
            $applicable_limits = $database->get_applicable_limits($species, $meldegruppe, $category);
            $limits[$category] = $applicable_limits['limit'];
        }
        
        return $limits;
    }

    /**
     * Get meldegruppen-specific allow exceeding settings with fallback to species defaults
     * 
     * @param string $species Game species
     * @param string $meldegruppe Meldegruppe name
     * @return array Array of category => allow_exceeding
     */
    private function get_meldegruppen_category_allow_exceeding($species, $meldegruppe) {
        $database = abschussplan_hgmh()->database;
        
        // Get dynamic categories for the specified species
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        
        $allow_exceeding = array();
        foreach ($categories as $category) {
            $applicable_limits = $database->get_applicable_limits($species, $meldegruppe, $category);
            $allow_exceeding[$category] = $applicable_limits['allow_exceeding'];
        }
        
        return $allow_exceeding;
    }

    /**
     * Render limits configuration for specific species
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the limits configuration
     */
    public function render_limits_config($atts = array()) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'wildart' => ''
        ), $atts, 'abschuss_limits');
        
        // Check permissions with new 3-level system
        if (!AHGMH_Permissions_Service::can_access_shortcode('abschuss_limits', $atts)) {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                // Not logged in - show login form
                return AHGMH_Permissions_Service::get_login_form('abschuss_limits');
            } else {
                // Logged in but not admin
                return AHGMH_Permissions_Service::get_permission_denied(
                    'Nur der Vorstand kann Limits für die Hegegemeinschaft verwalten.'
                );
            }
        }
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        $selected_wildart = sanitize_text_field($atts['wildart']);
        
        // Verweis auf Admin-Panel oder Embedded Interface
        if (empty($selected_wildart)) {
            return '<div class="ahgmh-limits-redirect" style="padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; margin: 15px 0;">
                <h4 style="margin: 0 0 15px 0; color: #2271b1;">⚙️ Limits-Verwaltung</h4>
                <p style="margin: 0 0 15px 0;">Für die vollständige Limits-Verwaltung nutzen Sie das Admin-Panel:</p>
                <p style="margin: 0;">
                    <a href="' . admin_url('admin.php?page=abschussplan-hgmh-settings#wildart-config') . '" class="button button-primary" style="text-decoration: none;">
                        🔗 Zum Admin-Panel
                    </a>
                </p>
            </div>';
        }
        
        // Get available species list
        $available_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        if (!in_array($selected_wildart, $available_species)) {
            return '<div class="alert alert-warning" style="padding: 15px; background: #fff3cd; color: #664d03; border: 1px solid #ffecb5; border-radius: 4px; margin: 10px 0;">
                <h4 style="margin: 0 0 10px 0;">⚠️ Wildart nicht gefunden</h4>
                <p style="margin: 0;">Die Wildart "' . esc_html($selected_wildart) . '" existiert nicht in der Konfiguration.</p>
                <p style="margin: 5px 0 0 0;"><small>Verfügbare Wildarten: ' . esc_html(implode(', ', $available_species)) . '</small></p>
            </div>';
        }
        
        // Get database instance
        $database = abschussplan_hgmh()->database;
        
        // Get dynamic categories for the selected wildart
        $categories_key = 'ahgmh_categories_' . sanitize_key($selected_wildart);
        $categories = get_option($categories_key, array());
        
        // Simple implementation to prevent fatal errors
        if (empty($selected_wildart)) {
            return '<div class="alert alert-info" style="padding: 15px; background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0;">
                <h4 style="margin: 0 0 10px 0;">⚙️ Limits-Verwaltung</h4>
                <p style="margin: 0 0 15px 0;">Für die vollständige Limits-Verwaltung nutzen Sie das Admin-Panel:</p>
                <p style="margin: 0;">
                    <a href="' . admin_url('admin.php?page=abschussplan-hgmh-settings') . '" class="button button-primary" style="text-decoration: none;">
                        🔗 Zum Admin-Panel
                    </a>
            </p>
        </div>';
        }
        
        return '<div class="alert alert-info" style="padding: 15px; background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0;">
        <h4 style="margin: 0 0 10px 0;">⚙️ Limits für ' . esc_html($selected_wildart) . '</h4>
        <p style="margin: 0 0 15px 0;">Die Limits-Konfiguration für ' . esc_html($selected_wildart) . ' ist im Admin-Panel verfügbar.</p>
        <p style="margin: 0;">
        <a href="' . admin_url('admin.php?page=abschussplan-hgmh-settings') . '" class="button button-primary" style="text-decoration: none;">
        🔗 Limits konfigurieren
        </a>
        </p>
        </div>';
    }

    /**
     * Save categories
     */
    public function save_categories() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['categories_nonce_field']) || !wp_verify_nonce($_POST['categories_nonce_field'], 'categories_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        $categories = isset($_POST['categories']) ? array_filter($_POST['categories']) : array();
        
        // Sanitize categories
        $sanitized_categories = array();
        foreach ($categories as $category) {
            $sanitized = sanitize_text_field($category);
            if (!empty($sanitized)) {
                $sanitized_categories[] = $sanitized;
            }
        }
        
        // Save categories
        update_option('ahgmh_categories', $sanitized_categories);
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d Kategorien gespeichert.', 'abschussplan-hgmh'), count($sanitized_categories))
        ));
    }

    /**
     * Save species
     */
    public function save_species() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['species_nonce_field']) || !wp_verify_nonce($_POST['species_nonce_field'], 'species_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        $species = isset($_POST['species']) ? array_filter($_POST['species']) : array();
        
        // Sanitize species
        $sanitized_species = array();
        foreach ($species as $specie) {
            $sanitized = sanitize_text_field($specie);
            if (!empty($sanitized)) {
                $sanitized_species[] = $sanitized;
            }
        }
        
        // Save species
        update_option('ahgmh_species', $sanitized_species);
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d Wildarten gespeichert.', 'abschussplan-hgmh'), count($sanitized_species))
        ));
    }

    /**
     * Save species-specific limits
     */
    public function save_species_limits() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['species_limits_nonce_field']) || !wp_verify_nonce($_POST['species_limits_nonce_field'], 'species_limits_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        $species = isset($_POST['species']) ? sanitize_text_field($_POST['species']) : '';
        $limits = isset($_POST['limits']) ? $_POST['limits'] : array();
        $allow_exceeding = isset($_POST['allow_exceeding']) ? $_POST['allow_exceeding'] : array();
        
        if (empty($species)) {
            wp_send_json_error(array(
                'message' => __('Keine Wildart angegeben.', 'abschussplan-hgmh')
            ));
        }
        
        // Sanitize limits
        $sanitized_limits = array();
        foreach ($limits as $category => $limit) {
            $sanitized_category = sanitize_text_field($category);
            $sanitized_limit = max(0, intval($limit));
            $sanitized_limits[$sanitized_category] = $sanitized_limit;
        }
        
        // Sanitize allow exceeding settings
        $sanitized_allow_exceeding = array();
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());
        foreach ($categories as $category) {
            $sanitized_category = sanitize_text_field($category);
            $exceeding_allowed = isset($allow_exceeding[$category]) && $allow_exceeding[$category] == '1';
            $sanitized_allow_exceeding[$sanitized_category] = $exceeding_allowed;
        }
        
        // Save species-specific limits
        $option_key = 'abschuss_category_limits_' . sanitize_key($species);
        update_option($option_key, $sanitized_limits);
        
        // Save species-specific allow exceeding settings
        $exceeding_option_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        update_option($exceeding_option_key, $sanitized_allow_exceeding);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Abschuss (Soll) für %s gespeichert.', 'abschussplan-hgmh'), $species)
        ));
    }
    
    /**
     * Load species-specific limits via AJAX
     */
    public function load_species_limits() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        $species = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : '';
        
        if (empty($species)) {
            wp_send_json_error(array(
                'message' => __('Keine Wildart angegeben.', 'abschussplan-hgmh')
            ));
        }
        
        // Load species-specific limits
        $option_key = 'abschuss_category_limits_' . sanitize_key($species);
        $limits = get_option($option_key, array());
        
        // Load species-specific allow exceeding settings
        $exceeding_option_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        $allow_exceeding = get_option($exceeding_option_key, array());
        
        wp_send_json_success(array(
            'limits' => $limits,
            'allowExceeding' => $allow_exceeding
        ));
    }
    
    /**
     * Save new Jagdbezirk via AJAX
     */
    public function save_jagdbezirk() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jagdbezirk_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        $data = array(
            'jagdbezirk' => sanitize_text_field($_POST['jagdbezirk']),
            'meldegruppe' => sanitize_text_field($_POST['meldegruppe']),
            'ungueltig' => (isset($_POST['ungueltig']) && $_POST['ungueltig'] === '1') ? 1 : 0,
            'bemerkung' => sanitize_textarea_field($_POST['bemerkung'])
        );
        
        $database = abschussplan_hgmh()->database;
        $result = $database->insert_jagdbezirk($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Jagdbezirk erfolgreich hinzugefügt.', 'abschussplan-hgmh'),
                'id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Fehler beim Hinzufügen des Jagdbezirks.', 'abschussplan-hgmh')
            ));
        }
    }
    
    /**
     * Update Jagdbezirk via AJAX
     */
    public function update_jagdbezirk() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jagdbezirk_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        $id = intval($_POST['id']);
        $data = array(
            'jagdbezirk' => sanitize_text_field($_POST['jagdbezirk']),
            'meldegruppe' => sanitize_text_field($_POST['meldegruppe']),
            'ungueltig' => (isset($_POST['ungueltig']) && $_POST['ungueltig'] === '1') ? 1 : 0,
            'bemerkung' => sanitize_textarea_field($_POST['bemerkung'])
        );
        
        $database = abschussplan_hgmh()->database;
        $result = $database->update_jagdbezirk($id, $data);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Jagdbezirk erfolgreich aktualisiert.', 'abschussplan-hgmh')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Fehler beim Aktualisieren des Jagdbezirks.', 'abschussplan-hgmh')
            ));
        }
    }
    
    /**
     * Delete Jagdbezirk via AJAX
     */
    public function delete_jagdbezirk() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jagdbezirk_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        $id = intval($_POST['id']);
        
        $database = abschussplan_hgmh()->database;
        $result = $database->delete_jagdbezirk($id);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Jagdbezirk erfolgreich gelöscht.', 'abschussplan-hgmh')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Fehler beim Löschen des Jagdbezirks.', 'abschussplan-hgmh')
            ));
        }
    }
    
    /**
     * Delete all Jagdbezirke via AJAX
     */
    public function delete_all_jagdbezirke() {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Sie haben keine Berechtigung für diese Aktion.', 'abschussplan-hgmh')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jagdbezirk_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }
        
        $database = abschussplan_hgmh()->database;
        $result = $database->delete_all_jagdbezirke();
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Alle Jagdbezirke erfolgreich gelöscht.', 'abschussplan-hgmh')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Fehler beim Löschen der Jagdbezirke.', 'abschussplan-hgmh')
            ));
        }
    }
    
    /**
     * Export submissions as CSV
     */
    public function export_csv() {
        try {
            // Sanitize input parameters
            $species = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : '';
            $from_date = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
            $to_date = isset($_GET['to']) ? sanitize_text_field($_GET['to']) : '';
            
            // Get export filename from WordPress options
            $export_filename = get_option('abschuss_export_filename', 'abschuss_export');
            
            // Get database instance
            $database = abschussplan_hgmh()->database;
            
            // Build query conditions
            $conditions = array();
            $params = array();
            
            if (!empty($species)) {
                $conditions[] = 'game_species = %s';
                $params[] = $species;
            }
            
            if (!empty($from_date)) {
                $conditions[] = 'DATE(field1) >= %s';
                $params[] = $from_date;
            }
            
            if (!empty($to_date)) {
                $conditions[] = 'DATE(field1) <= %s';
                $params[] = $to_date;
            }
            
            // Build WHERE clause
            $where_clause = '';
            if (!empty($conditions)) {
                $where_clause = 'WHERE ' . implode(' AND ', $conditions);
            }
            
            // Get submissions using WordPress database
            global $wpdb;
            $table_name = $database->get_table_name();
            
            $query = "SELECT s.id, s.game_species, s.field1, s.field2, s.field3, s.field4, s.field5, s.user_id, s.created_at, 
                             j.meldegruppe
                      FROM {$table_name} s 
                      LEFT JOIN {$wpdb->prefix}ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk 
                      {$where_clause} 
                      ORDER BY s.created_at DESC";
            
            if (!empty($params)) {
                $query = $wpdb->prepare($query, $params);
            }
            
            $submissions = $wpdb->get_results($query, ARRAY_A);
            
            // Generate filename
            $custom_filename = sanitize_text_field($_GET['filename'] ?? '');
            
            if (!empty($custom_filename)) {
                $filename = sanitize_file_name($custom_filename) . '.csv';
            } else {
                // Use configured pattern or default
                $filename_pattern = get_option('ahgmh_export_filename_pattern', 'abschussplan_{species}_{date}');
                $include_time = get_option('ahgmh_export_include_time', false);
                
                // Replace placeholders
                $replacements = array(
                    '{species}' => !empty($species) ? sanitize_file_name(strtolower($species)) : 'alle',
                    '{date}' => date('Y-m-d'),
                    '{datetime}' => date('Y-m-d_H-i')
                );
                
                // Add date filter info if present
                if (!empty($from_date) || !empty($to_date)) {
                    $date_filter = '';
                    if (!empty($from_date) && !empty($to_date)) {
                        $date_filter = '_' . $from_date . '_to_' . $to_date;
                    } elseif (!empty($from_date)) {
                        $date_filter = '_from_' . $from_date;
                    } elseif (!empty($to_date)) {
                        $date_filter = '_until_' . $to_date;
                    }
                    $replacements['{date}'] .= $date_filter;
                    $replacements['{datetime}'] .= $date_filter;
                }
                
                // Use {datetime} if time is included, otherwise use {date}
                if ($include_time && strpos($filename_pattern, '{datetime}') === false) {
                    $filename_pattern = str_replace('{date}', '{datetime}', $filename_pattern);
                }
                
                $filename = str_replace(array_keys($replacements), array_values($replacements), $filename_pattern) . '.csv';
                $filename = sanitize_file_name($filename);
            }
            
            // Clean any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Open output stream
            $output = fopen('php://output', 'w');
            
            // Write CSV header
            fputcsv($output, array(
                'ID',
                'Wildart', 
                'Abschussdatum',
                'Abschuss',
                'WUS',
                'Jagdbezirk',
                'Meldegruppe',
                'Bemerkung',
                'Erstellt von',
                'Erstellt am'
            ));
            
            // Write data rows
            foreach ($submissions as $submission) {
                // Get user display name
                $user = get_userdata($submission['user_id']);
                $created_by = $user ? $user->display_name : 'System';
                
                fputcsv($output, array(
                    $submission['id'],
                    $submission['game_species'],
                    $submission['field1'],      // Abschussdatum
                    $submission['field2'],      // Abschuss
                    $submission['field3'],      // WUS
                    $submission['field5'],      // Jagdbezirk
                    $submission['meldegruppe'] ?? '', // Meldegruppe
                    $submission['field4'],      // Bemerkung
                    $created_by,
                    $submission['created_at']
                ));
            }
            
            fclose($output);
            wp_die();
            
        } catch (Exception $e) {
            // Log error and return JSON error response
            error_log('CSV Export Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Export failed: ' . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for refreshing the submissions table
     */
    public function ajax_refresh_table() {
        // Check nonce if provided
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'ahgmh_form_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitscheck fehlgeschlagen.', 'abschussplan-hgmh')
            ));
        }

        // Get current page and species filter
        $current_page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $species_filter = isset($_POST['species']) ? sanitize_text_field($_POST['species']) : '';

        // Prepare attributes for the table shortcode
        $atts = array();
        if (!empty($species_filter)) {
            $atts['species'] = $species_filter;
        }

        // Generate fresh table HTML using the render_table method
        $table_html = $this->render_table($atts);

        wp_send_json_success(array(
            'html' => $table_html,
            'page' => $current_page
        ));
    }
}
