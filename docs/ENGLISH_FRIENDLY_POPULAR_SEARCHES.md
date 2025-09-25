# 英語ユーザー向け「人気の検索」対応

## 概要

英語ユーザー（`lang=en`）が「人気の検索」を表示する際に、よりフレンドリーな表示を実現するため、検索履歴データの保存時に英語表示用データを追加保存する機能を実装しました。

## 実装内容

### 1. テキスト検索 (`pageType: "text"`)

**対応方針**: 英語表示用データを追加しない
- 日本語ユーザーの入力した検索ワードは日本語のまま表示
- 逐次英訳は非現実的なため、そのまま日本語で表示

**JSON例**:
```json
{
  "lang": "ja",
  "title": "安藤忠雄",
  "pageType": "text"
}
```

### 2. 都道府県検索 (`pageType: "prefecture"`)

**対応方針**: 既存の`prefecture_en`を活用
- 日本語検索時に`title_en`として`prefecturesEn`を追加保存
- 英語ユーザーには英語都道府県名を表示

**JSON例**:
```json
{
  "lang": "ja",
  "title": "東京都",
  "pageType": "prefecture",
  "identifier": "tokyo",
  "prefecture_ja": "東京都",
  "prefecture_en": "Tokyo",
  "title_en": "Tokyo"
}
```

### 3. 建築物検索 (`pageType: "building"`)

**対応方針**: `building_table_3.titleEn`を追加保存
- 日本語検索時に`title_en`として`titleEn`を追加保存
- 英語ユーザーには英語建築物名を表示

**JSON例**:
```json
{
  "lang": "ja",
  "title": "上智短期大学管理棟",
  "pageType": "building",
  "identifier": "sophia-junior-college-administration-building",
  "building_id": 1765,
  "building_title_ja": "上智短期大学管理棟",
  "building_title_en": "Sophia Junior College Administration Building",
  "title_en": "Sophia Junior College Administration Building"
}
```

### 4. 建築家検索 (`pageType: "architect"`)

**対応方針**: `individual_architects_3.name_en`を追加保存
- 日本語検索時に`title_en`として`name_en`を追加保存
- 英語ユーザーには英語建築家名を表示

**JSON例**:
```json
{
  "lang": "ja",
  "title": "安藤忠雄",
  "pageType": "architect",
  "identifier": "tadao-ando",
  "architect_id": 123,
  "architect_name_ja": "安藤忠雄",
  "architect_name_en": "Tadao Ando",
  "title_en": "Tadao Ando"
}
```

### 5. 英語検索履歴 (`lang: "en"`)

**対応方針**: 追加処理なし
- 英語ユーザーが検索した記録は既に英語で保存されている
- 追加の英語表示用データは不要

## 実装詳細

### 修正されたファイル

**1. `src/Services/SearchLogService.php`**
- `getAdditionalSearchData()` メソッドに言語パラメータを追加
- 日本語検索時のみ`title_en`を追加保存
- `logSearch()` と `logPageView()` メソッドを更新

**2. `api/popular-searches.php`**
- 表示ロジックを英語対応に更新
- 英語ユーザーの場合、`title_en`が利用可能ならそれを使用

### データ保存ロジック

```php
// 日本語検索の場合、英語表示用データを追加
if ($lang === 'ja') {
    $additionalData['title_en'] = $englishData;
}
```

### 表示ロジック

```php
// 表示用タイトルを決定（英語ユーザー向け対応）
$displayTitle = $search['query'];
if ($lang === 'en') {
    // 英語ユーザーの場合、title_enが利用可能ならそれを使用
    $filters = json_decode($search['filters'] ?? '{}', true);
    if (isset($filters['title_en']) && !empty($filters['title_en'])) {
        $displayTitle = $filters['title_en'];
    }
}
```

## 表示例

### 日本語ユーザー (`lang=ja`)
- 建築物: "上智短期大学管理棟"
- 建築家: "安藤忠雄"
- 都道府県: "東京都"
- テキスト: "安藤忠雄" (そのまま)

### 英語ユーザー (`lang=en`)
- 建築物: "Sophia Junior College Administration Building"
- 建築家: "Tadao Ando"
- 都道府県: "Tokyo"
- テキスト: "安藤忠雄" (そのまま、英訳なし)

## メリット

1. **英語ユーザーの利便性向上**: 英語で検索語が表示される
2. **データベース効率**: 既存の英語データを活用
3. **段階的対応**: テキスト検索は現状維持
4. **後方互換性**: 既存のデータ構造を維持

## 注意事項

1. **既存データ**: 過去の検索履歴には`title_en`が含まれていない
2. **テキスト検索**: 日本語のまま表示される
3. **データサイズ**: JSONフィールドのサイズが若干増加
4. **パフォーマンス**: 追加のデータベースクエリが発生

## 今後の改善案

1. **既存データの移行**: 過去の検索履歴に`title_en`を追加
2. **テキスト検索の対応**: 翻訳APIの導入検討
3. **キャッシュ機能**: 英語表示データのキャッシュ
4. **統計分析**: 言語別の検索傾向分析
