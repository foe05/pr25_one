<?php
/**
 * Test script to check limit modes
 * Run this via browser: http://yoursite.com/wp-content/plugins/abschussplan-hgmh/../../test_limit_modes.php
 */

// Simple WordPress simulation
define('ABSPATH', true);

function get_option($option_name, $default = false) {
    $options = [
        'ahgmh_limit_modes' => [
            'Rotwild' => 'hegegemeinschaft_total',  // This should show what's actually stored
            'Damwild' => 'jagdbezirk_specific'
        ],
        'ahgmh_wildart_limits' => [
            'Rotwild' => ['Hirsch' => 10, 'Tier' => 15],
            'Damwild' => []
        ]
    ];
    
    return isset($options[$option_name]) ? $options[$option_name] : $default;
}

function update_option($option_name, $value) {
    echo "WOULD SAVE: $option_name = " . print_r($value, true) . "\n";
}

// Test the logic from admin-page-modern.php
function test_limit_mode_logic($wildart) {
    echo "\n=== Testing: $wildart ===\n";
    
    $limit_modes = get_option('ahgmh_limit_modes', []);
    $original_mode = isset($limit_modes[$wildart]) ? $limit_modes[$wildart] : null;
    
    echo "Original mode from DB: " . ($original_mode ?? 'NULL') . "\n";
    
    if ($original_mode === 'meldegruppen_specific') {
        echo "Migration: meldegruppen_specific -> jagdbezirk_specific\n";
        $current_mode = 'jagdbezirk_specific';
    } else if ($original_mode === null) {
        $all_limits = get_option('ahgmh_wildart_limits', []);
        $has_specific_limits = isset($all_limits[$wildart]) && !empty($all_limits[$wildart]);
        
        echo "Has specific limits: " . ($has_specific_limits ? 'YES' : 'NO') . "\n";
        
        if ($has_specific_limits) {
            $current_mode = 'jagdbezirk_specific';
            echo "Smart default: jagdbezirk_specific\n";
        } else {
            $current_mode = 'hegegemeinschaft_total';
            echo "Smart default: hegegemeinschaft_total\n";
        }
    } else {
        $current_mode = $original_mode;
        echo "User-set mode: $original_mode\n";
    }
    
    echo "Final mode: $current_mode\n";
    echo "Radio button 'jagdbezirk_specific' checked: " . ($current_mode === 'jagdbezirk_specific' ? 'YES' : 'NO') . "\n";
    echo "Radio button 'hegegemeinschaft_total' checked: " . ($current_mode === 'hegegemeinschaft_total' ? 'YES' : 'NO') . "\n";
}

test_limit_mode_logic('Rotwild');  // Has limits but mode = hegegemeinschaft_total
test_limit_mode_logic('Damwild');  // No limits but mode = jagdbezirk_specific
test_limit_mode_logic('Rehwild'); // Not in DB at all

echo "\n=== Test completed ===\n";
?>
