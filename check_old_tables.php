<?php
/**
 * 古いテーブルの存在確認と削除前チェック
 */

require_once 'config/database_unified.php';

echo "=== 古いテーブルの存在確認 ===\n\n";

try {
    $pdo = getDB();
    
    // 削除対象のテーブル一覧
    $tablesToCheck = [
        'architect_compositions',
        'architect_websites_3', 
        'buildings_table_2',
        'individual_architects',
        'individual_architects_2'
    ];
    
    echo "📋 削除対象テーブルの存在確認:\n";
    foreach ($tablesToCheck as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch();
            echo "   ✅ {$table}: 存在（レコード数: " . number_format($result['count']) . "件）\n";
        } catch (Exception $e) {
            echo "   ❌ {$table}: 存在しない\n";
        }
    }
    
    echo "\n";
    
    // 現在使用中のテーブル一覧
    $currentTables = [
        'buildings_table_3',
        'individual_architects_3',
        'architect_compositions_2',
        'architect_websites_4',
        'building_architects',
        'global_search_history'
    ];
    
    echo "📋 現在使用中のテーブル:\n";
    foreach ($currentTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch();
            echo "   ✅ {$table}: " . number_format($result['count']) . "件\n";
        } catch (Exception $e) {
            echo "   ❌ {$table}: 存在しない\n";
        }
    }
    
    echo "\n";
    
    // 外部キー制約の確認
    echo "🔍 外部キー制約の確認:\n";
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IN ('architect_compositions', 'architect_websites_3', 'buildings_table_2', 'individual_architects', 'individual_architects_2')
    ");
    
    $foreignKeys = $stmt->fetchAll();
    if (count($foreignKeys) > 0) {
        echo "   ⚠️ 外部キー制約が存在します:\n";
        foreach ($foreignKeys as $fk) {
            echo "      - {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
    } else {
        echo "   ✅ 削除対象テーブルへの外部キー制約はありません\n";
    }
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}

echo "\n=== 確認完了 ===\n";

