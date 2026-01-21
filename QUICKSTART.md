# 快速開始指南

## 5 分鐘快速設定

### 步驟 1：啟用外掛
1. 將 `hreflang-manager` 資料夾上傳到 `wp-content/plugins/`
2. 在 WordPress 後台啟用外掛

### 步驟 2：設定語言（2 分鐘）
1. 前往「設定」→「Hreflang Languages」
2. 新增您的語言，例如：

| 語言代碼 | Locale | Domain | 顯示名稱 | 啟用 |
|---------|--------|--------|---------|------|
| en | en-US | www.example.com | English | ✓ |
| zh-Hant | zh-Hant | www.example.tw | 繁體中文 | ✓ |
| zh-Hans | zh-Hans | www.example.cn | 简体中文 | ✓ |

3. 設定預設語言為 `en`
4. 點擊「儲存設定」

### 步驟 3：填寫文章對應 URL（1 分鐘）
1. 編輯任一文章或頁面
2. 在側邊欄找到「Hreflang 多語言 URL」
3. 填寫每個語言的對應 URL：
   - English URL: `https://www.example.com/about/`
   - 繁體中文 URL: `https://www.example.tw/about/`
   - 简体中文 URL: `https://www.example.cn/about/`

### 步驟 4：檢查前端輸出（1 分鐘）
1. 前往剛才編輯的頁面
2. 按 F12 打開開發者工具
3. 查看 `<head>` 區塊，應該看到：

```html
<!-- Hreflang Manager -->
<link rel="alternate" hreflang="en" href="https://www.example.com/about/" />
<link rel="alternate" hreflang="zh-Hant" href="https://www.example.tw/about/" />
<link rel="alternate" hreflang="zh-Hans" href="https://www.example.cn/about/" />
<!-- /Hreflang Manager -->
```

### 步驟 5：新增語言切換器（1 分鐘）
在任何頁面、文章或小工具中加入：

```
[hreflang_switcher style="list"]
```

完成！🎉

## 常見使用情境

### 情境 1：多域名多語站點
適用於不同語言使用不同域名的網站。

設定範例：
- 英文：`www.example.com`
- 繁中：`www.example.tw`
- 簡中：`www.example.cn`

### 情境 2：子目錄多語站點
適用於使用子目錄區分語言的網站。

設定範例：
- 英文：`www.example.com/en/`
- 繁中：`www.example.com/zh-tw/`
- 簡中：`www.example.com/zh-cn/`

### 情境 3：混合型
部分語言用域名，部分用子目錄。

## 進階技巧

### 批次填寫 URL
如果您有大量內容需要設定，建議：
1. 先設定好命名規則
2. 使用 Excel/Google Sheets 批次生成 URL
3. 使用 SQL 批次匯入（需要資料庫知識）

### 整合現有多語外掛
如果您已使用 WPML 或 Polylang，可以透過過濾器自動抓取對應 URL：

```php
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

## 疑難排解

### Q: hreflang 沒有顯示？
**A:** 確認：
- 已設定並啟用語言
- 文章已填寫對應 URL
- 外掛已啟用

### Q: 後台一直提示缺少 URL？
**A:** 這是正常提醒。填寫完所有語言的 URL 後提示會消失。

### Q: 語言切換器沒有顯示？
**A:** 確認：
- 短碼拼寫正確
- 當前頁面至少有 2 個語言的 URL
- CSS 檔案有正確載入

## 需要更多協助？

- 📖 查看 [完整文件](README.md)
- 📝 查看 [安裝指南](INSTALLATION.md)
- 🔄 查看 [更新日誌](CHANGELOG.md)
