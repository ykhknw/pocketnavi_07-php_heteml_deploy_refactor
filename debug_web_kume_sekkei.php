<?php
/**
 * Web環境でのkume-sekkei-10デバッグ
 * http://localhost/debug_web_kume_sekkei.php?debug=1
 */

// デバッグモードの確認
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

if ($debug) {
    echo "<pre>";
    echo "=== Web環境でのkume-sekkei-10デバッグ ===\n";
    echo "現在時刻: " . date('Y-m-d H:i:s') . "\n";
    echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
    echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
    echo "\n";
}

// 必要なファイルを読み込み
require_once 'src/Views/includes/functions.php';

$targetSlug = 'kume-sekkei-10';

// Step 1: functions.phpでの検索
if ($debug) {
    echo "--- Step 1: functions.phpでの検索 ---\n";
}
try {
    $result = searchBuildingsByArchitectSlug($targetSlug, 1, 'ja', 10);
    
    if ($debug) {
        echo "functions.php検索結果:\n";
        echo "  結果件数: " . $result['total'] . "\n";
        echo "  建築物数: " . count($result['buildings']) . "\n";
        
        if (!empty($result['buildings'])) {
            echo "  最初の5件の建築物:\n";
            for ($i = 0; $i < min(5, count($result['buildings'])); $i++) {
                $building = $result['buildings'][$i];
                echo "    " . ($i + 1) . ". " . $building['title'] . "\n";
                if (!empty($building['architects'])) {
                    $architectNames = array_map(function($arch) {
                        return $arch['architectJa'];
                    }, $building['architects']);
                    echo "       建築家: " . implode(' / ', $architectNames) . "\n";
                }
            }
        }
    }
    
    // デバッグ情報をJSONで出力
    if ($debug) {
        echo "\n--- デバッグ情報 ---\n";
        echo "Total: " . $result['total'] . "\n";
        echo "Buildings count: " . count($result['buildings']) . "\n";
        echo "Architect info: " . (isset($result['architectInfo']) ? 'Present' : 'Not present') . "\n";
    }
    
} catch (Exception $e) {
    if ($debug) {
        echo "エラー: " . $e->getMessage() . "\n";
        echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    }
}

if ($debug) {
    echo "\n=== デバッグ完了 ===\n";
    echo "</pre>";
} else {
    // デバッグモードでない場合は、結果をJSONで出力
    header('Content-Type: application/json');
    echo json_encode([
        'total' => $result['total'] ?? 0,
        'buildings_count' => count($result['buildings'] ?? []),
        'architect_info' => isset($result['architectInfo']),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
