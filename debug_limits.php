<?php
/**
 * Debug script to check current limit structure
 */

// Mock WordPress functions for debugging
function get_option($name, $default = null) {
    echo "Checking option: $name\n";
    
    // Simulate current limit structure
    if ($name === 'ahgmh_wildart_limits') {
        return [
            'Rotwild' => [
                'Hirsch (AK1)' => 10,      // Scalar - hegegemeinschaft_total
                'Hirschkalb (AK0)' => 5
            ],
            'Damwild' => [
                'Hirsch' => [               // Array - meldegruppen_specific
                    'Gruppe_A' => 3,
                    'Gruppe_B' => 2
                ]
            ]
        ];
    }
    
    if ($name === 'ahgmh_limit_modes') {
        return [
            'Rotwild' => 'hegegemeinschaft_total',
            'Damwild' => 'meldegruppen_specific'
        ];
    }
    
    return $default;
}

function sanitize_key($key) {
    return $key;
}

function is_assoc_array($value) {
    return is_array($value) && array_keys($value) !== range(0, count($value) - 1);
}

// Test the limit mode detection
function get_limit_mode($species) {
    $limit_modes = get_option('ahgmh_limit_modes', array());
    $original_mode = isset($limit_modes[$species]) ? $limit_modes[$species] : null;
    
    if ($original_mode === null) {
        // Intelligent detection based on data structure
        $all_limits = get_option('ahgmh_wildart_limits', array());
        $species_data = isset($all_limits[$species]) ? $all_limits[$species] : array();
        
        // Check if any category value is an associative array
        $has_arrays = false;
        foreach ($species_data as $category_value) {
            if (is_assoc_array($category_value)) {
                $has_arrays = true;
                break;
            }
        }
        
        return $has_arrays ? 'meldegruppen_specific' : 'hegegemeinschaft_total';
    }
    
    return $original_mode;
}

echo "=== DEBUGGING LIMIT STRUCTURE ===\n\n";

echo "Current limit data:\n";
$limits = get_option('ahgmh_wildart_limits');
var_dump($limits);

echo "\nLimit modes:\n";
$modes = get_option('ahgmh_limit_modes');
var_dump($modes);

echo "\n=== TESTING DETECTION ===\n";

foreach (['Rotwild', 'Damwild'] as $species) {
    echo "\nSpecies: $species\n";
    $mode = get_limit_mode($species);
    echo "Detected mode: $mode\n";
    
    $species_data = $limits[$species] ?? [];
    echo "Species data structure:\n";
    foreach ($species_data as $category => $value) {
        echo "  $category: " . (is_array($value) ? 'Array(' . count($value) . ' items)' : "Scalar($value)") . "\n";
    }
}

echo "\n=== TESTING LIMIT RETRIEVAL ===\n";

// Test hegegemeinschaft_total (Rotwild)
echo "\nRotwild (hegegemeinschaft_total):\n";
$rotwild_data = $limits['Rotwild'] ?? [];
foreach ($rotwild_data as $category => $value) {
    if (get_limit_mode('Rotwild') === 'hegegemeinschaft_total') {
        $limit = is_array($value) ? 0 : (int) $value; // Should be scalar
        echo "  $category: $limit (direct scalar)\n";
    }
}

// Test meldegruppen_specific (Damwild)
echo "\nDamwild (meldegruppen_specific):\n";
$damwild_data = $limits['Damwild'] ?? [];
foreach ($damwild_data as $category => $value) {
    if (get_limit_mode('Damwild') === 'meldegruppen_specific') {
        $limit = 0;
        if (is_array($value)) {
            foreach ($value as $meldegruppe_limit) {
                $limit += (int) $meldegruppe_limit;
            }
        }
        echo "  $category: $limit (sum of meldegruppen)\n";
    }
}
