<?php
/**
 * Migration script to initialize default meldegruppen for existing wildarten
 * 
 * Run this script once after the update to ensure all existing wildarten
 * have default meldegruppen (Gruppe_A, Gruppe_B)
 */

// WordPress environment laden
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-content/plugins/abschussplan-hgmh/abschussplan-hgmh.php';

echo "=== Meldegruppen Migration Script ===\n\n";

// Check if plugin is active
if (!function_exists('abschussplan_hgmh')) {
    die("Error: Plugin 'Abschussplan HGMH' is not active.\n");
}

// Get the wildart service
$plugin = abschussplan_hgmh();
$wildart_service = $plugin->wildart_service;

if (!$wildart_service) {
    die("Error: Wildart service not available.\n");
}

echo "Initializing default meldegruppen for all existing wildarten...\n\n";

try {
    // Get all existing wildarten
    $wildarten = $wildart_service->get_all_wildarten();
    echo "Found " . count($wildarten) . " wildarten:\n";
    
    foreach ($wildarten as $wildart) {
        echo "- $wildart\n";
    }
    echo "\n";
    
    // Initialize default meldegruppen for all
    $wildart_service->initialize_all_wildarten_meldegruppen();
    
    echo "✅ Successfully initialized default meldegruppen for all wildarten!\n\n";
    
    // Verify the result
    echo "=== Verification ===\n";
    $meldegruppen_config = get_option('ahgmh_wildart_meldegruppen', []);
    
    foreach ($wildarten as $wildart) {
        if (isset($meldegruppen_config[$wildart])) {
            $groups = implode(', ', $meldegruppen_config[$wildart]);
            echo "$wildart: $groups\n";
        } else {
            echo "$wildart: No meldegruppen found!\n";
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
