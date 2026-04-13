<?php
/**
 * Uninstall script for Hreflang Manager
 * 
 * ?嗅??◤?芷?銵迨?單嚗????澈銝剔??賊?
 * 
 * @package Hreflang_Manager
 * @since 1.0.0
 */

// 憒?銝?? WordPress ?貉?蝔?隤輻嚗????
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * 皜?憭??賊????澈?賊?
 */
function hreflang_manager_uninstall_cleanup() {
    // ?芷憭?閮剖??賊?
    delete_option('hreflang_languages');
    delete_option('hreflang_default_lang');
    
    // 憒??臬?蝡?嚗?????暺??賊?
    if (is_multisite()) {
        global $wpdb;
        
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        $original_blog_id = get_current_blog_id();
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            
            delete_option('hreflang_languages');
            delete_option('hreflang_default_lang');
            
            // 皜? post meta嚗?賂???閮餉圾隞亙??剁?
            // hreflang_manager_cleanup_post_meta();
            
            // 皜? term meta嚗?賂???閮餉圾隞亙??剁?
            // hreflang_manager_cleanup_term_meta();
        }
        
        switch_to_blog($original_blog_id);
    } else {
        // 皜? post meta嚗?賂???閮餉圾隞亙??剁?
        // hreflang_manager_cleanup_post_meta();
        
        // 皜? term meta嚗?賂???閮餉圾隞亙??剁?
        // hreflang_manager_cleanup_term_meta();
    }
}

/**
 * 皜????蝡? hreflang meta
 * 
 * 瘜冽?嚗?閮凋??瑁?甇斗?雿??雿輻??賣靽???鞈?
 * ?亥??嚗???銝?賢?銝剔?閮餉圾
 */
function hreflang_manager_cleanup_post_meta() {
    global $wpdb;
    
    // ?芷???alt_{lang}_url ?澆???meta
    $wpdb->query(
        "DELETE FROM $wpdb->postmeta 
         WHERE meta_key LIKE 'alt_%_url'"
    );
}

/**
 * 皜????憿? hreflang meta
 * 
 * 瘜冽?嚗?閮凋??瑁?甇斗?雿??雿輻??賣靽???鞈?
 * ?亥??嚗???銝?賢?銝剔?閮餉圾
 */
function hreflang_manager_cleanup_term_meta() {
    global $wpdb;
    
    // ?芷???term_alt_{lang}_url ?澆???meta
    $wpdb->query(
        "DELETE FROM $wpdb->termmeta 
         WHERE meta_key LIKE 'term_alt_%_url'"
    );
}

// ?瑁?皜?
hreflang_manager_uninstall_cleanup();
