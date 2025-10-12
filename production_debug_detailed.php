<?php
/**
 * index_production.phpの詳細デバッグスクリプト
 * システムエラーの詳細を確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 index_production.php詳細デバッグ</h1>";

// 1. 基本的な設定の読み込み
echo "<h2>📋 基本的な設定の読み込み</h2>";

try {
    require_once __DIR__ . '/src/Utils/ProductionConfig.php';
    $productionConfig = ProductionConfig::getInstance();
    echo "<p style='color: green;'>✅ ProductionConfig: 正常</p>";
    
    require_once __DIR__ . '/src/Utils/ProductionErrorHandler.php';
    $errorHandler = ProductionErrorHandler::getInstance();
    echo "<p style='color: green;'>✅ ProductionErrorHandler: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 基本設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    exit;
}

// 2. 本番環境設定の適用
echo "<h2>📋 本番環境設定の適用</h2>";

try {
    if ($productionConfig->isProduction()) {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 1);
        echo "<p style='color: green;'>✅ 本番環境設定: 適用</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ ローカル環境: デバッグモード</p>";
    }
    
    $perfConfig = $productionConfig->getPerformanceConfig();
    echo "<p style='color: green;'>✅ パフォーマンス設定: 適用</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 本番環境設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 3. セキュリティ設定の適用
echo "<h2>📋 セキュリティ設定の適用</h2>";

try {
    $securityConfig = $productionConfig->getSecurityConfig();
    echo "<p style='color: green;'>✅ セキュリティ設定: 取得</p>";
    
    if ($securityConfig['security_headers']) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        echo "<p style='color: green;'>✅ セキュリティヘッダー: 設定</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セキュリティ設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 4. セッション設定の適用
echo "<h2>📋 セッション設定の適用</h2>";

try {
    if ($securityConfig['session_lifetime']) {
        ini_set('session.gc_maxlifetime', $securityConfig['session_lifetime']);
        ini_set('session.cookie_lifetime', $securityConfig['session_lifetime']);
        echo "<p style='color: green;'>✅ セッション設定: 適用</p>";
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "<p style='color: green;'>✅ セッション: 開始</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ セッション: 既に開始済み</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セッション設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 5. データベース設定の適用
echo "<h2>📋 データベース設定の適用</h2>";

try {
    $dbConfig = $productionConfig->getDatabaseConfig();
    define('DB_HOST', $dbConfig['host']);
    define('DB_NAME', $dbConfig['dbname']);
    define('DB_USERNAME', $dbConfig['username']);
    define('DB_PASS', $dbConfig['password']);
    echo "<p style='color: green;'>✅ データベース定数: 定義</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ データベース設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 6. アプリケーション設定の適用
echo "<h2>📋 アプリケーション設定の適用</h2>";

try {
    define('APP_NAME', $productionConfig->get('APP_NAME', 'PocketNavi'));
    define('APP_ENV', $productionConfig->get('APP_ENV', 'production'));
    define('APP_DEBUG', $productionConfig->isDebug());
    echo "<p style='color: green;'>✅ アプリケーション定数: 定義</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ アプリケーション設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 7. 統一されたデータベース接続の読み込み
echo "<h2>📋 統一されたデータベース接続の読み込み</h2>";

try {
    require_once __DIR__ . '/config/database_unified.php';
    echo "<p style='color: green;'>✅ database_unified.php: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ database_unified.php: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    exit;
}

// 8. セキュリティシステムの初期化
echo "<h2>📋 セキュリティシステムの初期化</h2>";

try {
    if ($securityConfig['csrf_protection'] || $securityConfig['rate_limiting']) {
        require_once __DIR__ . '/src/Security/SecurityManager.php';
        $securityManager = SecurityManager::getInstance();
        $securityManager->initialize();
        echo "<p style='color: green;'>✅ セキュリティシステム: 初期化</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ セキュリティシステム: スキップ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セキュリティシステム: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    exit;
}

// 9. キャッシュシステムの初期化
echo "<h2>📋 キャッシュシステムの初期化</h2>";

try {
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    echo "<p style='color: green;'>✅ キャッシュシステム: 初期化</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ キャッシュシステム: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    exit;
}

// 10. パフォーマンス監視の開始
echo "<h2>📋 パフォーマンス監視の開始</h2>";

try {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    echo "<p style='color: green;'>✅ パフォーマンス監視: 開始</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ パフォーマンス監視: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 11. ルーティングシステムの読み込み
echo "<h2>📋 ルーティングシステムの読み込み</h2>";

try {
    require_once __DIR__ . '/src/Core/Router.php';
    require_once __DIR__ . '/routes/web.php';
    echo "<p style='color: green;'>✅ ルーティングシステム: 読み込み</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティングシステム: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    exit;
}

// 12. リクエストの処理
echo "<h2>📋 リクエストの処理</h2>";

try {
    $router = new Router();
    echo "<p style='color: green;'>✅ Router: インスタンス作成</p>";
    
    // 実際のルーティング処理をテスト
    echo "<p>ルーティング処理を開始...</p>";
    
    // 出力をキャプチャ
    ob_start();
    $router->dispatch();
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✅ ルーティング処理: 完了</p>";
    echo "<p>出力サイズ: " . strlen($output) . " バイト</p>";
    
    if (strlen($output) > 0) {
        echo "<p>出力内容:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ リクエスト処理: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 13. パフォーマンス監視の終了
echo "<h2>📋 パフォーマンス監視の終了</h2>";

try {
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);
    
    echo "<p style='color: green;'>✅ パフォーマンス監視: 完了</p>";
    echo "<p>実行時間: {$executionTime}ms</p>";
    echo "<p>メモリ使用量: {$memoryUsed}MB</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ パフォーマンス監視: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 14. エラーログの最新確認
echo "<h2>📋 エラーログの最新確認</h2>";

$logPath = __DIR__ . '/logs';
if (is_dir($logPath)) {
    $logFiles = glob($logPath . '/*.log');
    if (!empty($logFiles)) {
        $latestLog = '';
        $latestTime = 0;
        foreach ($logFiles as $logFile) {
            $time = filemtime($logFile);
            if ($time > $latestTime) {
                $latestTime = $time;
                $latestLog = $logFile;
            }
        }
        
        if ($latestLog) {
            echo "<p>最新のログファイル: " . basename($latestLog) . "</p>";
            $content = file_get_contents($latestLog);
            $lines = explode("\n", $content);
            $recentLines = array_slice($lines, -20); // 最後の20行
            echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>";
            foreach ($recentLines as $line) {
                if (!empty(trim($line))) {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo "</pre>";
        }
    }
}

echo "<h2>🎯 index_production.php詳細デバッグ完了</h2>";
echo "<p><a href='index_production.php'>← index_production.phpにアクセス</a></p>";
echo "<p><a href='simple_index.php'>← simple_index.phpにアクセス</a></p>";
?>
