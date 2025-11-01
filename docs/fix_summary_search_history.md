# 検索履歴記録問題の修正まとめ

## 修正日時
2025年1月

## 問題の概要

4つの `searchType`（`text`, `architect`, `prefecture`, `building`）があるが、`global_search_history` テーブルでは `search_type = 'text'` のレコードのみが大部分を占めていた。

### 原因
- `logSearch()` は正常に動作（テキスト検索時のみ）
- `logPageView()` が以下のケースで呼ばれていない：
  1. 都道府県バッジクリック（`?prefectures=Hyogo&lang=ja`）
  2. 建築家ページアクセス（`/architects/{slug}/?lang=ja`）
  3. 建築物ページアクセス（`/buildings/{slug}/?lang=ja`）

## 実施した修正

### 1. HomeController::building() にログ記録処理を追加

**ファイル**: `src/Controllers/HomeController.php`

建築物詳細ページ（`/buildings/{slug}`）で `HomeController@building` が呼ばれる場合のログ記録処理を追加。

```php
// 建築物ページ閲覧ログを記録
try {
    require_once __DIR__ . '/../../src/Services/SearchLogService.php';
    $searchLogService = new SearchLogService();
    $lang = $_GET['lang'] ?? 'ja';
    $searchLogService->logPageView('building', $slug, $building['title'] ?? $building['titleEn'] ?? $slug, [
        'building_id' => $building['building_id'] ?? null,
        'lang' => $lang
    ]);
} catch (Exception $e) {
    error_log("Building page view log error: " . $e->getMessage());
}
```

### 2. HomeController::architect() にログ記録処理を追加

**ファイル**: `src/Controllers/HomeController.php`

建築家詳細ページ（`/architects/{slug}`）で `HomeController@architect` が呼ばれる場合のログ記録処理を追加。

```php
// 建築家ページ閲覧ログを記録
try {
    require_once __DIR__ . '/../../src/Services/SearchLogService.php';
    $searchLogService = new SearchLogService();
    $lang = $_GET['lang'] ?? 'ja';
    $searchLogService->logPageView('architect', $slug, $architect['name_ja'] ?? $architect['name_en'] ?? $slug, [
        'architect_id' => $architect['individual_architect_id'] ?? null,
        'lang' => $lang
    ]);
} catch (Exception $e) {
    error_log("Architect page view log error: " . $e->getMessage());
}
```

### 3. index.php の searchByBuildingSlug() にログ記録処理を追加

**ファイル**: `index.php`

`index.php` で `building_slug` パラメータが処理される場合のログ記録処理を追加。

```php
if ($currentBuilding) {
    // 建築物ページ閲覧ログを記録
    try {
        require_once __DIR__ . '/src/Services/SearchLogService.php';
        $searchLogService = new SearchLogService();
        $searchLogService->logPageView('building', $this->searchParams['buildingSlug'], $currentBuilding['title'] ?? $currentBuilding['titleEn'] ?? $this->searchParams['buildingSlug'], [
            'building_id' => $currentBuilding['building_id'] ?? null,
            'lang' => $this->lang
        ]);
    } catch (Exception $e) {
        error_log("Building page view log error: " . $e->getMessage());
    }
    // ...
}
```

### 4. index.php の searchWithMultipleConditions() に都道府県パラメータのみの場合のログ記録処理を追加

**ファイル**: `index.php`

都道府県パラメータのみが指定された場合（`?prefectures=Hyogo&lang=ja`）のログ記録処理を追加。

```php
// 都道府県ページ閲覧ログを記録（都道府県パラメータのみの場合）
if (!empty($this->searchParams['prefectures']) && empty($this->searchParams['query']) && empty($this->searchParams['completionYears'])) {
    try {
        require_once __DIR__ . '/src/Services/SearchLogService.php';
        $searchLogService = new SearchLogService();
        
        // 都道府県名の英語→日本語変換
        $prefectureTranslations = $this->getPrefectureTranslations();
        $prefectureName = $this->searchParams['prefectures'];
        if ($this->lang === 'ja' && isset($prefectureTranslations[$this->searchParams['prefectures']])) {
            $prefectureName = $prefectureTranslations[$this->searchParams['prefectures']];
        }
        
        $searchLogService->logPageView('prefecture', $this->searchParams['prefectures'], $prefectureName, [
            'lang' => $this->lang,
            'hasPhotos' => $this->searchParams['hasPhotos'],
            'hasVideos' => $this->searchParams['hasVideos'],
            'prefecture_ja' => $prefectureTranslations[$this->searchParams['prefectures']] ?? $this->searchParams['prefectures'],
            'prefecture_en' => $this->searchParams['prefectures']
        ]);
    } catch (Exception $e) {
        error_log("Prefecture page view log error: " . $e->getMessage());
    }
}
```

### 5. index.php に getPrefectureTranslations() メソッドを追加

**ファイル**: `index.php`

都道府県名の英語→日本語変換用のメソッドを追加。既存の `getPrefectureDisplayName()` でも再利用できるように実装。

```php
/**
 * 都道府県翻訳データの取得
 */
private function getPrefectureTranslations() {
    return [
        'Hokkaido' => '北海道',
        'Aomori' => '青森県',
        // ... 全47都道府県のマッピング
    ];
}
```

## 修正後の動作

### 正常に記録されるようになった検索タイプ

1. **text**（テキスト検索）: 既に正常動作 ✅
   - `BuildingService::searchWithMultipleConditions()` の `logSearch()` で記録

2. **prefecture**（都道府県バッジ）: 修正により記録されるようになる ✅
   - `index.php` の `searchWithMultipleConditions()` で `logPageView()` を呼び出し

3. **architect**（建築家ページ）: 修正により記録されるようになる ✅
   - `HomeController::architect()` で `logPageView()` を呼び出し
   - `index.php` の `searchByArchitectSlug()` で `CachedBuildingService` 経由の場合も `logPageView()` を呼び出し
   - `src/Views/includes/functions.php` の `searchBuildingsByArchitectSlug()` でも既に実装済み（フォールバック時）

4. **building**（建築物ページ）: 修正により記録されるようになる ✅
   - `HomeController::building()` で `logPageView()` を呼び出し
   - `index.php` の `searchByBuildingSlug()` でも `logPageView()` を呼び出し

### 追加修正（建築家ページ）

**問題**: `CachedBuildingService::searchByArchitectSlug()` 経由の場合、ログ記録処理が実行されていなかった

**解決策**: `index.php` の `searchByArchitectSlug()` メソッドで、`CachedBuildingService` 経由の結果を受け取った後にログ記録処理を追加

```php
// CachedBuildingService経由の場合はログ記録処理が実行されないため、ここで記録
if ($this->cachedBuildingService && $searchResult && isset($searchResult['architectInfo']) && $searchResult['architectInfo']) {
    try {
        require_once __DIR__ . '/src/Services/SearchLogService.php';
        $searchLogService = new SearchLogService();
        $architectInfo = $searchResult['architectInfo'];
        $searchLogService->logPageView('architect', $this->searchParams['architectsSlug'], $architectInfo['name_ja'] ?? $architectInfo['name_en'] ?? $this->searchParams['architectsSlug'], [
            'architect_id' => $architectInfo['individual_architect_id'] ?? null,
            'lang' => $this->lang
        ]);
    } catch (Exception $e) {
        error_log("Architect page view log error: " . $e->getMessage());
    }
}
```

## 重複防止チェック

すべての `logPageView()` 呼び出しでは、`SearchLogService::isDuplicatePageView()` により以下がチェックされます：

- 同一セッションまたは同一IPアドレスで
- 5分以内の同一ページ閲覧は除外される

これにより、短時間の連続アクセスによる重複記録を防止しています。

## テスト確認事項

修正後、以下の動作を確認してください：

1. **都道府県バッジクリック時**
   - URL: `https://kenchikuka.com/?prefectures=Hyogo&lang=ja`
   - `global_search_history` テーブルに `search_type = 'prefecture'` のレコードが追加されることを確認

2. **建築家ページアクセス時**
   - URL: `https://kenchikuka.com/architects/mobility-design-kobo/?lang=ja`
   - `global_search_history` テーブルに `search_type = 'architect'` のレコードが追加されることを確認

3. **建築物ページアクセス時**
   - URL: `https://kenchikuka.com/buildings/nkr?lang=ja`
   - `global_search_history` テーブルに `search_type = 'building'` のレコードが追加されることを確認

4. **テキスト検索時（既存機能の確認）**
   - URL: `https://kenchikuka.com/?q=東京&lang=ja`
   - `global_search_history` テーブルに `search_type = 'text'` のレコードが追加されることを確認

## SQL確認クエリ

修正後の動作確認用：

```sql
-- 各search_typeの件数を確認
SELECT search_type, COUNT(*) as count 
FROM global_search_history 
WHERE searched_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY search_type
ORDER BY count DESC;

-- 最新10件を確認
SELECT id, query, search_type, JSON_EXTRACT(filters, '$.pageType') as pageType, searched_at
FROM global_search_history
ORDER BY searched_at DESC
LIMIT 10;
```

## 注意事項

- エラーが発生しても例外を投げず、`error_log` に記録して処理を続行します
- 重複防止チェックにより、5分以内の同一ページ閲覧は記録されません
- セッション管理が正常に動作していることを確認してください

