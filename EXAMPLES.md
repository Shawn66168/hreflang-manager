# 使用範例

本文件提供 Hreflang Manager 外掛的實際使用範例。

## 範例 1：基本設定（單一文章）

### 情境
您有一個關於產品的頁面，有三個語言版本：

1. **前往後台設定**（設定 → Hreflang Languages）

語言設定：
```
語言代碼: en
Locale: en-US
Domain: www.example.com
顯示名稱: English
啟用: ✓

語言代碼: zh-Hant
Locale: zh-Hant
Domain: www.example.tw
顯示名稱: 繁體中文
啟用: ✓

語言代碼: ja
Locale: ja-JP
Domain: www.example.jp
顯示名稱: 日本語
啟用: ✓
```

2. **編輯文章並填寫對應 URL**

在「Hreflang 多語言 URL」欄位中填寫：
- English URL: `https://www.example.com/products/widget-x/`
- 繁體中文 URL: `https://www.example.tw/products/widget-x/`
- 日本語 URL: `https://www.example.jp/products/widget-x/`

3. **前端輸出結果**

訪問任一版本的頁面，在 `<head>` 中會看到：

```html
<!-- Hreflang Manager -->
<link rel="alternate" hreflang="en" href="https://www.example.com/products/widget-x/" />
<link rel="alternate" hreflang="zh-Hant" href="https://www.example.tw/products/widget-x/" />
<link rel="alternate" hreflang="ja" href="https://www.example.jp/products/widget-x/" />
<!-- /Hreflang Manager -->
```

---

## 範例 2：分類頁面

### 情境
您有產品分類，需要不同語言版本的 hreflang。

1. **前往「文章 → 分類」**

2. **編輯分類（例如「Electronics」）**

在底部的「Hreflang 多語言 URL」欄位填寫：
- English URL: `https://www.example.com/category/electronics/`
- 繁體中文 URL: `https://www.example.tw/category/electronics/`
- 日本語 URL: `https://www.example.jp/category/electronics/`

3. **前端輸出**

訪問分類頁面時會自動輸出對應的 hreflang 標籤。

---

## 範例 3：語言切換器（下拉選單）

### 在頁面內容中使用

在文章或頁面編輯器中加入：

```
[hreflang_switcher style="dropdown"]
```

### 前端顯示效果

會顯示一個下拉選單：

```
┌────────────────┐
│ English      ▼ │
├────────────────┤
│ English        │
│ 繁體中文       │
│ 日本語         │
└────────────────┘
```

點擊後會導向對應語言的頁面。

---

## 範例 4：語言切換器（清單樣式）

### 在小工具中使用

1. 前往「外觀 → 小工具」
2. 新增「自訂 HTML」小工具
3. 在內容中加入：

```html
<div class="language-switcher-widget">
    <h3>選擇語言 / Select Language</h3>
    [hreflang_switcher style="list" class="widget-lang-list"]
</div>
```

### 前端顯示效果

```
選擇語言 / Select Language
┌──────────┬──────────┬──────────┐
│ English  │ 繁體中文  │ 日本語   │
└──────────┴──────────┴──────────┘
```

當前語言會以不同顏色高亮顯示。

---

## 範例 5：在主題中使用

### 在 header.php 中加入

```php
<?php
/**
 * 在主題 header 中顯示語言切換器
 */
if (function_exists('hreflang_switcher_shortcode')) {
    echo '<div class="header-lang-switcher">';
    echo hreflang_switcher_shortcode(['style' => 'list']);
    echo '</div>';
}
?>
```

### 自訂樣式

在主題的 `style.css` 中加入：

```css
.header-lang-switcher {
    position: absolute;
    top: 10px;
    right: 20px;
}

.header-lang-switcher .-lang-list {
    display: flex;
    gap: 5px;
}

.header-lang-switcher .-lang-link {
    padding: 5px 10px;
    background: #333;
    color: #fff;
    border-radius: 3px;
}

.header-lang-switcher .-lang-item.active .-lang-link {
    background: #0073aa;
}
```

---

## 範例 6：首頁設定（x-default）

### 情境
您希望首頁輸出 x-default hreflang。

1. **設定預設語言**

在「設定 → Hreflang Languages」中：
- 預設語言代碼：`en`

2. **前端輸出（首頁）**

訪問首頁時會看到：

```html
<link rel="alternate" hreflang="en" href="https://www.example.com/" />
<link rel="alternate" hreflang="zh-Hant" href="https://www.example.tw/" />
<link rel="alternate" hreflang="ja" href="https://www.example.jp/" />
<link rel="alternate" hreflang="x-default" href="https://www.example.com/" />
```

---

## 範例 7：使用過濾器自訂

### 自動從路徑生成 URL

在主題的 `functions.php` 中：

```php
add_filter('hreflang_alternate_urls', function($urls, $object) {
    // 如果是文章且沒有填寫 URL，自動生成
    if (is_singular() && empty($urls)) {
        $post = get_post();
        $slug = $post->post_name;
        
        $languages = hreflang_get_languages();
        foreach ($languages as $lang) {
            if (!$lang['active']) continue;
            
            // 自動生成 URL
            $urls[$lang['code']] = trailingslashit($lang['domain']) 
                                  . $lang['code'] . '/' 
                                  . $slug . '/';
        }
    }
    
    return $urls;
}, 10, 2);
```

結果：
- EN: `https://www.example.com/en/my-post/`
- ZH: `https://www.example.tw/zh-Hant/my-post/`
- JA: `https://www.example.jp/ja/my-post/`

---

## 範例 8：新增更多語言

### 情境
您要新增西班牙文支援。

1. **前往設定頁面**（設定 → Hreflang Languages）

2. **點擊「新增語言」**

填寫：
```
語言代碼: es
Locale: es-ES
Domain: www.example.es
顯示名稱: Español
啟用: ✓
順序: 4
```

3. **更新現有文章**

回到文章編輯頁面，會自動出現新的「Español URL」欄位。

4. **填寫西班牙文 URL**

完成後，hreflang 標籤會自動包含西班牙文版本。

---

## 範例 9：停用特定語言

### 情境
日文版本暫時停用維護。

1. **前往設定頁面**

2. **找到日文那一列**

3. **取消勾選「啟用」**

4. **儲存設定**

結果：
- 前端不再輸出日文的 hreflang 標籤
- 語言切換器不顯示日文選項
- 但資料仍保留，可隨時重新啟用

---

## 範例 10：與現有多語外掛整合

### 整合 WPML

```php
// 在 functions.php 中
add_filter('hreflang_alternate_urls', function($urls, $object) {
    // 如果安裝了 WPML，自動取得翻譯連結
    if (function_exists('icl_get_languages')) {
        $languages = icl_get_languages('skip_missing=0');
        
        foreach ($languages as $lang_code => $lang_data) {
            if (!empty($lang_data['url'])) {
                $urls[$lang_code] = $lang_data['url'];
            }
        }
    }
    
    return $urls;
}, 10, 2);
```

### 整合 Polylang

```php
add_filter('hreflang_alternate_urls', function($urls, $object) {
    if (function_exists('pll_the_languages')) {
        $languages = pll_the_languages(['raw' => 1]);
        
        foreach ($languages as $lang) {
            if (!empty($lang['url'])) {
                $urls[$lang['slug']] = $lang['url'];
            }
        }
    }
    
    return $urls;
}, 10, 2);
```

---

## 完整範例網站結構

### 多域名結構

```
www.example.com     (英文)
├── /
├── /about/
├── /products/
│   └── /widget-x/
└── /contact/

www.example.tw      (繁體中文)
├── /
├── /about/
├── /products/
│   └── /widget-x/
└── /contact/

www.example.jp      (日文)
├── /
├── /about/
├── /products/
│   └── /widget-x/
└── /contact/
```

每個對應頁面都會輸出正確的 hreflang 標籤。

---

## 疑難排解範例

### 問題：hreflang 沒顯示

**檢查步驟**：

```php
// 在 functions.php 暫時加入除錯程式碼
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        $urls = hreflang_get_alternate_urls();
        echo '<pre style="background:#fff; padding:20px; border:1px solid #ccc;">';
        echo 'Debug - Alternate URLs:' . "\n";
        print_r($urls);
        echo '</pre>';
    }
});
```

前端頁面底部會顯示偵錯資訊（僅管理員可見）。

---

## 需要更多範例？

請參考：
- [README.md](README.md) - 完整文件
- [INSTALLATION.md](INSTALLATION.md) - 詳細安裝指南
- [QUICKSTART.md](QUICKSTART.md) - 快速開始

或提出 Issue 說明您的使用情境！
