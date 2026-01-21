<?php
/**
 * Language Switcher Shortcode
 * 
 * @package Hreflang_Manager
 */

// 如果直接訪問此檔案則退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 註冊語言切換短碼
 */
function hreflang_register_shortcode() {
    add_shortcode('hreflang_switcher', 'hreflang_switcher_shortcode');
}
add_action('init', 'hreflang_register_shortcode');

/**
 * 語言切換短碼處理函數
 * 根據原始 Portwell Snippet 設計，支援下拉選單與 inline JavaScript
 * 
 * 使用方式: 
 * - [hreflang_switcher style="dropdown"] - 下拉選單（預設）
 * - [hreflang_switcher style="list"] - 清單樣式
 * 
 * @param array $atts 短碼屬性
 * @return string HTML 輸出
 */
function hreflang_switcher_shortcode($atts) {
    $atts = shortcode_atts([
        'class' => '',
        'style' => 'dropdown', // dropdown, list
    ], $atts, 'hreflang_switcher');
    
    // 取得替代語言 URL（不包含當前語言）
    $alternate_urls = hreflang_get_alt_urls_for_current();
    
    if (empty($alternate_urls)) {
        return '';
    }
    
    $current_lang = hreflang_detect_current_language();
    $current_label = hreflang_get_language_label($current_lang);
    
    ob_start();
    
    if ($atts['style'] === 'dropdown') {\n        // 下拉選單樣式（使用原始 Snippet 的 HTML 結構和 JS）\n        $wrapper_class = 'pww-navlang';\n        if (!empty($atts['class'])) {\n            $wrapper_class .= ' ' . esc_attr($atts['class']);\n        }\n        ?>\n        <div class=\"<?php echo esc_attr($wrapper_class); ?>\">\n            <button type=\"button\" class=\"pww-navlang__btn\"><?php echo esc_html($current_label); ?> ▾</button>\n            <ul class=\"pww-navlang__menu\" aria-hidden=\"true\">\n                <?php foreach ($alternate_urls as $lang_code => $url): ?>\n                    <li>\n                        <a hreflang=\"<?php echo esc_attr($lang_code); ?>\"\n                           href=\"<?php echo esc_url(hreflang_normalize_url($url)); ?>\">\n                            <?php echo esc_html(hreflang_get_language_label($lang_code)); ?>\n                        </a>\n                    </li>\n                <?php endforeach; ?>\n            </ul>\n        </div>\n        <script>\n        (function(){\n            try {\n                var script = document.currentScript;\n                if(!script) return;\n                var wrapper = script.previousElementSibling;\n                if(!wrapper || !wrapper.classList.contains('pww-navlang')) return;\n                \n                var btn = wrapper.querySelector('.pww-navlang__btn');\n                var menu = wrapper.querySelector('.pww-navlang__menu');\n                if(!btn || !menu) return;\n                \n                btn.addEventListener('click', function(e){\n                    e.preventDefault();\n                    var isOpen = menu.classList.toggle('is-open');\n                    menu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');\n                });\n                \n                document.addEventListener('click', function(e){\n                    if(!wrapper.contains(e.target)){\n                        menu.classList.remove('is-open');\n                        menu.setAttribute('aria-hidden', 'true');\n                    }\n                });\n            } catch(e) {}\n        })();\n        </script>\n        <?php
        
    } else {
        // 清單樣式
        $wrapper_class = 'hreflang-lang-switcher hreflang-list';
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }
        
        echo '<ul class=\"' . esc_attr($wrapper_class) . '\">';
        
        // 加入當前語言（active）
        echo '<li class=\"hreflang-lang-item active\">';
        printf(
            '<span class=\"hreflang-lang-link current\">%s</span>',
            esc_html($current_label)
        );
        echo '</li>';
        
        // 其他語言
        foreach ($alternate_urls as $lang_code => $url) {
            echo '<li class=\"hreflang-lang-item\">';
            printf(
                '<a href=\"%s\" class=\"hreflang-lang-link\" hreflang=\"%s\">%s</a>',
                esc_url(hreflang_normalize_url($url)),
                esc_attr($lang_code),
                esc_html(hreflang_get_language_label($lang_code))
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
 * 使用 inline CSS 注入，類似原始 Snippet 的做法
 */
function hreflang_enqueue_switcher_styles() {
    // 註冊一個假的樣式表以便加入 inline CSS
    wp_register_style('hreflang-inline-base', false);
    wp_enqueue_style('hreflang-inline-base');
    \n    // Inline CSS（來自原始 Portwell Snippet）\n    $css = '.pww-navlang{position:relative}';\n    $css .= '.pww-navlang__btn{cursor:pointer;padding:.5rem 1rem;border:1px solid #e5e5e5;border-radius:.5rem;background:#fff;font-size:14px}';\n    $css .= '.pww-navlang__btn:hover{background:#f9f9f9}';\n    $css .= '.pww-navlang__menu{display:none;position:absolute;right:0;z-index:9999;background:#fff;border:1px solid #e5e5e5;border-radius:.5rem;padding:.25rem 0;min-width:10rem;margin-top:.25rem}';\n    $css .= '.pww-navlang__menu.is-open{display:block}';\n    $css .= '.pww-navlang__menu li{list-style:none;margin:0}';\n    $css .= '.pww-navlang__menu a{display:block;padding:.5rem .75rem;text-decoration:none;color:#333}';\n    $css .= '.pww-navlang__menu a:hover{background:rgba(0,0,0,.04)}';\n    \n    // 清單樣式（保留原有）\n    $css .= '.hreflang-lang-switcher.hreflang-list{list-style:none;margin:10px 0;padding:0;display:flex;flex-wrap:wrap;gap:10px}';\n    $css .= '.hreflang-lang-item{display:inline-block}';\n    $css .= '.hreflang-lang-link{display:inline-block;padding:8px 16px;border:1px solid #ddd;border-radius:4px;background-color:#fff;color:#333;text-decoration:none;font-size:14px;transition:all 0.3s ease}';\n    $css .= '.hreflang-lang-link:hover{background-color:#f5f5f5;border-color:#999;color:#000}';\n    $css .= '.hreflang-lang-item.active .hreflang-lang-link{background-color:#0073aa;border-color:#0073aa;color:#fff;font-weight:bold}';\n    \n    wp_add_inline_style('hreflang-inline-base', $css);\n}
add_action('wp_enqueue_scripts', 'hreflang_enqueue_switcher_styles', 20);
