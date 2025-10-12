<?php
/**
 * 本番環境エラーデバッグスクリプト
 * index_production.phpのエラーを詳細に確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 本番環境エラーデバッグ</h1>";

// 1. index_production.phpの段階的テスト
echo "<h2>📋 index_production.phpの段階的テスト</h2>";

// Step 1: 基本的な設定の読み込み
echo "<h3>Step 1: 基本的な設定の読み込み</h3>";
try {
    require_once __DIR__ . '/src/Utils/ProductionConfig.php';
    echo "<p style='color: green;'>✅ ProductionConfig: 正常に読み込み</p>";
    
    $productionConfig = ProductionConfig::getInstance();
    echo "<p style='color: green;'>✅ ProductionConfig: インスタンス作成成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ProductionConfig: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 2: ProductionErrorHandlerの読み込み
echo "<h3>Step 2: ProductionErrorHandlerの読み込み</h3>";
try {
    require_once __DIR__ . '/src/Utils/ProductionErrorHandler.php';
    echo "<p style='color: green;'>✅ ProductionErrorHandler: 正常に読み込み</p>";
    
    $errorHandler = ProductionErrorHandler::getInstance();
    echo "<p style='color: green;'>✅ ProductionErrorHandler: インスタンス作成成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ProductionErrorHandler: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 3: 本番環境設定の適用
echo "<h3>Step 3: 本番環境設定の適用</h3>";
try {
    if ($productionConfig->isProduction()) {
        echo "<p style='color: green;'>✅ 本番環境: 検出</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ 本番環境: 未検出（ローカル環境）</p>";
    }
    
    $perfConfig = $productionConfig->getPerformanceConfig();
    echo "<p style='color: green;'>✅ パフォーマンス設定: 取得成功</p>";
    echo "<pre>" . print_r($perfConfig, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 本番環境設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 4: セキュリティ設定の適用
echo "<h3>Step 4: セキュリティ設定の適用</h3>";
try {
    $securityConfig = $productionConfig->getSecurityConfig();
    echo "<p style='color: green;'>✅ セキュリティ設定: 取得成功</p>";
    echo "<pre>" . print_r($securityConfig, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セキュリティ設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 5: セッション設定の適用
echo "<h3>Step 5: セッション設定の適用</h3>";
try {
    if ($securityConfig['session_lifetime']) {
        ini_set('session.gc_maxlifetime', $securityConfig['session_lifetime']);
        ini_set('session.cookie_lifetime', $securityConfig['session_lifetime']);
        echo "<p style='color: green;'>✅ セッション設定: 適用成功</p>";
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "<p style='color: green;'>✅ セッション: 開始成功</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ セッション: 既に開始済み</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セッション設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 6: データベース設定の適用
echo "<h3>Step 6: データベース設定の適用</h3>";
try {
    $dbConfig = $productionConfig->getDatabaseConfig();
    define('DB_HOST', $dbConfig['host']);
    define('DB_NAME', $dbConfig['dbname']);
    define('DB_USERNAME', $dbConfig['username']);
    define('DB_PASS', $dbConfig['password']);
    echo "<p style='color: green;'>✅ データベース定数: 定義成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ データベース設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 7: アプリケーション設定の適用
echo "<h3>Step 7: アプリケーション設定の適用</h3>";
try {
    define('APP_NAME', $productionConfig->get('APP_NAME', 'PocketNavi'));
    define('APP_ENV', $productionConfig->get('APP_ENV', 'production'));
    define('APP_DEBUG', $productionConfig->isDebug());
    echo "<p style='color: green;'>✅ アプリケーション定数: 定義成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ アプリケーション設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 8: database_unified.phpの読み込み
echo "<h3>Step 8: database_unified.phpの読み込み</h3>";
try {
    require_once __DIR__ . '/config/database_unified.php';
    echo "<p style='color: green;'>✅ database_unified.php: 正常に読み込み</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ database_unified.php: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 9: セキュリティシステムの初期化
echo "<h3>Step 9: セキュリティシステムの初期化</h3>";
try {
    if ($securityConfig['csrf_protection'] || $securityConfig['rate_limiting']) {
        require_once __DIR__ . '/src/Security/SecurityManager.php';
        $securityManager = SecurityManager::getInstance();
        $securityManager->initialize();
        echo "<p style='color: green;'>✅ セキュリティシステム: 初期化成功</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ セキュリティシステム: スキップ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セキュリティシステム: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 10: キャッシュシステムの初期化
echo "<h3>Step 10: キャッシュシステムの初期化</h3>";
try {
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    echo "<p style='color: green;'>✅ キャッシュシステム: 初期化成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ キャッシュシステム: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// Step 11: ルーティングシステムの読み込み
echo "<h3>Step 11: ルーティングシステムの読み込み</h3>";
try {
    require_once __DIR__ . '/src/Core/Router.php';
    require_once __DIR__ . '/routes/web.php';
    echo "<p style='color: green;'>✅ ルーティングシステム: 正常に読み込み</p>";
    
    $router = new Router();
    echo "<p style='color: green;'>✅ Router: インスタンス作成成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティングシステム: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// 2. エラーログの最新確認
echo "<h2>📋 エラーログの最新確認</h2>";

$logPath = __DIR__ . '/logs';
if (is_dir($logPath)) {
    $logFiles = glob($logPath . '/*.log');
    if (!empty($logFiles)) {
        // 最新のログファイルを確認
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

echo "<h2>🎯 本番環境エラーデバッグ完了</h2>";
echo "<p><a href='index_production.php'>← 本番環境用ページにアクセス</a></p>";
echo "<p><a href='index_local.php'>← ローカル環境用ページにアクセス</a></p>";
?>
