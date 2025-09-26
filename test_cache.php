<?php
/**
 * キャッシュ機能のテストスクリプト
 */

require_once 'config/database.php';
require_once 'src/Services/PopularSearchCache.php';

echo "<h1>人気検索キャッシュテスト</h1>\n";

try {
    $cacheService = new PopularSearchCache();
    
    echo "<h2>1. キャッシュ状態確認</h2>\n";
    $status = $cacheService->getCacheStatus();
    echo "<pre>" . print_r($status, true) . "</pre>\n";
    
    echo "<h2>2. データ取得テスト</h2>\n";
    
    // 全検索タイプのテスト
    $searchTypes = ['', 'architect', 'building', 'prefecture', 'text'];
    
    foreach ($searchTypes as $searchType) {
        echo "<h3>検索タイプ: " . ($searchType ?: 'all') . "</h3>\n";
        
        $startTime = microtime(true);
        $result = $cacheService->getPopularSearches(1, 10, '', $searchType);
        $endTime = microtime(true);
        
        echo "<p>実行時間: " . round(($endTime - $startTime) * 1000, 2) . " ms</p>\n";
        echo "<p>データ数: " . count($result['searches']) . "</p>\n";
        
        if (count($result['searches']) > 0) {
            echo "<ul>\n";
            foreach (array_slice($result['searches'], 0, 3) as $search) {
                echo "<li>" . htmlspecialchars($search['query']) . " (" . $search['search_type'] . ") - " . $search['total_searches'] . "回</li>\n";
            }
            echo "</ul>\n";
        }
    }
    
    echo "<h2>3. キャッシュファイル確認</h2>\n";
    $cacheFile = 'cache/popular_searches.php';
    if (file_exists($cacheFile)) {
        echo "<p>キャッシュファイルサイズ: " . filesize($cacheFile) . " bytes</p>\n";
        echo "<p>最終更新: " . date('Y-m-d H:i:s', filemtime($cacheFile)) . "</p>\n";
    } else {
        echo "<p>キャッシュファイルが存在しません</p>\n";
    }
    
    echo "<h2>4. パフォーマンステスト</h2>\n";
    $iterations = 10;
    $totalTime = 0;
    
    for ($i = 0; $i < $iterations; $i++) {
        $startTime = microtime(true);
        $cacheService->getPopularSearches(1, 10, '', '');
        $endTime = microtime(true);
        $totalTime += ($endTime - $startTime);
    }
    
    $averageTime = $totalTime / $iterations;
    echo "<p>平均実行時間（{$iterations}回）: " . round($averageTime * 1000, 2) . " ms</p>\n";
    
    echo "<h2>テスト完了</h2>\n";
    echo "<p><a href='admin/cache_management.php'>キャッシュ管理画面</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
