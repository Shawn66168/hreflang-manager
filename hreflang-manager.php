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

// 憒??湔閮芸?甇斗?獢????
if (!defined('ABSPATH')) {
    exit;
}

// 摰儔憭?撣豢
define('HREFLANG_MANAGER_VERSION', '1.0.0');
define('HREFLANG_MANAGER_PLUGIN_FILE', __FILE__);
define('HREFLANG_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HREFLANG_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HREFLANG_MANAGER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * 瑼Ｘ蝟餌絞?瘙?
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
 * 憭???銵?
 */
function hreflang_manager_activate() {
    // 瑼Ｘ?瘙?
    $errors = hreflang_manager_check_requirements();
    
    if (!empty($errors)) {
        deactivate_plugins(HREFLANG_MANAGER_PLUGIN_BASENAME);
        wp_die(
            implode('<br>', $errors),
            __('Plugin Activation Error', 'hreflang-manager'),
            ['back_link' => true]
        );
    }
    
    // 閮剖??身?賊?嚗???摮嚗?
    if (false === get_option('hreflang_languages')) {
        $default_languages = [
            [
                'code' => 'en',
                'locale' => 'en-US',
                'domain' => get_site_url(),
                'label' => 'English',
                'active' => true,
                'order' => 1
            ]
        ];
        add_option('hreflang_languages', $default_languages);
    }
    
    if (false === get_option('hreflang_default_lang')) {
        add_option('hreflang_default_lang', 'en');
    }
    
    // 皜敹怠?
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}
register_activation_hook(__FILE__, 'hreflang_manager_activate');

/**
 * 憭???銵?
 */
function hreflang_manager_deactivate() {
    // 皜敹怠?
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // 瘜冽?嚗??券ㄐ?芷?賊?嚗??蝙?刻身摰?
    // ?賊???貉?? uninstall.php ??
}
register_deactivation_hook(__FILE__, 'hreflang_manager_deactivate');

/**
 * 瑼Ｘ?隞?SEO 憭??摰寞?
 */
function hreflang_manager_check_seo_plugin_compatibility() {
    // Yoast SEO ?詨捆??
    if (defined('WPSEO_VERSION')) {
        // 蝣箔?銝? Yoast ??hreflang ?銵?
        // Yoast ??hreflang ??閬?premium ?嚗?隞仿虜銝?銵?
        add_filter('wpseo_hreflang_output', '__return_false', 99);
    }
    
    // Rank Math ?詨捆??
    if (defined('RANK_MATH_VERSION')) {
        // 憒??閬??臭誑?券ㄐ瘛餃??詨捆?扯???
    }
    
    // All in One SEO ?詨捆??
    if (defined('AIOSEO_VERSION')) {
        // 憒??閬??臭誑?券ㄐ瘛餃??詨捆?扯???
    }
}
add_action('plugins_loaded', 'hreflang_manager_check_seo_plugin_compatibility', 1);

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
 * 頛蝧餉陌
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
 * 瘛餃?閮剖?????啣???銵券???
 */
function hreflang_manager_add_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('options-general.php?page=hreflang-settings'),
        __('Documentation', 'hreflang-manager'));
    
    array_unshift($links, $settings_link);
    
    return $links;
}
add_filter('plugin_action_links_' . HREFLANG_MANAGER_PLUGIN_BASENAME, 'hreflang_manager_add_action_links');

/**
 * 瘛餃?憭?鞈????
 */
function hreflang_manager_add_plugin_row_meta($links, $file) {
    if (HREFLANG_MANAGER_PLUGIN_BASENAME === $file) {
        $row_meta = [
            'docs' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/Shawn66168/hreflang-manager/blob/master/README.md',
                __('Documentation', 'hreflang-manager')),
            'support' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/Shawn66168/hreflang-manager/issues',
                __('Support', 'hreflang-manager')),
        ];
        
        return array_merge($links, $row_meta);
    }
    
    return $links;
}
add_filter('plugin_row_meta', 'hreflang_manager_add_plugin_row_meta', 10, 2);

