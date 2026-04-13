# WordPress 外掛開發指南

## 🚀 開發環境已就緒

您的 WordPress 外掛開發環境已完全配置好！

### 環境特性
✅ **即時代碼更新** - 本地文件更改會立即反映在 WordPress 中  
✅ **完整調試模式** - 錯誤和警告會完整顯示  
✅ **WP-CLI 集成** - 快速執行 WordPress 命令  
✅ **獨立資料庫** - 數據持久化保存  
✅ **自定義 PHP 配置** - 針對開發優化

---

## 📂 開發工作流程

### 1. 啟動開發環境
```powershell
# 啟動所有服務
docker compose up -d

# 查看服務狀態
docker compose ps

# 查看實時日誌
docker compose logs -f wordpress
```

### 2. 訪問您的網站
- **前台**: http://localhost:8080
- **後台**: http://localhost:8080/wp-admin
  - 帳號: `admin`
  - 密碼: `admin123`

### 3. 開發您的外掛

#### 📝 即時編輯
直接在 VS Code 中編輯外掛文件，更改會立即生效：
```
wp-hreflang-manager/
├── hreflang-manager.php   ← 主插件文件
├── src/
│   ├── admin-settings.php ← 設定頁面
│   ├── hreflang-core.php  ← 核心功能
│   ├── helpers.php        ← 輔助函數
│   └── nav-shortcode.php  ← 短碼功能
└── assets/
    └── css/
        └── style.css      ← 樣式文件
```

#### 🔄 重新載入外掛
```powershell
# 停用後重新啟用（清除快取）
docker compose run --rm wpcli wp plugin deactivate wp-hreflang-manager/hreflang-manager
docker compose run --rm wpcli wp plugin activate wp-hreflang-manager/hreflang-manager
```

#### 📋 查看錯誤日誌
```powershell
# 方法 1: 查看 WordPress 日誌
docker compose logs wordpress

# 方法 2: 查看 PHP 錯誤日誌
docker compose exec wordpress tail -f /var/log/php_errors.log

# 方法 3: 查看 WordPress debug.log
docker compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

---

## 🛠️ 常用 WP-CLI 命令

### 載入快捷函數
```powershell
# 在項目根目錄執行
. .\wp-cli-helpers.ps1
```

### 外掛管理
```powershell
# 列出所有外掛
wp plugin list

# 查看外掛狀態
wp plugin status wp-hreflang-manager/hreflang-manager

# 停用外掛
wp plugin deactivate wp-hreflang-manager/hreflang-manager

# 啟用外掛
wp plugin activate wp-hreflang-manager/hreflang-manager

# 刪除外掛（危險！）
wp plugin delete wp-hreflang-manager/hreflang-manager
```

### 選項管理（設定值）
```powershell
# 獲取選項值
wp option get hreflang_settings

# 更新選項
wp option update hreflang_settings '{"key":"value"}' --format=json

# 刪除選項
wp option delete hreflang_settings

# 列出所有以 hreflang 開頭的選項
wp option list --search="hreflang*"
```

### 內容管理
```powershell
# 創建測試文章
wp post create --post_title="測試文章" --post_content="這是測試內容" --post_status=publish

# 列出所有文章
wp post list

# 創建測試頁面
wp post create --post_type=page --post_title="測試頁面" --post_status=publish

# 刪除所有文章（小心！）
wp post delete $(wp post list --post_type=post --format=ids) --force
```

### 資料庫操作
```powershell
# 搜尋資料庫
wp db query "SELECT * FROM wp_options WHERE option_name LIKE 'hreflang%'"

# 導出資料庫
docker compose run --rm wpcli wp db export - > backup.sql

# 導入資料庫
docker compose run --rm wpcli wp db import < backup.sql

# 搜尋替換（例如更改域名）
wp search-replace 'oldurl.com' 'localhost:8080'
```

### 快取清理
```powershell
# 清除對象快取
wp cache flush

# 清除重寫規則快取
wp rewrite flush

# 清除暫存資料
wp transient delete --all
```

---

## 🧪 測試與調試

### 1. 啟用 WordPress 調試功能
已自動配置以下調試選項：
- `WORDPRESS_DEBUG` - 啟用調試模式
- `WORDPRESS_DEBUG_LOG` - 記錄錯誤到 `wp-content/debug.log`
- `WORDPRESS_DEBUG_DISPLAY` - 在頁面上顯示錯誤
- `SCRIPT_DEBUG` - 使用未壓縮的 JS/CSS
- `SAVEQUERIES` - 記錄資料庫查詢

### 2. 調試技巧
```php
// 在代碼中使用
error_log('調試訊息: ' . print_r($variable, true));

// 使用 WordPress 調試函數
wp_die(print_r($data, true));

// 記錄到調試日誌
if (WP_DEBUG_LOG) {
    error_log('Hreflang Manager: ' . $message);
}
```

### 3. 查看調試日誌
```powershell
# 實時查看 debug.log
docker compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

---

## 📦 安裝測試外掛和主題

### 安裝流行的測試工具
```powershell
# Query Monitor - 強大的調試外掛
wp plugin install query-monitor --activate

# Debug Bar - 調試工具欄
wp plugin install debug-bar --activate

# WP Crontrol - 管理 WordPress 排程任務
wp plugin install wp-crontrol --activate

# Show Current Template - 顯示當前使用的模板
wp plugin install show-current-template --activate
```

### 安裝測試主題
```powershell
# 安裝 Twenty Twenty-Four
wp theme install twentytwentyfour --activate

# 列出所有主題
wp theme list
```

---

## 🔄 版本控制建議

### .gitignore 配置
確保忽略開發環境文件：
```gitignore
# Docker
docker-compose.override.yml

# WordPress
wp-content/
*.log

# 備份
*.sql
backup-*.sql
```

---

## 🚨 常見問題

### 問題：代碼更改沒有生效
```powershell
# 解決方案 1: 重啟 WordPress 容器
docker compose restart wordpress

# 解決方案 2: 清除所有快取
wp cache flush
wp rewrite flush

# 解決方案 3: 停用後重新啟用外掛
wp plugin deactivate wp-hreflang-manager/hreflang-manager
wp plugin activate wp-hreflang-manager/hreflang-manager
```

### 問題：看不到錯誤訊息
```powershell
# 檢查 PHP 錯誤日誌
docker compose exec wordpress cat /var/log/php_errors.log

# 檢查 WordPress 調試日誌
docker compose exec wordpress cat /var/www/html/wp-content/debug.log

# 檢查容器日誌
docker compose logs wordpress --tail 100
```

### 問題：權限問題
```powershell
# 重置 WordPress 文件權限
docker compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content
```

### 問題：資料庫連接失敗
```powershell
# 檢查資料庫容器狀態
docker compose ps db

# 查看資料庫日誌
docker compose logs db

# 重啟資料庫
docker compose restart db
```

---

## 🎯 開發最佳實踐

### 1. 使用 Git 分支
```powershell
# 創建功能分支
git checkout -b feature/new-language-selector

# 提交更改
git add .
git commit -m "新增語言選擇器功能"
```

### 2. 定期備份
```powershell
# 每天備份資料庫
docker compose run --rm wpcli wp db export - > "backup-$(Get-Date -Format 'yyyy-MM-dd').sql"
```

### 3. 代碼品質檢查
```powershell
# 檢查 PHP 語法
docker compose exec wordpress php -l /var/www/html/wp-content/plugins/wp-hreflang-manager/hreflang-manager.php

# 使用 WordPress Coding Standards（需要額外安裝）
# phpcs --standard=WordPress src/
```

### 4. 測試不同環境
```powershell
# 測試外掛停用
wp plugin deactivate wp-hreflang-manager/hreflang-manager

# 測試外掛啟用
wp plugin activate wp-hreflang-manager/hreflang-manager

# 測試外掛更新流程
wp plugin update wp-hreflang-manager/hreflang-manager
```

---

## 📞 需要幫助？

### 查看文檔
- [README-DOCKER.md](README-DOCKER.md) - Docker 環境說明
- [wp-cli-helpers.ps1](wp-cli-helpers.ps1) - WP-CLI 快捷命令

### 快速命令參考
```powershell
# 環境控制
docker compose up -d        # 啟動
docker compose down         # 停止
docker compose restart      # 重啟
docker compose ps           # 狀態
docker compose logs -f      # 日誌

# 外掛開發
wp plugin list              # 列出外掛
wp option get <name>        # 獲取設定
wp cache flush              # 清除快取
```

---

## 🎉 開始開發

現在您可以開始開發了！

1. 編輯 `src/` 目錄中的文件
2. 重新整理瀏覽器查看更改
3. 使用 WP-CLI 測試功能
4. 查看日誌排除問題

**祝您開發順利！** 🚀
