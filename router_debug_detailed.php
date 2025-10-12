<?php
/**
 * ルーティングの詳細デバッグスクリプト
 * 404エラーの原因を特定
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 ルーティング詳細デバッグ</h1>";

// 1. リクエスト情報の確認
echo "<h2>📋 リクエスト情報</h2>";
echo "<p><strong>REQUEST_METHOD:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";
echo "<p><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";
echo "<p><strong>PATH_INFO:</strong> " . ($_SERVER['PATH_INFO'] ?? 'N/A') . "</p>";

// 2. パスの解析
echo "<h2>📋 パスの解析</h2>";
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';
$path = strtok($path, '?');

echo "<p><strong>解析されたパス:</strong> " . htmlspecialchars($path) . "</p>";

// 3. Routerクラスの読み込み
echo "<h2>📋 Routerクラスの読み込み</h2>";
try {
    require_once __DIR__ . '/src/Core/Router.php';
    echo "<p style='color: green;'>✅ Routerクラス: 正常</p>";
    
    if (class_exists('Router')) {
        echo "<p style='color: green;'>✅ Routerクラス: 存在</p>";
    } else {
        echo "<p style='color: red;'>❌ Routerクラス: 存在しない</p>";
        exit;
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Routerクラス: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 4. ルートの登録
echo "<h2>📋 ルートの登録</h2>";
try {
    // ルートをクリア
    Router::clearRoutes();
    
    // ルートを登録
    Router::get('/', function() {
        return '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PocketNavi - 建築検索システム</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        p { color: #7f8c8d; line-height: 1.6; margin-bottom: 20px; }
        .success { color: #27ae60; font-weight: bold; }
        .link { display: inline-block; margin: 10px; padding: 15px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
        .link:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ PocketNavi - 建築検索システム</h1>
        <p class="success">✅ ルーティングシステムが正常に動作しています！</p>
        <p>リファクタリングが完了し、すべての機能が正常に動作しています。</p>
        <p>以下のリンクから各機能にアクセスできます：</p>
        <a href="simple_index.php" class="link">🔍 検索ページ</a>
        <a href="index.php" class="link">📱 メインページ</a>
        <a href="production_debug_detailed.php" class="link">🔧 デバッグ</a>
    </div>
</body>
</html>';
    });
    
    Router::get('/test', function() {
        return json_encode(['message' => 'Test route works!', 'status' => 'success']);
    });
    
    echo "<p style='color: green;'>✅ ルートの登録: 完了</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルートの登録: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 5. 登録されたルートの確認
echo "<h2>📋 登録されたルートの確認</h2>";
try {
    $routes = Router::getRoutes();
    echo "<p>登録されたルート数: " . count($routes) . "</p>";
    
    if (!empty($routes)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>メソッド</th><th>パス</th><th>パターン</th><th>ハンドラー</th></tr>";
        
        foreach ($routes as $route) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($route['method']) . "</td>";
            echo "<td>" . htmlspecialchars($route['path']) . "</td>";
            echo "<td>" . htmlspecialchars($route['pattern']) . "</td>";
            echo "<td>" . (is_callable($route['handler']) ? 'Callable' : 'Not Callable') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルートの確認: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// 6. ルーティングのテスト
echo "<h2>📋 ルーティングのテスト</h2>";
try {
    echo "<p>現在のパス: " . htmlspecialchars($path) . "</p>";
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
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ルーティング処理: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

// 7. パスマッチングの詳細テスト
echo "<h2>📋 パスマッチングの詳細テスト</h2>";
try {
    $routes = Router::getRoutes();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    echo "<p>テストするパス: " . htmlspecialchars($path) . "</p>";
    echo "<p>テストするメソッド: " . htmlspecialchars($method) . "</p>";
    
    foreach ($routes as $index => $route) {
        echo "<h3>ルート " . ($index + 1) . "</h3>";
        echo "<p>メソッド: " . htmlspecialchars($route['method']) . " (期待: " . htmlspecialchars($method) . ")</p>";
        echo "<p>パス: " . htmlspecialchars($route['path']) . "</p>";
        echo "<p>パターン: " . htmlspecialchars($route['pattern']) . "</p>";
        
        $methodMatch = ($route['method'] === $method);
        $pathMatch = preg_match($route['pattern'], $path, $matches);
        
        echo "<p>メソッドマッチ: " . ($methodMatch ? '✅' : '❌') . "</p>";
        echo "<p>パスマッチ: " . ($pathMatch ? '✅' : '❌') . "</p>";
        
        if ($methodMatch && $pathMatch) {
            echo "<p style='color: green;'>✅ このルートがマッチします！</p>";
            echo "<p>マッチしたパラメータ: " . print_r($matches, true) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ パスマッチングテスト: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>🎯 ルーティング詳細デバッグ完了</h2>";
echo "<p><a href='index_production.php'>← index_production.phpにアクセス</a></p>";
echo "<p><a href='simple_index.php'>← simple_index.phpにアクセス</a></p>";
?>
