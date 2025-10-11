<?php
/**
 * データベース接続テスト
 */

require_once 'config/database_unified.php';

echo "=== データベース接続テスト ===\n\n";

try {
    $pdo = getDB();
    echo "✅ データベース接続成功\n";
    
    // データベース名の確認
    $stmt = $pdo->query('SELECT DATABASE() as db_name');
    $result = $stmt->fetch();
    echo "📋 接続データベース: " . $result['db_name'] . "\n";
    
    // テーブル一覧の確認
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 テーブル一覧:\n";
    foreach ($tables as $table) {
        echo "   - {$table}\n";
    }
    
    echo "\n";
    
    // 各テーブルのレコード数を確認
    $tableCounts = [
        'buildings_table_3' => '建築物',
        'individual_architects_3' => '建築家',
        'architect_compositions_2' => '建築家構成',
        'architect_websites_4' => '建築家ウェブサイト',
        'global_search_history' => '検索履歴'
    ];
    
    echo "📊 テーブルレコード数:\n";
    foreach ($tableCounts as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch();
            echo "   - {$description} ({$table}): " . number_format($result['count']) . "件\n";
        } catch (Exception $e) {
            echo "   - {$description} ({$table}): テーブルが見つかりません\n";
        }
    }
    
    echo "\n";
    
    // 複雑なJOINクエリのテスト
    echo "🔍 複雑なJOINクエリテスト:\n";
    try {
        $stmt = $pdo->query('
            SELECT b.title, b.location, ia.name_ja as architect_name 
            FROM buildings_table_3 b 
            LEFT JOIN building_architects ba ON b.building_id = ba.building_id 
            LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id 
            LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id 
            LIMIT 5
        ');
        $results = $stmt->fetchAll();
        
        echo "   ✅ JOINクエリ成功（" . count($results) . "件取得）\n";
        foreach ($results as $row) {
            echo "   📋 {$row['title']} - {$row['architect_name']} ({$row['location']})\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ JOINクエリエラー: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ データベース接続エラー: " . $e->getMessage() . "\n";
}

echo "\n=== テスト完了 ===\n";

