<?php
/**
 * Wildart View - Renders Master-Detail UI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AHGMH_Wildart_View {
    
    /**
     * Render Master-Detail UI for wildart configuration
     */
    public function render_master_detail_ui($wildarten) {
        ?>
        <div class="ahgmh-wildart-config">
            <h2><?php echo esc_html__('Wildarten-Konfiguration', 'abschussplan-hgmh'); ?></h2>
            
            <div class="ahgmh-master-detail">
                <!-- Master Panel (Left Sidebar) -->
                <div class="ahgmh-master-panel">
                    <div class="master-header">
                        <h3><?php echo esc_html__('Wildarten', 'abschussplan-hgmh'); ?></h3>
                        <button id="add-new-wildart" class="button button-primary button-small">
                            <?php echo esc_html__('+ Neue Wildart', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>
                    
                    <div class="wildart-list">
                        <?php if (!empty($wildarten)): ?>
                            <?php foreach ($wildarten as $index => $wildart): ?>
                                <div class="wildart-item <?php echo $index === 0 ? 'active' : ''; ?>" data-wildart="<?php echo esc_attr($wildart); ?>">
                                    <span class="wildart-name"><?php echo esc_html($wildart); ?></span>
                                    <button class="wildart-delete" data-wildart="<?php echo esc_attr($wildart); ?>" title="<?php echo esc_attr__('Löschen', 'abschussplan-hgmh'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-wildarten"><?php echo esc_html__('Noch keine Wildarten konfiguriert.', 'abschussplan-hgmh'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Detail Panel (Right) -->
                <div class="ahgmh-detail-panel">
                    <div class="detail-placeholder">
                        <p><?php echo esc_html__('Wählen Sie eine Wildart aus der Liste, um die Konfiguration zu bearbeiten.', 'abschussplan-hgmh'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render detail panel for specific wildart
     */
    public function render_detail_panel($wildart, $config) {
        ob_start();
        ?>
        <div class="wildart-detail" data-wildart="<?php echo esc_attr($wildart); ?>">
            <div class="detail-header">
                <h3><?php echo esc_html($wildart); ?> - <?php echo esc_html__('Konfiguration', 'abschussplan-hgmh'); ?></h3>
            </div>
            
            <!-- Categories Configuration -->
            <div class="config-section">
                <h4><?php echo esc_html__('Kategorien', 'abschussplan-hgmh'); ?></h4>
                <div class="categories-config">
                    <?php if (!empty($config['categories'])): ?>
                        <?php foreach ($config['categories'] as $index => $category): ?>
                            <div class="category-row">
                                <input type="text" class="category-input" value="<?php echo esc_attr($category); ?>" />
                                <button class="button remove-category"><?php echo esc_html__('Entfernen', 'abschussplan-hgmh'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button class="button add-category"><?php echo esc_html__('+ Kategorie hinzufügen', 'abschussplan-hgmh'); ?></button>
                </div>
                <div class="config-actions">
                    <button class="button button-primary save-categories" data-wildart="<?php echo esc_attr($wildart); ?>">
                        <?php echo esc_html__('Kategorien speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Meldegruppen Configuration -->
            <div class="config-section">
                <h4><?php echo esc_html__('Meldegruppen', 'abschussplan-hgmh'); ?></h4>
                <div class="meldegruppen-config">
                    <?php if (!empty($config['meldegruppen'])): ?>
                        <?php foreach ($config['meldegruppen'] as $index => $meldegruppe): ?>
                            <div class="meldegruppe-row">
                                <input type="text" class="meldegruppe-input" value="<?php echo esc_attr($meldegruppe); ?>" />
                                <button class="button remove-meldegruppe"><?php echo esc_html__('Entfernen', 'abschussplan-hgmh'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button class="button add-meldegruppe"><?php echo esc_html__('+ Meldegruppe hinzufügen', 'abschussplan-hgmh'); ?></button>
                </div>
                <div class="config-actions">
                    <button class="button button-primary save-meldegruppen" data-wildart="<?php echo esc_attr($wildart); ?>">
                        <?php echo esc_html__('Meldegruppen speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Limits Configuration -->
            <div class="config-section">
                <h4><?php echo esc_html__('Limits-Konfiguration', 'abschussplan-hgmh'); ?></h4>
                
                <!-- Limit Mode Selection -->
                <div class="limit-mode-selection">
                    <label>
                        <input type="radio" name="limit_mode_<?php echo esc_attr($wildart); ?>" value="meldegruppen_specific" class="limit-mode-radio" data-wildart="<?php echo esc_attr($wildart); ?>" <?php checked($config['limit_mode'], 'meldegruppen_specific'); ?>>
                        <?php echo esc_html__('Meldegruppen-spezifische Limits', 'abschussplan-hgmh'); ?>
                    </label>
                    <label>
                        <input type="radio" name="limit_mode_<?php echo esc_attr($wildart); ?>" value="hegegemeinschaft_total" class="limit-mode-radio" data-wildart="<?php echo esc_attr($wildart); ?>" <?php checked($config['limit_mode'], 'hegegemeinschaft_total'); ?>>
                        <?php echo esc_html__('Hegegemeinschaft-Gesamt-Limits', 'abschussplan-hgmh'); ?>
                    </label>
                </div>
                
                <!-- Limits Table -->
                <div class="limits-configuration">
                    <?php $this->render_limits_table($wildart, $config); ?>
                </div>
                
                <div class="config-actions">
                    <button class="button button-primary save-limits-btn" data-wildart="<?php echo esc_attr($wildart); ?>">
                        <?php echo esc_html__('Limits speichern', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add category
            $(document).on('click', '.add-category', function(e) {
                e.preventDefault();
                var newRow = '<div class="category-row">' +
                    '<input type="text" class="category-input" value="" placeholder="Neue Kategorie" />' +
                    '<button class="button remove-category">Entfernen</button>' +
                    '</div>';
                $(this).before(newRow);
            });
            
            // Remove category
            $(document).on('click', '.remove-category', function(e) {
                e.preventDefault();
                $(this).closest('.category-row').remove();
            });
            
            // Add meldegruppe
            $(document).on('click', '.add-meldegruppe', function(e) {
                e.preventDefault();
                var newRow = '<div class="meldegruppe-row">' +
                    '<input type="text" class="meldegruppe-input" value="" placeholder="Neue Meldegruppe" />' +
                    '<button class="button remove-meldegruppe">Entfernen</button>' +
                    '</div>';
                $(this).before(newRow);
            });
            
            // Remove meldegruppe
            $(document).on('click', '.remove-meldegruppe', function(e) {
                e.preventDefault();
                $(this).closest('.meldegruppe-row').remove();
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render limits table
     */
    private function render_limits_table($wildart, $config) {
        $categories = $config['categories'] ?? [];
        $meldegruppen = $config['meldegruppen'] ?? [];
        $limits = $config['limits'] ?? [];
        $mode = $config['limit_mode'] ?? 'meldegruppen_specific';
        
        if (empty($categories)) {
            echo '<p class="notice notice-warning">' . esc_html__('Bitte konfigurieren Sie zuerst Kategorien für diese Wildart.', 'abschussplan-hgmh') . '</p>';
            return;
        }
        
        if (empty($meldegruppen)) {
            echo '<p class="notice notice-warning">' . esc_html__('Bitte konfigurieren Sie zuerst Meldegruppen für diese Wildart.', 'abschussplan-hgmh') . '</p>';
            return;
        }
        
        ?>
        <div class="limits-table-container">
            <?php if ($mode === 'meldegruppen_specific'): ?>
                <!-- Meldegruppen-spezifische Limits Tabelle -->
                <h4><?php echo esc_html__('Abschuss-Limits (Meldegruppen-spezifisch)', 'abschussplan-hgmh'); ?></h4>
                <table class="widefat striped limits-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                            <?php foreach ($meldegruppen as $meldegruppe): ?>
                                <th><?php echo esc_html($meldegruppe); ?></th>
                            <?php endforeach; ?>
                            <th><?php echo esc_html__('Gesamt', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo esc_html($category); ?></strong></td>
                                <?php foreach ($meldegruppen as $meldegruppe): ?>
                                    <td>
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
                                <td>
                                    <span id="gesamt-<?php echo esc_attr(strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $category))); ?>" class="gesamt-value">0</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php else: ?>
                <!-- Hegegemeinschaft-Gesamt-Limits Tabelle -->
                <h4><?php echo esc_html__('Abschuss-Limits (Hegegemeinschaft-Gesamt)', 'abschussplan-hgmh'); ?></h4>
                <table class="widefat striped limits-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Gesamt-Limit', 'abschussplan-hgmh'); ?></th>
                            <th><?php echo esc_html__('Status', 'abschussplan-hgmh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo esc_html($category); ?></strong></td>
                                <td>
                                    <input type="number" 
                                           class="limit-input regular-text limit-validation" 
                                           data-meldegruppe="gesamt" 
                                           data-kategorie="<?php echo esc_attr($category); ?>"
                                           value="<?php echo esc_attr($limits['gesamt'][$category] ?? '0'); ?>" 
                                           min="0" 
                                           step="1"
                                           title="<?php echo esc_attr__('Nur positive Zahlen erlaubt', 'abschussplan-hgmh'); ?>" />
                                </td>
                                <td>
                                    <span class="limit-status-badge">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php echo esc_html__('Aktiv', 'abschussplan-hgmh'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Validate negative values on input
            $(document).on('input', '.limit-validation', function() {
                var value = parseInt($(this).val());
                if (isNaN(value) || value < 0) {
                    $(this).val('0');
                    $(this).css('border-color', '#dc3232');
                    showLimitValidationError('Negative Werte sind nicht erlaubt. Wert wurde auf 0 gesetzt.');
                } else {
                    $(this).css('border-color', '');
                }
                
                // Update totals for meldegruppen_specific mode
                updateGesamtTotals();
            });
            
            function showLimitValidationError(message) {
                if ($('.limit-validation-notice').length === 0) {
                    $('<div class="notice notice-error limit-validation-notice"><p>' + message + '</p></div>')
                        .insertBefore('.limits-table-container')
                        .delay(3000)
                        .fadeOut(function() { $(this).remove(); });
                }
            }
            
            function updateGesamtTotals() {
                var totals = {};
                $('.limit-input').each(function() {
                    if ($(this).data('meldegruppe') === 'gesamt') return; // Skip total mode inputs
                    
                    var kategorie = $(this).data('kategorie');
                    var value = parseInt($(this).val()) || 0;
                    
                    if (!totals[kategorie]) totals[kategorie] = 0;
                    totals[kategorie] += value;
                });
                
                // Update gesamt displays
                for (var kategorie in totals) {
                    var gesamtId = 'gesamt-' + kategorie.toLowerCase().replace(/[^a-z0-9]/g, '-');
                    $('#' + gesamtId).text(totals[kategorie]);
                }
            }
            
            // Initialize totals
            updateGesamtTotals();
        });
        </script>
        <?php
    }
}
