<?php
/**
 * 建築家slug検索でslug_groupを利用する機能のテストスクリプト
 */

// 必要なファイルを読み込み
require_once 'src/Views/includes/functions.php';
require_once 'src/Services/BuildingService.php';

// getDB関数を定義（テスト用）
if (!function_exists('getDB')) {
    function getDB() {
        static $pdo = null;
        
        if ($pdo === null) {
            try {
                require_once __DIR__ . '/src/Utils/DatabaseConnection.php';
                $db = DatabaseConnection::getInstance();
                $pdo = $db->getConnection();
            } catch (Exception $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        
        return $pdo;
    }
}

echo "=== 建築家slug検索でslug_groupを利用する機能のテスト ===\n\n";

// テスト用の建築家slug（実際のデータに合わせて調整してください）
$testSlugs = [
    'shirai-architectural-institute',  // slug_group_idがNULLの場合
    'maki-and-associates',  // slug_group_idが設定されている場合
];

foreach ($testSlugs as $slug) {
    echo "--- テスト: {$slug} ---\n";
    
    // BuildingServiceを使用した検索
    echo "BuildingServiceでの検索:\n";
    try {
        $buildingService = new BuildingService();
        $result = $buildingService->searchByArchitectSlug($slug, 1, 'ja', 10);
        
        echo "  結果件数: " . $result['total'] . "\n";
        echo "  建築物数: " . count($result['buildings']) . "\n";
        
        if (!empty($result['buildings'])) {
            echo "  最初の建築物:\n";
            $firstBuilding = $result['buildings'][0];
            echo "    タイトル: " . $firstBuilding['title'] . "\n";
            
            // 建築家情報の表示
            if (!empty($firstBuilding['architects'])) {
                $architectNames = array_map(function($arch) {
                    return $arch['architectJa'];
                }, $firstBuilding['architects']);
                echo "    建築家: " . implode(' / ', $architectNames) . "\n";
            } else {
                echo "    建築家: N/A\n";
            }
            
            echo "    データ構造: " . implode(', ', array_keys($firstBuilding)) . "\n";
        }
        
    } catch (Exception $e) {
        echo "  エラー: " . $e->getMessage() . "\n";
    }
    
    // functions.phpを使用した検索（BuildingService経由）
    echo "functions.phpでの検索:\n";
    try {
        $result = searchBuildingsByArchitectSlug($slug, 1, 'ja', 10);
        
        echo "  結果件数: " . $result['total'] . "\n";
        echo "  建築物数: " . count($result['buildings']) . "\n";
        
        if (!empty($result['buildings'])) {
            echo "  最初の建築物:\n";
            $firstBuilding = $result['buildings'][0];
            echo "    タイトル: " . $firstBuilding['title'] . "\n";
            
            // 建築家情報の表示
            if (!empty($firstBuilding['architects'])) {
                $architectNames = array_map(function($arch) {
                    return $arch['architectJa'];
                }, $firstBuilding['architects']);
                echo "    建築家: " . implode(' / ', $architectNames) . "\n";
            } else {
                echo "    建築家: N/A\n";
            }
            
            echo "    データ構造: " . implode(', ', array_keys($firstBuilding)) . "\n";
        }
        
    } catch (Exception $e) {
        echo "  エラー: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// slug_group_idの確認テスト
echo "--- slug_group_id確認テスト ---\n";
foreach ($testSlugs as $slug) {
    $slugGroupId = getSlugGroupId($slug);
    echo "{$slug}: slug_group_id = " . ($slugGroupId ?? 'NULL') . "\n";
    
    if ($slugGroupId !== null) {
        $groupSlugs = getSlugsByGroupId($slugGroupId);
        echo "  グループ内のslug: " . implode(', ', $groupSlugs) . "\n";
    }
}

echo "\n=== テスト完了 ===\n";
?>
