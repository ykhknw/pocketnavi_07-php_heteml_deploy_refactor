<?php
/**
 * データベーステーブル構造の確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== データベーステーブル構造確認 ===\n";

try {
    // 環境変数の読み込み
    require_once 'src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    echo "環境変数読み込み完了\n";
    echo "DB_HOST: " . ($config['DB_HOST'] ?? '未設定') . "\n";
    echo "DB_NAME: " . ($config['DB_NAME'] ?? '未設定') . "\n";
    echo "DB_USERNAME: " . ($config['DB_USERNAME'] ?? '未設定') . "\n";
    echo "DB_PASSWORD: " . (empty($config['DB_PASSWORD']) ? '未設定' : '設定済み') . "\n\n";
    
    // データベース接続
    require_once 'src/Utils/DatabaseConnection.php';
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
    
    echo "データベース接続成功\n\n";
    
    // テーブル一覧を取得
    echo "=== テーブル一覧 ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // 各テーブルの構造を確認
    $targetTables = [
        'buildings_table_3',
        'building_architects',
        'architect_compositions_2',
        'individual_architects_3'
    ];
    
    foreach ($targetTables as $table) {
        echo "\n=== $table の構造 ===\n";
        
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                echo "  " . $column['Field'] . " - " . $column['Type'] . "\n";
            }
            
            // レコード数を確認
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  レコード数: " . $count['count'] . "\n";
        } else {
            echo "  ❌ テーブルが存在しません\n";
        }
    }
    
    // 建築物と建築家の関連を確認
    echo "\n=== 建築物と建築家の関連確認 ===\n";
    $slug = 'aichi-high-school-of-technology-and-engineering';
    
    // 建築物のIDを取得
    $stmt = $pdo->prepare('SELECT building_id FROM buildings_table_3 WHERE slug = ?');
    $stmt->execute([$slug]);
    $building = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($building) {
        $buildingId = $building['building_id'];
        echo "建築物ID: $buildingId\n";
        
        // building_architectsテーブルで関連を確認
        if (in_array('building_architects', $tables)) {
            $stmt = $pdo->prepare('SELECT * FROM building_architects WHERE building_id = ?');
            $stmt->execute([$buildingId]);
            $relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($relations) {
                echo "building_architects の関連:\n";
                foreach ($relations as $relation) {
                    echo "  architect_id: " . $relation['architect_id'] . "\n";
                }
            } else {
                echo "building_architects に関連が見つかりません\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 確認完了 ===\n";
?>
