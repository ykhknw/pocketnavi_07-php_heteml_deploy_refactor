<?php
/**
 * ローカル環境でのkume-sekkei-10テスト
 */

// 必要なファイルを読み込み
require_once 'src/Views/includes/functions.php';

echo "=== ローカル環境でのkume-sekkei-10テスト ===\n\n";

$targetSlug = 'kume-sekkei-10';

// Step 1: functions.phpでの検索
echo "--- Step 1: functions.phpでの検索 ---\n";
try {
    $result = searchBuildingsByArchitectSlug($targetSlug, 1, 'ja', 10);
    
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
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

// Step 2: BuildingService直接テスト
echo "\n--- Step 2: BuildingService直接テスト ---\n";
try {
    $buildingService = new BuildingService();
    $result = $buildingService->searchByArchitectSlug($targetSlug, 1, 'ja', 10);
    
    echo "BuildingService直接検索結果:\n";
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
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

// Step 3: addArchitectConditionsメソッドのテスト
echo "\n--- Step 3: addArchitectConditionsメソッドのテスト ---\n";
try {
    $buildingService = new BuildingService();
    
    // リフレクションを使用してプライベートメソッドをテスト
    $reflection = new ReflectionClass($buildingService);
    $method = $reflection->getMethod('addArchitectConditions');
    $method->setAccessible(true);
    
    $whereClauses = [];
    $params = [];
    $method->invoke($buildingService, $whereClauses, $params, $targetSlug);
    
    echo "addArchitectConditions結果:\n";
    echo "  WHERE句: " . implode(' AND ', $whereClauses) . "\n";
    echo "  パラメータ: " . json_encode($params) . "\n";
    echo "  パラメータ数: " . count($params) . "\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== テスト完了 ===\n";
?>
