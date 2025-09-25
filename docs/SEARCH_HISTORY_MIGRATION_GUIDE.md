# 検索履歴filters移行ガイド

## 概要

本番環境の`global_search_history`テーブルの`filters`カラムを新形式に移行し、英語ユーザー向け表示用データを追加します。

## 移行対象

- **テーブル**: `global_search_history`
- **条件**: `search_type = 'building'`
- **対象**: 旧形式のfiltersデータ（`title_en`が存在しない）

## 移行内容

### 旧形式（移行前）
```json
{
  "lang": "ja",
  "title": "新島グラスアートセンター",
  "pageType": "building",
  "identifier": "niijima-glass-art-center",
  "building_id": 2459
}
```

### 新形式（移行後）
```json
{
  "lang": "ja",
  "title": "新島グラスアートセンター",
  "pageType": "building",
  "identifier": "niijima-glass-art-center",
  "building_id": 2459,
  "building_slug": "niijima-glass-art-center",
  "building_title_ja": "新島グラスアートセンター",
  "building_title_en": "Niijima Glass Art Center",
  "title_en": "Niijima Glass Art Center"
}
```

## 実行手順

### 1. 事前準備

```bash
# スクリプトディレクトリに移動
cd scripts

# 実行権限の確認（Linux/Macの場合）
chmod +x migrate_search_history_filters.php
```

### 2. 実行方法

#### Windows環境
```cmd
# バッチファイル実行
scripts\run_migration.bat

# または直接実行
php scripts\migrate_search_history_filters.php
```

#### Linux/Mac環境
```bash
# 直接実行
php scripts/migrate_search_history_filters.php
```

### 3. 実行フロー

1. **統計情報表示**: 移行対象データの件数を表示
2. **確認プロンプト**: 実行確認（y/N）
3. **移行実行**: 対象データを順次処理
4. **進捗表示**: 100件ごとに進捗を表示
5. **結果表示**: 成功・エラー件数を表示
6. **検証**: 移行後のデータを確認

## 実行例

```
=== 移行前統計 ===
building検索総数: 15420件
移行対象数: 12350件
既に移行済み数: 3070件
==================

移行を実行しますか？ (y/N): y

=== 検索履歴filters移行開始 ===
対象レコード数: 12350
移行成功 (ID: 1, building_id: 2459)
移行成功 (ID: 2, building_id: 1765)
...
処理済み: 100件
...
=== 移行完了 ===
成功: 12345件
エラー: 5件

=== 移行後検証 ===
移行済み数: 15415件
サンプルデータ:
ID: 1
  title: 新島グラスアートセンター
  title_en: Niijima Glass Art Center
  building_id: 2459
---
```

## 注意事項

### 実行前の確認

1. **データベースバックアップ**: 必ず実行前にバックアップを取得
2. **本番環境**: 本番環境での実行は慎重に行う
3. **実行時間**: 大量データの場合は時間がかかる可能性

### エラーハンドリング

- **JSON解析エラー**: 不正なJSONデータはスキップ
- **建築物データ不存在**: 該当するbuilding_idがない場合はスキップ
- **データベースエラー**: 個別レコードのエラーは記録し、処理を継続

### ロールバック

移行に問題があった場合のロールバック手順：

```sql
-- 移行したデータを元に戻す（例）
UPDATE global_search_history 
SET filters = JSON_REMOVE(filters, '$.building_slug', '$.building_title_ja', '$.building_title_en', '$.title_en')
WHERE search_type = 'building' 
AND JSON_EXTRACT(filters, '$.title_en') IS NOT NULL;
```

## トラブルシューティング

### よくある問題

1. **接続エラー**: データベース接続情報を確認
2. **権限エラー**: データベースユーザーの権限を確認
3. **メモリ不足**: 大量データの場合はバッチサイズを調整

### ログ確認

```bash
# PHPエラーログの確認
tail -f /var/log/php_errors.log

# アプリケーションログの確認
tail -f logs/search_cleanup.log
```

## 移行後の確認

### 1. データ整合性チェック

```sql
-- 移行済みデータの確認
SELECT COUNT(*) as migrated_count
FROM global_search_history
WHERE search_type = 'building'
AND JSON_EXTRACT(filters, '$.title_en') IS NOT NULL;

-- サンプルデータの確認
SELECT id, filters
FROM global_search_history
WHERE search_type = 'building'
AND JSON_EXTRACT(filters, '$.title_en') IS NOT NULL
LIMIT 5;
```

### 2. アプリケーション動作確認

1. **人気検索表示**: 英語ユーザーでの表示確認
2. **検索機能**: 検索機能の正常動作確認
3. **パフォーマンス**: 表示速度の確認

## 関連ファイル

- `scripts/migrate_search_history_filters.php`: 移行スクリプト
- `scripts/run_migration.bat`: Windows実行用バッチファイル
- `docs/ENGLISH_FRIENDLY_POPULAR_SEARCHES.md`: 英語表示機能の詳細
