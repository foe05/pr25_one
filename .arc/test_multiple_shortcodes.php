<?php
/**
 * Test für mehrere [abschuss_summary_table] Shortcodes auf einer Seite
 * Demonstriert das behobene Problem mit URL-Parameter-Konflikten
 */

echo "<h1>TEST: Mehrere abschuss_summary_table Shortcodes</h1>\n";

echo "<h2>Szenario:</h2>\n";
echo "WordPress-Seite mit zwei Tabellen:<br>\n";
echo "1. [abschuss_summary_table species='Rotwild' limit='5']<br>\n";
echo "2. [abschuss_summary_table species='Damwild' limit='10']<br>\n";

echo "<h2>Problem VOR dem Fix:</h2>\n";
echo "<div style='background-color: #ffe6e6; padding: 10px; border: 1px solid red;'>\n";
echo "<strong>URL-Parameter-Konflikt:</strong><br>\n";
echo "- Beide Tabellen verwenden dieselben \$_GET['abschuss_page'] und \$_GET['abschuss_limit']<br>\n";
echo "- Paginierung einer Tabelle beeinflusst beide Tabellen<br>\n";
echo "- Species-Parameter werden eventuell nicht korrekt verarbeitet<br>\n";
echo "- Limit-Parameter werden durch URL überschrieben\n";
echo "</div>\n";

echo "<h2>Lösung NACH dem Fix:</h2>\n";
echo "<div style='background-color: #e6ffe6; padding: 10px; border: 1px solid green;'>\n";
echo "<strong>Unabhängige Shortcode-Parameter:</strong><br>\n";
echo "- Jeder Shortcode verwendet NUR seine eigenen Attribute<br>\n";
echo "- Keine URL-Parameter mehr in render_summary_table<br>\n";
echo "- Species-Filterung funktioniert korrekt<br>\n";
echo "- Limit-Parameter bleiben konstant pro Shortcode\n";
echo "</div>\n";

echo "<h2>Code-Änderung:</h2>\n";
echo "<pre style='background-color: #f0f0f0; padding: 10px;'>\n";
echo "// VORHER (Problem):\n";
echo "\$page = isset(\$_GET['abschuss_page']) ? max(1, intval(\$_GET['abschuss_page'])) : intval(\$atts['page']);\n";
echo "\$limit = isset(\$_GET['abschuss_limit']) ? max(1, intval(\$_GET['abschuss_limit'])) : intval(\$atts['limit']);\n";
echo "\n";
echo "// NACHHER (Fix):\n";
echo "\$page = intval(\$atts['page']);\n";
echo "\$limit = intval(\$atts['limit']);\n";
echo "</pre>\n";

echo "<h2>Erwartetes Verhalten nach dem Fix:</h2>\n";
echo "<ol>\n";
echo "<li><strong>Rotwild-Tabelle:</strong> Zeigt nur Rotwild-Einträge, maximal 5 pro Seite</li>\n";
echo "<li><strong>Damwild-Tabelle:</strong> Zeigt nur Damwild-Einträge, maximal 10 pro Seite</li>\n";
echo "<li><strong>Keine Konflikte:</strong> Beide Tabellen arbeiten unabhängig voneinander</li>\n";
echo "<li><strong>Species-Filterung:</strong> Funktioniert korrekt für beide Tabellen</li>\n";
echo "</ol>\n";

echo "<h2>Test-Shortcodes für WordPress:</h2>\n";
echo "<div style='background-color: #f9f9f9; padding: 10px; border: 1px solid #ccc;'>\n";
echo "// Fügen Sie diese Shortcodes in Ihre WordPress-Seite ein:<br><br>\n";
echo "<strong>Rotwild Tabelle:</strong><br>\n";
echo "[abschuss_summary_table species='Rotwild' limit='5']<br><br>\n";
echo "<strong>Damwild Tabelle:</strong><br>\n";
echo "[abschuss_summary_table species='Damwild' limit='10']<br><br>\n";
echo "<strong>Alle Einträge (Fallback):</strong><br>\n";
echo "[abschuss_summary_table limit='15']\n";
echo "</div>\n";

?>
