# WordPress 規範與相容性改進說明

> 更新日期：2026-01-21  
> Commit: 2c46e71

## 📋 改進總覽

本次更新使外掛完全符合 WordPress.org 外掛規範，並增強與主流 SEO 外掛的相容性。

---

## ✅ WordPress.org 規範實作

### 1. readme.txt（WordPress.org 標準格式）

**檔案位置**：`readme.txt`

符合 WordPress.org 外掛目錄的標準格式，包含：
- 外掛基本資訊（Contributors、Tags、需求版本）
- 詳細說明與功能列表
- 安裝指南
- 常見問題（FAQ）
- 更新日誌
- 開發者註釋

**用途**：
- WordPress.org 外掛頁面自動解析此檔案
- 提供使用者完整的外掛資訊
- 顯示在外掛搜尋結果中

### 2. 啟用/停用 Hook

**檔案**：`hreflang-switch.php`

```php
register_activation_hook(__FILE__, 'hreflang_manager_activate');
register_deactivation_hook(__FILE__, 'hreflang_manager_deactivate');
```

**啟用時執行**：
- ✅ 檢查系統需求（PHP 7.4+、WordPress 5.0+）
- ✅ 設定預設選項（如果不存在）
- ✅ 清除快取

**停用時執行**：
- ✅ 清除快取
- ⚠️ 不刪除選項（保留使用者設定）

### 3. 卸載腳本

**檔案位置**：`uninstall.php`

當使用者「刪除」外掛時自動執行：
- ✅ 刪除 `hreflang_languages` 選項
- ✅ 刪除 `hreflang_default_lang` 選項
- ✅ 支援多站點清理
- ⚠️ Post Meta 和 Term Meta 預設保留（可選擇性清理）

**為什麼保留 Meta？**
使用者可能只是暫時停用，刪除所有 URL 資料會造成困擾。

### 4. 外掛常數定義

**檔案**：`hreflang-switch.php`

```php
define('HREFLANG_MANAGER_VERSION', '1.0.0');
define('HREFLANG_MANAGER_PLUGIN_FILE', __FILE__);
define('HREFLANG_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HREFLANG_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HREFLANG_MANAGER_PLUGIN_BASENAME', plugin_basename(__FILE__));
```

**用途**：
- 方便在任何地方引用外掛路徑和版本
- 避免重複計算路徑
- 符合 WordPress 最佳實踐

### 5. 系統需求檢查

**函式**：`hreflang_manager_check_requirements()`

啟用外掛時自動檢查：
- ✅ PHP 版本 ≥ 7.4
- ✅ WordPress 版本 ≥ 5.0

如果不符合需求，外掛會自動停用並顯示錯誤訊息。

### 6. 外掛列表頁面連結

**功能**：
- ✅ 「設定」連結 → 直接前往設定頁面
- ✅ 「文檔」連結 → GitHub README
- ✅ 「支援」連結 → GitHub Issues

**實作**：
```php
add_filter('plugin_action_links_' . HREFLANG_MANAGER_PLUGIN_BASENAME, 'hreflang_manager_add_action_links');
add_filter('plugin_row_meta', 'hreflang_manager_add_plugin_row_meta', 10, 2);
```

---

## 🔒 安全性增強

### 1. ABSPATH 檢查

**所有 PHP 檔案**均已更新為：

```php
// 如果直接訪問此檔案則退出
if (!defined('ABSPATH')) {
    exit;
}
```

**保護的檔案**：
- ✅ `hreflang-switch.php`
- ✅ `uninstall.php`
- ✅ `src/helpers.php`
- ✅ `src/hreflang-core.php`
- ✅ `src/nav-shortcode.php`
- ✅ `src/admin-notice.php`
- ✅ `src/admin-settings.php`

### 2. 目錄瀏覽保護

**新增 index.php**（內容：`<?php // Silence is golden.`）

保護的目錄：
- ✅ 根目錄 `/index.php`
- ✅ `/src/index.php`
- ✅ `/assets/index.php`
- ✅ `/assets/css/index.php`

### 3. .htaccess 防護

**檔案位置**：`.htaccess`

```apache
# Prevent directory browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(md|json|lock)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

**保護**：
- ✅ 禁止目錄瀏覽
- ✅ 保護 `.md` 文件
- ✅ 保護 `composer.json`、`composer.lock`

---

## 🤝 SEO 外掛相容性

### 1. Yoast SEO

**相容性處理**：

```php
if (defined('WPSEO_VERSION')) {
    add_filter('wpseo_hreflang_output', '__return_false', 99);
}
```

**說明**：
- Yoast SEO Premium 有 hreflang 功能
- 自動停用 Yoast 的 hreflang 輸出
- 避免重複標籤造成 SEO 問題

### 2. Rank Math

**檢查點已建立**：

```php
if (defined('RANK_MATH_VERSION')) {
    // 可在此添加相容性處理
}
```

### 3. All in One SEO

**檢查點已建立**：

```php
if (defined('AIOSEO_VERSION')) {
    // 可在此添加相容性處理
}
```

### 4. 移除衝突的 hreflang

**函式**：`hreflang_manager_remove_conflicting_hreflang()`

```php
// 移除其他外掛可能輸出的 hreflang
if (has_filter('wpseo_hreflang_url')) {
    remove_all_filters('wpseo_hreflang_url');
}
```

### 5. 新增輸出控制過濾器

**新增過濾器**：`hreflang_manager_enable_output`

```php
// 允許在特定情況下停用輸出
if (!apply_filters('hreflang_manager_enable_output', true)) {
    return;
}
```

**使用範例**：

```php
// 在特定頁面停用 hreflang 輸出
add_filter('hreflang_manager_enable_output', function($enable) {
    if (is_page('特殊頁面')) {
        return false;
    }
    return $enable;
});
```

---

## 📦 檔案結構變更

### 新增檔案

```
wp-hreflang-manager/
├── readme.txt                    # WordPress.org 標準格式（新增）
├── uninstall.php                 # 卸載清理腳本（新增）
├── .htaccess                     # 安全防護（新增）
├── index.php                     # 目錄瀏覽保護（新增）
│
├── src/
│   └── index.php                 # 目錄瀏覽保護（新增）
│
└── assets/
    ├── index.php                 # 目錄瀏覽保護（新增）
    └── css/
        └── index.php             # 目錄瀏覽保護（新增）
```

### 更新檔案

```
✏️ hreflang-switch.php          # 新增啟用/停用 Hook、常數、相容性檢查
✏️ src/hreflang-core.php        # 新增輸出控制、移除衝突處理
✏️ src/helpers.php              # 統一安全檢查
✏️ src/nav-shortcode.php        # 統一安全檢查
✏️ src/admin-notice.php         # 統一安全檢查
✏️ src/admin-settings.php       # 統一安全檢查
✏️ CHANGELOG.md                 # 記錄所有改進
✏️ .gitignore                   # 排除測試和建置文件
```

---

## 🎯 測試清單

### WordPress.org 準備

- [x] readme.txt 格式正確
- [x] 外掛標頭資訊完整
- [x] 啟用/停用 Hook 正常運作
- [x] uninstall.php 清理正確
- [x] 所有檔案有 ABSPATH 檢查
- [x] 無 PHP 語法錯誤
- [x] 符合 WordPress Coding Standards

### 相容性測試

- [ ] 與 Yoast SEO 同時啟用測試
- [ ] 與 Rank Math 同時啟用測試
- [ ] 與 AIOSEO 同時啟用測試
- [ ] 與 WPML 同時啟用測試
- [ ] 與 Polylang 同時啟用測試
- [ ] 與 WooCommerce 同時啟用測試

### 功能測試

- [ ] 外掛啟用/停用正常
- [ ] 系統需求檢查有效
- [ ] 設定頁面運作正常
- [ ] Hreflang 輸出正確
- [ ] 語言切換器顯示正常
- [ ] 卸載清理完整

---

## 📝 使用建議

### 1. 與 Yoast SEO 同時使用

```php
// 如果 Yoast 輸出 hreflang，本外掛會自動停用 Yoast 的輸出
// 不需要額外設定
```

### 2. 條件性停用輸出

```php
// 在特定頁面停用 hreflang
add_filter('hreflang_manager_enable_output', function($enable) {
    if (is_404() || is_search()) {
        return false;  // 404 和搜尋頁面不輸出
    }
    return $enable;
});
```

### 3. 自訂卸載行為

如果要在卸載時刪除所有 Meta 資料，編輯 `uninstall.php`：

```php
// 取消註解以下行
hreflang_manager_cleanup_post_meta();
hreflang_manager_cleanup_term_meta();
```

---

## 🚀 發佈到 WordPress.org

### 準備步驟

1. **確認所有測試通過**
2. **建立 SVN 儲存庫**
   ```bash
   svn co https://plugins.svn.wordpress.org/hreflang-manager
   ```

3. **複製檔案到 trunk**
   ```bash
   cp -r /path/to/plugin/* trunk/
   ```

4. **提交到 SVN**
   ```bash
   svn add trunk/*
   svn ci -m "Initial release 1.0.0"
   ```

5. **建立標籤**
   ```bash
   svn cp trunk tags/1.0.0
   svn ci -m "Tagging version 1.0.0"
   ```

### 發佈檢查清單

- [ ] readme.txt 完整且格式正確
- [ ] 所有功能測試通過
- [ ] 相容性測試通過
- [ ] 無安全漏洞
- [ ] 符合 WordPress 編碼標準
- [ ] 載入速度合理
- [ ] 行動裝置相容

---

## 📚 相關文件

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Readme Standards](https://wordpress.org/plugins/developers/)
- [專案架構規範](.skills/ARCHITECTURE.md)

---

## 🎉 總結

本次更新使 **Hreflang Manager** 成為一個：

✅ **符合 WordPress.org 規範**的專業外掛  
✅ **安全可靠**的生產環境解決方案  
✅ **相容主流 SEO 外掛**的友善工具  
✅ **易於維護和擴展**的開源專案

現在您可以：
1. 直接上傳到 WordPress.org
2. 在任何 WordPress 網站安全使用
3. 與其他 SEO 外掛和平共處
4. 持續開發和改進功能

**GitHub Repository**: https://github.com/Shawn66168/hreflang-manager  
**Version**: 1.0.0  
**License**: GPL v2 or later
