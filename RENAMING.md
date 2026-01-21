# 重新命名指南

本外掛已去識別化，建議重新命名資料夾以符合外掛實際名稱。

## 建議的資料夾名稱

建議使用以下任一名稱：

- `hreflang-manager`（推薦）
- `hreflang-switcher`
- `wp-hreflang-manager`
- 或您自己的專案名稱

## 重新命名步驟

### 方法 1：手動重新命名

1. 關閉 VS Code 或任何正在編輯此專案的編輯器
2. 在檔案總管中將資料夾重新命名
3. 如果已經放在 WordPress plugins 目錄中，停用外掛後再重新命名
4. 重新啟用外掛

### 方法 2：使用命令列（Windows PowerShell）

```powershell
# 停止所有可能鎖定檔案的程式
# 然後執行：
Rename-Item -Path "D:\Projects\wp-hreflang-manager" -NewName "hreflang-manager"
```

### 方法 3：Git 重新命名

如果您使用 Git：

```bash
cd D:\Projects
git mv wp-hreflang-manager hreflang-manager
# 或者
mv wp-hreflang-manager hreflang-manager
cd hreflang-manager
git add .
git commit -m "Rename plugin folder to hreflang-manager"
```

## 重新命名後需要更新的內容

資料夾重新命名後，您可能需要更新：

1. **主外掛檔案名稱**（可選）：
   - 將 `hreflang-switch.php` 或 `hreflang-manager.php` 保持為主入口檔案
   
2. **README.md 中的參考**：
   - 確認所有提到資料夾名稱的地方都已更新

3. **Git remote**（如果使用 Git）：
   - 更新遠端儲存庫名稱

## 主外掛檔案重新命名（可選）

如果要重新命名主外掛檔案：

```powershell
# 在專案根目錄執行
Rename-Item -Path "hreflang-switch.php" -NewName "hreflang-manager.php"
```

**注意**：重新命名主檔案後，WordPress 會將此視為新外掛，需要：
- 先停用舊外掛
- 啟用新外掛
- 重新設定（如果有保存在 options 中的設定應該還會保留）

## 完全客製化

如果您想使用完全客製化的名稱（例如 `my-company-hreflang`），建議額外執行：

1. 全域搜尋替換 `hreflang-manager` 為您的新名稱
2. 更新 composer.json 中的 package name
3. 更新所有文件檔案中的外掛名稱

## 注意事項

- 重新命名不會影響外掛功能
- 資料庫中的 options 鍵值（如 `hreflang_languages`）保持不變
- 已安裝的外掛如果重新命名資料夾，WordPress 會視為新外掛
