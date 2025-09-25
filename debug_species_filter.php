<?php
/**
 * Debug script for Species filtering in abschuss_summary_table
 */

// Test der render_summary_table Methode
echo "<h1>DEBUG: Species Filtering Test</h1>\n";

// Test 1: Simuliere die render_summary_table Logik
echo "<h2>Test 1: Parameter-Übergabe</h2>\n";

$species = 'Rotwild';  // Beispiel parameter
$meldegruppe = '';     // Leer
$limit = 10;
$page = 1;

// Zeige die Bedingungen
echo "<strong>Parameter:</strong><br>\n";
echo "species: '$species'<br>\n";
echo "meldegruppe: '$meldegruppe'<br>\n";
echo "limit: $limit<br>\n";
echo "page: $page<br>\n";

echo "<br><strong>Logik-Test:</strong><br>\n";
echo "!empty(\$species): " . (!empty($species) ? 'TRUE' : 'FALSE') . "<br>\n";
echo "!empty(\$meldegruppe): " . (!empty($meldegruppe) ? 'TRUE' : 'FALSE') . "<br>\n";

// Zeige welcher Code-Pfad gewählt würde
if (!empty($species) && !empty($meldegruppe)) {
    echo "<br><span style='color: green;'>PFAD: Both species and meldegruppe specified</span><br>\n";
    echo "Methode: get_submissions_by_species_and_meldegruppe(\$species, \$meldegruppe, \$limit, \$offset)<br>\n";
} else if (!empty($species)) {
    echo "<br><span style='color: blue;'>PFAD: Only species specified</span><br>\n";
    echo "Methode: get_submissions_by_species(\$limit, \$offset, \$species)<br>\n";
    echo "Aufruf: get_submissions_by_species($limit, " . (($page - 1) * $limit) . ", '$species')<br>\n";
} else {
    echo "<br><span style='color: orange;'>PFAD: No filters - all submissions</span><br>\n";
    echo "Methode: get_submissions(\$limit, \$offset)<br>\n";
}

// Test 2: Überprüfe die tatsächliche Database-Methode
echo "<h2>Test 2: Database Methode Simulation</h2>\n";

// Simuliere die get_submissions_by_species Methode
function simulate_get_submissions_by_species($limit = 10, $offset = 0, $species = '') {
    echo "<strong>Parameter erhalten:</strong><br>\n";
    echo "limit: $limit<br>\n";
    echo "offset: $offset<br>\n";
    echo "species: '$species'<br>\n";
    
    echo "<br><strong>WHERE-Klausel wird hinzugefügt:</strong><br>\n";
    if (!empty($species)) {
        echo "JA - WHERE s.game_species = '$species'<br>\n";
        echo "<span style='color: green;'>Species-Filterung AKTIV</span><br>\n";
    } else {
        echo "NEIN - Keine WHERE-Klausel<br>\n";
        echo "<span style='color: red;'>Species-Filterung INAKTIV</span><br>\n";
    }
    
    return array(); // Dummy return
}

echo "<br><strong>Simulation der render_summary_table Aufruf:</strong><br>\n";
simulate_get_submissions_by_species($limit, ($page - 1) * $limit, $species);

?>
