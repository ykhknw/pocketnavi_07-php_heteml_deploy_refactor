# データベース設定の統一化ガイド

## 📋 推奨される設定方法

### **1. 環境変数ファイル (.env)**
```
# ローカル環境
DB_HOST=localhost
DB_NAME=_shinkenchiku_02
DB_USERNAME=root
DB_PASSWORD=

# 本番環境 (HETEML)
# DB_HOST=mysql320.phy.heteml.lan
# DB_NAME=_shinkenchiku_02
# DB_USERNAME=_shinkenchiku_02
# DB_PASSWORD=your_heteml_password
```

### **2. 統一されたデータベース設定 (database_unified.php)**
- EnvironmentLoaderを使用して.envから設定を読み込み
- 統一された接続ロジック
- エラーハンドリング付き

## 🗂️ ファイル構成

### **必要なファイル:**
- ✅ `.env` - 環境変数
- ✅ `config/database_unified.php` - 統一された設定

### **不要なファイル:**
- ❌ `config/database.php` - 重複
- ❌ `config/database_heteml.php` - ハードコード（非推奨）

## 🔄 移行手順

### **Step 1: 不要なファイルの削除**
```bash
# 重複するファイルを削除
rm config/database.php
rm config/database_heteml.php
```

### **Step 2: .envファイルの設定**
```env
# ローカル環境
DB_HOST=localhost
DB_NAME=_shinkenchiku_02
DB_USERNAME=root
DB_PASSWORD=

# 本番環境用の設定も準備
# DB_HOST=mysql320.phy.heteml.lan
# DB_NAME=_shinkenchiku_02
# DB_USERNAME=_shinkenchiku_02
# DB_PASSWORD=your_heteml_password
```

### **Step 3: アプリケーションでの使用**
```php
// 統一されたデータベース接続を使用
require_once __DIR__ . '/config/database_unified.php';
$pdo = getDatabaseConnection();
```

## 🎯 メリット

### **セキュリティ:**
- パスワードがコードに含まれない
- 環境別の設定管理

### **保守性:**
- 設定の一元管理
- 環境別の設定変更が容易

### **柔軟性:**
- 開発・本番環境の切り替えが簡単
- 新しい環境の追加が容易

## 📝 まとめ

**推奨される構成:**
1. **`.env`** - 環境変数（パスワード等の機密情報）
2. **`config/database_unified.php`** - 統一された接続ロジック

**不要なファイル:**
- `config/database.php` - 重複
- `config/database_heteml.php` - ハードコード（非推奨）
