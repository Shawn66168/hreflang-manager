<?php
/**
 * Hreflang Core - Output hreflang tags
 */
defined('ABSPATH') || exit;

/**
 * 初始化 hreflang 輸出
 */
function hreflang_init() {
    add_action('wp_head', 'hreflang_output_hreflang', 1);
}
add_action('init', 'hreflang_init');

/**
 * 在 <head> 中輸出 hreflang 標籤
 */
function hreflang_output_hreflang() {
    // 取得所有語言對應 URL
    $alternate_urls = hreflang_get_alternate_urls();
    
    if (empty($alternate_urls)) {
        return;
    }
    
    $languages = hreflang_get_languages();
    $default_lang = hreflang_get_default_language();
    
    echo "\n<!-- Hreflang Manager Hreflang -->\n";
    
    // 輸出每個語言的 hreflang
    foreach ($alternate_urls as $lang_code => $url) {
        if (!empty($url)) {
            printf(
                '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
                esc_attr($lang_code),
                esc_url($url)
            );
        }
    }
    
    // 如果是首頁，輸出 x-default
    if (is_home() || is_front_page()) {
        $default_url = '';
        foreach ($languages as $lang) {
            if ($lang['code'] === $default_lang && $lang['active']) {
                $default_url = trailingslashit($lang['domain']);
                break;
            }
        }
        
        if (!empty($default_url)) {
            printf(
                '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
                esc_url($default_url)
            );
        }
    }
    
    echo "<!-- /Hreflang Manager Hreflang -->\n\n";
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
