<?php
/**
 * Compliance Controller
 * Handles compliance dashboard display and AJAX operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compliance Controller Class
 */
class AHGMH_Compliance_Controller {

    private $report_service;

    /**
     * Constructor
     */
    public function __construct() {
        // Load report service
        require_once AHGMH_PLUGIN_DIR . 'admin/services/class-report-service.php';
        $this->report_service = new AHGMH_Report_Service();

        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_compliance_refresh', array($this, 'ajax_compliance_refresh'));
        add_action('wp_ajax_ahgmh_compliance_filter', array($this, 'ajax_compliance_filter'));
    }

    /**
     * Render compliance dashboard page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get initial compliance data
        $compliance_data = $this->get_compliance_data();

        // Get filter options
        $species_list = get_option('ahgmh_species', ['Rotwild', 'Damwild']);
        $meldegruppen = get_option('ahgmh_meldegruppen', []);

        // Render the page
        $this->render_compliance_page($compliance_data, $species_list, $meldegruppen);
    }

    /**
     * Get compliance data
     *
     * @param string $species Optional species filter
     * @param string $meldegruppe Optional meldegruppe filter
     * @return array Compliance data
     */
    private function get_compliance_data($species = '', $meldegruppe = '') {
        // Get overall compliance data
        $compliance = $this->report_service->get_compliance_data($species, $meldegruppe);

        // Get compliance by meldegruppe for detailed breakdown
        $by_meldegruppe = $this->report_service->get_compliance_by_meldegruppe($species);

        // Get compliance summary
        $summary = $this->report_service->get_compliance_summary();

        return [
            'compliance' => $compliance,
            'by_meldegruppe' => $by_meldegruppe,
            'summary' => $summary,
            'filters' => [
                'species' => $species,
                'meldegruppe' => $meldegruppe
            ]
        ];
    }

    /**
     * AJAX handler for compliance refresh
     */
    public function ajax_compliance_refresh() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Clear cache to get fresh data
            $this->report_service->clear_cache();

            // Get compliance data
            $compliance_data = $this->get_compliance_data();

            wp_send_json_success([
                'data' => $compliance_data,
                'message' => __('Compliance-Daten aktualisiert', 'abschussplan-hgmh')
            ]);
        } catch (Exception $e) {
            error_log('AHGMH Compliance Refresh Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Aktualisieren der Compliance-Daten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
        }
    }

    /**
     * AJAX handler for compliance filter
     */
    public function ajax_compliance_filter() {
        AHGMH_Validation_Service::verify_ajax_request();

        try {
            // Get and sanitize filter parameters
            $species = isset($_POST['species']) ? sanitize_text_field($_POST['species']) : '';
            $meldegruppe = isset($_POST['meldegruppe']) ? sanitize_text_field($_POST['meldegruppe']) : '';

            // Get filtered compliance data
            $compliance_data = $this->get_compliance_data($species, $meldegruppe);

            wp_send_json_success([
                'data' => $compliance_data,
                'message' => __('Filter angewendet', 'abschussplan-hgmh')
            ]);
        } catch (Exception $e) {
            error_log('AHGMH Compliance Filter Error: ' . $e->getMessage());
            wp_send_json_error(__('Fehler beim Filtern der Compliance-Daten. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh'));
        }
    }

    /**
     * Render compliance dashboard HTML
     *
     * @param array $compliance_data Compliance data
     * @param array $species_list Available species
     * @param array $meldegruppen Available meldegruppen
     */
    private function render_compliance_page($compliance_data, $species_list, $meldegruppen) {
        ?>
        <div class="wrap ahgmh-compliance-dashboard">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php echo esc_html__('Compliance Dashboard', 'abschussplan-hgmh'); ?>
            </h1>

            <!-- Filter Bar -->
            <div class="ahgmh-compliance-filters">
                <div class="filter-group">
                    <label for="compliance-species-filter"><?php echo esc_html__('Wildart:', 'abschussplan-hgmh'); ?></label>
                    <select id="compliance-species-filter" name="species">
                        <option value=""><?php echo esc_html__('Alle Wildarten', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($species_list as $species): ?>
                            <option value="<?php echo esc_attr($species); ?>"><?php echo esc_html($species); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="compliance-meldegruppe-filter"><?php echo esc_html__('Meldegruppe:', 'abschussplan-hgmh'); ?></label>
                    <select id="compliance-meldegruppe-filter" name="meldegruppe">
                        <option value=""><?php echo esc_html__('Alle Meldegruppen', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($meldegruppen as $mg): ?>
                            <option value="<?php echo esc_attr($mg); ?>"><?php echo esc_html($mg); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" class="button button-primary" id="apply-compliance-filters">
                    <?php echo esc_html__('Filter anwenden', 'abschussplan-hgmh'); ?>
                </button>

                <button type="button" class="button" id="refresh-compliance">
                    <span class="dashicons dashicons-update"></span>
                    <?php echo esc_html__('Aktualisieren', 'abschussplan-hgmh'); ?>
                </button>
            </div>

            <!-- Overall Summary -->
            <div id="compliance-summary-container">
                <?php $this->render_compliance_summary($compliance_data['summary']); ?>
            </div>

            <!-- Species Compliance Details -->
            <div id="compliance-details-container">
                <?php $this->render_compliance_details($compliance_data['compliance']); ?>
            </div>

            <!-- Meldegruppe Breakdown -->
            <div id="compliance-meldegruppe-container">
                <?php $this->render_meldegruppe_breakdown($compliance_data['by_meldegruppe']); ?>
            </div>

            <!-- Last Update Timestamp -->
            <div class="ahgmh-compliance-footer">
                <p class="last-update">
                    <?php
                    echo esc_html__('Zuletzt aktualisiert:', 'abschussplan-hgmh') . ' ';
                    echo esc_html(current_time('d.m.Y H:i:s'));
                    ?>
                </p>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Apply filters
            $('#apply-compliance-filters').on('click', function() {
                var species = $('#compliance-species-filter').val();
                var meldegruppe = $('#compliance-meldegruppe-filter').val();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_compliance_filter',
                        nonce: '<?php echo wp_create_nonce('ahgmh_admin_nonce'); ?>',
                        species: species,
                        meldegruppe: meldegruppe
                    },
                    beforeSend: function() {
                        $('#apply-compliance-filters').prop('disabled', true).text('<?php echo esc_js(__('Lädt...', 'abschussplan-hgmh')); ?>');
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'abschussplan-hgmh')); ?>');
                    },
                    complete: function() {
                        $('#apply-compliance-filters').prop('disabled', false).text('<?php echo esc_js(__('Filter anwenden', 'abschussplan-hgmh')); ?>');
                    }
                });
            });

            // Refresh data
            $('#refresh-compliance').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_compliance_refresh',
                        nonce: '<?php echo wp_create_nonce('ahgmh_admin_nonce'); ?>'
                    },
                    beforeSend: function() {
                        $('#refresh-compliance').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'abschussplan-hgmh')); ?>');
                    },
                    complete: function() {
                        $('#refresh-compliance').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render compliance summary section
     *
     * @param array $summary Summary data
     */
    private function render_compliance_summary($summary) {
        if (isset($summary['error']) || empty($summary)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Fehler beim Laden der Zusammenfassung.', 'abschussplan-hgmh') . '</p></div>';
            return;
        }

        $overall = $summary['overall'];
        $status_class = $this->get_status_class($overall['status']);
        ?>
        <div class="ahgmh-compliance-summary">
            <h2><?php echo esc_html__('Gesamtübersicht', 'abschussplan-hgmh'); ?></h2>
            <div class="compliance-summary-card <?php echo esc_attr($status_class); ?>">
                <div class="summary-stat">
                    <span class="stat-label"><?php echo esc_html__('Aktuell:', 'abschussplan-hgmh'); ?></span>
                    <span class="stat-value"><?php echo esc_html(number_format($overall['total_current'], 0, ',', '.')); ?></span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label"><?php echo esc_html__('Limit:', 'abschussplan-hgmh'); ?></span>
                    <span class="stat-value"><?php echo esc_html(number_format($overall['total_limit'], 0, ',', '.')); ?></span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label"><?php echo esc_html__('Verbleibend:', 'abschussplan-hgmh'); ?></span>
                    <span class="stat-value"><?php echo esc_html(number_format($overall['total_remaining'], 0, ',', '.')); ?></span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label"><?php echo esc_html__('Erfüllung:', 'abschussplan-hgmh'); ?></span>
                    <span class="stat-value"><?php echo esc_html($overall['percentage']); ?>%</span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render compliance details for each species
     *
     * @param array $compliance Compliance data
     */
    private function render_compliance_details($compliance) {
        if (isset($compliance['error']) || empty($compliance['compliance'])) {
            echo '<div class="notice notice-info"><p>' . esc_html__('Keine Compliance-Daten verfügbar.', 'abschussplan-hgmh') . '</p></div>';
            return;
        }

        foreach ($compliance['compliance'] as $species_data) {
            $species = $species_data['species'];
            $total = $species_data['total'];
            $status_class = $this->get_status_class($total['status']);
            ?>
            <div class="ahgmh-compliance-species">
                <h3><?php echo esc_html($species); ?></h3>
                <div class="species-total <?php echo esc_attr($status_class); ?>">
                    <span><?php echo esc_html__('Gesamt:', 'abschussplan-hgmh'); ?></span>
                    <span><?php echo esc_html($total['current'] . ' / ' . $total['limit']); ?></span>
                    <span><?php echo esc_html($total['percentage']); ?>%</span>
                </div>

                <div class="category-breakdown">
                    <?php foreach ($species_data['categories'] as $category => $data):
                        $cat_status_class = $this->get_status_class($data['status']);
                    ?>
                        <div class="category-item <?php echo esc_attr($cat_status_class); ?>">
                            <div class="category-name"><?php echo esc_html($category); ?></div>
                            <div class="category-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr(min($data['percentage'], 100)); ?>%;"></div>
                                </div>
                                <div class="progress-text">
                                    <?php echo esc_html($data['current'] . ' / ' . $data['limit']); ?>
                                    (<?php echo esc_html($data['percentage']); ?>%)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Render meldegruppe breakdown table
     *
     * @param array $by_meldegruppe Meldegruppe data
     */
    private function render_meldegruppe_breakdown($by_meldegruppe) {
        if (isset($by_meldegruppe['error']) || empty($by_meldegruppe['meldegruppen'])) {
            return;
        }
        ?>
        <div class="ahgmh-compliance-meldegruppen">
            <h2><?php echo esc_html__('Meldegruppen-Übersicht', 'abschussplan-hgmh'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Aktuell', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Limit', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Verbleibend', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_meldegruppe['meldegruppen'] as $mg_data):
                        $total = $mg_data['total'];
                        $status_class = $this->get_status_class($total['status']);
                    ?>
                        <tr class="<?php echo esc_attr($status_class); ?>">
                            <td><?php echo esc_html($mg_data['meldegruppe']); ?></td>
                            <td><?php echo esc_html($mg_data['species']); ?></td>
                            <td><?php echo esc_html(number_format($total['current'], 0, ',', '.')); ?></td>
                            <td><?php echo esc_html(number_format($total['limit'], 0, ',', '.')); ?></td>
                            <td><?php echo esc_html(number_format($total['remaining'], 0, ',', '.')); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($total['status']); ?>">
                                    <?php echo esc_html($this->get_status_label($total['status'])); ?>
                                    (<?php echo esc_html($total['percentage']); ?>%)
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Get CSS class for status
     *
     * @param string $status Status indicator
     * @return string CSS class
     */
    private function get_status_class($status) {
        $classes = [
            'good' => 'status-good',
            'warning' => 'status-warning',
            'critical' => 'status-critical',
            'exceeded' => 'status-exceeded'
        ];

        return isset($classes[$status]) ? $classes[$status] : 'status-good';
    }

    /**
     * Get status label for display
     *
     * @param string $status Status indicator
     * @return string Status label
     */
    private function get_status_label($status) {
        $labels = [
            'good' => __('Gut', 'abschussplan-hgmh'),
            'warning' => __('Achtung', 'abschussplan-hgmh'),
            'critical' => __('Kritisch', 'abschussplan-hgmh'),
            'exceeded' => __('Überschritten', 'abschussplan-hgmh')
        ];

        return isset($labels[$status]) ? $labels[$status] : $labels['good'];
    }
}
