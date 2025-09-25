<?php
// Debug für abschuss_summary_table mit mehreren Shortcodes

echo "<h1>Debug: abschuss_summary_table Species Filtering</h1>\n";

// Simuliere zwei Shortcodes mit verschiedenen Species
$shortcode1_atts = array('species' => 'Rotwild', 'limit' => 10);
$shortcode2_atts = array('species' => 'Damwild', 'limit' => 15);

function debug_render_summary_table($atts, $shortcode_name) {
    echo "<h2>$shortcode_name</h2>\n";
    
    // Parse shortcode attributes (wie in der echten Methode)
    $atts = shortcode_atts(
        array(
            'limit' => 10,
            'page' => 1,
            'species' => '',
            'meldegruppe' => ''
        ),
        $atts,
        'abschuss_summary_table'
    );
    
    echo "<strong>Parsed attributes:</strong><br>\n";
    foreach ($atts as $key => $value) {
        echo "$key: '" . htmlspecialchars($value) . "'<br>\n";
    }
    
    // URL-Parameter Simulation (Problem!)
    $_GET['abschuss_page'] = 1;
    $_GET['abschuss_limit'] = 20; // Global für beide Shortcodes!
    
    $page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : intval($atts['page']);
    $limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : intval($atts['limit']);
    
    echo "<br><strong>Final values:</strong><br>\n";
    echo "page (from URL or attr): $page<br>\n";
    echo "limit (from URL or attr): $limit<br>\n";
    
    $species = sanitize_text_field($atts['species']);
    $meldegruppe = sanitize_text_field($atts['meldegruppe']);
    
    echo "species: '$species'<br>\n";
    echo "meldegruppe: '$meldegruppe'<br>\n";
    
    // Zeige welcher Code-Pfad gewählt wird
    if (!empty($species) && !empty($meldegruppe)) {
        echo "<br><span style='color: green;'>✓ PFAD: Both species and meldegruppe specified</span><br>\n";
    } else if (!empty($species)) {
        echo "<br><span style='color: blue;'>✓ PFAD: Only species specified</span><br>\n";
        echo "Database call: get_submissions_by_species($limit, " . (($page - 1) * $limit) . ", '$species')<br>\n";
    } else {
        echo "<br><span style='color: red;'>✗ PFAD: No filters - all submissions</span><br>\n";
    }
    
    echo "<hr>\n";
}

function sanitize_text_field($value) {
    return trim(strip_tags($value));
}

debug_render_summary_table($shortcode1_atts, "Shortcode 1: [abschuss_summary_table species='Rotwild']");
debug_render_summary_table($shortcode2_atts, "Shortcode 2: [abschuss_summary_table species='Damwild']");

echo "<h2>Problem-Analyse</h2>\n";
echo "<strong style='color: red;'>PROBLEM 1:</strong> URL-Parameter werden von beiden Shortcodes geteilt!<br>\n";
echo "Beide Tabellen verwenden dieselben \$_GET['abschuss_page'] und \$_GET['abschuss_limit'] Parameter.<br><br>\n";

echo "<strong style='color: red;'>PROBLEM 2:</strong> Mögliche Paginierung-Konflikte<br>\n";
echo "Wenn eine Tabelle paginiert wird, betrifft das beide Tabellen auf der Seite.<br><br>\n";

?>
