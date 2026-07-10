# 更新日誌

## [1.2.0] - 2026-07-11

### 新增功能
- ✅ 外觀支援佈景主題 Design Token：顏色/字級/間距欄位可填 `var(--wp--preset--…)` 或 `inherit`，跟隨 theme.json 全域樣式
- ✅ 後台顏色欄位新增「主題色票…」下拉，一鍵帶入佈景主題調色盤 token（非 block theme 自動隱藏）

## [1.1.0] - 2026-07-11

### 新增功能
- ✅ 同 slug 自動對應：未手填 meta 時自動以「各語言網域＋相同路徑」產生 alternate URL（設定頁開關，手填永遠優先），hreflang 輸出與切換器共用
- ✅ 切換器外觀新增欄位：字重、按鈕內距/外距、選單內距、項目內距
- ✅ 文章編輯頁的多語言 URL 輸入框移至內文下方（原側欄），啟用自動對應時 placeholder 顯示將套用的自動 URL

### 修正
- ✅ 404 與搜尋頁不再輸出 hreflang（無對等內容）
- ✅ 日期/作者 archive 不再以各語言首頁充當 alternate
- ✅ 完全沒有 alternate 時不輸出整個 hreflang 區塊（孤立自身宣告無意義）
- ✅ uninstall.php 補齊外觀主題與自動對應選項的清理
- ✅ docker-compose 移除 amd64 平台鎖定（Apple Silicon 跑原生）

## [1.0.0] - 2026-01-21

### 新增功能
- ✅ 自動在全站輸出 hreflang 標籤
- ✅ 支援文章、頁面、分類、標籤、搜尋、archive 等所有頁面類型
- ✅ 語言切換短碼 `[hreflang_switcher]`
- ✅ 後台語言管理設定頁面
- ✅ ACF 整合（自動建立多語言 URL 欄位）
- ✅ Term meta 欄位（分類/標籤）
- ✅ 後台缺漏 URL 提示通知
- ✅ 文章列表頁面顯示 Hreflang 狀態欄位
- ✅ 分類/標籤列表頁面顯示 Hreflang 狀態
- ✅ 可自訂 CSS 樣式

### WordPress 規範
- ✅ 符合 WordPress Plugin Handbook 規範
- ✅ 添加 readme.txt（WordPress.org 格式）
- ✅ 實作啟用/停用 Hook
- ✅ 實作 uninstall.php 清理腳本
- ✅ 添加外掛常數定義
- ✅ 系統需求檢查（PHP 7.4+、WordPress 5.0+）
- ✅ 防止目錄瀏覽（index.php）
- ✅ 添加外掛列表頁面的快速連結
- ✅ 完整的 ABSPATH 安全檢查

### SEO 外掛相容性
- ✅ Yoast SEO 相容性處理
- ✅ Rank Math 相容性檢查
- ✅ All in One SEO 相容性檢查
- ✅ 避免重複 hreflang 輸出
- ✅ 優先級控制避免衝突

### 過濾器
- `hreflang_languages` - 修改語言清單
- `hreflang_alternate_urls` - 修改輸出 URL
- `hreflang_default_language` - 修改預設語言
- `hreflang_manager_enable_output` - 控制是否輸出 hreflang（新增）

### 安全性增強
- ✅ 所有 PHP 檔案添加 ABSPATH 檢查
- ✅ 目錄瀏覽保護（index.php）
- ✅ .htaccess 防護（可選）
- ✅ 完整的資料過濾和驗證

### 已知限制
- 目前僅支援手動填寫各語言 URL
- 需要安裝 ACF 外掛才能使用自動欄位功能（可選）
- 尚未包含單元測試

## 路線圖

### [1.1.0] - 計畫中
- [ ] Block/區塊型語言切換元件
- [ ] 國旗圖示支援
- [ ] 更多語言切換器樣式

### [1.2.0] - 計畫中
- [ ] Sitemap hreflang 支援
- [ ] 自動檢測相同內容的不同語言版本

### [1.3.0] - 計畫中
- [ ] 404 自動檢查功能
- [ ] URL 驗證工具

### [2.0.0] - 未來規劃
- [ ] CLI 匯入匯出功能
- [ ] 與 WPML/Polylang 整合
- [ ] 單元測試與 CI/CD
