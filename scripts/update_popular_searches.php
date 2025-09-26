<?php
/**
 * 人気検索キャッシュ更新スクリプト
 * このスクリプトは定期的に実行してキャッシュを更新します
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 必要なファイルを読み込み
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/PopularSearchCache.php';

try {
    echo "人気検索キャッシュ更新を開始します...\n";
    
    $cacheService = new PopularSearchCache();
    
    // キャッシュの状態を確認
    $status = $cacheService->getCacheStatus();
    echo "現在のキャッシュ状態: " . $status['status'] . "\n";
    echo "最終更新: " . $status['last_update'] . "\n";
    echo "データ数: " . $status['data_count'] . "\n";
    
    // 各検索タイプのキャッシュを更新
    $searchTypes = ['', 'architect', 'building', 'prefecture', 'text'];
    
    foreach ($searchTypes as $searchType) {
        echo "検索タイプ '{$searchType}' のキャッシュを更新中...\n";
        
        $result = $cacheService->getPopularSearches(1, 20, '', $searchType);
        
        if (isset($result['searches']) && count($result['searches']) > 0) {
            echo "  - " . count($result['searches']) . " 件のデータを取得しました\n";
        } else {
            echo "  - データが見つかりませんでした（フォールバックデータを使用）\n";
        }
    }
    
    // 更新後のキャッシュ状態を確認
    $newStatus = $cacheService->getCacheStatus();
    echo "\n更新後のキャッシュ状態:\n";
    echo "- 状態: " . $newStatus['status'] . "\n";
    echo "- 最終更新: " . $newStatus['last_update'] . "\n";
    echo "- データ数: " . $newStatus['data_count'] . "\n";
    
    echo "\n人気検索キャッシュ更新が完了しました。\n";
    
} catch (Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage() . "\n";
    error_log("Popular searches cache update error: " . $e->getMessage());
    exit(1);
}
?>
