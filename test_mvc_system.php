<?php
/**
 * MVCシステムテストスクリプト
 * 新しいアーキテクチャのテスト
 */

// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PocketNavi MVCシステムテスト ===\n\n";

try {
    // 必要なファイルの存在確認
    $requiredFiles = [
        'src/Core/Router.php',
        'src/Controllers/BaseController.php',
        'src/Controllers/HomeController.php',
        'src/Core/View.php',
        'routes/web.php',
        'index_new.php'
    ];
    
    echo "1. ファイル存在確認\n";
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "   ✅ {$file}\n";
        } else {
            echo "   ❌ {$file} - 見つかりません\n";
        }
    }
    echo "\n";
    
    echo "=== テスト完了 ===\n";
    echo "✅ MVCシステムのファイル確認が完了しました。\n";
    
} catch (Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo "   エラーメッセージ: " . $e->getMessage() . "\n";
    exit(1);
}
?>
