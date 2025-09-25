# HETEML環境での検索履歴管理ガイド

## 概要

HETEMLレンタルサーバーでの運用を考慮した検索履歴データ管理システムです。HETEMLの制約（実行時間30秒、メモリ128MB、cron制限）に最適化されています。

## HETEMLの制約

### リソース制限
- **実行時間**: 30秒以内
- **メモリ**: 128MB以内
- **cron実行**: 最大1日1回、5分以内
- **データベース**: 一般的に100MB制限

### 制約への対応
- バッチ処理（1,000件ずつ）
- 軽量アーカイブ機能
- 実行時間監視
- メモリ使用量監視

## ファイル構成

```
scripts/
├── heteml_cleanup_config.php          # HETEML用設定ファイル
├── heteml_cleanup_search_history.php  # HETEML用クリーンアップスクリプト
└── heteml_cron_setup.md               # cron設定ガイド

admin/
└── heteml_cleanup.php                 # HETEML用Web管理画面

docs/
└── HETEML_SEARCH_HISTORY_MANAGEMENT.md # このファイル
```

## 設定手順

### 1. 初期設定

```bash
# ファイルをアップロード
# scripts/heteml_cleanup_search_history.php
# admin/heteml_cleanup.php
# scripts/heteml_cleanup_config.php
```

### 2. 権限設定

```bash
# スクリプトに実行権限を付与
chmod +x scripts/heteml_cleanup_search_history.php

# ログディレクトリを作成
mkdir -p logs
chmod 755 logs
```

### 3. cron設定

HETEML管理画面で以下の設定：

```
# 毎日午前2時にクリーンアップ実行
0 2 * * * /usr/local/bin/php /home/your-account/public_html/scripts/heteml_cleanup_search_history.php 60 --archive
```

## 使用方法

### 1. コマンドライン実行

```bash
# 統計情報を表示
php scripts/heteml_cleanup_search_history.php --stats

# 60日より古いデータをアーカイブしてから削除
php scripts/heteml_cleanup_search_history.php 60 --archive

# 30日より古いデータを削除（アーカイブなし）
php scripts/heteml_cleanup_search_history.php 30
```

### 2. Web管理画面

```
# 統計情報確認
https://your-domain.com/admin/heteml_cleanup.php?key=heteml_admin_2024&action=stats

# クリーンアップ実行
https://your-domain.com/admin/heteml_cleanup.php?key=heteml_admin_2024&action=cleanup
```

### 3. 外部サービス連携

#### UptimeRobot を使用

```
# 毎日午前2時にアクセス
https://your-domain.com/admin/heteml_cleanup.php?key=heteml_admin_2024&action=cleanup
```

#### GitHub Actions を使用

```yaml
name: HETEML Search History Cleanup
on:
  schedule:
    - cron: '0 17 * * *'  # 毎日午後5時（JST午前2時）
  workflow_dispatch:

jobs:
  cleanup:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger HETEML Cleanup
        run: |
          curl -X POST "https://your-domain.com/admin/heteml_cleanup.php" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -d "key=heteml_admin_2024&action=cleanup"
```

## 推奨設定

### 本番環境

```bash
# 毎日午前2時、60日保持、アーカイブ有効
0 2 * * * /usr/local/bin/php /home/your-account/public_html/scripts/heteml_cleanup_search_history.php 60 --archive
```

### 開発環境

```bash
# 毎日午前3時、30日保持、アーカイブ無効
0 3 * * * /usr/local/bin/php /home/your-account/public_html/scripts/heteml_cleanup_search_history.php 30
```

## 監視とアラート

### アラート条件

| 項目 | 警告 | 緊急 |
|------|------|------|
| テーブルサイズ | 50MB | 80MB |
| レコード数 | 30,000件 | 45,000件 |

### 監視方法

1. **Web管理画面での定期確認**
   - 週1回は統計情報を確認
   - 推奨事項をチェック

2. **ログファイルの確認**
   ```bash
   # エラーログの確認
   tail -20 /home/your-account/logs/error.log
   
   # 検索履歴関連のログ
   grep "search_cleanup" /home/your-account/logs/error.log
   ```

## トラブルシューティング

### よくある問題

#### 1. cronジョブが実行されない

**原因**: HETEMLのcron機能の制限
**解決策**:
- 外部サービス（UptimeRobot）を使用
- 手動実行で定期確認

#### 2. 実行時間が30秒を超える

**原因**: データ量が多すぎる
**解決策**:
- 保持期間を短縮（30日）
- バッチサイズを小さくする（500件）
- アーカイブ機能を無効化

#### 3. メモリ不足エラー

**原因**: メモリ制限（128MB）を超過
**解決策**:
- バッチサイズをさらに小さくする（250件）
- 不要なデータを事前に削除

#### 4. データベースサイズ制限に近づく

**原因**: データの蓄積
**解決策**:
- 緊急クリーンアップ実行
- 保持期間を大幅に短縮

### 緊急時の対応

```bash
# 緊急クリーンアップ（30日保持）
php scripts/heteml_cleanup_search_history.php 30

# アーカイブなしでクリーンアップ
php scripts/heteml_cleanup_search_history.php 60

# 統計情報で状況確認
php scripts/heteml_cleanup_search_history.php --stats
```

## セキュリティ考慮事項

### 1. アクセス制御

```php
// 本番環境では環境変数から取得
$validKey = getenv('HETEML_ADMIN_KEY') ?: 'heteml_admin_2024';
```

### 2. ログファイルの保護

```bash
# ログファイルの権限設定
chmod 600 logs/search_cleanup.log
chown your-account:your-account logs/search_cleanup.log
```

### 3. データの機密性

- 個人情報が含まれる可能性があるため、適切なデータ保護措置を講じる
- ログファイルのアクセス権限を制限
- 定期的なログファイルの削除

## パフォーマンス最適化

### 1. データベース最適化

```sql
-- インデックスの最適化
OPTIMIZE TABLE global_search_history;

-- 不要なインデックスの削除
DROP INDEX IF EXISTS idx_unused ON global_search_history;
```

### 2. クエリ最適化

- バッチサイズの調整
- 実行時間の監視
- メモリ使用量の監視

### 3. キャッシュの活用

- 統計情報のキャッシュ
- アーカイブデータの圧縮

## 今後の改善案

### 1. 自動スケーリング

- データ量に応じて保持期間を自動調整
- リソース使用量に応じてバッチサイズを調整

### 2. 外部ストレージ連携

- AWS S3やGoogle Cloud Storageへの自動アーカイブ
- データの圧縮と暗号化

### 3. リアルタイム監視

- ダッシュボードでのリアルタイム監視
- アラート通知機能

## 関連ファイル

- `scripts/heteml_cleanup_search_history.php` - メインのクリーンアップスクリプト
- `scripts/heteml_cleanup_config.php` - HETEML用設定ファイル
- `admin/heteml_cleanup.php` - Web管理画面
- `scripts/heteml_cron_setup.md` - cron設定ガイド
- `src/Services/SearchLogService.php` - 検索ログサービス

## サポート

問題が発生した場合は、以下の情報を収集してください：

1. エラーメッセージ
2. ログファイルの内容
3. データベースの統計情報
4. 実行環境の詳細

これらの情報を基に、適切な解決策を提供します。
