# PocketNavi 本番環境移行ガイド

## 📋 概要

このドキュメントは、PocketNaviアプリケーションを本番環境に移行するための詳細な手順を説明します。

## 🎯 移行前の準備

### 1. システム要件の確認

#### サーバー要件
- **PHP**: 8.0以上
- **MySQL**: 5.7以上 または MariaDB 10.3以上
- **Webサーバー**: Apache 2.4以上 または Nginx 1.18以上
- **メモリ**: 最低512MB、推奨1GB以上
- **ディスク容量**: 最低1GB、推奨5GB以上

#### PHP拡張機能
```bash
# 必須拡張機能
php-mysql
php-pdo
php-json
php-mbstring
php-xml
php-curl
php-gd (画像処理用)
php-zip
php-openssl
```

### 2. データベースの準備

#### データベースの作成
```sql
CREATE DATABASE _shinkenchiku_02 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### ユーザーの作成と権限設定
```sql
CREATE USER 'pocketnavi_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON _shinkenchiku_02.* TO 'pocketnavi_user'@'localhost';
FLUSH PRIVILEGES;
```

#### テーブルのインポート
```bash
# 既存のデータベースからテーブルをエクスポート
mysqldump -u root -p _shinkenchiku_02 > pocketnavi_backup.sql

# 本番環境にインポート
mysql -u pocketnavi_user -p _shinkenchiku_02 < pocketnavi_backup.sql
```

## 🚀 本番環境への移行手順

### 1. ファイルのアップロード

#### 必要なファイルのコピー
```bash
# アプリケーションファイル
cp -r pocketnavi_07-php_heteml_deploy_refactor/* /var/www/html/pocketnavi/

# 権限の設定
chown -R www-data:www-data /var/www/html/pocketnavi/
chmod -R 755 /var/www/html/pocketnavi/
chmod -R 777 /var/www/html/pocketnavi/logs/
chmod -R 777 /var/www/html/pocketnavi/cache/
```

### 2. 設定ファイルの調整

#### 本番環境設定ファイルの作成
```bash
# 本番環境設定ファイルをコピー
cp config/env.production .env

# 設定値の調整
nano .env
```

#### 重要な設定項目
```env
# データベース設定
DB_HOST=localhost
DB_NAME=_shinkenchiku_02
DB_USERNAME=pocketnavi_user
DB_PASSWORD=strong_password_here

# セキュリティ設定
APP_KEY=your-unique-secret-key-here
SESSION_LIFETIME=7200

# 本番環境設定
APP_ENV=production
APP_DEBUG=false
```

### 3. Webサーバーの設定

#### Apache設定例
```apache
<VirtualHost *:80>
    ServerName pocketnavi.example.com
    DocumentRoot /var/www/html/pocketnavi
    
    # セキュリティヘッダー
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    # リライトルール
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index_production.php [QSA,L]
    
    # ログ設定
    ErrorLog ${APACHE_LOG_DIR}/pocketnavi_error.log
    CustomLog ${APACHE_LOG_DIR}/pocketnavi_access.log combined
</VirtualHost>
```

#### Nginx設定例
```nginx
server {
    listen 80;
    server_name pocketnavi.example.com;
    root /var/www/html/pocketnavi;
    index index_production.php;
    
    # セキュリティヘッダー
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    
    # PHP処理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index_production.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # リライトルール
    location / {
        try_files $uri $uri/ /index_production.php?$query_string;
    }
}
```

### 4. SSL証明書の設定

#### Let's Encryptを使用したSSL設定
```bash
# Certbotのインストール
sudo apt install certbot python3-certbot-apache

# SSL証明書の取得
sudo certbot --apache -d pocketnavi.example.com

# 自動更新の設定
sudo crontab -e
# 以下の行を追加
0 12 * * * /usr/bin/certbot renew --quiet
```

## 🔧 本番環境での設定

### 1. パフォーマンス最適化

#### PHP設定の調整
```ini
; php.ini
memory_limit = 512M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
```

#### データベース最適化
```sql
-- インデックスの最適化
ANALYZE TABLE buildings_table_3;
ANALYZE TABLE individual_architects_3;
ANALYZE TABLE architect_compositions_2;
ANALYZE TABLE building_architects;

-- クエリキャッシュの有効化
SET GLOBAL query_cache_size = 268435456;
SET GLOBAL query_cache_type = ON;
```

### 2. セキュリティ設定

#### ファイル権限の設定
```bash
# 設定ファイルの保護
chmod 600 .env
chmod 600 config/env.production

# ログディレクトリの保護
chmod 755 logs/
chmod 644 logs/*.log

# キャッシュディレクトリの保護
chmod 755 cache/
chmod 644 cache/.htaccess
```

#### ファイアウォール設定
```bash
# UFWの設定
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 3. 監視とログ

#### ログローテーションの設定
```bash
# logrotateの設定
sudo nano /etc/logrotate.d/pocketnavi
```

```conf
/var/www/html/pocketnavi/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        /bin/kill -USR1 `cat /var/run/php/php8.2-fpm.pid 2> /dev/null` 2> /dev/null || true
    endscript
}
```

#### システム監視の設定
```bash
# システムリソース監視
sudo apt install htop iotop nethogs

# ログ監視
sudo apt install logwatch
```

## 🧪 移行後のテスト

### 1. 機能テスト

#### 基本機能の確認
```bash
# 本番環境テストの実行
php test_production_ready.php
```

#### 手動テスト項目
- [ ] トップページの表示
- [ ] 検索機能の動作
- [ ] 建築物詳細ページの表示
- [ ] 建築家ページの表示
- [ ] 管理画面のアクセス
- [ ] ログイン機能
- [ ] エラーページの表示

### 2. パフォーマンステスト

#### レスポンス時間の測定
```bash
# Apache Benchを使用した負荷テスト
ab -n 1000 -c 10 http://pocketnavi.example.com/

# 詳細なパフォーマンステスト
php test_performance_detailed.php
```

### 3. セキュリティテスト

#### セキュリティ機能の確認
```bash
# セキュリティテストの実行
php test_security_fixed.php
```

#### 手動セキュリティテスト
- [ ] SQLインジェクション対策
- [ ] XSS対策
- [ ] CSRF保護
- [ ] セキュリティヘッダー
- [ ] セッション管理

## 📊 本番環境での運用

### 1. 定期メンテナンス

#### データベースメンテナンス
```sql
-- 週次メンテナンス
OPTIMIZE TABLE buildings_table_3;
OPTIMIZE TABLE individual_architects_3;
OPTIMIZE TABLE architect_compositions_2;
OPTIMIZE TABLE building_architects;

-- 月次メンテナンス
ANALYZE TABLE buildings_table_3;
ANALYZE TABLE individual_architects_3;
ANALYZE TABLE architect_compositions_2;
ANALYZE TABLE building_architects;
```

#### キャッシュクリア
```bash
# キャッシュのクリア
rm -rf cache/*
php -r "require_once 'src/Cache/CacheManager.php'; CacheManager::getInstance()->clear();"
```

### 2. バックアップ

#### データベースバックアップ
```bash
#!/bin/bash
# backup_database.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u pocketnavi_user -p _shinkenchiku_02 > backup_${DATE}.sql
gzip backup_${DATE}.sql
```

#### ファイルバックアップ
```bash
#!/bin/bash
# backup_files.sh
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf pocketnavi_backup_${DATE}.tar.gz /var/www/html/pocketnavi/
```

### 3. 監視とアラート

#### システム監視
```bash
# ディスク使用量の監視
df -h

# メモリ使用量の監視
free -h

# CPU使用率の監視
top

# ログファイルの監視
tail -f logs/production_errors.log
```

## 🚨 トラブルシューティング

### 1. よくある問題

#### データベース接続エラー
```bash
# 接続テスト
mysql -u pocketnavi_user -p -h localhost _shinkenchiku_02

# エラーログの確認
tail -f /var/log/mysql/error.log
```

#### パフォーマンス問題
```bash
# スロークエリログの確認
tail -f /var/log/mysql/slow.log

# プロセス監視
ps aux | grep php
```

#### セキュリティ問題
```bash
# セキュリティログの確認
tail -f logs/security.log

# アクセスログの確認
tail -f /var/log/apache2/access.log
```

### 2. 緊急時の対応

#### アプリケーションの停止
```bash
# Webサーバーの停止
sudo systemctl stop apache2
# または
sudo systemctl stop nginx
```

#### 緊急復旧
```bash
# バックアップからの復旧
mysql -u pocketnavi_user -p _shinkenchiku_02 < backup_YYYYMMDD_HHMMSS.sql
```

## 📞 サポート

### 連絡先
- **技術サポート**: support@pocketnavi.com
- **緊急連絡**: emergency@pocketnavi.com

### ドキュメント
- **API仕様書**: `/docs/API_SPECIFICATION.md`
- **運用マニュアル**: `/docs/OPERATION_MANUAL.md`
- **トラブルシューティング**: `/docs/TROUBLESHOOTING.md`

---

**注意**: 本番環境への移行は慎重に行い、必ずバックアップを取ってから実行してください。

