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
class CFD_Admin_Page {
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
            __('Custom Form Display', 'custom-form-display'),
            __('Custom Form', 'custom-form-display'),
            'manage_options',
            'custom-form-display',
            array($this, 'render_main_page'),
            'dashicons-feedback',
            30
        );
        
        add_submenu_page(
            'custom-form-display',
            __('Form Submissions', 'custom-form-display'),
            __('Submissions', 'custom-form-display'),
            'manage_options',
            'custom-form-display-submissions',
            array($this, 'render_submissions_page')
        );
        
        add_submenu_page(
            'custom-form-display',
            __('Settings', 'custom-form-display'),
            __('Settings', 'custom-form-display'),
            'manage_options',
            'custom-form-display-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'custom_form_display_settings',
            'custom_form_display_options',
            array($this, 'sanitize_settings')
        );
        
        add_settings_section(
            'custom_form_display_main_section',
            __('Main Settings', 'custom-form-display'),
            array($this, 'render_section_description'),
            'custom-form-display-settings'
        );
        
        add_settings_field(
            'field1_label',
            __('Field 1 Label', 'custom-form-display'),
            array($this, 'render_text_field'),
            'custom-form-display-settings',
            'custom_form_display_main_section',
            array(
                'label_for' => 'field1_label',
                'default' => __('Field 1', 'custom-form-display')
            )
        );
        
        add_settings_field(
            'field2_label',
            __('Field 2 Label', 'custom-form-display'),
            array($this, 'render_text_field'),
            'custom-form-display-settings',
            'custom_form_display_main_section',
            array(
                'label_for' => 'field2_label',
                'default' => __('Field 2', 'custom-form-display')
            )
        );
        
        add_settings_field(
            'field3_label',
            __('Field 3 Label', 'custom-form-display'),
            array($this, 'render_text_field'),
            'custom-form-display-settings',
            'custom_form_display_main_section',
            array(
                'label_for' => 'field3_label',
                'default' => __('Field 3', 'custom-form-display')
            )
        );
        
        add_settings_field(
            'field4_label',
            __('Field 4 Label', 'custom-form-display'),
            array($this, 'render_text_field'),
            'custom-form-display-settings',
            'custom_form_display_main_section',
            array(
                'label_for' => 'field4_label',
                'default' => __('Field 4', 'custom-form-display')
            )
        );
        
        add_settings_field(
            'entries_per_page',
            __('Entries Per Page', 'custom-form-display'),
            array($this, 'render_number_field'),
            'custom-form-display-settings',
            'custom_form_display_main_section',
            array(
                'label_for' => 'entries_per_page',
                'default' => 10,
                'min' => 1,
                'max' => 100
            )
        );
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
            <h1><?php echo esc_html__('Custom Form Display', 'custom-form-display'); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__('Usage Instructions', 'custom-form-display'); ?></h2>
                <div class="card-body">
                    <p><?php echo esc_html__('Use the following shortcodes to display the form and submissions table on your pages:', 'custom-form-display'); ?></p>
                    
                    <h3><?php echo esc_html__('Form Shortcode', 'custom-form-display'); ?></h3>
                    <code>[custom_form]</code>
                    <p class="description"><?php echo esc_html__('Add this shortcode to any page or post where you want the form to appear.', 'custom-form-display'); ?></p>
                    
                    <h3><?php echo esc_html__('Submissions Table Shortcode', 'custom-form-display'); ?></h3>
                    <code>[custom_form_submissions]</code>
                    <p class="description"><?php echo esc_html__('Add this shortcode to display the table of form submissions.', 'custom-form-display'); ?></p>
                    
                    <h4><?php echo esc_html__('Table Parameters', 'custom-form-display'); ?></h4>
                    <ul>
                        <li><code>limit</code> - <?php echo esc_html__('Number of entries per page (default: 10)', 'custom-form-display'); ?></li>
                        <li><code>page</code> - <?php echo esc_html__('Initial page number (default: 1)', 'custom-form-display'); ?></li>
                    </ul>
                    <p><strong><?php echo esc_html__('Example:', 'custom-form-display'); ?></strong> <code>[custom_form_submissions limit="20" page="1"]</code></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        // Get current page
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Get settings
        $options = get_option('custom_form_display_options', array());
        $limit = isset($options['entries_per_page']) ? intval($options['entries_per_page']) : 10;
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Get database instance
        $database = custom_form_display()->database;
        
        // Get submissions
        $submissions = $database->get_submissions($limit, $offset);
        
        // Get total count
        $total_submissions = $database->count_submissions();
        
        // Calculate pagination
        $total_pages = ceil($total_submissions / $limit);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Form Submissions', 'custom-form-display'); ?></h1>
            
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
                            <th scope="col" class="manage-column column-id"><?php echo esc_html__('ID', 'custom-form-display'); ?></th>
                            <th scope="col" class="manage-column column-field"><?php echo esc_html__('Field 1', 'custom-form-display'); ?></th>
                            <th scope="col" class="manage-column column-field"><?php echo esc_html__('Field 2', 'custom-form-display'); ?></th>
                            <th scope="col" class="manage-column column-field"><?php echo esc_html__('Field 3', 'custom-form-display'); ?></th>
                            <th scope="col" class="manage-column column-field"><?php echo esc_html__('Field 4', 'custom-form-display'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php echo esc_html__('Date', 'custom-form-display'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Actions', 'custom-form-display'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission) : ?>
                            <tr>
                                <td class="column-id"><?php echo esc_html($submission['id']); ?></td>
                                <td class="column-field"><?php echo esc_html($submission['field1']); ?></td>
                                <td class="column-field"><?php echo esc_html($submission['field2']); ?></td>
                                <td class="column-field"><?php echo esc_html($submission['field3']); ?></td>
                                <td class="column-field"><?php echo esc_html($submission['field4']); ?></td>
                                <td class="column-date"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission['created_at']))); ?></td>
                                <td class="column-actions">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=custom-form-display-submissions&action=delete&id=' . $submission['id']), 'delete_submission_' . $submission['id']); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this submission?', 'custom-form-display')); ?>')">
                                        <?php echo esc_html__('Delete', 'custom-form-display'); ?>
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
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Custom Form Display Settings', 'custom-form-display'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('custom_form_display_settings');
                do_settings_sections('custom-form-display-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
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
            $database = custom_form_display()->database;
            $success = $database->delete_submission($id);
            
            // Redirect back to the submissions page with a message
            $redirect_url = add_query_arg(
                array(
                    'page' => 'custom-form-display-submissions',
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
}
