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

    // 處理外觀設定儲存
    if (isset($_POST['hreflang_save_styles']) && check_admin_referer('hreflang_styles_nonce')) {
        $styles = hreflang_sanitize_switcher_styles($_POST['styles'] ?? []);
        update_option('hreflang_switcher_styles', $styles);
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
        <p>自訂語言切換器的顏色、字體與圓角。變更儲存後請重新整理前台頁面預覽效果。</p>

        <?php
        $cs = function_exists('hreflang_get_switcher_styles') ? hreflang_get_switcher_styles() : [];
        $cs = wp_parse_args($cs, [
            'btn_bg'=>'#ffffff','btn_color'=>'#333333','btn_border'=>'#e5e5e5',
            'btn_radius'=>'0.5rem','font_size'=>'14px',
            'menu_bg'=>'#ffffff','menu_border'=>'#e5e5e5','link_color'=>'#333333','hover_bg'=>'#f9f9f9',
            'active_bg'=>'#0073aa','active_color'=>'#ffffff','active_border'=>'#0073aa',
        ]);
        ?>

        <div style="margin-bottom:1.5rem">
            <strong>快速主題：</strong>&nbsp;
            <button type="button" class="button hrl-theme-btn" data-theme="default">⬜ 預設</button>
            <button type="button" class="button hrl-theme-btn" data-theme="dark">⬛ 深色</button>
            <button type="button" class="button hrl-theme-btn" data-theme="minimal">&#9651; 極簡</button>
            <button type="button" class="button hrl-theme-btn" data-theme="pill">&#9711; 藥丸</button>
        </div>

        <form method="post" action="" id="hrl-styles-form">
            <?php wp_nonce_field('hreflang_styles_nonce'); ?>

            <div style="display:flex;gap:2.5rem;flex-wrap:wrap;align-items:flex-start">

                <div>
                    <h3 style="margin-top:0">按鈕</h3>
                    <table class="form-table" style="width:auto">
                        <tr>
                            <th>背景色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_bg']); ?>"><input type="text" name="styles[btn_bg]" value="<?php echo esc_attr($cs['btn_bg']); ?>" class="hrl-hex" placeholder="#ffffff" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>文字色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_color']); ?>"><input type="text" name="styles[btn_color]" value="<?php echo esc_attr($cs['btn_color']); ?>" class="hrl-hex" placeholder="#333333" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>邊框色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['btn_border']); ?>"><input type="text" name="styles[btn_border]" value="<?php echo esc_attr($cs['btn_border']); ?>" class="hrl-hex" placeholder="#e5e5e5" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>圓角</th>
                            <td><input type="text" name="styles[btn_radius]" id="hrl-btn-radius" value="<?php echo esc_attr($cs['btn_radius']); ?>" style="width:90px" placeholder="0.5rem"><span class="description"> px / rem / 999px</span></td>
                        </tr>
                        <tr>
                            <th>字體大小</th>
                            <td><input type="text" name="styles[font_size]" id="hrl-font-size" value="<?php echo esc_attr($cs['font_size']); ?>" style="width:90px" placeholder="14px"><span class="description"> px / rem</span></td>
                        </tr>
                    </table>
                </div>

                <div>
                    <h3 style="margin-top:0">下拉選單</h3>
                    <table class="form-table" style="width:auto">
                        <tr>
                            <th>背景色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['menu_bg']); ?>"><input type="text" name="styles[menu_bg]" value="<?php echo esc_attr($cs['menu_bg']); ?>" class="hrl-hex" placeholder="#ffffff" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>邊框色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['menu_border']); ?>"><input type="text" name="styles[menu_border]" value="<?php echo esc_attr($cs['menu_border']); ?>" class="hrl-hex" placeholder="#e5e5e5" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>連結色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['link_color']); ?>"><input type="text" name="styles[link_color]" value="<?php echo esc_attr($cs['link_color']); ?>" class="hrl-hex" placeholder="#333333" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>Hover 背景</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['hover_bg']); ?>"><input type="text" name="styles[hover_bg]" value="<?php echo esc_attr($cs['hover_bg']); ?>" class="hrl-hex" placeholder="#f9f9f9" maxlength="9" spellcheck="false"></td>
                        </tr>
                    </table>
                </div>

                <div>
                    <h3 style="margin-top:0">Active 語言<small>（清單樣式）</small></h3>
                    <table class="form-table" style="width:auto">
                        <tr>
                            <th>背景色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_bg']); ?>"><input type="text" name="styles[active_bg]" value="<?php echo esc_attr($cs['active_bg']); ?>" class="hrl-hex" placeholder="#0073aa" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>文字色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_color']); ?>"><input type="text" name="styles[active_color]" value="<?php echo esc_attr($cs['active_color']); ?>" class="hrl-hex" placeholder="#ffffff" maxlength="9" spellcheck="false"></td>
                        </tr>
                        <tr>
                            <th>邊框色</th>
                            <td><input type="color" class="hrl-color" value="<?php echo esc_attr($cs['active_border']); ?>"><input type="text" name="styles[active_border]" value="<?php echo esc_attr($cs['active_border']); ?>" class="hrl-hex" placeholder="#0073aa" maxlength="9" spellcheck="false"></td>
                        </tr>
                    </table>
                </div>

                <div>
                    <h3 style="margin-top:0">即時預覽</h3>
                    <div style="padding:1.2rem;background:#f0f0f1;border-radius:4px;min-width:180px">
                        <div id="hrl-preview-switcher" style="display:inline-block;position:relative">
                            <button id="hrl-preview-btn" type="button" style="cursor:default">
                                繁體中文 ▾
                            </button>
                            <ul id="hrl-preview-menu" style="list-style:none;padding:.25rem 0;margin:.25rem 0 0;min-width:10rem">
                                <li><a id="hrl-preview-a1" href="#" onclick="return false" style="display:block;padding:.5rem .75rem;text-decoration:none">English</a></li>
                                <li><a id="hrl-preview-a2" href="#" onclick="return false" style="display:block;padding:.5rem .75rem;text-decoration:none">日本語</a></li>
                            </ul>
                        </div>
                        <p style="margin:.75rem 0 0;font-size:11px;color:#666">下拉選單（已展開）預覽</p>
                    </div>
                </div>

            </div>

            <?php submit_button('儲存外觀設定', 'secondary', 'hreflang_save_styles', false); ?>
        </form>

        <hr>
        
        <h2>使用說明</h2>
        <ol>
            <li>設定您網站支援的所有語言版本</li>
            <li>在文章/頁面編輯時，填寫各語言的對應 URL（使用 ACF 欄位或自訂欄位）</li>
            <li>在分類/標籤編輯頁面，填寫對應語言的 URL</li>
            <li>使用短碼 <code>[hreflang_switcher]</code> 在前端顯示語言切換器</li>
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
        var themes = {
            'default': {btn_bg:'#ffffff',btn_color:'#333333',btn_border:'#e5e5e5',menu_bg:'#ffffff',menu_border:'#e5e5e5',link_color:'#333333',hover_bg:'#f9f9f9',active_bg:'#0073aa',active_color:'#ffffff',active_border:'#0073aa',btn_radius:'0.5rem',font_size:'14px'},
            'dark':    {btn_bg:'#1e1e1e',btn_color:'#ffffff',btn_border:'#444444',menu_bg:'#2d2d2d',menu_border:'#444444',link_color:'#eeeeee',hover_bg:'#3a3a3a',active_bg:'#4a9eda',active_color:'#ffffff',active_border:'#4a9eda',btn_radius:'0.5rem',font_size:'14px'},
            'minimal': {btn_bg:'#ffffff',btn_color:'#555555',btn_border:'#ffffff',menu_bg:'#ffffff',menu_border:'#dddddd',link_color:'#555555',hover_bg:'#f5f5f5',active_bg:'#f0f0f0',active_color:'#333333',active_border:'#cccccc',btn_radius:'0px',font_size:'14px'},
            'pill':    {btn_bg:'#0073aa',btn_color:'#ffffff',btn_border:'#005a87',menu_bg:'#ffffff',menu_border:'#e5e5e5',link_color:'#333333',hover_bg:'#eaf4fb',active_bg:'#0073aa',active_color:'#ffffff',active_border:'#005a87',btn_radius:'999px',font_size:'14px'}
        };

        function updatePreview() {
            var btn  = document.getElementById('hrl-preview-btn');
            var menu = document.getElementById('hrl-preview-menu');
            if (!btn || !menu) return;
            var bg     = $('[name="styles[btn_bg]"]').val()     || '#ffffff';
            var color  = $('[name="styles[btn_color]"]').val()  || '#333333';
            var border = $('[name="styles[btn_border]"]').val() || '#e5e5e5';
            var radius = $('#hrl-btn-radius').val()             || '0.5rem';
            var fs     = $('#hrl-font-size').val()              || '14px';
            var menuBg = $('[name="styles[menu_bg]"]').val()    || '#ffffff';
            var menuBd = $('[name="styles[menu_border]"]').val()|| '#e5e5e5';
            var lc     = $('[name="styles[link_color]"]').val() || '#333333';
            var hv     = $('[name="styles[hover_bg]"]').val()   || '#f9f9f9';

            btn.style.cssText  = 'cursor:default;padding:.5rem 1rem;border:1px solid '+border+';border-radius:'+radius+';background:'+bg+';color:'+color+';font-size:'+fs;
            menu.style.cssText = 'list-style:none;padding:.25rem 0;margin:.25rem 0 0;min-width:10rem;border:1px solid '+menuBd+';background:'+menuBg;
            $('#hrl-preview-menu a').each(function() {
                $(this).css({color: lc, fontSize: fs});
                $(this).off('mouseenter mouseleave');
                $(this).on('mouseenter', function(){ $(this).css('background', hv); });
                $(this).on('mouseleave', function(){ $(this).css('background', ''); });
            });
        }

        $(document).on('click', '.hrl-theme-btn', function () {
            var key = $(this).data('theme');
            var t   = themes[key];
            if (!t) return;
            $.each(t, function (k, v) {
                var el = $('[name="styles[' + k + ']"]');
                if (el.length) {
                    el.val(v);
                    // 同步更新旁邊的顏色選擇器
                    if (el.hasClass('hrl-hex')) {
                        el.siblings('.hrl-color').val(v);
                    }
                }
            });
            if (t.btn_radius) $('#hrl-btn-radius').val(t.btn_radius);
            if (t.font_size)  $('#hrl-font-size').val(t.font_size);
            updatePreview();
        });

        // 顏色選擇器 → 文字欄位（雙向同步）
        $(document).on('input', '.hrl-color', function () {
            $(this).siblings('.hrl-hex').val($(this).val());
            updatePreview();
        });

        // 文字欄位 → 顏色選擇器（輸入合法 hex 才同步）
        $(document).on('input blur', '.hrl-hex', function () {
            var v = $.trim($(this).val());
            if (/^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/.test(v)) {
                $(this).siblings('.hrl-color').val(v.slice(0, 7));
            }
            updatePreview();
        });

        $(document).on('input change', '#hrl-btn-radius, #hrl-font-size', updatePreview);
        $(document).ready(updatePreview);
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
    .hrl-theme-btn { margin-right:6px !important; }
    #hrl-preview-btn { border: 1px solid #e5e5e5; background: #fff; }
    #hrl-preview-menu { border: 1px solid #e5e5e5; background: #fff; }
    </style>
    <?php
}
