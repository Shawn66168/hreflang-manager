<?php
/**
 * Hreflang Core - Output hreflang tags
 * 
 * @package Hreflang_Manager
 */

// 憒??湔閮芸?甇斗?獢????
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ????hreflang 頛詨
 * 雿輻頛????蝣箔??典隞?SEO 憭?銋??瑁?
 */
function hreflang_init() {
    // ?芸?蝝身??1嚗Ⅱ靽?抵撓?箏 head
    add_action('wp_head', 'hreflang_output_hreflang', 1);
}
add_action('init', 'hreflang_init');

/**
 * ??<head> 銝剛撓??hreflang 璅惜
 * ?寞??? Portwell Snippet ??頛航身閮?
 */
function hreflang_output_hreflang() {
    // ?迂???蕪?典??刻撓?綽?靘??函摰??ｇ?
    if (!apply_filters('hreflang_manager_enable_output', true)) {
        return;
    }
    
    // ?菜葫?嗅?蝡?隤?
    $current_lang = hreflang_detect_current_language();
    
    // ???嗅?? URL
    $current_url = hreflang_get_current_url();
    
    if (!$current_url) {
        return;
    }
    
    echo "\n<!-- Hreflang Manager -->\n";
    
    // 1. 頛詨?嗅???芸楛??hreflang
    printf(
        '<link rel="alternate" hreflang="%s" href="%s" />'."\n",
        esc_attr($current_lang),
        esc_url($current_url)
    );
    
    // 2. 頛詨 x-default嚗?券?閮剛?閮????
    $default_lang = hreflang_get_default_language();
    if ($current_lang === $default_lang && (is_front_page() || is_home())) {
        printf(
            '<link rel="alternate" hreflang="x-default" href="%s" />'."\n",
            esc_url(hreflang_normalize_url(home_url('/')))
        );
    }
    
    // 3. 頛詨?嗡?隤???hreflang
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
 * 瑼Ｘ?臬?府蝘駁?嗡?憭???hreflang 頛詨
 * ?踹???頛詨?? SEO ??
 */
function hreflang_manager_remove_conflicting_hreflang() {
    // 蝘駁 Yoast SEO Premium ??hreflang嚗????剁?
    if (has_filter('wpseo_hreflang_url')) {
        remove_all_filters('wpseo_hreflang_url');
    }
}
add_action('template_redirect', 'hreflang_manager_remove_conflicting_hreflang', 1);

/**
 * ???嗅??????閮撠? URL
 * ?寞??? Portwell Snippet ??頛航身閮?
 * 
 * @return array 隤?隞?Ⅳ => URL ?????銝??怨撌梧?
 */
function hreflang_get_alt_urls_for_current() {
    $current_lang = hreflang_detect_current_language();
    $languages = hreflang_get_languages();
    $urls = [];
    
    // ?寞?隤?撱箇? meta key 撠?嚗摰寡???Portwell ?賢?嚗?
    $lang_meta_map = [];
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        // ?舀憭車 meta key ?澆?
        $lang_meta_map[$lang['code']] = [
            'post' => 'alt_' . $lang['code'] . '_url',
            'term' => 'term_alt_' . $lang['code'] . '_url',
        ];
    }
    
    if (is_singular()) {
        // ??????
        $post_id = get_the_ID();
        
        // ?????閮??URL
        foreach ($lang_meta_map as $code => $keys) {
            $url = get_post_meta($post_id, $keys['post'], true);
            if ($url) {
                $urls[$code] = $url;
            }
        }
        
    } elseif (is_category() || is_tag() || is_tax()) {
        // ?舀???憿??ｇ??刻?澆?憿?璅惜 + ?芾??? + WooCommerce ??嚗?
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
        // Fallback嚗??桐??嚗???????頧擐?
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            $urls[$lang['code']] = trailingslashit($lang['domain']);
        }
    }
    
    // 蝘駁?嗅?隤?嚗?頛詨?芸楛嚗?
    if (isset($urls[$current_lang])) {
        unset($urls[$current_lang]);
    }
    
    // 雿輻 filter ?蕪嚗??文???????ｇ?
    $urls = hreflang_filter_targets($urls);
    
    // ?迂?蕪?其耨??URL ?”
    return apply_filters('hreflang_alternate_urls', $urls, get_queried_object());
}

/**
 * ?冽?蝡楊頛舫??Ｗ???ACF 甈?嚗??蝙??ACF嚗?
 * 甇文?貊蝷箔?嚗祕?蝙?冽??摰?銝血???ACF 憭?
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
            'instructions' => '頛詨 ' . $lang['label'] . ' ?????URL',
            'placeholder' => 'https://' . $lang['domain'] . '/...',
        ];
    }
    
    if (!empty($fields)) {
        acf_add_local_field_group([
            'key' => 'group_hreflang',
            'title' => 'Hreflang 憭?閮 URL',
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
 * ?典?憿楊頛舫??Ｗ???term meta 甈?
 */
function hreflang_add_term_meta_fields($term) {
    $languages = hreflang_get_languages();
    
    echo '<tr class="form-field">';
    echo '<th scope="row"><strong>Hreflang 憭?閮 URL</strong></th>';
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
 * ?脣? term meta
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

// 閮餃? term meta 甈??啣虜閬???
$taxonomies = ['category', 'post_tag', 'product_cat'];
foreach ($taxonomies as $taxonomy) {
    add_action($taxonomy . '_edit_form_fields', 'hreflang_add_term_meta_fields');
    add_action('edited_' . $taxonomy, 'hreflang_save_term_meta_fields');
}
