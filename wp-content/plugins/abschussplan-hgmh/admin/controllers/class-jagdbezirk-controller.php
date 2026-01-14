<?php
/**
 * Jagdbezirk Controller
 * Handles CRUD operations for Eigenjagdbezirke (hunting districts)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Jagdbezirk Controller Class
 * Manages Jagdbezirk AJAX operations with proper security
 */
class AHGMH_Jagdbezirk_Controller {

    /**
     * @var AHGMH_Jagdbezirk_Service
     */
    private $service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->service = new AHGMH_Jagdbezirk_Service();
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_ahgmh_get_jagdbezirke', [$this, 'ajax_get_jagdbezirke']);
        add_action('wp_ajax_ahgmh_get_jagdbezirk', [$this, 'ajax_get_jagdbezirk']);
        add_action('wp_ajax_ahgmh_create_jagdbezirk', [$this, 'ajax_create_jagdbezirk']);
        add_action('wp_ajax_ahgmh_update_jagdbezirk', [$this, 'ajax_update_jagdbezirk']);
        add_action('wp_ajax_ahgmh_delete_jagdbezirk', [$this, 'ajax_delete_jagdbezirk']);
        add_action('wp_ajax_ahgmh_restore_jagdbezirk', [$this, 'ajax_restore_jagdbezirk']);
        add_action('wp_ajax_ahgmh_assign_jagdbezirk_meldegruppen', [$this, 'ajax_assign_meldegruppen']);
        add_action('wp_ajax_ahgmh_get_jagdbezirk_meldegruppen', [$this, 'ajax_get_meldegruppen']);
        add_action('wp_ajax_ahgmh_get_jagdbezirk_statistics', [$this, 'ajax_get_statistics']);
    }

    /**
     * Verify AJAX request security
     */
    private function verify_request() {
        if (!check_ajax_referer('ahgmh_admin_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Sicherheitspruefung fehlgeschlagen.', 'abschussplan-hgmh')
            ]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Unzureichende Berechtigungen.', 'abschussplan-hgmh')
            ]);
        }
    }

    /**
     * AJAX: Get all Jagdbezirke
     */
    public function ajax_get_jagdbezirke() {
        $this->verify_request();

        try {
            $include_inactive = isset($_POST['include_inactive']) && $_POST['include_inactive'] === 'true';
            $jagdbezirke = $this->service->get_all(!$include_inactive);

            wp_send_json_success([
                'jagdbezirke' => $jagdbezirke,
                'count' => count($jagdbezirke)
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Laden der Jagdbezirke: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Get single Jagdbezirk
     */
    public function ajax_get_jagdbezirk() {
        $this->verify_request();

        try {
            $id = absint($_POST['id'] ?? 0);

            if ($id <= 0) {
                wp_send_json_error([
                    'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
                ]);
                return;
            }

            $jagdbezirk = $this->service->get_by_id($id);

            if (!$jagdbezirk) {
                wp_send_json_error([
                    'message' => __('Jagdbezirk nicht gefunden.', 'abschussplan-hgmh')
                ]);
                return;
            }

            wp_send_json_success([
                'jagdbezirk' => $jagdbezirk
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Laden des Jagdbezirks: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Create new Jagdbezirk
     */
    public function ajax_create_jagdbezirk() {
        $this->verify_request();

        try {
            $data = [
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'description' => sanitize_textarea_field($_POST['description'] ?? ''),
                'meldegruppe_ids' => isset($_POST['meldegruppe_ids']) ? array_map('absint', (array) $_POST['meldegruppe_ids']) : []
            ];

            $result = $this->service->create($data);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Erstellen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Update existing Jagdbezirk
     */
    public function ajax_update_jagdbezirk() {
        $this->verify_request();

        try {
            $id = absint($_POST['id'] ?? 0);

            if ($id <= 0) {
                wp_send_json_error([
                    'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
                ]);
                return;
            }

            $data = [
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'description' => sanitize_textarea_field($_POST['description'] ?? ''),
                'meldegruppe_ids' => isset($_POST['meldegruppe_ids']) ? array_map('absint', (array) $_POST['meldegruppe_ids']) : []
            ];

            $result = $this->service->update($id, $data);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Aktualisieren: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Delete Jagdbezirk
     */
    public function ajax_delete_jagdbezirk() {
        $this->verify_request();

        try {
            $id = absint($_POST['id'] ?? 0);

            if ($id <= 0) {
                wp_send_json_error([
                    'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
                ]);
                return;
            }

            $force = isset($_POST['force']) && $_POST['force'] === 'true';

            $result = $this->service->delete($id, $force);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Loeschen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Restore deleted Jagdbezirk
     */
    public function ajax_restore_jagdbezirk() {
        $this->verify_request();

        try {
            $id = absint($_POST['id'] ?? 0);

            if ($id <= 0) {
                wp_send_json_error([
                    'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
                ]);
                return;
            }

            $result = $this->service->restore($id);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Wiederherstellen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Assign Meldegruppen to Jagdbezirk
     */
    public function ajax_assign_meldegruppen() {
        $this->verify_request();

        try {
            $jagdbezirk_id = absint($_POST['jagdbezirk_id'] ?? 0);
            $meldegruppe_ids = isset($_POST['meldegruppe_ids']) ? array_map('absint', (array) $_POST['meldegruppe_ids']) : [];

            if ($jagdbezirk_id <= 0) {
                wp_send_json_error([
                    'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
                ]);
                return;
            }

            $result = $this->service->assign_meldegruppen($jagdbezirk_id, $meldegruppe_ids);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Zuweisen der Meldegruppen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Get all available Meldegruppen
     */
    public function ajax_get_meldegruppen() {
        $this->verify_request();

        try {
            $meldegruppen = $this->service->get_all_meldegruppen();

            wp_send_json_success([
                'meldegruppen' => $meldegruppen,
                'count' => count($meldegruppen)
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Laden der Meldegruppen: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * AJAX: Get Jagdbezirk statistics
     */
    public function ajax_get_statistics() {
        $this->verify_request();

        try {
            $statistics = $this->service->get_statistics();

            wp_send_json_success([
                'statistics' => $statistics
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Fehler beim Laden der Statistiken: ', 'abschussplan-hgmh') . esc_html($e->getMessage())
            ]);
        }
    }

    /**
     * Render the Jagdbezirke admin tab content
     *
     * @return string HTML content
     */
    public function render_tab_content() {
        ob_start();
        $this->render_jagdbezirke_ui();
        return ob_get_clean();
    }

    /**
     * Render the Jagdbezirke management UI
     */
    private function render_jagdbezirke_ui() {
        $jagdbezirke = $this->service->get_all(false); // Include inactive
        $meldegruppen = $this->service->get_all_meldegruppen();
        $statistics = $this->service->get_statistics();
        ?>
        <div class="ahgmh-jagdbezirke-manager">
            <div class="ahgmh-section-header">
                <h2><?php echo esc_html__('Jagdbezirke verwalten', 'abschussplan-hgmh'); ?></h2>
                <p class="description">
                    <?php echo esc_html__('Hier koennen Sie Eigenjagdbezirke erstellen, bearbeiten und Meldegruppen zuweisen.', 'abschussplan-hgmh'); ?>
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="ahgmh-stats-grid">
                <div class="ahgmh-stat-card">
                    <span class="stat-icon dashicons dashicons-location"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($statistics['total']); ?></span>
                        <span class="stat-label"><?php echo esc_html__('Gesamt', 'abschussplan-hgmh'); ?></span>
                    </div>
                </div>
                <div class="ahgmh-stat-card">
                    <span class="stat-icon dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($statistics['active']); ?></span>
                        <span class="stat-label"><?php echo esc_html__('Aktiv', 'abschussplan-hgmh'); ?></span>
                    </div>
                </div>
                <div class="ahgmh-stat-card">
                    <span class="stat-icon dashicons dashicons-groups"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($statistics['with_meldegruppen']); ?></span>
                        <span class="stat-label"><?php echo esc_html__('Mit Meldegruppen', 'abschussplan-hgmh'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="ahgmh-actions-bar">
                <button type="button" id="add-jagdbezirk-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php echo esc_html__('Neuen Jagdbezirk anlegen', 'abschussplan-hgmh'); ?>
                </button>
                <label class="ahgmh-checkbox-label">
                    <input type="checkbox" id="show-inactive-jagdbezirke" />
                    <?php echo esc_html__('Inaktive anzeigen', 'abschussplan-hgmh'); ?>
                </label>
            </div>

            <!-- Jagdbezirke Table -->
            <div class="ahgmh-table-container">
                <table class="widefat striped ahgmh-jagdbezirke-table" id="jagdbezirke-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Name', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Beschreibung', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Meldegruppen', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="jagdbezirke-tbody">
                        <?php if (empty($jagdbezirke)): ?>
                            <tr class="no-items">
                                <td colspan="5">
                                    <?php echo esc_html__('Keine Jagdbezirke vorhanden. Erstellen Sie einen neuen Jagdbezirk.', 'abschussplan-hgmh'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jagdbezirke as $jb): ?>
                                <?php $is_active = intval($jb['is_active']) === 1; ?>
                                <tr class="jagdbezirk-row <?php echo $is_active ? '' : 'inactive-row'; ?>"
                                    data-id="<?php echo esc_attr($jb['id']); ?>"
                                    <?php echo $is_active ? '' : 'style="display:none;"'; ?>>
                                    <td>
                                        <strong><?php echo esc_html($jb['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo esc_html($jb['description'] ?: '-'); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($jb['meldegruppen_names'])): ?>
                                            <span class="meldegruppen-tags">
                                                <?php echo esc_html($jb['meldegruppen_names']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="no-meldegruppen"><?php echo esc_html__('Keine', 'abschussplan-hgmh'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_active): ?>
                                            <span class="status-badge status-active">
                                                <span class="dashicons dashicons-yes"></span>
                                                <?php echo esc_html__('Aktiv', 'abschussplan-hgmh'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <span class="dashicons dashicons-no"></span>
                                                <?php echo esc_html__('Inaktiv', 'abschussplan-hgmh'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <button type="button" class="button button-small edit-jagdbezirk"
                                                data-id="<?php echo esc_attr($jb['id']); ?>"
                                                title="<?php echo esc_attr__('Bearbeiten', 'abschussplan-hgmh'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <?php if ($is_active): ?>
                                            <button type="button" class="button button-small delete-jagdbezirk"
                                                    data-id="<?php echo esc_attr($jb['id']); ?>"
                                                    title="<?php echo esc_attr__('Deaktivieren', 'abschussplan-hgmh'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="button button-small restore-jagdbezirk"
                                                    data-id="<?php echo esc_attr($jb['id']); ?>"
                                                    title="<?php echo esc_attr__('Wiederherstellen', 'abschussplan-hgmh'); ?>">
                                                <span class="dashicons dashicons-undo"></span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add/Edit Modal -->
            <div id="jagdbezirk-modal" class="ahgmh-modal" style="display: none;">
                <div class="ahgmh-modal-content">
                    <div class="ahgmh-modal-header">
                        <h3 id="jagdbezirk-modal-title"><?php echo esc_html__('Jagdbezirk anlegen', 'abschussplan-hgmh'); ?></h3>
                        <button type="button" class="ahgmh-modal-close">&times;</button>
                    </div>
                    <form id="jagdbezirk-form">
                        <input type="hidden" id="jagdbezirk-id" name="id" value="">
                        <div class="ahgmh-modal-body">
                            <div class="ahgmh-form-group">
                                <label for="jagdbezirk-name">
                                    <?php echo esc_html__('Name', 'abschussplan-hgmh'); ?>
                                    <span class="required">*</span>
                                </label>
                                <input type="text" id="jagdbezirk-name" name="name"
                                       class="regular-text" required maxlength="255"
                                       placeholder="<?php echo esc_attr__('Name des Jagdbezirks', 'abschussplan-hgmh'); ?>">
                            </div>
                            <div class="ahgmh-form-group">
                                <label for="jagdbezirk-description">
                                    <?php echo esc_html__('Beschreibung', 'abschussplan-hgmh'); ?>
                                </label>
                                <textarea id="jagdbezirk-description" name="description"
                                          class="large-text" rows="3"
                                          placeholder="<?php echo esc_attr__('Optionale Beschreibung', 'abschussplan-hgmh'); ?>"></textarea>
                            </div>
                            <div class="ahgmh-form-group">
                                <label>
                                    <?php echo esc_html__('Meldegruppen zuweisen', 'abschussplan-hgmh'); ?>
                                </label>
                                <div class="ahgmh-checkbox-group" id="meldegruppen-checkboxes">
                                    <?php if (empty($meldegruppen)): ?>
                                        <p class="description">
                                            <?php echo esc_html__('Keine Meldegruppen verfuegbar. Bitte erstellen Sie zuerst Meldegruppen in der Wildarten-Konfiguration.', 'abschussplan-hgmh'); ?>
                                        </p>
                                    <?php else: ?>
                                        <?php foreach ($meldegruppen as $mg): ?>
                                            <label class="ahgmh-checkbox-item">
                                                <input type="checkbox" name="meldegruppe_ids[]"
                                                       value="<?php echo esc_attr($mg['id']); ?>">
                                                <span><?php echo esc_html($mg['name']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ahgmh-modal-footer">
                            <button type="button" class="button button-secondary ahgmh-modal-close">
                                <?php echo esc_html__('Abbrechen', 'abschussplan-hgmh'); ?>
                            </button>
                            <button type="submit" class="button button-primary" id="save-jagdbezirk-btn">
                                <?php echo esc_html__('Speichern', 'abschussplan-hgmh'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="delete-confirm-modal" class="ahgmh-modal" style="display: none;">
                <div class="ahgmh-modal-content ahgmh-modal-small">
                    <div class="ahgmh-modal-header">
                        <h3><?php echo esc_html__('Jagdbezirk deaktivieren', 'abschussplan-hgmh'); ?></h3>
                        <button type="button" class="ahgmh-modal-close">&times;</button>
                    </div>
                    <div class="ahgmh-modal-body">
                        <p>
                            <?php echo esc_html__('Moechten Sie diesen Jagdbezirk wirklich deaktivieren?', 'abschussplan-hgmh'); ?>
                        </p>
                        <p class="jagdbezirk-name-confirm"><strong></strong></p>
                        <p class="description">
                            <?php echo esc_html__('Der Jagdbezirk wird nicht geloescht, sondern nur deaktiviert und kann spaeter wiederhergestellt werden.', 'abschussplan-hgmh'); ?>
                        </p>
                    </div>
                    <div class="ahgmh-modal-footer">
                        <input type="hidden" id="delete-jagdbezirk-id" value="">
                        <button type="button" class="button button-secondary ahgmh-modal-close">
                            <?php echo esc_html__('Abbrechen', 'abschussplan-hgmh'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="confirm-delete-btn">
                            <?php echo esc_html__('Deaktivieren', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .ahgmh-jagdbezirke-manager {
            padding: 20px 0;
        }
        .ahgmh-section-header {
            margin-bottom: 20px;
        }
        .ahgmh-section-header h2 {
            margin-bottom: 5px;
        }
        .ahgmh-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .ahgmh-stat-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .ahgmh-stat-card .stat-icon {
            font-size: 30px;
            color: #0073aa;
        }
        .ahgmh-stat-card .stat-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        .ahgmh-stat-card .stat-label {
            color: #666;
            font-size: 13px;
        }
        .ahgmh-actions-bar {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .ahgmh-checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .ahgmh-table-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .ahgmh-jagdbezirke-table {
            margin: 0;
        }
        .ahgmh-jagdbezirke-table th,
        .ahgmh-jagdbezirke-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        .inactive-row {
            background-color: #f9f9f9 !important;
            opacity: 0.7;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .status-badge .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .meldegruppen-tags {
            display: inline-block;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .no-meldegruppen {
            color: #999;
            font-style: italic;
        }
        .actions-cell {
            white-space: nowrap;
        }
        .actions-cell .button {
            margin-right: 5px;
        }
        .actions-cell .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            vertical-align: text-top;
        }

        /* Modal Styles */
        .ahgmh-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ahgmh-modal-content {
            background: #fff;
            border-radius: 4px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
        }
        .ahgmh-modal-small {
            max-width: 450px;
        }
        .ahgmh-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ahgmh-modal-header h3 {
            margin: 0;
        }
        .ahgmh-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            line-height: 1;
        }
        .ahgmh-modal-close:hover {
            color: #000;
        }
        .ahgmh-modal-body {
            padding: 20px;
        }
        .ahgmh-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        .ahgmh-modal-footer .button {
            margin-left: 10px;
        }
        .ahgmh-form-group {
            margin-bottom: 15px;
        }
        .ahgmh-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .ahgmh-form-group .required {
            color: #dc3232;
        }
        .ahgmh-form-group input[type="text"],
        .ahgmh-form-group textarea {
            width: 100%;
        }
        .ahgmh-checkbox-group {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            background: #fafafa;
        }
        .ahgmh-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
            cursor: pointer;
        }
        .ahgmh-checkbox-item:hover {
            background: #f0f0f0;
        }
        .jagdbezirk-name-confirm {
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = '<?php echo wp_create_nonce('ahgmh_admin_nonce'); ?>';

            // Show/hide inactive rows
            $('#show-inactive-jagdbezirke').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.jagdbezirk-row.inactive-row').show();
                } else {
                    $('.jagdbezirk-row.inactive-row').hide();
                }
            });

            // Open add modal
            $('#add-jagdbezirk-btn').on('click', function() {
                $('#jagdbezirk-modal-title').text('<?php echo esc_js(__('Jagdbezirk anlegen', 'abschussplan-hgmh')); ?>');
                $('#jagdbezirk-form')[0].reset();
                $('#jagdbezirk-id').val('');
                $('#meldegruppen-checkboxes input[type="checkbox"]').prop('checked', false);
                $('#jagdbezirk-modal').fadeIn(200);
            });

            // Open edit modal
            $(document).on('click', '.edit-jagdbezirk', function() {
                var id = $(this).data('id');
                loadJagdbezirkForEdit(id);
            });

            // Load Jagdbezirk for editing
            function loadJagdbezirkForEdit(id) {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_get_jagdbezirk',
                        nonce: nonce,
                        id: id
                    },
                    beforeSend: function() {
                        $('#jagdbezirk-modal').fadeIn(200);
                        $('#jagdbezirk-modal-title').text('<?php echo esc_js(__('Laden...', 'abschussplan-hgmh')); ?>');
                    },
                    success: function(response) {
                        if (response.success) {
                            var jb = response.data.jagdbezirk;
                            $('#jagdbezirk-modal-title').text('<?php echo esc_js(__('Jagdbezirk bearbeiten', 'abschussplan-hgmh')); ?>');
                            $('#jagdbezirk-id').val(jb.id);
                            $('#jagdbezirk-name').val(jb.name);
                            $('#jagdbezirk-description').val(jb.description || '');

                            // Set checkboxes
                            $('#meldegruppen-checkboxes input[type="checkbox"]').prop('checked', false);
                            if (jb.meldegruppe_ids && jb.meldegruppe_ids.length > 0) {
                                jb.meldegruppe_ids.forEach(function(mgId) {
                                    $('#meldegruppen-checkboxes input[value="' + mgId + '"]').prop('checked', true);
                                });
                            }
                        } else {
                            alert(response.data.message || '<?php echo esc_js(__('Fehler beim Laden', 'abschussplan-hgmh')); ?>');
                            $('#jagdbezirk-modal').fadeOut(200);
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Serverfehler', 'abschussplan-hgmh')); ?>');
                        $('#jagdbezirk-modal').fadeOut(200);
                    }
                });
            }

            // Save Jagdbezirk
            $('#jagdbezirk-form').on('submit', function(e) {
                e.preventDefault();

                var id = $('#jagdbezirk-id').val();
                var name = $('#jagdbezirk-name').val().trim();
                var description = $('#jagdbezirk-description').val().trim();
                var meldegruppeIds = [];

                $('#meldegruppen-checkboxes input[type="checkbox"]:checked').each(function() {
                    meldegruppeIds.push($(this).val());
                });

                if (!name) {
                    alert('<?php echo esc_js(__('Bitte geben Sie einen Namen ein.', 'abschussplan-hgmh')); ?>');
                    return;
                }

                var action = id ? 'ahgmh_update_jagdbezirk' : 'ahgmh_create_jagdbezirk';

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: action,
                        nonce: nonce,
                        id: id,
                        name: name,
                        description: description,
                        meldegruppe_ids: meldegruppeIds
                    },
                    beforeSend: function() {
                        $('#save-jagdbezirk-btn').prop('disabled', true).text('<?php echo esc_js(__('Speichern...', 'abschussplan-hgmh')); ?>');
                    },
                    success: function(response) {
                        $('#save-jagdbezirk-btn').prop('disabled', false).text('<?php echo esc_js(__('Speichern', 'abschussplan-hgmh')); ?>');

                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php echo esc_js(__('Fehler beim Speichern', 'abschussplan-hgmh')); ?>');
                        }
                    },
                    error: function() {
                        $('#save-jagdbezirk-btn').prop('disabled', false).text('<?php echo esc_js(__('Speichern', 'abschussplan-hgmh')); ?>');
                        alert('<?php echo esc_js(__('Serverfehler', 'abschussplan-hgmh')); ?>');
                    }
                });
            });

            // Delete confirmation
            $(document).on('click', '.delete-jagdbezirk', function() {
                var id = $(this).data('id');
                var name = $(this).closest('tr').find('td:first strong').text();

                $('#delete-jagdbezirk-id').val(id);
                $('.jagdbezirk-name-confirm strong').text(name);
                $('#delete-confirm-modal').fadeIn(200);
            });

            // Confirm delete
            $('#confirm-delete-btn').on('click', function() {
                var id = $('#delete-jagdbezirk-id').val();

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_delete_jagdbezirk',
                        nonce: nonce,
                        id: id
                    },
                    beforeSend: function() {
                        $('#confirm-delete-btn').prop('disabled', true).text('<?php echo esc_js(__('Deaktivieren...', 'abschussplan-hgmh')); ?>');
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php echo esc_js(__('Fehler beim Deaktivieren', 'abschussplan-hgmh')); ?>');
                            $('#confirm-delete-btn').prop('disabled', false).text('<?php echo esc_js(__('Deaktivieren', 'abschussplan-hgmh')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Serverfehler', 'abschussplan-hgmh')); ?>');
                        $('#confirm-delete-btn').prop('disabled', false).text('<?php echo esc_js(__('Deaktivieren', 'abschussplan-hgmh')); ?>');
                    }
                });
            });

            // Restore Jagdbezirk
            $(document).on('click', '.restore-jagdbezirk', function() {
                var id = $(this).data('id');

                if (!confirm('<?php echo esc_js(__('Moechten Sie diesen Jagdbezirk wiederherstellen?', 'abschussplan-hgmh')); ?>')) {
                    return;
                }

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ahgmh_restore_jagdbezirk',
                        nonce: nonce,
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php echo esc_js(__('Fehler beim Wiederherstellen', 'abschussplan-hgmh')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Serverfehler', 'abschussplan-hgmh')); ?>');
                    }
                });
            });

            // Close modals
            $(document).on('click', '.ahgmh-modal-close', function() {
                $(this).closest('.ahgmh-modal').fadeOut(200);
            });

            // Close modal on overlay click
            $('.ahgmh-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(200);
                }
            });

            // Close modal on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.ahgmh-modal').fadeOut(200);
                }
            });
        });
        </script>
        <?php
    }
}
