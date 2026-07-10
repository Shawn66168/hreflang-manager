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
    // 404 與搜尋頁沒有對等的多語言內容，不輸出 hreflang
    if (is_404() || is_search()) {
        return;
    }

    // 允許透過過濾器停用輸出（例如在特定頁面）
    if (!apply_filters('hreflang_manager_enable_output', true)) {
        return;
    }
    
    // 偵測當前站點語言
    $current_lang = hreflang_detect_current_language();
    $current_hreflang = hreflang_get_hreflang_code($current_lang);
    
    // 取得當前頁面 URL
    $current_url = hreflang_get_current_url();
    
    if (!$current_url) {
        return;
    }

    // 沒有任何對等頁時不輸出（單獨的自身宣告沒有意義）
    $alternate_urls = hreflang_get_alt_urls_for_current();
    if (empty($alternate_urls)) {
        return;
    }

    echo "\n<!-- Hreflang Manager -->\n";

    // 1. 輸出當前頁面自己的 hreflang
    printf(
        '<link rel="alternate" hreflang="%s" href="%s" />'."\n",
        esc_attr($current_hreflang),
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
    foreach ($alternate_urls as $lang_code => $url) {
        if (!empty($url)) {
            $hreflang_code = hreflang_get_hreflang_code($lang_code);
            printf(
                '<link rel="alternate" hreflang="%s" href="%s" />'."\n",
                esc_attr($hreflang_code),
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
    
    if (is_singular()) {
        // 文章或頁面：手填 meta 優先，未填時可自動以相同路徑對應
        $post_id = get_the_ID();

        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $url = hreflang_get_post_language_url($post_id, $lang);
            if ($url) {
                $urls[$lang['code']] = $url;
            }
        }

    } elseif (is_category() || is_tag() || is_tax()) {
        // 支援所有分類頁面（部落格分類/標籤 + 自訂分類 + WooCommerce 分類）
        $term = get_queried_object();
        if ($term && !is_wp_error($term) && !empty($term->term_id)) {
            foreach ($languages as $lang) {
                if (!$lang['active']) continue;
                $url = get_term_meta($term->term_id, 'term_alt_' . $lang['code'] . '_url', true);
                if ($url) {
                    $urls[$lang['code']] = $url;
                }
            }
        }

    } elseif (is_front_page() || is_home()) {
        // 首頁：各語言首頁互為對等頁
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $urls[$lang['code']] = trailingslashit($lang['domain']);
        }
    }
    // 其他頁面（日期/作者 archive 等）沒有可靠的對等 URL，不輸出 alternate
    
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
            'type' => 'text',
            'instructions' => '輸入 ' . $lang['label'] . ' 版本的對應 URL；填「-」表示該語言無對應版本',
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
 * 註冊原生文章/頁面 metabox（未安裝 ACF 時使用）
 */
function hreflang_register_post_meta_box() {
    // 若有 ACF，已由 ACF 欄位負責 UI，避免重複顯示
    if (function_exists('acf_add_local_field_group')) {
        return;
    }

    $post_types = ['post', 'page'];
    foreach ($post_types as $post_type) {
        add_meta_box(
            'hreflang_post_urls',
            'Hreflang 多語言 URL',
            'hreflang_render_post_meta_box',
            $post_type,
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'hreflang_register_post_meta_box');

/**
 * 渲染文章/頁面 hreflang URL metabox
 *
 * @param WP_Post $post
 */
function hreflang_render_post_meta_box($post) {
    wp_nonce_field('hreflang_post_meta_nonce', 'hreflang_post_meta_nonce');

    $languages = hreflang_get_languages();
    if (empty($languages)) {
        echo '<p>尚未設定語言，請先至設定頁面新增語言。</p>';
        return;
    }

    $auto_enabled = hreflang_is_auto_same_slug_enabled();

    foreach ($languages as $lang) {
        if (empty($lang['active'])) {
            continue;
        }

        $meta_key = 'alt_' . $lang['code'] . '_url';
        $value = get_post_meta($post->ID, $meta_key, true);
        $hreflang_code = hreflang_get_hreflang_code($lang);

        // 啟用自動對應時，placeholder 顯示留空將套用的 URL
        $placeholder = 'https://' . (parse_url($lang['domain'], PHP_URL_HOST) ?: $lang['domain']) . '/...';
        if ($auto_enabled) {
            $auto_url = hreflang_build_same_slug_url($post->ID, $lang);
            if ($auto_url) {
                $placeholder = '自動：' . $auto_url;
            }
        }

        echo '<p>';
        printf(
            '<label for="%1$s"><strong>%2$s</strong> <code>[%3$s]</code></label><br>',
            esc_attr($meta_key),
            esc_html($lang['label']),
            esc_html($hreflang_code)
        );
        printf(
            '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="widefat" placeholder="%3$s" />',
            esc_attr($meta_key),
            esc_attr($value),
            esc_attr($placeholder)
        );
        echo '</p>';
    }

    if ($auto_enabled) {
        echo '<p class="description">留空＝自動以「相同路徑＋該語言網域」輸出（placeholder 所示）；手動填寫以填寫值優先；填 <code>-</code> ＝該語言無對應版本（僅本地發布，該語言不輸出 hreflang）。</p>';
    } else {
        echo '<p class="description">每篇文章/頁面可獨立設定各語系對應 URL；填 <code>-</code> 表示該語言無對應版本。</p>';
    }
}

/**
 * 儲存文章/頁面 hreflang URL metabox 資料
 *
 * @param int $post_id
 */
function hreflang_save_post_meta_fields($post_id) {
    if (!isset($_POST['hreflang_post_meta_nonce']) || !wp_verify_nonce($_POST['hreflang_post_meta_nonce'], 'hreflang_post_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $post_type = get_post_type($post_id);
    if (!in_array($post_type, ['post', 'page'], true)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $languages = hreflang_get_languages();
    foreach ($languages as $lang) {
        if (empty($lang['active'])) {
            continue;
        }

        $meta_key = 'alt_' . $lang['code'] . '_url';
        if (!isset($_POST[$meta_key])) {
            continue;
        }

        $value = trim((string) wp_unslash($_POST[$meta_key]));
        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
            continue;
        }

        // 「-」＝標記此語言無對應版本
        update_post_meta($post_id, $meta_key, $value === '-' ? '-' : esc_url_raw($value));
    }
}
add_action('save_post', 'hreflang_save_post_meta_fields');

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
