# Hreflang Manager & Language Switcher

WordPress 多語言 SEO 外掛，自動輸出 hreflang 標籤並提供語言切換功能。

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/Shawn66168/hreflang-manager)
[![License: GPLv2 or later](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-8892BF.svg)](https://php.net)

## 核心功能

✅ **自動輸出 hreflang** - 所有頁面類型（文章、頁面、分類、標籤、archive）  
✅ **語言切換器** - 短碼 `[hreflang_switcher]` 支援下拉選單和清單樣式  
✅ **後台管理** - 友善的語言設定介面  
✅ **SEO 整合** - 與 Yoast SEO、Rank Math 相容  
✅ **開發友善** - 完整的 Hook 系統與過濾器

## 快速開始

### 安裝

```bash
git clone https://github.com/Shawn66168/hreflang-manager.git
# 將資料夾放入 wp-content/plugins/ 並在後台啟用
```

### 基本使用

1. 前往「設定」→「Hreflang Languages」新增語言
2. 在文章編輯頁面填寫各語言對應 URL
3. 使用短碼顯示語言切換器：

```php
[hreflang_switcher style="dropdown"]
```

## 文件

📘 **[完整安裝指南](INSTALLATION.md)** - 詳細的安裝與設定步驟  
📗 **[快速開始](QUICKSTART.md)** - 5 分鐘快速設定  
📙 **[使用範例](EXAMPLES.md)** - 更多實際應用案例  
📕 **[開發規範](.skills/ARCHITECTURE.md)** - 架構設計與開發指南  
📄 **[WordPress.org 說明](readme.txt)** - 完整插件說明文檔

## 系統需求

- PHP 7.4+
- WordPress 5.0+

## 授權

GPL-2.0-or-later

## 作者

**CHUANG,HSIN-HSUEH**  
📧 shawen66@gmail.com  
🐙 [GitHub](https://github.com/Shawn66168/hreflang-manager)


