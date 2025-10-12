<?php
/**
 * index_production.phpのエラートレーススクリプト
 * システムエラーの詳細な原因を特定
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 index_production.phpエラートレース</h1>";

// エラーハンドラーを設定
set_error_handler(function($severity, $message, $file, $line) {
    echo "<p style='color: red;'>❌ PHP Error: " . htmlspecialchars($message) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($file) . "</p>";
    echo "<p>行: " . $line . "</p>";
    return true;
});

set_exception_handler(function($exception) {
    echo "<p style='color: red;'>❌ Exception: " . htmlspecialchars($exception->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($exception->getFile()) . "</p>";
    echo "<p>行: " . $exception->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
});

// 1. 段階的なテスト
echo "<h2>📋 段階的なテスト</h2>";

// Step 1: 基本的な設定
echo "<h3>Step 1: 基本的な設定</h3>";
try {
    require_once __DIR__ . '/src/Utils/ProductionConfig.php';
    $productionConfig = ProductionConfig::getInstance();
    echo "<p style='color: green;'>✅ ProductionConfig: 正常</p>";
    
    require_once __DIR__ . '/src/Utils/ProductionErrorHandler.php';
    $errorHandler = ProductionErrorHandler::getInstance();
    echo "<p style='color: green;'>✅ ProductionErrorHandler: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 基本設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 2: 本番環境設定
echo "<h3>Step 2: 本番環境設定</h3>";
try {
    if ($productionConfig->isProduction()) {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 1);
    }
    
    $perfConfig = $productionConfig->getPerformanceConfig();
    $securityConfig = $productionConfig->getSecurityConfig();
    echo "<p style='color: green;'>✅ 本番環境設定: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 本番環境設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 3: セキュリティ設定
echo "<h3>Step 3: セキュリティ設定</h3>";
try {
    if ($securityConfig['security_headers']) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    echo "<p style='color: green;'>✅ セキュリティ設定: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セキュリティ設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 4: セッション設定
echo "<h3>Step 4: セッション設定</h3>";
try {
    if ($securityConfig['session_lifetime']) {
        ini_set('session.gc_maxlifetime', $securityConfig['session_lifetime']);
        ini_set('session.cookie_lifetime', $securityConfig['session_lifetime']);
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p style='color: green;'>✅ セッション設定: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セッション設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 5: データベース設定
echo "<h3>Step 5: データベース設定</h3>";
try {
    $dbConfig = $productionConfig->getDatabaseConfig();
    define('DB_HOST', $dbConfig['host']);
    define('DB_NAME', $dbConfig['dbname']);
    define('DB_USERNAME', $dbConfig['username']);
    define('DB_PASS', $dbConfig['password']);
    echo "<p style='color: green;'>✅ データベース設定: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ データベース設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 6: アプリケーション設定
echo "<h3>Step 6: アプリケーション設定</h3>";
try {
    define('APP_NAME', $productionConfig->get('APP_NAME', 'PocketNavi'));
    define('APP_ENV', $productionConfig->get('APP_ENV', 'production'));
    define('APP_DEBUG', $productionConfig->isDebug());
    echo "<p style='color: green;'>✅ アプリケーション設定: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ アプリケーション設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 7: データベース接続
echo "<h3>Step 7: データベース接続</h3>";
try {
    require_once __DIR__ . '/config/database_unified.php';
    echo "<p style='color: green;'>✅ データベース接続: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ データベース接続: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 8: セキュリティシステム
echo "<h3>Step 8: セキュリティシステム</h3>";
try {
    if ($securityConfig['csrf_protection'] || $securityConfig['rate_limiting']) {
        require_once __DIR__ . '/src/Security/SecurityManager.php';
        $securityManager = SecurityManager::getInstance();
        $securityManager->initialize();
    }
    echo "<p style='color: green;'>✅ セキュリティシステム: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セキュリティシステム: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 9: キャッシュシステム
echo "<h3>Step 9: キャッシュシステム</h3>";
try {
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    echo "<p style='color: green;'>✅ キャッシュシステム: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ キャッシュシステム: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 10: ルーティングシステム
echo "<h3>Step 10: ルーティングシステム</h3>";
try {
    require_once __DIR__ . '/src/Core/Router.php';
    echo "<p style='color: green;'>✅ Router: 正常</p>";
    
    require_once __DIR__ . '/routes/web_safe.php';
    echo "<p style='color: green;'>✅ web_safe.php: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティングシステム: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// Step 11: ルーティング処理
echo "<h3>Step 11: ルーティング処理</h3>";
try {
    $router = new Router();
    echo "<p style='color: green;'>✅ Routerインスタンス: 正常</p>";
    
    // 出力をキャプチャ
    ob_start();
    $router->dispatch();
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✅ ルーティング処理: 正常</p>";
    echo "<p>出力サイズ: " . strlen($output) . " バイト</p>";
    
    if (strlen($output) > 0) {
        echo "<p>出力内容:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティング処理: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 2. エラーログの確認
echo "<h2>📋 エラーログの確認</h2>";

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

echo "<h2>🎯 エラートレース完了</h2>";
echo "<p><a href='index_production.php'>← index_production.phpにアクセス</a></p>";
echo "<p><a href='simple_index.php'>← simple_index.phpにアクセス</a></p>";
?>
