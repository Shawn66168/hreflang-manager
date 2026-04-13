# WordPress 外掛打包腳本
# 用途：建立標準的 WordPress 外掛 ZIP 檔案

$pluginSlug = "hreflang-manager"
$version = "1.0.0"
$zipName = "$pluginSlug-$version.zip"

Write-Host "`n🚀 開始打包 WordPress 外掛..." -ForegroundColor Cyan

# 1. 清理舊檔案
Write-Host "清理舊檔案..." -ForegroundColor Yellow
if (Test-Path $zipName) { Remove-Item $zipName -Force }
if (Test-Path $pluginSlug) { Remove-Item $pluginSlug -Recurse -Force }

# 2. 定義排除項目
$exclude = @(
    '.git', '.gitignore', $pluginSlug, '*.zip', 'build-plugin.ps1',
    'DEIDENTIFIED.md', 'RENAME_COMPLETE.md', 'RENAMING.md',
    'WORDPRESS_ORG_COMPLIANCE.md', 'PROJECT_SUMMARY.md'
)

# 3. 建立臨時目錄
Write-Host "建立外掛目錄..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $pluginSlug -Force | Out-Null

# 4. 複製檔案
Write-Host "複製檔案..." -ForegroundColor Yellow
Get-ChildItem -Exclude $exclude | ForEach-Object {
    Copy-Item $_.FullName -Destination $pluginSlug -Recurse -Force
}

# 5. 驗證必要檔案
Write-Host "驗證檔案結構..." -ForegroundColor Yellow
$requiredFiles = @("hreflang-switch.php", "readme.txt", "uninstall.php")
$missing = @()

foreach ($file in $requiredFiles) {
    if (!(Test-Path "$pluginSlug\$file")) {
        $missing += $file
    }
}

if ($missing.Count -gt 0) {
    Write-Host "❌ 缺少必要檔案: $($missing -join ', ')" -ForegroundColor Red
    Remove-Item $pluginSlug -Recurse -Force
    exit 1
}

# 6. 建立 ZIP
Write-Host "壓縮檔案..." -ForegroundColor Yellow
Compress-Archive -Path $pluginSlug -DestinationPath $zipName -CompressionLevel Optimal -Force

# 7. 清理臨時目錄
Remove-Item $pluginSlug -Recurse -Force

# 8. 顯示結果
if (Test-Path $zipName) {
    $zipInfo = Get-Item $zipName
    Write-Host "`n✅ 外掛打包成功！" -ForegroundColor Green
    Write-Host "`n檔案資訊:" -ForegroundColor Cyan
    Write-Host "  名稱: $($zipInfo.Name)"
    Write-Host "  大小: $([math]::Round($zipInfo.Length/1KB, 2)) KB"
    Write-Host "  路徑: $($zipInfo.FullName)"
    
    Write-Host "`n📦 ZIP 結構:" -ForegroundColor Cyan
    Write-Host "  $zipName"
    Write-Host "  └── $pluginSlug/"
    Write-Host "      ├── hreflang-switch.php (主檔案)"
    Write-Host "      ├── readme.txt"
    Write-Host "      ├── uninstall.php"
    Write-Host "      ├── src/"
    Write-Host "      └── assets/"
    
    Write-Host "`n📥 安裝說明:" -ForegroundColor Yellow
    Write-Host "  1. 下載 $zipName"
    Write-Host "  2. WordPress 後台 → 外掛 → 安裝外掛 → 上傳外掛"
    Write-Host "  3. 選擇 ZIP 檔案上傳並安裝"
    Write-Host "  4. 啟用外掛"
    
    Write-Host "`n🗑️  刪除說明:" -ForegroundColor Yellow
    Write-Host "  1. 先「停用」外掛"
    Write-Host "  2. 再「刪除」外掛"
    Write-Host "  3. uninstall.php 會自動清理資料"
} else {
    Write-Host "`n❌ 打包失敗" -ForegroundColor Red
    exit 1
}
