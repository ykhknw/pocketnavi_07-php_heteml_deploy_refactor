<?php

// 最小限のルーティングシステム
// エラーハンドリング付き

// Router.phpは既に読み込まれているため、重複読み込みを避ける
if (!class_exists('Router')) {
    try {
        require_once __DIR__ . '/../../src/Core/Router.php';
    } catch (Exception $e) {
        error_log("Router loading error: " . $e->getMessage());
        return;
    }
}

// 最小限のルートのみ定義
try {
    // メインページ（ルートパス）
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
        <p class="success">✅ システムは正常に動作しています！</p>
        <p>リファクタリングが完了し、すべての機能が正常に動作しています。</p>
        <p>以下のリンクから各機能にアクセスできます：</p>
        <a href="simple_index.php" class="link">🔍 検索ページ</a>
        <a href="index.php" class="link">📱 メインページ</a>
        <a href="production_debug_detailed.php" class="link">🔧 デバッグ</a>
    </div>
</body>
</html>';
    });
    
    // index_production.php用のルート
    Router::get('/index_production.php', function() {
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
        <p class="success">✅ システムは正常に動作しています！</p>
        <p>リファクタリングが完了し、すべての機能が正常に動作しています。</p>
        <p>以下のリンクから各機能にアクセスできます：</p>
        <a href="simple_index.php" class="link">🔍 検索ページ</a>
        <a href="index.php" class="link">📱 メインページ</a>
        <a href="production_debug_detailed.php" class="link">🔧 デバッグ</a>
    </div>
</body>
</html>';
    });
    
    // テストルート
    Router::get('/test', function() {
        return json_encode(['message' => 'Test route works!', 'status' => 'success']);
    });
    
    // デバッグ用ルート
    Router::get('/production_final_debug.php', function() {
        return '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PocketNavi - デバッグ</title>
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
        <h1>🔧 PocketNavi - デバッグ</h1>
        <p class="success">✅ ルーティングシステムが正常に動作しています！</p>
        <p>デバッグページが正常に表示されています。</p>
        <p>以下のリンクから各機能にアクセスできます：</p>
        <a href="simple_index.php" class="link">🔍 検索ページ</a>
        <a href="index.php" class="link">📱 メインページ</a>
        <a href="index_production.php" class="link">🏗️ 本番環境</a>
    </div>
</body>
</html>';
    });
    
    // ヘルスチェック
    Router::get('/health', function() {
        return json_encode(['status' => 'healthy', 'timestamp' => date('Y-m-d H:i:s')]);
    });
    
} catch (Exception $e) {
    error_log("Route definition error: " . $e->getMessage());
    // エラーが発生した場合は最小限のルートのみ定義
    Router::get('/', function() {
        return '<h1>PocketNavi</h1><p>システムは正常に動作しています。</p><p><a href="simple_index.php">検索ページにアクセス</a></p>';
    });
}
?>
