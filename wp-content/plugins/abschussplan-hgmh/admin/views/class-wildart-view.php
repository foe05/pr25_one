<?php
/**
 * Wildart View - Renders Master-Detail UI
 *
 * Uses WordPress-native postbox and form-table patterns.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Wildart_View {

    /**
     * Render Master-Detail UI for wildart configuration
     *
     * @param array $wildarten List of configured species
     */
    public function render_master_detail_ui($wildarten) {
        ?>
        <div class="ahgmh-wildart-config" style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; margin-top: 20px;">
            <!-- Master Panel (Left Sidebar) -->
            <div class="postbox" style="margin: 0;">
                <div class="postbox-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="hndle" style="border: 0;"><?php echo esc_html__('Wildarten', 'abschussplan-hgmh'); ?></h2>
                    <div style="padding-right: 12px;">
                        <button id="add-new-wildart" class="button button-primary button-small">
                            <?php echo esc_html__('+ Neue Wildart', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>
                </div>
                <div class="inside" style="padding: 0;">
                    <div class="wildart-list" id="sortable-wildart-list">
                        <?php if (!empty($wildarten)): ?>
                            <?php foreach ($wildarten as $index => $wildart): ?>
                                <div class="wildart-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                     data-wildart="<?php echo esc_attr($wildart); ?>"
                                     data-order="<?php echo esc_attr($index); ?>"
                                     style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-bottom: 1px solid #f0f0f1; cursor: pointer;<?php echo $index === 0 ? ' background: #f0f6fc;' : ''; ?>">
                                    <span class="wildart-drag-handle dashicons dashicons-menu" title="<?php echo esc_attr__('Ziehen zum Sortieren', 'abschussplan-hgmh'); ?>" style="color: #c3c4c7; cursor: move;"></span>
                                    <span class="wildart-name" style="flex: 1; font-weight: 600;"><?php echo esc_html($wildart); ?></span>
                                    <button class="wildart-delete button-link" data-wildart="<?php echo esc_attr($wildart); ?>" title="<?php echo esc_attr__('Loeschen', 'abschussplan-hgmh'); ?>" style="color: #b32d2e; text-decoration: none;">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="padding: 20px; text-align: center; color: #646970;">
                                <?php echo esc_html__('Noch keine Wildarten konfiguriert.', 'abschussplan-hgmh'); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($wildarten)): ?>
                        <div class="wildart-sort-actions" style="padding: 10px 12px;">
                            <button id="save-wildart-order" class="button button-secondary" style="display: none; width: 100%;">
                                <span class="dashicons dashicons-yes" style="margin-top: 4px;"></span>
                                <?php echo esc_html__('Reihenfolge speichern', 'abschussplan-hgmh'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Panel (Right) -->
            <div class="postbox ahgmh-detail-panel" style="margin: 0;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html__('Konfiguration', 'abschussplan-hgmh'); ?></h2>
                </div>
                <div class="inside">
                    <div class="detail-placeholder" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; color: #646970;">
                        <span class="dashicons dashicons-admin-settings" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 15px;"></span>
                        <p><?php echo esc_html__('Waehlen Sie eine Wildart aus der Liste, um die Konfiguration zu bearbeiten.', 'abschussplan-hgmh'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render detail panel for specific wildart
     *
     * @param string $wildart Species name
     * @param array  $config  Species configuration
     * @return string Rendered HTML
     */
    public function render_detail_panel($wildart, $config) {
        ob_start();
        ?>
        <div class="wildart-detail" data-wildart="<?php echo esc_attr($wildart); ?>">
            <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1;">
                <?php echo esc_html($wildart); ?> &mdash; <?php echo esc_html__('Konfiguration', 'abschussplan-hgmh'); ?>
            </h3>

            <!-- Categories Configuration -->
            <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f1;">
                <h4 style="margin-top: 0;"><?php echo esc_html__('Kategorien', 'abschussplan-hgmh'); ?></h4>
                <p class="description" style="margin-bottom: 10px;">
                    <?php echo esc_html__('Definieren Sie die Unterkategorien fuer diese Wildart (z.B. Geschlecht, Altersklasse).', 'abschussplan-hgmh'); ?>
                </p>
                <div class="categories-config">
                    <?php if (!empty($config['categories'])): ?>
                        <?php foreach ($config['categories'] as $index => $category): ?>
                            <div class="category-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" class="category-input regular-text" value="<?php echo esc_attr($category); ?>" />
                                <button class="button remove-category"><?php echo esc_html__('Entfernen', 'abschussplan-hgmh'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button class="button add-category" style="margin-top: 4px;">
                        <?php echo esc_html__('+ Kategorie hinzufuegen', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
                <p class="submit" style="margin-bottom: 0;">
                    <button class="button button-primary save-categories" data-wildart="<?php echo esc_attr($wildart); ?>">
                        <?php echo esc_html__('Kategorien speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </p>
            </div>

            <!-- Meldegruppen Configuration -->
            <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f1;">
                <h4 style="margin-top: 0;"><?php echo esc_html__('Meldegruppen', 'abschussplan-hgmh'); ?></h4>
                <p class="description" style="margin-bottom: 10px;">
                    <?php echo esc_html__('Definieren Sie die Meldegruppen (Reviere/Bereiche) fuer diese Wildart.', 'abschussplan-hgmh'); ?>
                </p>
                <div class="meldegruppen-config">
                    <?php if (!empty($config['meldegruppen'])): ?>
                        <?php foreach ($config['meldegruppen'] as $index => $meldegruppe): ?>
                            <div class="meldegruppe-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" class="meldegruppe-input regular-text" value="<?php echo esc_attr($meldegruppe); ?>" />
                                <button class="button remove-meldegruppe"><?php echo esc_html__('Entfernen', 'abschussplan-hgmh'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button class="button add-meldegruppe" style="margin-top: 4px;">
                        <?php echo esc_html__('+ Meldegruppe hinzufuegen', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
                <p class="submit" style="margin-bottom: 0;">
                    <button class="button button-primary save-meldegruppen" data-wildart="<?php echo esc_attr($wildart); ?>">
                        <?php echo esc_html__('Meldegruppen speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </p>
            </div>

            <!-- Limits Configuration -->
            <div>
                <h4 style="margin-top: 0;"><?php echo esc_html__('Abschuss-Limits', 'abschussplan-hgmh'); ?></h4>
                <p class="description" style="margin-bottom: 10px;">
                    <?php echo esc_html__('Legen Sie fest, wie die Soll-Werte (Kontingente) aufgeteilt werden.', 'abschussplan-hgmh'); ?>
                </p>

                <!-- Limit Mode Selection -->
                <fieldset style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 6px; cursor: pointer;">
                        <input type="radio" name="limit_mode_<?php echo esc_attr($wildart); ?>" value="meldegruppen_specific" class="limit-mode-radio" data-wildart="<?php echo esc_attr($wildart); ?>" <?php checked($config['limit_mode'], 'meldegruppen_specific'); ?>>
                        <strong><?php echo esc_html__('Meldegruppen-spezifische Limits', 'abschussplan-hgmh'); ?></strong>
                        <span style="display: block; margin-left: 24px; color: #646970; font-size: 12px;">
                            <?php echo esc_html__('Jede Meldegruppe erhaelt ein eigenes Kontingent pro Kategorie.', 'abschussplan-hgmh'); ?>
                        </span>
                    </label>
                    <label style="display: block; cursor: pointer;">
                        <input type="radio" name="limit_mode_<?php echo esc_attr($wildart); ?>" value="hegegemeinschaft_total" class="limit-mode-radio" data-wildart="<?php echo esc_attr($wildart); ?>" <?php checked($config['limit_mode'], 'hegegemeinschaft_total'); ?>>
                        <strong><?php echo esc_html__('Hegegemeinschaft-Gesamt-Limits', 'abschussplan-hgmh'); ?></strong>
                        <span style="display: block; margin-left: 24px; color: #646970; font-size: 12px;">
                            <?php echo esc_html__('Ein gemeinsames Kontingent fuer die gesamte Hegegemeinschaft pro Kategorie.', 'abschussplan-hgmh'); ?>
                        </span>
                    </label>
                </fieldset>

                <!-- Limits Table -->
                <div class="limits-configuration">
                    <?php $this->render_limits_table($wildart, $config); ?>
                </div>

                <p class="submit" style="margin-bottom: 0;">
                    <button class="button button-primary save-limits-btn" data-wildart="<?php echo esc_attr($wildart); ?>">
                        <?php echo esc_html__('Limits speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
        // Note: All JavaScript handlers are in admin-modern.js
        return ob_get_clean();
    }

    /**
     * Render limits table
     *
     * @param string $wildart Species name
     * @param array  $config  Species configuration including categories, meldegruppen, limits, limit_mode
     */
    private function render_limits_table($wildart, $config) {
        $categories = $config['categories'] ?? [];
        $meldegruppen = $config['meldegruppen'] ?? [];
        $limits = $config['limits'] ?? [];
        $mode = $config['limit_mode'] ?? 'meldegruppen_specific';

        if (empty($categories)) {
            ?>
            <div class="notice notice-warning inline" style="margin: 0;">
                <p><?php echo esc_html__('Bitte konfigurieren Sie zuerst Kategorien fuer diese Wildart.', 'abschussplan-hgmh'); ?></p>
            </div>
            <?php
            return;
        }

        if (empty($meldegruppen)) {
            ?>
            <div class="notice notice-warning inline" style="margin: 0;">
                <p><?php echo esc_html__('Bitte konfigurieren Sie zuerst Meldegruppen fuer diese Wildart.', 'abschussplan-hgmh'); ?></p>
            </div>
            <?php
            return;
        }

        ?>
        <div class="limits-table-container">
            <?php if ($mode === 'meldegruppen_specific'): ?>
                <!-- Meldegruppen-spezifische Limits Tabelle -->
                <table class="wp-list-table widefat striped limits-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                            <?php foreach ($meldegruppen as $meldegruppe): ?>
                                <th style="text-align: center;"><?php echo esc_html($meldegruppe); ?></th>
                            <?php endforeach; ?>
                            <th style="text-align: center;"><?php echo esc_html__('Gesamt', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo esc_html($category); ?></strong></td>
                                <?php foreach ($meldegruppen as $meldegruppe): ?>
                                    <td style="text-align: center;">
                                        <input type="number"
                                               class="limit-input small-text limit-validation"
                                               data-meldegruppe="<?php echo esc_attr($meldegruppe); ?>"
                                               data-kategorie="<?php echo esc_attr($category); ?>"
                                               value="<?php echo esc_attr($limits[$meldegruppe][$category] ?? '0'); ?>"
                                               min="0"
                                               step="1"
                                               title="<?php echo esc_attr__('Nur positive Zahlen erlaubt', 'abschussplan-hgmh'); ?>" />
                                    </td>
                                <?php endforeach; ?>
                                <td style="text-align: center;">
                                    <span id="gesamt-<?php echo esc_attr(strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $category))); ?>" class="gesamt-value" style="font-weight: 600;">0</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <!-- Hegegemeinschaft-Gesamt-Limits Tabelle -->
                <table class="wp-list-table widefat striped limits-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                            <th style="text-align: center;"><?php echo esc_html__('Gesamt-Limit', 'abschussplan-hgmh'); ?></th>
                            <th style="text-align: center;"><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo esc_html($category); ?></strong></td>
                                <td style="text-align: center;">
                                    <input type="number"
                                           class="limit-input small-text limit-validation"
                                           data-meldegruppe="gesamt"
                                           data-kategorie="<?php echo esc_attr($category); ?>"
                                           value="<?php echo esc_attr($limits['gesamt'][$category] ?? '0'); ?>"
                                           min="0"
                                           step="1"
                                           title="<?php echo esc_attr__('Nur positive Zahlen erlaubt', 'abschussplan-hgmh'); ?>" />
                                </td>
                                <td style="text-align: center;">
                                    <span style="display: inline-block; background: #00a32a; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 14px; width: 14px; height: 14px; margin-top: 1px;"></span>
                                        <?php echo esc_html__('Aktiv', 'abschussplan-hgmh'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        // Note: All JavaScript handlers are in admin-modern.js
    }
}
