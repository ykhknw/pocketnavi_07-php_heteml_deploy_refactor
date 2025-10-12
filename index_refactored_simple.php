<?php
/**
 * PocketNavi PHP版 - シンプルなリファクタリングテスト版
 * 段階的に問題を解決するためのテストファイル
 */

// エラーレポートの設定（開発環境用）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>PocketNavi リファクタリングテスト</h1>";

// 1. 基本設定ファイルの確認
echo "<h2>1. 設定ファイルの確認</h2>";

$configFiles = [
    'config/database_unified.php',
    'src/Utils/ErrorHandler.php',
    'src/Views/includes/functions.php'
];

foreach ($configFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} - 存在<br>";
    } else {
        echo "❌ {$file} - 存在しない<br>";
    }
}

// 2. データベース接続のテスト
echo "<h2>2. データベース接続のテスト</h2>";

try {
    require_once 'config/database_unified.php';
    $db = getDB();
    if ($db) {
        echo "✅ データベース接続成功<br>";
        
        // テーブルの確認
        $tables = ['buildings_table_3', 'individual_architects_3'];
        foreach ($tables as $table) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM {$table}");
                $stmt->execute();
                $count = $stmt->fetchColumn();
                echo "✅ テーブル {$table}: {$count} レコード<br>";
            } catch (Exception $e) {
                echo "❌ テーブル {$table}: エラー - " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "❌ データベース接続失敗<br>";
    }
} catch (Exception $e) {
    echo "❌ データベース接続エラー: " . $e->getMessage() . "<br>";
}

// 3. 関数ファイルのテスト
echo "<h2>3. 関数ファイルのテスト</h2>";

try {
    require_once 'src/Views/includes/functions.php';
    echo "✅ functions.php 読み込み成功<br>";
    
    // 基本的な関数のテスト
    if (function_exists('getPopularSearches')) {
        echo "✅ getPopularSearches 関数存在<br>";
    } else {
        echo "❌ getPopularSearches 関数不存在<br>";
    }
    
    if (function_exists('searchBuildings')) {
        echo "✅ searchBuildings 関数存在<br>";
    } else {
        echo "❌ searchBuildings 関数不存在<br>";
    }
    
} catch (Exception $e) {
    echo "❌ functions.php 読み込みエラー: " . $e->getMessage() . "<br>";
}

// 4. 新しいクラスファイルの確認
echo "<h2>4. 新しいクラスファイルの確認</h2>";

$newFiles = [
    'src/Controllers/RefactoredHomeController.php',
    'src/Core/RefactoredRouter.php',
    'src/Services/OptimizedBuildingService.php',
    'src/Utils/EnhancedErrorHandler.php',
    'src/Cache/EnhancedCacheManager.php'
];

foreach ($newFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} - 存在<br>";
        
        // ファイルの構文チェック
        $output = shell_exec("php -l {$file} 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "&nbsp;&nbsp;✅ 構文OK<br>";
        } else {
            echo "&nbsp;&nbsp;❌ 構文エラー: " . htmlspecialchars($output) . "<br>";
        }
    } else {
        echo "❌ {$file} - 存在しない<br>";
    }
}

// 5. 基本的な検索テスト
echo "<h2>5. 基本的な検索テスト</h2>";

try {
    if (function_exists('searchBuildings')) {
        $result = searchBuildings('', 1, false, false, 'ja', 5);
        if (is_array($result) && isset($result['buildings'])) {
            echo "✅ 検索機能正常動作 - " . count($result['buildings']) . " 件の結果<br>";
        } else {
            echo "❌ 検索結果の形式が不正<br>";
        }
    } else {
        echo "❌ 検索関数が利用できません<br>";
    }
} catch (Exception $e) {
    echo "❌ 検索テストエラー: " . $e->getMessage() . "<br>";
}

// 6. 環境情報
echo "<h2>6. 環境情報</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "<br>";

echo "<hr>";
echo "<p><strong>テスト完了</strong></p>";
echo "<p>問題がある場合は、上記のエラーメッセージを確認してください。</p>";
echo "<p><a href='/index.php'>元のindex.phpに戻る</a></p>";
?>
