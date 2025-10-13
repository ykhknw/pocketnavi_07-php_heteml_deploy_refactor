<?php
/**
 * データベース接続テストスクリプト
 * 建築物の存在確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== データベース接続テスト ===\n";

try {
    // 環境変数の読み込み
    require_once 'src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    echo "環境変数読み込み完了\n";
    echo "DB_HOST: " . ($config['DB_HOST'] ?? '未設定') . "\n";
    echo "DB_NAME: " . ($config['DB_NAME'] ?? '未設定') . "\n";
    echo "DB_USERNAME: " . ($config['DB_USERNAME'] ?? '未設定') . "\n";
    echo "DB_PASSWORD: " . (empty($config['DB_PASSWORD']) ? '未設定' : '設定済み') . "\n";
    
    // データベース接続
    require_once 'src/Utils/DatabaseConnection.php';
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
    
    echo "\nデータベース接続成功\n";
    
    // 接続設定の確認
    $dbConfig = $db->getConfig();
    echo "接続設定:\n";
    foreach ($dbConfig as $key => $value) {
        echo "  $key: $value\n";
    }
    
    // 該当建築物の検索
    echo "\n=== 建築物検索テスト ===\n";
    $slug = 'aichi-high-school-of-technology-and-engineering';
    
    $stmt = $pdo->prepare('SELECT building_id, title, slug FROM buildings_table_3 WHERE slug = ?');
    $stmt->execute([$slug]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ 建築物が見つかりました:\n";
        echo "  ID: " . $result['building_id'] . "\n";
        echo "  タイトル: " . $result['title'] . "\n";
        echo "  スラッグ: " . $result['slug'] . "\n";
    } else {
        echo "❌ 建築物が見つかりませんでした: $slug\n";
        
        // 類似のスラッグを検索
        echo "\n類似のスラッグを検索中...\n";
        $stmt = $pdo->prepare('SELECT slug, title FROM buildings_table_3 WHERE slug LIKE ? LIMIT 10');
        $stmt->execute(['%aichi%']);
        $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($similar) {
            echo "類似のスラッグ:\n";
            foreach ($similar as $row) {
                echo "  - " . $row['slug'] . " (" . $row['title'] . ")\n";
            }
        } else {
            echo "類似のスラッグが見つかりませんでした。\n";
        }
        
        // 全スラッグの数を確認
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM buildings_table_3');
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nデータベース内の建築物総数: " . $count['total'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== テスト完了 ===\n";
?>