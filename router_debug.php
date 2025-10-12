<?php
/**
 * ルーティングシステム詳細デバッグスクリプト
 * Step 11の詳細なエラーを確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 ルーティングシステム詳細デバッグ</h1>";

// 1. 基本的な設定の読み込み（Step 1-10を再現）
echo "<h2>📋 基本的な設定の読み込み</h2>";

try {
    require_once __DIR__ . '/src/Utils/ProductionConfig.php';
    $productionConfig = ProductionConfig::getInstance();
    echo "<p style='color: green;'>✅ ProductionConfig: 正常</p>";
    
    require_once __DIR__ . '/src/Utils/ProductionErrorHandler.php';
    $errorHandler = ProductionErrorHandler::getInstance();
    echo "<p style='color: green;'>✅ ProductionErrorHandler: 正常</p>";
    
    $securityConfig = $productionConfig->getSecurityConfig();
    if ($securityConfig['csrf_protection'] || $securityConfig['rate_limiting']) {
        require_once __DIR__ . '/src/Security/SecurityManager.php';
        $securityManager = SecurityManager::getInstance();
        $securityManager->initialize();
        echo "<p style='color: green;'>✅ SecurityManager: 正常</p>";
    }
    
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    echo "<p style='color: green;'>✅ CacheManager: 正常</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 基本設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    exit;
}

// 2. Router.phpの詳細テスト
echo "<h2>📋 Router.phpの詳細テスト</h2>";

try {
    echo "<p>Router.phpの読み込みを開始...</p>";
    require_once __DIR__ . '/src/Core/Router.php';
    echo "<p style='color: green;'>✅ Router.php: 正常に読み込み</p>";
    
    echo "<p>Routerクラスの存在確認...</p>";
    if (class_exists('Router')) {
        echo "<p style='color: green;'>✅ Routerクラス: 存在</p>";
    } else {
        echo "<p style='color: red;'>❌ Routerクラス: 存在しない</p>";
        exit;
    }
    
    echo "<p>Routerインスタンスの作成...</p>";
    $router = new Router();
    echo "<p style='color: green;'>✅ Routerインスタンス: 作成成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Router.php: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 3. web.phpの詳細テスト
echo "<h2>📋 web.phpの詳細テスト</h2>";

try {
    echo "<p>web.phpの読み込みを開始...</p>";
    
    // HomeControllerの存在確認
    echo "<p>HomeControllerの存在確認...</p>";
    if (file_exists(__DIR__ . '/src/Controllers/HomeController.php')) {
        echo "<p style='color: green;'>✅ HomeController.php: 存在</p>";
    } else {
        echo "<p style='color: red;'>❌ HomeController.php: 存在しない</p>";
        exit;
    }
    
    echo "<p>HomeControllerの読み込み...</p>";
    require_once __DIR__ . '/src/Controllers/HomeController.php';
    echo "<p style='color: green;'>✅ HomeController: 正常に読み込み</p>";
    
    echo "<p>HomeControllerクラスの存在確認...</p>";
    if (class_exists('HomeController')) {
        echo "<p style='color: green;'>✅ HomeControllerクラス: 存在</p>";
    } else {
        echo "<p style='color: red;'>❌ HomeControllerクラス: 存在しない</p>";
        exit;
    }
    
    echo "<p>web.phpの読み込み...</p>";
    require_once __DIR__ . '/routes/web.php';
    echo "<p style='color: green;'>✅ web.php: 正常に読み込み</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ web.php: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 4. ルーティングのテスト
echo "<h2>📋 ルーティングのテスト</h2>";

try {
    echo "<p>ルーティングの実行...</p>";
    
    // 簡単なルートのテスト
    $testRoute = Router::get('/test', function() {
        return json_encode(['message' => 'Test route works!']);
    });
    echo "<p style='color: green;'>✅ テストルート: 登録成功</p>";
    
    // ルーティングの実行
    $router = new Router();
    echo "<p style='color: green;'>✅ ルーティング: 実行成功</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティング: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 5. 実際のindex_production.phpのテスト
echo "<h2>📋 実際のindex_production.phpのテスト</h2>";

try {
    echo "<p>index_production.phpの実行を開始...</p>";
    
    // 実際のindex_production.phpの内容を段階的に実行
    ob_start();
    
    // 本番環境設定の適用
    if ($productionConfig->isProduction()) {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 1);
    }
    
    // セキュリティ設定の適用
    if ($securityConfig['security_headers']) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    // セッション設定の適用
    if ($securityConfig['session_lifetime']) {
        ini_set('session.gc_maxlifetime', $securityConfig['session_lifetime']);
        ini_set('session.cookie_lifetime', $securityConfig['session_lifetime']);
    }
    
    // セッションの開始
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // データベース設定の適用
    $dbConfig = $productionConfig->getDatabaseConfig();
    define('DB_HOST', $dbConfig['host']);
    define('DB_NAME', $dbConfig['dbname']);
    define('DB_USERNAME', $dbConfig['username']);
    define('DB_PASS', $dbConfig['password']);
    
    // アプリケーション設定の適用
    define('APP_NAME', $productionConfig->get('APP_NAME', 'PocketNavi'));
    define('APP_ENV', $productionConfig->get('APP_ENV', 'production'));
    define('APP_DEBUG', $productionConfig->isDebug());
    
    // 統一されたデータベース接続の読み込み
    require_once __DIR__ . '/config/database_unified.php';
    
    // セキュリティシステムの初期化
    if ($securityConfig['csrf_protection'] || $securityConfig['rate_limiting']) {
        require_once __DIR__ . '/src/Security/SecurityManager.php';
        $securityManager = SecurityManager::getInstance();
        $securityManager->initialize();
    }
    
    // キャッシュシステムの初期化
    require_once __DIR__ . '/src/Cache/CacheManager.php';
    $cacheManager = CacheManager::getInstance();
    
    // パフォーマンス監視の開始
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    // ルーティングシステムの読み込み
    require_once __DIR__ . '/src/Core/Router.php';
    require_once __DIR__ . '/routes/web.php';
    
    // リクエストの処理
    $router = new Router();
    $router->dispatch();
    
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✅ index_production.php: 実行成功</p>";
    echo "<p>出力内容:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ index_production.php: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>🎯 ルーティングシステム詳細デバッグ完了</h2>";
echo "<p><a href='index_production.php'>← 本番環境用ページにアクセス</a></p>";
echo "<p><a href='index_local.php'>← ローカル環境用ページにアクセス</a></p>";
?>
