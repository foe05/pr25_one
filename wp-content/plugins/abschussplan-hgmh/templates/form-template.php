<?php
/**
 * Form template for Abschussmeldung
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="abschussplan-hgmh-form-container">
    <h2 class="mb-4"><?php echo esc_html__('Abschussmeldung', 'abschussplan-hgmh'); ?></h2>
    
    <div id="abschuss-form-response" class="alert" role="alert" style="display: none;"></div>
    
    <?php
    // Get current user info
    $current_user = wp_get_current_user();
    $first_name = get_user_meta($current_user->ID, 'first_name', true);
    $last_name = get_user_meta($current_user->ID, 'last_name', true);
    $display_name = !empty($first_name) || !empty($last_name) 
                   ? trim($first_name . ' ' . $last_name) 
                   : $current_user->display_name;
    ?>
    
    <div class="alert alert-info mb-4" role="alert">
        <strong><?php echo esc_html__('Meldung wird erstellt für:', 'abschussplan-hgmh'); ?></strong> 
        <?php echo esc_html($display_name); ?>
        <small class="d-block mt-1 text-muted">
            <?php echo esc_html__('Sie sind angemeldet als', 'abschussplan-hgmh'); ?> <?php echo esc_html($current_user->user_login); ?>
        </small>
    </div>

    <form class="abschussplan-hgmh-form" id="abschuss-form" method="post">
        <?php wp_nonce_field('ahgmh_form_nonce', 'ahgmh_nonce'); ?>
        
        <!-- Hidden field for game species (set via shortcode) -->
        <input type="hidden" id="game_species" name="game_species" value="<?php echo esc_attr($selected_species); ?>" />
        
        <div class="mb-3">
            <label for="field1" class="form-label"><?php echo esc_html__('Abschussdatum', 'abschussplan-hgmh'); ?></label>
            <input type="date" class="form-control" id="field1" name="field1" required value="<?php echo esc_attr($yesterday); ?>">
            <div class="form-error"></div>
        </div>
        
        <div class="mb-3">
            <label for="field2" class="form-label"><?php echo esc_html__('Abschuss', 'abschussplan-hgmh'); ?></label>
            <select class="form-select" id="field2" name="field2" required>
                <option value="" selected disabled><?php echo esc_html__('Bitte wählen...', 'abschussplan-hgmh'); ?></option>
                
                <?php 
                // Get allow exceeding settings for the selected species
                $allow_exceeding = array();
                $categories_obj = get_option('ahgmh_categories', array('Rotwild', 'Damwild'));
                $default_exceeding = array();
                foreach ($categories_obj as $cat) {
                    $default_exceeding[$cat] = false;
                }
                $exceeding_option_key = 'abschuss_category_allow_exceeding_' . sanitize_key($selected_species);
                $allow_exceeding = get_option($exceeding_option_key, $default_exceeding);
                
                foreach ($categories as $category) : 
                    // Check if category has reached its limit
                    $current_count = isset($counts[$category]) ? $counts[$category] : 0;
                    $max_count = isset($limits[$category]) ? $limits[$category] : 0;
                    $exceeding_allowed = isset($allow_exceeding[$category]) ? $allow_exceeding[$category] : false;
                    
                    // Categories are always enabled regardless of limits
                    $disabled = '';
                    $limit_text = '';
                    
                    if ($max_count > 0 && $current_count >= $max_count) {
                        if ($exceeding_allowed) {
                            $limit_text = ' (' . esc_html__('Limit erreicht - Überschreitung erlaubt', 'abschussplan-hgmh') . ')';
                        } else {
                            $limit_text = ' (' . esc_html__('Limit erreicht', 'abschussplan-hgmh') . ')';
                        }
                    }
                ?>
                    <option value="<?php echo esc_attr($category); ?>" <?php echo $disabled; ?>>
                        <?php echo esc_html($category . $limit_text); ?>
                    </option>
                <?php endforeach; ?>
                
            </select>
            <div class="form-error"></div>
        </div>
        
        <div class="mb-3">
            <label for="field3" class="form-label"><?php echo esc_html__('WUS', 'abschussplan-hgmh'); ?></label>
            <input type="number" class="form-control" id="field3" name="field3" min="1000000" maxlength="7" max="9999999">
            <div class="form-error"></div>
        </div>
        
        <div class="mb-3">
            <label for="field5" class="form-label"><?php echo esc_html__('Jagdbezirk', 'abschussplan-hgmh'); ?></label>
            <select class="form-select" id="field5" name="field5" required>
                <option value="" selected disabled><?php echo esc_html__('Bitte wählen...', 'abschussplan-hgmh'); ?></option>
                
                <?php 
                // Get active Jagdbezirke from database (ungueltig = 0)
                $database = abschussplan_hgmh()->database;
                $jagdbezirke = $database->get_active_jagdbezirke();
                

                
                foreach ($jagdbezirke as $jagdbezirk) : ?>
                    <option value="<?php echo esc_attr($jagdbezirk['jagdbezirk']); ?>">
                        <?php echo esc_html($jagdbezirk['jagdbezirk']); ?>
                        <?php if (!empty($jagdbezirk['meldegruppe'])) : ?>
                            (<?php echo esc_html($jagdbezirk['meldegruppe']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
                
            </select>
            <div class="form-error"></div>
        </div>
        
        <div class="mb-3">
            <label for="field4" class="form-label"><?php echo esc_html__('Bemerkung', 'abschussplan-hgmh'); ?></label>
            <textarea class="form-control" id="field4" name="field4" rows="4"></textarea>
            <div class="form-error"></div>
        </div>
        
        <button type="submit" class="btn btn-primary submit-btn"><?php echo esc_html__('Speichern', 'abschussplan-hgmh'); ?></button>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Set up datepicker for the date field
    var dateField = document.getElementById('field1');
    
    // Set max date to today (prevent future dates)
    var today = new Date();
    var maxDate = today.toISOString().split('T')[0];
    dateField.setAttribute('max', maxDate);
    
    // Form validation and submission
    $('#abschuss-form').on('submit', function(e) {
        e.preventDefault();
        
        // Reset previous error messages
        $('.form-error').text('').hide();
        $('.is-invalid').removeClass('is-invalid');
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $responseContainer = $('#abschuss-form-response');
        
        // Additional date validation
        const dateValue = new Date($('#field1').val());
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time portion for proper comparison
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1); // Get tomorrow's date
        
        if (dateValue >= tomorrow) {
            $('#field1').addClass('is-invalid');
            $('#field1').siblings('.form-error').text('<?php echo esc_js(__('Das Datum darf nicht in der Zukunft liegen.', 'abschussplan-hgmh')); ?>').show();
            return;
        }
        
        // Validate Jagdbezirk selection
        if (!$('#field5').val()) {
            $('#field5').addClass('is-invalid');
            $('#field5').siblings('.form-error').text('<?php echo esc_js(__('Bitte wählen Sie einen Jagdbezirk aus.', 'abschussplan-hgmh')); ?>').show();
            return;
        }
        
        // Disable the submit button to prevent multiple submissions
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo esc_js(__('Wird gespeichert...', 'abschussplan-hgmh')); ?>');
        
        // Get form data
        const formData = new FormData();
        formData.append('action', 'submit_abschuss_form');
        formData.append('ahgmh_nonce', $('#ahgmh_nonce').val());
        formData.append('game_species', $('#game_species').val());
        formData.append('field1', $('#field1').val());
        formData.append('field2', $('#field2').val());
        formData.append('field3', $('#field3').val());
        formData.append('field4', $('#field4').val());
        formData.append('field5', $('#field5').val());
        
        // Send AJAX request
        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $responseContainer.removeClass('alert-danger').addClass('alert-success').text(response.data.message).show();
                    
                    // Reset the form but keep the date
                    const currentDate = $('#field1').val();
                    $form[0].reset();
                    $('#field1').val(currentDate);
                    
                    // Reload the page to show updated table
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500); // Small delay to show success message
                } else {
                    // Show error message
                    $responseContainer.removeClass('alert-success').addClass('alert-danger').text(response.data.message).show();
                    
                    // Display field specific errors
                    if (response.data.errors) {
                        $.each(response.data.errors, function(field, error) {
                            const $field = $form.find(`[name="${field}"]`);
                            $field.addClass('is-invalid');
                            $field.siblings('.form-error').text(error).show();
                        });
                    }
                }
            },
            error: function() {
                // Show general error message
                $responseContainer.removeClass('alert-success').addClass('alert-danger')
                    .text('<?php echo esc_js(__('Es gab einen Fehler beim Speichern. Bitte versuchen Sie es erneut.', 'abschussplan-hgmh')); ?>').show();
            },
            complete: function() {
                // Re-enable the submit button
                $submitBtn.prop('disabled', false).text('<?php echo esc_js(__('Speichern', 'abschussplan-hgmh')); ?>');
                
                // Scroll to the response message
                $('html, body').animate({
                    scrollTop: $responseContainer.offset().top - 100
                }, 500);
            }
        });
    });
    
    // Real-time validation
    $('.abschussplan-hgmh-form input, .abschussplan-hgmh-form select, .abschussplan-hgmh-form textarea').on('blur', function() {
        const $field = $(this);
        const fieldValue = $field.val();
        
        // Validate required fields
        if ($field.prop('required') && !fieldValue) {
            $field.addClass('is-invalid');
            $field.siblings('.form-error').text('<?php echo esc_js(__('Dieses Feld ist erforderlich', 'abschussplan-hgmh')); ?>').show();
        } else {
            $field.removeClass('is-invalid');
            $field.siblings('.form-error').text('').hide();
        }
        
        // Special validation for date field
        if ($field.attr('id') === 'field1' && fieldValue) {
            const dateValue = new Date(fieldValue);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time portion for proper comparison
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1); // Get tomorrow's date
            
            if (dateValue >= tomorrow) {
                $field.addClass('is-invalid');
                $field.siblings('.form-error').text('<?php echo esc_js(__('Das Datum darf nicht in der Zukunft liegen.', 'abschussplan-hgmh')); ?>').show();
            }
        }
        
        // Special validation for WUS field
        if ($field.attr('id') === 'field3' && fieldValue && !$.isNumeric(fieldValue)) {
            $field.addClass('is-invalid');
            $field.siblings('.form-error').text('<?php echo esc_js(__('WUS muss eine ganze Zahl sein.', 'abschussplan-hgmh')); ?>').show();
        }
    });
});
</script>
