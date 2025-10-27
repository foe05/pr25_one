<?php
/**
 * REST API Handler Class
 * Provides REST API endpoints for mobile app integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling REST API operations
 */
class AHGMH_REST_API {

    /**
     * API namespace
     */
    private $namespace = 'ahgmh/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register all REST API routes
     */
    public function register_routes() {
        // Public endpoints (no authentication required)

        // Plugin info endpoint
        register_rest_route($this->namespace, '/info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_plugin_info'),
            'permission_callback' => '__return_true'
        ));

        // Species list endpoint
        register_rest_route($this->namespace, '/species', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_species_list'),
            'permission_callback' => '__return_true'
        ));

        // Public summary endpoint
        register_rest_route($this->namespace, '/summary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_summary'),
            'permission_callback' => '__return_true',
            'args' => array(
                'species' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'meldegruppe' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Public submissions list endpoint
        register_rest_route($this->namespace, '/submissions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_submissions_list'),
            'permission_callback' => '__return_true',
            'args' => array(
                'species' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'meldegruppe' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Categories endpoint
        register_rest_route($this->namespace, '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => '__return_true',
            'args' => array(
                'species' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Jagdbezirke endpoint
        register_rest_route($this->namespace, '/jagdbezirke', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_jagdbezirke'),
            'permission_callback' => '__return_true',
            'args' => array(
                'species' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Authenticated endpoints (require login)

        // Create submission endpoint
        register_rest_route($this->namespace, '/submissions', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_submission'),
            'permission_callback' => array($this, 'check_user_authenticated'),
            'args' => array(
                'species' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'date' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'category' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'wus' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'jagdbezirk' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'bemerkung' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'interne_notiz' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));

        // Get user's own submissions
        register_rest_route($this->namespace, '/submissions/my', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_submissions'),
            'permission_callback' => array($this, 'check_user_authenticated'),
            'args' => array(
                'page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // User profile endpoint
        register_rest_route($this->namespace, '/user/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_profile'),
            'permission_callback' => array($this, 'check_user_authenticated')
        ));

        // Export endpoint
        register_rest_route($this->namespace, '/export', array(
            'methods' => 'GET',
            'callback' => array($this, 'export_submissions'),
            'permission_callback' => array($this, 'check_user_authenticated'),
            'args' => array(
                'species' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'from_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'to_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'format' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'json',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }

    /**
     * Permission callback: Check if user is authenticated
     */
    public function check_user_authenticated() {
        return is_user_logged_in();
    }

    /**
     * Get plugin info
     *
     * GET /wp-json/ahgmh/v1/info
     */
    public function get_plugin_info($request) {
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'plugin_name' => 'Abschussplan HGMH',
                'plugin_version' => AHGMH_PLUGIN_VERSION,
                'api_version' => '1.0.0',
                'app_compatible' => true,
                'site_name' => get_bloginfo('name'),
                'site_url' => get_site_url(),
                'features' => array(
                    'submissions' => true,
                    'export' => true,
                    'notifications' => false // Not implemented yet
                )
            )
        ));
    }

    /**
     * Get species list
     *
     * GET /wp-json/ahgmh/v1/species
     */
    public function get_species_list($request) {
        $species_list = get_option('ahgmh_species', array('Rotwild', 'Damwild'));

        return rest_ensure_response(array(
            'success' => true,
            'data' => $species_list
        ));
    }

    /**
     * Get summary data
     *
     * GET /wp-json/ahgmh/v1/summary?species=Rotwild&meldegruppe=GruppeA
     */
    public function get_summary($request) {
        $species = $request->get_param('species');
        $meldegruppe = $request->get_param('meldegruppe');

        $database = abschussplan_hgmh()->database;
        $summary_data = $database->get_public_summary_data($species, $meldegruppe);

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'species' => $species,
                'meldegruppe' => $meldegruppe,
                'categories' => $summary_data['categories'],
                'limits' => $summary_data['limits'],
                'counts' => $summary_data['counts'],
                'allow_exceeding' => $summary_data['allow_exceeding']
            )
        ));
    }

    /**
     * Get submissions list (public)
     *
     * GET /wp-json/ahgmh/v1/submissions?species=Rotwild&page=1&per_page=20
     */
    public function get_submissions_list($request) {
        $species = $request->get_param('species');
        $meldegruppe = $request->get_param('meldegruppe');
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100); // Max 100 per page

        $database = abschussplan_hgmh()->database;
        $offset = ($page - 1) * $per_page;

        // Get submissions based on filters
        if (!empty($species) && !empty($meldegruppe)) {
            $submissions = $database->get_submissions_by_species_and_meldegruppe(
                $species,
                $meldegruppe,
                $per_page,
                $offset
            );
            $total_count = $database->count_submissions_by_species_and_meldegruppe($species, $meldegruppe);
        } elseif (!empty($species)) {
            $submissions = $database->get_submissions_by_species($per_page, $offset, $species);
            $total_count = $database->count_submissions_by_species($species);
        } else {
            $submissions = $database->get_submissions($per_page, $offset);
            $total_count = $database->count_submissions();
        }

        // Format submissions for API (remove sensitive data)
        $formatted_submissions = array_map(function($submission) {
            return array(
                'id' => intval($submission['id']),
                'species' => $submission['game_species'],
                'date' => $submission['field1'], // Abschussdatum
                'category' => $submission['field2'], // Kategorie
                'wus' => $submission['field3'], // WUS (optional)
                'jagdbezirk' => $submission['field5'], // Jagdbezirk
                'bemerkung' => $submission['field4'], // Bemerkung
                'created_at' => $submission['created_at']
                // Note: field6 (interne_notiz) is NOT exposed to public API
            );
        }, $submissions);

        $total_pages = ceil($total_count / $per_page);

        return rest_ensure_response(array(
            'success' => true,
            'data' => $formatted_submissions,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_count,
                'total_pages' => $total_pages
            )
        ));
    }

    /**
     * Get categories for a species
     *
     * GET /wp-json/ahgmh/v1/categories?species=Rotwild
     */
    public function get_categories($request) {
        $species = $request->get_param('species');

        if (empty($species)) {
            return new WP_Error(
                'missing_species',
                'Parameter "species" ist erforderlich',
                array('status' => 400)
            );
        }

        // Get categories for species
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());

        // Get limits for species
        $limits_key = 'abschuss_category_limits_' . sanitize_key($species);
        $limits = get_option($limits_key, array());

        // Get allow_exceeding settings
        $allow_exceeding_key = 'abschuss_category_allow_exceeding_' . sanitize_key($species);
        $allow_exceeding = get_option($allow_exceeding_key, array());

        // Get current counts
        $database = abschussplan_hgmh()->database;
        $counts = $database->get_category_counts($species, '');

        // Build response
        $categories_data = array();
        foreach ($categories as $category) {
            $categories_data[] = array(
                'name' => $category,
                'limit' => isset($limits[$category]) ? intval($limits[$category]) : 0,
                'current_count' => isset($counts[$category]) ? intval($counts[$category]) : 0,
                'allow_exceeding' => isset($allow_exceeding[$category]) ? (bool)$allow_exceeding[$category] : false,
                'is_at_limit' => isset($limits[$category]) && isset($counts[$category]) &&
                                 $counts[$category] >= $limits[$category] &&
                                 !$allow_exceeding[$category]
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'species' => $species,
                'categories' => $categories_data
            )
        ));
    }

    /**
     * Get jagdbezirke list
     *
     * GET /wp-json/ahgmh/v1/jagdbezirke
     */
    public function get_jagdbezirke($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ahgmh_jagdbezirke';

        // Get all active jagdbezirke
        $results = $wpdb->get_results(
            "SELECT jagdbezirk, meldegruppe, wildart, bemerkung
             FROM {$table_name}
             WHERE ungueltig = 0 AND active = 1
             ORDER BY meldegruppe, jagdbezirk",
            ARRAY_A
        );

        // Group by meldegruppe
        $grouped = array();
        foreach ($results as $row) {
            $meldegruppe = $row['meldegruppe'];
            if (!isset($grouped[$meldegruppe])) {
                $grouped[$meldegruppe] = array();
            }
            $grouped[$meldegruppe][] = array(
                'jagdbezirk' => $row['jagdbezirk'],
                'wildart' => $row['wildart'],
                'bemerkung' => $row['bemerkung']
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => $grouped
        ));
    }

    /**
     * Create new submission (authenticated)
     *
     * POST /wp-json/ahgmh/v1/submissions
     */
    public function create_submission($request) {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                'Sie müssen angemeldet sein',
                array('status' => 401)
            );
        }

        // Get parameters
        $species = $request->get_param('species');
        $date = $request->get_param('date');
        $category = $request->get_param('category');
        $wus = $request->get_param('wus');
        $jagdbezirk = $request->get_param('jagdbezirk');
        $bemerkung = $request->get_param('bemerkung');
        $interne_notiz = $request->get_param('interne_notiz');

        // Validate date (not in future)
        try {
            $selected_date = new DateTime($date);
            $tomorrow = new DateTime('tomorrow');
            $tomorrow->setTime(0, 0, 0);

            if ($selected_date >= $tomorrow) {
                return new WP_Error(
                    'invalid_date',
                    'Das Datum darf nicht in der Zukunft liegen',
                    array('status' => 400)
                );
            }
        } catch (Exception $e) {
            return new WP_Error(
                'invalid_date',
                'Ungültiges Datumsformat',
                array('status' => 400)
            );
        }

        // Validate category exists for species
        $categories_key = 'ahgmh_categories_' . sanitize_key($species);
        $categories = get_option($categories_key, array());

        if (!in_array($category, $categories)) {
            return new WP_Error(
                'invalid_category',
                'Ungültige Kategorie für diese Wildart',
                array('status' => 400)
            );
        }

        // Validate WUS if provided
        if (!empty($wus)) {
            if (!is_numeric($wus) || $wus < 1000000 || $wus > 9999999) {
                return new WP_Error(
                    'invalid_wus',
                    'WUS muss zwischen 1000000 und 9999999 liegen',
                    array('status' => 400)
                );
            }

            // Check if WUS already exists
            $database = abschussplan_hgmh()->database;
            if ($database->check_wus_exists($wus)) {
                return new WP_Error(
                    'duplicate_wus',
                    'Diese WUS-Nummer ist bereits vergeben',
                    array('status' => 400)
                );
            }
        }

        // Create submission
        $data = array(
            'user_id' => $user_id,
            'game_species' => $species,
            'field1' => $date,
            'field2' => $category,
            'field3' => $wus,
            'field4' => $bemerkung,
            'field5' => $jagdbezirk,
            'field6' => $interne_notiz
        );

        $database = abschussplan_hgmh()->database;
        $submission_id = $database->insert_submission($data);

        if ($submission_id) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Abschussmeldung erfolgreich gespeichert',
                'data' => array(
                    'submission_id' => $submission_id
                )
            ));
        } else {
            return new WP_Error(
                'submission_failed',
                'Fehler beim Speichern der Meldung',
                array('status' => 500)
            );
        }
    }

    /**
     * Get user's own submissions
     *
     * GET /wp-json/ahgmh/v1/submissions/my
     */
    public function get_my_submissions($request) {
        $user_id = get_current_user_id();
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100);

        global $wpdb;
        $database = abschussplan_hgmh()->database;
        $table_name = $database->get_table_name();

        $offset = ($page - 1) * $per_page;

        // Get user's submissions
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name}
                 WHERE user_id = %d
                 ORDER BY created_at DESC
                 LIMIT %d OFFSET %d",
                $user_id,
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
                $user_id
            )
        );

        // Format submissions
        $formatted_submissions = array_map(function($submission) {
            return array(
                'id' => intval($submission['id']),
                'species' => $submission['game_species'],
                'date' => $submission['field1'],
                'category' => $submission['field2'],
                'wus' => $submission['field3'],
                'jagdbezirk' => $submission['field5'],
                'bemerkung' => $submission['field4'],
                'interne_notiz' => $submission['field6'], // Visible to owner
                'created_at' => $submission['created_at']
            );
        }, $submissions);

        $total_pages = ceil($total_count / $per_page);

        return rest_ensure_response(array(
            'success' => true,
            'data' => $formatted_submissions,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => intval($total_count),
                'total_pages' => $total_pages
            )
        ));
    }

    /**
     * Get user profile
     *
     * GET /wp-json/ahgmh/v1/user/profile
     */
    public function get_user_profile($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error(
                'user_not_found',
                'Benutzer nicht gefunden',
                array('status' => 404)
            );
        }

        // Check if user is Vorstand (admin)
        $is_vorstand = AHGMH_Permissions_Service::is_vorstand($user_id);

        // Get user's wildarten assignments
        $wildarten = AHGMH_Permissions_Service::get_user_wildarten($user_id);

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'id' => $user_id,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'role' => $is_vorstand ? 'vorstand' : 'obmann',
                'wildarten' => $wildarten,
                'capabilities' => array(
                    'can_create_submissions' => true,
                    'can_export' => true,
                    'can_manage_settings' => $is_vorstand
                )
            )
        ));
    }

    /**
     * Export submissions
     *
     * GET /wp-json/ahgmh/v1/export?species=Rotwild&from_date=2024-01-01&format=json
     */
    public function export_submissions($request) {
        $user_id = get_current_user_id();

        $species = $request->get_param('species');
        $from_date = $request->get_param('from_date');
        $to_date = $request->get_param('to_date');
        $format = $request->get_param('format');

        $database = abschussplan_hgmh()->database;
        global $wpdb;
        $table_name = $database->get_table_name();

        // Build query
        $where_conditions = array();
        $params = array();

        if (!empty($species)) {
            $where_conditions[] = 'game_species = %s';
            $params[] = $species;
        }

        if (!empty($from_date)) {
            $where_conditions[] = 'DATE(field1) >= %s';
            $params[] = $from_date;
        }

        if (!empty($to_date)) {
            $where_conditions[] = 'DATE(field1) <= %s';
            $params[] = $to_date;
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        $query = "SELECT s.*, j.meldegruppe
                  FROM {$table_name} s
                  LEFT JOIN {$wpdb->prefix}ahgmh_jagdbezirke j ON s.field5 = j.jagdbezirk
                  {$where_clause}
                  ORDER BY s.created_at DESC";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $submissions = $wpdb->get_results($query, ARRAY_A);

        // Format submissions
        $formatted_submissions = array_map(function($submission) use ($user_id) {
            $user = get_userdata($submission['user_id']);
            return array(
                'id' => intval($submission['id']),
                'species' => $submission['game_species'],
                'date' => $submission['field1'],
                'category' => $submission['field2'],
                'wus' => $submission['field3'],
                'jagdbezirk' => $submission['field5'],
                'meldegruppe' => $submission['meldegruppe'],
                'bemerkung' => $submission['field4'],
                'created_by' => $user ? $user->display_name : 'Unbekannt',
                'created_at' => $submission['created_at']
            );
        }, $submissions);

        // Return as JSON (CSV generation can be done client-side)
        return rest_ensure_response(array(
            'success' => true,
            'data' => $formatted_submissions,
            'meta' => array(
                'total_records' => count($formatted_submissions),
                'filters' => array(
                    'species' => $species,
                    'from_date' => $from_date,
                    'to_date' => $to_date
                ),
                'exported_at' => current_time('mysql')
            )
        ));
    }
}

// Initialize REST API
new AHGMH_REST_API();
