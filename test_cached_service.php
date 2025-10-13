<?php
/**
 * CachedBuildingServiceの初期化テスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CachedBuildingService初期化テスト ===\n";

try {
    // 環境変数の読み込み
    require_once 'src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    echo "環境変数読み込み完了\n";
    
    // データベース接続
    require_once 'src/Utils/DatabaseConnection.php';
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
    
    echo "データベース接続成功\n";
    
    // 必要なファイルを読み込み
    require_once 'src/Views/includes/functions.php';
    require_once 'src/Services/BuildingService.php';
    require_once 'src/Services/CachedBuildingService.php';
    
    echo "必要なファイル読み込み完了\n";
    
    // 1. BuildingServiceのテスト
    echo "\n=== 1. BuildingServiceのテスト ===\n";
    try {
        $buildingService = new BuildingService();
        echo "✅ BuildingService初期化成功\n";
        
        $slug = 'aichi-high-school-of-technology-and-engineering';
        $building = $buildingService->getBySlug($slug, 'ja');
        
        if ($building) {
            echo "✅ BuildingServiceで建築物が見つかりました:\n";
            echo "  ID: " . $building['building_id'] . "\n";
            echo "  タイトル: " . $building['title'] . "\n";
            echo "  スラッグ: " . $building['slug'] . "\n";
        } else {
            echo "❌ BuildingServiceで建築物が見つかりませんでした\n";
        }
    } catch (Exception $e) {
        echo "❌ BuildingServiceエラー: " . $e->getMessage() . "\n";
        echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    }
    
    // 2. CachedBuildingServiceのテスト
    echo "\n=== 2. CachedBuildingServiceのテスト ===\n";
    try {
        $cachedService = new CachedBuildingService(true, 3600);
        echo "✅ CachedBuildingService初期化成功\n";
        
        $slug = 'aichi-high-school-of-technology-and-engineering';
        $building = $cachedService->getBySlug($slug, 'ja');
        
        if ($building) {
            echo "✅ CachedBuildingServiceで建築物が見つかりました:\n";
            echo "  ID: " . $building['building_id'] . "\n";
            echo "  タイトル: " . $building['title'] . "\n";
            echo "  スラッグ: " . $building['slug'] . "\n";
        } else {
            echo "❌ CachedBuildingServiceで建築物が見つかりませんでした\n";
        }
    } catch (Exception $e) {
        echo "❌ CachedBuildingServiceエラー: " . $e->getMessage() . "\n";
        echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    }
    
    // 3. 既存の関数のテスト
    echo "\n=== 3. 既存の関数のテスト ===\n";
    try {
        $slug = 'aichi-high-school-of-technology-and-engineering';
        $building = getBuildingBySlug($slug, 'ja');
        
        if ($building) {
            echo "✅ 既存の関数で建築物が見つかりました:\n";
            echo "  ID: " . $building['building_id'] . "\n";
            echo "  タイトル: " . $building['title'] . "\n";
            echo "  スラッグ: " . $building['slug'] . "\n";
        } else {
            echo "❌ 既存の関数で建築物が見つかりませんでした\n";
        }
    } catch (Exception $e) {
        echo "❌ 既存の関数エラー: " . $e->getMessage() . "\n";
        echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== テスト完了 ===\n";
?>
