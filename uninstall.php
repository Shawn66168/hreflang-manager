<?php
/**
 * Uninstall script for Hreflang Manager
 * 
 * 當外掛被刪除時執行此腳本，清理資料庫中的選項
 * 
 * @package Hreflang_Manager
 * @since 1.0.0
 */

// 如果不是透過 WordPress 卸載程序調用，則退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * 清理外掛相關的資料庫選項
 */
function hreflang_manager_uninstall_cleanup() {
    // 刪除外掛設定選項
    delete_option('hreflang_languages');
    delete_option('hreflang_default_lang');
    
    // 如果是多站點，清理所有站點的選項
    if (is_multisite()) {
        global $wpdb;
        
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            
            delete_option('hreflang_languages');
            delete_option('hreflang_default_lang');
            
            // 清理 post meta（可選，取消註解以啟用）
            // hreflang_manager_cleanup_post_meta();
            
            // 清理 term meta（可選，取消註解以啟用）
            // hreflang_manager_cleanup_term_meta();
        }
        
        switch_to_blog($original_blog_id);
    } else {
        // 清理 post meta（可選，取消註解以啟用）
        // hreflang_manager_cleanup_post_meta();
        
        // 清理 term meta（可選，取消註解以啟用）
        // hreflang_manager_cleanup_term_meta();
    }
}

/**
 * 清理所有文章的 hreflang meta
 * 
 * 注意：預設不執行此操作，因為使用者可能想保留這些資料
 * 若要啟用，請取消上方函式中的註解
 */
function hreflang_manager_cleanup_post_meta() {
    global $wpdb;
    
    // 刪除所有 alt_{lang}_url 格式的 meta
    $wpdb->query(
        "DELETE FROM $wpdb->postmeta 
         WHERE meta_key LIKE 'alt_%_url'"
    );
}

/**
 * 清理所有分類的 hreflang meta
 * 
 * 注意：預設不執行此操作，因為使用者可能想保留這些資料
 * 若要啟用，請取消上方函式中的註解
 */
function hreflang_manager_cleanup_term_meta() {
    global $wpdb;
    
    // 刪除所有 term_alt_{lang}_url 格式的 meta
    $wpdb->query(
        "DELETE FROM $wpdb->termmeta 
         WHERE meta_key LIKE 'term_alt_%_url'"
    );
}

// 執行清理
hreflang_manager_uninstall_cleanup();
