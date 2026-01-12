<?php
/**
 * Import View - Renders import interface UI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Import_View {

    /**
     * Render main import page
     */
    public function render_import_page() {
        ?>
        <div class="wrap ahgmh-admin-modern">
            <h1 class="ahgmh-page-title">
                <span class="dashicons dashicons-upload"></span>
                <?php echo esc_html__('Daten importieren', 'abschussplan-hgmh'); ?>
            </h1>

            <div class="ahgmh-import-container">
                <!-- Step 1: File Upload -->
                <div class="ahgmh-import-step" id="step-upload" data-step="1">
                    <?php $this->render_upload_section(); ?>
                </div>

                <!-- Step 2: Column Mapping -->
                <div class="ahgmh-import-step" id="step-mapping" data-step="2" style="display: none;">
                    <?php $this->render_mapping_section(); ?>
                </div>

                <!-- Step 3: Preview & Validation -->
                <div class="ahgmh-import-step" id="step-preview" data-step="3" style="display: none;">
                    <?php $this->render_preview_section(); ?>
                </div>

                <!-- Step 4: Import Complete -->
                <div class="ahgmh-import-step" id="step-complete" data-step="4" style="display: none;">
                    <?php $this->render_complete_section(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render file upload section (Step 1)
     */
    private function render_upload_section() {
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Schritt 1: Datei hochladen', 'abschussplan-hgmh'); ?></h2>

            <div class="ahgmh-import-instructions">
                <p><?php echo esc_html__('Laden Sie eine CSV- oder Excel-Datei (.xlsx) mit Ihren Abschussmeldungen hoch.', 'abschussplan-hgmh'); ?></p>
                <p><?php echo esc_html__('Unterstützte Formate:', 'abschussplan-hgmh'); ?></p>
                <ul>
                    <li><?php echo esc_html__('CSV-Dateien (mit Komma oder Semikolon als Trennzeichen)', 'abschussplan-hgmh'); ?></li>
                    <li><?php echo esc_html__('Excel-Dateien (.xlsx)', 'abschussplan-hgmh'); ?></li>
                    <li><?php echo esc_html__('LJV-Vorlagen (Landesjagdverband Hessen, Bayern, NRW)', 'abschussplan-hgmh'); ?></li>
                </ul>
            </div>

            <!-- Dropzone -->
            <div id="import-dropzone" class="ahgmh-dropzone">
                <div class="dropzone-content">
                    <span class="dashicons dashicons-cloud-upload"></span>
                    <p class="dropzone-text"><?php echo esc_html__('Datei hier ablegen oder klicken zum Auswählen', 'abschussplan-hgmh'); ?></p>
                    <p class="dropzone-hint"><?php echo esc_html__('Unterstützt: CSV, XLSX (max. 10 MB)', 'abschussplan-hgmh'); ?></p>
                </div>
                <input type="file" id="import-file-input" accept=".csv,.xlsx" style="display: none;" />
            </div>

            <!-- Upload Progress -->
            <div id="upload-progress" class="ahgmh-upload-progress" style="display: none;">
                <div class="progress-bar-container">
                    <div class="progress-bar" id="upload-progress-bar"></div>
                </div>
                <p class="progress-text" id="upload-progress-text"><?php echo esc_html__('Hochladen...', 'abschussplan-hgmh'); ?></p>
            </div>

            <!-- Upload Error -->
            <div id="upload-error" class="notice notice-error" style="display: none;">
                <p id="upload-error-message"></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render column mapping section (Step 2)
     */
    private function render_mapping_section() {
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Schritt 2: Spalten zuordnen', 'abschussplan-hgmh'); ?></h2>

            <div class="ahgmh-import-instructions">
                <p><?php echo esc_html__('Ordnen Sie die Spalten aus Ihrer Datei den Datenbankfeldern zu. Die Zuordnung wurde automatisch erkannt und kann bei Bedarf angepasst werden.', 'abschussplan-hgmh'); ?></p>
            </div>

            <!-- Template Detection Info -->
            <div id="template-detection-info" class="notice notice-info" style="display: none;">
                <p id="template-detection-message"></p>
            </div>

            <!-- Mapping Table -->
            <table class="widefat striped ahgmh-mapping-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Datenbankfeld', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Spalte aus Datei', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Erforderlich', 'abschussplan-hgmh'); ?></th>
                        <th><?php echo esc_html__('Konfidenz', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody id="mapping-table-body">
                    <?php $this->render_mapping_rows(); ?>
                </tbody>
            </table>

            <!-- Mapping Actions -->
            <div class="ahgmh-import-actions">
                <button id="btn-back-to-upload" class="button">
                    <?php echo esc_html__('« Zurück', 'abschussplan-hgmh'); ?>
                </button>
                <button id="btn-continue-to-preview" class="button button-primary">
                    <?php echo esc_html__('Weiter zur Vorschau »', 'abschussplan-hgmh'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render mapping table rows (placeholder, will be populated by JavaScript)
     */
    private function render_mapping_rows() {
        $fields = array(
            'datum' => array(
                'label' => __('Datum', 'abschussplan-hgmh'),
                'required' => false
            ),
            'wildart' => array(
                'label' => __('Wildart', 'abschussplan-hgmh'),
                'required' => true
            ),
            'kategorie' => array(
                'label' => __('Kategorie', 'abschussplan-hgmh'),
                'required' => true
            ),
            'meldegruppe' => array(
                'label' => __('Meldegruppe', 'abschussplan-hgmh'),
                'required' => false
            ),
            'jagdbezirk' => array(
                'label' => __('Jagdbezirk', 'abschussplan-hgmh'),
                'required' => false
            ),
            'wus_nummer' => array(
                'label' => __('WUS-Nummer', 'abschussplan-hgmh'),
                'required' => false
            ),
            'bemerkung' => array(
                'label' => __('Bemerkung', 'abschussplan-hgmh'),
                'required' => false
            )
        );

        foreach ($fields as $field_name => $field_info) {
            ?>
            <tr data-field="<?php echo esc_attr($field_name); ?>">
                <td>
                    <strong><?php echo esc_html($field_info['label']); ?></strong>
                    <?php if ($field_info['required']): ?>
                        <span class="required-indicator" title="<?php echo esc_attr__('Erforderlich', 'abschussplan-hgmh'); ?>">*</span>
                    <?php endif; ?>
                </td>
                <td>
                    <select class="column-mapping-select" data-field="<?php echo esc_attr($field_name); ?>">
                        <option value=""><?php echo esc_html__('-- Nicht zuordnen --', 'abschussplan-hgmh'); ?></option>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </td>
                <td>
                    <?php if ($field_info['required']): ?>
                        <span class="dashicons dashicons-yes required-icon"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-minus optional-icon"></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="confidence-badge" data-confidence="0">
                        <span class="confidence-value">-</span>
                    </span>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Render preview and validation section (Step 3)
     */
    private function render_preview_section() {
        ?>
        <div class="ahgmh-panel">
            <h2><?php echo esc_html__('Schritt 3: Vorschau & Validierung', 'abschussplan-hgmh'); ?></h2>

            <div class="ahgmh-import-instructions">
                <p><?php echo esc_html__('Überprüfen Sie die Vorschau der zu importierenden Daten. Fehler und Warnungen werden unten angezeigt.', 'abschussplan-hgmh'); ?></p>
            </div>

            <!-- Validation Summary -->
            <div id="validation-summary" class="ahgmh-validation-summary" style="display: none;">
                <div id="validation-errors" class="notice notice-error" style="display: none;">
                    <p><strong><?php echo esc_html__('Fehler gefunden:', 'abschussplan-hgmh'); ?></strong></p>
                    <ul id="validation-errors-list"></ul>
                </div>

                <div id="validation-warnings" class="notice notice-warning" style="display: none;">
                    <p><strong><?php echo esc_html__('Warnungen:', 'abschussplan-hgmh'); ?></strong></p>
                    <ul id="validation-warnings-list"></ul>
                </div>

                <div id="validation-success" class="notice notice-success" style="display: none;">
                    <p>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <strong id="validation-success-message"></strong>
                    </p>
                </div>
            </div>

            <!-- Preview Table -->
            <div class="ahgmh-preview-container">
                <h3><?php echo esc_html__('Vorschau der ersten Zeilen', 'abschussplan-hgmh'); ?></h3>
                <div class="ahgmh-preview-scroll">
                    <table class="widefat striped ahgmh-preview-table" id="preview-table">
                        <thead id="preview-table-head">
                            <!-- Will be populated by JavaScript -->
                        </thead>
                        <tbody id="preview-table-body">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <p class="preview-hint"><?php echo esc_html__('Zeigt die ersten 5 Zeilen der Importdatei an', 'abschussplan-hgmh'); ?></p>
            </div>

            <!-- Preview Actions -->
            <div class="ahgmh-import-actions">
                <button id="btn-back-to-mapping" class="button">
                    <?php echo esc_html__('« Zurück zur Zuordnung', 'abschussplan-hgmh'); ?>
                </button>
                <button id="btn-execute-import" class="button button-primary" disabled>
                    <?php echo esc_html__('Import starten', 'abschussplan-hgmh'); ?>
                </button>
            </div>

            <!-- Import Progress -->
            <div id="import-progress" class="ahgmh-import-progress" style="display: none;">
                <div class="progress-bar-container">
                    <div class="progress-bar" id="import-progress-bar"></div>
                </div>
                <p class="progress-text" id="import-progress-text"></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render import complete section (Step 4)
     */
    private function render_complete_section() {
        ?>
        <div class="ahgmh-panel">
            <div class="ahgmh-import-complete">
                <span class="dashicons dashicons-yes-alt complete-icon"></span>
                <h2><?php echo esc_html__('Import abgeschlossen!', 'abschussplan-hgmh'); ?></h2>

                <!-- Import Summary -->
                <div id="import-summary" class="ahgmh-import-summary">
                    <div class="summary-stat">
                        <div class="stat-number" id="import-success-count">0</div>
                        <div class="stat-label"><?php echo esc_html__('Erfolgreich importiert', 'abschussplan-hgmh'); ?></div>
                    </div>
                    <div class="summary-stat">
                        <div class="stat-number" id="import-error-count">0</div>
                        <div class="stat-label"><?php echo esc_html__('Fehler', 'abschussplan-hgmh'); ?></div>
                    </div>
                    <div class="summary-stat">
                        <div class="stat-number" id="import-warning-count">0</div>
                        <div class="stat-label"><?php echo esc_html__('Warnungen', 'abschussplan-hgmh'); ?></div>
                    </div>
                </div>

                <!-- Import Details -->
                <div id="import-details" class="ahgmh-import-details" style="display: none;">
                    <h3><?php echo esc_html__('Details', 'abschussplan-hgmh'); ?></h3>
                    <div id="import-details-content"></div>
                </div>

                <!-- Complete Actions -->
                <div class="ahgmh-import-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=abschussplan-hgmh')); ?>" class="button button-primary">
                        <?php echo esc_html__('Zum Dashboard', 'abschussplan-hgmh'); ?>
                    </a>
                    <button id="btn-start-new-import" class="button">
                        <?php echo esc_html__('Neuen Import starten', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render inline validation error/warning for preview table
     */
    public function render_inline_validation($row_number, $errors, $warnings) {
        if (empty($errors) && empty($warnings)) {
            return '';
        }

        ob_start();
        ?>
        <div class="inline-validation">
            <?php if (!empty($errors)): ?>
                <div class="inline-errors">
                    <span class="dashicons dashicons-warning error-icon"></span>
                    <?php foreach ($errors as $error): ?>
                        <span class="error-message"><?php echo esc_html($error); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($warnings)): ?>
                <div class="inline-warnings">
                    <span class="dashicons dashicons-info warning-icon"></span>
                    <?php foreach ($warnings as $warning): ?>
                        <span class="warning-message"><?php echo esc_html($warning); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
