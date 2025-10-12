<?php

// Router.phpは既に読み込まれているため、重複読み込みを避ける
if (!class_exists('Router')) {
    require_once __DIR__ . '/../../src/Core/Router.php';
}

// HomeController.phpは既に読み込まれているため、重複読み込みを避ける
if (!class_exists('HomeController')) {
    try {
        require_once __DIR__ . '/../../src/Controllers/HomeController.php';
    } catch (Exception $e) {
        error_log("HomeController loading error: " . $e->getMessage());
        // HomeControllerが読み込めない場合はスキップ
    }
}

// ルートの定義
Router::get('/', 'HomeController@index');
Router::get('/buildings/{slug}', 'HomeController@building');
Router::get('/architects/{slug}', 'HomeController@architect');

// テストルート
Router::get('/test', function() {
    return json_encode(['message' => 'Test route works!']);
});
