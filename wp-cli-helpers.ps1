# WP-CLI 快捷命令腳本（PowerShell）

# 方便使用 WP-CLI 的快捷函數

# 基本 WP-CLI 包裝函數
function wp {
    docker compose run --rm wpcli wp $args
}

# 插件管理
function wp-plugin-list {
    docker compose run --rm wpcli wp plugin list
}

function wp-plugin-activate {
    param([string]$plugin)
    docker compose run --rm wpcli wp plugin activate $plugin
}

function wp-plugin-deactivate {
    param([string]$plugin)
    docker compose run --rm wpcli wp plugin deactivate $plugin
}

# 主題管理
function wp-theme-list {
    docker compose run --rm wpcli wp theme list
}

# 用戶管理
function wp-user-list {
    docker compose run --rm wpcli wp user list
}

# 數據庫管理
function wp-db-export {
    param([string]$filename = "backup-$(Get-Date -Format 'yyyy-MM-dd-HHmmss').sql")
    docker compose run --rm wpcli wp db export - > $filename
    Write-Host "資料庫已導出到: $filename" -ForegroundColor Green
}

# 容器管理
function wp-start {
    docker compose up -d
    Write-Host "WordPress 環境已啟動" -ForegroundColor Green
    Write-Host "訪問: http://localhost:8080" -ForegroundColor Cyan
}

function wp-stop {
    docker compose down
    Write-Host "WordPress 環境已停止" -ForegroundColor Yellow
}

function wp-restart {
    docker compose restart
    Write-Host "WordPress 環境已重啟" -ForegroundColor Green
}

function wp-logs {
    docker compose logs -f wordpress
}

function wp-shell {
    docker compose exec wordpress bash
}

Write-Host @"
╔═══════════════════════════════════════════════════════╗
║   WP-CLI 快捷命令已載入                              ║
╚═══════════════════════════════════════════════════════╝

可用命令:
  wp <command>              - 執行任何 WP-CLI 命令
  wp-plugin-list            - 列出所有插件
  wp-plugin-activate <名稱> - 啟用插件
  wp-plugin-deactivate <名> - 停用插件
  wp-theme-list             - 列出所有主題
  wp-user-list              - 列出所有用戶
  wp-db-export [檔名]       - 導出資料庫
  
  wp-start                  - 啟動 WordPress
  wp-stop                   - 停止 WordPress
  wp-restart                - 重啟 WordPress
  wp-logs                   - 查看日誌
  wp-shell                  - 進入容器 shell

範例:
  wp core version
  wp option get siteurl
  wp post list
  wp-db-export my-backup.sql

"@ -ForegroundColor Cyan
