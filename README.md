# Hreflang Manager & Language Switcher

## 1. Purpose

建立一個 WordPress 外掛，用於多語言站點 SEO 的 hreflang 輸出與語言切換 UI。 
適用在擁有多個網域但不同語系的WordPress網站的網站。 
全站所有頁面（文章、頁面、分類、標籤、搜尋、分頁、archive 、產品、產品分類）都會輸出 hreflang。

---

* * * * *

## 2. Requirements

### 2.1 功能需求

| 功能 | 必要 | 描述 |
|------|------|------|
| 全站 hreflang 輸出 | ✅ | 所有頁面正確輸出 hreflang |
| 語言切換 UI | ✅ | 前端語言切換按鈕 Shortcode |
| 多語言管理 | ✅ | 後台可新增/編輯語言 |
| ACF URL 對照 | ✅ | 文章/Term 對應 URL |
| 缺漏提醒 | ✅ | 後台提示未填對應欄位 |
| 可擴充語言 | ✅ | 支援新增語言 |

---

* * * * *

## 3. Plugin Architecture

```
hreflang-manager/
├─ hreflang-switch.php          # 主外掛檔案
├─ composer.json                 # Composer 配置
├─ .skills/
│  └─ ARCHITECTURE.md            # 架構規範文件
├─ src/
│  ├─ helpers.php                # 工具函式
│  ├─ hreflang-core.php         # Hreflang 輸出邏輯
│  ├─ nav-shortcode.php         # 語言切換短碼
│  ├─ admin-notice.php          # 後台提示系統
│  └─ admin-settings.php        # 設定頁面
└─ assets/
   └─ css/
      └─ style.css               # 語言切換器樣式
```

---

# Hreflang Manager & Language Switcher

本專案為 WordPress 外掛，用於在多語言站點正確輸出 hreflang 標籤與提供前端語言切換介面。此文件整理使用、開發與架構資訊，方便維護與擴充。

## 目錄
- [目的](#目的)
- [特色](#特色)
- [系統需求](#系統需求)
- [安裝](#安裝)
- [使用範例](#使用範例)
- [資料模型與對應](#資料模型與對應)
- [目錄結構](#目錄結構)
- [勾點（Hooks）與過濾器](#勾點hooks與過濾器)
- [安全性與國際化](#安全性與國際化)
- [開發與貢獻](#開發與貢獻)
- [授權](#授權)
- [路線圖](#路線圖)

## 目的
建立一個可擴充的 hreflang 管理外掛，支援：
- 在所有頁面（文章、頁面、分類、搜尋、archive 等）輸出 hreflang
- 透過短碼或元件顯示前端語言切換
- 以後台資料或欄位（例如 ACF）維護各語系對應 URL

## 特色
- 自動在 `head` 輸出 `rel="alternate" hreflang="..."` 標籤
- 可透過短碼插入語言切換 UI
- 可擴充語言、支援多域名或子目錄型多語站點

## 系統需求
- PHP 7.4+
- WordPress 5.0+

## 安裝
1. 將整個資料夾放入 `wp-content/plugins/`。
2. 在 WordPress 後台啟用外掛。
3. 前往設定頁面（若已實作）設定語言資料。

或以 git 開發流程：

```bash
git clone https://github.com/Shawn66168/hreflang-manager.git
cd hreflang-manager
# 將資料夾放入 WordPress plugins 中並啟用
```

## 使用範例
- 在主題或頁面中，外掛會在 `<head>` 中輸出 hreflang（透過 `add_action('wp_head', ...)`）。
- 若要顯示語言切換短碼（示例）:

```html
[hreflang_switcher class="my-switcher"]
```

輸出範例：

```html
<link rel="alternate" hreflang="en" href="https://www.example.com/en/page/" />
<link rel="alternate" hreflang="zh-Hant" href="https://www.example.com/zh-hant/page/" />
<link rel="alternate" hreflang="x-default" href="https://www.example.com/" />
```

輸出範例：

```html
<link rel="alternate" hreflang="en" href="https://www.example.com/en/page/" />
<link rel="alternate" hreflang="zh-Hant" href="https://www.example.com/zh-hant/page/" />
<link rel="alternate" hreflang="x-default" href="https://www.example.com/" />
```

## 資料模型與對應
建議的語言資料結構範例：

```json
{
  "languages": [
    {"code":"en","locale":"en-US","domain":"example.com","label":"English","active":true},
    {"code":"zh-Hant","locale":"zh-Hant","domain":"example.com","label":"繁體中文","active":true}
  ],
  "default_lang": "en"
}
```

URL 對應建議：文章/頁面可使用 ACF 或 post meta 儲存替代 URL（例如 `alt_en_url`、`alt_zh-Hant_url`）；taxonomy 使用 term meta（例如 `term_alt_en_url`）。若未提供對應 URL，對應語系的 hreflang 不輸出。

## 目錄結構
範例來源檔位於 `src/`：

- `hreflang-manager.php` — 外掛註冊與初始化
- `src/hreflang-core.php` — 產生 hreflang 的核心邏輯
- `src/nav-shortcode.php` — 短碼/語言切換 UI
- `src/helpers.php` — 工具函式
- `src/admin-notice.php` — 後台提示
- `assets/css/style.css` — 樣式

## 勾點（Hooks）與過濾器
- `apply_filters('hreflang_languages', $languages)` — 調整語言清單
- `apply_filters('hreflang_alternate_urls', $urls, $object)` — 修改輸出 URL 列表

## 安全性與國際化
- 對輸出 URL 使用 `esc_url()`，對屬性使用 `esc_attr()`。
- 請使用 `load_plugin_textdomain()` 加入翻譯支援。

## 開發與貢獻

### 開發環境
- 建議在本機建立 WordPress 測試環境（Local、Docker 等）
- PHP 7.4+
- WordPress 5.0+

### 參考文件
- [架構規範文件](.skills/ARCHITECTURE.md) - 詳細的開發規範與指南
- [快速開始](QUICKSTART.md) - 5 分鐘快速設定
- [安裝指南](INSTALLATION.md) - 詳細安裝步驟

### 貢獻流程
1. Fork 本專案
2. 建立功能分支 (`git checkout -b feature/amazing-feature`)
3. 遵循 `.skills/ARCHITECTURE.md` 中的開發規範
4. 提交變更 (`git commit -m 'Add amazing feature'`)
5. 推送到分支 (`git push origin feature/amazing-feature`)
6. 開啟 Pull Request

## 授權
本專案採用 GPL-2.0-or-later 授權。

## 作者
- **CHUANG,HSIN-HSUEH**
- Email: shawen66@gmail.com
- GitHub: https://github.com/Shawn66168/hreflang-manager

## 路線圖
- v1.1: Block/區塊型語言切換元件
- v1.2: Sitemap hreflang 支援
- v1.3: 自動檢查缺漏（例如 404）

---

若需我幫你：實作後台設定介面、短碼參數、或加入測試與 CI，請告訴我優先順序。 
* * * * *


