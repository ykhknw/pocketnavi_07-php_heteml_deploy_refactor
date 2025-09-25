# FTP移行テストガイド

## 概要

レンタルサーバー（HETEML）にFTPでPHPファイルをアップロードし、ブラウザで1件のテスト移行を実行する手順です。

## 対象データ

- **ID**: 24116
- **Query**: "箱の家-112 [神宮前計画]"
- **Search Type**: building
- **Building ID**: 112（推定）

## 手順

### 1. ファイルの準備

**アップロードするファイル**:
- `scripts/test_migration_single.php`

### 2. FTPアップロード

1. **FTPクライアント**（FileZilla等）でHETEMLサーバーに接続
2. **アップロード先**: ドキュメントルート（通常は`/public_html/`）
3. **ファイル名**: `test_migration_single.php`としてアップロード

### 3. ブラウザでの実行

1. **URL**: `https://your-domain.com/test_migration_single.php`
2. **初回アクセス**: プレビューモードで表示
3. **実行確認**: 「実際に更新を実行する」ボタンをクリック

### 4. 実行結果の確認

**成功時の表示**:
```
✓ データベース接続成功
✓ 更新成功！
✓ 移行テスト完了！
```

**確認項目**:
- データベース接続
- 対象レコードの取得
- 建築物データの取得
- 新しいfiltersデータの構築
- データベース更新
- 更新後の確認

## 期待される結果

### 移行前（現在）
```json
{
  "lang": "ja",
  "title": "箱の家-112 [神宮前計画]",
  "pageType": "building",
  "identifier": "hako-no-ie-112-jingumae",
  "building_id": 112
}
```

### 移行後（期待値）
```json
{
  "lang": "ja",
  "title": "箱の家-112 [神宮前計画]",
  "pageType": "building",
  "identifier": "hako-no-ie-112-jingumae",
  "building_id": 112,
  "building_slug": "hako-no-ie-112-jingumae",
  "building_title_ja": "箱の家-112 [神宮前計画]",
  "building_title_en": "Hako no Ie - 112 [Jingumae Project]",
  "title_en": "Hako no Ie - 112 [Jingumae Project]"
}
```

## トラブルシューティング

### よくある問題

1. **接続エラー**
   - データベース接続情報を確認
   - HETEMLのデータベース設定を確認

2. **権限エラー**
   - ファイルの実行権限を確認
   - データベースユーザーの権限を確認

3. **建築物データが見つからない**
   - `buildings_table_3`テーブルの存在確認
   - `building_id`の値の確認

### ログ確認

```php
// エラーログの確認（スクリプト内に追加）
error_log("Test migration: " . $message);
```

## 安全対策

### 実行前の確認

1. **バックアップ**: 必ずデータベースのバックアップを取得
2. **テスト環境**: 可能であればテスト環境で先に実行
3. **1件のみ**: このスクリプトは1件のみの処理

### 実行後の確認

1. **データ整合性**: 更新後のデータが正しいか確認
2. **アプリケーション動作**: 人気検索の表示確認
3. **ロールバック準備**: 問題があった場合の復旧手順を準備

## 次のステップ

テストが成功した場合：

1. **全件移行**: `migrate_search_history_filters.php`の実行
2. **バッチ処理**: 大量データの場合はバッチサイズの調整
3. **監視**: 移行後のアプリケーション動作監視

## 関連ファイル

- `scripts/test_migration_single.php`: テスト用スクリプト
- `scripts/migrate_search_history_filters.php`: 本格移行用スクリプト
- `docs/SEARCH_HISTORY_MIGRATION_GUIDE.md`: 詳細移行ガイド
