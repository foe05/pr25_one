<?php
/**
 * Public Form Handler Class
 * Handles public form submissions without WordPress login requirement
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling public form operations with email verification
 */
class AHGMH_Public_Form_Handler {
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode for public form
        add_shortcode('abschuss_form_public', array($this, 'render_form'));

        // Handle form submissions via AJAX (nopriv allows non-logged-in users)
        add_action('wp_ajax_submit_abschuss_form_public', array($this, 'process_form_submission'));
        add_action('wp_ajax_nopriv_submit_abschuss_form_public', array($this, 'process_form_submission'));
    }

    /**
     * Render the public form using shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output of the form
     */
    public function render_form($atts = array()) {
        // Parse shortcode attributes with species as required parameter
        $atts = shortcode_atts(array(
            'species' => ''
        ), $atts, 'abschuss_form_public');

        // Parameter validation - species is required
        if (empty($atts['species'])) {
            return '<div class="alert alert-warning">Parameter "species" ist erforderlich für [abschuss_form_public]. Beispiel: [abschuss_form_public species="Rotwild"]</div>';
        }

        // Enqueue form-specific scripts
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // Enqueue public form validation script
        wp_enqueue_script(
            'ahgmh-public-form-validation',
            AHGMH_PLUGIN_URL . 'assets/js/public-form-validation.js',
            array('jquery'),
            AHGMH_PLUGIN_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script('ahgmh-public-form-validation', 'ahgmh_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ahgmh_form_nonce')
        ));

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

        // Get dynamic categories from options for the selected species
        $categories_key = 'ahgmh_categories_' . sanitize_key($selected_species);
        $saved_categories = get_option($categories_key, array());

        // Generate list of available categories
        $categories = !empty($saved_categories) ? $saved_categories : array();

        // Define variables for template
        $counts = array();
        $limits = array();

        ob_start();
        include AHGMH_PLUGIN_DIR . 'templates/public-form-template.php';
        return ob_get_clean();
    }

    /**
     * Process public form submission via AJAX
     * No login required, uses email verification and rate limiting
     */
    public function process_form_submission() {
        // Verify nonce for security
        if (!isset($_POST['ahgmh_nonce']) || !wp_verify_nonce($_POST['ahgmh_nonce'], 'ahgmh_form_nonce')) {
            wp_send_json_error(array(
                'message' => __('Sicherheitsüberprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'abschussplan-hgmh')
            ));
        }

        // Get client IP address
        $client_ip = AHGMH_Verification_Service::get_client_ip();

        // Check rate limit
        if (AHGMH_Rate_Limiter::check_rate_limit($client_ip)) {
            $remaining_time = AHGMH_Rate_Limiter::get_rate_limit_info($client_ip);
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Sie haben das Limit von %d Meldungen pro Stunde erreicht. Bitte versuchen Sie es später erneut.', 'abschussplan-hgmh'),
                    AHGMH_Rate_Limiter::MAX_SUBMISSIONS
                )
            ));
        }

        // Get form data
        $game_species = isset($_POST['game_species']) ? sanitize_text_field($_POST['game_species']) : 'Rotwild';
        $field1 = isset($_POST['field1']) ? sanitize_text_field($_POST['field1']) : ''; // Abschussdatum
        $field2 = isset($_POST['field2']) ? sanitize_text_field($_POST['field2']) : ''; // Abschuss
        $field3 = isset($_POST['field3']) ? sanitize_text_field($_POST['field3']) : ''; // WUS (optional)
        $field4 = isset($_POST['field4']) ? sanitize_textarea_field($_POST['field4']) : ''; // Bemerkung (optional)
        $field5 = isset($_POST['field5']) ? sanitize_text_field($_POST['field5']) : ''; // Jagdbezirk
        $field6 = isset($_POST['field6']) ? sanitize_textarea_field($_POST['field6']) : ''; // Interne Notiz (optional)
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : ''; // Email (required for public form)

        // Validate data
        $errors = array();

        // Validate email (required for public form)
        if (empty($email)) {
            $errors['email'] = __('E-Mail-Adresse ist erforderlich.', 'abschussplan-hgmh');
        } elseif (!is_email($email)) {
            $errors['email'] = __('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'abschussplan-hgmh');
        }

        // Validate date field (required)
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

        // Validate category field (required)
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
            $wildart_meldegruppen = get_option('ahgmh_wildart_meldegruppen', array());
            $valid_meldegruppen = isset($wildart_meldegruppen[$game_species]) ? $wildart_meldegruppen[$game_species] : array('Gruppe_A', 'Gruppe_B');

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

        // If there are errors, return them
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => __('Bitte beheben Sie die Fehler im Formular.', 'abschussplan-hgmh'),
                'errors' => $errors
            ));
        }

        // Prepare submission data
        $data = array(
            'user_id' => 0, // No user for public submissions
            'game_species' => $game_species,
            'field1' => $field1,
            'field2' => $field2,
            'field3' => $field3,
            'field4' => $field4,
            'field5' => $field5,
            'field6' => $field6,
            'email' => $email,
            'ip' => $client_ip
        );

        // Create pending submission with verification
        $result = AHGMH_Verification_Service::create_pending_submission($data);

        if ($result) {
            hege_send_log( 'submission_created_public', array( 'species' => $game_species ) );

            // Increment rate limit counter
            AHGMH_Rate_Limiter::increment_submission_count($client_ip);

            wp_send_json_success(array(
                'message' => __('Ihre Meldung wurde erfolgreich übermittelt! Bitte prüfen Sie Ihr E-Mail-Postfach und bestätigen Sie Ihre E-Mail-Adresse innerhalb von 48 Stunden.', 'abschussplan-hgmh'),
                'submission_id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Es gab einen Fehler beim Speichern der Meldung. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
            ));
        }
    }
}
