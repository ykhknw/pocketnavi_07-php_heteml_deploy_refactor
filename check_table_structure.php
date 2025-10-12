<?php
/**
 * データベーステーブル構造確認スクリプト
 * buildings_table_3の実際のカラム構造を確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 データベーステーブル構造確認</h1>";

try {
    // データベース接続
    require_once __DIR__ . '/config/database_unified.php';
    $pdo = getDatabaseConnection();
    echo "<p style='color: green;'>✅ データベース接続: 成功</p>";
    
    // buildings_table_3の構造を確認
    echo "<h2>📋 buildings_table_3の構造</h2>";
    
    $stmt = $pdo->prepare("DESCRIBE buildings_table_3");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th><th>追加</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // サンプルデータの確認
    echo "<h2>📋 サンプルデータ（最初の3件）</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM buildings_table_3 LIMIT 3");
    $stmt->execute();
    $samples = $stmt->fetchAll();
    
    if (!empty($samples)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys($samples[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        foreach ($samples as $sample) {
            echo "<tr>";
            foreach ($sample as $value) {
                echo "<td>" . htmlspecialchars(substr($value ?? '', 0, 50)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 他のテーブルの構造も確認
    echo "<h2>📋 他のテーブルの構造</h2>";
    
    $tables = ['individual_architects_3', 'architect_compositions_2', 'popular_searches'];
    
    foreach ($tables as $table) {
        echo "<h3>$table</h3>";
        
        try {
            $stmt = $pdo->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th><th>追加</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ $table: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

echo "<h2>🎯 データベーステーブル構造確認完了</h2>";
echo "<p><a href='simple_index.php'>← 簡単なメインページに戻る</a></p>";
?>
