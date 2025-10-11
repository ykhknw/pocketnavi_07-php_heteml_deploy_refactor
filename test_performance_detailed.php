<?php
/**
 * Phase 5.2: 詳細パフォーマンステスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Phase 5.2: 詳細パフォーマンステスト ===\n\n";

$performanceResults = [];

// 1. システム初期化時間テスト
echo "1. システム初期化時間テスト\n";
$startTime = microtime(true);

try {
    require_once 'config/database_unified.php';
    require_once 'config/app_unified.php';
    require_once 'src/Cache/CacheManager.php';
    require_once 'src/Database/QueryOptimizer.php';
    require_once 'src/Utils/ImageOptimizer.php';
    require_once 'src/Security/SecurityManager.php';
    
    $pdo = getDB();
    $cacheManager = CacheManager::getInstance();
    $queryOptimizer = QueryOptimizer::getInstance();
    $imageOptimizer = ImageOptimizer::getInstance();
    $securityManager = SecurityManager::getInstance();
    
    $endTime = microtime(true);
    $initTime = ($endTime - $startTime) * 1000; // ミリ秒
    
    echo "   ✅ システム初期化時間: " . round($initTime, 2) . "ms\n";
    $performanceResults['init_time'] = $initTime;
    
    if ($initTime < 100) {
        echo "   🏆 優秀（100ms未満）\n";
    } elseif ($initTime < 500) {
        echo "   ✅ 良好（500ms未満）\n";
    } else {
        echo "   ⚠️ 改善の余地あり（500ms以上）\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ システム初期化エラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. データベースクエリパフォーマンステスト
echo "2. データベースクエリパフォーマンステスト\n";
try {
    // 単純なSELECTクエリ
    $startTime = microtime(true);
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM buildings_table_3');
    $result = $stmt->fetch();
    $endTime = microtime(true);
    $queryTime = ($endTime - $startTime) * 1000;
    
    echo "   ✅ 単純SELECTクエリ: " . round($queryTime, 2) . "ms\n";
    echo "   📋 建築物総数: " . $result['count'] . "件\n";
    $performanceResults['simple_query'] = $queryTime;
    
    // 複雑なJOINクエリ
    $startTime = microtime(true);
    $stmt = $pdo->query('
        SELECT b.title, b.location, ia.name_ja as architect_name 
        FROM buildings_table_3 b 
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id 
        LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id 
        LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id 
        LIMIT 100
    ');
    $results = $stmt->fetchAll();
    $endTime = microtime(true);
    $joinQueryTime = ($endTime - $startTime) * 1000;
    
    echo "   ✅ 複雑なJOINクエリ: " . round($joinQueryTime, 2) . "ms\n";
    echo "   📋 取得件数: " . count($results) . "件\n";
    $performanceResults['join_query'] = $joinQueryTime;
    
    // インデックス付きクエリ
    $startTime = microtime(true);
    $stmt = $pdo->query('SELECT * FROM buildings_table_3 WHERE has_photo IS NOT NULL AND has_photo != "" LIMIT 10');
    $results = $stmt->fetchAll();
    $endTime = microtime(true);
    $indexedQueryTime = ($endTime - $startTime) * 1000;
    
    echo "   ✅ インデックス付きクエリ: " . round($indexedQueryTime, 2) . "ms\n";
    echo "   📋 取得件数: " . count($results) . "件\n";
    $performanceResults['indexed_query'] = $indexedQueryTime;
    
} catch (Exception $e) {
    echo "   ❌ データベースクエリエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. キャッシュパフォーマンステスト
echo "3. キャッシュパフォーマンステスト\n";
try {
    // キャッシュ書き込みテスト
    $startTime = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $cacheManager->set("test_key_{$i}", "test_value_{$i}", 60);
    }
    $endTime = microtime(true);
    $writeTime = ($endTime - $startTime) * 1000;
    
    echo "   ✅ キャッシュ書き込み（100件）: " . round($writeTime, 2) . "ms\n";
    echo "   📋 1件あたり: " . round($writeTime / 100, 2) . "ms\n";
    $performanceResults['cache_write'] = $writeTime;
    
    // キャッシュ読み込みテスト
    $startTime = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $value = $cacheManager->get("test_key_{$i}");
    }
    $endTime = microtime(true);
    $readTime = ($endTime - $startTime) * 1000;
    
    echo "   ✅ キャッシュ読み込み（100件）: " . round($readTime, 2) . "ms\n";
    echo "   📋 1件あたり: " . round($readTime / 100, 2) . "ms\n";
    $performanceResults['cache_read'] = $readTime;
    
    // キャッシュ統計
    $stats = $cacheManager->getStats();
    echo "   📊 キャッシュ統計:\n";
    echo "      - ヒット数: " . $stats['hits'] . "\n";
    echo "      - ミス数: " . $stats['misses'] . "\n";
    echo "      - ヒット率: " . round($stats['hit_rate'], 1) . "%\n";
    
} catch (Exception $e) {
    echo "   ❌ キャッシュパフォーマンスエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. 画像最適化パフォーマンステスト
echo "4. 画像最適化パフォーマンステスト\n";
try {
    $testImage = 'assets/images/default-building.jpg';
    
    if (file_exists($testImage)) {
        // 画像情報取得テスト
        $startTime = microtime(true);
        $imageInfo = $imageOptimizer->getImageInfo($testImage);
        $endTime = microtime(true);
        $infoTime = ($endTime - $startTime) * 1000;
        
        echo "   ✅ 画像情報取得: " . round($infoTime, 2) . "ms\n";
        if ($imageInfo) {
            echo "   📋 画像サイズ: " . $imageInfo['width'] . "x" . $imageInfo['height'] . "\n";
            echo "   📋 画像形式: " . $imageInfo['type'] . "\n";
        }
        $performanceResults['image_info'] = $infoTime;
        
        // サムネイル生成テスト（GD拡張機能がある場合）
        if ($imageOptimizer->isGdAvailable()) {
            $startTime = microtime(true);
            $thumbnail = $imageOptimizer->generateThumbnail($testImage, 150, 150);
            $endTime = microtime(true);
            $thumbnailTime = ($endTime - $startTime) * 1000;
            
            echo "   ✅ サムネイル生成: " . round($thumbnailTime, 2) . "ms\n";
            $performanceResults['thumbnail_generation'] = $thumbnailTime;
        } else {
            echo "   ⚠️ GD拡張機能なし（サムネイル生成スキップ）\n";
        }
        
    } else {
        echo "   ⚠️ テスト画像が見つかりません\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ 画像最適化パフォーマンスエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. セキュリティシステムパフォーマンステスト
echo "5. セキュリティシステムパフォーマンステスト\n";
try {
    // CSRFトークン生成テスト
    $startTime = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $token = $securityManager->generateCsrfToken();
    }
    $endTime = microtime(true);
    $csrfTime = ($endTime - $startTime) * 1000;
    
    echo "   ✅ CSRFトークン生成（100回）: " . round($csrfTime, 2) . "ms\n";
    echo "   📋 1回あたり: " . round($csrfTime / 100, 2) . "ms\n";
    $performanceResults['csrf_generation'] = $csrfTime;
    
    // セキュリティログ記録テスト
    $startTime = microtime(true);
    for ($i = 0; $i < 50; $i++) {
        $securityManager->logSecurityEvent('PERFORMANCE_TEST', "テストイベント {$i}");
    }
    $endTime = microtime(true);
    $logTime = ($endTime - $startTime) * 1000;
    
    echo "   ✅ セキュリティログ記録（50回）: " . round($logTime, 2) . "ms\n";
    echo "   📋 1回あたり: " . round($logTime / 50, 2) . "ms\n";
    $performanceResults['security_logging'] = $logTime;
    
} catch (Exception $e) {
    echo "   ❌ セキュリティシステムパフォーマンスエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. メモリ使用量テスト
echo "6. メモリ使用量テスト\n";
$memoryUsage = memory_get_usage(true);
$memoryPeak = memory_get_peak_usage(true);

echo "   📊 現在のメモリ使用量: " . round($memoryUsage / 1024 / 1024, 2) . "MB\n";
echo "   📊 ピークメモリ使用量: " . round($memoryPeak / 1024 / 1024, 2) . "MB\n";
echo "   📊 メモリ制限: " . ini_get('memory_limit') . "\n";

$performanceResults['memory_usage'] = $memoryUsage;
$performanceResults['memory_peak'] = $memoryPeak;

echo "\n";

// 7. パフォーマンスサマリー
echo "7. パフォーマンスサマリー\n";
echo "   📊 システム初期化: " . round($performanceResults['init_time'] ?? 0, 2) . "ms\n";
echo "   📊 単純クエリ: " . round($performanceResults['simple_query'] ?? 0, 2) . "ms\n";
echo "   📊 JOINクエリ: " . round($performanceResults['join_query'] ?? 0, 2) . "ms\n";
echo "   📊 キャッシュ書き込み: " . round($performanceResults['cache_write'] ?? 0, 2) . "ms\n";
echo "   📊 キャッシュ読み込み: " . round($performanceResults['cache_read'] ?? 0, 2) . "ms\n";
echo "   📊 CSRF生成: " . round($performanceResults['csrf_generation'] ?? 0, 2) . "ms\n";

echo "\n";

// 8. パフォーマンス評価
echo "8. パフォーマンス評価\n";
$totalScore = 0;
$maxScore = 0;

// 初期化時間評価
if (isset($performanceResults['init_time'])) {
    $maxScore += 20;
    if ($performanceResults['init_time'] < 100) {
        $totalScore += 20;
        echo "   🏆 初期化時間: 優秀（20/20点）\n";
    } elseif ($performanceResults['init_time'] < 500) {
        $totalScore += 15;
        echo "   ✅ 初期化時間: 良好（15/20点）\n";
    } else {
        $totalScore += 10;
        echo "   ⚠️ 初期化時間: 改善の余地あり（10/20点）\n";
    }
}

// クエリパフォーマンス評価
if (isset($performanceResults['simple_query'])) {
    $maxScore += 20;
    if ($performanceResults['simple_query'] < 10) {
        $totalScore += 20;
        echo "   🏆 クエリパフォーマンス: 優秀（20/20点）\n";
    } elseif ($performanceResults['simple_query'] < 50) {
        $totalScore += 15;
        echo "   ✅ クエリパフォーマンス: 良好（15/20点）\n";
    } else {
        $totalScore += 10;
        echo "   ⚠️ クエリパフォーマンス: 改善の余地あり（10/20点）\n";
    }
}

// キャッシュパフォーマンス評価
if (isset($performanceResults['cache_read'])) {
    $maxScore += 20;
    if ($performanceResults['cache_read'] < 50) {
        $totalScore += 20;
        echo "   🏆 キャッシュパフォーマンス: 優秀（20/20点）\n";
    } elseif ($performanceResults['cache_read'] < 200) {
        $totalScore += 15;
        echo "   ✅ キャッシュパフォーマンス: 良好（15/20点）\n";
    } else {
        $totalScore += 10;
        echo "   ⚠️ キャッシュパフォーマンス: 改善の余地あり（10/20点）\n";
    }
}

// メモリ使用量評価
if (isset($performanceResults['memory_peak'])) {
    $maxScore += 20;
    $memoryMB = $performanceResults['memory_peak'] / 1024 / 1024;
    if ($memoryMB < 50) {
        $totalScore += 20;
        echo "   🏆 メモリ使用量: 優秀（20/20点）\n";
    } elseif ($memoryMB < 100) {
        $totalScore += 15;
        echo "   ✅ メモリ使用量: 良好（15/20点）\n";
    } else {
        $totalScore += 10;
        echo "   ⚠️ メモリ使用量: 改善の余地あり（10/20点）\n";
    }
}

// 総合評価
$maxScore += 20; // 総合評価用
if ($totalScore >= $maxScore * 0.9) {
    $totalScore += 20;
    echo "   🏆 総合評価: 優秀（20/20点）\n";
} elseif ($totalScore >= $maxScore * 0.7) {
    $totalScore += 15;
    echo "   ✅ 総合評価: 良好（15/20点）\n";
} else {
    $totalScore += 10;
    echo "   ⚠️ 総合評価: 改善の余地あり（10/20点）\n";
}

$finalScore = round(($totalScore / ($maxScore + 20)) * 100, 1);
echo "\n   🎯 総合スコア: {$totalScore}/" . ($maxScore + 20) . "点（{$finalScore}%）\n";

if ($finalScore >= 90) {
    echo "   🏆 パフォーマンス評価: 優秀\n";
} elseif ($finalScore >= 70) {
    echo "   ✅ パフォーマンス評価: 良好\n";
} else {
    echo "   ⚠️ パフォーマンス評価: 改善の余地あり\n";
}

echo "\n";

echo "=== Phase 5.2: パフォーマンステスト完了 ===\n";
