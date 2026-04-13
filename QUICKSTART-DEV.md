# 🚀 WordPress 外掛開發環境 - 快速啟動

## 環境狀態
✅ **WordPress Site A**: http://localhost:8080  
✅ **後台 Site A**: http://localhost:8080/wp-admin (admin / admin123)  
✅ **WordPress Site B**: http://localhost:8081  
✅ **後台 Site B**: http://localhost:8081/wp-admin (admin / admin123)  
✅ **您的外掛**: wp-hreflang-manager (已啟用)  
✅ **調試工具**: Query Monitor (已安裝)  
✅ **WP-CLI**: 已配置  
✅ **調試模式**: 已啟用  

---

## 📋 每日開發流程

### 1️⃣ 啟動環境
```powershell
cd D:\Projects\wp-hreflang-manager
docker compose up -d
```

### 2️⃣ 開始編輯代碼
在 VS Code 中編輯這些文件，更改會立即生效：
- `hreflang-manager.php` - 主插件文件
- `src/*.php` - 核心功能
- `assets/css/style.css` - 樣式

### 3️⃣ 測試更改
```powershell
# 重新整理瀏覽器查看前台
# 或使用 WP-CLI 測試
docker compose run --rm wpcli wp option get hreflang_settings
```

### 4️⃣ 查看錯誤（如果有）
```powershell
# 查看容器日誌
docker compose logs wordpress -f

# 或進入容器查看 debug.log
docker compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

### 5️⃣ 結束工作
```powershell
# 保持運行（推薦）
# Docker 會在背景執行

# 或完全停止
docker compose down
```

---

## ⚡ 常用命令速查

### WP-CLI 命令
```powershell
# 載入快捷函數（推薦）
. .\wp-cli-helpers.ps1

# 然後可以直接使用
wp plugin list
wp option get hreflang_settings
wp cache flush

# Site B
docker compose run --rm wpcli2 wp plugin list
```

### 外掛管理
```powershell
# 查看外掛狀態
docker compose run --rm wpcli wp plugin status wp-hreflang-manager/hreflang-manager.php

# 停用重啟（清除快取）
docker compose run --rm wpcli wp plugin deactivate wp-hreflang-manager/hreflang-manager.php
docker compose run --rm wpcli wp plugin activate wp-hreflang-manager/hreflang-manager.php
```

### 測試數據
```powershell
# 運行測試數據生成腳本
.\setup-test-data.ps1

# 或手動創建文章
docker compose run --rm wpcli wp post create --post_title="測試" --post_status=publish
docker compose run --rm wpcli2 wp post create --post_title="站點B測試" --post_status=publish
```

### 容器管理
```powershell
docker compose ps          # 查看狀態
docker compose restart     # 重啟所有服務
docker compose logs -f     # 查看日誌
```

---

## 🔧 調試技巧

### 1. 使用 Query Monitor
已安裝 Query Monitor 外掛，訪問網站時會在頂部顯示調試信息：
- 資料庫查詢
- PHP 錯誤
- 掛鉤和過濾器
- HTTP 請求

### 2. 代碼中添加日誌
```php
// 在您的外掛代碼中
error_log('Hreflang Debug: ' . print_r($data, true));

// 然後查看日誌
// docker compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

### 3. 使用 wp_die() 調試
```php
// 暫時性調試
wp_die('<pre>' . print_r($variable, true) . '</pre>');
```

---

## 📚 完整文檔

- **[DEVELOPMENT.md](DEVELOPMENT.md)** - 完整開發指南
- **[README-DOCKER.md](README-DOCKER.md)** - Docker 環境說明
- **[wp-cli-helpers.ps1](wp-cli-helpers.ps1)** - WP-CLI 快捷函數

---

## 🆘 常見問題快速修復

### 代碼沒有生效？
```powershell
# 清除 WordPress 快取
docker compose run --rm wpcli wp cache flush

# 重啟容器
docker compose restart wordpress
```

### 看不到錯誤？
```powershell
# 檢查 debug.log
docker compose exec wordpress cat /var/www/html/wp-content/debug.log
```

### 外掛衝突？
```powershell
# 停用所有其他外掛
docker compose run --rm wpcli wp plugin deactivate --all --exclude=wp-hreflang-manager/hreflang-manager.php
```

### 重置環境？
```powershell
# ⚠️ 警告：這會刪除所有數據！
docker compose down -v
docker compose up -d
# 等待 30 秒讓資料庫初始化
```

---

## 🎯 下一步

1. **訪問網站**: http://localhost:8080
2. **登入後台**: http://localhost:8080/wp-admin (admin / admin123)
3. **開啟第二站**: http://localhost:8081/wp-admin (admin / admin123)
4. **找到您的外掛**: 外掛 → 已安裝的外掛 → Hreflang Manager
5. **開始編輯**: 在 VS Code 中修改 `src/` 目錄的文件
6. **測試跨站**: 兩站各自建立對應頁面並互填 URL

---

**祝您開發順利！** 🚀

有問題？查看 [DEVELOPMENT.md](DEVELOPMENT.md) 獲取更多詳細資訊。
