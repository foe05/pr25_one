<?php
/**
 * Debug file to check plugin functionality
 * Add [abschuss_debug] shortcode to a page to test
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function abschuss_debug_shortcode() {
    ob_start();
    ?>
    <div class="abschuss-debug">
        <h3>Plugin Debug Information</h3>
        
        <?php if (class_exists('Abschussplan_HGMH')): ?>
            <p style="color: green;">✓ Main plugin class loaded</p>
            
            <?php 
            $plugin = abschussplan_hgmh();
            if ($plugin && $plugin->database): ?>
                <p style="color: green;">✓ Database handler initialized</p>
                
                <?php 
                // Test database connection
                global $wpdb;
                $table_name = $wpdb->prefix . 'ahgmh_submissions';
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                ?>
                
                <?php if ($table_exists): ?>
                    <p style="color: green;">✓ Database table exists</p>
                    
                    <?php 
                    $count = $plugin->database->count_submissions();
                    ?>
                    <p>Total submissions: <?php echo $count; ?></p>
                    
                <?php else: ?>
                    <p style="color: red;">✗ Database table missing</p>
                    <p>Expected table: <?php echo $table_name; ?></p>
                <?php endif; ?>
                
            <?php else: ?>
                <p style="color: red;">✗ Database handler not initialized</p>
            <?php endif; ?>
            
        <?php else: ?>
            <p style="color: red;">✗ Main plugin class not loaded</p>
        <?php endif; ?>
        
        <h4>Available Shortcodes:</h4>
        <ul>
            <li><code>[abschuss_form]</code></li>
            <li><code>[abschuss_table]</code></li>
            <li><code>[abschuss_summary]</code></li>
            <li><code>[abschuss_admin]</code></li>
            <li><code>[ahgmh_submissions]</code></li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

// Register debug shortcode
add_shortcode('abschuss_debug', 'abschuss_debug_shortcode');