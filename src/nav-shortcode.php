<?php
/**
 * Language Switcher Shortcode
 */
defined('ABSPATH') || exit;

/**
 * 註冊語言切換短碼
 */
function hreflang_register_shortcode() {
    add_shortcode('hreflang_switcher', 'hreflang_switcher_shortcode');
}
add_action('init', 'hreflang_register_shortcode');

/**
 * 語言切換短碼處理函數
 * 
 * 使用方式: [hreflang_switcher class="my-class" show_flags="yes"]
 * 
 * @param array $atts 短碼屬性
 * @return string HTML 輸出
 */
function hreflang_switcher_shortcode($atts) {
    $atts = shortcode_atts([
        'class' => '',
        'show_flags' => 'no',
        'style' => 'dropdown', // dropdown, list
    ], $atts, 'hreflang_switcher');
    
    // 取得所有語言對應 URL
    $alternate_urls = hreflang_get_alternate_urls();
    
    if (empty($alternate_urls)) {
        return '';
    }
    
    $languages = hreflang_get_languages();
    $current_lang = hreflang_detect_current_language();
    $wrapper_class = 'hreflang-lang-switcher';
    
    if (!empty($atts['class'])) {
        $wrapper_class .= ' ' . esc_attr($atts['class']);
    }
    
    ob_start();
    
    if ($atts['style'] === 'dropdown') {
        // 下拉選單樣式
        echo '<div class="' . esc_attr($wrapper_class) . ' hreflang-dropdown">';
        echo '<select class="hreflang-lang-select" onchange="if(this.value) window.location.href=this.value">';
        
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            
            $url = isset($alternate_urls[$lang['code']]) ? $alternate_urls[$lang['code']] : '';
            if (empty($url)) continue;
            
            $selected = ($lang['code'] === $current_lang) ? ' selected' : '';
            
            printf(
                '<option value="%s"%s>%s</option>',
                esc_url($url),
                $selected,
                esc_html($lang['label'])
            );
        }
        
        echo '</select>';
        echo '</div>';
        
    } else {
        // 清單樣式
        echo '<ul class="' . esc_attr($wrapper_class) . ' hreflang-list">';
        
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            
            $url = isset($alternate_urls[$lang['code']]) ? $alternate_urls[$lang['code']] : '';
            if (empty($url)) continue;
            
            $active_class = ($lang['code'] === $current_lang) ? ' active' : '';
            
            echo '<li class="hreflang-lang-item' . esc_attr($active_class) . '">';
            printf(
                '<a href="%s" class="hreflang-lang-link" hreflang="%s">%s</a>',
                esc_url($url),
                esc_attr($lang['code']),
                esc_html($lang['label'])
            );
            echo '</li>';
        }
        
        echo '</ul>';
    }
    
    return ob_get_clean();
}

/**
 * 偵測當前頁面的語言
 * 
 * @return string 語言代碼
 */
function hreflang_detect_current_language() {
    $languages = hreflang_get_languages();
    $current_domain = $_SERVER['HTTP_HOST'] ?? '';
    
    // 根據域名判斷
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;
        
        $lang_domain = parse_url($lang['domain'], PHP_URL_HOST);
        if ($lang_domain === $current_domain) {
            return $lang['code'];
        }
    }
    
    // 預設回傳第一個啟用的語言
    foreach ($languages as $lang) {
        if ($lang['active']) {
            return $lang['code'];
        }
    }
    
    return hreflang_get_default_language();
}

/**
 * 載入語言切換器的 CSS
 */
function hreflang_enqueue_switcher_styles() {
    $css_url = plugin_dir_url(dirname(__FILE__)) . 'assets/css/style.css';
    $css_path = plugin_dir_path(dirname(__FILE__)) . 'assets/css/style.css';
    
    if (file_exists($css_path)) {
        wp_enqueue_style(
            'hreflang-hreflang-switcher',
            $css_url,
            [],
            filemtime($css_path)
        );
    }
}
add_action('wp_enqueue_scripts', 'hreflang_enqueue_switcher_styles');
