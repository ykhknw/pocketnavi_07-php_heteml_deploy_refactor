# 検索履歴データ管理ガイド

## 概要

`global_search_history`テーブルは検索語が記録され続けるため、長期間運用すると膨大なサイズになります。このガイドでは、効率的なデータ管理方法について説明します。

## 問題点

- **データの無制限蓄積**: 検索履歴が削除されることなく蓄積され続ける
- **パフォーマンスの劣化**: テーブルサイズが大きくなると検索性能が低下
- **ストレージ容量の圧迫**: ディスク容量の無駄遣い

## 解決策

### 1. データクリーンアップ機能

`SearchLogService`クラスに以下の機能を追加しました：

- **古いデータの削除**: 指定した期間より古いデータを削除
- **重要なデータのアーカイブ**: 人気検索ワードを別テーブルに保存
- **統計情報の取得**: データベースの状況を監視

### 2. 自動化スクリプト

#### クリーンアップスクリプト (`scripts/cleanup_search_history.php`)

```bash
# 統計情報を表示
php scripts/cleanup_search_history.php --stats

# 90日より古いデータをアーカイブしてから削除
php scripts/cleanup_search_history.php 90 --archive

# 30日より古いデータを削除（アーカイブなし）
php scripts/cleanup_search_history.php 30
```

#### 定期実行設定

**Linux/Mac (cron):**
```bash
# 毎週日曜日午前2時にクリーンアップ実行
0 2 * * 0 cd /path/to/project && php scripts/cleanup_search_history.php 90 --archive >> /var/log/search_cleanup.log 2>&1

# 毎月1日午前1時に統計情報確認
0 1 1 * * cd /path/to/project && php scripts/cleanup_search_history.php --stats >> /var/log/search_stats.log 2>&1
```

**Windows (タスクスケジューラー):**
```cmd
# クリーンアップタスク
schtasks /create /tn "SearchHistoryCleanup" /tr "php C:\path\to\project\scripts\cleanup_search_history.php 90 --archive" /sc weekly /d SUN /st 02:00 /f

# 統計確認タスク
schtasks /create /tn "SearchHistoryStats" /tr "php C:\path\to\project\scripts\cleanup_search_history.php --stats" /sc monthly /d 1 /st 01:00 /f
```

### 3. Web管理画面

`admin/search_history_management.php`でブラウザから管理できます：

- データベース統計情報の表示
- 推奨事項の確認
- 手動クリーンアップの実行

アクセス方法：
```
https://your-domain.com/admin/search_history_management.php?key=admin_search_history_2024
```

## 推奨設定

### データ保持期間

| 環境 | 推奨期間 | 理由 |
|------|----------|------|
| 開発環境 | 30日 | テストデータの蓄積を防ぐ |
| ステージング環境 | 60日 | 本番前の検証用 |
| 本番環境 | 90日 | バランスの取れた期間 |

### アーカイブ設定

- **有効にする場合**: 重要な検索データを長期保存したい場合
- **無効にする場合**: ディスク容量を節約したい場合

## 監視項目

### アラート条件

- 総レコード数が10万件を超えた場合
- 月間レコード数が1万件を超えた場合
- 最も古いデータが180日を超えた場合

### 定期チェック項目

- テーブルサイズの増加率
- クリーンアップの実行結果
- アーカイブテーブルのサイズ

## トラブルシューティング

### よくある問題

1. **クリーンアップが実行されない**
   - cronジョブまたはタスクスケジューラーの設定を確認
   - ログファイルでエラーメッセージを確認

2. **ディスク容量が不足**
   - データ保持期間を短縮
   - アーカイブ機能を無効化

3. **パフォーマンスが低下**
   - インデックスの最適化を実行
   - より頻繁なクリーンアップを設定

### ログファイルの場所

- **Linux/Mac**: `/var/log/search_cleanup.log`
- **Windows**: イベントログまたは指定したログファイル

## セキュリティ考慮事項

1. **管理画面のアクセス制御**
   - 本番環境では適切な認証機能を実装
   - アクセスキーを環境変数で管理

2. **データの機密性**
   - 個人情報が含まれる可能性があるため、適切なデータ保護措置を講じる
   - ログファイルのアクセス権限を制限

## 今後の改善案

1. **自動スケーリング**: データ量に応じて保持期間を自動調整
2. **データ圧縮**: 古いデータの圧縮による容量削減
3. **分散アーカイブ**: 外部ストレージへの自動アーカイブ
4. **リアルタイム監視**: ダッシュボードでのリアルタイム監視

## 関連ファイル

- `src/Services/SearchLogService.php` - メインのサービスクラス
- `scripts/cleanup_search_history.php` - クリーンアップスクリプト
- `scripts/setup_cron_jobs.sh` - Linux/Mac用設定スクリプト
- `scripts/setup_cron_jobs.ps1` - Windows用設定スクリプト
- `admin/search_history_management.php` - Web管理画面
- `database/popular_searches_schema.sql` - データベーススキーマ
