# 安裝與設定指南

## 安裝步驟

### 方法 1：手動安裝

1. 下載或複製整個 `hreflang-manager` 資料夾
2. 上傳到您的 WordPress 網站的 `wp-content/plugins/` 目錄
3. 登入 WordPress 後台
4. 前往「外掛」頁面
5. 找到「Hreflang Manager & Language Switcher」並點擊「啟用」

### 方法 2：開發環境安裝

```bash
cd wp-content/plugins/
git clone <repository-url> hreflang-manager
cd hreflang-manager
composer install  # 如果需要使用 Composer 依賴
```

然後在 WordPress 後台啟用外掛。

## 初始設定

### 1. 設定語言

1. 登入 WordPress 後台
2. 前往「設定」→「Hreflang Languages」
3. 點擊「新增語言」按鈕
4. 填寫語言資訊：
   - **語言代碼**：如 `en`、`zh-Hant`、`es-MX`
   - **Locale**：如 `en-US`、`zh-Hant`
   - **Domain**：該語言版本的域名，如 `www.example.com`
   - **顯示名稱**：如「English」、「繁體中文」
   - **啟用**：勾選以啟用該語言
   - **順序**：控制語言切換器中的顯示順序
5. 設定預設語言代碼（用於 `x-default` hreflang）
6. 點擊「儲存設定」

### 2. 設定文章/頁面的語言對應 URL

#### 如果您使用 ACF (Advanced Custom Fields)：

1. 外掛會自動建立「Hreflang 多語言 URL」欄位群組
2. 在編輯文章或頁面時，在側邊欄找到該欄位群組
3. 為每個語言填寫對應的 URL

#### 如果不使用 ACF：

您需要手動為文章新增自訂欄位：
- 欄位名稱格式：`alt_{語言代碼}_url`
- 例如：`alt_en_url`、`alt_zh-Hant_url`

### 3. 設定分類/標籤的語言對應 URL

1. 前往「文章」→「分類」（或「標籤」）
2. 點擊要編輯的分類/標籤
3. 向下捲動找到「Hreflang 多語言 URL」區塊
4. 為每個語言填寫對應的 URL
5. 點擊「更新」

## 前端使用

### 自動輸出 hreflang

外掛會自動在所有頁面的 `<head>` 中輸出 hreflang 標籤，無需額外設定。

### 顯示語言切換器

在文章、頁面或小工具中使用短碼：

```
[hreflang_switcher]
```

#### 短碼參數

- `class`：自訂 CSS class
- `style`：`dropdown`（下拉選單，預設）或 `list`（清單）
- `show_flags`：是否顯示國旗圖示（尚未實作）

範例：

```
[hreflang_switcher style="list" class="my-custom-class"]
```

### 在主題中使用

您也可以在主題範本檔案中直接呼叫：

```php
<?php
if (function_exists('hreflang_switcher_shortcode')) {
    echo hreflang_switcher_shortcode(['style' => 'list']);
}
?>
```

## 自訂樣式

外掛包含基本的 CSS 樣式（位於 `assets/css/style.css`）。

您可以在主題的 `style.css` 或自訂 CSS 中覆寫這些樣式：

```css
/* 自訂語言切換器樣式 */
.-lang-switcher.-list {
    background: #f5f5f5;
    padding: 10px;
}

.-lang-link {
    color: #333;
    font-weight: bold;
}
```

## 疑難排解

### hreflang 標籤沒有顯示

1. 確認已在後台設定頁面新增並啟用語言
2. 檢查文章/頁面是否已填寫對應語言的 URL
3. 查看頁面原始碼，確認 `<head>` 中是否有 `<!-- Hreflang Manager -->` 註解

### 語言切換器沒有顯示

1. 確認已正確使用短碼
2. 確認當前頁面有填寫至少 2 個語言的對應 URL
3. 檢查瀏覽器控制台是否有 JavaScript 錯誤

### 後台提示缺少語言 URL

這是正常的提醒功能。填寫完所有語言的對應 URL 後，提示會自動消失。

## 進階設定

### 使用過濾器

您可以在主題的 `functions.php` 中使用過濾器自訂功能：

```php
// 修改語言清單
add_filter('hreflang_languages', function($languages) {
    // 自訂邏輯
    return $languages;
});

// 修改輸出的 URL
add_filter('hreflang_alternate_urls', function($urls, $object) {
    // 自訂邏輯
    return $urls;
}, 10, 2);
```

## 需要協助？

如有問題或建議，請：
1. 查看 [README.md](README.md) 文件
2. 提交 Issue 到專案儲存庫
3. 聯繫開發團隊
