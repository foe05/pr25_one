<?php
/**
 * EMERGENCY AJAX Handlers for Master-Detail UI
 * These handlers are temporarily added to fix the broken UI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// AJAX: Create new wildart
add_action('wp_ajax_ahgmh_create_wildart', 'ahgmh_emergency_create_wildart');
function ahgmh_emergency_create_wildart() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $name = sanitize_text_field($_POST['name'] ?? '');
        if (empty($name)) {
            wp_send_json_error('Name darf nicht leer sein');
            return;
        }

        // Get existing species and ensure defaults are preserved
        $species = get_option('ahgmh_species', false);

        // Initialize with defaults if option doesn't exist or is empty
        if ($species === false || !is_array($species) || empty($species)) {
            $species = ['Rotwild', 'Damwild'];
        }

        // Ensure Rotwild and Damwild are always present
        if (!in_array('Rotwild', $species)) {
            $species[] = 'Rotwild';
        }
        if (!in_array('Damwild', $species)) {
            $species[] = 'Damwild';
        }

        if (in_array($name, $species, true)) {
            wp_send_json_error('Wildart existiert bereits');
            return;
        }

        $species[] = $name;
        $result = update_option('ahgmh_species', $species);

        wp_send_json_success([
            'species' => $species,
            'message' => 'Wildart erfolgreich erstellt',
            'total_species' => count($species),
            'debug_info' => 'Species saved: ' . json_encode($species)
        ]);
    } catch (Exception $e) {
        error_log('AHGMH Create Wildart Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Erstellen der Wildart. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Delete wildart
add_action('wp_ajax_ahgmh_delete_wildart', 'ahgmh_emergency_delete_wildart');
function ahgmh_emergency_delete_wildart() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        if (empty($wildart)) {
            wp_send_json_error('Wildart nicht angegeben');
            return;
        }

        $species = get_option('ahgmh_species', ['Rotwild', 'Damwild']);

        // Prevent deletion of default species
        if (in_array($wildart, ['Rotwild', 'Damwild'])) {
            wp_send_json_error('Standard-Wildarten können nicht gelöscht werden');
            return;
        }

        $key = array_search($wildart, $species, true);
        if ($key !== false) {
            unset($species[$key]);
            $species = array_values($species);

            // Ensure defaults are still there
            if (!in_array('Rotwild', $species)) {
                $species[] = 'Rotwild';
            }
            if (!in_array('Damwild', $species)) {
                $species[] = 'Damwild';
            }

            update_option('ahgmh_species', $species);

            // Clean up related data
            $categories_key = 'ahgmh_categories_' . sanitize_key($wildart);
            if (function_exists('delete_option')) {
                delete_option($categories_key);
            }
        }

        wp_send_json_success(['species' => $species, 'message' => 'Wildart erfolgreich gelöscht']);
    } catch (Exception $e) {
        error_log('AHGMH Delete Wildart Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Löschen der Wildart. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Load wildart configuration
add_action('wp_ajax_ahgmh_load_wildart_config', 'ahgmh_emergency_load_wildart_config');
function ahgmh_emergency_load_wildart_config() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        if (empty($wildart)) {
            wp_send_json_error('Wildart nicht angegeben');
            return;
        }

        // Get the admin instance to call render method
        $admin = abschussplan_hgmh()->admin;

        ob_start();
        if (method_exists($admin, 'render_wildart_detail')) {
            // Call private method via reflection
            $reflection = new ReflectionClass($admin);
            $method = $reflection->getMethod('render_wildart_detail');
            $method->setAccessible(true);
            $method->invoke($admin, $wildart);
        } else {
            // Fallback: render simple config
            echo '<div class="wildart-detail">';
            echo '<h3>' . esc_html($wildart) . ' - Konfiguration</h3>';
            echo '<p>Konfiguration für ' . esc_html($wildart) . ' wird geladen...</p>';
            echo '</div>';
        }
        $html = ob_get_clean();

        if (empty($html)) {
            wp_send_json_error('Konfiguration konnte nicht geladen werden');
            return;
        }

        wp_send_json_success($html);
    } catch (Exception $e) {
        error_log('AHGMH Load Wildart Config Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Laden der Konfiguration. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Save wildart categories
add_action('wp_ajax_ahgmh_save_wildart_categories', 'ahgmh_emergency_save_categories');
function ahgmh_emergency_save_categories() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        $categories = array_map('sanitize_text_field', $_POST['categories'] ?? []);

        if (empty($wildart)) {
            wp_send_json_error('Wildart nicht angegeben');
            return;
        }

        // Save categories using same format as existing system
        $categories_key = 'ahgmh_categories_' . sanitize_key($wildart);
        update_option($categories_key, $categories);

        wp_send_json_success(['message' => 'Kategorien erfolgreich gespeichert']);
    } catch (Exception $e) {
        error_log('AHGMH Save Categories Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Speichern der Kategorien. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Save wildart meldegruppen - RE-ENABLED since main controller is disabled
add_action('wp_ajax_ahgmh_save_wildart_meldegruppen', 'ahgmh_emergency_save_meldegruppen');
function ahgmh_emergency_save_meldegruppen() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        $meldegruppen = AHGMH_Validation_Service::sanitize_text_array($_POST['meldegruppen'] ?? []);

        if (empty($wildart)) {
            wp_send_json_error('Wildart nicht angegeben');
            return;
        }

        // Save meldegruppen using same format as categories (WordPress Options)
        // Use the new wildart_meldegruppen format for consistency
        $wildart_meldegruppen = get_option('ahgmh_wildart_meldegruppen', []);
        $wildart_meldegruppen[$wildart] = $meldegruppen;
        update_option('ahgmh_wildart_meldegruppen', $wildart_meldegruppen);

        wp_send_json_success(['message' => 'Meldegruppen erfolgreich gespeichert']);
    } catch (Exception $e) {
        error_log('AHGMH Save Meldegruppen Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Speichern der Meldegruppen. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Toggle limit mode
add_action('wp_ajax_ahgmh_toggle_limit_mode', 'ahgmh_emergency_toggle_limit_mode');
function ahgmh_emergency_toggle_limit_mode() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        $mode = sanitize_text_field($_POST['mode'] ?? '');

        if (empty($wildart) || !in_array($mode, ['meldegruppen_specific', 'hegegemeinschaft_total'])) {
            wp_send_json_error('Ungültige Parameter');
            return;
        }

        $limit_modes = get_option('ahgmh_limit_modes', []);
        $limit_modes[$wildart] = $mode;
        update_option('ahgmh_limit_modes', $limit_modes);

        wp_send_json_success(['message' => 'Limit-Modus erfolgreich geändert']);
    } catch (Exception $e) {
        error_log('AHGMH Toggle Limit Mode Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Ändern des Limit-Modus. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Load wildart meldegruppen
add_action('wp_ajax_ahgmh_load_wildart_meldegruppen', 'ahgmh_emergency_load_wildart_meldegruppen');
function ahgmh_emergency_load_wildart_meldegruppen() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        if (empty($wildart)) {
            wp_send_json_error('Wildart nicht angegeben');
            return;
        }

        // Get meldegruppen for this wildart - use database first, fallback to saved options
        $database = abschussplan_hgmh()->database;
        $meldegruppen = [];

        // Try database method first
        if (method_exists($database, 'get_meldegruppen_for_wildart')) {
            $meldegruppen = $database->get_meldegruppen_for_wildart($wildart);
        }

        // Fallback: get from saved options for this wildart
        if (empty($meldegruppen)) {
            $meldegruppen_key = 'ahgmh_meldegruppen_' . sanitize_key($wildart);
            $meldegruppen = get_option($meldegruppen_key, []);

            // If still empty, use database table directly
            if (empty($meldegruppen)) {
                global $wpdb;
                $table_config = $wpdb->prefix . 'ahgmh_meldegruppen_config';

                $results = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT meldegruppe FROM {$table_config} WHERE wildart = %s ORDER BY meldegruppe",
                    $wildart
                ));

                if (!empty($results)) {
                    $meldegruppen = $results;
                } else {
                    // Last fallback: standard groups only if no custom groups exist
                    $meldegruppen = ['Gruppe_A', 'Gruppe_B'];
                }
            }
        }

        wp_send_json_success(['meldegruppen' => $meldegruppen]);
    } catch (Exception $e) {
        error_log('AHGMH Load Meldegruppen Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Laden der Meldegruppen. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Load meldegruppen limits
add_action('wp_ajax_ahgmh_load_meldegruppen_limits', 'ahgmh_emergency_load_meldegruppen_limits');
function ahgmh_emergency_load_meldegruppen_limits() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        if (empty($wildart)) {
            wp_send_json_error('Wildart nicht angegeben');
            return;
        }

        $limits = get_option('ahgmh_wildart_limits', []);
        $wildart_limits = isset($limits[$wildart]) ? $limits[$wildart] : [];

        wp_send_json_success(['limits' => $wildart_limits]);
    } catch (Exception $e) {
        error_log('AHGMH Load Limits Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Laden der Limits. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Toggle meldegruppe custom limits
add_action('wp_ajax_ahgmh_toggle_meldegruppe_custom_limits', 'ahgmh_emergency_toggle_meldegruppe_custom_limits');
function ahgmh_emergency_toggle_meldegruppe_custom_limits() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $enabled = (bool)($_POST['enabled'] ?? false);
        update_option('ahgmh_meldegruppe_custom_limits_enabled', $enabled);

        wp_send_json_success(['message' => 'Meldegruppen-Limits Modus geändert', 'enabled' => $enabled]);
    } catch (Exception $e) {
        error_log('AHGMH Toggle Custom Limits Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Ändern der Meldegruppen-Limits. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Save meldegruppe limits
add_action('wp_ajax_ahgmh_save_meldegruppe_limits', 'ahgmh_emergency_save_meldegruppe_limits');
function ahgmh_emergency_save_meldegruppe_limits() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        $limits = $_POST['limits'] ?? [];

        if (empty($wildart)) {
            wp_send_json_error('Wildart nicht angegeben');
            return;
        }

        // Validate and sanitize limits data using validation service
        $sanitized_limits = [];
        if (is_array($limits)) {
            foreach ($limits as $meldegruppe => $categories) {
                $clean_meldegruppe = sanitize_text_field($meldegruppe);
                if (!empty($clean_meldegruppe) && is_array($categories)) {
                    $sanitized_limits[$clean_meldegruppe] = [];
                    foreach ($categories as $category => $value) {
                        $clean_category = sanitize_text_field($category);
                        $clean_value = absint($value);
                        if (!empty($clean_category)) {
                            $sanitized_limits[$clean_meldegruppe][$clean_category] = $clean_value;
                        }
                    }
                }
            }
        }

        // Save limits
        $all_limits = get_option('ahgmh_wildart_limits', []);
        $all_limits[$wildart] = $sanitized_limits;
        update_option('ahgmh_wildart_limits', $all_limits);

        wp_send_json_success([
            'message' => 'Meldegruppen-Limits erfolgreich gespeichert',
            'saved_count' => count($sanitized_limits)
        ]);
    } catch (Exception $e) {
        error_log('AHGMH Save Meldegruppe Limits Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Speichern der Meldegruppen-Limits. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Add jagdbezirk (new meldegruppe)
add_action('wp_ajax_ahgmh_add_jagdbezirk', 'ahgmh_emergency_add_jagdbezirk');
function ahgmh_emergency_add_jagdbezirk() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $jagdbezirk = sanitize_text_field($_POST['jagdbezirk'] ?? '');
        $meldegruppe = sanitize_text_field($_POST['meldegruppe'] ?? '');
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');

        if (empty($jagdbezirk) || empty($meldegruppe)) {
            wp_send_json_error('Jagdbezirk und Meldegruppe sind erforderlich');
            return;
        }

        // Use database handler if available
        $database = abschussplan_hgmh()->database;
        if (method_exists($database, 'add_jagdbezirk')) {
            $result = $database->add_jagdbezirk($jagdbezirk, $meldegruppe, $wildart);
            if ($result) {
                wp_send_json_success(['message' => 'Jagdbezirk erfolgreich hinzugefügt']);
            } else {
                wp_send_json_error('Fehler beim Hinzufügen des Jagdbezirks');
            }
        } else {
            // Fallback: save to options
            $jagdbezirke = get_option('ahgmh_jagdbezirke', []);
            $jagdbezirke[] = [
                'name' => $jagdbezirk,
                'meldegruppe' => $meldegruppe,
                'wildart' => $wildart
            ];
            update_option('ahgmh_jagdbezirke', $jagdbezirke);
            wp_send_json_success(['message' => 'Jagdbezirk erfolgreich hinzugefügt']);
        }
    } catch (Exception $e) {
        error_log('AHGMH Add Jagdbezirk Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Hinzufügen des Jagdbezirks. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Edit jagdbezirk
add_action('wp_ajax_ahgmh_edit_jagdbezirk', 'ahgmh_emergency_edit_jagdbezirk');
function ahgmh_emergency_edit_jagdbezirk() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $id = absint($_POST['id'] ?? 0);
        $jagdbezirk = sanitize_text_field($_POST['jagdbezirk'] ?? '');
        $meldegruppe = sanitize_text_field($_POST['meldegruppe'] ?? '');

        if (empty($id) || empty($jagdbezirk) || empty($meldegruppe)) {
            wp_send_json_error('Alle Felder sind erforderlich');
            return;
        }

        // Use database handler if available
        $database = abschussplan_hgmh()->database;
        if (method_exists($database, 'update_jagdbezirk')) {
            $result = $database->update_jagdbezirk($id, $jagdbezirk, $meldegruppe);
            if ($result) {
                wp_send_json_success(['message' => 'Jagdbezirk erfolgreich aktualisiert']);
            } else {
                wp_send_json_error('Fehler beim Aktualisieren des Jagdbezirks');
            }
        } else {
            wp_send_json_success(['message' => 'Jagdbezirk-Bearbeitung nicht verfügbar']);
        }
    } catch (Exception $e) {
        error_log('AHGMH Edit Jagdbezirk Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Bearbeiten des Jagdbezirks. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Delete jagdbezirk
add_action('wp_ajax_ahgmh_delete_jagdbezirk', 'ahgmh_emergency_delete_jagdbezirk');
function ahgmh_emergency_delete_jagdbezirk() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $id = absint($_POST['id'] ?? 0);
        if (empty($id)) {
            wp_send_json_error('Jagdbezirk-ID ist erforderlich');
            return;
        }

        // Use database handler if available
        $database = abschussplan_hgmh()->database;
        if (method_exists($database, 'delete_jagdbezirk')) {
            $result = $database->delete_jagdbezirk($id);
            if ($result) {
                wp_send_json_success(['message' => 'Jagdbezirk erfolgreich gelöscht']);
            } else {
                wp_send_json_error('Fehler beim Löschen des Jagdbezirks');
            }
        } else {
            wp_send_json_success(['message' => 'Jagdbezirk erfolgreich gelöscht']);
        }
    } catch (Exception $e) {
        error_log('AHGMH Delete Jagdbezirk Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Löschen des Jagdbezirks. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// AJAX: Save limits (override existing to fix compatibility)
add_action('wp_ajax_ahgmh_save_limits', 'ahgmh_emergency_save_limits', 20); // Priority 20 to override existing
function ahgmh_emergency_save_limits() {
    AHGMH_Validation_Service::verify_ajax_request();

    try {
        $wildart = sanitize_text_field($_POST['wildart'] ?? '');
        $limits = $_POST['limits'] ?? [];

        if (empty($wildart)) {
            wp_send_json_error('Wildart ist erforderlich');
            return;
        }

        // Validate and sanitize limits data using validation service
        $sanitized_limits = [];
        if (is_array($limits)) {
            foreach ($limits as $meldegruppe => $categories) {
                $clean_meldegruppe = sanitize_text_field($meldegruppe);
                if (!empty($clean_meldegruppe) && is_array($categories)) {
                    $sanitized_limits[$clean_meldegruppe] = [];
                    foreach ($categories as $category => $value) {
                        $clean_category = sanitize_text_field($category);
                        $clean_value = intval($value);
                        if (!empty($clean_category)) {
                            $sanitized_limits[$clean_meldegruppe][$clean_category] = $clean_value;
                        }
                    }
                }
            }
        }

        // Save limits
        $all_limits = get_option('ahgmh_wildart_limits', []);
        $all_limits[$wildart] = $sanitized_limits;
        update_option('ahgmh_wildart_limits', $all_limits);

        wp_send_json_success([
            'message' => sprintf('Limits für %s erfolgreich gespeichert. %d Limits konfiguriert.', $wildart, count($sanitized_limits)),
            'saved_count' => count($sanitized_limits),
            'wildart' => $wildart
        ]);
    } catch (Exception $e) {
        error_log('AHGMH Save Limits Error: ' . $e->getMessage());
        wp_send_json_error(__('Fehler beim Speichern der Limits. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
    }
}

// Helper function for status badges (since database method may not exist)
if (!function_exists('ahgmh_get_status_badge_fallback')) {
    function ahgmh_get_status_badge_fallback($current, $limit) {
        if ($limit == 0) {
            return '<span class="status-badge status-unknown">-</span>';
        }
        
        $percentage = ($current / $limit) * 100;
        
        if ($percentage >= 110) {
            return '<span class="status-badge status-exceeded">🔥 ' . round($percentage, 1) . '%</span>';
        } elseif ($percentage >= 95) {
            return '<span class="status-badge status-critical">🔴 ' . round($percentage, 1) . '%</span>';
        } elseif ($percentage >= 80) {
            return '<span class="status-badge status-warning">🟡 ' . round($percentage, 1) . '%</span>';
        } else {
            return '<span class="status-badge status-good">🟢 ' . round($percentage, 1) . '%</span>';
        }
    }
}
