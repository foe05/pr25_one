<?php
/**
 * Quick syntax test for the plugin files
 */

// Exit if accessed directly
if (!defined('ABSPATH') && !defined('SYNTAX_TEST_MODE')) {
    exit;
}

// Simulate WordPress environment
define('ABSPATH', __DIR__ . '/');
define('WPINC', 'wp-includes');

// Mock critical WordPress functions to prevent undefined function errors
function add_action($hook, $callback, $priority = 10, $args = 1) { return true; }
function add_shortcode($tag, $callback) { return true; }
function get_option($option, $default = false) { return $default; }
function update_option($option, $value) { return true; }
function sanitize_text_field($str) { return $str; }
function sanitize_key($key) { return $key; }
function current_user_can($capability) { return true; }
function wp_send_json_success($data) { echo json_encode(['success' => true, 'data' => $data]); }
function wp_send_json_error($data) { echo json_encode(['success' => false, 'data' => $data]); }
function check_ajax_referer($action, $query_arg = false) { return true; }
function __($text, $domain = 'default') { return $text; }
function esc_html($text) { return htmlspecialchars($text); }
function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES); }
function is_user_logged_in() { return true; }
function wp_login_url($redirect = '') { return '/wp-login.php'; }
function get_permalink($id = 0) { return '/'; }
function admin_url($path = '') { return '/wp-admin/' . $path; }
function wp_enqueue_script($handle, $src = '') { return true; }
function checked($checked, $current = true, $echo = true) { return $checked === $current ? 'checked="checked"' : ''; }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return ''; }
function register_activation_hook($file, $callback) { return true; }
function register_deactivation_hook($file, $callback) { return true; }
function flush_rewrite_rules() { return true; }
function wp_enqueue_style($handle, $src = '') { return true; }
function wp_localize_script($handle, $object_name, $l10n) { return true; }
function wp_create_nonce($action) { return 'test_nonce'; }
function dbDelta($queries) { return true; }
function current_time($type) { return date('Y-m-d H:i:s'); }
function shortcode_atts($pairs, $atts, $shortcode = '') { return array_merge($pairs, $atts); }

// Mock global $wpdb
global $wpdb;
$wpdb = new stdClass();
$wpdb->prefix = 'wp_';
$wpdb->get_charset_collate = function() { return 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'; };
$wpdb->prepare = function($query, ...$args) { return $query; };
$wpdb->get_var = function($query) { return 0; };
$wpdb->get_results = function($query, $type = OBJECT) { return array(); };
$wpdb->insert = function($table, $data, $format = null) { return 1; };
$wpdb->update = function($table, $data, $where, $format = null, $where_format = null) { return true; };
$wpdb->replace = function($table, $data, $format = null) { return 1; };

echo "Testing syntax of plugin files...\n";

try {
    // Test main plugin file
    echo "Testing abschussplan-hgmh.php...\n";
    include_once 'abschussplan-hgmh.php';
    echo "✅ Main plugin file: OK\n";
    
} catch (Error $e) {
    echo "❌ FATAL ERROR in main plugin file: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ EXCEPTION in main plugin file: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ All plugin files passed syntax test!\n";
echo "The plugin should now be activatable in WordPress.\n";
