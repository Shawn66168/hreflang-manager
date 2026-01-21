<?php
/**
 * Hreflang Core - Output hreflang tags
 * 
 * @package Hreflang_Manager
 */

// 如果直接訪問此檔案則退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 初始化 hreflang 輸出
 * 使用較低的優先級確保在其他 SEO 外掛之後執行
 */
function hreflang_init() {
    // 優先級設為 1，確保盡早輸出到 head
    add_action('wp_head', 'hreflang_output_hreflang', 1);
}
add_action('init', 'hreflang_init');

/**
 * 在 <head> 中輸出 hreflang 標籤
 * 根據原始 Portwell Snippet 的邏輯設計
 */
function hreflang_output_hreflang() {
    // 允許透過過濾器停用輸出（例如在特定頁面）
    if (!apply_filters('hreflang_manager_enable_output', true)) {
        return;
    }
    
    // 偵測當前站點語言
    $current_lang = hreflang_detect_current_language();
    
    // 取得當前頁面 URL
    $current_url = hreflang_get_current_url();
    
    if (!$current_url) {
        return;
    }
    
    echo "\n<!-- Hreflang Manager -->\n";
    
    // 1. 輸出當前頁面自己的 hreflang
    printf(
        '<link rel="alternate" hreflang="%s" href="%s" />'."\n",
        esc_attr($current_lang),
        esc_url($current_url)
    );
    
    // 2. 輸出 x-default（只在預設語言的首頁）
    $default_lang = hreflang_get_default_language();
    if ($current_lang === $default_lang && (is_front_page() || is_home())) {
        printf(
            '<link rel="alternate" hreflang="x-default" href="%s" />'."\n",
            esc_url(hreflang_normalize_url(home_url('/')))
        );
    }
    
    // 3. 輸出其他語言的 hreflang
    $alternate_urls = hreflang_get_alt_urls_for_current();
    
    foreach ($alternate_urls as $lang_code => $url) {
        if (!empty($url)) {
            printf(
                '<link rel="alternate" hreflang="%s" href="%s" />'."\n",
                esc_attr($lang_code),
                esc_url(hreflang_normalize_url($url))
            );
        }
    }
    
    echo "<!-- /Hreflang Manager -->\n\n";
}

/**
 * 檢查是否應該移除其他外掛的 hreflang 輸出
 * 避免重複輸出造成 SEO 問題
 */
function hreflang_manager_remove_conflicting_hreflang() {
    // 移除 Yoast SEO Premium 的 hreflang（如果存在）
    if (has_filter('wpseo_hreflang_url')) {
        remove_all_filters('wpseo_hreflang_url');
    }
}
add_action('template_redirect', 'hreflang_manager_remove_conflicting_hreflang', 1);

/**
 * 取得當前頁面的所有語言對應 URL
 * 根據原始 Portwell Snippet 的邏輯設計
 * 
 * @return array 語言代碼 => URL 的對應陣列（不包含自己）
 */
function hreflang_get_alt_urls_for_current() {
    $current_lang = hreflang_detect_current_language();
    $languages = hreflang_get_languages();
    $urls = [];
    
    // 根據語言建立 meta key 對應（相容舊的 Portwell 命名）
    $lang_meta_map = [];
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        // 支援多種 meta key 格式
        $lang_meta_map[$lang['code']] = [
            'post' => 'alt_' . $lang['code'] . '_url',
            'term' => 'term_alt_' . $lang['code'] . '_url',
        ];
    }
    
    if (is_singular()) {
        // 文章或頁面
        $post_id = get_the_ID();
        
        // 取得所有語言的 URL
        foreach ($lang_meta_map as $code => $keys) {
            $url = get_post_meta($post_id, $keys['post'], true);
            if ($url) {
                $urls[$code] = $url;
            }
        }
        
    } elseif (is_category() || is_tag() || is_tax()) {
        // 支援所有分類頁面（部落格分類/標籤 + 自訂分類 + WooCommerce 分類）
        $term = get_queried_object();
        if ($term && !is_wp_error($term) && !empty($term->term_id)) {
            foreach ($lang_meta_map as $code => $keys) {
                $url = get_term_meta($term->term_id, $keys['term'], true);
                if ($url) {
                    $urls[$code] = $url;
                }
            }
        }
        
    } else {
        // Fallback：非單一頁面，沒有對應資料時轉到首頁
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $urls[$lang['code']] = trailingslashit($lang['domain']);
        }
    }
    
    // 移除當前語言（不輸出自己）
    if (isset($urls[$current_lang])) {
        unset($urls[$current_lang]);
    }
    
    // 使用 filter 過濾（排除同域名和相同頁面）
    $urls = hreflang_filter_targets($urls);
    
    // 允許過濾器修改 URL 列表
    return apply_filters('hreflang_alternate_urls', $urls, get_queried_object());
}

/**
 * 在文章編輯頁面加入 ACF 欄位（如果使用 ACF）
 * 此函數為示例，實際使用時需安裝並啟用 ACF 外掛
 */
function hreflang_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    $languages = hreflang_get_languages();
    $fields = [];
    
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        
        $fields[] = [
            'key' => 'field_alt_' . $lang['code'] . '_url',
            'label' => $lang['label'] . ' URL',
            'name' => 'alt_' . $lang['code'] . '_url',
            'type' => 'url',
            'instructions' => '輸入 ' . $lang['label'] . ' 版本的對應 URL',
            'placeholder' => 'https://' . $lang['domain'] . '/...',
        ];
    }
    
    if (!empty($fields)) {
        acf_add_local_field_group([
            'key' => 'group_hreflang',
            'title' => 'Hreflang 多語言 URL',
            'fields' => $fields,
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ],
                ],
            ],
            'menu_order' => 20,
            'position' => 'side',
            'style' => 'default',
        ]);
    }
}
add_action('acf/init', 'hreflang_register_acf_fields');

/**
 * 在分類編輯頁面加入 term meta 欄位
 */
function hreflang_add_term_meta_fields($term) {
    $languages = hreflang_get_languages();
    
    echo '<tr class="form-field">';
    echo '<th scope="row"><strong>Hreflang 多語言 URL</strong></th>';
    echo '<td>';
    
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        
        $meta_key = 'term_alt_' . $lang['code'] . '_url';
        $value = get_term_meta($term->term_id, $meta_key, true);
        
        echo '<p>';
        printf(
            '<label for="%s">%s URL:</label><br>',
            esc_attr($meta_key),
            esc_html($lang['label'])
        );
        printf(
            '<input type="url" id="%s" name="%s" value="%s" class="regular-text" placeholder="https://%s/..." />',
            esc_attr($meta_key),
            esc_attr($meta_key),
            esc_attr($value),
            esc_attr($lang['domain'])
        );
        echo '</p>';
    }
    
    echo '</td>';
    echo '</tr>';
}

/**
 * 儲存 term meta
 */
function hreflang_save_term_meta_fields($term_id) {
    $languages = hreflang_get_languages();
    
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        
        $meta_key = 'term_alt_' . $lang['code'] . '_url';
        
        if (isset($_POST[$meta_key])) {
            $value = sanitize_text_field($_POST[$meta_key]);
            update_term_meta($term_id, $meta_key, $value);
        }
    }
}

// 註冊 term meta 欄位到常見的分類
$taxonomies = ['category', 'post_tag', 'product_cat'];
foreach ($taxonomies as $taxonomy) {
    add_action($taxonomy . '_edit_form_fields', 'hreflang_add_term_meta_fields');
    add_action('edited_' . $taxonomy, 'hreflang_save_term_meta_fields');
}
