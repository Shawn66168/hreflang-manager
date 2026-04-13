# WordPress 本地開發環境

## 快速開始

### 1. 啟動環境
```bash
docker compose up -d
```

### 2. 訪問 WordPress
- **Site A 前台**：http://localhost:8080
- **Site A 後台**：http://localhost:8080/wp-admin
- **Site B 前台**：http://localhost:8081
- **Site B 後台**：http://localhost:8081/wp-admin

### 3. 預設登入資訊
初次訪問時需要完成 WordPress 安裝，建議使用：
- **用戶名**：admin
- **密碼**：admin123（或您自定義的密碼）
- **電子郵件**：your-email@example.com

### 4. 資料庫資訊
- **Site A 主機**：db
- **Site A 資料庫名稱**：wordpress_a
- **Site B 主機**：db2
- **Site B 資料庫名稱**：wordpress_b
- **用戶名**：wordpress
- **密碼**：wordpress
- **端口**：3306

### 5. 初始化兩個站點（首次）
```bash
# Site A
docker compose run --rm wpcli wp core install \
  --url=http://localhost:8080 \
  --title="Hreflang Site A" \
  --admin_user=admin \
  --admin_password=admin123 \
  --admin_email=admin@example.com

# Site B
docker compose run --rm wpcli2 wp core install \
  --url=http://localhost:8081 \
  --title="Hreflang Site B" \
  --admin_user=admin \
  --admin_password=admin123 \
  --admin_email=admin@example.com
```

## 使用 WP-CLI

### 執行 WP-CLI 命令
```bash
# 基本語法
docker compose run --rm wpcli wp <command>
docker compose run --rm wpcli2 wp <command>

# 範例：檢查 WordPress 版本
docker compose run --rm wpcli wp core version

# 範例：列出所有插件
docker compose run --rm wpcli wp plugin list
docker compose run --rm wpcli2 wp plugin list

# 範例：啟用本插件
docker compose run --rm wpcli wp plugin activate wp-hreflang-manager
docker compose run --rm wpcli2 wp plugin activate wp-hreflang-manager

# 範例：安裝其他插件
docker compose run --rm wpcli wp plugin install akismet --activate

# 範例：創建測試文章
docker compose run --rm wpcli wp post create --post_title="測試文章" --post_content="這是測試內容" --post_status=publish
docker compose run --rm wpcli2 wp post create --post_title="站點 B 測試文章" --post_content="這是站點 B 測試內容" --post_status=publish
```

### 快捷腳本（可選）
建議創建 PowerShell 函數來簡化 WP-CLI 使用：

在 PowerShell 配置文件中添加：
```powershell
function wp {
    docker compose run --rm wpcli wp $args
}
```

之後可以直接使用：
```bash
wp plugin list
wp core version
```

## 常用命令

### 容器管理
```bash
# 啟動所有容器
docker compose up -d

# 停止所有容器
docker compose down

# 查看容器狀態
docker compose ps

# 查看日誌
docker compose logs wordpress
docker compose logs wordpress2
docker compose logs db
docker compose logs db2

# 重啟容器
docker compose restart wordpress
docker compose restart wordpress2
```

### 插件開發
```bash
# 啟用開發中的插件
docker compose run --rm wpcli wp plugin activate wp-hreflang-manager
docker compose run --rm wpcli2 wp plugin activate wp-hreflang-manager

# 查看插件狀態
docker compose run --rm wpcli wp plugin status wp-hreflang-manager
docker compose run --rm wpcli2 wp plugin status wp-hreflang-manager

# 停用插件
docker compose run --rm wpcli wp plugin deactivate wp-hreflang-manager
docker compose run --rm wpcli2 wp plugin deactivate wp-hreflang-manager
```

### 資料庫管理
```bash
# 導出資料庫
docker compose run --rm wpcli wp db export - > backup.sql
docker compose run --rm wpcli2 wp db export - > backup-b.sql

# 導入資料庫
docker compose run --rm wpcli wp db import < backup.sql
docker compose run --rm wpcli2 wp db import < backup-b.sql

# 搜尋和替換（例如更改域名）
docker compose run --rm wpcli wp search-replace 'oldurl.com' 'localhost:8080'
docker compose run --rm wpcli2 wp search-replace 'oldurl.com' 'localhost:8081'
```

## 故障排除

### 端口已被占用
如果 8080 或 8081 端口已被使用，編輯 `docker-compose.yml` 修改：
```yaml
ports:
  - "8000:80"  # 改為其他端口
```

### 權限問題
```bash
# 重置文件權限
docker compose exec wordpress chown -R www-data:www-data /var/www/html
```

### 清理並重新開始
```bash
# 停止並刪除所有容器和卷
docker compose down -v

# 重新啟動
docker compose up -d
```

## 目錄結構
```
wp-hreflang-manager/
├── docker-compose.yml         # Docker 配置
├── .dockerignore             # Docker 忽略文件
├── README-DOCKER.md          # 本文件
└── [您的插件文件]
```

## 注意事項

1. **插件自動掛載**：當前目錄自動掛載到 `/wp-content/plugins/wp-hreflang-manager`
2. **數據持久化**：資料庫和 WordPress 文件存儲在 Docker volumes 中
3. **開發模式**：已啟用 `WORDPRESS_DEBUG`，方便調試
4. **性能**：首次啟動需要下載 Docker 映像，之後會快很多
