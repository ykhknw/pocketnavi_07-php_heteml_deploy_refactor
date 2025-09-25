# 高負荷環境での検索履歴管理ガイド

## 概要

**データ増加率: 1日12.4MB（12時間で6.2MB）** の高負荷環境での検索履歴管理について説明します。

## 🚨 緊急対応が必要な理由

### データ増加率の分析
- **現在**: 6.2MB（12時間）
- **1日**: 12.4MB
- **1週間**: 86.8MB
- **1ヶ月**: 372MB

### HETEML制限との比較
- **HETEML制限**: 100MB
- **警告閾値**: 80MB
- **緊急閾値**: 90MB

**結論**: 従来の設定（60-90日保持）では、1週間以内に制限に達します。

## 🎯 推奨設定

### 1. 基本設定
- **保持期間**: 5日
- **クリーンアップ頻度**: 毎日
- **予想サイズ**: 62MB（安全範囲内）

### 2. cron設定
```bash
# 毎日午前2時にクリーンアップ実行
0 2 * * * /usr/local/bin/php /home/your-account/public_html/scripts/heteml_cleanup_search_history.php 5 --archive
```

### 3. 監視設定
- **警告**: 40MB超過
- **緊急**: 60MB超過
- **統計確認**: 週1回

## 🛠️ 実装手順

### 1. 設定ファイルの更新
`scripts/heteml_cleanup_config.php` を以下のように設定：

```php
'retention_days' => 5,       // 5日保持
'archive_threshold' => 3,    // 3回以上検索されたものをアーカイブ
```

### 2. アラート設定の更新
```php
'table_size_warning' => 40,  // 40MBで警告
'table_size_critical' => 60, // 60MBで緊急
'record_count_warning' => 20000,  // 2万件で警告
'record_count_critical' => 30000, // 3万件で緊急
```

### 3. 緊急クリーンアップスクリプトの準備
```bash
# 緊急時用（3日保持）
php scripts/emergency_cleanup.php emergency

# 段階的クリーンアップ
php scripts/emergency_cleanup.php gradual

# 統計情報確認
php scripts/emergency_cleanup.php stats
```

## 📊 監視とアラート

### 1. 日常監視
- **週1回**: Web管理画面で統計確認
- **月1回**: ログファイルの確認
- **緊急時**: 手動クリーンアップ実行

### 2. アラート条件
| 項目 | 警告 | 緊急 | 対応 |
|------|------|------|------|
| テーブルサイズ | 40MB | 60MB | 即座にクリーンアップ |
| レコード数 | 20,000件 | 30,000件 | 保持期間を短縮 |
| 実行時間 | 25秒 | 30秒 | バッチサイズを削減 |

### 3. 監視方法
```bash
# 統計情報確認
https://your-domain.com/admin/heteml_cleanup.php?key=heteml_admin_2024&action=stats

# 緊急クリーンアップ
https://your-domain.com/admin/heteml_cleanup.php?key=heteml_admin_2024&action=cleanup
```

## 🚨 緊急時の対応

### 1. 即座の対応
```bash
# 緊急クリーンアップ（3日保持）
php scripts/emergency_cleanup.php emergency
```

### 2. 段階的対応
```bash
# 段階的クリーンアップ
php scripts/emergency_cleanup.php gradual
```

### 3. 手動対応
```bash
# 1日保持でクリーンアップ
php scripts/heteml_cleanup_search_history.php 1

# アーカイブなしでクリーンアップ
php scripts/heteml_cleanup_search_history.php 3
```

## 📈 パフォーマンス最適化

### 1. バッチサイズの調整
```php
// 高負荷環境用
'batch_size' => 500,  // 1,000から500に削減
```

### 2. 実行時間の最適化
```php
// 実行時間制限
'max_execution_time' => 25,  // 30秒から25秒に削減
```

### 3. メモリ使用量の最適化
```php
// メモリ制限
'memory_limit' => '96M',  // 128MBから96MBに削減
```

## 🔄 代替案

### 1. 外部サービス連携
```yaml
# GitHub Actions での定期実行
name: High Load Cleanup
on:
  schedule:
    - cron: '0 17 * * *'  # 毎日午後5時（JST午前2時）
  workflow_dispatch:

jobs:
  cleanup:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger Cleanup
        run: |
          curl -X POST "https://your-domain.com/admin/heteml_cleanup.php" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -d "key=heteml_admin_2024&action=cleanup"
```

### 2. UptimeRobot での監視
```
# 毎日午前2時にアクセス
https://your-domain.com/admin/heteml_cleanup.php?key=heteml_admin_2024&action=cleanup
```

## 📋 チェックリスト

### 初期設定
- [ ] 設定ファイルの更新
- [ ] cron設定の変更
- [ ] 緊急クリーンアップスクリプトの配置
- [ ] Web管理画面の設定

### 日常運用
- [ ] 週1回の統計確認
- [ ] 月1回のログ確認
- [ ] アラート条件の監視
- [ ] パフォーマンスの確認

### 緊急時対応
- [ ] 緊急クリーンアップの実行
- [ ] 段階的クリーンアップの実行
- [ ] 手動クリーンアップの実行
- [ ] 設定の見直し

## ⚠️ 注意事項

### 1. データの重要性
- 5日保持では、一部の検索データが失われる可能性
- 重要な検索データはアーカイブ機能で保護
- 定期的なバックアップの実施

### 2. パフォーマンスへの影響
- 毎日のクリーンアップによる負荷
- データベースの最適化の必要性
- インデックスの定期的な再構築

### 3. 監視の重要性
- 自動化に依存せず、手動確認も実施
- ログファイルの定期的な確認
- アラート条件の見直し

## 🔧 トラブルシューティング

### よくある問題

1. **クリーンアップが完了しない**
   - バッチサイズを500に削減
   - 保持期間を3日に短縮
   - アーカイブ機能を無効化

2. **メモリ不足エラー**
   - メモリ制限を96MBに削減
   - バッチサイズを250に削減
   - 不要なデータを事前に削除

3. **実行時間超過**
   - 実行時間制限を25秒に削減
   - バッチサイズを500に削減
   - 段階的クリーンアップの実行

### 緊急時の連絡先
- システム管理者
- データベース管理者
- ホスティングプロバイダー（HETEML）

## 📚 関連ドキュメント

- `docs/HETEML_SEARCH_HISTORY_MANAGEMENT.md` - HETEML環境での基本設定
- `scripts/heteml_cleanup_config.php` - 設定ファイル
- `scripts/emergency_cleanup.php` - 緊急クリーンアップスクリプト
- `admin/heteml_cleanup.php` - Web管理画面
