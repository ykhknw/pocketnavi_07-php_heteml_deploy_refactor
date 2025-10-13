<?php
/**
 * キャッシュクリアスクリプト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== キャッシュクリアスクリプト ===\n";

try {
    // キャッシュディレクトリの確認
    $cacheDir = 'cache/search';
    
    if (!is_dir($cacheDir)) {
        echo "キャッシュディレクトリが存在しません: $cacheDir\n";
        exit;
    }
    
    echo "キャッシュディレクトリ: $cacheDir\n";
    
    // キャッシュファイルの一覧
    $cacheFiles = glob($cacheDir . '/*.json');
    echo "キャッシュファイル数: " . count($cacheFiles) . "\n";
    
    if (count($cacheFiles) > 0) {
        echo "\n=== キャッシュファイルの削除 ===\n";
        
        $deletedCount = 0;
        foreach ($cacheFiles as $file) {
            if (unlink($file)) {
                $deletedCount++;
                echo "削除: " . basename($file) . "\n";
            } else {
                echo "削除失敗: " . basename($file) . "\n";
            }
        }
        
        echo "\n削除されたファイル数: $deletedCount\n";
    } else {
        echo "削除するキャッシュファイルがありません\n";
    }
    
    echo "\n=== キャッシュクリア完了 ===\n";
    
} catch (Exception $e) {
    echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
}

echo "\n=== スクリプト完了 ===\n";
?>
