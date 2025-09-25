<?php
/**
 * 検索履歴データのクリーンアップスクリプト
 * 
 * 使用方法:
 * php scripts/cleanup_search_history.php [retention_days] [--archive] [--stats]
 * 
 * 例:
 * php scripts/cleanup_search_history.php 90 --archive
 * php scripts/cleanup_search_history.php --stats
 */

// スクリプトのパスを取得
$scriptDir = dirname(__FILE__);
$projectRoot = dirname($scriptDir);

// プロジェクトのルートディレクトリをインクルードパスに追加
set_include_path($projectRoot . PATH_SEPARATOR . get_include_path());

// 必要なファイルを読み込み
require_once $projectRoot . '/config/database.php';
require_once $projectRoot . '/src/Services/SearchLogService.php';

/**
 * コマンドライン引数を解析
 */
function parseArguments($argv) {
    $options = [
        'retention_days' => 90,  // デフォルト90日
        'archive' => false,
        'stats' => false,
        'help' => false
    ];
    
    foreach ($argv as $arg) {
        if (is_numeric($arg)) {
            $options['retention_days'] = (int)$arg;
        } elseif ($arg === '--archive') {
            $options['archive'] = true;
        } elseif ($arg === '--stats') {
            $options['stats'] = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        }
    }
    
    return $options;
}

/**
 * ヘルプメッセージを表示
 */
function showHelp() {
    echo "検索履歴データのクリーンアップスクリプト\n\n";
    echo "使用方法:\n";
    echo "  php scripts/cleanup_search_history.php [retention_days] [options]\n\n";
    echo "引数:\n";
    echo "  retention_days    データ保持期間（日数、デフォルト: 90）\n\n";
    echo "オプション:\n";
    echo "  --archive         重要なデータをアーカイブしてから削除\n";
    echo "  --stats           データベースの統計情報を表示\n";
    echo "  --help, -h        このヘルプメッセージを表示\n\n";
    echo "例:\n";
    echo "  php scripts/cleanup_search_history.php 90 --archive\n";
    echo "  php scripts/cleanup_search_history.php --stats\n";
    echo "  php scripts/cleanup_search_history.php 30\n";
}

/**
 * 統計情報を表示
 */
function showStats($searchLogService) {
    echo "=== データベース統計情報 ===\n\n";
    
    $stats = $searchLogService->getDatabaseStats();
    
    if (isset($stats['error'])) {
        echo "エラー: " . $stats['error'] . "\n";
        return;
    }
    
    // テーブルサイズ情報
    echo "テーブルサイズ:\n";
    foreach ($stats['table_stats'] as $table) {
        echo sprintf(
            "  %s: %s MB (%s レコード)\n",
            $table['table_name'],
            $table['Size (MB)'],
            number_format($table['table_rows'])
        );
    }
    echo "\n";
    
    // 検索履歴統計
    if (!empty($stats['history_stats'])) {
        $history = $stats['history_stats'];
        echo "検索履歴統計:\n";
        echo "  総レコード数: " . number_format($history['total_records']) . "\n";
        echo "  ユニーク検索語: " . number_format($history['unique_queries']) . "\n";
        echo "  検索タイプ数: " . $history['search_types'] . "\n";
        echo "  最古のレコード: " . $history['oldest_record'] . "\n";
        echo "  最新のレコード: " . $history['newest_record'] . "\n";
        echo "  過去1週間: " . number_format($history['records_last_week']) . " レコード\n";
        echo "  過去1ヶ月: " . number_format($history['records_last_month']) . " レコード\n\n";
    }
    
    // 推奨事項
    if (!empty($stats['recommendations'])) {
        echo "推奨事項:\n";
        foreach ($stats['recommendations'] as $recommendation) {
            $type = $recommendation['type'] === 'warning' ? '⚠️' : 'ℹ️';
            echo "  {$type} " . $recommendation['message'] . "\n";
        }
        echo "\n";
    }
}

/**
 * クリーンアップを実行
 */
function performCleanup($searchLogService, $retentionDays, $archive) {
    echo "=== 検索履歴クリーンアップ開始 ===\n";
    echo "保持期間: {$retentionDays}日\n";
    echo "アーカイブ: " . ($archive ? '有効' : '無効') . "\n\n";
    
    $result = $searchLogService->cleanupOldSearchHistory($retentionDays, $archive);
    
    if ($result['error']) {
        echo "❌ エラー: " . $result['error'] . "\n";
        return false;
    }
    
    echo "✅ クリーンアップ完了\n";
    echo "削除されたレコード: " . number_format($result['deleted_count']) . "\n";
    
    if ($archive) {
        echo "アーカイブされたレコード: " . number_format($result['archived_count']) . "\n";
    }
    
    echo "\n";
    return true;
}

/**
 * メイン処理
 */
function main() {
    global $argv;
    
    $options = parseArguments($argv);
    
    if ($options['help']) {
        showHelp();
        return;
    }
    
    try {
        $searchLogService = new SearchLogService();
        
        if ($options['stats']) {
            showStats($searchLogService);
        } else {
            $success = performCleanup(
                $searchLogService, 
                $options['retention_days'], 
                $options['archive']
            );
            
            if ($success) {
                echo "クリーンアップ後の統計情報:\n";
                showStats($searchLogService);
            }
        }
        
    } catch (Exception $e) {
        echo "❌ エラー: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// スクリプトが直接実行された場合のみメイン処理を実行
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    main();
}
