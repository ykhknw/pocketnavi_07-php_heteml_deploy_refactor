## アプリケーション仕様書（PocketNavi PHP）

- **アプリ名**: PocketNavi
- **目的**: 建築物・建築家情報の検索/閲覧、人気検索の可視化、管理運用
- **対象**: 一般ユーザー（検索/閲覧）、管理者（運用/分析）
- **環境**: PHP 8系想定、MySQL、ファイルキャッシュ、(任意) Redis、Heteml 共有ホスティング対応

## システム構成（概要）

- **アーキテクチャ**: シンプルMVC＋ルータ、自前キャッシュ＆レート制限、API/管理画面併設
- **エントリポイント**: `index.php`（バリアント: `index_production.php`, `index_new.php` ほか）
- **ルーティング**: `src/Core/Router.php` と `routes/*.php` で定義
- **DBアクセス**: `config/database_*.php` と `src/Utils/*Database*` 経由でPDO接続
- **キャッシュ**: `src/Cache/CacheManager.php`（メモリ/ファイル）
- **レート制限**: `src/Security/RateLimiter.php` + `config/rate_limit_config.php`
- **セキュリティ**: CSRF/セッション強化/簡易ログイン/管理者判定
- **デプロイ**: Heteml向けスクリプト/ドキュメント

## ユースケース/主要機能

- **ユーザー**
  - 建築物・建築家ページの閲覧
  - スラッグによる詳細ページ遷移
  - 人気検索一覧の参照（タブ/検索/ページネーション）
- **管理者**
  - ログイン/ログアウト
  - キャッシュ管理、検索キャッシュ管理
  - レート制限設定/確認
  - 検索履歴/利用傾向の簡易分析
  - ユーザー/セッション分析（機能ページあり、拡張余地）

## ルーティング

- 定義場所: `routes/web.php`（他に `routes/web_safe.php`, `routes/web_minimal.php` など）
- 例: `/`, `/buildings/{slug}`, `/architects/{slug}`
- パラメータ `{slug}` を正規表現へ変換してマッチング
- 404時はルータ内のNotFound処理へフォールバック

## API 仕様（/api）

- 共通: JSONレスポンス、CORS許可（GET/POST/OPTIONS）、入力バリデーション
- `popular-searches.php`
  - 入力: `page`(int,1..), `limit`(int,<=100), `q`(検索文字列), `search_type`(text/architect/prefecture/building), `lang`(ja/en)
  - 出力: `{ success, data: { searches[], pagination{}, html }, lang }`
  - ふるまい: キャッシュファイル（`cache/popular_searches.php`）の統合/フィルタ。欠如時はフォールバック生成
- `search-count.php`: 検索件数等の集計返却（実装準拠）
- `get-photos.php`: 建築物の写真一覧（`building_id` 等）
- `debug-search-types.php`: 人気検索タイプのデバッグ

注: 外部公開時は追加の認証/細粒度レート制御を推奨。

## 人気の検索機能（サイドバー表示）

### 表示方法

- **配置場所**: サイドバー（`src/Views/includes/sidebar.php`）
- **タイトル**: "人気の検索"（多言語対応: 日本語/英語）
- **初期表示**: ページ読み込み時に `loadSidebarPopularSearches()` 関数（`assets/js/popular-searches.js`）が自動実行
- **タブ機能**: 以下の5つのタブで分類表示
  - すべて（all）
  - 建築家（architect）
  - 建築物（building）
  - 都道府県（prefecture）
  - テキスト（text）

### フロントエンド実装

- **JavaScript**: `assets/js/popular-searches.js`
  - `loadSidebarPopularSearches()`: サイドバー用データの取得と表示
  - `generateSidebarHTML()`: APIから取得したHTMLをサイドバー用に変換
  - `filterSidebarSearches(searchType)`: タブ切り替え時のフィルタリング
  - `removePaginationFromHtml()`: ページネーション要素の除去（サイドバーでは不要）

- **API呼び出し**: `/api/popular-searches.php`
  - パラメータ: `page=1`, `limit={deviceLimit}`, `lang={currentLang}`
  - `deviceLimit`: デバイス別の表示件数制限（モバイル/デスクトップで自動調整）

- **表示形式**:
  - Bootstrapのリストグループ形式（`list-group-item`）
  - 各項目に検索回数（バッジ表示）、ユーザー数（「すべて」タブのみ）
  - クリックで該当検索結果ページへ遷移

### キャッシュ仕様

#### キャッシュファイル構造

- **保存先**: `cache/popular_searches.php`
- **フォーマット**: PHP配列として保存（`var_export`形式）
- **構造**:
  ```php
  [
    'timestamp' => 更新時刻（UNIXタイムスタンプ）,
    'data' => [
      '{cacheKey}' => [
        'searches' => [...],
        'total' => 件数,
        'page' => ページ番号,
        'limit' => 表示件数,
        'totalPages' => 総ページ数
      ]
    ]
  ]
  ```

#### キャッシュ管理クラス

- **実装**: `src/Services/PopularSearchCache.php`
- **キャッシュ有効期限**: 30分（`CACHE_DURATION = 1800秒`）
- **キャッシュキー生成**: `md5(page_limit_searchQuery_searchType)` 形式
- **自動更新**: 無効化（`AUTO_UPDATE_ENABLED = false`）
  - 理由: CRONジョブによる定期更新を前提とするため
  - アクセス時の自動更新は行わない

#### キャッシュ更新プロセス

1. **ロック機構**: `cache/popular_searches.php.lock` を使用した排他制御
   - 非ブロッキングロック（`LOCK_EX | LOCK_NB`）
   - ロック取得失敗時は既存キャッシュを返すか、フォールバックデータを使用

2. **更新方法**:
   - **定期更新**: CRONジョブ（`scripts/update_popular_searches.php`）で実行
   - **手動更新**: 管理画面のキャッシュ管理機能から実行可能
   - **更新スクリプト**: `scripts/update_popular_searches.php`
     - 全検索タイプ（`''`, `'architect'`, `'building'`, `'prefecture'`, `'text'`）のキャッシュを生成
     - 各タイプごとに最大50件のデータを取得してキャッシュ化

3. **バックアップ**: 更新前に `cache/popular_searches_backup.php` に自動バックアップ

4. **データ取得元**: 
   - データベース: `global_search_history` テーブル
   - サービス: `src/Services/SearchLogService::getPopularSearchesForModal()`

#### フォールバック動作

- キャッシュが存在しない、または有効期限切れの場合:
  - **自動更新が無効**: フォールバックデータを返す
  - **フォールバックデータ**: `PopularSearchCache::getFallbackData()` で定義されたサンプルデータ（安藤忠雄、隈研吾、東京、大阪などの固定データ）
- API側でも同様のフォールバック処理を実装（`api/popular-searches.php`）

#### キャッシュ状態確認

- `PopularSearchCache::getCacheStatus()` メソッドで以下の情報を取得可能:
  - `status`: `valid`（有効）/ `expired`（期限切れ）/ `not_exists`（不存在）
  - `age`: キャッシュの経過時間（秒）
  - `max_age`: 最大有効期限（1800秒）
  - `data_count`: キャッシュ内のデータキー数
  - `last_update`: 最終更新日時

### モーダル表示機能

- **トリガー**: サイドバーの「もっと見る」ボタン、または外部リンクアイコン
- **モーダル**: `#popularSearchesModal`（Bootstrapモーダル）
- **機能**: サイドバーと同様のタブ機能、ページネーション対応、検索フィルタリング
- **初期化**: `loadPopularSearchesModal()` 関数でモーダル表示時にデータを読み込み

### 運用上の注意

- CRONジョブの設定が必要（`admin/cron_setup_guide.md` を参照）
- キャッシュファイルの書き込み権限確保（`cache/` ディレクトリ）
- ロックファイルの自動削除機構がないため、長時間ロックが残る場合は手動削除が必要
- キャッシュが古い場合は管理画面から手動クリア可能

## データモデル（主なテーブル）

- `buildings_table_3`: 建築物（座標/種別/画像/Youtube/likes、インデックス最適化）
- `individual_architects_3`: 個別建築家（日本語/英語名、`slug` ユニーク）
- `architect_compositions_2`: 建築家構成（個別建築家との関連）
- `building_architects`: 建築物-建築家のN:N中間
- `photos`: 写真（URL/サムネ/likes）
- `users`: 将来拡張用ユーザー（メール/名前）
- `global_search_history`: 検索履歴（人気検索用、`filters` JSON）
- 認証関連（自動作成され得る）: `login_attempts`, `user_sessions`

## データアクセス/接続

- 設定読込: `config/database.php__`（`.env`ローダ経由）
- 接続: PDO（例: `getDatabaseConnection()`、`DatabaseConnection::getInstance()`）
- 文字コード: `utf8mb4`、エラーモード例外、エミュレートOFF

## キャッシュ仕様

- 実装: `src/Cache/CacheManager.php`
  - 二層キャッシュ: メモリ（プロセス内）＋ファイル（`cache/`）
  - 既定TTL: メモリ1時間、ファイル2時間
  - キー正規化、最古エビクション、ハッシュ分散ディレクトリ
- 業務キャッシュ例: `src/Services/PopularSearchCache.php`, `src/Cache/SearchResultCache.php`, `src/Services/CachedBuildingService.php`

## レート制限

- 実装: `src/Security/RateLimiter.php`
  - Redis対応（任意）。未接続時はプロセスメモリにフォールバック
  - 種別例: `api.search`, `api.general`, `api.admin`, `api.search_count`
  - 設定: `config/rate_limit_config.php`（分/バースト/ブロック/環境別）
- 追加の簡易制限: `src/Security/SecurityManager.php` がセッションベース429を提供

## 認証/セキュリティ

- セッション強化: HTTPOnly/SameSite/再生成、UA+IP検証、タイムアウト
- CSRF: トークン生成/検証機構あり（`SecurityManager` 等）
- 管理ログイン: `admin/login.php` は開発用の簡易固定認証（本番は強化必須）
- 役割: `users.role`（admin/user）想定、`AuthenticationManager::isAdmin()` API

## 環境/設定

- `.env` 互換: `src/Utils/EnvironmentLoader.php` 経由
- 主な設定: `config/app_unified.php`, `config/security_config.php`, `config/rate_limit_config.php`, `config/cache.php`
- 本番エントリ: `index_production.php`（セキュリティ/キャッシュ初期化、エラー処理、最小ルート）

## ログ/監視

- PHPエラーログ使用（`error_log` 出力先の運用設定を推奨）
- パフォーマンス測定: `index_production.php` が時間/メモリ計測
- APIは一部デバッグログ（件数/分布）出力

## デプロイ（Heteml）

- ドキュメント: `HETEML_CORRECT_DEPLOYMENT.md`, `HETEML_DEPLOYMENT_STEPS.md`
- スクリプト: `deploy_to_heteml.php`, `admin/heteml_cleanup.php`
- 注意点
  - PHPバージョン固定
  - 書き込み権限（`cache/`）付与
  - `.htaccess` とフロントコントローラ配置
  - 環境変数/DB設定の適用

## 既知の制約/運用上の注意

- 管理ログインは本番用強化（DBユーザー管理・ハッシュ・MFA等）必須
- Redis未導入時は制限の持続性なし（プロセス間共有されない）
- 人気検索はキャッシュ前提。生成ジョブ/更新ロックの運用が重要
- データ増大時はインデックス最適化とページング調整が必要

## 代表的ファイル

- ルーティング: `src/Core/Router.php`, `routes/web.php`, `routes/web_safe.php`
- エントリ: `index.php`, `index_production.php`, `index_new.php`
- API: `api/popular-searches.php`, `api/search-count.php`, `api/get-photos.php`
- キャッシュ: `src/Cache/CacheManager.php`
- レート制限: `src/Security/RateLimiter.php`, `config/rate_limit_config.php`
- セキュリティ: `src/Security/SecurityManager.php`, `src/Security/AuthenticationManager.php`, `src/Security/SecureAuthManager.php`
- DB: `database/schema.sql`, `database/popular_searches_schema.sql`, `config/database.php__`
- 管理: `admin/*`


