# 外掛資訊

## 專案簡介

本專案為一個 WordPress 外掛，用於管理多語言網站的 hreflang 標籤與語言切換功能。

## 外掛資訊

### 外掛名稱
### 外掛名稱
- **名稱**: Hreflang Manager & Language Switcher

### 函式前綴
- **前綴**: `hreflang_`

### Text Domain
- **值**: `hreflang-manager`

### CSS 類別前綴
- **前綴**: `hreflang-`

### 短碼名稱
- **短碼**: `[hreflang_switcher]`

### 資料庫選項鍵
- **鍵值**: `hreflang_*`

### 過濾器 Hooks
### 過濾器 Hooks
- `hreflang_languages`
- `hreflang_alternate_urls`
- `hreflang_default_language`

### Composer 套件名稱
- **名稱**: `hreflang-manager/wordpress-plugin`

### 作者資訊
- **作者**: CHUANG,HSIN-HSUEH
- **Email**: shawen66@gmail.com
- **GitHub**: https://github.com/Shawn66168/hreflang-manager

## 已更新的檔案

## 已更新的檔案

### PHP 核心檔案（6 個）
- [x] hreflang-switch.php (主外掛檔案)
- [x] src/helpers.php
- [x] src/hreflang-core.php
- [x] src/nav-shortcode.php
- [x] src/admin-notice.php
- [x] src/admin-settings.php

### 樣式檔案（1 個）
- [x] assets/css/style.css

### 文件檔案（7 個）
- [x] README.md
- [x] INSTALLATION.md
- [x] QUICKSTART.md
- [x] EXAMPLES.md
- [x] CHANGELOG.md
- [x] PROJECT_SUMMARY.md
- [x] composer.json

### 新增檔案（2 個）
- [x] RENAMING.md - 資料夾重新命名指南
- [x] DEIDENTIFIED.md - 本文件

## 驗證結果

✅ 無 PHP 語法錯誤
✅ 所有函式前綴已統一為 `hreflang_`
✅ CSS 類別名稱已更新
✅ 短碼名稱已簡化
✅ 文件已全面更新

## 建議的後續步驟

### 1. 安裝外掛
1. 將資料夾上傳到 `wp-content/plugins/`
2. 在 WordPress 後台啟用外掛
3. 前往「設定 → Hreflang Languages」進行設定

### 基本使用
- 設定語言（後台設定頁面）
- 在文章/頁面填寫各語言對應 URL
- 使用短碼 `[hreflang_switcher]` 顯示語言切換器

### 完整文件
- [README.md](README.md) - 專案主文件
- [INSTALLATION.md](INSTALLATION.md) - 詳細安裝指南
- [QUICKSTART.md](QUICKSTART.md) - 5 分鐘快速開始
- [EXAMPLES.md](EXAMPLES.md) - 10 個實用範例

## 授權

本專案採用 GPL-2.0-or-later 授權。

---

**最後更新日期**: 2026-01-21  
**版本**: 1.0.0  
**狀態**: ✅ 已完成
