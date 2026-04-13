<?php
/**
 * Admin Settings Page - 敺閮剖??
 * 
 * @package Hreflang_Manager
 */

// 憒??湔閮芸?甇斗?獢????
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 閮餃?閮剖??
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
 * 閮餃?閮剖??賊?
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
 * 皜?隤?鞈?
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
    
    // ??order ??
    usort($sanitized, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    return $sanitized;
}

/**
 * 皜脫?閮剖??
 */
function hreflang_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // ??銵典?漱
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
        
        echo '<div class="notice notice-success"><p>閮剖?撌脣摮?/p></div>';
    }
    
    $languages = get_option('hreflang_languages', []);
    $default_lang = get_option('hreflang_default_lang', 'en');
    
    ?>
    <div class="wrap">
        <h1>Hreflang 隤?閮剖?</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('hreflang_languages_nonce'); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>??</th>
                        <th>隤?隞?Ⅳ</th>
                        <th>Locale</th>
                        <th>Domain</th>
                        <th>憿舐內?迂</th>
                        <th>?</th>
                        <th>??</th>
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
                                    <button type="button" class="button remove-language">蝘駁</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">撠閮剖?隤?嚗?暺?銝?憓?閮????/td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p>
                <button type="button" class="button" id="add-language">?啣?隤?</button>
            </p>
            
            <h2>?身隤?</h2>
            <p>
                <label for="default_lang">?身隤?隞?Ⅳ嚗??x-default嚗?</label>
                <input type="text" id="default_lang" name="default_lang" 
                       value="<?php echo esc_attr($default_lang); ?>" 
                       placeholder="en" />
            </p>
            
            <?php submit_button('?脣?閮剖?', 'primary', 'hreflang_save_languages'); ?>
        </form>
        
        <hr>
        
        <h2>雿輻隤芣?</h2>
        <ol>
            <li>閮剖??函雯蝡?渡????閮?</li>
            <li>?冽?蝡??蝺刻摩??憛怠神??閮????URL嚗蝙??ACF 甈??閮?雿?</li>
            <li>?典?憿?璅惜蝺刻摩?嚗‵撖怠???閮??URL</li>
            <li>雿輻?剔Ⅳ <code>[hreflang_switcher]</code> ?典?蝡舫＊蝷箄?閮????/li>
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
                    <td><button type="button" class="button remove-language">蝘駁</button></td>
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
