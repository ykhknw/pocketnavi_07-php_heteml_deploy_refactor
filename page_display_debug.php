<?php
/**
 * ページ表示エラーデバッグスクリプト
 * 実際のページ表示でのエラーを確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 ページ表示エラーデバッグ</h1>";

// 1. 基本的な設定の読み込み
echo "<h2>📋 基本的な設定の読み込み</h2>";

try {
    require_once __DIR__ . '/src/Utils/ProductionConfig.php';
    $productionConfig = ProductionConfig::getInstance();
    
    require_once __DIR__ . '/src/Utils/ProductionErrorHandler.php';
    $errorHandler = ProductionErrorHandler::getInstance();
    
    $securityConfig = $productionConfig->getSecurityConfig();
    if ($securityConfig['csrf_protection'] || $securityConfig['rate_limiting']) {
        require_once __DIR__ . '/src/Security/SecurityManager.php';
        $securityManager = SecurityManager::getInstance();
        $securityManager->initialize();
    }
    
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    
    echo "<p style='color: green;'>✅ 基本設定: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 基本設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 2. データベース接続のテスト
echo "<h2>📋 データベース接続のテスト</h2>";

try {
    require_once __DIR__ . '/config/database_unified.php';
    $pdo = getDatabaseConnection();
    echo "<p style='color: green;'>✅ データベース接続: 成功</p>";
    
    // 簡単なクエリのテスト
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM buildings_table_3");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>建物データ数: " . number_format($result['count']) . "件</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ データベース接続: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 3. 翻訳関数のテスト
echo "<h2>📋 翻訳関数のテスト</h2>";

try {
    require_once __DIR__ . '/src/Utils/Translation.php';
    $jaResult = t('search', 'ja');
    echo "<p style='color: green;'>✅ 翻訳関数: 成功 ('search' = '$jaResult')</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 翻訳関数: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 4. HomeControllerの直接テスト
echo "<h2>📋 HomeControllerの直接テスト</h2>";

try {
    require_once __DIR__ . '/src/Controllers/HomeController.php';
    $homeController = new HomeController();
    echo "<p style='color: green;'>✅ HomeController: インスタンス作成成功</p>";
    
    // 簡単な検索のテスト
    $_GET['q'] = '';
    $_GET['prefectures'] = '';
    $_GET['completionYears'] = '';
    $_GET['buildingTypes'] = '';
    $_GET['lang'] = 'ja';
    
    echo "<p>HomeController::index()の実行を開始...</p>";
    
    // 出力をキャプチャ
    ob_start();
    $homeController->index();
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✅ HomeController::index(): 実行成功</p>";
    echo "<p>出力サイズ: " . strlen($output) . " バイト</p>";
    
    // 出力の最初の部分を表示
    $lines = explode("\n", $output);
    echo "<p>出力の最初の10行:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 200px; overflow-y: scroll;'>";
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ HomeController: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 5. ルーティングシステムのテスト
echo "<h2>📋 ルーティングシステムのテスト</h2>";

try {
    require_once __DIR__ . '/src/Core/Router.php';
    require_once __DIR__ . '/routes/web.php';
    
    $router = new Router();
    echo "<p style='color: green;'>✅ ルーティングシステム: 正常</p>";
    
    // ルートの一覧を表示
    $routes = Router::getRoutes();
    echo "<p>登録されたルート数: " . count($routes) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティングシステム: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    exit;
}

// 6. 実際のindex_production.phpの実行テスト
echo "<h2>📋 実際のindex_production.phpの実行テスト</h2>";

try {
    echo "<p>index_production.phpの実行を開始...</p>";
    
    // 出力をキャプチャ
    ob_start();
    
    // 実際のindex_production.phpの内容を実行
    $productionConfig = ProductionConfig::getInstance();
    $errorHandler = ProductionErrorHandler::getInstance();
    
    if ($productionConfig->isProduction()) {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 1);
    }
    
    $securityConfig = $productionConfig->getSecurityConfig();
    if ($securityConfig['security_headers']) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    if ($securityConfig['session_lifetime']) {
        ini_set('session.gc_maxlifetime', $securityConfig['session_lifetime']);
        ini_set('session.cookie_lifetime', $securityConfig['session_lifetime']);
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $dbConfig = $productionConfig->getDatabaseConfig();
    define('DB_HOST', $dbConfig['host']);
    define('DB_NAME', $dbConfig['dbname']);
    define('DB_USERNAME', $dbConfig['username']);
    define('DB_PASS', $dbConfig['password']);
    
    define('APP_NAME', $productionConfig->get('APP_NAME', 'PocketNavi'));
    define('APP_ENV', $productionConfig->get('APP_ENV', 'production'));
    define('APP_DEBUG', $productionConfig->isDebug());
    
    require_once __DIR__ . '/config/database_unified.php';
    
    if ($securityConfig['csrf_protection'] || $securityConfig['rate_limiting']) {
        require_once __DIR__ . '/src/Security/SecurityManager.php';
        $securityManager = SecurityManager::getInstance();
        $securityManager->initialize();
    }
    
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    require_once __DIR__ . '/src/Core/Router.php';
    require_once __DIR__ . '/routes/web.php';
    
    $router = new Router();
    $router->dispatch();
    
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✅ index_production.php: 実行成功</p>";
    echo "<p>出力サイズ: " . strlen($output) . " バイト</p>";
    
    // 出力の内容を表示
    if (strlen($output) > 0) {
        echo "<p>出力内容:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: scroll;'>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ 出力が空です</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ index_production.php: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// 7. エラーログの最新確認
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
            $recentLines = array_slice($lines, -30); // 最後の30行
            echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: scroll;'>";
            foreach ($recentLines as $line) {
                if (!empty(trim($line))) {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo "</pre>";
        }
    }
}

echo "<h2>🎯 ページ表示エラーデバッグ完了</h2>";
echo "<p><a href='index_production.php'>← 本番環境用ページにアクセス</a></p>";
echo "<p><a href='index_local.php'>← ローカル環境用ページにアクセス</a></p>";
?>
