<?php
/**
 * Plugin Name: Hreflang Manager & Language Switcher
 * Plugin URI:  https://github.com/shawen66/hreflang-manager
 * Description: 輸出 hreflang 標籤 + 語言切換元件，支援多語站點與 ACF URL 對應。
 * Version:     1.0.0
 * Author:      CHUANG,HSIN-HSUEH
 * Author URI:  https://github.com/shawen66
 * Text Domain: hreflang-manager
 * Domain Path: /languages
 */
defined('ABSPATH') || exit;

// Composer autoload (optional, if you install libs later)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load core parts
require_once plugin_dir_path(__FILE__) . 'src/helpers.php';
require_once plugin_dir_path(__FILE__) . 'src/hreflang-core.php';
require_once plugin_dir_path(__FILE__) . 'src/nav-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'src/admin-notice.php';
require_once plugin_dir_path(__FILE__) . 'src/admin-settings.php';

/**
 * 載入翻譯
 */
function hreflang_manager_load_textdomain() {
    load_plugin_textdomain(
        'hreflang-manager',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'hreflang_manager_load_textdomain');
