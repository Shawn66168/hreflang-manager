<?php
/**
 * Plugin Name: Hreflang Manager & Language Switcher
 * Plugin URI:  https://github.com/Shawn66168/hreflang-manager
 * Description: Manage hreflang alternate tags and language switcher. Supports ACF custom URL fields for multi-language SEO.
 * Version:     1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author:      CHUANG,HSIN-HSUEH
 * Author URI:  https://github.com/Shawn66168
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hreflang-manager
 * Domain Path: /languages
 *
 * @package Hreflang_Manager
 */

// 如果直接訪問此檔案則退出
if (!defined('ABSPATH')) {
    exit;
}

// 定義常數
define('HREFLANG_MANAGER_VERSION', '1.0.0');
define('HREFLANG_MANAGER_PLUGIN_FILE', __FILE__);
define('HREFLANG_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HREFLANG_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HREFLANG_MANAGER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * 檢查系統需求
 */
function hreflang_manager_check_requirements() {
    $php_version = phpversion();
    $wp_version = get_bloginfo('version');

    $errors = [];

    if (version_compare($php_version, '7.4', '<')) {
        $errors[] = sprintf(
            __('Hreflang Manager 需要 PHP 7.4 或更高版本。您目前的版本是 %s。', 'hreflang-manager'),
            $php_version
        );
    }

    if (version_compare($wp_version, '5.0', '<')) {
        $errors[] = sprintf(
            __('Hreflang Manager 需要 WordPress 5.0 或更高版本。您目前的版本是 %s。', 'hreflang-manager'),
            $wp_version
        );
    }

    return $errors;
}

/**
 * 外掛啟用
 */
function hreflang_manager_activate() {
    // 檢查需求
    $errors = hreflang_manager_check_requirements();

    if (!empty($errors)) {
        deactivate_plugins(HREFLANG_MANAGER_PLUGIN_BASENAME);
        wp_die(
            implode('<br>', $errors),
            __('Plugin Activation Error', 'hreflang-manager'),
            ['back_link' => true]
        );
    }

    // 設定預設值（僅首次）
    if (false === get_option('hreflang_languages')) {
        $default_languages = [
            [
                'code'   => 'en',
                'locale' => 'en-US',
                'domain' => get_site_url(),
                'label'  => 'English',
                'active' => true,
                'order'  => 1,
            ],
        ];
        add_option('hreflang_languages', $default_languages);
    }

    if (false === get_option('hreflang_default_lang')) {
        add_option('hreflang_default_lang', 'en');
    }

    // 清除快取
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}
register_activation_hook(__FILE__, 'hreflang_manager_activate');

/**
 * 外掛停用
 */
function hreflang_manager_deactivate() {
    // 清除快取
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    // 不刪除設定，留待 uninstall.php 處理
}
register_deactivation_hook(__FILE__, 'hreflang_manager_deactivate');

/**
 * 檢查與其他 SEO 外掛的相容性
 */
function hreflang_manager_check_seo_plugin_compatibility() {
    // Yoast SEO 相容性：停用 Yoast 的 hreflang 輸出（僅 premium 版才會輸出）
    if (defined('WPSEO_VERSION')) {
        add_filter('wpseo_hreflang_output', '__return_false', 99);
    }

    // Rank Math 相容性
    if (defined('RANK_MATH_VERSION')) {
        // 如有衝突，在此加入處理邏輯
    }

    // All in One SEO 相容性
    if (defined('AIOSEO_VERSION')) {
        // 如有衝突，在此加入處理邏輯
    }
}
add_action('plugins_loaded', 'hreflang_manager_check_seo_plugin_compatibility', 1);

// Composer autoload（選用，未來安裝套件時使用）
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 載入核心模組
require_once plugin_dir_path(__FILE__) . 'src/helpers.php';
require_once plugin_dir_path(__FILE__) . 'src/hreflang-core.php';
require_once plugin_dir_path(__FILE__) . 'src/nav-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'src/admin-notice.php';
require_once plugin_dir_path(__FILE__) . 'src/admin-settings.php';

/**
 * 載入語言包
 */
function hreflang_manager_load_textdomain() {
    load_plugin_textdomain(
        'hreflang-manager',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'hreflang_manager_load_textdomain');

/**
 * 在外掛列表新增「設定」快捷連結
 */
function hreflang_manager_add_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('options-general.php?page=hreflang-settings'),
        __('Settings', 'hreflang-manager')
    );
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . HREFLANG_MANAGER_PLUGIN_BASENAME, 'hreflang_manager_add_action_links');

/**
 * 在外掛列表新增文件與支援連結
 */
function hreflang_manager_add_plugin_row_meta($links, $file) {
    if (HREFLANG_MANAGER_PLUGIN_BASENAME === $file) {
        $row_meta = [
            'docs' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/Shawn66168/hreflang-manager/blob/master/README.md',
                __('Documentation', 'hreflang-manager')
            ),
            'support' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/Shawn66168/hreflang-manager/issues',
                __('Support', 'hreflang-manager')
            ),
        ];
        return array_merge($links, $row_meta);
    }
    return $links;
}
add_filter('plugin_row_meta', 'hreflang_manager_add_plugin_row_meta', 10, 2);