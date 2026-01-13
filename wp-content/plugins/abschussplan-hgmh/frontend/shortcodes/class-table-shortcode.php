<?php
/**
 * Table Shortcode Class
 * Provides [abschuss_table] shortcode with moderation capabilities
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for displaying submissions table with moderation actions
 */
class AHGMH_Table_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode for displaying moderation table
        add_shortcode('abschuss_table', array($this, 'render_table'));

        // Register AJAX handlers for moderation actions
        $this->register_ajax_handlers();

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_table_approve', array($this, 'ajax_approve_submission'));
        add_action('wp_ajax_ahgmh_table_reject', array($this, 'ajax_reject_submission'));
        add_action('wp_ajax_ahgmh_table_update', array($this, 'ajax_update_submission'));
    }

    /**
     * Enqueue scripts and styles for the table
     */
    public function enqueue_scripts() {
        // Only enqueue if shortcode is present on the page
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'abschuss_table')) {
            return;
        }

        // Enqueue Bootstrap if not already loaded (check if theme/plugin already loads it)
        // Note: Many WordPress themes already include Bootstrap

        // Enqueue our custom JavaScript for table moderation
        wp_enqueue_script(
            'ahgmh-table-moderation',
            AHGMH_PLUGIN_URL . 'frontend/assets/js/table-moderation.js',
            array('jquery'),
            AHGMH_PLUGIN_VERSION,
            true
        );

        // Pass data to JavaScript
        wp_localize_script('ahgmh-table-moderation', 'ahgmh_table_moderation', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ahgmh_table_moderation_nonce'),
            'strings' => array(
                'confirm_approve' => __('Möchten Sie diese Meldung wirklich freigeben?', 'abschussplan-hgmh'),
                'confirm_reject' => __('Möchten Sie diese Meldung wirklich ablehnen?', 'abschussplan-hgmh'),
                'error_comment_required' => __('Bitte geben Sie einen Kommentar ein.', 'abschussplan-hgmh'),
                'success_approved' => __('Meldung erfolgreich freigegeben.', 'abschussplan-hgmh'),
                'success_rejected' => __('Meldung erfolgreich abgelehnt.', 'abschussplan-hgmh'),
                'success_updated' => __('Meldung erfolgreich aktualisiert.', 'abschussplan-hgmh'),
                'error_generic' => __('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
            )
        ));
    }

    /**
     * Render the moderation table using shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output of the table
     */
    public function render_table($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'limit' => 20,
                'page' => 1,
                'species' => '', // Optional: filter by species
                'status' => 'email_verified,pending_approval', // Default: show moderatable statuses
            ),
            $atts,
            'abschuss_table'
        );

        // Check if user has moderation permissions
        $can_moderate = current_user_can('manage_options');

        // Get pagination parameters
        $limit = isset($_GET['ahgmh_limit']) ? max(1, intval($_GET['ahgmh_limit'])) : intval($atts['limit']);
        $page = isset($_GET['ahgmh_page']) ? max(1, intval($_GET['ahgmh_page'])) : max(1, intval($atts['page']));
        $offset = ($page - 1) * $limit;

        // Get database instance
        $database = abschussplan_hgmh()->database;

        // Get submissions
        // Note: The database doesn't have status filtering yet (Spec 005 not implemented)
        // For now, get all submissions - will be enhanced when status field is added
        $submissions = $database->get_submissions($limit, $offset);

        // Get total count
        $total_submissions = $database->count_submissions();

        // Calculate pagination
        $total_pages = ceil($total_submissions / $limit);

        // Start output buffer
        ob_start();

        // Include template
        include AHGMH_PLUGIN_DIR . 'frontend/templates/table.php';

        // Return the output
        return ob_get_clean();
    }

    /**
     * AJAX handler for approving a submission
     */
    public function ajax_approve_submission() {
        // Verify AJAX request with nonce and capability
        AHGMH_Validation_Service::verify_ajax_request('ahgmh_table_moderation_nonce', 'manage_options');

        try {
            // Get submission ID
            $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;

            if ($submission_id <= 0) {
                wp_send_json_error(array(
                    'message' => __('Ungültige Meldungs-ID.', 'abschussplan-hgmh')
                ));
            }

            // Call moderation service to approve submission
            $moderation_service = new AHGMH_Moderation_Service();
            $result = $moderation_service->approve_submission($submission_id);

            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Meldung erfolgreich freigegeben.', 'abschussplan-hgmh'),
                    'submission_id' => $submission_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Fehler beim Freigeben der Meldung. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
                ));
            }

        } catch (Exception $e) {
            error_log('AHGMH Table Approve Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Fehler beim Freigeben der Meldung. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
            ));
        }
    }

    /**
     * AJAX handler for rejecting a submission
     */
    public function ajax_reject_submission() {
        // Verify AJAX request with nonce and capability
        AHGMH_Validation_Service::verify_ajax_request('ahgmh_table_moderation_nonce', 'manage_options');

        try {
            // Get submission ID and rejection comment
            $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
            $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';

            if ($submission_id <= 0) {
                wp_send_json_error(array(
                    'message' => __('Ungültige Meldungs-ID.', 'abschussplan-hgmh')
                ));
            }

            // Validate that comment is provided
            if (empty($comment)) {
                wp_send_json_error(array(
                    'message' => __('Bitte geben Sie einen Kommentar für die Ablehnung ein.', 'abschussplan-hgmh')
                ));
            }

            // Call moderation service to reject submission
            $moderation_service = new AHGMH_Moderation_Service();
            $result = $moderation_service->reject_submission($submission_id, $comment);

            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Meldung erfolgreich abgelehnt.', 'abschussplan-hgmh'),
                    'submission_id' => $submission_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Fehler beim Ablehnen der Meldung. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
                ));
            }

        } catch (Exception $e) {
            error_log('AHGMH Table Reject Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Fehler beim Ablehnen der Meldung. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
            ));
        }
    }

    /**
     * AJAX handler for updating a submission
     */
    public function ajax_update_submission() {
        // Verify AJAX request with nonce and capability
        AHGMH_Validation_Service::verify_ajax_request('ahgmh_table_moderation_nonce', 'manage_options');

        try {
            // Get submission ID
            $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;

            if ($submission_id <= 0) {
                wp_send_json_error(array(
                    'message' => __('Ungültige Meldungs-ID.', 'abschussplan-hgmh')
                ));
            }

            // Sanitize and validate form data
            // Based on database schema: game_species, field1-field6
            $data = array();

            // Game species (optional in update, might not be changed)
            if (isset($_POST['game_species'])) {
                $data['game_species'] = sanitize_text_field($_POST['game_species']);
            }

            // Field1 (e.g., date, sex, or other submission data)
            if (isset($_POST['field1'])) {
                $data['field1'] = sanitize_text_field($_POST['field1']);
            }

            // Field2 (e.g., category)
            if (isset($_POST['field2'])) {
                $data['field2'] = sanitize_text_field($_POST['field2']);
            }

            // Field3 (e.g., WUS number)
            if (isset($_POST['field3'])) {
                $data['field3'] = sanitize_text_field($_POST['field3']);
            }

            // Field4 (e.g., meldegruppe)
            if (isset($_POST['field4'])) {
                $data['field4'] = sanitize_text_field($_POST['field4']);
            }

            // Field5 (e.g., jagdbezirk)
            if (isset($_POST['field5'])) {
                $data['field5'] = sanitize_text_field($_POST['field5']);
            }

            // Field6 (e.g., notes/comments - optional)
            if (isset($_POST['field6'])) {
                $data['field6'] = sanitize_textarea_field($_POST['field6']);
            }

            // Validate that at least one field is being updated
            if (empty($data)) {
                wp_send_json_error(array(
                    'message' => __('Keine Änderungen zum Speichern vorhanden.', 'abschussplan-hgmh')
                ));
            }

            // Call moderation service to update submission
            $moderation_service = new AHGMH_Moderation_Service();
            $result = $moderation_service->update_submission($submission_id, $data);

            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => __('Meldung erfolgreich aktualisiert.', 'abschussplan-hgmh'),
                    'submission_id' => $submission_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Fehler beim Aktualisieren der Meldung.', 'abschussplan-hgmh')
                ));
            }

        } catch (Exception $e) {
            error_log('AHGMH Table Update Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Fehler beim Aktualisieren der Meldung. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')
            ));
        }
    }

    /**
     * Generate pagination HTML
     *
     * @param int $current_page Current page number
     * @param int $total_pages Total number of pages
     * @param int $limit Items per page
     * @return string Pagination HTML
     */
    public function pagination_html($current_page, $total_pages, $limit) {
        if ($total_pages <= 1) {
            return '';
        }

        $output = '<nav aria-label="' . esc_attr__('Meldungen Navigation', 'abschussplan-hgmh') . '">';
        $output .= '<ul class="pagination justify-content-center">';

        // Previous page link
        $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
        $output .= '<li class="page-item ' . $prev_disabled . '">';
        if ($current_page > 1) {
            $output .= '<a class="page-link" href="' . esc_url(add_query_arg(array('ahgmh_page' => $current_page - 1, 'ahgmh_limit' => $limit))) . '">&laquo; ' . esc_html__('Zurück', 'abschussplan-hgmh') . '</a>';
        } else {
            $output .= '<span class="page-link">&laquo; ' . esc_html__('Zurück', 'abschussplan-hgmh') . '</span>';
        }
        $output .= '</li>';

        // Page links
        $range = 2; // Number of pages to show on each side of current page

        // Start range
        $start = max(1, $current_page - $range);

        // End range
        $end = min($total_pages, $current_page + $range);

        // Show first page if not in range
        if ($start > 1) {
            $output .= '<li class="page-item"><a class="page-link" href="' . esc_url(add_query_arg(array('ahgmh_page' => 1, 'ahgmh_limit' => $limit))) . '">1</a></li>';
            if ($start > 2) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Page numbers
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $current_page) ? 'active' : '';
            $output .= '<li class="page-item ' . $active . '">';
            if ($i == $current_page) {
                $output .= '<span class="page-link">' . $i . '</span>';
            } else {
                $output .= '<a class="page-link" href="' . esc_url(add_query_arg(array('ahgmh_page' => $i, 'ahgmh_limit' => $limit))) . '">' . $i . '</a>';
            }
            $output .= '</li>';
        }

        // Show last page if not in range
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $output .= '<li class="page-item"><a class="page-link" href="' . esc_url(add_query_arg(array('ahgmh_page' => $total_pages, 'ahgmh_limit' => $limit))) . '">' . $total_pages . '</a></li>';
        }

        // Next page link
        $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        $output .= '<li class="page-item ' . $next_disabled . '">';
        if ($current_page < $total_pages) {
            $output .= '<a class="page-link" href="' . esc_url(add_query_arg(array('ahgmh_page' => $current_page + 1, 'ahgmh_limit' => $limit))) . '">' . esc_html__('Weiter', 'abschussplan-hgmh') . ' &raquo;</a>';
        } else {
            $output .= '<span class="page-link">' . esc_html__('Weiter', 'abschussplan-hgmh') . ' &raquo;</span>';
        }
        $output .= '</li>';

        $output .= '</ul>';
        $output .= '</nav>';

        return $output;
    }
}
