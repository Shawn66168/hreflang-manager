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
            'locale' => sanitize_text_field($lang['locale'] ?? ''),
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
 * 清理切換器外觀設定
 * 只允許 hex 顏色（#rrggbb）與安全的 CSS 尺寸值
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
            $val = sanitize_text_field($input[$field]);
            if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
                $sanitized[$field] = strtolower($val);
            }
        }
    }

    if (isset($input['btn_radius'])) {
        $val = sanitize_text_field($input['btn_radius']);
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%)?$/', $val)) {
            $sanitized['btn_radius'] = $val;
        }
    }

    if (isset($input['font_size'])) {
        $val = sanitize_text_field($input['font_size']);
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|pt)?$/', $val)) {
            $sanitized['font_size'] = $val;
        }
    }

    return $sanitized;
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
                    'locale' => sanitize_text_field($lang['locale']),
                    'domain' => esc_url_raw($lang['domain']),
                    'label' => sanitize_text_field($lang['label']),
                    'active' => isset($lang['active']),
                    'order' => intval($lang['order']),
                ];
            }
        }
        
        update_option('hreflang_languages', $languages);
        update_option('hreflang_default_lang', sanitize_text_field($_POST['default_lang'] ?? 'en'));
        
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
                        <th>Locale</th>
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
                                    <input type="text" name="languages[<?php echo $index; ?>][locale]" 
                                           value="<?php echo esc_attr($lang['locale']); ?>" 
                                           placeholder="en-US" />
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
            
            <?php submit_button('儲存設定', 'primary', 'hreflang_save_languages'); ?>
        </form>
        
        <hr>

        <h2>切換器外觀設定</h2>
        <p>可建立多組外觀，並在短碼使用 <code>[hreflang_switcher theme="組別"]</code> 指定。</p>

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
                    'btn_radius'=>'0.5rem','font_size'=>'14px',
                    'menu_bg'=>'#ffffff','menu_border'=>'#e5e5e5','link_color'=>'#333333','hover_bg'=>'#f9f9f9',
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
                                <tr><th>背景色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_bg]" value="<?php echo esc_attr($cs['btn_bg']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>文字色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_color']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_color]" value="<?php echo esc_attr($cs['btn_color']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>邊框色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_border']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_border]" value="<?php echo esc_attr($cs['btn_border']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>圓角</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][btn_radius]" value="<?php echo esc_attr($cs['btn_radius']); ?>" style="width:90px" placeholder="0.5rem"></td></tr>
                                <tr><th>字體大小</th><td><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][font_size]" value="<?php echo esc_attr($cs['font_size']); ?>" style="width:90px" placeholder="14px"></td></tr>
                            </table>
                        </div>

                        <div>
                            <h4>下拉選單</h4>
                            <table class="form-table" style="width:auto">
                                <tr><th>背景色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['menu_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][menu_bg]" value="<?php echo esc_attr($cs['menu_bg']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>邊框色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['menu_border']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][menu_border]" value="<?php echo esc_attr($cs['menu_border']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>連結色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['link_color']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][link_color]" value="<?php echo esc_attr($cs['link_color']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>Hover 背景</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['hover_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][hover_bg]" value="<?php echo esc_attr($cs['hover_bg']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                            </table>
                        </div>

                        <div>
                            <h4>Active 語言（清單樣式）</h4>
                            <table class="form-table" style="width:auto">
                                <tr><th>背景色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_bg']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][active_bg]" value="<?php echo esc_attr($cs['active_bg']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>文字色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_color']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][active_color]" value="<?php echo esc_attr($cs['active_color']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
                                <tr><th>邊框色</th><td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_border']); ?>"><input type="text" name="styles_by_theme[<?php echo esc_attr($theme_name); ?>][active_border]" value="<?php echo esc_attr($cs['active_border']); ?>" class="hrl-hex" maxlength="9" spellcheck="false"></td></tr>
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
            <li>使用短碼 <code>[hreflang_switcher]</code> 顯示預設外觀，或 <code>[hreflang_switcher theme="dark-header"]</code> 指定外觀組別</li>
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
                    <td><input type="text" name="languages[${langIndex}][locale]" placeholder="en-US" /></td>
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
    }(jQuery));
    </script>
    
    <style>
    .language-row input[type="text"],
    .language-row input[type="number"] {
        width: 100%;
        max-width: 200px;
    }
    .hrl-color { width:46px; height:32px; padding:2px; cursor:pointer; border:1px solid #ddd; border-radius:3px 0 0 3px; border-right:0; vertical-align:middle; box-sizing:border-box; }
    .hrl-hex { width:82px; height:32px; font-size:12px; font-family:monospace; padding:0 6px; border:1px solid #ddd; border-radius:0 3px 3px 0; vertical-align:middle; box-sizing:border-box; text-transform:lowercase; }
    .hrl-hex:focus { outline:none; border-color:#007cba; box-shadow:0 0 0 1px #007cba; z-index:1; position:relative; }
    #hrl-styles-form .form-table th { width:110px; font-weight:600; padding:8px 10px 8px 0; white-space:nowrap; vertical-align:middle; }
    #hrl-styles-form .form-table td { padding:6px 0; vertical-align:middle; }
    .hrl-theme-card { margin:16px 0 24px; padding:16px; border:1px solid #dcdcde; border-radius:4px; background:#fff; }
    .hrl-theme-card h3 { margin-top:0; margin-bottom:6px; }
    .hrl-style-grid { display:flex; gap:2rem; flex-wrap:wrap; align-items:flex-start; }
    </style>
    <?php
}
