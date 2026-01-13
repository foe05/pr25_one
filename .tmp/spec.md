# Feature Flags System

## Übersicht
Implementierung eines Feature-Flag-Systems für sicheres, schrittweises Rollout neuer Features im WordPress Plugin für Hegegemeinschaftsmanagement.

## User Story
```
Als Entwickler
Möchte ich neue Features schrittweise aktivieren können
Damit ich sicher deployen kann ohne Production zu gefährden
```

## Problem
Das Plugin wird von Prototyp zu Production-Grade refactored. Während der Umstellung müssen alte und neue Features parallel existieren. Ohne Feature Flags müssen alle Änderungen sofort live gehen - riskant!

## Lösung
Feature-Flag-System mit Admin-UI zum Umschalten zwischen alter und neuer Implementierung.

## Akzeptanzkriterien

### Must-Have
- [ ] Class `HGMH_Feature_Flags` erstellt
- [ ] Feature Flags werden in WordPress Options gespeichert
- [ ] Admin-Seite "Feature Flags" mit Toggle-Switches
- [ ] Folgende Flags implementiert:
  - `use_new_db_schema` (Default: OFF)
  - `use_public_form` (Default: OFF)
  - `use_moderation` (Default: OFF)
  - `use_activity_log` (Default: OFF)
- [ ] Code-Checks funktionieren: `HGMH_Feature_Flags::is_enabled('flag_name')`
- [ ] Nur Vorstand (capability: `manage_options`) kann Flags ändern
- [ ] AJAX Save funktioniert

### Should-Have
- [ ] Beschreibung pro Flag (was macht es?)
- [ ] Warning bei kritischen Flags
- [ ] Logging wenn Flag geändert wird

### Nice-to-Have
- [ ] Flag-Dependencies (z.B. `use_public_form` erfordert `use_new_db_schema`)
- [ ] Bulk Enable/Disable

## Technische Implementierung

### Datei-Struktur
```
includes/
├── class-feature-flags.php          # Core Feature Flag Logic
admin/
└── feature-flags.php                # Admin UI
```

### Code-Template: `includes/class-feature-flags.php`

```php
<?php
/**
 * Feature Flags Manager
 * 
 * Enables safe, gradual rollout of new features
 */

if (!defined('ABSPATH')) {
    exit;
}

class HGMH_Feature_Flags {
    
    const OPTION_KEY = 'hgmh_feature_flags';
    
    // Flag Definitions
    const FLAG_NEW_DB_SCHEMA = 'use_new_db_schema';
    const FLAG_PUBLIC_FORM = 'use_public_form';
    const FLAG_MODERATION = 'use_moderation';
    const FLAG_ACTIVITY_LOG = 'use_activity_log';
    
    private static $flags = null;
    
    /**
     * Initialize flags from database
     */
    private static function load_flags() {
        if (self::$flags === null) {
            $defaults = [
                self::FLAG_NEW_DB_SCHEMA => false,
                self::FLAG_PUBLIC_FORM => false,
                self::FLAG_MODERATION => false,
                self::FLAG_ACTIVITY_LOG => false
            ];
            
            self::$flags = get_option(self::OPTION_KEY, $defaults);
        }
    }
    
    /**
     * Check if a feature flag is enabled
     * 
     * @param string $flag_name Flag constant
     * @return bool
     */
    public static function is_enabled($flag_name) {
        self::load_flags();
        return isset(self::$flags[$flag_name]) && self::$flags[$flag_name] === true;
    }
    
    /**
     * Enable a feature flag
     */
    public static function enable($flag_name) {
        self::load_flags();
        self::$flags[$flag_name] = true;
        update_option(self::OPTION_KEY, self::$flags);
    }
    
    /**
     * Disable a feature flag
     */
    public static function disable($flag_name) {
        self::load_flags();
        self::$flags[$flag_name] = false;
        update_option(self::OPTION_KEY, self::$flags);
    }
    
    /**
     * Get all flags with metadata
     */
    public static function get_all_flags() {
        return [
            self::FLAG_NEW_DB_SCHEMA => [
                'label' => 'Neues Datenbank-Schema',
                'description' => 'Nutzt hgmh_submissions_v2 statt ahgmh_submissions',
                'critical' => true
            ],
            self::FLAG_PUBLIC_FORM => [
                'label' => 'Öffentliches Formular',
                'description' => 'Aktiviert anonyme Meldungen mit Email-Verifizierung',
                'critical' => false
            ],
            self::FLAG_MODERATION => [
                'label' => 'Moderation-Workflow',
                'description' => 'Aktiviert Approve/Reject/Edit in abschuss_table',
                'critical' => false
            ],
            self::FLAG_ACTIVITY_LOG => [
                'label' => 'Activity Logging',
                'description' => 'Trackt alle User-Aktionen für Analytics',
                'critical' => false
            ]
        ];
    }
}
```

### Admin-UI: `admin/feature-flags.php`

```php
<?php
/**
 * Feature Flags Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission check
if (!current_user_can('manage_options')) {
    wp_die('Keine Berechtigung');
}

// Handle AJAX save
add_action('wp_ajax_hgmh_save_feature_flags', function() {
    check_ajax_referer('hgmh_feature_flags_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
    }
    
    $flags = $_POST['flags'] ?? [];
    
    foreach ($flags as $flag_name => $enabled) {
        if ($enabled) {
            HGMH_Feature_Flags::enable($flag_name);
        } else {
            HGMH_Feature_Flags::disable($flag_name);
        }
    }
    
    wp_send_json_success(['message' => 'Feature Flags gespeichert']);
});

$all_flags = HGMH_Feature_Flags::get_all_flags();
?>

<div class="wrap">
    <h1>🚩 Feature Flags</h1>
    
    <div class="notice notice-warning">
        <p><strong>⚠️ Achtung:</strong> Änderungen an Feature Flags können die Plugin-Funktionalität beeinflussen!</p>
    </div>
    
    <form id="feature-flags-form">
        <?php wp_nonce_field('hgmh_feature_flags_nonce', 'nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Beschreibung</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_flags as $flag_name => $flag_meta): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($flag_meta['label']); ?></strong>
                        <?php if ($flag_meta['critical']): ?>
                            <span class="dashicons dashicons-warning" style="color: red;" title="Kritisches Flag"></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($flag_meta['description']); ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" 
                                   name="flags[<?php echo esc_attr($flag_name); ?>]" 
                                   <?php checked(HGMH_Feature_Flags::is_enabled($flag_name)); ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary">Änderungen speichern</button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#feature-flags-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=hgmh_save_feature_flags',
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            }
        });
    });
});
</script>

<style>
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}
.switch input { display: none; }
.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider { background-color: #2196F3; }
input:checked + .slider:before { transform: translateX(26px); }
</style>
```

## Integration ins Haupt-Plugin

In `hgmh-core.php`:

```php
// Load Feature Flags early
require_once plugin_dir_path(__FILE__) . 'includes/class-feature-flags.php';

// Admin Menu
add_action('admin_menu', function() {
    add_submenu_page(
        'hgmh-dashboard',
        'Feature Flags',
        '🚩 Feature Flags',
        'manage_options',
        'hgmh-feature-flags',
        function() {
            require_once plugin_dir_path(__FILE__) . 'admin/feature-flags.php';
        }
    );
});
```

## Verwendung im Code

```php
// Beispiel: Neues vs. altes Schema
if (HGMH_Feature_Flags::is_enabled(HGMH_Feature_Flags::FLAG_NEW_DB_SCHEMA)) {
    // Nutze neue Tabelle
    $submissions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hgmh_submissions_v2");
} else {
    // Nutze alte Tabelle
    $submissions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ahgmh_submissions");
}
```

## Testing

### Manueller Test
1. Plugin aktivieren
2. Im Admin: "Abschussplan → Feature Flags" öffnen
3. Flag einschalten → Seite neu laden → Flag sollte EIN sein
4. Flag ausschalten → Prüfen dass es AUS ist

### Code-Test
```php
// Test Flag-Check
assert(HGMH_Feature_Flags::is_enabled('use_new_db_schema') === false); // Default OFF

// Test Enable
HGMH_Feature_Flags::enable('use_new_db_schema');
assert(HGMH_Feature_Flags::is_enabled('use_new_db_schema') === true);

// Test Disable
HGMH_Feature_Flags::disable('use_new_db_schema');
assert(HGMH_Feature_Flags::is_enabled('use_new_db_schema') === false);
```

## Abhängigkeiten
- Keine (dies ist der erste Task!)

## Geschätzte Komplexität
**SIMPLE** (2-3 Stunden)

## Story Points
**3 Points**

## Priorität
🔴 **CRITICAL** - Muss als erstes implementiert werden!
