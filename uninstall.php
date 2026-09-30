<?php
/**
 * Uninstall script for Hreflang Manager
 *
 * 外掛卸載時執行，完整清除所有儲存的資料。
 *
 * @package Hreflang_Manager
 * @since 1.0.0
 */

// 如果不是從 WordPress 觸發的卸載流程，則退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * 執行卸載清理作業
 */
function hreflang_manager_delete_options() {
    delete_option('hreflang_languages');
    delete_option('hreflang_default_lang');
    delete_option('hreflang_auto_same_slug');
    delete_option('hreflang_switcher_styles');

    // 外觀主題（每組一個 option）
    $themes = get_option('hreflang_style_themes', []);
    if (is_array($themes)) {
        foreach ($themes as $theme) {
            delete_option('hreflang_switcher_styles_' . $theme);
        }
    }
    delete_option('hreflang_style_themes');
}

/**
 * 同一站台是否還裝著另一份 hreflang-manager（例如改名保留的舊副本）
 *
 * 各份副本共用同一組 option，刪除其中一份時若照常清理，
 * 仍在使用的那份會失去全部設定而停止輸出 hreflang。
 *
 * @return bool
 */
function hreflang_manager_has_other_copy() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    foreach (array_keys(get_plugins()) as $plugin_file) {
        if ($plugin_file !== WP_UNINSTALL_PLUGIN && basename($plugin_file) === 'hreflang-manager.php') {
            return true;
        }
    }

    return false;
}

function hreflang_manager_uninstall_cleanup() {
    // 刪除外掛設定選項
    hreflang_manager_delete_options();

    // 多站點環境：逐一清理各子站資料
    if (is_multisite()) {
        global $wpdb;

        $blog_ids        = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);

            hreflang_manager_delete_options();

            // 如需清理 post meta，請取消以下注解
            // hreflang_manager_cleanup_post_meta();

            // 如需清理 term meta，請取消以下注解
            // hreflang_manager_cleanup_term_meta();
        }

        switch_to_blog($original_blog_id);
    } else {
        // 如需清理 post meta，請取消以下注解
        // hreflang_manager_cleanup_post_meta();

        // 如需清理 term meta，請取消以下注解
        // hreflang_manager_cleanup_term_meta();
    }
}

/**
 * 清除所有文章的 hreflang meta 欄位
 *
 * 注意：此操作不可逆，請確認業務需求後再啟用。
 */
function hreflang_manager_cleanup_post_meta() {
    global $wpdb;

    // 刪除所有 alt_{lang}_url 格式的 post meta
    $wpdb->query(
        "DELETE FROM $wpdb->postmeta
         WHERE meta_key LIKE 'alt_%_url'"
    );
}

/**
 * 清除所有分類/標籤的 hreflang meta 欄位
 *
 * 注意：此操作不可逆，請確認業務需求後再啟用。
 */
function hreflang_manager_cleanup_term_meta() {
    global $wpdb;

    // 刪除所有 term_alt_{lang}_url 格式的 term meta
    $wpdb->query(
        "DELETE FROM $wpdb->termmeta
         WHERE meta_key LIKE 'term_alt_%_url'"
    );
}

// 執行清理（還有其他副本在用這組設定時跳過）
if (!hreflang_manager_has_other_copy()) {
    hreflang_manager_uninstall_cleanup();
}