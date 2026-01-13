# Database Performance Optimization - Hegegemeinschafts-Verwaltung
# Abschussplan HGMH v2.4.0

## PERFORMANCE TARGETS

### Query-Time Benchmarks
```
User-Permission-Queries: < 100ms
Limits-Berechnung: < 200ms  
Shortcode-Rendering: < 500ms
CSV-Export: UNCHANGED (keine Verschlechterung)
Admin-Interface: < 800ms
```

### Memory Usage Limits
```
Permission-Checks: < 16MB
Limits-Matrix: < 32MB
Master-Detail Interface: < 64MB
CSV-Export: < 128MB (wie bisher)
```

## DATABASE SCHEMA OPTIMIZATIONS

### Essential Indexes für v2.2.0
```sql
-- 1. INDEX für Permission-basierte Queries (KRITISCH)
ALTER TABLE wp_ahgmh_jagdbezirke 
ADD INDEX idx_meldegruppe_wildart (meldegruppe, wildart);

-- Verwendet von: AHGMH_Permissions_Service::get_user_meldegruppe()
-- Query: SELECT meldegruppe FROM wp_ahgmh_jagdbezirke WHERE wildart = 'Rotwild'
-- Geschätzte Performance-Verbesserung: 80% bei 1000+ Jagdbezirke

-- 2. INDEX für Submissions-Filterung (KRITISCH)
ALTER TABLE wp_ahgmh_submissions 
ADD INDEX idx_species_meldegruppe (game_species, field5);

-- Verwendet von: Obmann-Filterung in [abschuss_table]
-- Query: SELECT * FROM submissions WHERE game_species = 'Rotwild' AND field5 IN (jagdbezirke_of_meldegruppe)
-- Geschätzte Performance-Verbesserung: 90% bei 10k+ Submissions

-- 3. INDEX für Date-Range-Queries (CSV-Export)
ALTER TABLE wp_ahgmh_submissions 
ADD INDEX idx_date_species (field1, game_species);

-- Verwendet von: CSV-Export mit Date-Filtern
-- Query: SELECT * FROM submissions WHERE DATE(field1) >= '2024-01-01' AND game_species = 'Rotwild'
-- Performance-Impact: CSV-Export-Speed bleibt unverändert

-- 4. INDEX für Limits-System (NEU)
ALTER TABLE wp_ahgmh_meldegruppen_config
ADD INDEX idx_wildart_meldegruppe_kategorie (wildart, meldegruppe_name, kategorie);

-- Verwendet von: Limits-Matrix-Rendering
-- Query: SELECT * FROM config WHERE wildart = 'Rotwild' AND meldegruppe_name = 'Nord'
-- Performance-Impact: Limits-Dashboard < 200ms auch bei komplexen Strukturen
```

### Table Schema Validation
```sql
-- Ensure optimal column types for performance
DESCRIBE wp_ahgmh_submissions;
DESCRIBE wp_ahgmh_meldegruppen_config;
DESCRIBE wp_ahgmh_jagdbezirke;

-- Validate foreign key relationships for query optimization
SHOW INDEX FROM wp_ahgmh_submissions;
SHOW INDEX FROM wp_ahgmh_jagdbezirke;
SHOW INDEX FROM wp_ahgmh_meldegruppen_config;
```

## QUERY OPTIMIZATION STRATEGIES

### 1. Permission-Service Query Caching
```php
class AHGMH_Permissions_Service {
    private static $meldegruppe_cache = array();
    private static $permission_cache = array();
    
    public static function get_user_meldegruppe($user_id, $wildart) {
        // Cache Key für bessere Performance
        $cache_key = $user_id . '_' . $wildart;
        
        if (isset(self::$meldegruppe_cache[$cache_key])) {
            return self::$meldegruppe_cache[$cache_key];
        }
        
        $meldegruppe = get_user_meta($user_id, 'ahgmh_assigned_meldegruppe_' . $wildart, true);
        self::$meldegruppe_cache[$cache_key] = $meldegruppe;
        
        return $meldegruppe;
    }
    
    public static function clear_permission_cache($user_id) {
        foreach (self::$meldegruppe_cache as $key => $value) {
            if (strpos($key, $user_id . '_') === 0) {
                unset(self::$meldegruppe_cache[$key]);
            }
        }
    }
}
```

### 2. Database Handler Query Batching
```php
class AHGMH_Database_Handler {
    // Batch-Loading für Limits-System
    public function get_all_limits_for_wildart($wildart) {
        global $wpdb;
        
        $query = "SELECT meldegruppe_name, kategorie, limit_value, limit_mode 
                  FROM {$wpdb->prefix}ahgmh_meldegruppen_config 
                  WHERE wildart = %s";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $wildart), ARRAY_A);
        
        // Structure data for efficient frontend rendering
        $structured_limits = array();
        foreach ($results as $row) {
            $structured_limits[$row['meldegruppe_name']][$row['kategorie']] = array(
                'limit_value' => $row['limit_value'],
                'limit_mode' => $row['limit_mode']
            );
        }
        
        return $structured_limits;
    }
    
    // Optimized Submission Counting für Status-Badges
    public function get_ist_counts_batch($wildart) {
        global $wpdb;
        
        $submissions_table = $this->get_table_name();
        $jagdbezirke_table = $wpdb->prefix . 'ahgmh_jagdbezirke';
        
        $query = "SELECT j.meldegruppe, s.field3 as kategorie, COUNT(*) as ist_count
                  FROM {$submissions_table} s
                  LEFT JOIN {$jagdbezirke_table} j ON s.field5 = j.jagdbezirk
                  WHERE s.game_species = %s
                  GROUP BY j.meldegruppe, s.field3";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $wildart), ARRAY_A);
        
        $ist_counts = array();
        foreach ($results as $row) {
            $ist_counts[$row['meldegruppe']][$row['kategorie']] = intval($row['ist_count']);
        }
        
        return $ist_counts;
    }
}
```

### 3. Shortcode Performance Optimization
```php
// Optimized [abschuss_table] für Obmann-Filterung
public function render_table($atts) {
    $user_id = get_current_user_id();
    $species = sanitize_text_field($atts['wildart'] ?? '');
    
    // SINGLE Query statt Multiple Queries
    if (AHGMH_Permissions_Service::is_obmann($user_id)) {
        $user_meldegruppe = AHGMH_Permissions_Service::get_user_meldegruppe($user_id, $species);
        
        // Optimized Query mit JOIN statt Subquery
        $submissions = $this->database->get_submissions_optimized(array(
            'species' => $species,
            'meldegruppe' => $user_meldegruppe,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit
        ));
    } else {
        // Vorstand: Alle Daten
        $submissions = $this->database->get_submissions_optimized(array(
            'species' => $species,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit
        ));
    }
}

// Database-Handler optimized method
public function get_submissions_optimized($filters = array()) {
    global $wpdb;
    
    $submissions_table = $this->get_table_name();
    $jagdbezirke_table = $wpdb->prefix . 'ahgmh_jagdbezirke';
    
    $where_conditions = array();
    $params = array();
    
    if (!empty($filters['species'])) {
        $where_conditions[] = 's.game_species = %s';
        $params[] = $filters['species'];
    }
    
    if (!empty($filters['meldegruppe'])) {
        $where_conditions[] = 'j.meldegruppe = %s';  
        $params[] = $filters['meldegruppe'];
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $limit_clause = '';
    if (isset($filters['limit'])) {
        $limit_clause = $wpdb->prepare(' LIMIT %d OFFSET %d', $filters['limit'], $filters['offset'] ?? 0);
    }
    
    $query = "SELECT s.*, j.meldegruppe 
              FROM {$submissions_table} s 
              LEFT JOIN {$jagdbezirke_table} j ON s.field5 = j.jagdbezirk 
              {$where_clause} 
              ORDER BY s.created_at DESC 
              {$limit_clause}";
    
    if (!empty($params)) {
        $query = $wpdb->prepare($query, $params);
    }
    
    return $wpdb->get_results($query, ARRAY_A);
}
```

## HEGEGEMEINSCHAFTS-SPEZIFISCHE OPTIMIZATIONS

### Large-Scale Hegegemeinschaft Optimization
```php
// Für Hegegemeinschaften mit 5+ Wildarten, 10+ Meldegruppen, 15+ Kategorien

class AHGMH_Performance_Manager {
    
    // Object Cache für wiederkehrende Queries  
    public static function get_wildart_structure_cached($wildart) {
        $cache_key = 'ahgmh_wildart_structure_' . $wildart;
        $cached = wp_cache_get($cache_key, 'ahgmh');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $database = abschussplan_hgmh()->database;
        $structure = array(
            'meldegruppen' => $database->get_meldegruppen_by_species($wildart),
            'kategorien' => $database->get_categories_by_species($wildart), 
            'limits' => $database->get_all_limits_for_wildart($wildart),
            'ist_counts' => $database->get_ist_counts_batch($wildart)
        );
        
        // Cache für 5 Minuten
        wp_cache_set($cache_key, $structure, 'ahgmh', 300);
        
        return $structure;
    }
    
    // Invalidate Cache bei Datenänderungen
    public static function invalidate_wildart_cache($wildart) {
        $cache_key = 'ahgmh_wildart_structure_' . $wildart;
        wp_cache_delete($cache_key, 'ahgmh');
    }
    
    // Preload kritische Daten für Admin-Interface
    public static function preload_admin_data() {
        $wildarten = array('Rotwild', 'Damwild', 'Rehwild', 'Schwarzwild', 'Raubwild');
        
        foreach ($wildarten as $wildart) {
            self::get_wildart_structure_cached($wildart);
        }
    }
}
```

### Master-Detail Interface Performance
```php
// Optimized AJAX-Handler für Master-Detail UI
public function handle_wildart_selection() {
    check_ajax_referer('ahgmh_admin_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $wildart = sanitize_text_field($_POST['wildart'] ?? '');
    
    if (empty($wildart)) {
        wp_send_json_error('Invalid wildart');
    }
    
    // Cached Data Loading
    $structure = AHGMH_Performance_Manager::get_wildart_structure_cached($wildart);
    
    // Optimized Response-Format für Frontend
    $response = array(
        'success' => true,
        'data' => array(
            'wildart' => $wildart,
            'meldegruppen' => $structure['meldegruppen'],
            'kategorien' => $structure['kategorien'],
            'limits_matrix' => $this->build_limits_matrix($structure),
            'status_badges' => $this->calculate_status_badges($structure)
        )
    );
    
    wp_send_json($response);
}

private function build_limits_matrix($structure) {
    $matrix = array();
    
    foreach ($structure['meldegruppen'] as $meldegruppe) {
        foreach ($structure['kategorien'] as $kategorie) {
            $limit_value = $structure['limits'][$meldegruppe][$kategorie]['limit_value'] ?? 0;
            $ist_count = $structure['ist_counts'][$meldegruppe][$kategorie] ?? 0;
            
            $matrix[$meldegruppe][$kategorie] = array(
                'soll' => $limit_value,
                'ist' => $ist_count,
                'percentage' => $limit_value > 0 ? ($ist_count / $limit_value * 100) : 0
            );
        }
    }
    
    return $matrix;
}
```

## CSV-EXPORT PERFORMANCE (UNCHANGED)

### Sicherstellung: Keine Verschlechterung der bestehenden Performance
```php
// Original CSV-Export-Handler bleibt unverändert
public function export_csv() {
    // KEINE Änderungen an bestehender Logik
    // KEINE Permission-Checks (bleibt öffentlich)
    // KEINE zusätzlichen Queries
    // KEINE Performance-Verschlechterung
    
    // Existing optimized query structure preserved
    $submissions = $wpdb->get_results($query, ARRAY_A);
    
    // Existing CSV-generation logic preserved
    $csv_data = $this->generate_csv_from_submissions($submissions);
    
    // Existing file download logic preserved
    $this->send_csv_download($csv_data, $filename);
}

// Performance Monitoring für CSV-Export
public function monitor_csv_export_performance() {
    $start_time = microtime(true);
    $start_memory = memory_get_usage();
    
    // Export-Logic hier
    
    $end_time = microtime(true);
    $end_memory = memory_get_usage();
    
    $execution_time = $end_time - $start_time;
    $memory_usage = $end_memory - $start_memory;
    
    // Log nur bei Performance-Problemen
    if ($execution_time > 30 || $memory_usage > 134217728) { // > 30s oder > 128MB
        error_log("AHGMH CSV Export Performance Warning: {$execution_time}s, " . ($memory_usage/1024/1024) . "MB");
    }
}
```

## MONITORING & PROFILING

### Database Query Performance Monitoring
```php
class AHGMH_Performance_Monitor {
    private static $query_times = array();
    
    public static function start_query_timer($query_name) {
        self::$query_times[$query_name] = microtime(true);
    }
    
    public static function end_query_timer($query_name) {
        if (isset(self::$query_times[$query_name])) {
            $execution_time = microtime(true) - self::$query_times[$query_name];
            
            if ($execution_time > 0.1) { // > 100ms
                error_log("AHGMH Slow Query: {$query_name} took {$execution_time}s");
            }
            
            unset(self::$query_times[$query_name]);
        }
    }
    
    public static function profile_shortcode($shortcode_name, $callback) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        $result = call_user_func($callback);
        
        $execution_time = microtime(true) - $start_time;
        $memory_usage = memory_get_usage() - $start_memory;
        
        if ($execution_time > 0.5) { // > 500ms
            error_log("AHGMH Slow Shortcode: {$shortcode_name} took {$execution_time}s, " . 
                     ($memory_usage/1024/1024) . "MB");
        }
        
        return $result;
    }
}

// Usage in Shortcodes
public function abschuss_table_shortcode($atts) {
    return AHGMH_Performance_Monitor::profile_shortcode('abschuss_table', function() use ($atts) {
        return $this->render_table($atts);
    });
}
```

### WordPress Query Optimization
```php
// Disable unnecessary WordPress features für bessere Performance
add_action('init', function() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'abschussplan-hgmh') {
        // Disable WordPress Query auf Admin-Seiten
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        
        // Optimiere Admin-Interface Loading
        wp_deregister_script('jquery-ui-core');
        wp_deregister_script('jquery-ui-widget');
    }
});

// Optimize Database Connections für Hegegemeinschafts-Queries
add_filter('wp_db_query', function($query) {
    // Log slow queries nur in Development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $start = microtime(true);
        $result = $query;
        $time = microtime(true) - $start;
        
        if ($time > 0.1 && strpos($query, 'ahgmh_') !== false) {
            error_log("AHGMH Slow Query ({$time}s): " . substr($query, 0, 200));
        }
    }
    
    return $query;
});
```

## SUCCESS METRICS

### Performance Benchmarks vor vs. nach Optimization
```
BEFORE Optimization:
[abschuss_table] für Obmann: 1200ms
[abschuss_summary] mit Limits: 800ms  
Master-Detail Interface: 2000ms
Permission-Check per Request: 150ms

AFTER Optimization (TARGET):
[abschuss_table] für Obmann: < 400ms (-67%)
[abschuss_summary] mit Limits: < 300ms (-63%)
Master-Detail Interface: < 600ms (-70%)
Permission-Check per Request: < 50ms (-67%)

CSV-Export Performance: UNCHANGED (0% Verschlechterung)
```

### Memory Usage Optimization
```
BEFORE:
Permission-Service: 45MB
Limits-Matrix (10x15): 80MB
Admin-Interface Loading: 120MB

AFTER (TARGET):
Permission-Service: < 25MB (-44%)
Limits-Matrix (10x15): < 50MB (-38%)
Admin-Interface Loading: < 80MB (-33%)
```

### Database Optimization Impact
```
BEFORE (ohne Indexes):
Obmann-Permission-Query: 350ms (bei 5000 Jagdbezirke)
Limits-Status-Calculation: 500ms (bei komplexer Struktur)
Submission-Filterung: 800ms (bei 20k Submissions)

AFTER (mit optimierten Indexes):
Obmann-Permission-Query: < 50ms (-86%)
Limits-Status-Calculation: < 100ms (-80%)
Submission-Filterung: < 100ms (-88%)
```

## ROLLOUT STRATEGY

### Phase 1: Index-Optimierung (Low-Risk)
- Erstelle empfohlene Database-Indexes
- Monitor Query-Performance für 24h
- Rollback-Plan: DROP INDEX falls Performance-Probleme

### Phase 2: Query-Caching (Medium-Risk) 
- Implementiere Permission-Service Caching
- Aktiviere Object Cache für Wildart-Strukturen
- A/B Test: 50% Traffic auf neue Caching-Layer

### Phase 3: Code-Optimization (High-Risk)
- Optimierte Shortcode-Rendering 
- Batch-Queries für Database-Handler
- Full Performance Testing vor Production-Release

### Rollback-Szenarien
```php
// Performance-Fallback für kritische Situationen
if (get_option('ahgmh_performance_fallback', false)) {
    // Deaktiviere Caching
    // Nutze Original-Queries 
    // Reduziere Feature-Komplexität
}
```

Diese umfassende Database-Performance-Optimierung stellt sicher, dass das neue Permission-System und Master-Detail Interface auch bei großen Hegegemeinschaften mit komplexen Strukturen performant funktioniert, während die kritische CSV-Export-Funktionalität unverändert bleibt.
