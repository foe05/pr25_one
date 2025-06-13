<?php
/**
 * Admin panel template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="abschuss-admin-container">
    <h2><?php echo esc_html__('Abschussmeldungen - Admin Panel', 'abschussplan-hgmh'); ?></h2>
    <p class="lead"><?php echo esc_html__('Konfiguration und Verwaltung', 'abschussplan-hgmh'); ?></p>
    
    <div class="nav-wrapper">
        <ul class="nav nav-tabs" id="abschussAdminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="db-tab" data-bs-toggle="tab" data-bs-target="#db-content" type="button" role="tab" aria-controls="db-content" aria-selected="true">
                    <?php echo esc_html__('Datenbank Konfiguration', 'abschussplan-hgmh'); ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="limits-tab" data-bs-toggle="tab" data-bs-target="#limits-content" type="button" role="tab" aria-controls="limits-content" aria-selected="false">
                    <?php echo esc_html__('Höchstgrenzen', 'abschussplan-hgmh'); ?>
                </button>
            </li>
        </ul>
    </div>
    
    <div class="tab-content" id="abschussAdminTabsContent">
        <!-- Database Configuration Tab -->
        <div class="tab-pane fade show active" id="db-content" role="tabpanel" aria-labelledby="db-tab">
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="mb-0"><?php echo esc_html__('Datenbank Konfiguration', 'abschussplan-hgmh'); ?></h3>
                </div>
                <div class="card-body">
                    <div id="db-response" class="alert" role="alert" style="display: none;"></div>
                    
                    <form id="db-config-form">
                        <?php wp_nonce_field('db_config_nonce', 'db_nonce'); ?>
                        
                        <div class="mb-3">
                            <label for="db_type" class="form-label"><?php echo esc_html__('Datenbanktyp', 'abschussplan-hgmh'); ?></label>
                            <select class="form-select" id="db_type" name="db_type">
                                <option value="sqlite" <?php selected($db_config['type'], 'sqlite'); ?>>
                                    <?php echo esc_html__('SQLite (Standard)', 'abschussplan-hgmh'); ?>
                                </option>
                                <option value="postgresql" <?php selected($db_config['type'], 'postgresql'); ?>>
                                    <?php echo esc_html__('PostgreSQL', 'abschussplan-hgmh'); ?>
                                </option>
                                <option value="mysql" <?php selected($db_config['type'], 'mysql'); ?>>
                                    <?php echo esc_html__('MySQL', 'abschussplan-hgmh'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="sqlite-settings" style="<?php echo $db_config['type'] !== 'sqlite' ? 'display: none;' : ''; ?>">
                            <div class="mb-3">
                                <label for="sqlite_file" class="form-label">
                                    <?php echo esc_html__('SQLite Datei-Pfad', 'abschussplan-hgmh'); ?>
                                </label>
                                <input type="text" class="form-control" id="sqlite_file" name="sqlite_file" 
                                       value="<?php echo esc_attr($db_config['type'] === 'sqlite' && isset($db_config['sqlite_file']) ? $db_config['sqlite_file'] : 'abschuss_db.sqlite'); ?>">
                                <small class="text-muted">
                                    <?php echo esc_html__('Relativer oder absoluter Pfad zur Datenbank-Datei', 'abschussplan-hgmh'); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="postgresql-settings" style="<?php echo $db_config['type'] !== 'postgresql' ? 'display: none;' : ''; ?>">
                            <div class="mb-3">
                                <label for="pg_host" class="form-label"><?php echo esc_html__('Host', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="pg_host" name="pg_host" 
                                       value="<?php echo esc_attr($db_config['type'] === 'postgresql' && isset($db_config['host']) ? $db_config['host'] : 'localhost'); ?>" 
                                       placeholder="localhost">
                            </div>
                            <div class="mb-3">
                                <label for="pg_port" class="form-label"><?php echo esc_html__('Port', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="pg_port" name="pg_port" 
                                       value="<?php echo esc_attr($db_config['type'] === 'postgresql' && isset($db_config['port']) ? $db_config['port'] : '5432'); ?>" 
                                       placeholder="5432">
                            </div>
                            <div class="mb-3">
                                <label for="pg_dbname" class="form-label"><?php echo esc_html__('Datenbankname', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="pg_dbname" name="pg_dbname" 
                                       value="<?php echo esc_attr($db_config['type'] === 'postgresql' && isset($db_config['dbname']) ? $db_config['dbname'] : ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="pg_user" class="form-label"><?php echo esc_html__('Benutzername', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="pg_user" name="pg_user" 
                                       value="<?php echo esc_attr($db_config['type'] === 'postgresql' && isset($db_config['user']) ? $db_config['user'] : ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="pg_password" class="form-label"><?php echo esc_html__('Passwort', 'abschussplan-hgmh'); ?></label>
                                <input type="password" class="form-control" id="pg_password" name="pg_password" 
                                       value="<?php echo esc_attr($db_config['type'] === 'postgresql' && isset($db_config['password']) ? $db_config['password'] : ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mysql-settings" style="<?php echo $db_config['type'] !== 'mysql' ? 'display: none;' : ''; ?>">
                            <div class="mb-3">
                                <label for="mysql_host" class="form-label"><?php echo esc_html__('Host', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="mysql_host" name="mysql_host" 
                                       value="<?php echo esc_attr($db_config['type'] === 'mysql' && isset($db_config['host']) ? $db_config['host'] : 'localhost'); ?>" 
                                       placeholder="localhost">
                            </div>
                            <div class="mb-3">
                                <label for="mysql_port" class="form-label"><?php echo esc_html__('Port', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="mysql_port" name="mysql_port" 
                                       value="<?php echo esc_attr($db_config['type'] === 'mysql' && isset($db_config['port']) ? $db_config['port'] : '3306'); ?>" 
                                       placeholder="3306">
                            </div>
                            <div class="mb-3">
                                <label for="mysql_dbname" class="form-label"><?php echo esc_html__('Datenbankname', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="mysql_dbname" name="mysql_dbname" 
                                       value="<?php echo esc_attr($db_config['type'] === 'mysql' && isset($db_config['dbname']) ? $db_config['dbname'] : ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="mysql_user" class="form-label"><?php echo esc_html__('Benutzername', 'abschussplan-hgmh'); ?></label>
                                <input type="text" class="form-control" id="mysql_user" name="mysql_user" 
                                       value="<?php echo esc_attr($db_config['type'] === 'mysql' && isset($db_config['user']) ? $db_config['user'] : ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="mysql_password" class="form-label"><?php echo esc_html__('Passwort', 'abschussplan-hgmh'); ?></label>
                                <input type="password" class="form-control" id="mysql_password" name="mysql_password" 
                                       value="<?php echo esc_attr($db_config['type'] === 'mysql' && isset($db_config['password']) ? $db_config['password'] : ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?php echo esc_html__('Konfiguration speichern', 'abschussplan-hgmh'); ?>
                        </button>
                        <button type="button" id="test-connection" class="btn btn-secondary">
                            <?php echo esc_html__('Verbindung testen', 'abschussplan-hgmh'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Limits Configuration Tab -->
        <div class="tab-pane fade" id="limits-content" role="tabpanel" aria-labelledby="limits-tab">
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="mb-0"><?php echo esc_html__('Höchstgrenzen für Abschüsse', 'abschussplan-hgmh'); ?></h3>
                </div>
                <div class="card-body">
                    <div id="limits-response" class="alert" role="alert" style="display: none;"></div>
                    
                    <form id="limits-form">
                        <?php wp_nonce_field('limits_nonce', 'limits_nonce'); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="limit-wildkalb-ak-0" class="form-label">Wildkalb (AK 0)</label>
                                    <input type="number" class="form-control" id="limit-wildkalb-ak-0" 
                                           name="limit-wildkalb-ak-0" min="0" 
                                           value="<?php echo isset($limits['Wildkalb (AK 0)']) ? esc_attr($limits['Wildkalb (AK 0)']) : '0'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="limit-schmaltier-ak-1" class="form-label">Schmaltier (AK 1)</label>
                                    <input type="number" class="form-control" id="limit-schmaltier-ak-1" 
                                           name="limit-schmaltier-ak-1" min="0" 
                                           value="<?php echo isset($limits['Schmaltier (AK 1)']) ? esc_attr($limits['Schmaltier (AK 1)']) : '0'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="limit-alttier-ak-2" class="form-label">Alttier (AK 2)</label>
                                    <input type="number" class="form-control" id="limit-alttier-ak-2" 
                                           name="limit-alttier-ak-2" min="0" 
                                           value="<?php echo isset($limits['Alttier (AK 2)']) ? esc_attr($limits['Alttier (AK 2)']) : '0'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="limit-hirschkalb-ak-0" class="form-label">Hirschkalb (AK 0)</label>
                                    <input type="number" class="form-control" id="limit-hirschkalb-ak-0" 
                                           name="limit-hirschkalb-ak-0" min="0" 
                                           value="<?php echo isset($limits['Hirschkalb (AK 0)']) ? esc_attr($limits['Hirschkalb (AK 0)']) : '0'; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="limit-schmalspiesser-ak1" class="form-label">Schmalspießer (AK1)</label>
                                    <input type="number" class="form-control" id="limit-schmalspiesser-ak1" 
                                           name="limit-schmalspiesser-ak1" min="0" 
                                           value="<?php echo isset($limits['Schmalspießer (AK1)']) ? esc_attr($limits['Schmalspießer (AK1)']) : '0'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="limit-junger-hirsch-ak-2" class="form-label">Junger Hirsch (AK 2)</label>
                                    <input type="number" class="form-control" id="limit-junger-hirsch-ak-2" 
                                           name="limit-junger-hirsch-ak-2" min="0" 
                                           value="<?php echo isset($limits['Junger Hirsch (AK 2)']) ? esc_attr($limits['Junger Hirsch (AK 2)']) : '0'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="limit-mittelalter-hirsch-ak-3" class="form-label">Mittelalter Hirsch (AK 3)</label>
                                    <input type="number" class="form-control" id="limit-mittelalter-hirsch-ak-3" 
                                           name="limit-mittelalter-hirsch-ak-3" min="0" 
                                           value="<?php echo isset($limits['Mittelalter Hirsch (AK 3)']) ? esc_attr($limits['Mittelalter Hirsch (AK 3)']) : '0'; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="limit-alte-hirsch-ak-4" class="form-label">Alter Hirsch (AK 4)</label>
                                    <input type="number" class="form-control" id="limit-alte-hirsch-ak-4" 
                                           name="limit-alte-hirsch-ak-4" min="0" 
                                           value="<?php echo isset($limits['Alter Hirsch (AK 4)']) ? esc_attr($limits['Alter Hirsch (AK 4)']) : '0'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?php echo esc_html__('Höchstgrenzen speichern', 'abschussplan-hgmh'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Summary Table within Admin Panel -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="mb-0"><?php echo esc_html__('Aktuelle Abschusszahlen', 'abschussplan-hgmh'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Kategorie', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Abschüsse', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Höchstgrenze', 'abschussplan-hgmh'); ?></th>
                                    <th><?php echo esc_html__('Verbleibend', 'abschussplan-hgmh'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category) : ?>
                                    <tr>
                                        <td><?php echo esc_html($category); ?></td>
                                        <td><?php echo isset($counts[$category]) ? esc_html($counts[$category]) : '0'; ?></td>
                                        <td><?php echo isset($limits[$category]) ? esc_html($limits[$category]) : '0'; ?></td>
                                        <td>
                                            <?php if (isset($limits[$category]) && $limits[$category] > 0) : ?>
                                                <?php 
                                                $remaining = $limits[$category] - (isset($counts[$category]) ? $counts[$category] : 0);
                                                echo esc_html($remaining);
                                                ?>
                                            <?php else : ?>
                                                <span class="text-muted"><?php echo esc_html__('Keine Grenze', 'abschussplan-hgmh'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle database connection fields based on selected type
    $('#db_type').on('change', function() {
        var selectedType = $(this).val();
        $('.sqlite-settings, .postgresql-settings, .mysql-settings').hide();
        
        if (selectedType === 'sqlite') {
            $('.sqlite-settings').show();
        } else if (selectedType === 'postgresql') {
            $('.postgresql-settings').show();
        } else if (selectedType === 'mysql') {
            $('.mysql-settings').show();
        }
    });
    
    // Database configuration form submission
    $('#db-config-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $responseContainer = $('#db-response');
        
        // Disable the submit button during submission
        $submitBtn.prop('disabled', true);
        
        // Get form data
        var formData = new FormData(this);
        formData.append('action', 'save_db_config');
        formData.append('nonce', $('#db_nonce').val());
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $responseContainer.removeClass('alert-danger').addClass('alert-success')
                                      .text(response.data.message).show();
                } else {
                    $responseContainer.removeClass('alert-success').addClass('alert-danger')
                                      .text(response.data.message).show();
                }
            },
            error: function() {
                $responseContainer.removeClass('alert-success').addClass('alert-danger')
                                  .text('<?php echo esc_js(__('Es gab einen Fehler beim Speichern der Konfiguration.', 'abschussplan-hgmh')); ?>')
                                  .show();
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Test database connection
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const $form = $('#db-config-form');
        const $responseContainer = $('#db-response');
        
        // Disable the button during testing
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Wird getestet...', 'abschussplan-hgmh')); ?>');
        
        // Get form data
        var formData = new FormData($form[0]);
        formData.append('action', 'test_db_connection');
        formData.append('nonce', $('#db_nonce').val());
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $responseContainer.removeClass('alert-danger').addClass('alert-success')
                                      .text(response.data.message).show();
                } else {
                    $responseContainer.removeClass('alert-success').addClass('alert-danger')
                                      .text(response.data.message).show();
                }
            },
            error: function() {
                $responseContainer.removeClass('alert-success').addClass('alert-danger')
                                  .text('<?php echo esc_js(__('Es gab einen Fehler beim Testen der Verbindung.', 'abschussplan-hgmh')); ?>')
                                  .show();
            },
            complete: function() {
                $btn.prop('disabled', false)
                    .text('<?php echo esc_js(__('Verbindung testen', 'abschussplan-hgmh')); ?>');
            }
        });
    });
    
    // Limits form submission
    $('#limits-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $responseContainer = $('#limits-response');
        
        // Disable the submit button during submission
        $submitBtn.prop('disabled', true);
        
        // Get form data
        var formData = new FormData(this);
        formData.append('action', 'save_limits');
        formData.append('nonce', $('#limits_nonce').val());
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $responseContainer.removeClass('alert-danger').addClass('alert-success')
                                      .text(response.data.message).show();
                    
                    // Reload the page after a short delay to show the updated values
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    $responseContainer.removeClass('alert-success').addClass('alert-danger')
                                      .text(response.data.message).show();
                }
            },
            error: function() {
                $responseContainer.removeClass('alert-success').addClass('alert-danger')
                                  .text('<?php echo esc_js(__('Es gab einen Fehler beim Speichern der Höchstgrenzen.', 'abschussplan-hgmh')); ?>')
                                  .show();
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
            }
        });
    });
});
</script>
