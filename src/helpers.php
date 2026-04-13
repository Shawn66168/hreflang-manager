<?php
/**
 * Helper functions for Hreflang Manager Plugin
 * 
 * @package Hreflang_Manager
 */

// 憒??湔閮芸?甇斗?獢????
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ??憭?閮剖???閮皜
 * 
 * @return array 隤????
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
            'label' => '蝜?銝剜?',
            'active' => true,
            'order' => 2
        ]
    ];
    
    $languages = get_option('hreflang_languages', $default_languages);
    
    // ?迂?蕪?其耨?寡?閮皜
    return apply_filters('hreflang_languages', $languages);
}

/**
 * ???嗅??????閮撠? URL
 * 
 * @return array 隤?隞?Ⅳ => URL ?????
 */
function hreflang_get_alternate_urls() {
    $urls = [];
    $languages = hreflang_get_languages();
    
    // ?斗?嗅??憿?
    if (is_singular()) {
        // ??????
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
        // ????蝐斗??芸?蝢拙?憿?
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
        // 擐?
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $urls[$lang['code']] = trailingslashit($lang['domain']);
        }
    } elseif (is_search()) {
        // ????
        $search_query = get_search_query();
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $urls[$lang['code']] = trailingslashit($lang['domain']) . '?s=' . urlencode($search_query);
        }
    } elseif (is_archive()) {
        // ?嗡? archive ?
        $current_url = hreflang_get_current_url();
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            // 蝪∪?摩嚗蝙?函?楝敺?
            $urls[$lang['code']] = trailingslashit($lang['domain']) . ltrim(parse_url($current_url, PHP_URL_PATH), '/');
        }
    }
    
    // ?迂?蕪?其耨??URL ?”
    return apply_filters('hreflang_alternate_urls', $urls, get_queried_object());
}

/**
 * 甇????URL嚗Ⅱ靽?https?railing slash嚗?
 * 靽? query string ??fragment
 * 
 * @param string $url
 * @return string
 */
function hreflang_normalize_url($url) {
    if (!$url) return '';
    
    // 蝣箔?雿輻 https
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
    
    // 蝣箔? path 隞?/ ?銝衣?撠?
    $path = '/' . ltrim($path, '/');
    $path = trailingslashit($path);
    
    return $scheme . '://' . $host . $path . $query . $fragment;
}

/**
 * ???嗅????canonical URL嚗?隡?canonical tag ??頛荔?
 * ?舀????撠rchive 蝑?蝔桅??ａ???
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
        // 銝蝙??urlencode嚗dd_query_arg ????
        $url = add_query_arg('s', get_search_query(), home_url('/'));
    } else {
        global $wp;
        $request = isset($wp->request) ? $wp->request : '';
        $url = home_url($request ? "/$request/" : '/');
    }
    
    // ????嚗??銴?/page/N/嚗?
    $paged = get_query_var('paged') ?: get_query_var('page');
    if ($paged && $paged > 1) {
        $normalized = hreflang_normalize_url($url);
        $parsed = wp_parse_url($normalized);
        $path = $parsed['path'] ?? '/';
        
        // ?芸頝臬?銝剝?瘝? /page/N/ ???
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
 * 瑼Ｘ????term ?臬蝻箏?撠?隤? URL
 * 
 * @param int    $object_id   ?拐辣 ID
 * @param string $object_type 'post' ??'term'
 * @return array 蝻箏???閮隞?Ⅳ???
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
 * ?斗?拙?URL ?臬?箏?銝?
 * 瘥? host ??path嚗蕭??query/fragment嚗?
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
    
    // 瘥? host
    $same_host = isset($parsed1['host'], $parsed2['host']) && 
                 strtolower($parsed1['host']) === strtolower($parsed2['host']);
    
    // 瘥? path嚗宏??trailing slash ??頛?
    $path1 = isset($parsed1['path']) ? rtrim($parsed1['path'], '/') : '/';
    $path2 = isset($parsed2['path']) ? rtrim($parsed2['path'], '/') : '/';
    
    return $same_host && $path1 === $path2;
}

/**
 * ?蕪?格? URL嚗??方撌梁???????ｇ?
 * 
 * @param array $targets 隤?隞?Ⅳ => URL ???
 * @return array ?蕪敺????
 */
function hreflang_filter_targets($targets) {
    if (empty($targets)) return [];
    
    $filtered = [];
    $self_host = strtolower(parse_url(home_url(), PHP_URL_HOST));
    
    foreach ($targets as $lang => $url) {
        if (!$url) continue;
        
        $url_host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        
        // ?????
        if ($url_host === $self_host) continue;
        
        // ??詨??
        if (hreflang_is_same_page($url)) continue;
        
        $filtered[$lang] = $url;
    }
    
    return $filtered;
}

/**
 * ?寞??嗅????芸??菜葫隤?
 * 
 * @return string 隤?隞?Ⅳ
 */
function hreflang_detect_current_language() {
    $current_host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $languages = hreflang_get_languages();
    
    // ?寞????寥?
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        
        $lang_host = strtolower(parse_url($lang['domain'], PHP_URL_HOST) ?: '');
        if ($lang_host === $current_host) {
            return $lang['code'];
        }
    }
    
    // Fallback ?圈?閮剛?閮
    return hreflang_get_default_language();
}

/**
 * ??隤?憿舐內?迂
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
 * ???身隤?隞?Ⅳ
 * 
 * @return string
 */
function hreflang_get_default_language() {
    $default = get_option('hreflang_default_lang', 'en');
    return apply_filters('hreflang_default_language', $default);
}
