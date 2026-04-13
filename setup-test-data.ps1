# 快速生成測試數據腳本

Write-Host "正在生成 WordPress 測試數據..." -ForegroundColor Cyan

# 創建測試文章
Write-Host "`n📝 創建測試文章..." -ForegroundColor Yellow
for ($i = 1; $i -le 5; $i++) {
    docker compose run --rm wpcli wp post create `
        --post_title="測試文章 $i" `
        --post_content="這是測試文章 $i 的內容。這個文章用於測試 hreflang 功能。" `
        --post_status=publish `
        --quiet
}

# 創建測試頁面
Write-Host "`n📄 創建測試頁面..." -ForegroundColor Yellow
docker compose run --rm wpcli wp post create `
    --post_type=page `
    --post_title="關於我們" `
    --post_content="這是關於我們頁面的內容。" `
    --post_status=publish `
    --quiet

docker compose run --rm wpcli wp post create `
    --post_type=page `
    --post_title="聯絡我們" `
    --post_content="這是聯絡我們頁面的內容。" `
    --post_status=publish `
    --quiet

# 創建測試分類
Write-Host "`n🏷️  創建測試分類..." -ForegroundColor Yellow
docker compose run --rm wpcli wp term create category "技術文章" --slug=tech --quiet
docker compose run --rm wpcli wp term create category "生活分享" --slug=life --quiet
docker compose run --rm wpcli wp term create category "產品介紹" --slug=products --quiet

# 安裝實用的開發外掛
Write-Host "`n🔌 安裝開發工具外掛..." -ForegroundColor Yellow
docker compose run --rm wpcli wp plugin install query-monitor --activate --quiet
docker compose run --rm wpcli wp plugin install show-current-template --activate --quiet

# 顯示完成訊息
Write-Host "`n✅ 測試數據生成完成！" -ForegroundColor Green
Write-Host "`n生成的內容：" -ForegroundColor Cyan
docker compose run --rm wpcli wp post list --post_type=post,page --format=table

Write-Host "`n已安裝的外掛：" -ForegroundColor Cyan
docker compose run --rm wpcli wp plugin list --status=active --format=table

Write-Host "`n🎉 開發環境準備就緒！" -ForegroundColor Green
Write-Host "訪問: http://localhost:8080" -ForegroundColor Cyan
