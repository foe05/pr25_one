<?php
/**
 * Standalone Wildarten Management Admin Page
 * Provides full CRUD interface for wildarten, categories, meldegruppen, and Obmann assignments
 *
 * @package Abschussplan_HGMH
 * @subpackage Admin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Wildarten Management Page
 * This page provides a master-detail interface for managing wildarten configuration
 */
function ahgmh_render_wildarten_management_page() {
    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('Sie haben keine Berechtigung, auf diese Seite zuzugreifen.', 'abschussplan-hgmh'));
    }

    // Initialize services and views
    $wildart_service = new AHGMH_Wildart_Service();
    $wildart_view = new AHGMH_Wildart_View();

    // Get all wildarten
    $wildarten = $wildart_service->get_all_wildarten();

    // Get configuration for first wildart (if any) to load initially
    $initial_config = null;
    $initial_wildart = null;
    if (!empty($wildarten)) {
        $initial_wildart = $wildarten[0];
        $initial_config = $wildart_service->get_wildart_config($initial_wildart);
    }

    ?>
    <div class="wrap ahgmh-admin-modern">
        <h1 class="ahgmh-page-title">
            <span class="dashicons dashicons-admin-network"></span>
            <?php echo esc_html__('Wildarten-Konfiguration', 'abschussplan-hgmh'); ?>
        </h1>

        <div class="ahgmh-page-description">
            <p><?php echo esc_html__('Verwalten Sie Wildarten, Kategorien und Meldegruppen. Wählen Sie eine Wildart aus der linken Liste, um deren Einstellungen zu bearbeiten.', 'abschussplan-hgmh'); ?></p>
        </div>

        <?php
        // Render the master-detail UI
        $wildart_view->render_master_detail_ui($wildarten);
        ?>

        <?php if ($initial_config && $initial_wildart): ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Auto-load first wildart detail panel on page load
                setTimeout(function() {
                    $('.wildart-item.active').trigger('click');
                }, 100);
            });
            </script>
        <?php endif; ?>

        <!-- Add New Wildart Modal -->
        <div id="add-wildart-modal" class="ahgmh-modal" style="display: none;">
            <div class="ahgmh-modal-content">
                <div class="ahgmh-modal-header">
                    <h2><?php echo esc_html__('Neue Wildart hinzufügen', 'abschussplan-hgmh'); ?></h2>
                    <button class="ahgmh-modal-close">&times;</button>
                </div>
                <div class="ahgmh-modal-body">
                    <label for="new-wildart-name">
                        <?php echo esc_html__('Wildart-Name', 'abschussplan-hgmh'); ?>
                        <input type="text" id="new-wildart-name" class="regular-text" placeholder="<?php echo esc_attr__('z.B. Rotwild, Damwild, Schwarzwild', 'abschussplan-hgmh'); ?>" />
                    </label>
                    <div class="ahgmh-modal-notice" style="display: none;"></div>
                </div>
                <div class="ahgmh-modal-footer">
                    <button class="button button-secondary ahgmh-modal-close">
                        <?php echo esc_html__('Abbrechen', 'abschussplan-hgmh'); ?>
                    </button>
                    <button id="save-new-wildart" class="button button-primary">
                        <?php echo esc_html__('Wildart erstellen', 'abschussplan-hgmh'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Obmann Assignment Section -->
        <div class="ahgmh-panel" style="margin-top: 30px;">
            <h2 class="panel-title">
                <span class="dashicons dashicons-groups"></span>
                <?php echo esc_html__('Obmann-Zuweisungen', 'abschussplan-hgmh'); ?>
            </h2>
            <p class="panel-description">
                <?php echo esc_html__('Weisen Sie Obleute den Meldegruppen zu. Die Obleute können dann für ihre Meldegruppen Meldungen verwalten.', 'abschussplan-hgmh'); ?>
            </p>

            <div class="ahgmh-obmann-assignment-ui">
                <div class="obmann-form-row">
                    <div class="form-field">
                        <label for="obmann-wildart-select">
                            <?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?>
                        </label>
                        <select id="obmann-wildart-select" class="regular-text">
                            <option value=""><?php echo esc_html__('Wildart wählen...', 'abschussplan-hgmh'); ?></option>
                            <?php foreach ($wildarten as $wildart): ?>
                                <option value="<?php echo esc_attr($wildart); ?>"><?php echo esc_html($wildart); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="obmann-meldegruppe-select">
                            <?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?>
                        </label>
                        <select id="obmann-meldegruppe-select" class="regular-text" disabled>
                            <option value=""><?php echo esc_html__('Erst Wildart wählen...', 'abschussplan-hgmh'); ?></option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="obmann-user-select">
                            <?php echo esc_html__('Obmann', 'abschussplan-hgmh'); ?>
                        </label>
                        <select id="obmann-user-select" class="regular-text">
                            <option value=""><?php echo esc_html__('Benutzer wählen...', 'abschussplan-hgmh'); ?></option>
                            <?php
                            $users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));
                            foreach ($users as $user):
                            ?>
                                <option value="<?php echo esc_attr($user->ID); ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <button id="assign-obmann-btn" class="button button-primary" style="margin-top: 23px;">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php echo esc_html__('Zuweisen', 'abschussplan-hgmh'); ?>
                        </button>
                    </div>
                </div>

                <div id="obmann-assignments-list" style="margin-top: 20px;">
                    <!-- Assignments will be loaded here via AJAX -->
                    <p class="description"><?php echo esc_html__('Wählen Sie eine Wildart, um Zuweisungen anzuzeigen.', 'abschussplan-hgmh'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Initialize wildart master-detail UI functionality

        // Click handler for wildart items
        $(document).on('click', '.wildart-item', function() {
            $('.wildart-item').removeClass('active');
            $(this).addClass('active');

            var wildart = $(this).data('wildart');
            loadWildartConfig(wildart);
        });

        // Add new wildart button
        $('#add-new-wildart').on('click', function() {
            $('#add-wildart-modal').fadeIn();
            $('#new-wildart-name').val('').focus();
        });

        // Close modal
        $('.ahgmh-modal-close').on('click', function() {
            $(this).closest('.ahgmh-modal').fadeOut();
        });

        // Save new wildart
        $('#save-new-wildart').on('click', function() {
            var name = $('#new-wildart-name').val().trim();
            if (!name) {
                showModalNotice('<?php echo esc_js(__('Bitte geben Sie einen Namen ein.', 'abschussplan-hgmh')); ?>', 'error');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_create_wildart',
                    nonce: ahgmh_admin.nonce,
                    name: name
                },
                success: function(response) {
                    if (response.success) {
                        showModalNotice('<?php echo esc_js(__('Wildart erfolgreich erstellt!', 'abschussplan-hgmh')); ?>', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showModalNotice(response.data || '<?php echo esc_js(__('Fehler beim Erstellen.', 'abschussplan-hgmh')); ?>', 'error');
                    }
                },
                error: function() {
                    showModalNotice('<?php echo esc_js(__('Netzwerkfehler.', 'abschussplan-hgmh')); ?>', 'error');
                }
            });
        });

        // Delete wildart
        $(document).on('click', '.wildart-delete', function(e) {
            e.stopPropagation();

            if (!confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie diese Wildart löschen möchten?', 'abschussplan-hgmh')); ?>')) {
                return;
            }

            var wildart = $(this).data('wildart');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_delete_wildart',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || '<?php echo esc_js(__('Fehler beim Löschen.', 'abschussplan-hgmh')); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Netzwerkfehler.', 'abschussplan-hgmh')); ?>');
                }
            });
        });

        // Load wildart config function
        function loadWildartConfig(wildart) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_load_wildart_config',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart
                },
                success: function(response) {
                    if (response.success) {
                        $('.ahgmh-detail-panel').html(response.data.html);
                    } else {
                        $('.ahgmh-detail-panel').html('<div class="notice notice-error"><p>' +
                            (response.data || '<?php echo esc_js(__('Fehler beim Laden.', 'abschussplan-hgmh')); ?>') +
                            '</p></div>');
                    }
                },
                error: function() {
                    $('.ahgmh-detail-panel').html('<div class="notice notice-error"><p><?php echo esc_js(__('Netzwerkfehler.', 'abschussplan-hgmh')); ?></p></div>');
                }
            });
        }

        // Save categories
        $(document).on('click', '.save-categories', function() {
            var wildart = $(this).data('wildart');
            var categories = [];

            $('.category-input').each(function() {
                var val = $(this).val().trim();
                if (val) categories.push(val);
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_save_wildart_categories',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart,
                    categories: categories
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('<?php echo esc_js(__('Kategorien gespeichert!', 'abschussplan-hgmh')); ?>', 'success');
                        loadWildartConfig(wildart);
                    } else {
                        showNotice(response.data || '<?php echo esc_js(__('Fehler beim Speichern.', 'abschussplan-hgmh')); ?>', 'error');
                    }
                }
            });
        });

        // Save meldegruppen
        $(document).on('click', '.save-meldegruppen', function() {
            var wildart = $(this).data('wildart');
            var meldegruppen = [];

            $('.meldegruppe-input').each(function() {
                var val = $(this).val().trim();
                if (val) meldegruppen.push(val);
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_save_wildart_meldegruppen',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart,
                    meldegruppen: meldegruppen
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('<?php echo esc_js(__('Meldegruppen gespeichert!', 'abschussplan-hgmh')); ?>', 'success');
                        loadWildartConfig(wildart);
                    } else {
                        showNotice(response.data || '<?php echo esc_js(__('Fehler beim Speichern.', 'abschussplan-hgmh')); ?>', 'error');
                    }
                }
            });
        });

        // Toggle limit mode
        $(document).on('change', '.limit-mode-radio', function() {
            var wildart = $(this).data('wildart');
            var mode = $(this).val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_toggle_limit_mode',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart,
                    mode: mode
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('<?php echo esc_js(__('Limit-Modus geändert!', 'abschussplan-hgmh')); ?>', 'success');
                        loadWildartConfig(wildart);
                    } else {
                        showNotice(response.data || '<?php echo esc_js(__('Fehler beim Ändern.', 'abschussplan-hgmh')); ?>', 'error');
                    }
                }
            });
        });

        // Save limits
        $(document).on('click', '.save-limits-btn', function() {
            var wildart = $(this).data('wildart');
            var limits = {};

            $('.limit-input').each(function() {
                var meldegruppe = $(this).data('meldegruppe');
                var kategorie = $(this).data('kategorie');
                var value = parseInt($(this).val()) || 0;

                if (!limits[meldegruppe]) {
                    limits[meldegruppe] = {};
                }
                limits[meldegruppe][kategorie] = value;
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_save_limits',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart,
                    limits: limits
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('<?php echo esc_js(__('Limits gespeichert!', 'abschussplan-hgmh')); ?>', 'success');
                    } else {
                        showNotice(response.data || '<?php echo esc_js(__('Fehler beim Speichern.', 'abschussplan-hgmh')); ?>', 'error');
                    }
                }
            });
        });

        // Obmann Assignment functionality

        // Load meldegruppen when wildart is selected
        $('#obmann-wildart-select').on('change', function() {
            var wildart = $(this).val();

            if (!wildart) {
                $('#obmann-meldegruppe-select').prop('disabled', true).html('<option value=""><?php echo esc_js(__('Erst Wildart wählen...', 'abschussplan-hgmh')); ?></option>');
                $('#obmann-assignments-list').html('<p class="description"><?php echo esc_js(__('Wählen Sie eine Wildart, um Zuweisungen anzuzeigen.', 'abschussplan-hgmh')); ?></p>');
                return;
            }

            // Load meldegruppen for selected wildart
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_load_wildart_config',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart
                },
                success: function(response) {
                    if (response.success && response.data.config) {
                        var meldegruppen = response.data.config.meldegruppen || [];
                        var options = '<option value=""><?php echo esc_js(__('Meldegruppe wählen...', 'abschussplan-hgmh')); ?></option>';

                        meldegruppen.forEach(function(mg) {
                            options += '<option value="' + mg + '">' + mg + '</option>';
                        });

                        $('#obmann-meldegruppe-select').prop('disabled', false).html(options);
                    }
                }
            });

            // Load existing assignments
            loadObmannAssignments(wildart);
        });

        // Assign obmann
        $('#assign-obmann-btn').on('click', function() {
            var wildart = $('#obmann-wildart-select').val();
            var meldegruppe = $('#obmann-meldegruppe-select').val();
            var userId = $('#obmann-user-select').val();

            if (!wildart || !meldegruppe || !userId) {
                alert('<?php echo esc_js(__('Bitte füllen Sie alle Felder aus.', 'abschussplan-hgmh')); ?>');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_assign_obmann',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart,
                    meldegruppe: meldegruppe,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('<?php echo esc_js(__('Obmann erfolgreich zugewiesen!', 'abschussplan-hgmh')); ?>', 'success');
                        loadObmannAssignments(wildart);
                        $('#obmann-meldegruppe-select').val('');
                        $('#obmann-user-select').val('');
                    } else {
                        alert(response.data || '<?php echo esc_js(__('Fehler bei der Zuweisung.', 'abschussplan-hgmh')); ?>');
                    }
                }
            });
        });

        // Load obmann assignments
        function loadObmannAssignments(wildart) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_get_obmann_assignments',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart
                },
                success: function(response) {
                    if (response.success && response.data.assignments) {
                        var html = '<table class="widefat striped">';
                        html += '<thead><tr>';
                        html += '<th><?php echo esc_js(__('Meldegruppe', 'abschussplan-hgmh')); ?></th>';
                        html += '<th><?php echo esc_js(__('Obmann', 'abschussplan-hgmh')); ?></th>';
                        html += '<th><?php echo esc_js(__('Aktionen', 'abschussplan-hgmh')); ?></th>';
                        html += '</tr></thead><tbody>';

                        var assignments = response.data.assignments;
                        if (assignments.length === 0) {
                            html += '<tr><td colspan="3"><?php echo esc_js(__('Noch keine Zuweisungen vorhanden.', 'abschussplan-hgmh')); ?></td></tr>';
                        } else {
                            assignments.forEach(function(assignment) {
                                html += '<tr>';
                                html += '<td>' + assignment.meldegruppe + '</td>';
                                html += '<td>' + assignment.user_name + '</td>';
                                html += '<td><button class="button button-small remove-obmann-assignment" data-wildart="' + wildart + '" data-meldegruppe="' + assignment.meldegruppe + '">';
                                html += '<span class="dashicons dashicons-trash"></span> <?php echo esc_js(__('Entfernen', 'abschussplan-hgmh')); ?></button></td>';
                                html += '</tr>';
                            });
                        }

                        html += '</tbody></table>';
                        $('#obmann-assignments-list').html(html);
                    }
                }
            });
        }

        // Remove obmann assignment
        $(document).on('click', '.remove-obmann-assignment', function() {
            if (!confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie diese Zuweisung entfernen möchten?', 'abschussplan-hgmh')); ?>')) {
                return;
            }

            var wildart = $(this).data('wildart');
            var meldegruppe = $(this).data('meldegruppe');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ahgmh_remove_obmann',
                    nonce: ahgmh_admin.nonce,
                    wildart: wildart,
                    meldegruppe: meldegruppe
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('<?php echo esc_js(__('Zuweisung erfolgreich entfernt!', 'abschussplan-hgmh')); ?>', 'success');
                        loadObmannAssignments(wildart);
                    } else {
                        alert(response.data || '<?php echo esc_js(__('Fehler beim Entfernen.', 'abschussplan-hgmh')); ?>');
                    }
                }
            });
        });

        // Helper functions
        function showNotice(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            $('.ahgmh-page-title').after(notice);

            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }

        function showModalNotice(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            $('.ahgmh-modal-notice').removeClass('notice-success notice-error')
                .addClass('notice ' + noticeClass)
                .html('<p>' + message + '</p>')
                .show();
        }
    });
    </script>
    <?php
}

// Call the render function
ahgmh_render_wildarten_management_page();
