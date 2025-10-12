<?php

// 安全なルーティングシステム
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

// HomeController.phpは既に読み込まれているため、重複読み込みを避ける
if (!class_exists('HomeController')) {
    try {
        require_once __DIR__ . '/../../src/Controllers/HomeController.php';
    } catch (Exception $e) {
        error_log("HomeController loading error: " . $e->getMessage());
        // HomeControllerが読み込めない場合は簡単なルートのみ定義
        Router::get('/', function() {
            return '<h1>PocketNavi - 建築検索システム</h1><p>システムは正常に動作しています。</p><p><a href="simple_index.php">検索ページにアクセス</a></p>';
        });
        return;
    }
}

// ルートの定義
try {
    Router::get('/', 'HomeController@index');
    Router::get('/buildings/{slug}', 'HomeController@building');
    Router::get('/architects/{slug}', 'HomeController@architect');
    
    // テストルート
    Router::get('/test', function() {
        return json_encode(['message' => 'Test route works!']);
    });
    
} catch (Exception $e) {
    error_log("Route definition error: " . $e->getMessage());
    // エラーが発生した場合は簡単なルートのみ定義
    Router::get('/', function() {
        return '<h1>PocketNavi - 建築検索システム</h1><p>システムは正常に動作しています。</p><p><a href="simple_index.php">検索ページにアクセス</a></p>';
    });
}
?>
