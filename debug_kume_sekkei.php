<?php
/**
 * kume-sekkei-10のslug_group機能デバッグ用テストスクリプト
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

echo "=== kume-sekkei-10のslug_group機能デバッグテスト ===\n\n";

$targetSlug = 'kume-sekkei-10';

// Step 1: individual_architects_3でslug='kume-sekkei-10'を検索
echo "--- Step 1: individual_architects_3での検索 ---\n";
try {
    $db = getDB();
    $sql = "SELECT individual_architect_id, name_ja, name_en, slug, slug_group_id FROM individual_architects_3 WHERE slug = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$targetSlug]);
    $architect = $stmt->fetch();
    
    if ($architect) {
        echo "建築家データが見つかりました:\n";
        echo "  individual_architect_id: " . $architect['individual_architect_id'] . "\n";
        echo "  name_ja: " . $architect['name_ja'] . "\n";
        echo "  name_en: " . $architect['name_en'] . "\n";
        echo "  slug: " . $architect['slug'] . "\n";
        echo "  slug_group_id: " . ($architect['slug_group_id'] ?? 'NULL') . "\n";
    } else {
        echo "建築家データが見つかりませんでした。\n";
        exit;
    }
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: slug_group_idがNULLでない場合、slug_to_groupでgroup_idのslug一覧を取得
if ($architect['slug_group_id'] !== null) {
    echo "\n--- Step 2: slug_to_groupでの検索 ---\n";
    try {
        $groupId = $architect['slug_group_id'];
        $sql = "SELECT slug FROM slug_to_group WHERE group_id = ? ORDER BY slug";
        $stmt = $db->prepare($sql);
        $stmt->execute([$groupId]);
        $groupSlugs = $stmt->fetchAll();
        
        echo "group_id = {$groupId} のslug一覧:\n";
        foreach ($groupSlugs as $row) {
            echo "  - " . $row['slug'] . "\n";
        }
        echo "合計: " . count($groupSlugs) . "個のslug\n";
        
    } catch (Exception $e) {
        echo "エラー: " . $e->getMessage() . "\n";
        exit;
    }
} else {
    echo "\n--- Step 2: slug_group_idがNULLのため、グループ検索をスキップ ---\n";
    $groupSlugs = [['slug' => $targetSlug]];
}

// Step 3: 各slugに対応する建築家の建築物数を確認
echo "\n--- Step 3: 各建築家の建築物数確認 ---\n";
try {
    $slugList = array_column($groupSlugs, 'slug');
    $placeholders = str_repeat('?,', count($slugList) - 1) . '?';
    
    $sql = "SELECT ia.slug, ia.name_ja, COUNT(DISTINCT b.building_id) as building_count
            FROM individual_architects_3 ia
            LEFT JOIN architect_compositions_2 ac ON ia.individual_architect_id = ac.individual_architect_id
            LEFT JOIN building_architects ba ON ac.architect_id = ba.architect_id
            LEFT JOIN buildings_table_3 b ON ba.building_id = b.building_id
            WHERE ia.slug IN ($placeholders)
            GROUP BY ia.slug, ia.name_ja
            ORDER BY ia.slug";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($slugList);
    $results = $stmt->fetchAll();
    
    $totalBuildings = 0;
    foreach ($results as $row) {
        echo "  " . $row['slug'] . " (" . $row['name_ja'] . "): " . $row['building_count'] . "件\n";
        $totalBuildings += $row['building_count'];
    }
    echo "合計建築物数: " . $totalBuildings . "件\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}

// Step 4: BuildingServiceでの検索結果確認
echo "\n--- Step 4: BuildingServiceでの検索結果 ---\n";
try {
    $buildingService = new BuildingService();
    $result = $buildingService->searchByArchitectSlug($targetSlug, 1, 'ja', 10);
    
    echo "BuildingService検索結果:\n";
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
}

// Step 5: functions.phpでの検索結果確認
echo "\n--- Step 5: functions.phpでの検索結果 ---\n";
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
}

// Step 6: 直接SQLクエリでの確認
echo "\n--- Step 6: 直接SQLクエリでの確認 ---\n";
try {
    $placeholders = str_repeat('?,', count($slugList) - 1) . '?';
    
    $sql = "SELECT COUNT(DISTINCT b.building_id) as total_count
            FROM buildings_table_3 b
            LEFT JOIN building_architects ba ON b.building_id = ba.building_id
            LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
            LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
            WHERE ia.slug IN ($placeholders)
            AND b.location IS NOT NULL AND b.location != ''";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($slugList);
    $result = $stmt->fetch();
    
    echo "直接SQLクエリ結果:\n";
    echo "  総建築物数: " . $result['total_count'] . "件\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}

echo "\n=== デバッグテスト完了 ===\n";
?>
