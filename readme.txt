=== Hreflang Manager & Language Switcher ===
Contributors: shawen66
Tags: hreflang, seo, multilingual, language switcher, international seo
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

完整的 hreflang 標籤管理與語言切換功能，支援多域名、子目錄等多語言架構。

== Description ==

Hreflang Manager 是一個專業的多語言 SEO 外掛，自動為您的網站輸出正確的 hreflang 標籤，並提供友善的語言切換介面。

### 核心功能

* **自動輸出 hreflang 標籤** - 在所有頁面類型（文章、頁面、分類、標籤、搜尋、archive）自動輸出
* **語言切換器** - 提供短碼 `[hreflang_switcher]`，支援下拉選單和清單兩種樣式
* **後台管理介面** - 友善的語言設定管理頁面
* **ACF 整合** - 自動建立多語言 URL 欄位
* **Term Meta 支援** - 分類、標籤等皆可設定多語言 URL
* **後台提醒系統** - 自動提示缺少的語言 URL
* **過濾器系統** - 提供完整的 Hook 讓開發者擴展功能

### 適用情境

* 多域名多語言網站（如 example.com、example.tw）
* 子目錄多語言網站（如 example.com/en/、example.com/zh/）
* 混合型多語言架構

### 相容性

* ✅ 與 Yoast SEO、Rank Math 等 SEO 外掛相容
* ✅ 可與 WPML、Polylang 整合使用
* ✅ 支援 WooCommerce 產品與分類
* ✅ 支援所有自訂文章類型和分類法

== Installation ==

### 自動安裝

1. 在 WordPress 後台前往「外掛」→「安裝外掛」
2. 搜尋「Hreflang Manager」
3. 點擊「立即安裝」
4. 啟用外掛

### 手動安裝

1. 下載外掛檔案
2. 解壓縮並上傳至 `/wp-content/plugins/` 目錄
3. 在 WordPress 後台啟用外掛
4. 前往「設定」→「Hreflang Languages」進行設定

### 初始設定

1. 前往「設定」→「Hreflang Languages」
2. 新增您的語言版本（語言代碼、域名、顯示名稱）
3. 設定預設語言
4. 在文章/頁面編輯時填寫各語言的對應 URL
5. 使用短碼 `[hreflang_switcher]` 在前端顯示語言切換器

== Frequently Asked Questions ==

= 如何使用語言切換器？ =

在任何頁面、文章或小工具中加入短碼：

`[hreflang_switcher style="dropdown"]` - 下拉選單樣式
`[hreflang_switcher style="list"]` - 清單樣式

= 如何檢查 hreflang 標籤是否正確輸出？ =

1. 前往您的網站前台
2. 按 F12 開啟開發者工具
3. 查看 `<head>` 區塊，應該會看到 `<link rel="alternate" hreflang="...">`

= 與 Yoast SEO 會衝突嗎？ =

不會。本外掛專注於 hreflang 標籤輸出，不會干擾 Yoast SEO 的其他功能。兩者可以同時使用。

= 支援 WooCommerce 嗎？ =

是的。外掛支援 WooCommerce 產品和產品分類的 hreflang 標籤輸出。

= 可以自動偵測對應 URL 嗎？ =

如果您使用 WPML 或 Polylang，可以透過過濾器自動取得對應 URL。詳情請參考文檔。

= 如何為分類/標籤設定多語言 URL？ =

在編輯分類或標籤時，會看到「Hreflang 多語言 URL」欄位，直接填寫即可。

== Screenshots ==

1. 後台語言設定頁面
2. 文章編輯頁面的多語言 URL 欄位
3. 前端語言切換器（下拉選單樣式）
4. 前端語言切換器（清單樣式）

== Changelog ==

= 1.0.0 =
* 首次發布
* 自動 hreflang 標籤輸出
* 語言切換器短碼
* 後台語言管理介面
* ACF 整合
* Term Meta 支援
* 後台缺漏提醒

== Upgrade Notice ==

= 1.0.0 =
首次發布版本。

== Developer Notes ==

### 可用過濾器

**hreflang_languages** - 修改語言清單
`add_filter('hreflang_languages', function($languages) { return $languages; });`

**hreflang_alternate_urls** - 修改輸出的 URL 列表
`add_filter('hreflang_alternate_urls', function($urls, $object) { return $urls; }, 10, 2);`

**hreflang_default_language** - 修改預設語言
`add_filter('hreflang_default_language', function($default) { return $default; });`

### GitHub

專案開源於 GitHub：https://github.com/Shawn66168/hreflang-manager

完整文檔和範例請參考 GitHub Repository。
