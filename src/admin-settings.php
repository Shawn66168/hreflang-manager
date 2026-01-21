<?php
/**
 * Admin Settings Page - 後台設定頁面
 */
defined('ABSPATH') || exit;

/**
 * 註冊設定頁面
 */
function hreflang_add_settings_page() {
    add_options_page(
        'Hreflang Languages',
        'Hreflang Languages',
        'manage_options',
        'hreflang-hreflang-settings',
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
    
    <style>
    .language-row input[type="text"],
    .language-row input[type="number"] {
        width: 100%;
        max-width: 200px;
    }
    </style>
    <?php
}
