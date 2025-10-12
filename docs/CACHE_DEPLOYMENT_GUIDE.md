# PocketNavi キャッシュ機能 デプロイメントガイド

## 📋 **概要**

このガイドでは、PocketNaviのキャッシュ機能を本番環境（HETEML）にデプロイする手順を説明します。

## 🚀 **デプロイメント手順**

### **Step 1: ファイルのアップロード**

以下のファイルを本番環境にアップロードします：

```
src/Cache/SearchResultCache.php          # キャッシュシステム
src/Services/CachedBuildingService.php   # キャッシュ機能付き検索サービス
index_refactored_cache_test.php          # キャッシュテスト版
cache_management.php                     # キャッシュ管理画面
```

### **Step 2: ディレクトリの作成**

本番環境でキャッシュディレクトリを作成：

```bash
mkdir -p cache/search
chmod 755 cache/search
```

### **Step 3: 権限の設定**

キャッシュディレクトリに書き込み権限を設定：

```bash
chmod 755 cache
chmod 755 cache/search
```

### **Step 4: テストの実行**

1. **キャッシュテスト版にアクセス**
   ```
   https://kenchikuka.com/index_refactored_cache_test.php
   ```

2. **キャッシュ有効/無効の切り替えテスト**
   ```
   https://kenchikuka.com/index_refactored_cache_test.php?cache=1  # 有効
   https://kenchikuka.com/index_refactored_cache_test.php?cache=0  # 無効
   ```

3. **パフォーマンス比較**
   - キャッシュ無効で検索実行（時間測定）
   - キャッシュ有効で同じ検索実行（時間測定）
   - 2回目の検索でキャッシュヒットを確認

### **Step 5: キャッシュ管理画面の設定**

1. **管理画面にアクセス**
   ```
   https://kenchikuka.com/cache_management.php
   ```

2. **パスワードの変更**
   `cache_management.php`の`$adminPassword`を強力なパスワードに変更

3. **キャッシュ統計の確認**
   - ファイル数
   - サイズ
   - 期限切れファイル数

## 🔧 **設定項目**

### **キャッシュTTL（Time To Live）**

```php
// デフォルト: 3600秒（1時間）
$cachedService = new CachedBuildingService(true, 3600);

// 設定例
$cachedService = new CachedBuildingService(true, 1800);  // 30分
$cachedService = new CachedBuildingService(true, 7200);  // 2時間
```

### **キャッシュディレクトリ**

```php
// デフォルト: cache/search
$cache = new SearchResultCache('cache/search', 3600, true);

// カスタムディレクトリ
$cache = new SearchResultCache('custom/cache/path', 3600, true);
```

## 📊 **パフォーマンス監視**

### **キャッシュ統計の確認**

```php
$stats = $cachedService->getCacheStats();
echo "ファイル数: " . $stats['totalFiles'];
echo "サイズ: " . round($stats['totalSize'] / 1024, 2) . "KB";
echo "期限切れ: " . $stats['expiredFiles'];
```

### **ログの監視**

```bash
# エラーログの確認
tail -f /path/to/error.log | grep "Cache"

# アクセスログの確認
tail -f /path/to/access.log | grep "cache_test"
```

## 🛡️ **セキュリティ設定**

### **キャッシュ管理画面の保護**

1. **パスワードの強化**
   ```php
   $adminPassword = 'your_strong_password_here';
   ```

2. **IP制限の追加**
   ```php
   $allowedIPs = ['192.168.1.100', '203.0.113.0'];
   if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
       die('Access denied');
   }
   ```

3. **HTTPSの使用**
   - 管理画面は必ずHTTPSでアクセス

### **キャッシュファイルの保護**

```bash
# キャッシュディレクトリに.htaccessを作成
echo "Deny from all" > cache/.htaccess
echo "Allow from 127.0.0.1" >> cache/.htaccess
```

## 🔄 **メンテナンス**

### **定期メンテナンス**

1. **期限切れキャッシュのクリア**
   ```php
   $cachedService->clearCache();
   ```

2. **キャッシュ統計の確認**
   - 週1回の統計確認
   - 異常なサイズ増加の監視

3. **ログローテーション**
   ```bash
   # ログファイルのローテーション
   logrotate /path/to/logrotate.conf
   ```

### **トラブルシューティング**

#### **キャッシュが効かない場合**

1. **権限の確認**
   ```bash
   ls -la cache/search/
   ```

2. **ディスク容量の確認**
   ```bash
   df -h
   ```

3. **PHPエラーログの確認**
   ```bash
   tail -f /path/to/php_error.log
   ```

#### **パフォーマンスが改善しない場合**

1. **キャッシュヒット率の確認**
   - 同じ検索を複数回実行
   - 2回目以降の高速化を確認

2. **TTLの調整**
   - 短いTTL（1800秒）でテスト
   - 長いTTL（7200秒）でテスト

3. **キャッシュサイズの最適化**
   - 不要なパラメータの除外
   - 結果データの最適化

## 📈 **本番適用の判断基準**

### **テスト結果の評価**

1. **パフォーマンス改善**
   - 検索速度が20%以上向上
   - データベース負荷の軽減

2. **機能の安定性**
   - エラーが発生しない
   - 既存機能に影響がない

3. **キャッシュの効果**
   - キャッシュヒット率が50%以上
   - メモリ使用量が適切

### **本番適用手順**

1. **バックアップの作成**
   ```bash
   cp index.php index.php.backup
   cp -r cache cache.backup
   ```

2. **本番版の適用**
   ```bash
   cp index_refactored_cache_test.php index.php
   ```

3. **動作確認**
   - 既存機能の動作確認
   - パフォーマンスの確認
   - エラーログの確認

4. **ロールバック準備**
   ```bash
   # 問題が発生した場合
   cp index.php.backup index.php
   ```

## 🎯 **期待される効果**

### **パフォーマンス向上**

- **検索速度**: 20-50%の高速化
- **データベース負荷**: 30-60%の軽減
- **サーバーリソース**: CPU使用率の削減

### **ユーザー体験の向上**

- **レスポンス時間**: 体感速度の向上
- **安定性**: 高負荷時の安定性向上
- **可用性**: サーバー負荷軽減による可用性向上

## 📞 **サポート**

問題が発生した場合は、以下の情報を収集してください：

1. **エラーログ**
2. **キャッシュ統計**
3. **システムリソース使用状況**
4. **テスト結果**

---

**注意**: 本番環境での適用前に、必ずステージング環境で十分なテストを実施してください。
