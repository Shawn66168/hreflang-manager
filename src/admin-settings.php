<?php
/**
 * Admin Settings Page - 後台設定頁面
 * 
 * @package Hreflang_Manager
 */

// 如果直接訪問此檔案則退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 註冊設定頁面
 */
function hreflang_add_settings_page() {
    add_options_page(
        'Hreflang Languages',
        'Hreflang Languages',
        'manage_options',
        'hreflang-settings',
        'hreflang_render_settings_page'
    );
}
add_action('admin_menu', 'hreflang_add_settings_page');

/**
 * 註冊設定選項
 */
function hreflang_register_settings() {
    register_setting('hreflang_options', 'hreflang_languages', [
        'sanitize_callback' => 'hreflang_sanitize_languages',
    ]);
    
    register_setting('hreflang_options', 'hreflang_default_lang', [
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}
add_action('admin_init', 'hreflang_register_settings');

/**
 * 清理語言資料
 */
function hreflang_sanitize_languages($input) {
    if (!is_array($input)) {
        return [];
    }
    
    $sanitized = [];
    
    foreach ($input as $lang) {
        if (!isset($lang['code']) || empty($lang['code'])) {
            continue;
        }
        
        $sanitized[] = [
            'code' => sanitize_text_field($lang['code']),
            'locale' => hreflang_sanitize_locale_code($lang['locale'] ?? '', $lang['code'] ?? ''),
            'domain' => esc_url_raw($lang['domain'] ?? ''),
            'label' => sanitize_text_field($lang['label'] ?? ''),
            'active' => !empty($lang['active']),
            'order' => intval($lang['order'] ?? 0),
        ];
    }
    
    // 按 order 排序
    usort($sanitized, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    return $sanitized;
}

/**
 * 取得常用全球 hreflang / locale 選單。
 *
 * @return array<string, array<string, string>>
 */
function hreflang_get_locale_catalog() {
    return [
        '全球常用' => [
            'en-US' => 'English (United States)',
            'en-GB' => 'English (United Kingdom)',
            'zh-Hant' => 'Chinese Traditional',
            'zh-Hans' => 'Chinese Simplified',
            'ja-JP' => 'Japanese (Japan)',
            'ko-KR' => 'Korean (South Korea)',
            'fr-FR' => 'French (France)',
            'de-DE' => 'German (Germany)',
            'es-ES' => 'Spanish (Spain)',
            'pt-BR' => 'Portuguese (Brazil)',
        ],
        'English' => [
            'en-US' => 'English (United States)',
            'en-GB' => 'English (United Kingdom)',
            'en-AU' => 'English (Australia)',
            'en-CA' => 'English (Canada)',
            'en-IN' => 'English (India)',
            'en-SG' => 'English (Singapore)',
            'en-NZ' => 'English (New Zealand)',
            'en-ZA' => 'English (South Africa)',
            'en-IE' => 'English (Ireland)',
        ],
        '中文 / East Asia' => [
            'zh-Hant' => 'Chinese Traditional',
            'zh-Hant-TW' => 'Chinese Traditional (Taiwan)',
            'zh-Hant-HK' => 'Chinese Traditional (Hong Kong)',
            'zh-Hans' => 'Chinese Simplified',
            'zh-Hans-CN' => 'Chinese Simplified (China)',
            'zh-Hans-SG' => 'Chinese Simplified (Singapore)',
            'ja-JP' => 'Japanese (Japan)',
            'ko-KR' => 'Korean (South Korea)',
        ],
        'Europe' => [
            'fr-FR' => 'French (France)',
            'fr-CA' => 'French (Canada)',
            'fr-CH' => 'French (Switzerland)',
            'de-DE' => 'German (Germany)',
            'de-AT' => 'German (Austria)',
            'de-CH' => 'German (Switzerland)',
            'es-ES' => 'Spanish (Spain)',
            'it-IT' => 'Italian (Italy)',
            'nl-NL' => 'Dutch (Netherlands)',
            'nl-BE' => 'Dutch (Belgium)',
            'pt-PT' => 'Portuguese (Portugal)',
            'sv-SE' => 'Swedish (Sweden)',
            'no-NO' => 'Norwegian (Norway)',
            'da-DK' => 'Danish (Denmark)',
            'fi-FI' => 'Finnish (Finland)',
            'pl-PL' => 'Polish (Poland)',
            'cs-CZ' => 'Czech (Czech Republic)',
            'sk-SK' => 'Slovak (Slovakia)',
            'hu-HU' => 'Hungarian (Hungary)',
            'ro-RO' => 'Romanian (Romania)',
            'bg-BG' => 'Bulgarian (Bulgaria)',
            'el-GR' => 'Greek (Greece)',
            'tr-TR' => 'Turkish (Turkey)',
            'uk-UA' => 'Ukrainian (Ukraine)',
            'ru-RU' => 'Russian (Russia)',
        ],
        'Americas' => [
            'en-US' => 'English (United States)',
            'en-CA' => 'English (Canada)',
            'fr-CA' => 'French (Canada)',
            'es-MX' => 'Spanish (Mexico)',
            'es-AR' => 'Spanish (Argentina)',
            'es-CL' => 'Spanish (Chile)',
            'es-CO' => 'Spanish (Colombia)',
            'es-PE' => 'Spanish (Peru)',
            'es-419' => 'Spanish (Latin America)',
            'pt-BR' => 'Portuguese (Brazil)',
        ],
        'Asia Pacific' => [
            'hi-IN' => 'Hindi (India)',
            'bn-BD' => 'Bengali (Bangladesh)',
            'ur-PK' => 'Urdu (Pakistan)',
            'th-TH' => 'Thai (Thailand)',
            'vi-VN' => 'Vietnamese (Vietnam)',
            'id-ID' => 'Indonesian (Indonesia)',
            'ms-MY' => 'Malay (Malaysia)',
            'tl-PH' => 'Filipino (Philippines)',
            'ta-IN' => 'Tamil (India)',
            'te-IN' => 'Telugu (India)',
            'mr-IN' => 'Marathi (India)',
        ],
        'Middle East / Africa' => [
            'ar' => 'Arabic',
            'ar-SA' => 'Arabic (Saudi Arabia)',
            'ar-AE' => 'Arabic (United Arab Emirates)',
            'he-IL' => 'Hebrew (Israel)',
            'fa-IR' => 'Persian (Iran)',
            'af-ZA' => 'Afrikaans (South Africa)',
            'sw-KE' => 'Swahili (Kenya)',
            'am-ET' => 'Amharic (Ethiopia)',
        ],
    ];
}

/**
 * 產生 locale / hreflang 下拉選單 HTML。
 *
 * @param string $name
 * @param string $selected
 * @return string
 */
function hreflang_get_locale_select_html($name, $selected = '') {
    $selected = hreflang_sanitize_locale_code($selected);
    $catalog = hreflang_get_locale_catalog();
    $known_codes = [];
    $html = '<select name="' . esc_attr($name) . '" class="hrl-locale-select">';
    $html .= '<option value="">請選擇 hreflang / Locale</option>';

    foreach ($catalog as $group => $items) {
        foreach (array_keys($items) as $code) {
            $known_codes[$code] = true;
        }
    }

    if (!empty($selected) && !isset($known_codes[$selected])) {
        $html .= '<option value="' . esc_attr($selected) . '" selected="selected">自訂目前值 (' . esc_html($selected) . ')</option>';
    }

    foreach ($catalog as $group => $items) {
        $html .= '<optgroup label="' . esc_attr($group) . '">';
        foreach ($items as $code => $label) {
            $html .= '<option value="' . esc_attr($code) . '" ' . selected($selected, $code, false) . '>';
            $html .= esc_html($label . ' [' . $code . ']');
            $html .= '</option>';
        }
        $html .= '</optgroup>';
    }

    $html .= '</select>';
    return $html;
}

/**
 * 是否為 design token 引用：
 * theme.json 的 --wp--preset--*，或 Elementor 全域樣式的 --e-global-*
 *
 * @param string $val
 * @return bool
 */
function hreflang_is_theme_token($val) {
    return (bool) preg_match('/^var\(--(wp|e-global)-[a-z0-9\-]+\)$/', $val);
}

/**
 * 清理切換器外觀設定
 * 顏色允許 hex、佈景主題 token（var(--wp--…)）與 inherit/currentColor/transparent；
 * 尺寸允許安全的 CSS 長度值、token 與 inherit
 */
function hreflang_sanitize_switcher_styles($input) {
    if (!is_array($input)) {
        return [];
    }
    $sanitized = [];

    $color_fields = ['btn_bg', 'btn_color', 'btn_border', 'menu_bg', 'menu_border',
                     'link_color', 'hover_bg', 'active_bg', 'active_color', 'active_border'];
    foreach ($color_fields as $field) {
        if (isset($input[$field])) {
            $val = trim(sanitize_text_field($input[$field]));
            if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
                $sanitized[$field] = strtolower($val);
            } elseif (hreflang_is_theme_token($val)
                || in_array($val, ['inherit', 'currentColor', 'transparent'], true)) {
                $sanitized[$field] = $val;
            }
        }
    }

    if (isset($input['btn_radius'])) {
        $val = trim(sanitize_text_field($input['btn_radius']));
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%)?$/', $val) || hreflang_is_theme_token($val)) {
            $sanitized['btn_radius'] = $val;
        }
    }

    if (isset($input['font_size'])) {
        $val = trim(sanitize_text_field($input['font_size']));
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|pt)?$/', $val)
            || hreflang_is_theme_token($val) || $val === 'inherit') {
            $sanitized['font_size'] = $val;
        }
    }

    if (isset($input['font_weight'])) {
        $val = trim(sanitize_text_field($input['font_weight']));
        if (preg_match('/^(normal|bold|inherit|[1-9]00)$/', $val)) {
            $sanitized['font_weight'] = $val;
        }
    }

    // padding / margin：1~4 個 CSS 長度值（如 "0.5rem 1rem"），或單一 token
    $spacing_fields = ['btn_padding', 'btn_margin', 'menu_padding', 'link_padding'];
    $length = '(\d*\.)?\d+(px|rem|em|%)?';
    foreach ($spacing_fields as $field) {
        if (isset($input[$field])) {
            $val = trim(sanitize_text_field($input[$field]));
            if (preg_match('/^' . $length . '(\s+' . $length . '){0,3}$/', $val)
                || hreflang_is_theme_token($val)) {
                $sanitized[$field] = $val;
            }
        }
    }

    return $sanitized;
}

/**
 * 佈景主題色票下拉 HTML（帶入 var(--wp--preset--color--…) token）
 * 非 block theme（無調色盤）時回傳空字串
 *
 * @return string
 */
function hreflang_get_theme_palette_select_html() {
    if (!function_exists('wp_get_global_settings')) {
        return '';
    }

    $palette = wp_get_global_settings(['color', 'palette']);
    $origin_labels = ['theme' => '佈景主題', 'custom' => '自訂', 'default' => 'WP 預設'];
    $groups = [];

    foreach ($origin_labels as $origin => $label) {
        if (!empty($palette[$origin]) && is_array($palette[$origin])) {
            $groups[$label] = $palette[$origin];
        }
    }

    $options = '';
    foreach ($groups as $label => $colors) {
        $options .= '<optgroup label="' . esc_attr($label) . '">';
        foreach ($colors as $c) {
            if (empty($c['slug'])) {
                continue;
            }
            $name = !empty($c['name']) ? $c['name'] : $c['slug'];
            $options .= sprintf(
                '<option value="var(--wp--preset--color--%1$s)">%2$s</option>',
                esc_attr($c['slug']),
                esc_html($name . (!empty($c['color']) ? ' ' . $c['color'] : ''))
            );
        }
        $options .= '</optgroup>';
    }

    // Elementor 全域色彩（classic theme + Elementor 站的 design system）
    if (class_exists('\Elementor\Plugin')) {
        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        $e_colors = [];
        if ($kit) {
            foreach (['system_colors', 'custom_colors'] as $key) {
                $set = $kit->get_settings($key);
                if (is_array($set)) {
                    $e_colors = array_merge($e_colors, $set);
                }
            }
        }
        if (!empty($e_colors)) {
            $options .= '<optgroup label="Elementor 全域色彩">';
            foreach ($e_colors as $c) {
                if (empty($c['_id'])) {
                    continue;
                }
                $title = !empty($c['title']) ? $c['title'] : $c['_id'];
                $options .= sprintf(
                    '<option value="var(--e-global-color-%1$s)">%2$s</option>',
                    esc_attr($c['_id']),
                    esc_html($title . (!empty($c['color']) ? ' ' . $c['color'] : ''))
                );
            }
            $options .= '</optgroup>';
        }
    }

    if ($options === '') {
        return '';
    }

    return '<select class="hrl-preset" title="帶入設計系統色票（Design Token）">'
        . '<option value="">主題色票…</option>' . $options . '</select>';
}

/**
 * 渲染設定頁面
 */
function hreflang_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // 處理表單提交
    if (isset($_POST['hreflang_save_languages']) && check_admin_referer('hreflang_languages_nonce')) {
        $languages = [];
        
        if (isset($_POST['languages']) && is_array($_POST['languages'])) {
            foreach ($_POST['languages'] as $index => $lang) {
                $languages[] = [
                    'code' => sanitize_text_field($lang['code']),
                    'locale' => hreflang_sanitize_locale_code($lang['locale'] ?? '', $lang['code'] ?? ''),
                    'domain' => esc_url_raw($lang['domain']),
                    'label' => sanitize_text_field($lang['label']),
                    'active' => isset($lang['active']),
                    'order' => intval($lang['order']),
                ];
            }
        }
        
        update_option('hreflang_languages', $languages);
        update_option('hreflang_default_lang', sanitize_text_field($_POST['default_lang'] ?? 'en'));
        update_option('hreflang_auto_same_slug', isset($_POST['auto_same_slug']) ? 1 : 0);

        echo '<div class="notice notice-success"><p>設定已儲存。</p></div>';
    }

    // 處理外觀設定儲存（多組主題）
    if (isset($_POST['hreflang_save_styles']) && check_admin_referer('hreflang_styles_nonce')) {
        $posted_themes = isset($_POST['style_themes']) && is_array($_POST['style_themes'])
            ? $_POST['style_themes']
            : ['default'];

        $new_theme_raw = sanitize_text_field($_POST['new_theme_name'] ?? '');
        if (!empty($new_theme_raw)) {
            $posted_themes[] = $new_theme_raw;
        }

        $normalized_themes = [];
        foreach ($posted_themes as $theme_name) {
            $theme = preg_replace('/[^a-z0-9\-_]/', '', strtolower(sanitize_key($theme_name)));
            if (!empty($theme)) {
                $normalized_themes[] = $theme;
            }
        }

        if (!in_array('default', $normalized_themes, true)) {
            array_unshift($normalized_themes, 'default');
        }

        $normalized_themes = array_values(array_unique($normalized_themes));

        $remove_themes = isset($_POST['remove_themes']) && is_array($_POST['remove_themes'])
            ? $_POST['remove_themes']
            : [];
        $remove_themes = array_map(function ($theme_name) {
            return preg_replace('/[^a-z0-9\-_]/', '', strtolower(sanitize_key($theme_name)));
        }, $remove_themes);

        $normalized_themes = array_values(array_filter($normalized_themes, function ($theme) use ($remove_themes) {
            return $theme === 'default' || !in_array($theme, $remove_themes, true);
        }));

        $styles_by_theme = isset($_POST['styles_by_theme']) && is_array($_POST['styles_by_theme'])
            ? $_POST['styles_by_theme']
            : [];

        $previous_themes = get_option('hreflang_style_themes', ['default']);
        if (!is_array($previous_themes)) {
            $previous_themes = ['default'];
        }

        foreach ($normalized_themes as $theme) {
            $raw_styles = isset($styles_by_theme[$theme]) && is_array($styles_by_theme[$theme])
                ? $styles_by_theme[$theme]
                : [];
            $styles = hreflang_sanitize_switcher_styles($raw_styles);
            update_option('hreflang_switcher_styles_' . $theme, $styles);

            if ($theme === 'default') {
                update_option('hreflang_switcher_styles', $styles);
            }
        }

        foreach ($previous_themes as $old_theme) {
            $old_theme = preg_replace('/[^a-z0-9\-_]/', '', strtolower(sanitize_key($old_theme)));
            if (!empty($old_theme) && $old_theme !== 'default' && !in_array($old_theme, $normalized_themes, true)) {
                delete_option('hreflang_switcher_styles_' . $old_theme);
            }
        }

        update_option('hreflang_style_themes', $normalized_themes);
        echo '<div class="notice notice-success"><p>外觀設定已儲存。</p></div>';
    }
    
    $languages = get_option('hreflang_languages', []);
    $default_lang = get_option('hreflang_default_lang', 'en');
    $locale_select_template = hreflang_get_locale_select_html('languages[__INDEX__][locale]');
    
    ?>
    <div class="wrap">
        <h1>Hreflang 語言設定</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('hreflang_languages_nonce'); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>順序</th>
                        <th>語言代碼</th>
                        <th>Locale / hreflang</th>
                        <th>Domain</th>
                        <th>顯示名稱</th>
                        <th>啟用</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="languages-list">
                    <?php if (!empty($languages)): ?>
                        <?php foreach ($languages as $index => $lang): ?>
                            <tr class="language-row">
                                <td>
                                    <input type="number" name="languages[<?php echo $index; ?>][order]" 
                                           value="<?php echo esc_attr($lang['order']); ?>" 
                                           style="width: 60px;" />
                                </td>
                                <td>
                                    <input type="text" name="languages[<?php echo $index; ?>][code]" 
                                           value="<?php echo esc_attr($lang['code']); ?>" 
                                           placeholder="en" required />
                                </td>
                                <td>
                                    <?php echo hreflang_get_locale_select_html('languages[' . $index . '][locale]', $lang['locale']); ?>
                                    <div class="description hrl-locale-note">
                                        <code class="hrl-locale-preview"><?php echo esc_html('hreflang="' . hreflang_get_hreflang_code($lang) . '"'); ?></code>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="languages[<?php echo $index; ?>][domain]" 
                                           value="<?php echo esc_attr($lang['domain']); ?>" 
                                           placeholder="example.com" style="width: 200px;" />
                                </td>
                                <td>
                                    <input type="text" name="languages[<?php echo $index; ?>][label]" 
                                           value="<?php echo esc_attr($lang['label']); ?>" 
                                           placeholder="English" />
                                </td>
                                <td>
                                    <input type="checkbox" name="languages[<?php echo $index; ?>][active]" 
                                           <?php checked($lang['active']); ?> />
                                </td>
                                <td>
                                    <button type="button" class="button remove-language">移除</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">尚未設定語言，請點擊下方「新增語言」按鈕。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p>
                <button type="button" class="button" id="add-language">新增語言</button>
            </p>
            
            <h2>預設語言</h2>
            <p>
                <label for="default_lang">預設語言代碼（用於 x-default）：</label>
                <input type="text" id="default_lang" name="default_lang"
                       value="<?php echo esc_attr($default_lang); ?>"
                       placeholder="en" />
            </p>

            <h2>同 slug 自動對應</h2>
            <p>
                <label>
                    <input type="checkbox" name="auto_same_slug" <?php checked(hreflang_is_auto_same_slug_enabled()); ?> />
                    未手動填寫時，自動以「各語言網域＋相同路徑」產生 alternate URL
                </label>
            </p>
            <p class="description">適用於各站固定網址結構相同（slug 一致、只有網域不同）的站群。文章編輯頁手動填寫的 URL 永遠優先；路徑不同的舊文章請逐篇手填。</p>

            <?php submit_button('儲存設定', 'primary', 'hreflang_save_languages'); ?>
        </form>
        
        <hr>

        <h2>切換器外觀設定</h2>
        <p>可建立多組外觀，並在短碼使用 <code>[hreflang_switcher theme="組別"]</code> 指定。</p>
        <p class="description">
            顏色欄位可透過「主題色票…」帶入佈景主題的 Design Token（<code>var(--wp--preset--color--…)</code>），主題全域樣式改動時切換器自動跟隨；
            文字欄位可填 <code>inherit</code>（跟隨版型文字）或 <code>var(--wp--preset--font-size--medium)</code> 這類字級 token。
        </p>

        <?php
        $style_themes = function_exists('hreflang_get_style_themes')
            ? hreflang_get_style_themes()
            : get_option('hreflang_style_themes', ['default']);
        if (!is_array($style_themes) || empty($style_themes)) {
            $style_themes = ['default'];
        }
        if (!in_array('default', $style_themes, true)) {
            array_unshift($style_themes, 'default');
        }
        $style_themes = array_values(array_unique($style_themes));
        ?>

        <?php $hrl_palette_select = hreflang_get_theme_palette_select_html(); ?>

        <form method="post" action="" id="hrl-styles-form">
            <?php wp_nonce_field('hreflang_styles_nonce'); ?>

            <table class="form-table" style="max-width:720px">
                <tr>
                    <th scope="row"><label for="new_theme_name">新增外觀組別</label></th>
                    <td>
                        <input type="text" id="new_theme_name" name="new_theme_name" placeholder="例如：dark-header" style="width:220px" />
                        <p class="description">僅允許小寫英數、-、_。儲存後即可使用該組別。</p>
                    </td>
                </tr>
            </table>

            <?php foreach ($style_themes as $theme_name) :
                $theme_name = preg_replace('/[^a-z0-9\-_]/', '', strtolower(sanitize_key($theme_name)));
                if (empty($theme_name)) continue;
                $cs = function_exists('hreflang_get_switcher_styles') ? hreflang_get_switcher_styles($theme_name) : [];
                $cs = wp_parse_args($cs, [
                    'btn_bg'=>'#ffffff','btn_color'=>'#333333','btn_border'=>'#e5e5e5',
                    'btn_radius'=>'0.5rem','font_size'=>'14px','font_weight'=>'400',
                    'btn_padding'=>'0.5rem 1rem','btn_margin'=>'0',
                    'menu_bg'=>'#ffffff','menu_border'=>'#e5e5e5','menu_padding'=>'0.25rem 0',
                    'link_color'=>'#333333','link_padding'=>'0.5rem 0.75rem','hover_bg'=>'#f9f9f9',
                    'active_bg'=>'#0073aa','active_color'=>'#ffffff','active_border'=>'#0073aa',
                ]);
            ?>
                <div class="hrl-theme-card">
                    <h3>
                        外觀組別：<code><?php echo esc_html($theme_name); ?></code>
                        <?php if ($theme_name !== 'default') : ?>
                            <label style="margin-left:12px;font-weight:normal">
                                <input type="checkbox" name="remove_themes[]" value="<?php echo esc_attr($theme_name); ?>" /> 刪除此組別
                            </label>
                        <?php endif; ?>
                    </h3>
                    <p class="description" style="margin-top:-6px">
                        使用方式：<code>[hreflang_switcher theme="<?php echo esc_attr($theme_name); ?>"]</code>
                    </p>
                    <input type="hidden" name="style_themes[]" value="<?php echo esc_attr($theme_name); ?>" />

                    <div class="hrl-style-grid">
                        <div>
                            <h4>按鈕</h4>
                            <table class="form-table" style="width:auto">
                                <tr><th>背景色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_bg]" value="<?php echo esc_attr($cs['btn_bg']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>文字色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_color']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_color]" value="<?php echo esc_attr($cs['btn_color']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>邊框色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_border']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_border]" value="<?php echo esc_attr($cs['btn_border']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>圓角</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_radius]" value="<?php echo esc_attr($cs['btn_radius']); ?>" style="width:90px" placeholder="0.5rem"></td></tr>
                                <tr><th>字體大小</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][font_size]" value="<?php echo esc_attr($cs['font_size']); ?>" style="width:90px" placeholder="14px"></td></tr>
                                <tr><th>字重</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][font_weight]" value="<?php echo esc_attr($cs['font_weight']); ?>" style="width:90px" placeholder="400 / bold"></td></tr>
                                <tr><th>內距 padding</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_padding]" value="<?php echo esc_attr($cs['btn_padding']); ?>" style="width:120px" placeholder="0.5rem 1rem"></td></tr>
                                <tr><th>外距 margin</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_margin]" value="<?php echo esc_attr($cs['btn_margin']); ?>" style="width:120px" placeholder="0"></td></tr>
                            </table>
                        </div>

                        <div>
                            <h4>下拉選單</h4>
                            <table class="form-table" style="width:auto">
                                <tr><th>背景色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['menu_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][menu_bg]" value="<?php echo esc_attr($cs['menu_bg']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>邊框色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['menu_border']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][menu_border]" value="<?php echo esc_attr($cs['menu_border']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>連結色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['link_color']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][link_color]" value="<?php echo esc_attr($cs['link_color']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>Hover 背景</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['hover_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][hover_bg]" value="<?php echo esc_attr($cs['hover_bg']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>選單內距</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][menu_padding]" value="<?php echo esc_attr($cs['menu_padding']); ?>" style="width:120px" placeholder="0.25rem 0"></td></tr>
                                <tr><th>項目內距</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][link_padding]" value="<?php echo esc_attr($cs['link_padding']); ?>" style="width:120px" placeholder="0.5rem 0.75rem"></td></tr>
                            </table>
                        </div>

                        <div>
                            <h4>Active 語言（清單樣式）</h4>
                            <table class="form-table" style="width:auto">
                                <tr><th>背景色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][active_bg]" value="<?php echo esc_attr($cs['active_bg']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>文字色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_color']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][active_color]" value="<?php echo esc_attr($cs['active_color']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                                <tr><th>邊框色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_border']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][active_border]" value="<?php echo esc_attr($cs['active_border']); ?>" class="hrl-hex" spellcheck="false"><?php echo $hrl_palette_select; ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php submit_button('儲存外觀設定', 'secondary', 'hreflang_save_styles', false); ?>
        </form>

        <hr>
        
        <h2>使用說明</h2>
        <ol>
            <li>設定您網站支援的所有語言版本</li>
            <li>在文章/頁面編輯時，填寫各語言的對應 URL（使用 ACF 欄位或自訂欄位）</li>
            <li>在分類/標籤編輯頁面，填寫對應語言的 URL</li>
            <li>使用短碼 <code>[hreflang_switcher]</code> 顯示預設下拉選單，或 <code>[hreflang_switcher type="list"]</code> 使用列表樣式</li>
            <li>可搭配外觀組別：<code>[hreflang_switcher type="dropdown" theme="dark-header"]</code></li>
        </ol>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let langIndex = <?php echo count($languages); ?>;
        
        $('#add-language').on('click', function() {
            const row = `
                <tr class="language-row">
                    <td><input type="number" name="languages[${langIndex}][order]" value="${langIndex + 1}" style="width: 60px;" /></td>
                    <td><input type="text" name="languages[${langIndex}][code]" placeholder="en" required /></td>
                    <td>${localeSelectTemplate.replace(/__INDEX__/g, langIndex)}<div class="description hrl-locale-note"><code class="hrl-locale-preview">hreflang=""</code></div></td>
                    <td><input type="text" name="languages[${langIndex}][domain]" placeholder="example.com" style="width: 200px;" /></td>
                    <td><input type="text" name="languages[${langIndex}][label]" placeholder="English" /></td>
                    <td><input type="checkbox" name="languages[${langIndex}][active]" checked /></td>
                    <td><button type="button" class="button remove-language">移除</button></td>
                </tr>
            `;
            $('#languages-list').append(row);
            langIndex++;
        });
        
        $(document).on('click', '.remove-language', function() {
            $(this).closest('tr').remove();
        });

        function updateLocalePreview(row) {
            const select = row.find('.hrl-locale-select');
            const preview = row.find('.hrl-locale-preview');
            if (!select.length || !preview.length) return;

            const value = $.trim(select.val());
            preview.text(value ? 'hreflang="' + value + '"' : 'hreflang=""');
        }

        const localeSelectTemplate = <?php echo wp_json_encode($locale_select_template); ?>;

        $(document).on('change', '.hrl-locale-select', function() {
            updateLocalePreview($(this).closest('tr'));
        });

        $('.language-row').each(function() {
            updateLocalePreview($(this));
        });
    });
    </script>

    <script>
    (function ($) {
        // 顏色選擇器 → 文字欄位（雙向同步）
        $(document).on('input', '.hrl-color', function () {
            $(this).siblings('.hrl-hex').val($(this).val());
        });

        // 文字欄位 → 顏色選擇器（輸入合法 hex 才同步）
        $(document).on('input blur', '.hrl-hex', function () {
            var v = $.trim($(this).val());
            if (/^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/.test(v)) {
                $(this).siblings('.hrl-color').val(v.slice(0, 7));
            }
        });

        // 主題色票下拉 → 帶入 token 到文字欄位
        $(document).on('change', '.hrl-preset', function () {
            var v = $(this).val();
            if (!v) return;
            $(this).siblings('.hrl-hex').val(v).trigger('blur');
            $(this).val('');
        });
    }(jQuery));
    </script>
    
    <style>
    .language-row input[type="text"],
    .language-row input[type="number"] {
        width: 100%;
        max-width: 200px;
    }
    .hrl-locale-select { width: 100%; max-width: 240px; }
    .hrl-locale-note { margin-top: 6px; }
    .hrl-locale-preview { font-size: 12px; }
    .hrl-color { width:46px; height:32px; padding:2px; cursor:pointer; border:1px solid #ddd; border-radius:3px 0 0 3px; border-right:0; vertical-align:middle; box-sizing:border-box; }
    .hrl-hex { width:170px; height:32px; font-size:12px; font-family:monospace; padding:0 6px; border:1px solid #ddd; border-radius:0 3px 3px 0; vertical-align:middle; box-sizing:border-box; }
    .hrl-preset { max-width:120px; height:32px; margin-left:6px; vertical-align:middle; font-size:12px; }
    .hrl-hex:focus { outline:none; border-color:#007cba; box-shadow:0 0 0 1px #007cba; z-index:1; position:relative; }
    #hrl-styles-form .form-table th { width:110px; font-weight:600; padding:8px 10px 8px 0; white-space:nowrap; vertical-align:middle; }
    #hrl-styles-form .form-table td { padding:6px 0; vertical-align:middle; }
    .hrl-theme-card { margin:16px 0 24px; padding:16px; border:1px solid #dcdcde; border-radius:4px; background:#fff; }
    .hrl-theme-card h3 { margin-top:0; margin-bottom:6px; }
    .hrl-style-grid { display:flex; gap:2rem; flex-wrap:wrap; align-items:flex-start; }
    </style>
    <?php
}
