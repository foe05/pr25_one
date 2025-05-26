<?php
/**
 * Admin Page Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling admin pages and settings
 */
class AHGMH_Admin_Page {
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Abschussplan HGMH', 'abschussplan-hgmh'),
            __('Abschussplan', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh',
            array($this, 'render_summary_page'),
            'dashicons-chart-pie',
            30
        );
        
        add_submenu_page(
            'abschussplan-hgmh',
            __('Übersicht', 'abschussplan-hgmh'),
            __('Übersicht', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh',
            array($this, 'render_summary_page')
        );
        
        add_submenu_page(
            'abschussplan-hgmh',
            __('Abschussmeldungen', 'abschussplan-hgmh'),
            __('Meldungen', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-submissions',
            array($this, 'render_submissions_page')
        );
        
        add_submenu_page(
            'abschussplan-hgmh',
            __('Abschussplanung', 'abschussplan-hgmh'),
            __('Abschussplanung', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-admin',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'abschussplan-hgmh',
            __('Kategorien verwalten', 'abschussplan-hgmh'),
            __('Kategorien', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-categories',
            array($this, 'render_categories_page')
        );
        
        add_submenu_page(
            'abschussplan-hgmh',
            __('Wildarten verwalten', 'abschussplan-hgmh'),
            __('Wildarten', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-species',
            array($this, 'render_species_page')
        );
        
        add_submenu_page(
            'abschussplan-hgmh',
            __('Über', 'abschussplan-hgmh'),
            __('Über', 'abschussplan-hgmh'),
            'manage_options',
            'abschussplan-hgmh-settings',
            array($this, 'render_about_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Settings removed - no longer needed
    }

    /**
     * Sanitize settings
     *
     * @param array $input Settings input
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['field1_label'] = sanitize_text_field($input['field1_label']);
        $sanitized['field2_label'] = sanitize_text_field($input['field2_label']);
        $sanitized['field3_label'] = sanitize_text_field($input['field3_label']);
        $sanitized['field4_label'] = sanitize_text_field($input['field4_label']);
        $sanitized['entries_per_page'] = intval($input['entries_per_page']);
        
        return $sanitized;
    }

    /**
     * Render section description
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure the form fields and display settings.', 'custom-form-display') . '</p>';
    }

    /**
     * Render text field
     *
     * @param array $args Field arguments
     */
    public function render_text_field($args) {
        $options = get_option('custom_form_display_options', array());
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        
        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="custom_form_display_options[' . esc_attr($args['label_for']) . ']" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Render number field
     *
     * @param array $args Field arguments
     */
    public function render_number_field($args) {
        $options = get_option('custom_form_display_options', array());
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        
        echo '<input type="number" id="' . esc_attr($args['label_for']) . '" name="custom_form_display_options[' . esc_attr($args['label_for']) . ']" value="' . esc_attr($value) . '" min="' . esc_attr($args['min']) . '" max="' . esc_attr($args['max']) . '" class="small-text">';
    }

    /**
     * Render main admin page
     */
    public function render_main_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Abschussplan HGMH', 'abschussplan-hgmh'); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__('Anweisungen', 'abschussplan-hgmh'); ?></h2>
                <div class="card-body">
                    <p><?php echo esc_html__('Verwenden Sie die folgenden Shortcodes, um das Formular und die Tabellen auf Ihren Seiten anzuzeigen:', 'abschussplan-hgmh'); ?></p>
                    
                    <h3><?php echo esc_html__('Formular Shortcode', 'abschussplan-hgmh'); ?></h3>
                    <code>[abschuss_form]</code>
                    <p class="description"><?php echo esc_html__('Fügen Sie diesen Shortcode zu jeder Seite hinzu, auf der das Formular erscheinen soll.', 'abschussplan-hgmh'); ?></p>
                    
                    <h3><?php echo esc_html__('Tabellen Shortcode', 'abschussplan-hgmh'); ?></h3>
                    <code>[abschuss_table]</code>
                    <p class="description"><?php echo esc_html__('Zeigt die Tabelle der Abschussmeldungen an.', 'abschussplan-hgmh'); ?></p>
                    
                    <h3><?php echo esc_html__('Zusammenfassung Shortcode', 'abschussplan-hgmh'); ?></h3>
                    <code>[abschuss_summary]</code>
                    <p class="description"><?php echo esc_html__('Zeigt eine Zusammenfassung der Abschusszahlen an.', 'abschussplan-hgmh'); ?></p>
                    
                    <h3><?php echo esc_html__('Admin Shortcode', 'abschussplan-hgmh'); ?></h3>
                    <code>[abschuss_admin]</code>
                    <p class="description"><?php echo esc_html__('Zeigt die Admin-Konfiguration an.', 'abschussplan-hgmh'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        // Handle bulk actions first
        $this->handle_bulk_actions();
        
        // Get current page
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Get species filter
        $selected_species = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : '';
        
        // Get settings
        $options = get_option('custom_form_display_options', array());
        $limit = isset($options['entries_per_page']) ? intval($options['entries_per_page']) : 10;
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Get database instance
        $database = abschussplan_hgmh()->database;
        
        // Get available species
        $available_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        // Get submissions (filtered by species if selected)
        if (!empty($selected_species)) {
            $submissions = $database->get_submissions_by_species($limit, $offset, $selected_species);
            $total_submissions = $database->count_submissions_by_species($selected_species);
        } else {
            $submissions = $database->get_submissions($limit, $offset);
            $total_submissions = $database->count_submissions();
        }
        
        // Calculate pagination
        $total_pages = ceil($total_submissions / $limit);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Abschussmeldungen', 'abschussplan-hgmh'); ?></h1>
            
            <!-- Species Filter and Actions -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" style="display: inline-block;">
                        <input type="hidden" name="page" value="abschussplan-hgmh-submissions" />
                        <select name="species" id="species-filter">
                            <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                            <?php foreach ($available_species as $species) : ?>
                                <option value="<?php echo esc_attr($species); ?>" <?php selected($species, $selected_species); ?>>
                                    <?php echo esc_html($species); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button" value="<?php echo esc_attr__('Filtern', 'abschussplan-hgmh'); ?>" />
                    </form>
                    
                    <?php if (!empty($submissions)) : ?>
                        <form method="post" style="display: inline-block; margin-left: 10px;">
                            <?php wp_nonce_field('bulk_delete_submissions', 'bulk_delete_nonce'); ?>
                            <input type="hidden" name="species" value="<?php echo esc_attr($selected_species); ?>" />
                            <input type="hidden" name="action" value="delete_all" />
                            <input type="submit" class="button button-secondary" 
                                   value="<?php echo esc_attr(!empty($selected_species) ? sprintf(__('Alle %s Meldungen löschen', 'abschussplan-hgmh'), $selected_species) : __('Alle Meldungen löschen', 'abschussplan-hgmh')); ?>"
                                   onclick="return confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie alle Meldungen löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.', 'abschussplan-hgmh')); ?>')" />
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($submissions)) : ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html__('No submissions found.', 'custom-form-display'); ?></p>
                </div>
            <?php else : ?>
                <div class="tablenav top">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php echo sprintf(_n('%s item', '%s items', $total_submissions, 'custom-form-display'), number_format_i18n($total_submissions)); ?>
                        </span>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;', 'custom-form-display'),
                            'next_text' => __('&raquo;', 'custom-form-display'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-id"><?php echo esc_html__('ID', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-species"><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php echo esc_html__('Abschussdatum', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-category"><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-wus"><?php echo esc_html__('WUS', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-remark"><?php echo esc_html__('Bemerkung', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-user"><?php echo esc_html__('Erstellt von', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-created"><?php echo esc_html__('Erstellt am', 'abschussplan-hgmh'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission) : ?>
                            <tr>
                                <td class="column-id"><?php echo esc_html($submission['id']); ?></td>
                                <td class="column-species"><?php echo esc_html($submission['game_species'] ?? 'Rotwild'); ?></td>
                                <td class="column-date">
                                    <?php 
                                    if (!empty($submission['field1'])) {
                                        echo esc_html(date_i18n(get_option('date_format'), strtotime($submission['field1'])));
                                    }
                                    ?>
                                </td>
                                <td class="column-category"><?php echo esc_html($submission['field2']); ?></td>
                                <td class="column-wus"><?php echo esc_html($submission['field3']); ?></td>
                                <td class="column-remark"><?php echo esc_html($submission['field4']); ?></td>
                                <td class="column-user">
                                    <?php 
                                    if (isset($submission['user_id']) && $submission['user_id'] > 0) {
                                        $user = get_user_by('id', $submission['user_id']);
                                        if ($user) {
                                            $first_name = get_user_meta($user->ID, 'first_name', true);
                                            $last_name = get_user_meta($user->ID, 'last_name', true);
                                            
                                            if (!empty($first_name) && !empty($last_name)) {
                                                echo esc_html(trim($first_name . ' ' . $last_name));
                                            } elseif (!empty($first_name)) {
                                                echo esc_html($first_name);
                                            } elseif (!empty($last_name)) {
                                                echo esc_html($last_name);
                                            } else {
                                                echo esc_html($user->display_name);
                                            }
                                        } else {
                                            echo esc_html__('Unbekannter Benutzer', 'abschussplan-hgmh');
                                        }
                                    } else {
                                        echo esc_html__('Kein Name verfügbar', 'abschussplan-hgmh');
                                    }
                                    ?>
                                </td>
                                <td class="column-created"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission['created_at']))); ?></td>
                                <td class="column-actions">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=abschussplan-hgmh-submissions&action=delete&id=' . $submission['id']), 'delete_submission_' . $submission['id']); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie diese Meldung löschen möchten?', 'abschussplan-hgmh')); ?>')">
                                        <?php echo esc_html__('Löschen', 'abschussplan-hgmh'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php echo sprintf(_n('%s item', '%s items', $total_submissions, 'custom-form-display'), number_format_i18n($total_submissions)); ?>
                        </span>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;', 'custom-form-display'),
                            'next_text' => __('&raquo;', 'custom-form-display'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        // Handle submission deletion
        $this->handle_submission_deletion();
    }

    /**
     * Render about page (formerly settings page)
     */
    public function render_about_page() {
        // Handle table name update
        $this->handle_table_name_update();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Über Abschussplan HGMH', 'abschussplan-hgmh'); ?></h1>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html__('Plugin-Informationen', 'abschussplan-hgmh'); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php echo esc_html__('Version', 'abschussplan-hgmh'); ?></th>
                            <td><?php echo esc_html(defined('AHGMH_PLUGIN_VERSION') ? AHGMH_PLUGIN_VERSION : '1.0.0'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Plugin-Verzeichnis', 'abschussplan-hgmh'); ?></th>
                            <td><code><?php echo esc_html(defined('AHGMH_PLUGIN_DIR') ? AHGMH_PLUGIN_DIR : plugin_dir_path(__FILE__)); ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html__('Datenbank-Konfiguration', 'abschussplan-hgmh'); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <div class="notice notice-warning inline">
                        <p><strong><?php echo esc_html__('Warnung:', 'abschussplan-hgmh'); ?></strong> <?php echo esc_html__('Die Funktionalität des Plugins kann gefährdet werden, wenn die Datenbanktabelle unbeabsichtigt geändert wird. Ändern Sie diese Einstellung nur, wenn Sie genau wissen, was Sie tun.', 'abschussplan-hgmh'); ?></p>
                    </div>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('update_table_name', 'table_name_nonce'); ?>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="table_name"><?php echo esc_html__('Datenbanktabelle', 'abschussplan-hgmh'); ?></label>
                                </th>
                                <td>
                                    <?php 
                                    global $wpdb;
                                    $current_table = get_option('ahgmh_table_name', $wpdb->prefix . 'ahgmh_submissions');
                                    ?>
                                    <input type="text" id="table_name" name="table_name" value="<?php echo esc_attr($current_table); ?>" class="regular-text" />
                                    <p class="description"><?php echo esc_html__('Standard:', 'abschussplan-hgmh'); ?> <code><?php echo esc_html($wpdb->prefix . 'ahgmh_submissions'); ?></code></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="update_table_name" class="button button-primary" value="<?php echo esc_attr__('Tabellennamen speichern', 'abschussplan-hgmh'); ?>" />
                        </p>
                    </form>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html__('Entwickler', 'abschussplan-hgmh'); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php echo esc_html__('Entwickelt von', 'abschussplan-hgmh'); ?></th>
                            <td><strong>Johannes B.</strong></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Plugin-Name', 'abschussplan-hgmh'); ?></th>
                            <td>Abschussplan HGMH - Hunting Submission Management</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Beschreibung', 'abschussplan-hgmh'); ?></th>
                            <td><?php echo esc_html__('WordPress Plugin für die Verwaltung von Abschussmeldungen deutscher Jagdreviere mit Unterstützung für verschiedene Wildarten und Kategorien.', 'abschussplan-hgmh'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Repository', 'abschussplan-hgmh'); ?></th>
                            <td>
                                <a href="https://github.com/foe05/pr25_one" target="_blank" class="button button-secondary">
                                    <?php echo esc_html__('GitHub Repository öffnen', 'abschussplan-hgmh'); ?>
                                </a>
                                <p class="description">
                                    <code>https://github.com/foe05/pr25_one</code>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Technologie', 'abschussplan-hgmh'); ?></th>
                            <td>
                                <ul>
                                    <li>WordPress Plugin Framework</li>
                                    <li>PHP 7.4+</li>
                                    <li>MySQL/MariaDB</li>
                                    <li>Bootstrap 5</li>
                                    <li>jQuery/AJAX</li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle table name update
     */
    private function handle_table_name_update() {
        if (isset($_POST['update_table_name']) && isset($_POST['table_name_nonce'])) {
            if (!wp_verify_nonce($_POST['table_name_nonce'], 'update_table_name')) {
                wp_die(__('Security check failed. Please try again.', 'abschussplan-hgmh'));
            }

            $new_table_name = sanitize_text_field($_POST['table_name']);
            
            if (!empty($new_table_name)) {
                update_option('ahgmh_table_name', $new_table_name);
                
                add_settings_error(
                    'abschussplan_messages',
                    'table_name_updated',
                    __('Tabellenname erfolgreich aktualisiert. Beachten Sie, dass die Plugin-Funktionalität beeinträchtigt werden kann, wenn die Tabelle nicht existiert.', 'abschussplan-hgmh'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'abschussplan_messages',
                    'table_name_error',
                    __('Tabellenname darf nicht leer sein.', 'abschussplan-hgmh'),
                    'error'
                );
            }
            
            settings_errors('abschussplan_messages');
        }
    }

    /**
     * Handle bulk actions
     */
    private function handle_bulk_actions() {
        // Handle delete all action
        if (isset($_POST['action']) && $_POST['action'] === 'delete_all') {
            // Verify nonce
            if (!isset($_POST['bulk_delete_nonce']) || !wp_verify_nonce($_POST['bulk_delete_nonce'], 'bulk_delete_submissions')) {
                wp_die(__('Security check failed. Please try again.', 'abschussplan-hgmh'));
            }
            
            $database = abschussplan_hgmh()->database;
            $species = isset($_POST['species']) ? sanitize_text_field($_POST['species']) : '';
            
            // Delete submissions
            $success = false;
            if (!empty($species)) {
                $success = $database->delete_submissions_by_species($species);
            } else {
                $success = $database->delete_all_submissions();
            }
            
            // Redirect with message
            $redirect_url = add_query_arg(
                array(
                    'page' => 'abschussplan-hgmh-submissions',
                    'message' => $success ? 'bulk_deleted' : 'bulk_error',
                    'species' => $species
                ),
                admin_url('admin.php')
            );
            
            wp_redirect($redirect_url);
            exit;
        }
        
        // Display bulk action messages
        if (isset($_GET['message'])) {
            if ($_GET['message'] === 'bulk_deleted') {
                $species = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : '';
                $message = !empty($species) 
                    ? sprintf(__('Alle %s Meldungen wurden erfolgreich gelöscht.', 'abschussplan-hgmh'), $species)
                    : __('Alle Meldungen wurden erfolgreich gelöscht.', 'abschussplan-hgmh');
                    
                add_settings_error(
                    'abschussplan_messages',
                    'bulk_deleted',
                    $message,
                    'updated'
                );
            } elseif ($_GET['message'] === 'bulk_error') {
                add_settings_error(
                    'abschussplan_messages',
                    'bulk_error',
                    __('Fehler beim Löschen der Meldungen.', 'abschussplan-hgmh'),
                    'error'
                );
            }
            
            settings_errors('abschussplan_messages');
        }
    }

    /**
     * Handle submission deletion
     */
    private function handle_submission_deletion() {
        // Check if we're trying to delete a submission
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            
            // Verify nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_submission_' . $id)) {
                wp_die(__('Security check failed. Please try again.', 'custom-form-display'));
            }
            
            // Delete the submission
            $database = abschussplan_hgmh()->database;
            $success = $database->delete_submission($id);
            
            // Redirect back to the submissions page with a message
            $redirect_url = add_query_arg(
                array(
                    'page' => 'abschussplan-hgmh-submissions',
                    'message' => $success ? 'deleted' : 'error'
                ),
                admin_url('admin.php')
            );
            
            wp_redirect($redirect_url);
            exit;
        }
        
        // Display messages
        if (isset($_GET['message'])) {
            if ($_GET['message'] === 'deleted') {
                add_settings_error(
                    'custom_form_display_messages',
                    'submission_deleted',
                    __('Submission deleted successfully.', 'custom-form-display'),
                    'updated'
                );
            } elseif ($_GET['message'] === 'error') {
                add_settings_error(
                    'custom_form_display_messages',
                    'submission_error',
                    __('Error deleting submission.', 'custom-form-display'),
                    'error'
                );
            }
            
            settings_errors('custom_form_display_messages');
        }
    }

    /**
     * Render the summary page (like [abschuss_summary] shortcode)
     */
    public function render_summary_page() {
        // Get form handler instance to use its methods
        $form_handler = abschussplan_hgmh()->form;
        
        // Get available species
        $available_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
        
        // Get selected species from URL parameter
        $selected_species = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : 'Rotwild';
        
        // Validate selected species
        if (!in_array($selected_species, $available_species)) {
            $selected_species = !empty($available_species) ? $available_species[0] : 'Rotwild';
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Abschussplan Übersicht', 'abschussplan-hgmh'); ?></h1>
            
            <!-- Species Selection -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html__('Wildart auswählen', 'abschussplan-hgmh'); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="abschussplan-hgmh" />
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="species-selector"><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?></label>
                                </th>
                                <td>
                                    <select name="species" id="species-selector">
                                        <?php foreach ($available_species as $species) : ?>
                                            <option value="<?php echo esc_attr($species); ?>" <?php selected($species, $selected_species); ?>>
                                                <?php echo esc_html($species); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="submit" class="button" value="<?php echo esc_attr__('Anzeigen', 'abschussplan-hgmh'); ?>" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
            
            <!-- Species-specific Summary -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html(sprintf(__('Übersicht für %s', 'abschussplan-hgmh'), $selected_species)); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <?php
                    // Use the summary shortcode with specific species
                    echo $form_handler->render_summary(array('species' => $selected_species));
                    ?>
                </div>
            </div>
            
            <!-- Categories Overview per Species -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html(sprintf(__('Kategorien für %s', 'abschussplan-hgmh'), $selected_species)); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <?php
                    $categories = get_option('ahgmh_categories', array('Wildkalb (AK 0)', 'Schmaltier (AK 1)', 'Alttier (AK 2)'));
                    $limits = get_option('abschuss_category_limits_' . sanitize_key($selected_species), array());
                    $allow_exceeding = get_option('abschuss_category_allow_exceeding_' . sanitize_key($selected_species), array());
                    $database = abschussplan_hgmh()->database;
                    $counts = $database->get_category_counts($selected_species);
                    ?>
                    
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                                <th><?php echo esc_html__('Abschuss (Ist)', 'abschussplan-hgmh'); ?></th>
                                <th><?php echo esc_html__('Abschuss (Soll)', 'abschussplan-hgmh'); ?></th>
                                <th><?php echo esc_html__('Überschreitung erlaubt', 'abschussplan-hgmh'); ?></th>
                                <th><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): 
                                $current_count = isset($counts[$category]) ? $counts[$category] : 0;
                                $limit = isset($limits[$category]) ? $limits[$category] : 0;
                                $exceeding_allowed = isset($allow_exceeding[$category]) ? $allow_exceeding[$category] : false;
                                $percentage = $limit > 0 ? ($current_count / $limit) * 100 : 0;
                                $status_class = '';
                                $status_text = '';
                                
                                if ($limit > 0) {
                                    if ($percentage >= 100) {
                                        $status_class = 'notice-error';
                                        $status_text = round($percentage, 1) . '% - ' . __('Limit erreicht/überschritten', 'abschussplan-hgmh');
                                    } elseif ($percentage >= 90) {
                                        $status_class = 'notice-warning';
                                        $status_text = round($percentage, 1) . '% - ' . __('Limit fast erreicht', 'abschussplan-hgmh');
                                    } else {
                                        $status_class = 'notice-success';
                                        $status_text = round($percentage, 1) . '% - ' . __('Im Rahmen', 'abschussplan-hgmh');
                                    }
                                } else {
                                    $status_class = 'notice-info';
                                    $status_text = __('Kein Limit gesetzt', 'abschussplan-hgmh');
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($category); ?></strong></td>
                                <td><?php echo esc_html($current_count); ?></td>
                                <td><?php echo $limit > 0 ? esc_html($limit) : '<em>' . esc_html__('Unbegrenzt', 'abschussplan-hgmh') . '</em>'; ?></td>
                                <td>
                                    <?php if ($exceeding_allowed): ?>
                                        <span style="color: #46b450; font-weight: bold;"><?php echo esc_html__('Ja', 'abschussplan-hgmh'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3232; font-weight: bold;"><?php echo esc_html__('Nein', 'abschussplan-hgmh'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="notice <?php echo esc_attr($status_class); ?> inline" style="padding: 4px 8px; margin: 0;">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-4">
                <h3><?php echo esc_html__('Verfügbare Shortcodes', 'abschussplan-hgmh'); ?></h3>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h4 class="hndle ui-sortable-handle">
                            <span><?php echo esc_html__('Grundlegende Shortcodes', 'abschussplan-hgmh'); ?></span>
                        </h4>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Shortcode', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Beschreibung', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Beispiel für alle Wildarten', 'abschussplan-hgmh'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>[abschuss_form]</code></td>
                                    <td><?php echo esc_html__('Abschussformular anzeigen', 'abschussplan-hgmh'); ?></td>
                                    <td><code>[abschuss_form]</code></td>
                                </tr>
                                <tr>
                                    <td><code>[abschuss_table]</code></td>
                                    <td><?php echo esc_html__('Tabelle der Meldungen anzeigen', 'abschussplan-hgmh'); ?></td>
                                    <td><code>[abschuss_table]</code></td>
                                </tr>
                                <tr>
                                    <td><code>[abschuss_summary]</code></td>
                                    <td><?php echo esc_html__('Zusammenfassung anzeigen', 'abschussplan-hgmh'); ?></td>
                                    <td><code>[abschuss_summary]</code></td>
                                </tr>
                                <tr>
                                    <td><code>[abschuss_limits]</code></td>
                                    <td><?php echo esc_html__('Grenzwerte konfigurieren', 'abschussplan-hgmh'); ?></td>
                                    <td><code>[abschuss_limits]</code></td>
                                </tr>
                                <tr>
                                    <td><code>[abschuss_admin]</code></td>
                                    <td><?php echo esc_html__('Admin-Konfiguration anzeigen', 'abschussplan-hgmh'); ?></td>
                                    <td><code>[abschuss_admin]</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header">
                        <h4 class="hndle ui-sortable-handle">
                            <span><?php echo esc_html__('Wildarten-spezifische Shortcodes', 'abschussplan-hgmh'); ?></span>
                        </h4>
                    </div>
                    <div class="inside">
                        <p><strong><?php echo esc_html__('Mit dem Parameter "species" können Sie die Shortcodes auf bestimmte Wildarten beschränken:', 'abschussplan-hgmh'); ?></strong></p>
                        
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Formular', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Tabelle', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Zusammenfassung', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Grenzwerte', 'abschussplan-hgmh'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Rotwild</strong></td>
                                    <td><code>[abschuss_form species="Rotwild"]</code></td>
                                    <td><code>[abschuss_table species="Rotwild"]</code></td>
                                    <td><code>[abschuss_summary species="Rotwild"]</code></td>
                                    <td><code>[abschuss_limits species="Rotwild"]</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Damwild</strong></td>
                                    <td><code>[abschuss_form species="Damwild"]</code></td>
                                    <td><code>[abschuss_table species="Damwild"]</code></td>
                                    <td><code>[abschuss_summary species="Damwild"]</code></td>
                                    <td><code>[abschuss_limits species="Damwild"]</code></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="notice notice-info inline">
                            <p><strong><?php echo esc_html__('Hinweise:', 'abschussplan-hgmh'); ?></strong></p>
                            <ul>
                                <li><?php echo esc_html__('Verfügbare Wildarten können unter "Wildarten" verwaltet werden', 'abschussplan-hgmh'); ?></li>
                                <li><?php echo esc_html__('Bei wildarten-spezifischen Shortcodes werden nur Meldungen der gewählten Wildart angezeigt', 'abschussplan-hgmh'); ?></li>
                                <li><?php echo esc_html__('Formulare ohne Species-Parameter verwenden "Rotwild" als Standard', 'abschussplan-hgmh'); ?></li>
                                <li><?php echo esc_html__('Grenzwerte müssen für jede Wildart separat konfiguriert werden', 'abschussplan-hgmh'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header">
                        <h4 class="hndle ui-sortable-handle">
                            <span><?php echo esc_html__('Zusätzliche Parameter', 'abschussplan-hgmh'); ?></span>
                        </h4>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Parameter', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Verfügbar für', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Beschreibung', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Beispiel', 'abschussplan-hgmh'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>species</code></td>
                                    <td>Alle Shortcodes</td>
                                    <td><?php echo esc_html__('Beschränkt auf bestimmte Wildart', 'abschussplan-hgmh'); ?></td>
                                    <td><code>species="Rotwild"</code></td>
                                </tr>
                                <tr>
                                    <td><code>limit</code></td>
                                    <td>[abschuss_table]</td>
                                    <td><?php echo esc_html__('Anzahl der angezeigten Einträge pro Seite', 'abschussplan-hgmh'); ?></td>
                                    <td><code>[abschuss_table limit="20"]</code></td>
                                </tr>
                                <tr>
                                    <td><code>page</code></td>
                                    <td>[abschuss_table]</td>
                                    <td><?php echo esc_html__('Startseite für die Tabellenpaginierung', 'abschussplan-hgmh'); ?></td>
                                    <td><code>[abschuss_table page="2"]</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the admin configuration page (modern style)
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Abschussplanung', 'abschussplan-hgmh'); ?></h1>
            
            <div id="limits-response" class="notice" style="display: none;"></div>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html__('Abschuss (Soll) verwalten', 'abschussplan-hgmh'); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <p><?php echo esc_html__('Hier können Sie die Abschusszahlen (Soll-Werte) für jede Wildart konfigurieren.', 'abschussplan-hgmh'); ?></p>
                    
                    <form id="limits-form">
                        <?php wp_nonce_field('limits_nonce', 'limits_nonce_field'); ?>
                        
                        <div class="tablenav">
                            <div class="alignleft">
                                <label for="species-select"><?php echo esc_html__('Wildart auswählen:', 'abschussplan-hgmh'); ?></label>
                                <select id="species-select" name="species">
                                    <?php 
                                    $available_species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
                                    foreach ($available_species as $species): ?>
                                        <option value="<?php echo esc_attr($species); ?>"><?php echo esc_html($species); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="load-limits" class="button"><?php echo esc_html__('Laden', 'abschussplan-hgmh'); ?></button>
                            </div>
                        </div>
                        
                        <div id="limits-container">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col" class="manage-column"><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                                        <th scope="col" class="manage-column" style="width: 150px;"><?php echo esc_html__('Abschuss (Soll)', 'abschussplan-hgmh'); ?></th>
                                        <th scope="col" class="manage-column" style="width: 150px;"><?php echo esc_html__('Überschießen möglich?', 'abschussplan-hgmh'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="limits-tbody">
                                    <!-- Content will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Abschuss (Soll) speichern', 'abschussplan-hgmh'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load initial limits
            loadLimits();
            
            // Load limits when species changes
            $('#load-limits, #species-select').on('change click', function() {
                loadLimits();
            });
            
            function loadLimits() {
                const species = $('#species-select').val();
                const categories = <?php echo json_encode(get_option('ahgmh_categories', array('Wildkalb (AK 0)', 'Schmaltier (AK 1)', 'Alttier (AK 2)'))); ?>;
                const data = getStoredLimitsAndExceeding(species);
                const limits = data.limits;
                const allowExceeding = data.allowExceeding;
                
                let html = '';
                categories.forEach(function(category) {
                    const value = limits[category] || '0';
                    const exceedingChecked = allowExceeding[category] ? 'checked' : '';
                    const categoryId = category.replace(/[^a-zA-Z0-9]/g, '_');
                    
                    html += '<tr>';
                    html += '<td>' + category + '</td>';
                    html += '<td><input type="number" name="limits[' + category + ']" value="' + value + '" min="0" style="width: 100px;" /></td>';
                    html += '<td>';
                    html += '<label>';
                    html += '<input type="checkbox" name="allow_exceeding[' + category + ']" value="1" ' + exceedingChecked + ' /> ';
                    html += '<?php echo esc_js(__('Ja', 'abschussplan-hgmh')); ?>';
                    html += '</label>';
                    html += '</td>';
                    html += '</tr>';
                });
                
                $('#limits-tbody').html(html);
            }
            
            function getStoredLimitsAndExceeding(species) {
                let data = { limits: {}, allowExceeding: {} };
                $.ajax({
                    url: ajaxurl,
                    type: 'GET',
                    data: {
                        action: 'load_species_limits',
                        species: species
                    },
                    async: false,
                    success: function(response) {
                        if (response.success) {
                            data.limits = response.data.limits || {};
                            data.allowExceeding = response.data.allowExceeding || {};
                        }
                    }
                });
                return data;
            }
            
            // Handle form submission
            $('#limits-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const species = $('#species-select').val();
                formData.append('action', 'save_species_limits');
                formData.append('species', species);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#limits-response').removeClass('notice-error').addClass('notice-success').html('<p>' + response.data.message + '</p>').show();
                        } else {
                            $('#limits-response').removeClass('notice-success').addClass('notice-error').html('<p>' + response.data.message + '</p>').show();
                        }
                        
                        $('html, body').animate({
                            scrollTop: $('#limits-response').offset().top - 100
                        }, 500);
                    },
                    error: function() {
                        $('#limits-response').removeClass('notice-success').addClass('notice-error').html('<p><?php echo esc_js(__('Fehler beim Speichern der Abschuss (Soll) Werte.', 'abschussplan-hgmh')); ?></p>').show();
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render the categories management page
     */
    public function render_categories_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Kategorien verwalten', 'abschussplan-hgmh'); ?></h1>
            
            <div id="categories-response" class="notice" style="display: none;"></div>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html__('Verfügbare Kategorien', 'abschussplan-hgmh'); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <form id="categories-form">
                        <?php wp_nonce_field('categories_nonce', 'categories_nonce_field'); ?>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column"><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                                    <th scope="col" class="manage-column"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="categories-list">
                                <?php 
                                $categories = get_option('ahgmh_categories', array(
                                    "Wildkalb (AK 0)", "Schmaltier (AK 1)", "Alttier (AK 2)", 
                                    "Hirschkalb (AK 0)", "Schmalspießer (AK1)", "Junger Hirsch (AK 2)", 
                                    "Mittelalter Hirsch (AK 3)", "Alter Hirsch (AK 4)"
                                ));
                                foreach ($categories as $index => $category): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="categories[]" value="<?php echo esc_attr($category); ?>" class="regular-text" />
                                    </td>
                                    <td>
                                        <button type="button" class="button button-secondary remove-category"><?php echo esc_html__('Entfernen', 'abschussplan-hgmh'); ?></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p>
                            <button type="button" class="button button-secondary" id="add-category"><?php echo esc_html__('Kategorie hinzufügen', 'abschussplan-hgmh'); ?></button>
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Kategorien speichern', 'abschussplan-hgmh'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add new category
            $('#add-category').click(function() {
                var newRow = '<tr>' +
                    '<td><input type="text" name="categories[]" value="" class="regular-text" placeholder="<?php echo esc_js(__('Kategorie eingeben', 'abschussplan-hgmh')); ?>" /></td>' +
                    '<td><button type="button" class="button button-secondary remove-category"><?php echo esc_js(__('Entfernen', 'abschussplan-hgmh')); ?></button></td>' +
                    '</tr>';
                $('#categories-list').append(newRow);
            });
            
            // Remove category
            $(document).on('click', '.remove-category', function() {
                if (confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie diese Kategorie entfernen möchten?', 'abschussplan-hgmh')); ?>')) {
                    $(this).closest('tr').remove();
                }
            });
            
            // Save categories
            $('#categories-form').on('submit', function(e) {
                e.preventDefault();
                
                var $submitBtn = $(this).find('button[type="submit"]');
                var originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Speichern...', 'abschussplan-hgmh')); ?>');
                
                var formData = new FormData(this);
                formData.append('action', 'save_categories');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#categories-response')
                                .removeClass('notice-error')
                                .addClass('notice notice-success')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        } else {
                            $('#categories-response')
                                .removeClass('notice-success')
                                .addClass('notice notice-error')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        }
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text(originalText);
                        $('html, body').animate({ scrollTop: 0 }, 500);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render the species management page
     */
    public function render_species_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Wildarten verwalten', 'abschussplan-hgmh'); ?></h1>
            
            <div id="species-response" class="notice" style="display: none;"></div>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle">
                        <span><?php echo esc_html__('Verfügbare Wildarten', 'abschussplan-hgmh'); ?></span>
                    </h2>
                </div>
                <div class="inside">
                    <form id="species-form">
                        <?php wp_nonce_field('species_nonce', 'species_nonce_field'); ?>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column"><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                                    <th scope="col" class="manage-column"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="species-list">
                                <?php 
                                $species = get_option('ahgmh_species', array('Rotwild', 'Damwild'));
                                foreach ($species as $specie): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="species[]" value="<?php echo esc_attr($specie); ?>" class="regular-text" />
                                    </td>
                                    <td>
                                        <button type="button" class="button button-secondary remove-species"><?php echo esc_html__('Entfernen', 'abschussplan-hgmh'); ?></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p>
                            <button type="button" class="button button-secondary" id="add-species"><?php echo esc_html__('Wildart hinzufügen', 'abschussplan-hgmh'); ?></button>
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Wildarten speichern', 'abschussplan-hgmh'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add new species
            $('#add-species').click(function() {
                var newRow = '<tr>' +
                    '<td><input type="text" name="species[]" value="" class="regular-text" placeholder="<?php echo esc_js(__('Neue Wildart', 'abschussplan-hgmh')); ?>" /></td>' +
                    '<td><button type="button" class="button button-secondary remove-species"><?php echo esc_js(__('Entfernen', 'abschussplan-hgmh')); ?></button></td>' +
                    '</tr>';
                $('#species-list').append(newRow);
            });
            
            // Remove species
            $(document).on('click', '.remove-species', function() {
                if (confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie diese Wildart entfernen möchten?', 'abschussplan-hgmh')); ?>')) {
                    $(this).closest('tr').remove();
                }
            });
            
            // Save species
            $('#species-form').on('submit', function(e) {
                e.preventDefault();
                
                var $submitBtn = $(this).find('button[type="submit"]');
                var originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('<?php echo esc_js(__('Speichern...', 'abschussplan-hgmh')); ?>');
                
                var formData = new FormData(this);
                formData.append('action', 'save_species');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#species-response')
                                .removeClass('notice-error')
                                .addClass('notice notice-success')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        } else {
                            $('#species-response')
                                .removeClass('notice-success')
                                .addClass('notice notice-error')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                        }
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text(originalText);
                        $('html, body').animate({ scrollTop: 0 }, 500);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
