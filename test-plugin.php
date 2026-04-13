<?php
/**
 * 外掛功能測試腳本
 * 
 * 使用方法: docker compose run --rm wpcli wp eval-file test-plugin.php
 */

echo "=== Hreflang Manager 外掛功能測試 ===\n\n";

// 1. 檢查外掛是否啟用
echo "1. 檢查外掛狀態...\n";
$plugin_file = 'wp-hreflang-manager/hreflang-manager.php';
if (is_plugin_active($plugin_file)) {
    echo "   ✓ 外掛已啟用\n";
} else {
    echo "   ✗ 外掛未啟用\n";
    exit(1);
}

// 2. 檢查設定是否存在
echo "\n2. 檢查設定選項...\n";
$settings = get_option('hreflang_settings');
if ($settings) {
    echo "   ✓ 設定選項存在\n";
    echo "   資料: " . print_r($settings, true) . "\n";
} else {
    echo "   ⚠ 設定選項不存在（正常，首次使用）\n";
}

// 3. 檢查函數是否可用
echo "\n3. 檢查核心函數...\n";
$functions = [
    'hreflang_get_languages',
    'hreflang_get_default_language',
    'hreflang_detect_current_language',
    'hreflang_get_language_label',
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "   ✓ $func 存在\n";
    } else {
        echo "   ✗ $func 不存在\n";
    }
}

// 4. 測試語言偵測
echo "\n4. 測試語言偵測功能...\n";
if (function_exists('hreflang_detect_current_language')) {
    $current_lang = hreflang_detect_current_language();
    echo "   當前語言: $current_lang\n";
}

// 5. 檢查短碼註冊
echo "\n5. 檢查短碼註冊...\n";
global $shortcode_tags;
if (isset($shortcode_tags['hreflang_switcher'])) {
    echo "   ✓ hreflang_switcher 短碼已註冊\n";
} else {
    echo "   ✗ hreflang_switcher 短碼未註冊\n";
}

// 6. 檢查 Hook 註冊
echo "\n6. 檢查 WordPress Hook...\n";
if (has_action('wp_head', 'hreflang_output_tags')) {
    echo "   ✓ wp_head hook 已註冊（輸出 hreflang 標籤）\n";
} else {
    echo "   ⚠ wp_head hook 未註冊\n";
}

if (has_action('admin_menu', 'hreflang_register_settings_page')) {
    echo "   ✓ admin_menu hook 已註冊（設定頁面）\n";
} else {
    echo "   ⚠ admin_menu hook 未註冊\n";
}

echo "\n=== 測試完成 ===\n";
