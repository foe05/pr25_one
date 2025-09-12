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
                $default_exceeding = array();
                foreach ($categories as $cat) {
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
            <label for="field5" class="form-label"><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></label>
            <select class="form-select" id="field5" name="field5" required>
                <option value="" selected disabled><?php echo esc_html__('Bitte wählen...', 'abschussplan-hgmh'); ?></option>
                
                <?php 
                // Get active Jagdbezirke from database with permission filtering
                $database = abschussplan_hgmh()->database;
                $jagdbezirke = $database->get_active_jagdbezirke();
                
                // Filter Jagdbezirke based on user permissions
                $user_id = get_current_user_id();
                if (!AHGMH_Permissions_Service::is_vorstand($user_id)) {
                    // Obmann: Only show jagdbezirke from their assigned meldegruppe
                    $user_meldegruppe = AHGMH_Permissions_Service::get_user_meldegruppe($user_id, $selected_species);
                    $jagdbezirke = array_filter($jagdbezirke, function($jagdbezirk) use ($user_meldegruppe) {
                        return $jagdbezirk['meldegruppe'] === $user_meldegruppe;
                    });
                }
                
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
    
    // Note: Form submission handling is now in form-validation.js to prevent conflicts
});
</script>
