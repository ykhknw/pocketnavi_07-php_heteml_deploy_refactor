<?php
/**
 * index_production.phpの最終デバッグスクリプト
 * 何も表示されない問題を解決
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 index_production.php最終デバッグ</h1>";

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
    echo "<p style='color: red;'>❌ 基本設定: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    $securityConfig = $productionConfig->getSecurityConfig();
    echo "<p style='color: green;'>✅ 設定取得: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 本番環境設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 3. セキュリティ設定の適用
echo "<h2>📋 セキュリティ設定の適用</h2>";

try {
    if ($securityConfig['security_headers']) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        echo "<p style='color: green;'>✅ セキュリティヘッダー: 設定</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ セキュリティ設定: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    echo "<p style='color: red;'>❌ セッション設定: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    echo "<p style='color: red;'>❌ データベース設定: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    echo "<p style='color: red;'>❌ アプリケーション設定: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 7. データベース接続
echo "<h2>📋 データベース接続</h2>";

try {
    require_once __DIR__ . '/config/database_unified.php';
    echo "<p style='color: green;'>✅ データベース接続: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ データベース接続: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 8. セキュリティシステム
echo "<h2>📋 セキュリティシステム</h2>";

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
    echo "<p style='color: red;'>❌ セキュリティシステム: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 9. キャッシュシステム
echo "<h2>📋 キャッシュシステム</h2>";

try {
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    echo "<p style='color: green;'>✅ キャッシュシステム: 初期化</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ キャッシュシステム: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 10. パフォーマンス監視
echo "<h2>📋 パフォーマンス監視</h2>";

try {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    echo "<p style='color: green;'>✅ パフォーマンス監視: 開始</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ パフォーマンス監視: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 11. ルーティングシステム
echo "<h2>📋 ルーティングシステム</h2>";

try {
    require_once __DIR__ . '/src/Core/Router.php';
    echo "<p style='color: green;'>✅ Router: 正常</p>";
    
    require_once __DIR__ . '/routes/web_minimal.php';
    echo "<p style='color: green;'>✅ web_minimal.php: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティングシステム: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 12. ルーティング処理
echo "<h2>📋 ルーティング処理</h2>";

try {
    echo "<p>ルーティング処理を開始...</p>";
    
    // 出力をキャプチャ
    ob_start();
    $result = Router::dispatch();
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✅ ルーティング処理: 完了</p>";
    echo "<p>結果: " . ($result ? 'Success' : 'Failed') . "</p>";
    echo "<p>出力サイズ: " . strlen($output) . " バイト</p>";
    
    if (strlen($output) > 0) {
        echo "<p>出力内容:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ 出力が空です</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティング処理: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    echo "<p style='color: red;'>❌ パフォーマンス監視: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>🎯 最終デバッグ完了</h2>";
echo "<p><a href='index_production.php'>← index_production.phpにアクセス</a></p>";
echo "<p><a href='simple_index.php'>← simple_index.phpにアクセス</a></p>";
?>
