<?php
/**
 * has_photoフィールドの値を確認するテスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== has_photoフィールドの値確認テスト ===\n";

try {
    // 環境変数の読み込み
    require_once 'src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    // データベース接続
    require_once 'src/Utils/DatabaseConnection.php';
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
    
    echo "データベース接続成功\n\n";
    
    // has_photoフィールドの値を確認
    echo "=== has_photoフィールドの値確認 ===\n";
    $stmt = $pdo->query('SELECT building_id, title, has_photo FROM buildings_table_3 ORDER BY building_id LIMIT 10');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        echo "ID: " . $row['building_id'] . "\n";
        echo "タイトル: " . $row['title'] . "\n";
        echo "has_photo: " . var_export($row['has_photo'], true) . "\n";
        echo "has_photo type: " . gettype($row['has_photo']) . "\n";
        echo "!empty(has_photo): " . var_export(!empty($row['has_photo']), true) . "\n";
        echo "has_photo != '0': " . var_export($row['has_photo'] != '0', true) . "\n";
        echo "---\n";
    }
    
    // has_photoがNULLのレコードを確認
    echo "\n=== has_photoがNULLのレコード確認 ===\n";
    $stmt = $pdo->prepare('SELECT building_id, title, has_photo FROM buildings_table_3 WHERE has_photo IS NULL LIMIT 5');
    $stmt->execute();
    $nullResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($nullResults as $row) {
        echo "ID: " . $row['building_id'] . "\n";
        echo "タイトル: " . $row['title'] . "\n";
        echo "has_photo: " . var_export($row['has_photo'], true) . "\n";
        echo "has_photo type: " . gettype($row['has_photo']) . "\n";
        echo "!empty(has_photo): " . var_export(!empty($row['has_photo']), true) . "\n";
        echo "has_photo != '0': " . var_export($row['has_photo'] != '0', true) . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== テスト完了 ===\n";
?>
