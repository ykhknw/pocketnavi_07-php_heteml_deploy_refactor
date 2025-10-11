# HETEML本番環境移行手順書（修正版）

## 🚨 重要な修正: index.phpの扱い

### **問題:**
- `index.php` (49KB): 現在のメインアプリケーション
- `index_production.php` (7KB): 本番環境用の新しいエントリーポイント
- **これらは全く異なるファイルです！**

### **❌ 間違った手順:**
```bash
# これは間違い！既存のindex.phpが消えてしまいます
mv index_production.php index.php
```

## ✅ 正しい移行手順

### **Option 1: 段階的移行（推奨）**

#### Step 1: バックアップの作成
```bash
# HETEMLサーバー上で実行
cd public_html
cp index.php index_backup.php  # 既存のindex.phpをバックアップ
```

#### Step 2: 新しいアーキテクチャのテスト
```bash
# 新しいアーキテクチャをテスト用URLで確認
# 例: https://your-domain.heteml.jp/index_production.php
```

#### Step 3: 動作確認後の切り替え
```bash
# 動作確認が完了したら
mv index.php index_old.php           # 既存のindex.phpを退避
mv index_production.php index.php    # 新しいindex.phpに切り替え
```

### **Option 2: 直接置き換え**

#### Step 1: 既存ファイルの退避
```bash
# HETEMLサーバー上で実行
cd public_html
mv index.php index_old.php           # 既存のindex.phpを退避
```

#### Step 2: 新しいファイルの配置
```bash
# 新しいindex.phpとして配置
cp index_production.php index.php
```

## 🔧 移行前の確認事項

### **1. 既存のindex.phpの機能確認**
現在の`index.php`には以下の機能が含まれています：
- 検索機能
- 建築物表示
- 管理機能
- 既存のデータベース接続

### **2. 新しいindex_production.phpの機能**
新しい`index_production.php`には以下が含まれています：
- リファクタリング後のアーキテクチャ
- セキュリティ強化
- エラーハンドリング改善
- 統一されたデータベース接続

## 📋 推奨移行手順

### **Phase 1: 準備**
```bash
# 1. 必要なファイルをアップロード
# 2. 設定ファイルの配置
cp config/env.heteml .env
# 3. ディレクトリ権限の設定
mkdir -p logs cache
chmod 755 logs cache
```

### **Phase 2: テスト**
```bash
# 1. 新しいアーキテクチャのテスト
# URL: https://your-domain.heteml.jp/index_production.php
# 2. 機能の動作確認
# 3. エラーログの確認
```

### **Phase 3: 切り替え**
```bash
# 1. 既存ファイルのバックアップ
mv index.php index_old.php
# 2. 新しいファイルの配置
mv index_production.php index.php
# 3. 最終動作確認
```

## ⚠️ 重要な注意事項

### **1. バックアップの重要性**
- 必ず既存の`index.php`をバックアップしてください
- 問題が発生した場合の復旧用として保持してください

### **2. 段階的移行の推奨**
- いきなり置き換えるのではなく、テスト用URLで動作確認
- 問題がないことを確認してから本格切り替え

### **3. ロールバック手順**
```bash
# 問題が発生した場合の復旧手順
mv index.php index_production_backup.php  # 新しいファイルを退避
mv index_old.php index.php                # 既存ファイルを復元
```

## 🔍 移行後の確認事項

### **機能確認**
- [ ] トップページの表示
- [ ] 検索機能の動作
- [ ] 建築物詳細ページの表示
- [ ] 管理画面のアクセス
- [ ] エラーログの確認

### **パフォーマンス確認**
- [ ] ページ読み込み速度
- [ ] データベース接続
- [ ] キャッシュ機能

### **セキュリティ確認**
- [ ] CSRFトークンの生成
- [ ] セキュリティヘッダーの設定
- [ ] エラーハンドリング

## 📞 トラブルシューティング

### **よくある問題**
1. **ページが表示されない**: ログファイルを確認
2. **データベース接続エラー**: `.env`の設定を確認
3. **機能が動作しない**: 既存のindex.phpと比較

### **復旧手順**
```bash
# 緊急時の復旧
mv index.php index_production_backup.php
mv index_old.php index.php
```

---

**結論**: `index_production.php`を`index.php`に**リネーム**ではなく、**置き換え**が必要です。必ず既存の`index.php`をバックアップしてから実行してください。
