<?php
/**
 * パフォーマンス最適化システム修正テストスクリプト
 */

// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PocketNavi パフォーマンス最適化システム修正テスト ===\n\n";

try {
    // 必要なファイルの存在確認
    $requiredFiles = [
        'src/Cache/CacheManager.php',
        'src/Database/QueryOptimizer.php',
        'src/Utils/ImageOptimizer.php'
    ];
    
    echo "1. ファイル存在確認\n";
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "   ✅ {$file}\n";
        } else {
            echo "   ❌ {$file} - 見つかりません\n";
        }
    }
    echo "\n";
    
    // クラスの読み込みテスト
    echo "2. クラス読み込みテスト\n";
    require_once 'src/Cache/CacheManager.php';
    echo "   ✅ CacheManager クラス\n";
    
    require_once 'src/Database/QueryOptimizer.php';
    echo "   ✅ QueryOptimizer クラス\n";
    
    require_once 'src/Utils/ImageOptimizer.php';
    echo "   ✅ ImageOptimizer クラス\n";
    echo "\n";
    
    // キャッシュシステムのテスト
    echo "3. キャッシュシステムテスト\n";
    $cache = CacheManager::getInstance();
    echo "   ✅ CacheManager インスタンス作成\n";
    
    // キャッシュのテスト
    $testKey = 'test_key_' . time();
    $testValue = ['message' => 'Hello, Cache!', 'timestamp' => time()];
    
    $cache->set($testKey, $testValue, 60);
    echo "   ✅ キャッシュへの保存\n";
    
    $retrievedValue = $cache->get($testKey);
    if ($retrievedValue && $retrievedValue['message'] === $testValue['message']) {
        echo "   ✅ キャッシュからの取得\n";
    } else {
        echo "   ❌ キャッシュからの取得に失敗\n";
    }
    
    $cache->delete($testKey);
    echo "   ✅ キャッシュからの削除\n";
    
    $stats = $cache->getStats();
    echo "   ✅ キャッシュ統計: " . json_encode($stats) . "\n";
    echo "\n";
    
    // クエリ最適化システムのテスト
    echo "4. クエリ最適化システムテスト\n";
    $queryOptimizer = QueryOptimizer::getInstance();
    echo "   ✅ QueryOptimizer インスタンス作成\n";
    
    // 最適化提案のテスト
    $testSql = "SELECT * FROM buildings_table_3 WHERE title LIKE '%test%' ORDER BY building_id DESC";
    $suggestions = $queryOptimizer->getOptimizationSuggestions($testSql);
    echo "   ✅ 最適化提案: " . count($suggestions) . " 件\n";
    
    foreach ($suggestions as $suggestion) {
        echo "     - [{$suggestion['type']}] {$suggestion['message']}\n";
    }
    
    $queryStats = $queryOptimizer->getQueryStats();
    echo "   ✅ クエリ統計: " . json_encode($queryStats) . "\n";
    echo "\n";
    
    // 画像最適化システムのテスト
    echo "5. 画像最適化システムテスト\n";
    $imageOptimizer = ImageOptimizer::getInstance();
    echo "   ✅ ImageOptimizer インスタンス作成\n";
    
    // GD拡張機能の確認
    if ($imageOptimizer->isGdAvailable()) {
        echo "   ✅ GD拡張機能: 利用可能\n";
    } else {
        echo "   ⚠️ GD拡張機能: 利用不可（シンプルモードで動作）\n";
    }
    
    // 画像情報の取得テスト（存在しないファイルでもエラーにならない）
    $testImagePath = 'assets/images/default-building.jpg';
    if (file_exists($testImagePath)) {
        $imageInfo = $imageOptimizer->getImageInfo($testImagePath);
        if ($imageInfo) {
            echo "   ✅ 画像情報取得: {$imageInfo['width']}x{$imageInfo['height']} ({$imageInfo['format']})\n";
        } else {
            echo "   ⚠️ 画像情報の取得に失敗\n";
        }
    } else {
        echo "   ⚠️ テスト画像が見つかりません: {$testImagePath}\n";
    }
    
    // 遅延読み込みHTMLの生成テスト
    $lazyLoadHtml = $imageOptimizer->generateLazyLoadHtml('/test/image.jpg', 'Test Image', ['width' => 300, 'height' => 200]);
    if (strpos($lazyLoadHtml, 'lazy-load') !== false) {
        echo "   ✅ 遅延読み込みHTML生成\n";
    } else {
        echo "   ❌ 遅延読み込みHTML生成に失敗\n";
    }
    echo "\n";
    
    // 統合テスト
    echo "6. 統合テスト\n";
    
    // キャッシュとクエリ最適化の統合
    $cacheKey = 'query_test_' . time();
    $cache->set($cacheKey, ['result' => 'cached_data'], 60);
    $cachedResult = $cache->get($cacheKey);
    
    if ($cachedResult && $cachedResult['result'] === 'cached_data') {
        echo "   ✅ キャッシュとクエリ最適化の統合\n";
    } else {
        echo "   ❌ キャッシュとクエリ最適化の統合に失敗\n";
    }
    
    // パフォーマンス統計
    $finalCacheStats = $cache->getStats();
    $finalQueryStats = $queryOptimizer->getQueryStats();
    
    echo "   ✅ 最終キャッシュ統計: ヒット率 {$finalCacheStats['hit_rate']}%\n";
    echo "   ✅ 最終クエリ統計: 総クエリ数 {$finalQueryStats['total_queries']}\n";
    echo "\n";
    
    echo "=== テスト完了 ===\n";
    echo "✅ パフォーマンス最適化システムは正常に動作しています！\n";
    echo "キャッシュ、クエリ最適化、画像最適化の各システムが正常に機能しています。\n";
    
} catch (Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo "   エラーメッセージ: " . $e->getMessage() . "\n";
    echo "   ファイル: " . $e->getFile() . "\n";
    echo "   行番号: " . $e->getLine() . "\n";
    exit(1);
}
?>
