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
 * - [hreflang_switcher] - 預設外觀
 * - [hreflang_switcher theme="dark-header"] - 使用指定外觀組別
 * - [hreflang_switcher style="list"] - 清單樣式
 *
 * @param array $atts 短碼屬性
 * @return string HTML 輸出
 */
function hreflang_switcher_shortcode($atts) {
    $atts = shortcode_atts([
        'class' => '',
        'style' => 'dropdown',
        'theme' => 'default',
    ], $atts, 'hreflang_switcher');

    // 清理 theme 名稱，只允許英數字、dash、underscore
    $theme = preg_replace('/[^a-z0-9\-_]/', '', strtolower(sanitize_key($atts['theme'])));
    if (empty($theme)) $theme = 'default';

    $languages    = hreflang_get_languages();
    $current_lang = hreflang_detect_current_language();
    $post_id      = is_singular() ? get_the_ID() : 0;

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
            'label'    => $lang['label'],
            'url'      => hreflang_normalize_url($url),
            'hreflang' => hreflang_get_hreflang_code($lang),
        ];
    }

    $other_langs = $lang_data;
    unset($other_langs[$current_lang]);

    if (empty($other_langs)) {
        return '';
    }

    $current_label = isset($lang_data[$current_lang])
        ? $lang_data[$current_lang]['label']
        : strtoupper($current_lang);

    $uid = 'hrlsw-' . wp_unique_id();

    // 決定 wrapper class
    // non-default theme 加上 hrl-theme-{name} 讓 scoped CSS 生效
    ob_start();

    if ($atts['style'] === 'dropdown') {
        $wrapper_class = 'pww-navlang';
        if ($theme !== 'default') {
            $wrapper_class .= ' hrl-theme-' . esc_attr($theme);
        }
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="<?php echo esc_attr($wrapper_class); ?>">
            <button type="button" class="pww-navlang__btn"><?php echo esc_html($current_label); ?> &#9660;</button>
            <ul class="pww-navlang__menu" aria-hidden="true">
                <?php foreach ($other_langs as $lang_code => $data) : ?>
                    <li>
                                <a hreflang="<?php echo esc_attr($data['hreflang']); ?>"
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
        $wrapper_class = 'hreflang-lang-switcher hreflang-list';
        if ($theme !== 'default') {
            $wrapper_class .= ' hrl-theme-' . esc_attr($theme);
        }
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }

        echo '<ul class="' . esc_attr($wrapper_class) . '">';

        echo '<li class="hreflang-lang-item active">';
        printf('<span class="hreflang-lang-link current">%s</span>', esc_html($current_label));
        echo '</li>';

        foreach ($other_langs as $lang_code => $data) {
            echo '<li class="hreflang-lang-item">';
            printf(
                '<a href="%s" class="hreflang-lang-link" hreflang="%s">%s</a>',
                esc_url($data['url']),
                esc_attr($data['hreflang']),
                esc_html($data['label'])
            );
            echo '</li>';
        }

        echo '</ul>';
    }

    return ob_get_clean();
}

/**
 * 取得所有已設定的外觀組別名稱
 *
 * @return string[]
 */
function hreflang_get_style_themes() {
    $themes = get_option('hreflang_style_themes', ['default']);
    if (!is_array($themes) || empty($themes)) {
        return ['default'];
    }
    return array_values(array_unique($themes));
}

/**
 * 取得指定組別的外觀設定（含預設值）
 *
 * @param string $theme 組別名稱
 * @return array
 */
function hreflang_get_switcher_styles($theme = 'default') {
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
    $key   = 'hreflang_switcher_styles_' . sanitize_key($theme);
    $saved = get_option($key, []);
    // 向下相容：若 theme=default 新 key 空白，嘗試讀舊 key
    if (empty($saved) && $theme === 'default') {
        $saved = get_option('hreflang_switcher_styles', []);
    }
    return wp_parse_args(is_array($saved) ? $saved : [], $defaults);
}

/**
 * 為單一組別產生 scoped CSS 字串
 *
 * @param string $theme  組別名稱
 * @param string $prefix CSS selector prefix（空字串=不加 scope）
 * @return string
 */
function hreflang_build_theme_css($theme, $prefix = '') {
    $s = hreflang_get_switcher_styles($theme);
    foreach ($s as $k => $v) {
        $s[$k] = preg_replace('/[^a-zA-Z0-9#.,()%\-_ \/]/', '', (string) $v);
    }
    $r = $s['btn_radius'];
    $f = $s['font_size'];
    $p = $prefix ? $prefix . ' ' : '';

    $css  = "{$p}.pww-navlang{position:relative;display:inline-block}";
    $css .= "{$p}.pww-navlang__btn{cursor:pointer;padding:.5rem 1rem;border:1px solid {$s['btn_border']};border-radius:{$r};background:{$s['btn_bg']};color:{$s['btn_color']};font-size:{$f}}";
    $css .= "{$p}.pww-navlang__btn:hover{background:{$s['hover_bg']}}";
    $css .= "{$p}.pww-navlang__menu{display:none;position:absolute;right:0;z-index:9999;background:{$s['menu_bg']};border:1px solid {$s['menu_border']};border-radius:{$r};padding:.25rem 0;min-width:10rem;margin-top:.25rem;list-style:none}";
    $css .= "{$p}.pww-navlang__menu.is-open{display:block}";
    $css .= "{$p}.pww-navlang__menu li{list-style:none;margin:0;padding:0}";
    $css .= "{$p}.pww-navlang__menu a{display:block;padding:.5rem .75rem;text-decoration:none;color:{$s['link_color']};font-size:{$f}}";
    $css .= "{$p}.pww-navlang__menu a:hover{background:{$s['hover_bg']}}";
    $css .= "{$p}.hreflang-lang-switcher.hreflang-list{list-style:none;margin:10px 0;padding:0;display:flex;flex-wrap:wrap;gap:10px}";
    $css .= "{$p}.hreflang-lang-item{display:inline-block}";
    $css .= "{$p}.hreflang-lang-link{display:inline-block;padding:8px 16px;border:1px solid {$s['btn_border']};border-radius:{$r};background-color:{$s['btn_bg']};color:{$s['btn_color']};text-decoration:none;font-size:{$f};transition:all .3s ease}";
    $css .= "{$p}.hreflang-lang-link:hover{background-color:{$s['hover_bg']};border-color:#999}";
    $css .= "{$p}.hreflang-lang-item.active .hreflang-lang-link{background-color:{$s['active_bg']};border-color:{$s['active_border']};color:{$s['active_color']};font-weight:bold}";
    return $css;
}

/**
 * 載入所有組別的 CSS（inline 注入）
 * default 組不加 scope；其他組以 .hrl-theme-{name} 為 scope
 */
function hreflang_enqueue_switcher_styles() {
    wp_register_style('hreflang-inline-base', false);
    wp_enqueue_style('hreflang-inline-base');

    $css = hreflang_build_theme_css('default');

    foreach (hreflang_get_style_themes() as $theme) {
        if ($theme === 'default') continue;
        $scope = '.hrl-theme-' . sanitize_html_class($theme);
        $css  .= hreflang_build_theme_css($theme, $scope);
    }

    wp_add_inline_style('hreflang-inline-base', $css);
}
add_action('wp_enqueue_scripts', 'hreflang_enqueue_switcher_styles', 20);