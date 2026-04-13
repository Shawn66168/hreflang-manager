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
        'style' => 'dropdown',
    ], $atts, 'hreflang_switcher');

    // 取得替代語言 URL（不包含當前語言）
    $alternate_urls = hreflang_get_alt_urls_for_current();

    if (empty($alternate_urls)) {
        return '';
    }

    $current_lang  = hreflang_detect_current_language();
    $current_label = hreflang_get_language_label($current_lang);

    ob_start();

    if ($atts['style'] === 'dropdown') {
        // 下拉選單樣式
        $wrapper_class = 'pww-navlang';
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>">
            <button type="button" class="pww-navlang__btn"><?php echo esc_html($current_label); ?> &#9660;</button>
            <ul class="pww-navlang__menu" aria-hidden="true">
                <?php foreach ($alternate_urls as $lang_code => $url) : ?>
                    <li>
                        <a hreflang="<?php echo esc_attr($lang_code); ?>"
                           href="<?php echo esc_url(hreflang_normalize_url($url)); ?>">
                            <?php echo esc_html(hreflang_get_language_label($lang_code)); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <script>
        (function () {
            try {
                var script  = document.currentScript;
                if (!script) return;
                var wrapper = script.previousElementSibling;
                if (!wrapper || !wrapper.classList.contains('pww-navlang')) return;

                var btn  = wrapper.querySelector('.pww-navlang__btn');
                var menu = wrapper.querySelector('.pww-navlang__menu');
                if (!btn || !menu) return;

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var isOpen = menu.classList.toggle('is-open');
                    menu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                });

                document.addEventListener('click', function (e) {
                    if (!wrapper.contains(e.target)) {
                        menu.classList.remove('is-open');
                        menu.setAttribute('aria-hidden', 'true');
                    }
                });
            } catch (e) {}
        }());
        </script>
        <?php
    } else {
        // 清單樣式
        $wrapper_class = 'hreflang-lang-switcher hreflang-list';
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }

        echo '<ul class="' . esc_attr($wrapper_class) . '">';

        // 加入當前語言（active）
        echo '<li class="hreflang-lang-item active">';
        printf(
            '<span class="hreflang-lang-link current">%s</span>',
            esc_html($current_label)
        );
        echo '</li>';

        // 其他語言
        foreach ($alternate_urls as $lang_code => $url) {
            echo '<li class="hreflang-lang-item">';
            printf(
                '<a href="%s" class="hreflang-lang-link" hreflang="%s">%s</a>',
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
 * 載入語言切換器的 CSS（inline 注入）
 */
function hreflang_enqueue_switcher_styles() {
    wp_register_style('hreflang-inline-base', false);
    wp_enqueue_style('hreflang-inline-base');

    $css  = '.pww-navlang{position:relative}';
    $css .= '.pww-navlang__btn{cursor:pointer;padding:.5rem 1rem;border:1px solid #e5e5e5;border-radius:.5rem;background:#fff;font-size:14px}';
    $css .= '.pww-navlang__btn:hover{background:#f9f9f9}';
    $css .= '.pww-navlang__menu{display:none;position:absolute;right:0;z-index:9999;background:#fff;border:1px solid #e5e5e5;border-radius:.5rem;padding:.25rem 0;min-width:10rem;margin-top:.25rem}';
    $css .= '.pww-navlang__menu.is-open{display:block}';
    $css .= '.pww-navlang__menu li{list-style:none;margin:0}';
    $css .= '.pww-navlang__menu a{display:block;padding:.5rem .75rem;text-decoration:none;color:#333}';
    $css .= '.pww-navlang__menu a:hover{background:rgba(0,0,0,.04)}';

    // 清單樣式
    $css .= '.hreflang-lang-switcher.hreflang-list{list-style:none;margin:10px 0;padding:0;display:flex;flex-wrap:wrap;gap:10px}';
    $css .= '.hreflang-lang-item{display:inline-block}';
    $css .= '.hreflang-lang-link{display:inline-block;padding:8px 16px;border:1px solid #ddd;border-radius:4px;background-color:#fff;color:#333;text-decoration:none;font-size:14px;transition:all .3s ease}';
    $css .= '.hreflang-lang-link:hover{background-color:#f5f5f5;border-color:#999;color:#000}';
    $css .= '.hreflang-lang-item.active .hreflang-lang-link{background-color:#0073aa;border-color:#0073aa;color:#fff;font-weight:bold}';

    wp_add_inline_style('hreflang-inline-base', $css);
}
add_action('wp_enqueue_scripts', 'hreflang_enqueue_switcher_styles', 20);