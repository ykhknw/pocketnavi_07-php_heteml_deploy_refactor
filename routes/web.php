<?php

require_once __DIR__ . '/../../src/Core/Router.php';
require_once __DIR__ . '/../../src/Controllers/HomeController.php';

// ルートの定義
Router::get('/', 'HomeController@index');
Router::get('/buildings/{slug}', 'HomeController@building');
Router::get('/architects/{slug}', 'HomeController@architect');

// テストルート
Router::get('/test', function() {
    return json_encode(['message' => 'Test route works!']);
});
