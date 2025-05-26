<?php
// Debug script to check database content
// Run this from the WordPress root

require_once('wp-config.php');
require_once('wp-load.php');

global $wpdb;
$table_name = $wpdb->prefix . 'ahgmh_submissions';

echo "=== Database Debug ===\n";
echo "Table name: $table_name\n\n";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if (!$table_exists) {
    echo "Table does not exist!\n";
    exit;
}

// Get table structure
echo "=== Table Structure ===\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");
foreach ($columns as $column) {
    echo $column->Field . " - " . $column->Type . " (" . $column->Null . ")\n";
}

echo "\n=== Sample Data ===\n";
$results = $wpdb->get_results("SELECT id, user_id, game_species, field1, field2 FROM $table_name LIMIT 5", ARRAY_A);

if (empty($results)) {
    echo "No data found in table.\n";
} else {
    foreach ($results as $row) {
        echo "ID: " . $row['id'] . ", User ID: " . $row['user_id'] . ", Species: " . $row['game_species'] . ", Date: " . $row['field1'] . ", Category: " . $row['field2'] . "\n";
    }
}

echo "\n=== User Check ===\n";
foreach ($results as $row) {
    if ($row['user_id'] > 0) {
        $user = get_user_by('id', $row['user_id']);
        if ($user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            echo "User ID " . $row['user_id'] . ": " . $first_name . " " . $last_name . " (Display: " . $user->display_name . ")\n";
        } else {
            echo "User ID " . $row['user_id'] . " not found!\n";
        }
    } else {
        echo "Submission ID " . $row['id'] . " has no user_id set\n";
    }
}
?>