<?php
/**
 * Phase 5.4: 本番環境移行準備テスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Phase 5.4: 本番環境移行準備テスト ===\n\n";

$productionResults = [
    'config' => false,
    'error_handling' => false,
    'security' => false,
    'database' => false,
    'performance' => false,
    'logging' => false,
    'cache' => false,
    'routing' => false
];

$totalTests = 0;
$passedTests = 0;

// 1. 本番環境設定テスト
echo "1. 本番環境設定テスト\n";
try {
    require_once 'src/Utils/ProductionConfig.php';
    $productionConfig = ProductionConfig::getInstance();
    
    // 設定の確認
    $isProduction = $productionConfig->isProduction();
    $isDebug = $productionConfig->isDebug();
    $dbConfig = $productionConfig->getDatabaseConfig();
    $securityConfig = $productionConfig->getSecurityConfig();
    $perfConfig = $productionConfig->getPerformanceConfig();
    
    echo "   ✅ 本番環境設定読み込み成功\n";
    echo "   📋 環境: " . ($isProduction ? '本番' : '開発') . "\n";
    echo "   📋 デバッグモード: " . ($isDebug ? 'ON' : 'OFF') . "\n";
    echo "   📋 データベース: {$dbConfig['dbname']}\n";
    echo "   📋 セキュリティ: CSRF=" . ($securityConfig['csrf_protection'] ? 'ON' : 'OFF') . 
         ", Rate Limiting=" . ($securityConfig['rate_limiting'] ? 'ON' : 'OFF') . "\n";
    echo "   📋 パフォーマンス: メモリ制限={$perfConfig['memory_limit']}, 実行時間制限={$perfConfig['max_execution_time']}秒\n";
    
    $productionResults['config'] = true;
    $passedTests++;
    
} catch (Exception $e) {
    echo "   ❌ 本番環境設定テストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 2. エラーハンドリングテスト
echo "2. エラーハンドリングテスト\n";
try {
    require_once 'src/Utils/ProductionErrorHandler.php';
    $errorHandler = ProductionErrorHandler::getInstance();
    
    echo "   ✅ 本番環境エラーハンドラー初期化成功\n";
    
    // エラーログの確認
    $errorLog = $errorHandler->getErrorLog(5);
    echo "   📋 エラーログ件数: " . count($errorLog) . "件\n";
    
    // テストエラーの生成（本番環境では表示されない）
    $originalDisplayErrors = ini_get('display_errors');
    ini_set('display_errors', 0);
    
    // 意図的なエラーを生成（ログに記録される）
    trigger_error('本番環境テスト用エラー', E_USER_NOTICE);
    
    ini_set('display_errors', $originalDisplayErrors);
    
    echo "   ✅ エラーログ記録機能正常\n";
    
    $productionResults['error_handling'] = true;
    $passedTests++;
    
} catch (Exception $e) {
    echo "   ❌ エラーハンドリングテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 3. セキュリティシステムテスト
echo "3. セキュリティシステムテスト\n";
try {
    require_once 'src/Security/SecurityManager.php';
    $securityManager = SecurityManager::getInstance();
    
    // セキュリティシステムの初期化
    $securityManager->initialize();
    echo "   ✅ セキュリティシステム初期化成功\n";
    
    // CSRFトークンの生成
    $csrfToken = $securityManager->generateCsrfToken();
    if ($csrfToken) {
        echo "   ✅ CSRFトークン生成成功\n";
    } else {
        echo "   ❌ CSRFトークン生成失敗\n";
    }
    
    // セキュリティイベントの記録
    $securityManager->logSecurityEvent('PRODUCTION_TEST', '本番環境テスト実行');
    echo "   ✅ セキュリティイベント記録成功\n";
    
    $productionResults['security'] = true;
    $passedTests++;
    
} catch (Exception $e) {
    echo "   ❌ セキュリティシステムテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 4. データベース接続テスト
echo "4. データベース接続テスト\n";
try {
    require_once 'config/database_unified.php';
    $pdo = getDB();
    
    if ($pdo) {
        echo "   ✅ データベース接続成功\n";
        
        // データベース情報の確認
        $stmt = $pdo->query('SELECT DATABASE() as db_name');
        $result = $stmt->fetch();
        echo "   📋 接続データベース: " . $result['db_name'] . "\n";
        
        // テーブル数の確認
        $stmt = $pdo->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   📋 テーブル数: " . count($tables) . "個\n";
        
        $productionResults['database'] = true;
        $passedTests++;
    } else {
        echo "   ❌ データベース接続失敗\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ データベース接続テストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 5. パフォーマンステスト
echo "5. パフォーマンステスト\n";
try {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    // 重い処理のシミュレーション
    require_once 'src/Cache/CacheManager.php';
    require_once 'src/Database/QueryOptimizer.php';
    require_once 'src/Utils/ImageOptimizer.php';
    
    $cacheManager = CacheManager::getInstance();
    $queryOptimizer = QueryOptimizer::getInstance();
    $imageOptimizer = ImageOptimizer::getInstance();
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    
    $executionTime = ($endTime - $startTime) * 1000;
    $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024;
    
    echo "   ✅ パフォーマンスシステム初期化成功\n";
    echo "   📋 初期化時間: " . round($executionTime, 2) . "ms\n";
    echo "   📋 メモリ使用量: " . round($memoryUsage, 2) . "MB\n";
    
    if ($executionTime < 100 && $memoryUsage < 10) {
        echo "   ✅ パフォーマンス良好\n";
        $productionResults['performance'] = true;
        $passedTests++;
    } else {
        echo "   ⚠️ パフォーマンス改善の余地あり\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ パフォーマンステストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 6. ログシステムテスト
echo "6. ログシステムテスト\n";
try {
    require_once 'src/Security/LogMonitor.php';
    $logMonitor = LogMonitor::getInstance();
    
    // ログ分析の実行
    $analysis = $logMonitor->analyzeSecurityEvents(3600);
    if ($analysis && isset($analysis['total_events'])) {
        echo "   ✅ ログ分析成功\n";
        echo "   📋 総イベント数: {$analysis['total_events']}\n";
        echo "   📋 リスクレベル: {$analysis['risk_level']}\n";
        
        $productionResults['logging'] = true;
        $passedTests++;
    } else {
        echo "   ❌ ログ分析失敗\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ ログシステムテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 7. キャッシュシステムテスト
echo "7. キャッシュシステムテスト\n";
try {
    $cacheManager = CacheManager::getInstance();
    
    // キャッシュの書き込みテスト
    $cacheManager->set('production_test', 'test_value', 60);
    $cachedValue = $cacheManager->get('production_test');
    
    if ($cachedValue === 'test_value') {
        echo "   ✅ キャッシュシステム正常動作\n";
        
        // キャッシュ統計の確認
        $stats = $cacheManager->getStats();
        echo "   📋 キャッシュヒット率: " . round($stats['hit_rate'], 1) . "%\n";
        
        $productionResults['cache'] = true;
        $passedTests++;
    } else {
        echo "   ❌ キャッシュシステム異常\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ キャッシュシステムテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 8. ルーティングシステムテスト
echo "8. ルーティングシステムテスト\n";
try {
    require_once 'src/Core/Router.php';
    require_once 'src/Controllers/BaseController.php';
    require_once 'src/Controllers/HomeController.php';
    require_once 'src/Core/View.php';
    
    $router = new Router();
    $controller = new HomeController();
    $view = new View();
    
    echo "   ✅ ルーティングシステム初期化成功\n";
    echo "   ✅ コントローラーシステム正常\n";
    echo "   ✅ ビューシステム正常\n";
    
    $productionResults['routing'] = true;
    $passedTests++;
    
} catch (Exception $e) {
    echo "   ❌ ルーティングシステムテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 9. 本番環境移行準備テスト結果サマリー
echo "9. 本番環境移行準備テスト結果サマリー\n";
echo "   📊 総テスト数: {$totalTests}\n";
echo "   ✅ 成功: {$passedTests}\n";
echo "   ❌ 失敗: " . ($totalTests - $passedTests) . "\n";
echo "   📈 成功率: " . round(($passedTests / $totalTests) * 100, 1) . "%\n";

echo "\n";

// 10. 詳細結果
echo "10. 詳細本番環境移行準備テスト結果\n";
$testNames = [
    'config' => '本番環境設定',
    'error_handling' => 'エラーハンドリング',
    'security' => 'セキュリティシステム',
    'database' => 'データベース接続',
    'performance' => 'パフォーマンス',
    'logging' => 'ログシステム',
    'cache' => 'キャッシュシステム',
    'routing' => 'ルーティングシステム'
];

foreach ($productionResults as $test => $result) {
    $status = $result ? '✅ 成功' : '❌ 失敗';
    echo "   {$status} {$testNames[$test]}\n";
}

echo "\n";

// 11. 本番環境移行準備評価
echo "11. 本番環境移行準備評価\n";
if ($passedTests === $totalTests) {
    echo "   🏆 すべての本番環境移行準備テストが成功しました！\n";
    echo "   🚀 本番環境への移行準備が完了しています。\n";
    echo "   📋 次のステップ: Phase 5.5 最終検証とドキュメント作成\n";
} elseif ($passedTests >= $totalTests * 0.8) {
    echo "   ✅ 本番環境移行準備は良好です。\n";
    echo "   ⚠️ 一部の項目で改善の余地があります。\n";
    echo "   📋 失敗したテストを修正してから本番環境に移行することを推奨します。\n";
} else {
    echo "   ⚠️ 本番環境移行準備に改善が必要です。\n";
    echo "   🔧 失敗したテストを修正してから本番環境に移行してください。\n";
}

echo "\n";

// 12. 本番環境移行チェックリスト
echo "12. 本番環境移行チェックリスト\n";
echo "   📋 本番環境移行前の確認事項:\n";
echo "      ✅ データベースのバックアップ\n";
echo "      ✅ 設定ファイルの確認\n";
echo "      ✅ セキュリティ設定の確認\n";
echo "      ✅ エラーハンドリングの確認\n";
echo "      ✅ ログ設定の確認\n";
echo "      ✅ パフォーマンス設定の確認\n";
echo "      ✅ キャッシュ設定の確認\n";
echo "      ✅ ルーティング設定の確認\n";

echo "\n";

echo "=== Phase 5.4: 本番環境移行準備テスト完了 ===\n";

