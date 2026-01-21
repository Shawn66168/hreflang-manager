<?php
/**
 * Helper functions for Hreflang Manager Plugin
 * 
 * @package Hreflang_Manager
 */

// 如果直接訪問此檔案則退出
if (!defined('ABSPATH')) {
    exit;
}

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
 * 正規化 URL（確保 https、trailing slash）
 * 保留 query string 和 fragment
 * 
 * @param string $url
 * @return string
 */
function hreflang_normalize_url($url) {
    if (!$url) return '';
    
    // 確保使用 https
    $url = set_url_scheme($url, 'https');
    
    $parsed = wp_parse_url($url);
    if (!$parsed || empty($parsed['host'])) {
        return $url; // fallback
    }
    
    $scheme = $parsed['scheme'] ?? 'https';
    $host   = $parsed['host'];
    $path   = $parsed['path'] ?? '/';
    $query  = isset($parsed['query']) ? ('?' . $parsed['query']) : '';
    $fragment = isset($parsed['fragment']) ? ('#' . $parsed['fragment']) : '';
    
    // 確保 path 以 / 開頭並結尾
    $path = '/' . ltrim($path, '/');
    $path = trailingslashit($path);
    
    return $scheme . '://' . $host . $path . $query . $fragment;
}

/**
 * 取得當前頁面的 canonical URL（類似 canonical tag 的邏輯）
 * 支援分頁、搜尋、archive 等各種頁面類型
 * 
 * @return string
 */
function hreflang_get_current_url() {
    $url = '';
    
    if (is_singular()) {
        $url = get_permalink();
    } elseif (is_front_page() || is_home()) {
        $url = home_url('/');
    } elseif (is_post_type_archive()) {
        $url = get_post_type_archive_link(get_post_type());
    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $url = (!is_wp_error($term) && $term) ? get_term_link($term) : home_url('/');
    } elseif (is_search()) {
        // 不使用 urlencode，add_query_arg 會處理
        $url = add_query_arg('s', get_search_query(), home_url('/'));
    } else {
        global $wp;
        $request = isset($wp->request) ? $wp->request : '';
        $url = home_url($request ? "/$request/" : '/');
    }
    
    // 處理分頁（避免重複 /page/N/）
    $paged = get_query_var('paged') ?: get_query_var('page');
    if ($paged && $paged > 1) {
        $normalized = hreflang_normalize_url($url);
        $parsed = wp_parse_url($normalized);
        $path = $parsed['path'] ?? '/';
        
        // 只在路徑中還沒有 /page/N/ 時才加入
        if (!preg_match('~/page/\d+/?$~', $path)) {
            $query = isset($parsed['query']) ? ('?' . $parsed['query']) : '';
            $fragment = isset($parsed['fragment']) ? ('#' . $parsed['fragment']) : '';
            $base_path = trailingslashit($path);
            $url = $parsed['scheme'] . '://' . $parsed['host'] . $base_path . 'page/' . intval($paged) . '/' . $query . $fragment;
        }
    }
    
    return hreflang_normalize_url($url);
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
 * 判斷兩個 URL 是否為同一頁面
 * 比較 host 和 path（忽略 query/fragment）
 * 
 * @param string $url1
 * @param string $url2
 * @return bool
 */
function hreflang_is_same_page($url1, $url2 = '') {
    if (!$url1) return false;
    
    if (!$url2) {
        $url2 = hreflang_get_current_url();
    }
    
    $parsed1 = wp_parse_url(hreflang_normalize_url($url1));
    $parsed2 = wp_parse_url(hreflang_normalize_url($url2));
    
    if (!$parsed1 || !$parsed2) return false;
    
    // 比較 host
    $same_host = isset($parsed1['host'], $parsed2['host']) && 
                 strtolower($parsed1['host']) === strtolower($parsed2['host']);
    
    // 比較 path（移除 trailing slash 再比較）
    $path1 = isset($parsed1['path']) ? rtrim($parsed1['path'], '/') : '/';
    $path2 = isset($parsed2['path']) ? rtrim($parsed2['path'], '/') : '/';
    
    return $same_host && $path1 === $path2;
}

/**
 * 過濾目標 URL（排除自己的域名和相同頁面）
 * 
 * @param array $targets 語言代碼 => URL 陣列
 * @return array 過濾後的陣列
 */
function hreflang_filter_targets($targets) {
    if (empty($targets)) return [];
    
    $filtered = [];
    $self_host = strtolower(parse_url(home_url(), PHP_URL_HOST));
    
    foreach ($targets as $lang => $url) {
        if (!$url) continue;
        
        $url_host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        
        // 排除同域名
        if ($url_host === $self_host) continue;
        
        // 排除相同頁面
        if (hreflang_is_same_page($url)) continue;
        
        $filtered[$lang] = $url;
    }
    
    return $filtered;
}

/**
 * 根據當前域名自動偵測語言
 * 
 * @return string 語言代碼
 */
function hreflang_detect_current_language() {
    $current_host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $languages = hreflang_get_languages();
    
    // 根據域名匹配
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        
        $lang_host = strtolower(parse_url($lang['domain'], PHP_URL_HOST) ?: '');
        if ($lang_host === $current_host) {
            return $lang['code'];
        }
    }
    
    // Fallback 到預設語言
    return hreflang_get_default_language();
}

/**
 * 取得語言顯示名稱
 * 
 * @param string $lang_code
 * @return string
 */
function hreflang_get_language_label($lang_code) {
    $languages = hreflang_get_languages();
    
    foreach ($languages as $lang) {
        if ($lang['code'] === $lang_code) {
            return $lang['label'];
        }
    }
    
    return strtoupper($lang_code);
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
