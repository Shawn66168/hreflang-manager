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
function hreflang_manager_uninstall_cleanup() {
    // 刪除外掛設定選項
    delete_option('hreflang_languages');
    delete_option('hreflang_default_lang');

    // 多站點環境：逐一清理各子站資料
    if (is_multisite()) {
        global $wpdb;

        $blog_ids        = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);

            delete_option('hreflang_languages');
            delete_option('hreflang_default_lang');

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

// 執行清理
hreflang_manager_uninstall_cleanup();