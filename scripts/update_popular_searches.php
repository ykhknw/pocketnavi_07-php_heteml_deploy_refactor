#!/usr/local/php/8.3/bin/php
<?php
/**
 * 人気検索キャッシュ更新スクリプト
 * このスクリプトは定期的に実行してキャッシュを更新します
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ログファイルのパスを設定
$logFile = __DIR__ . '/../logs/cron_update_popular_searches.log';

// ログ出力関数
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

// 必要なファイルを読み込み
//require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_unified.php';
require_once __DIR__ . '/../src/Services/PopularSearchCache.php';

try {
    writeLog("人気検索キャッシュ更新を開始します");
    
    $cacheService = new PopularSearchCache();
    
    // キャッシュの状態を確認
    $status = $cacheService->getCacheStatus();
    writeLog("現在のキャッシュ状態: " . $status['status']);
    writeLog("最終更新: " . $status['last_update']);
    writeLog("データ数: " . $status['data_count']);
    
    // 各検索タイプのキャッシュを更新
    $searchTypes = ['', 'architect', 'building', 'prefecture', 'text'];
    
    foreach ($searchTypes as $searchType) {
        writeLog("検索タイプ '{$searchType}' のキャッシュを更新中");
        
        $result = $cacheService->getPopularSearches(1, 50, '', $searchType);
        
        if (isset($result['searches']) && count($result['searches']) > 0) {
            writeLog("  - " . count($result['searches']) . " 件のデータを取得しました");
        } else {
            writeLog("  - データが見つかりませんでした（フォールバックデータを使用）");
        }
    }
    
    // 更新後のキャッシュ状態を確認
    $newStatus = $cacheService->getCacheStatus();
    writeLog("更新後のキャッシュ状態:");
    writeLog("- 状態: " . $newStatus['status']);
    writeLog("- 最終更新: " . $newStatus['last_update']);
    writeLog("- データ数: " . $newStatus['data_count']);
    
    writeLog("人気検索キャッシュ更新が完了しました");
    
} catch (Exception $e) {
    $errorMessage = "エラーが発生しました: " . $e->getMessage();
    writeLog($errorMessage);
    error_log("Popular searches cache update error: " . $e->getMessage());
    exit(1);
}
?>
