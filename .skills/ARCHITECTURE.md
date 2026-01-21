# Hreflang Manager 專案架構規範

> **專案專屬架構規範與開發指南**  
> 最後更新：2026-01-21  
> 作者：CHUANG,HSIN-HSUEH

## 📋 目錄

- [專案概述](#專案概述)
- [核心架構](#核心架構)
- [命名規範](#命名規範)
- [代碼結構](#代碼結構)
- [資料模型](#資料模型)
- [Hook 系統](#hook-系統)
- [前端樣式](#前端樣式)
- [安全性規範](#安全性規範)
- [開發流程](#開發流程)
- [擴展指南](#擴展指南)

---

## 專案概述

### 專案資訊
- **專案名稱**：Hreflang Manager & Language Switcher
- **專案類型**：WordPress Plugin
- **版本**：1.0.0
- **PHP 版本**：≥ 7.4
- **WordPress 版本**：≥ 5.0

### 專案目標
為多語言網站提供完整的 hreflang 標籤管理和語言切換功能，支援多域名、子目錄等多種多語架構。

### 核心功能
1. 自動在 `<head>` 輸出 hreflang 標籤
2. 支援所有 WordPress 頁面類型（文章、頁面、分類、標籤、搜尋、archive）
3. 提供語言切換 UI（下拉選單、清單樣式）
4. 後台語言管理介面
5. ACF 整合與 Term Meta 支援
6. 後台缺漏提醒系統

---

## 核心架構

### 目錄結構

```
wp-hreflang-manager/
├── hreflang-switch.php              # 主外掛檔案（入口點）
├── hreflang-manager.php             # 備用主檔案
├── composer.json                    # Composer 配置
├── .gitignore                       # Git 忽略規則
├── README.md                        # 專案說明文件
├── INSTALLATION.md                  # 安裝指南
├── QUICKSTART.md                    # 快速開始
├── CHANGELOG.md                     # 更新日誌
├── PROJECT_SUMMARY.md               # 專案總結
├── LICENSE                          # GPL-2.0 授權
│
├── .skills/                         # 專案規範文件
│   └── ARCHITECTURE.md              # 本文件
│
├── src/                             # 核心程式碼
│   ├── helpers.php                  # 工具函式
│   ├── hreflang-core.php           # Hreflang 輸出邏輯
│   ├── nav-shortcode.php           # 語言切換短碼
│   ├── admin-notice.php            # 後台提示系統
│   └── admin-settings.php          # 設定頁面
│
└── assets/                          # 前端資源
    └── css/
        └── style.css                # 語言切換器樣式
```

### 檔案職責

| 檔案 | 職責 | 大小 | 核心函式 |
|------|------|------|---------|
| `hreflang-switch.php` | 外掛入口、載入所有模組 | ~1.5 KB | - |
| `helpers.php` | 工具函式庫 | ~4.7 KB | `hreflang_get_languages()` |
| `hreflang-core.php` | Hreflang 標籤輸出 | ~5.5 KB | `hreflang_output_hreflang()` |
| `nav-shortcode.php` | 語言切換器 | ~4.3 KB | `hreflang_switcher_shortcode()` |
| `admin-settings.php` | 後台設定頁面 | ~9.2 KB | `hreflang_render_settings_page()` |
| `admin-notice.php` | 後台通知系統 | ~5.1 KB | `hreflang_admin_notice_missing_urls()` |

### 模組載入順序

```php
// 1. Composer Autoload (可選)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 2. 載入核心模組
require_once plugin_dir_path(__FILE__) . 'src/helpers.php';
require_once plugin_dir_path(__FILE__) . 'src/hreflang-core.php';
require_once plugin_dir_path(__FILE__) . 'src/nav-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'src/admin-notice.php';
require_once plugin_dir_path(__FILE__) . 'src/admin-settings.php';

// 3. 翻譯載入
add_action('plugins_loaded', 'hreflang_manager_load_textdomain');
```

---

## 命名規範

### 函式命名

**規則**：所有函式使用 `hreflang_` 前綴，使用蛇形命名法 (snake_case)

```php
// ✅ 正確
function hreflang_get_languages() {}
function hreflang_output_hreflang() {}
function hreflang_get_alternate_urls() {}

// ❌ 錯誤
function get_languages() {}          // 缺少前綴
function hreflangGetLanguages() {}   // 駝峰命名
function portwell_hreflang_test() {} // 舊前綴
```

### 選項鍵命名

**格式**：`hreflang_{功能名稱}`

```php
// ✅ 選項鍵
'hreflang_languages'       // 語言清單
'hreflang_default_lang'    // 預設語言

// ✅ Post Meta
'alt_{lang_code}_url'      // 文章語言 URL

// ✅ Term Meta
'term_alt_{lang_code}_url' // 分類語言 URL
```

### Hook 命名

**格式**：`hreflang_{動作或過濾對象}`

```php
// ✅ 過濾器
apply_filters('hreflang_languages', $languages);
apply_filters('hreflang_alternate_urls', $urls, $object);
apply_filters('hreflang_default_language', $default);

// ✅ 動作鉤子（未來擴展）
do_action('hreflang_before_output', $urls);
do_action('hreflang_after_output', $urls);
```

### CSS 類別命名

**格式**：`hreflang-{元件}-{子元件}`

```css
/* ✅ 正確 */
.hreflang-lang-switcher {}
.hreflang-lang-select {}
.hreflang-lang-item {}
.hreflang-lang-link {}

/* ❌ 錯誤 */
.portwell-lang-switcher {}  /* 舊前綴 */
.lang-switcher {}            /* 缺少前綴 */
```

### 短碼命名

```php
// ✅ 主要短碼
[hreflang_switcher]

// ❌ 避免使用
[portwell_hreflang_switcher]  // 舊命名
```

---

## 代碼結構

### 檔案結構模板

每個 PHP 檔案應遵循以下結構：

```php
<?php
/**
 * 檔案用途簡述
 * 
 * @package Hreflang_Manager
 * @since 1.0.0
 */

// 1. 安全檢查
defined('ABSPATH') || exit;

// 2. 常數定義（如需要）
define('HREFLANG_CONSTANT', 'value');

// 3. 主要函式定義
function hreflang_main_function() {
    // 實作
}

// 4. Hook 註冊
add_action('init', 'hreflang_main_function');
add_filter('hreflang_example', 'hreflang_filter_function');

// 5. 輔助函式
function hreflang_helper_function() {
    // 實作
}
```

### 函式結構模板

```php
/**
 * 函式用途說明
 * 
 * @since 1.0.0
 * @param string $param1 參數說明
 * @param array  $param2 參數說明
 * @return mixed 回傳值說明
 */
function hreflang_example_function($param1, $param2 = []) {
    // 1. 參數驗證
    if (empty($param1)) {
        return false;
    }
    
    // 2. 資料處理
    $data = process_data($param1);
    
    // 3. 套用過濾器（如需要）
    $data = apply_filters('hreflang_example_data', $data, $param1);
    
    // 4. 回傳結果
    return $data;
}
```

### WordPress Hook 使用原則

```php
// ✅ 優先使用 WordPress 標準 Hook
add_action('init', 'hreflang_init');
add_action('wp_head', 'hreflang_output_hreflang', 1);
add_action('admin_menu', 'hreflang_add_settings_page');

// ✅ 提供自訂 Filter 讓使用者擴展
$languages = apply_filters('hreflang_languages', $languages);

// ✅ 使用優先級控制執行順序
add_action('wp_head', 'hreflang_output_hreflang', 1);  // 優先輸出
```

---

## 資料模型

### 語言資料結構

**選項鍵**：`hreflang_languages`  
**類型**：Array of Objects

```php
[
    [
        'code'   => 'en',              // 語言代碼（ISO 639-1）
        'locale' => 'en-US',           // Locale 代碼
        'domain' => 'www.example.com', // 域名（不含協議）
        'label'  => 'English',         // 顯示名稱
        'active' => true,              // 是否啟用（boolean）
        'order'  => 1                  // 排序順序（int）
    ],
    [
        'code'   => 'zh-Hant',
        'locale' => 'zh-Hant',
        'domain' => 'www.example.tw',
        'label'  => '繁體中文',
        'active' => true,
        'order'  => 2
    ]
]
```

### 預設語言設定

**選項鍵**：`hreflang_default_lang`  
**類型**：String  
**預設值**：`'en'`

### Post Meta 結構

**格式**：`alt_{lang_code}_url`

```php
// 範例
'alt_en_url'       => 'https://www.example.com/about/'
'alt_zh-Hant_url'  => 'https://www.example.tw/about/'
'alt_ja_url'       => 'https://www.example.jp/about/'
```

### Term Meta 結構

**格式**：`term_alt_{lang_code}_url`

```php
// 範例
'term_alt_en_url'      => 'https://www.example.com/category/tech/'
'term_alt_zh-Hant_url' => 'https://www.example.tw/category/tech/'
```

### 資料流程圖

```
[後台設定頁面]
      ↓
[hreflang_languages] (Options Table)
      ↓
[hreflang_get_languages()] ← apply_filters('hreflang_languages')
      ↓
[hreflang_get_alternate_urls()]
      ↓
[Post/Term Meta] → [URL 對應]
      ↓
[hreflang_output_hreflang()] → <head> 輸出
      ↓
[語言切換器短碼]
```

---

## Hook 系統

### 可用過濾器 (Filters)

#### 1. `hreflang_languages`

修改語言清單

```php
/**
 * 修改語言清單
 * 
 * @param array $languages 語言陣列
 * @return array 修改後的語言陣列
 */
add_filter('hreflang_languages', function($languages) {
    // 動態新增語言
    $languages[] = [
        'code'   => 'fr',
        'locale' => 'fr-FR',
        'domain' => 'www.example.fr',
        'label'  => 'Français',
        'active' => true,
        'order'  => 10
    ];
    return $languages;
});
```

#### 2. `hreflang_alternate_urls`

修改輸出的 URL 列表

```php
/**
 * 修改 URL 列表
 * 
 * @param array  $urls   語言代碼 => URL 的對應陣列
 * @param object $object 當前查詢物件（post/term）
 * @return array 修改後的 URL 陣列
 */
add_filter('hreflang_alternate_urls', function($urls, $object) {
    // 自動從 WPML 取得對應 URL
    if (function_exists('icl_get_languages')) {
        $languages = icl_get_languages('skip_missing=0');
        foreach ($languages as $lang) {
            $urls[$lang['language_code']] = $lang['url'];
        }
    }
    return $urls;
}, 10, 2);
```

#### 3. `hreflang_default_language`

修改預設語言

```php
/**
 * 修改預設語言
 * 
 * @param string $default 預設語言代碼
 * @return string 修改後的語言代碼
 */
add_filter('hreflang_default_language', function($default) {
    return 'zh-Hant';  // 改為繁中
});
```

### 可用動作鉤子 (Actions) - 未來擴展

```php
// 建議在未來版本加入

do_action('hreflang_before_output', $urls);
do_action('hreflang_after_output', $urls);
do_action('hreflang_language_saved', $languages);
do_action('hreflang_settings_saved', $settings);
```

---

## 前端樣式

### CSS 架構

```
assets/css/style.css
├── 下拉選單樣式 (.hreflang-dropdown)
├── 清單樣式 (.hreflang-list)
├── 共用元素
└── 響應式設計 (@media queries)
```

### 樣式命名空間

所有樣式使用 `.hreflang-` 前綴，避免衝突：

```css
/* 主容器 */
.hreflang-lang-switcher {}

/* 下拉選單 */
.hreflang-dropdown {}
.hreflang-lang-select {}

/* 清單樣式 */
.hreflang-list {}
.hreflang-lang-item {}
.hreflang-lang-link {}

/* 狀態 */
.hreflang-lang-item.active {}
```

### 樣式覆寫指南

使用者可透過主題樣式覆寫：

```css
/* 在主題的 style.css 中 */
.hreflang-lang-switcher.custom-class {
    /* 自訂樣式 */
}
```

---

## 安全性規範

### 輸出過濾

**原則**：所有輸出到 HTML 的資料必須過濾

```php
// ✅ URL 輸出
echo esc_url($url);

// ✅ 屬性輸出
echo esc_attr($lang_code);

// ✅ 文字輸出
echo esc_html($label);

// ✅ HTML 內容輸出（謹慎使用）
echo wp_kses_post($content);
```

### 輸入驗證

```php
// ✅ 文字欄位
$code = sanitize_text_field($_POST['code']);

// ✅ URL 欄位
$url = esc_url_raw($_POST['url']);

// ✅ 整數
$order = intval($_POST['order']);

// ✅ 布林值
$active = !empty($_POST['active']);
```

### Nonce 驗證

所有表單提交必須使用 Nonce：

```php
// 產生 Nonce
wp_nonce_field('hreflang_languages_nonce');

// 驗證 Nonce
if (!check_admin_referer('hreflang_languages_nonce')) {
    wp_die('安全驗證失敗');
}
```

### 權限檢查

```php
// ✅ 管理員權限
if (!current_user_can('manage_options')) {
    return;
}

// ✅ 編輯文章權限
if (!current_user_can('edit_post', $post_id)) {
    return;
}
```

---

## 開發流程

### 1. 環境設置

```bash
# 安裝到 WordPress
cd /path/to/wordpress/wp-content/plugins/
git clone <repo-url> hreflang-manager

# 啟用外掛
wp plugin activate hreflang-manager
```

### 2. 開發規範

#### 代碼風格
- 使用 4 空格縮排
- 函式、變數使用小寫蛇形命名
- 類別使用駝峰命名（未來 OOP 重構時）
- 遵循 [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)

#### 註解規範

```php
/**
 * PHPDoc 格式函式註解
 * 
 * 詳細說明函式用途、參數和回傳值
 * 
 * @since 1.0.0
 * @param string $param1 參數說明
 * @param array  $param2 可選參數說明
 * @return bool 回傳值說明
 */
function hreflang_example($param1, $param2 = []) {
    // 行內註解說明邏輯
    return true;
}
```

### 3. 測試流程

#### 手動測試清單

```markdown
- [ ] 安裝/啟用外掛
- [ ] 後台設定頁面
  - [ ] 新增語言
  - [ ] 編輯語言
  - [ ] 刪除語言
  - [ ] 儲存設定
- [ ] 文章/頁面
  - [ ] 填寫多語言 URL
  - [ ] 檢查 ACF 欄位顯示
  - [ ] 檢查後台提示
- [ ] 分類/標籤
  - [ ] 填寫 Term Meta
  - [ ] 檢查編輯頁面
- [ ] 前端輸出
  - [ ] 檢查 <head> 的 hreflang 標籤
  - [ ] 測試短碼顯示
  - [ ] 測試語言切換連結
- [ ] 不同頁面類型
  - [ ] 首頁
  - [ ] 文章
  - [ ] 頁面
  - [ ] 分類頁
  - [ ] 標籤頁
  - [ ] 搜尋頁
  - [ ] Archive 頁
```

### 4. 版本發佈

#### 版本號規則
遵循 [Semantic Versioning](https://semver.org/)：`MAJOR.MINOR.PATCH`

- **MAJOR**：重大架構變更、不向後相容
- **MINOR**：新功能、向後相容
- **PATCH**：Bug 修復

#### 發佈清單

```markdown
1. [ ] 更新 CHANGELOG.md
2. [ ] 更新版本號（外掛主檔案、README）
3. [ ] 完整測試
4. [ ] Git 標籤
5. [ ] 發佈到 WordPress.org（如適用）
```

---

## 擴展指南

### 新增語言偵測方式

在 `nav-shortcode.php` 擴展：

```php
function hreflang_detect_current_language() {
    // 方法 1：根據域名
    // 方法 2：根據 URL 子目錄
    // 方法 3：根據 Cookie
    // 方法 4：根據瀏覽器語言
    
    // 允許自訂偵測邏輯
    return apply_filters('hreflang_detected_language', $detected);
}
```

### 新增頁面類型支援

在 `helpers.php` 的 `hreflang_get_alternate_urls()` 中擴展：

```php
elseif (is_post_type_archive('product')) {
    // 處理產品 archive
}
elseif (is_author()) {
    // 處理作者頁面
}
```

### 整合第三方外掛

#### 範例：整合 WPML

```php
add_filter('hreflang_alternate_urls', function($urls, $object) {
    if (!function_exists('icl_get_languages')) {
        return $urls;
    }
    
    $languages = icl_get_languages('skip_missing=0');
    foreach ($languages as $lang) {
        $urls[$lang['language_code']] = $lang['url'];
    }
    
    return $urls;
}, 10, 2);
```

#### 範例：整合 Polylang

```php
add_filter('hreflang_alternate_urls', function($urls, $object) {
    if (!function_exists('pll_get_post_translations')) {
        return $urls;
    }
    
    if (is_singular()) {
        $translations = pll_get_post_translations(get_the_ID());
        foreach ($translations as $lang_code => $post_id) {
            $urls[$lang_code] = get_permalink($post_id);
        }
    }
    
    return $urls;
}, 10, 2);
```

### 新增短碼樣式

在 `nav-shortcode.php` 擴展：

```php
function hreflang_switcher_shortcode($atts) {
    // 新增 'style' 選項
    if ($atts['style'] === 'flags') {
        // 顯示國旗圖示
    }
    elseif ($atts['style'] === 'buttons') {
        // 顯示按鈕樣式
    }
}
```

### 建立管理 API（未來版本）

```php
// 類別架構建議
class Hreflang_Manager_API {
    public function get_languages() {}
    public function add_language($lang_data) {}
    public function update_language($code, $lang_data) {}
    public function delete_language($code) {}
}

// REST API 端點
add_action('rest_api_init', function() {
    register_rest_route('hreflang-manager/v1', '/languages', [
        'methods' => 'GET',
        'callback' => 'hreflang_api_get_languages',
    ]);
});
```

---

## 最佳實踐

### 效能優化

```php
// ✅ 快取語言清單
$languages = wp_cache_get('hreflang_languages');
if (false === $languages) {
    $languages = get_option('hreflang_languages');
    wp_cache_set('hreflang_languages', $languages, '', 3600);
}

// ✅ 只在需要時載入資源
add_action('wp_enqueue_scripts', function() {
    if (has_shortcode(get_the_content(), 'hreflang_switcher')) {
        wp_enqueue_style('hreflang-hreflang-switcher');
    }
});
```

### 錯誤處理

```php
// ✅ 優雅的錯誤處理
function hreflang_get_alternate_urls() {
    try {
        $urls = calculate_urls();
        return apply_filters('hreflang_alternate_urls', $urls);
    } catch (Exception $e) {
        error_log('Hreflang Manager Error: ' . $e->getMessage());
        return [];
    }
}
```

### 向後相容

```php
// ✅ 檢查函式是否存在
if (!function_exists('hreflang_old_function')) {
    function hreflang_old_function() {
        _deprecated_function(__FUNCTION__, '1.1.0', 'hreflang_new_function');
        return hreflang_new_function();
    }
}
```

---

## 疑難排解

### 常見問題

**Q: Hreflang 標籤沒有顯示？**
```php
// 檢查點：
1. 語言是否已啟用
2. 是否填寫了對應 URL
3. hreflang_get_alternate_urls() 是否回傳空陣列
4. 是否有其他外掛干擾 wp_head
```

**Q: 語言切換器沒有顯示？**
```php
// 檢查點：
1. 短碼拼寫是否正確
2. 當前頁面是否有多語言 URL
3. CSS 檔案是否正確載入
4. 是否有 JavaScript 錯誤
```

### 除錯模式

```php
// 啟用除錯輸出
define('HREFLANG_DEBUG', true);

function hreflang_debug($message) {
    if (defined('HREFLANG_DEBUG') && HREFLANG_DEBUG) {
        error_log('Hreflang Manager: ' . print_r($message, true));
    }
}
```

---

## 未來規劃

### v1.1 計劃功能
- [ ] Block 編輯器元件
- [ ] 語言切換器 Widget
- [ ] 國旗圖示支援
- [ ] 更多切換器樣式

### v1.2 計劃功能
- [ ] XML Sitemap hreflang 支援
- [ ] URL 自動偵測與建議
- [ ] 批次匯入/匯出功能

### v1.3 計劃功能
- [ ] 404 檢查工具
- [ ] URL 驗證與自動修正
- [ ] 效能監控儀表板

### v2.0 長期目標
- [ ] OOP 重構
- [ ] WP-CLI 支援
- [ ] GraphQL API
- [ ] 完整單元測試覆蓋
- [ ] WPML/Polylang 深度整合

---

## 參考資源

### WordPress 開發
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Hook Reference](https://developer.wordpress.org/reference/hooks/)

### Hreflang 規範
- [Google Hreflang 指南](https://developers.google.com/search/docs/advanced/crawling/localized-versions)
- [Hreflang 最佳實務](https://moz.com/learn/seo/hreflang-tag)

### SEO 參考
- [International SEO Guide](https://www.sistrix.com/international-seo/)
- [Hreflang Implementation](https://yoast.com/hreflang-ultimate-guide/)

---

## 貢獻指南

### 提交代碼

1. Fork 專案
2. 建立功能分支：`git checkout -b feature/new-feature`
3. 遵循本文件的規範
4. 提交變更：`git commit -m 'Add new feature'`
5. 推送分支：`git push origin feature/new-feature`
6. 提交 Pull Request

### 報告問題

提交 Issue 時請包含：
- WordPress 版本
- PHP 版本
- 外掛版本
- 錯誤訊息
- 重現步驟

---

## 授權與聯繫

**授權**：GPL-2.0-or-later  
**作者**：CHUANG,HSIN-HSUEH  
**郵箱**：shawen66@gmail.com  
**GitHub**：https://github.com/shawen66/hreflang-manager

---

**文件版本**：1.0.0  
**最後更新**：2026-01-21
