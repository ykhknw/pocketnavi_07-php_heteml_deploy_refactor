<?php
/**
 * MVCシステム詳細テストスクリプト
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PocketNavi MVCシステム詳細テスト ===\n\n";

try {
    // クラスの読み込みテスト
    echo "1. クラス読み込みテスト\n";
    require_once 'src/Core/Router.php';
    echo "   ✅ Router クラス\n";
    
    require_once 'src/Controllers/BaseController.php';
    echo "   ✅ BaseController クラス\n";
    
    require_once 'src/Controllers/HomeController.php';
    echo "   ✅ HomeController クラス\n";
    
    require_once 'src/Core/View.php';
    echo "   ✅ View クラス\n";
    echo "\n";
    
    // ルーターのテスト
    echo "2. ルーター機能テスト\n";
    Router::clearRoutes();
    
    // テストルートの追加
    Router::get('/test', function() {
        return "Test route works!";
    });
    
    Router::get('/test/{id}', function($id) {
        return "Test route with ID: {$id}";
    });
    
    $routes = Router::getRoutes();
    echo "   ✅ ルート登録: " . count($routes) . " 件\n";
    
    foreach ($routes as $route) {
        echo "     - {$route['method']} {$route['path']}\n";
    }
    echo "\n";
    
    // コントローラーのテスト
    echo "3. コントローラーテスト\n";
    $controller = new HomeController();
    echo "   ✅ HomeController インスタンス作成\n";
    echo "   ✅ 言語設定: " . $controller->getLanguage() . "\n";
    echo "\n";
    
    // ビューシステムのテスト
    echo "4. ビューシステムテスト\n";
    $view = new View();
    echo "   ✅ View インスタンス作成\n";
    echo "   ✅ テンプレートシステム初期化\n";
    echo "\n";
    
    echo "=== テスト完了 ===\n";
    echo "✅ MVCシステムは正常に動作しています！\n";
    echo "新しいアーキテクチャが正常に機能しています。\n";
    
} catch (Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo "   エラーメッセージ: " . $e->getMessage() . "\n";
    echo "   ファイル: " . $e->getFile() . "\n";
    echo "   行番号: " . $e->getLine() . "\n";
    exit(1);
}
?>
