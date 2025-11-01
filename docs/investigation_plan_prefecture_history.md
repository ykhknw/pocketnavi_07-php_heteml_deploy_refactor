# 検索履歴記録不備 - 原因究明計画

## 問題の概要

- **正常動作**: フォームでのテキスト検索（`?q=検索語&lang=ja`）では `global_search_history` テーブルにデータが追加される（`search_type = 'text'`）
- **不具合1**: 都道府県バッジクリック（`?prefectures=Hyogo&lang=ja`）では `global_search_history` テーブルにデータが追加されない（`search_type = 'prefecture'` が記録されない）
- **不具合2**: 建築家ページアクセス（`/architects/{slug}/?lang=ja`）では `global_search_history` テーブルにデータが追加されない（`search_type = 'architect'` が記録されない）
- **不具合3**: 建築物ページアクセス（`/buildings/{slug}/?lang=ja`）では `global_search_history` テーブルにデータが追加されない（`search_type = 'building'` が記録されない）

## 問題の本質

4つの `searchType`（`text`, `architect`, `prefecture`, `building`）があるが、現状 `global_search_history` テーブルでは `search_type = 'text'` のレコードのみが大部分を占めている。これは以下を示唆している：

1. **`logSearch()` は正常に動作している**（テキスト検索時）
   - `BuildingService::searchWithMultipleConditions()` 内で、`$query` が空でない場合のみ呼ばれる

2. **`logPageView()` が正しく呼ばれていない**（建築家、建築物、都道府県ページ閲覧時）
   - ルーティング方式やエントリポイントによって、ログ記録処理が実行されていない

## 調査計画

### フェーズ1: 現在の実装の確認

#### 1.1 都道府県パラメータの処理フロー確認

**確認ポイント**:
- [ ] メインのエントリポイント（`index.php`, `index_production.php` など）で都道府県パラメータがどのように処理されているか
- [ ] `BuildingService::searchWithMultipleConditions()` メソッドが呼ばれているか
- [ ] 都道府県パラメータのみの場合の処理分岐を確認

**調査対象ファイル**:
- `index.php` (主要エントリポイント)
- `src/Services/BuildingService.php` (検索処理)
- `src/Services/SearchLogService.php` (ログ記録処理)

#### 1.2 ログ記録処理の呼び出し条件確認

**確認ポイント**:
- [ ] `BuildingService::searchWithMultipleConditions()` 内の `logSearch()` 呼び出し条件
  - 現在の実装: `if (!empty($query))` のため、`$query` が空の場合は呼ばれない
- [ ] 都道府県パラメータのみの場合に `logPageView()` が呼ばれる実装があるか
- [ ] `index_refactored_complete.php` に存在する `logPrefecturePageView()` が他のエントリポイントにも実装されているか

**確認箇所**:
```php
// src/Services/BuildingService.php (57-78行目)
public function searchWithMultipleConditions(...) {
    // 検索ログを記録
    if (!empty($query)) {  // ← この条件が問題の可能性
        $searchLogService->logSearch(...);
    }
    // ...
}
```

### フェーズ2: コードパス分析

#### 2.1 都道府県バッジクリック時の処理フロー追跡

**調査手順**:
1. 都道府県バッジの実装箇所を特定
   - `src/Views/includes/building_card.php` (都道府県バッジ表示)
   - バッジクリック時のリンク先を確認

2. URLパラメータ `?prefectures=Hyogo` がどのように処理されるか追跡
   - エントリポイントでのパラメータ取得
   - 検索メソッドへの引き渡し
   - ログ記録メソッドの呼び出し有無

#### 2.2 条件分岐の確認

**確認項目**:
- [ ] `$query` が空で `$prefectures` のみが設定されている場合の処理分岐
- [ ] `logSearch()` が呼ばれる条件と `logPageView()` が呼ばれる条件の違い
- [ ] 都道府県パラメータのみの場合に適切なログ記録処理が実行されるか

### フェーズ3: 実装パターンの確認

#### 3.1 他の類似機能との比較

**比較対象**:
- [ ] 建築家ページ（`/architects/{slug}`）閲覧時の `logPageView()` 呼び出し
- [ ] 建築物ページ（`/buildings/{slug}`）閲覧時の `logPageView()` 呼び出し
- [ ] `index_refactored_complete.php` の `logPrefecturePageView()` 実装

**参考実装** (`index_refactored_complete.php` 176-178行目):
```php
// 都道府県ページ閲覧ログを記録
if (!empty($this->searchParams['prefectures']) && empty($this->searchParams['query']) && empty($this->searchParams['completionYears'])) {
    $this->logPrefecturePageView();
}
```

#### 3.2 エントリポイントごとの実装差異確認

**確認対象**:
- [ ] `index.php` (メインエントリポイント)
- [ ] `index_production.php` (本番用)
- [ ] `index_refactored_complete.php` (リファクタリング版)
- [ ] その他のエントリポイント

各ファイルで都道府県パラメータ処理とログ記録の実装を比較

### フェーズ4: データベース確認

#### 4.1 実際のデータ登録状況確認

**SQL確認クエリ**:
```sql
-- 都道府県検索の記録を確認
SELECT * FROM global_search_history 
WHERE search_type = 'prefecture' 
ORDER BY searched_at DESC 
LIMIT 10;

-- ページタイプがprefectureの記録を確認
SELECT * FROM global_search_history 
WHERE JSON_EXTRACT(filters, '$.pageType') = 'prefecture'
ORDER BY searched_at DESC 
LIMIT 10;
```

#### 4.2 検索タイプの分布確認

**確認クエリ**:
```sql
-- 検索タイプ別の件数
SELECT search_type, COUNT(*) as count 
FROM global_search_history 
GROUP BY search_type;
```

### フェーズ5: 想定される原因の仮説

#### 仮説1: logSearch()の呼び出し条件不足
**問題**: `BuildingService::searchWithMultipleConditions()` で `if (!empty($query))` のため、都道府県のみの場合に `logSearch()` が呼ばれない

**検証方法**: 
- `BuildingService.php` の該当箇所を確認
- `$prefectures` のみの場合の分岐追加の必要性を確認

#### 仮説2: logPageView()の未実装
**問題**: 都道府県ページ閲覧時の `logPageView()` 呼び出しが実装されていない

**検証方法**:
- 各エントリポイントで都道府県パラメータのみの場合の `logPageView()` 呼び出し有無を確認
- `index_refactored_complete.php` の実装が他のファイルにも存在するか確認

#### 仮説3: 重複防止チェックによる除外
**問題**: `isDuplicatePageView()` などの重複防止チェックで除外されている

**検証方法**:
- `SearchLogService::isDuplicatePageView()` の実装を確認
- 都道府県検索の場合の重複チェック条件を確認

#### 仮説4: エラーハンドリングによる例外処理
**問題**: エラーが発生しているが例外が握りつぶされている

**検証方法**:
- PHPエラーログを確認
- `try-catch` ブロックで例外が握りつぶされていないか確認

### フェーズ6: 検証方法

#### 6.1 デバッグログの追加

以下の箇所にデバッグログを追加して検証:

1. **エントリポイント** (`index.php` など):
   ```php
   error_log("DEBUG: prefectures param = " . ($_GET['prefectures'] ?? 'none'));
   error_log("DEBUG: query param = " . ($_GET['q'] ?? 'none'));
   ```

2. **BuildingService::searchWithMultipleConditions()**:
   ```php
   error_log("DEBUG: searchWithMultipleConditions called - query: '$query', prefectures: '$prefectures'");
   error_log("DEBUG: logSearch will be called: " . (!empty($query) ? 'YES' : 'NO'));
   ```

3. **SearchLogService::logSearch()** / **logPageView()**:
   ```php
   error_log("DEBUG: logSearch called with query: '$query', searchType: '$searchType'");
   error_log("DEBUG: logPageView called with pageType: '$pageType', identifier: '$identifier'");
   ```

#### 6.2 テスト手順

1. **都道府県バッジクリック前の状態確認**
   - `global_search_history` テーブルの最新レコードを確認

2. **都道府県バッジクリック実行**
   - URL: `https://kenchikuka.com/?prefectures=Hyogo&lang=ja`
   - ブラウザで実際にアクセス

3. **アクセス後の状態確認**
   - `global_search_history` テーブルに新しいレコードが追加されたか確認
   - PHPエラーログにデバッグメッセージが出力されたか確認

4. **ログ記録処理の呼び出し確認**
   - デバッグログから `logSearch()` または `logPageView()` が呼ばれたか確認
   - 呼ばれていない場合、どの段階で処理が停止しているか特定

### フェーズ7: 調査結果のまとめ

#### 確認事項チェックリスト

- [ ] 都道府県パラメータの処理フローが明確になったか
- [ ] `logSearch()` または `logPageView()` が呼ばれているか
- [ ] 呼ばれていない場合、どの条件で除外されているか
- [ ] エラーが発生している場合、エラーの内容は何か
- [ ] 重複防止チェックで除外されていないか
- [ ] 他のエントリポイントとの実装差分は何か

#### 原因特定後の対応方針

1. **原因が明確になったら**:
   - 修正方法を提案
   - 影響範囲を確認
   - テスト計画を策定

2. **修正実装**:
   - 適切なログ記録処理の追加
   - 既存の重複防止チェックとの整合性確認
   - エラーハンドリングの改善（必要に応じて）

## 現在判明している情報

### 確認済みの実装状況

#### 1. BuildingService::searchWithMultipleConditions() の問題点

**ファイル**: `src/Services/BuildingService.php` (57-78行目)

```php
public function searchWithMultipleConditions(...) {
    // 検索ログを記録
    if (!empty($query)) {  // ← 都道府県のみの場合は $query が空のため実行されない
        $searchLogService->logSearch(...);
    }
    // ...
}
```

**問題**: `$query` が空の場合（都道府県パラメータのみの場合）、`logSearch()` が呼ばれない

#### 2. index_refactored_complete.php での対応実装

**ファイル**: `index_refactored_complete.php` (176-178行目, 222行目以降)

```php
// 都道府県ページ閲覧ログを記録
if (!empty($this->searchParams['prefectures']) && empty($this->searchParams['query']) && empty($this->searchParams['completionYears'])) {
    $this->logPrefecturePageView();
}

private function logPrefecturePageView() {
    try {
        require_once __DIR__ . '/src/Services/SearchLogService.php';
        $searchLogService = new SearchLogService();
        
        // 都道府県名の英語→日本語変換
        $prefectureTranslations = [...];
        $prefectureJa = $prefectureTranslations[$this->searchParams['prefectures']] ?? $this->searchParams['prefectures'];
        
        $searchLogService->logPageView('prefecture', $this->searchParams['prefectures'], $prefectureJa, [
            'lang' => $this->lang,
            'hasPhotos' => $this->searchParams['hasPhotos'],
            'hasVideos' => $this->searchParams['hasVideos'],
        ]);
    } catch (Exception $e) {
        error_log("Prefecture page view log error: " . $e->getMessage());
    }
}
```

**確認**: `logPrefecturePageView()` は `index_refactored_complete.php` にのみ実装されている

#### 3. メインエントリポイント（index.php）の確認が必要

**調査が必要な点**:
- `index.php` で都道府県パラメータのみの場合の処理
- `logPrefecturePageView()` 相当の処理が実装されているか
- `BuildingService::searchWithMultipleConditions()` を呼ぶ前にログ記録処理があるか

### 想定される主な原因

**都道府県バッジの問題**:
1. **メインエントリポイント（`index.php`）に都道府県パラメータのみの場合のログ記録処理が実装されていない**
   - `index_refactored_complete.php` には実装があるが、実際に使用されている `index.php` には実装がない可能性が高い

2. **`BuildingService::searchWithMultipleConditions()` の `logSearch()` 呼び出し条件が `$query` のみをチェックしている**
   - 都道府県パラメータのみの場合は `$query` が空のため、ログ記録がスキップされる

**建築家ページの問題**:
3. **`HomeController::architect()` にログ記録処理が実装されていない**
   - `src/Views/includes/functions.php` の `searchBuildingsByArchitectSlug()` には実装があるが、`HomeController::architect()` には実装がない
   - ルーティングが `/architects/{slug}` → `HomeController@architect` の場合、ログ記録が実行されない

4. **ルーティング方式による処理の違い**
   - `router.php` では `/architects/{slug}` が `index.php?architects_slug=slug` に書き換えられる
   - しかし、実際のルーティング（`HomeController`経由）が使われる場合、`searchBuildingsByArchitectSlug()` が呼ばれない可能性がある

## 建築家ページの問題の追加調査

### 確認済みの実装状況（建築家ページ）

#### 1. HomeController::architect() の実装

**ファイル**: `src/Controllers/HomeController.php` (142-171行目)

```php
public function architect($slug) {
    // データベース接続
    // 建築家情報の取得
    // ビューの表示
    // ← ログ記録処理がない！
}
```

**問題**: `logPageView()` が呼ばれていない

#### 2. functions.php の searchBuildingsByArchitectSlug() の実装

**ファイル**: `src/Views/includes/functions.php` (342-353行目)

```php
// 建築家ページ閲覧ログを記録
if ($architectInfo) {
    try {
        require_once __DIR__ . '/../../Services/SearchLogService.php';
        $searchLogService = new SearchLogService();
        $searchLogService->logPageView('architect', $architectSlug, ...);
    } catch (Exception $e) {
        error_log("Architect page view log error: " . $e->getMessage());
    }
}
```

**確認**: `searchBuildingsByArchitectSlug()` 内には実装があるが、この関数が呼ばれているかどうかが問題

#### 3. ルーティングの確認が必要

**調査項目**:
- [ ] `/architects/{slug}` がどのように処理されているか
  - `router.php` の書き換えルール（`/architects/{slug}` → `index.php?architects_slug=slug`）
  - `HomeController@architect` へのルーティング
- [ ] 実際の本番環境でどちらのルートが使われているか
- [ ] `index.php` で `architects_slug` パラメータが処理されている場合、`searchBuildingsByArchitectSlug()` が呼ばれているか

### 建築家ページの想定原因

**最も可能性が高い原因**:
1. **`HomeController::architect()` にログ記録処理が未実装**
   - ルーター経由で `HomeController@architect` が呼ばれる場合、`logPageView()` が実行されない

2. **`searchBuildingsByArchitectSlug()` が呼ばれていない**
   - `index.php` で `architects_slug` パラメータが処理される場合、`searchBuildingsByArchitectSlug()` が呼ばれていなければログ記録されない

3. **重複防止チェックで除外されている可能性**
   - `isDuplicatePageView()` で5分以内の同一ページ閲覧が除外されている可能性

### 建築物ページの問題の追加調査

#### 確認済みの実装状況（建築物ページ）

#### 1. HomeController::building() の実装

**ファイル**: `src/Controllers/HomeController.php` (108-137行目)

```php
public function building($slug) {
    // データベース接続
    // 建物情報の取得
    // ビューの表示
    // ← ログ記録処理がない！
}
```

**問題**: `logPageView()` が呼ばれていない

#### 2. index.php の searchByBuildingSlug() の実装

**ファイル**: `index.php` (429-454行目)

```php
private function searchByBuildingSlug($limit) {
    // 建物情報を取得
    // 検索結果を返す
    // ← ログ記録処理がない！
}
```

**問題**: `logPageView()` が呼ばれていない

#### 3. index_refactored_complete.php の実装

**ファイル**: `index_refactored_complete.php` (206-217行目, 94-112行目)

```php
// 建築物ページビューログの記録
if ($this->searchParams['buildingSlug'] && isset($this->searchResult['currentBuilding'])) {
    $this->logBuildingPageView($this->searchResult['currentBuilding']);
}

private function logBuildingPageView($currentBuilding) {
    $searchLogService->logPageView('building', ...);
}
```

**確認**: `index_refactored_complete.php` には実装があるが、メインの `index.php` には実装がない

#### 4. index.php.org2025-10-12 の実装

**ファイル**: `index.php.org2025-10-12` (56-66行目)

```php
if ($buildingSlug) {
    $currentBuilding = getBuildingBySlug($buildingSlug, $lang);
    if ($currentBuilding) {
        // 建築物ページ閲覧ログを記録
        $searchLogService->logPageView('building', $buildingSlug, ...);
    }
}
```

**確認**: 過去のバージョンには実装があったが、現在の `index.php` では削除されている可能性

### 建築物ページの想定原因

**最も可能性が高い原因**:
1. **`HomeController::building()` にログ記録処理が未実装**
   - ルーター経由で `HomeController@building` が呼ばれる場合、`logPageView()` が実行されない

2. **`index.php` の `searchByBuildingSlug()` にログ記録処理が未実装**
   - `index.php` で `building_slug` パラメータが処理される場合、ログ記録処理がない

3. **過去の実装が削除されている**
   - `index.php.org2025-10-12` には実装があったが、現在の `index.php` では削除されている可能性

## 次のステップ

### フェーズ1: エントリポイントとルーティングの確認

1. **`index.php` の実装を確認**
   - 都道府県パラメータ処理の有無とログ記録処理
   - 建築家スラッグパラメータ処理の有無とログ記録処理
   - 建築物スラッグパラメータ処理の有無とログ記録処理
   - `logPrefecturePageView()` 相当の処理の有無
   - `searchBuildingsByArchitectSlug()` の呼び出し有無とログ記録処理
   - `searchByBuildingSlug()` 内のログ記録処理の有無

2. **実際に使用されているエントリポイントを特定**
   - 本番環境でどのファイルが使用されているか確認
   - ルーティング方式（`router.php` vs `HomeController`）の確認

### フェーズ2: 各問題の原因特定

3. **都道府県バッジの問題**
   - `BuildingService::searchWithMultipleConditions()` の条件分岐を確認
   - 都道府県パラメータのみの場合の処理フローを追跡

4. **建築家ページの問題**
   - `HomeController::architect()` にログ記録処理が実装されているか確認
   - `searchBuildingsByArchitectSlug()` が実際に呼ばれているか確認
   - 重複防止チェックで除外されていないか確認

5. **建築物ページの問題**
   - `HomeController::building()` にログ記録処理が実装されているか確認
   - `index.php` の `searchByBuildingSlug()` にログ記録処理が実装されているか確認
   - 過去の実装（`index.php.org2025-10-12`）との差分を確認
   - 重複防止チェックで除外されていないか確認

### フェーズ3: 修正方針の決定

5. **都道府県バッジの修正**
   - `index.php` に `logPrefecturePageView()` を追加するか
   - `BuildingService::searchWithMultipleConditions()` を修正するか
   - 両方修正するか

6. **建築家ページの修正**
   - `HomeController::architect()` に `logPageView()` を追加するか
   - `index.php` の `architects_slug` 処理にログ記録を追加するか

7. **建築物ページの修正**
   - `HomeController::building()` に `logPageView()` を追加するか
   - `index.php` の `searchByBuildingSlug()` にログ記録処理を追加するか
   - 過去の実装（`index.php.org2025-10-12`）を参考に復元するか

### フェーズ4: 修正実装とテスト

7. **修正実装**
   - 両方の問題を解決する実装を追加
   - 既存の重複防止チェックとの整合性確認

8. **テスト**
   - テキスト検索時のデータ追加を確認（`search_type = 'text'`）
   - 都道府県バッジクリック時のデータ追加を確認（`search_type = 'prefecture'`）
   - 建築家ページアクセス時のデータ追加を確認（`search_type = 'architect'`）
   - 建築物ページアクセス時のデータ追加を確認（`search_type = 'building'`）
   - 重複防止チェックの動作を確認
   - 各 `search_type` が正しく記録されることを確認

## まとめ：4つのsearchTypeの問題の共通点

### 問題の構造

すべての問題は同じ根本原因を持っています：

1. **`logSearch()` は正常に動作**
   - `BuildingService::searchWithMultipleConditions()` 内で、`$query` が空でない場合にのみ呼ばれる
   - テキスト検索時のみ実行される

2. **`logPageView()` が呼ばれていない**
   - 建築家、建築物、都道府県ページで `logPageView()` が実装されていない、または呼ばれていない
   - ルーティング方式（`HomeController` vs `index.php` 直接処理）による違い
   - エントリポイントごとの実装差分

### 修正が必要な箇所

1. **`HomeController` クラス**
   - `architect()` メソッドにログ記録処理を追加
   - `building()` メソッドにログ記録処理を追加

2. **`index.php` クラス**
   - `searchByBuildingSlug()` メソッドにログ記録処理を追加
   - 都道府県パラメータのみの場合のログ記録処理を追加
   - `searchByArchitectSlug()` で `searchBuildingsByArchitectSlug()` が呼ばれていることを確認

3. **共通の確認事項**
   - 実際に使用されているエントリポイントの特定
   - ルーティング方式の統一または各ルートでの処理追加

