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

    $languages    = hreflang_get_languages();
    $current_lang = hreflang_detect_current_language();
    $post_id      = is_singular() ? get_the_ID() : 0;

    // 為每個語言取得 URL
    // 優先從文章 meta 讀取，否則 fallback 到該語言的 domain 根目錄
    // 不使用 hreflang_filter_targets()，避免在單域名環境下全部被過濾
    $lang_data = [];
    foreach ($languages as $lang) {
        if (!$lang['active']) continue;

        $url = '';
        if ($post_id) {
            $url = get_post_meta($post_id, 'alt_' . $lang['code'] . '_url', true);
        }
        if (empty($url)) {
            $url = trailingslashit($lang['domain']);
        }

        $lang_data[$lang['code']] = [
            'label' => $lang['label'],
            'url'   => hreflang_normalize_url($url),
        ];
    }

    // 移除當前語言（顯示在按鈕上，不放進下拉選單）
    $other_langs = $lang_data;
    unset($other_langs[$current_lang]);

    // 沒有其他語言時不輸出
    if (empty($other_langs)) {
        return '';
    }

    $current_label = isset($lang_data[$current_lang])
        ? $lang_data[$current_lang]['label']
        : strtoupper($current_lang);

    // 使用 unique ID 讓 JS 精確定位 wrapper，避免 wpautop 插入多餘標籤的影響
    $uid = 'hrlsw-' . wp_unique_id();

    ob_start();

    if ($atts['style'] === 'dropdown') {
        // 下拉選單樣式
        $wrapper_class = 'pww-navlang';
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="<?php echo esc_attr($wrapper_class); ?>">
            <button type="button" class="pww-navlang__btn"><?php echo esc_html($current_label); ?> &#9660;</button>
            <ul class="pww-navlang__menu" aria-hidden="true">
                <?php foreach ($other_langs as $lang_code => $data) : ?>
                    <li>
                        <a hreflang="<?php echo esc_attr($lang_code); ?>"
                           href="<?php echo esc_url($data['url']); ?>">
                            <?php echo esc_html($data['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <script>
        (function () {
            try {
                var wrapper = document.getElementById('<?php echo esc_js($uid); ?>');
                if (!wrapper) return;
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
        // 清單樣式 - 顯示所有語言，當前標記 active
        $wrapper_class = 'hreflang-lang-switcher hreflang-list';
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }

        echo '<ul class="' . esc_attr($wrapper_class) . '">';

        // 當前語言（active 狀態）
        echo '<li class="hreflang-lang-item active">';
        printf('<span class="hreflang-lang-link current">%s</span>', esc_html($current_label));
        echo '</li>';

        // 其他語言
        foreach ($other_langs as $lang_code => $data) {
            echo '<li class="hreflang-lang-item">';
            printf(
                '<a href="%s" class="hreflang-lang-link" hreflang="%s">%s</a>',
                esc_url($data['url']),
                esc_attr($lang_code),
                esc_html($data['label'])
            );
            echo '</li>';
        }

        echo '</ul>';
    }

    return ob_get_clean();
}

/**
 * 取得切換器外觀設定（含預設值）
 *
 * @return array
 */
function hreflang_get_switcher_styles() {
    $defaults = [
        'btn_bg'        => '#ffffff',
        'btn_color'     => '#333333',
        'btn_border'    => '#e5e5e5',
        'btn_radius'    => '0.5rem',
        'font_size'     => '14px',
        'menu_bg'       => '#ffffff',
        'menu_border'   => '#e5e5e5',
        'link_color'    => '#333333',
        'hover_bg'      => '#f9f9f9',
        'active_bg'     => '#0073aa',
        'active_color'  => '#ffffff',
        'active_border' => '#0073aa',
    ];
    $saved = get_option('hreflang_switcher_styles', []);
    return wp_parse_args(is_array($saved) ? $saved : [], $defaults);
}

/**
 * 載入語言切換器的 CSS（inline 注入，套用外觀設定）
 */
function hreflang_enqueue_switcher_styles() {
    wp_register_style('hreflang-inline-base', false);
    wp_enqueue_style('hreflang-inline-base');

    $s = hreflang_get_switcher_styles();

    // 防禦性清理：只允許 CSS 安全字元
    foreach ($s as $k => $v) {
        $s[$k] = preg_replace('/[^a-zA-Z0-9#.,()%\-_ \/]/', '', (string) $v);
    }

    $r = $s['btn_radius'];
    $f = $s['font_size'];

    $css  = '.pww-navlang{position:relative;display:inline-block}';
    $css .= ".pww-navlang__btn{cursor:pointer;padding:.5rem 1rem;border:1px solid {$s['btn_border']};border-radius:{$r};background:{$s['btn_bg']};color:{$s['btn_color']};font-size:{$f}}";
    $css .= ".pww-navlang__btn:hover{background:{$s['hover_bg']}}";
    $css .= ".pww-navlang__menu{display:none;position:absolute;right:0;z-index:9999;background:{$s['menu_bg']};border:1px solid {$s['menu_border']};border-radius:{$r};padding:.25rem 0;min-width:10rem;margin-top:.25rem;list-style:none}";
    $css .= '.pww-navlang__menu.is-open{display:block}';
    $css .= '.pww-navlang__menu li{list-style:none;margin:0;padding:0}';
    $css .= ".pww-navlang__menu a{display:block;padding:.5rem .75rem;text-decoration:none;color:{$s['link_color']};font-size:{$f}}";
    $css .= ".pww-navlang__menu a:hover{background:{$s['hover_bg']}}";

    // 清單樣式
    $css .= '.hreflang-lang-switcher.hreflang-list{list-style:none;margin:10px 0;padding:0;display:flex;flex-wrap:wrap;gap:10px}';
    $css .= '.hreflang-lang-item{display:inline-block}';
    $css .= ".hreflang-lang-link{display:inline-block;padding:8px 16px;border:1px solid {$s['btn_border']};border-radius:{$r};background-color:{$s['btn_bg']};color:{$s['btn_color']};text-decoration:none;font-size:{$f};transition:all .3s ease}";
    $css .= ".hreflang-lang-link:hover{background-color:{$s['hover_bg']};border-color:#999}";
    $css .= ".hreflang-lang-item.active .hreflang-lang-link{background-color:{$s['active_bg']};border-color:{$s['active_border']};color:{$s['active_color']};font-weight:bold}";

    wp_add_inline_style('hreflang-inline-base', $css);
}
add_action('wp_enqueue_scripts', 'hreflang_enqueue_switcher_styles', 20);