<?php
/**
 * Helper functions for Hreflang Manager Plugin
 */
defined('ABSPATH') || exit;

/**
 * 取得外掛設定的語言清單
 * 
 * @return array 語言陣列
 */
function hreflang_get_languages() {
    $default_languages = [
        [
            'code' => 'en',
            'locale' => 'en-US',
            'domain' => get_site_url(),
            'label' => 'English',
            'active' => true,
            'order' => 1
        ],
        [
            'code' => 'zh-Hant',
            'locale' => 'zh-Hant',
            'domain' => get_site_url(),
            'label' => '繁體中文',
            'active' => true,
            'order' => 2
        ]
    ];
    
    $languages = get_option('hreflang_languages', $default_languages);
    
    // 允許過濾器修改語言清單
    return apply_filters('hreflang_languages', $languages);
}

/**
 * 取得當前頁面的所有語言對應 URL
 * 
 * @return array 語言代碼 => URL 的對應陣列
 */
function hreflang_get_alternate_urls() {
    $urls = [];
    $languages = hreflang_get_languages();
    
    // 判斷當前頁面類型
    if (is_singular()) {
        // 文章或頁面
        $post_id = get_the_ID();
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            
            $meta_key = 'alt_' . $lang['code'] . '_url';
            $alt_url = get_post_meta($post_id, $meta_key, true);
            
            if (!empty($alt_url)) {
                $urls[$lang['code']] = $alt_url;
            }
        }
    } elseif (is_category() || is_tag() || is_tax()) {
        // 分類、標籤或自定義分類
        $term = get_queried_object();
        if ($term) {
            foreach ($languages as $lang) {
                if (!$lang['active']) continue;
                
                $meta_key = 'term_alt_' . $lang['code'] . '_url';
                $alt_url = get_term_meta($term->term_id, $meta_key, true);
                
                if (!empty($alt_url)) {
                    $urls[$lang['code']] = $alt_url;
                }
            }
        }
    } elseif (is_home() || is_front_page()) {
        // 首頁
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $urls[$lang['code']] = trailingslashit($lang['domain']);
        }
    } elseif (is_search()) {
        // 搜尋頁
        $search_query = get_search_query();
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $urls[$lang['code']] = trailingslashit($lang['domain']) . '?s=' . urlencode($search_query);
        }
    } elseif (is_archive()) {
        // 其他 archive 頁面
        $current_url = hreflang_get_current_url();
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            // 簡單邏輯：使用當前路徑
            $urls[$lang['code']] = trailingslashit($lang['domain']) . ltrim(parse_url($current_url, PHP_URL_PATH), '/');
        }
    }
    
    // 允許過濾器修改 URL 列表
    return apply_filters('hreflang_alternate_urls', $urls, get_queried_object());
}

/**
 * 取得當前完整 URL
 * 
 * @return string
 */
function hreflang_get_current_url() {
    global $wp;
    return home_url(add_query_arg([], $wp->request));
}

/**
 * 檢查文章或 term 是否缺少對應語言 URL
 * 
 * @param int    $object_id   物件 ID
 * @param string $object_type 'post' 或 'term'
 * @return array 缺少的語言代碼陣列
 */
function hreflang_get_missing_language_urls($object_id, $object_type = 'post') {
    $languages = hreflang_get_languages();
    $missing = [];
    
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        
        if ($object_type === 'post') {
            $meta_key = 'alt_' . $lang['code'] . '_url';
            $value = get_post_meta($object_id, $meta_key, true);
        } else {
            $meta_key = 'term_alt_' . $lang['code'] . '_url';
            $value = get_term_meta($object_id, $meta_key, true);
        }
        
        if (empty($value)) {
            $missing[] = [
                'code' => $lang['code'],
                'label' => $lang['label'],
                'meta_key' => $meta_key
            ];
        }
    }
    
    return $missing;
}

/**
 * 取得預設語言代碼
 * 
 * @return string
 */
function hreflang_get_default_language() {
    $default = get_option('hreflang_default_lang', 'en');
    return apply_filters('hreflang_default_language', $default);
}
