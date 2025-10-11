# HETEML本番環境移行手順書

## 📋 移行手順

### Step 1: ファイルのアップロード
以下のファイル・ディレクトリをHETEMLサーバーの`public_html`ディレクトリにアップロード：

```
✅ アップロードが必要なファイル・ディレクトリ:
├── index.php                    # メインエントリーポイント
├── index_production.php         # 本番環境用エントリーポイント
├── router.php                   # ルーティング設定
├── about.php                    # アプリケーション固有ファイル
├── contact.php                  # アプリケーション固有ファイル
├── sitemap.php                  # サイトマップ生成
├── generate-sitemap.php         # サイトマップ生成スクリプト
├── robots.txt                   # 検索エンジン設定
├── sitemap.xml                  # サイトマップファイル
├── config/                      # 設定ディレクトリ
├── src/                         # ソースコード
├── assets/                      # 静的ファイル（CSS、JS、画像）
├── pictures/                    # 建築物写真
├── screen_shots_3_webp/         # WebP画像
├── api/                         # API
├── admin/                       # 管理画面
└── routes/                      # ルート設定
```

### Step 2: 本番環境設定の適用

#### 2.1: エントリーポイントの設定
```bash
# HETEMLサーバー上で実行
cd public_html
mv index_production.php index.php
```

#### 2.2: 環境設定ファイルの配置
```bash
# HETEMLサーバー上で実行
cp config/env.heteml .env
```

### Step 3: データベース設定の確認

#### 3.1: HETEMLのデータベース情報を確認
- データベース名
- ユーザー名
- パスワード
- ホスト名

#### 3.2: .envファイルの更新
```bash
# .envファイルを編集
nano .env
```

以下の値をHETEMLの実際の値に更新：
```env
DB_HOST=localhost
DB_NAME=your_heteml_database_name
DB_USERNAME=your_heteml_db_user
DB_PASSWORD=your_heteml_db_password
APP_URL=https://your-domain.heteml.jp
```

### Step 4: ディレクトリ権限の設定
```bash
# ログディレクトリの作成と権限設定
mkdir -p logs
chmod 755 logs
chmod 666 logs/*.log 2>/dev/null || true

# キャッシュディレクトリの作成と権限設定
mkdir -p cache
chmod 755 cache
chmod 666 cache/* 2>/dev/null || true
```

### Step 5: 動作確認

#### 5.1: 基本的な動作確認
- トップページの表示
- 検索機能の動作
- 建築物詳細ページの表示
- 管理画面のアクセス

#### 5.2: エラーログの確認
```bash
# エラーログの確認
tail -f logs/production_errors.log
tail -f logs/security.log
```

## 🔧 設定ファイルの詳細

### .envファイルの配置場所
```
public_html/
├── .env                        # ← ここに配置（ルートディレクトリ）
├── index.php
└── config/
    ├── env.heteml             # アップロード用（削除可能）
    └── ...
```

### 環境変数の読み込み順序
1. `public_html/.env` (最優先)
2. `public_html/config/.env`
3. その他のパス

## ⚠️ 重要な注意事項

### セキュリティ
- `.env`ファイルの権限を適切に設定（644推奨）
- `APP_KEY`は絶対に他人に知られないようにする
- 本番環境では`APP_DEBUG=false`に設定

### データベース
- 本番環境のデータベース情報を正確に設定
- データベースのバックアップを取ってから移行

### ファイル権限
- ログ・キャッシュディレクトリは書き込み可能にする
- 設定ファイルは読み取り専用にする

## 🚨 トラブルシューティング

### よくある問題
1. **データベース接続エラー**: `.env`のデータベース設定を確認
2. **権限エラー**: ログ・キャッシュディレクトリの権限を確認
3. **設定読み込みエラー**: `.env`ファイルの配置場所を確認

### ログの確認方法
```bash
# アプリケーションログ
tail -f logs/production_errors.log

# セキュリティログ
tail -f logs/security.log

# PHPエラーログ
tail -f /var/log/php_errors.log
```

## 📞 サポート

移行で問題が発生した場合：
1. エラーログを確認
2. 設定ファイルの内容を確認
3. ファイル権限を確認
4. 必要に応じてサポートに連絡

---

**移行完了後の確認事項:**
- [ ] トップページが正常に表示される
- [ ] 検索機能が動作する
- [ ] 建築物詳細ページが表示される
- [ ] 管理画面にアクセスできる
- [ ] エラーログにエラーがない
- [ ] セキュリティログが正常に記録される
