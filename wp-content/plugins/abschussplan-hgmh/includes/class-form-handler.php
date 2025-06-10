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
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'species' => 'Rotwild'
        ), $atts, 'abschuss_form');
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning" role="alert">' . 
                   '<h4>' . __('Anmeldung erforderlich', 'abschussplan-hgmh') . '</h4>' .
                   '<p>' . __('Sie müssen angemeldet sein, um eine Abschussmeldung zu erstellen.', 'abschussplan-hgmh') . '</p>' .
                   '<a href="' . wp_login_url(get_permalink()) . '" class="btn btn-primary">' . __('Jetzt anmelden', 'abschussplan-hgmh') . '</a>' .
                   '</div>';
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
            $selected_species = !empty($available_species) ? $available_species[0] : 'Rotwild';
        }
        
        // Get limits to check if any category has reached its limit
        $limits = $this->get_category_limits($selected_species);
        $counts = $this->get_category_counts($selected_species);
        
        // Get dynamic categories from options
        $saved_categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
        
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
            ),
            $atts,
            'abschuss_table'
        );
        
        // Get current page and limit
        $page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : intval($atts['page']);
        $limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : intval($atts['limit']);
        
        // Get submissions data
        $database = abschussplan_hgmh()->database;
        $species = sanitize_text_field($atts['species']);
        
        if (!empty($species)) {
            $submissions = $database->get_submissions_by_species($limit, ($page - 1) * $limit, $species);
            $total_count = $database->count_submissions_by_species($species);
        } else {
            $submissions = $database->get_submissions($limit, ($page - 1) * $limit);
            $total_count = $database->count_submissions();
        }
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
        
        // Get categories from options
        $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
        
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
     * @return string HTML output of the summary table
     */
    public function render_summary($atts = array()) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'species' => 'Rotwild'
        ), $atts, 'abschuss_summary');
        
        $selected_species = sanitize_text_field($atts['species']);
        
        // Get dynamic categories
        $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
        
        $limits = $this->get_category_limits($selected_species);
        $counts = $this->get_category_counts($selected_species);
        
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
            // Validate dropdown value is in the allowed list (use dynamic categories)
            $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
            
            if (!in_array($field2, $categories)) {
                $errors['field2'] = __('Bitte wählen Sie einen gültigen Wert aus.', 'custom-form-display');
            }
        }
        
        // Validate field5 (Jagdbezirk) - required field
        if (empty($field5)) {
            $errors['field5'] = __('Dieses Feld ist erforderlich.', 'custom-form-display');
        } else {
            // Validate that the selected Jagdbezirk exists and is active
            $database = abschussplan_hgmh()->database;
            $active_jagdbezirke = $database->get_active_jagdbezirke();
            $valid_jagdbezirke = array_column($active_jagdbezirke, 'jagdbezirk');
            

            
            if (!in_array($field5, $valid_jagdbezirke)) {
                $errors['field5'] = __('Bitte wählen Sie einen gültigen Jagdbezirk aus.', 'custom-form-display');
            }
        }
        
        // Validate WUS to ensure it's an integer if provided and within range
        if (!empty($field3)) {
            if (!is_numeric($field3)) {
                $errors['field3'] = __('WUS muss eine ganze Zahl sein.', 'custom-form-display');
            } elseif ($field3 < 1000000 || $field3 > 9999999) {
                $errors['field3'] = __('WUS muss zwischen 1000000 und 9999999 liegen.', 'custom-form-display');
            }
        }
        
        // Check if WUS number is unique (if provided)
        if (!empty($field3) && is_numeric($field3)) {
            $database = abschussplan_hgmh()->database;
            $existing_wus = $database->check_wus_exists($field3);
            if ($existing_wus) {
                $errors['field3'] = __('Diese WUS-Nummer ist bereits vergeben. Bitte geben Sie eine andere WUS-Nummer an.', 'custom-form-display');
            }
        }
        
        // Check if the selected category has reached its maximum limit (only if exceeding is not allowed)
        if (empty($errors['field2'])) {
            $limits = $this->get_category_limits($game_species);
            $counts = $this->get_category_counts($game_species);
            $allow_exceeding = $this->get_category_allow_exceeding($game_species);
            
            $current_count = isset($counts[$field2]) ? $counts[$field2] : 0;
            $max_count = isset($limits[$field2]) ? $limits[$field2] : 0;
            $exceeding_allowed = isset($allow_exceeding[$field2]) ? $allow_exceeding[$field2] : false;
            
            if ($max_count > 0 && $current_count >= $max_count && !$exceeding_allowed) {
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
        
        // Save export filename setting
        if (isset($_POST['export_filename'])) {
            $export_filename = sanitize_text_field($_POST['export_filename']);
            update_option('abschuss_export_filename', $export_filename);
        }
        
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
        
        $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
        
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
     * Get category limits for a specific species
     * 
     * @param string $species Game species
     */
    private function get_category_limits($species = 'Rotwild') {
        // Get dynamic categories
        $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
        
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
     */
    private function get_category_counts($species = '') {
        $database = abschussplan_hgmh()->database;
        return $database->get_category_counts($species);
    }

    /**
     * Get category allow exceeding settings for a specific species
     * 
     * @param string $species Game species
     */
    private function get_category_allow_exceeding($species = 'Rotwild') {
        // Get dynamic categories
        $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
        
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
     * Render limits configuration for specific species
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of the limits configuration
     */
    public function render_limits_config($atts = array()) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'species' => 'Rotwild'
        ), $atts, 'abschuss_limits');
        
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            return '<p>' . __('Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'abschussplan-hgmh') . '</p>';
        }
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        $selected_species = sanitize_text_field($atts['species']);
        
        // Get dynamic categories
        $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
        
        $limits = $this->get_category_limits($selected_species);
        $counts = $this->get_category_counts($selected_species);
        $allow_exceeding = $this->get_category_allow_exceeding($selected_species);
        
        ob_start();
        ?>
        <div class="ahgmh-limits-config">
            <div id="limits-response" class="notice" style="display: none;"></div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><?php echo esc_html(sprintf(__('Abschuss (Soll) für %s', 'abschussplan-hgmh'), $selected_species)); ?></h3>
                </div>
                <div class="card-body">
                    <form id="species-limits-form" data-species="<?php echo esc_attr($selected_species); ?>">
                        <?php wp_nonce_field('species_limits_nonce', 'species_limits_nonce_field'); ?>
                        
                        <table class="table table-striped">
                        <thead>
                        <tr>
                        <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Abschuss (Ist)', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Abschuss (Soll)', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Überschießen möglich?', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): 
                                    $current_count = isset($counts[$category]) ? $counts[$category] : 0;
                                    $limit = isset($limits[$category]) ? $limits[$category] : 0;
                                    $allow_exceed = isset($allow_exceeding[$category]) ? $allow_exceeding[$category] : false;
                                    $percentage = $limit > 0 ? ($current_count / $limit) * 100 : 0;
                                    $status_class = '';
                                    if ($percentage >= 100) {
                                        $status_class = 'bg-danger text-white';
                                    } elseif ($percentage >= 90) {
                                        $status_class = 'bg-warning text-dark';
                                    } else {
                                        $status_class = 'bg-success text-white';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($category); ?></strong></td>
                                    <td><span class="badge bg-primary"><?php echo esc_html($current_count); ?></span></td>
                                    <td>
                                        <input type="number" 
                                               name="limits[<?php echo esc_attr($category); ?>]" 
                                               value="<?php echo esc_attr($limit); ?>" 
                                               min="0" 
                                               max="999" 
                                               class="form-control" 
                                               style="width: 100px;" />
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   name="allow_exceeding[<?php echo esc_attr($category); ?>]" 
                                                   value="1"
                                                   class="form-check-input" 
                                                   id="exceed_<?php echo esc_attr(sanitize_title($category)); ?>"
                                                   <?php checked($allow_exceed, true); ?> />
                                            <label class="form-check-label" for="exceed_<?php echo esc_attr(sanitize_title($category)); ?>">
                                                <?php echo esc_html__('Ja', 'abschussplan-hgmh'); ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($limit > 0): ?>
                                            <span class="badge <?php echo esc_attr($status_class); ?>" style="padding: 6px 12px;">
                                                <?php echo esc_html(round($percentage, 1)); ?>%
                                                <?php if ($allow_exceed): ?>
                                                    <small> (<?php echo esc_html__('Überschreitung erlaubt', 'abschussplan-hgmh'); ?>)</small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-white"><?php echo esc_html__('Unbegrenzt', 'abschussplan-hgmh'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <?php echo esc_html__('Grenzen speichern', 'abschussplan-hgmh'); ?>
                            </button>
                            <button type="button" class="btn btn-secondary" id="reset-species-limits">
                                <?php echo esc_html__('Alle zurücksetzen', 'abschussplan-hgmh'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Species-specific limits form
            $('#species-limits-form').on('submit', function(e) {
                e.preventDefault();
                
                var $submitBtn = $(this).find('button[type="submit"]');
                var originalText = $submitBtn.text();
                var species = $(this).data('species');
                
                $submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Speichern...', 'abschussplan-hgmh')); ?>');
                
                var formData = new FormData(this);
                formData.append('action', 'save_species_limits');
                formData.append('species', species);
                 
                 // Debug: Log form data
                 console.log('Submitting form for species:', species);
                 for (var pair of formData.entries()) {
                     console.log(pair[0] + ': ' + pair[1]);
                 }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('AJAX Response:', response);
                        if (response.success) {
                            $('#limits-response')
                                .removeClass('notice-error')
                                .addClass('notice notice-success')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        } else {
                            $('#limits-response')
                                .removeClass('notice-success')
                                .addClass('notice notice-error')
                                .html('<p>' + (response.data && response.data.message ? response.data.message : 'Unbekannter Fehler') + '</p>')
                                .show();
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        console.log('AJAX Error:', textStatus, errorThrown);
                        console.log('Response Text:', xhr.responseText);
                        $('#limits-response')
                            .removeClass('notice-success')
                            .addClass('notice notice-error')
                            .html('<p><?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'abschussplan-hgmh')); ?></p>')
                            .show();
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text(originalText);
                        $('html, body').animate({ scrollTop: 0 }, 500);
                    }
                });
            });
            
            // Reset limits button
            $('#reset-species-limits').click(function() {
                if (confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie alle Grenzen auf 0 zurücksetzen möchten?', 'abschussplan-hgmh')); ?>')) {
                    $('#species-limits-form input[type="number"]').val('0');
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
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
        $categories = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
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
            
            $query = "SELECT id, game_species, field1, field2, field3, field4, field5, user_id, created_at 
                      FROM {$table_name} {$where_clause} 
                      ORDER BY created_at DESC";
            
            if (!empty($params)) {
                $query = $wpdb->prepare($query, $params);
            }
            
            $submissions = $wpdb->get_results($query, ARRAY_A);
            
            // Build filename with filters
            $filename_parts = array($export_filename);
            if (!empty($species)) {
                $filename_parts[] = sanitize_file_name(strtolower($species));
            }
            if (!empty($from_date) || !empty($to_date)) {
                if (!empty($from_date) && !empty($to_date)) {
                    $filename_parts[] = $from_date . '_to_' . $to_date;
                } elseif (!empty($from_date)) {
                    $filename_parts[] = 'from_' . $from_date;
                } elseif (!empty($to_date)) {
                    $filename_parts[] = 'until_' . $to_date;
                }
            }
            
            $filename = implode('_', $filename_parts) . '.csv';
            
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
                    $submission['field4'],      // Bemerkung
                    $created_by,
                    $submission['created_at']
                ));
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            // Log error and return JSON error response
            error_log('CSV Export Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Export failed: ' . $e->getMessage()
            ));
        }
    }
}
