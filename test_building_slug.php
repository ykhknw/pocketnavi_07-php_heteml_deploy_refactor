<?php
/**
 * 建築物スラッグ検索のデバッグテスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 建築物スラッグ検索デバッグテスト ===\n";

try {
    // 環境変数の読み込み
    require_once 'src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    // データベース接続
    require_once 'src/Utils/DatabaseConnection.php';
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();
    
    // 必要なファイルを読み込み
    require_once 'src/Views/includes/functions.php';
    require_once 'src/Services/BuildingService.php';
    
    $slug = 'aichi-high-school-of-technology-and-engineering';
    $lang = 'ja';
    
    echo "検索対象スラッグ: $slug\n";
    echo "言語: $lang\n\n";
    
    // 1. 直接データベースで検索
    echo "=== 1. 直接データベース検索 ===\n";
    $stmt = $pdo->prepare('SELECT building_id, title, slug FROM buildings_table_3 WHERE slug = ?');
    $stmt->execute([$slug]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ データベースで建築物が見つかりました:\n";
        echo "  ID: " . $result['building_id'] . "\n";
        echo "  タイトル: " . $result['title'] . "\n";
        echo "  スラッグ: " . $result['slug'] . "\n";
    } else {
        echo "❌ データベースで建築物が見つかりませんでした\n";
        exit;
    }
    
    // 2. BuildingServiceクラスで検索
    echo "\n=== 2. BuildingServiceクラスで検索 ===\n";
    $buildingService = new BuildingService();
    $building = $buildingService->getBySlug($slug, $lang);
    
    if ($building) {
        echo "✅ BuildingServiceで建築物が見つかりました:\n";
        echo "  ID: " . $building['building_id'] . "\n";
        echo "  タイトル: " . $building['title'] . "\n";
        echo "  スラッグ: " . $building['slug'] . "\n";
        echo "  建築家: " . ($building['architectJa'] ?? 'なし') . "\n";
    } else {
        echo "❌ BuildingServiceで建築物が見つかりませんでした\n";
    }
    
    // 3. 既存の関数で検索
    echo "\n=== 3. 既存の関数で検索 ===\n";
    $building2 = getBuildingBySlug($slug, $lang);
    
    if ($building2) {
        echo "✅ 既存の関数で建築物が見つかりました:\n";
        echo "  ID: " . $building2['building_id'] . "\n";
        echo "  タイトル: " . $building2['title'] . "\n";
        echo "  スラッグ: " . $building2['slug'] . "\n";
        echo "  建築家: " . ($building2['architectJa'] ?? 'なし') . "\n";
    } else {
        echo "❌ 既存の関数で建築物が見つかりませんでした\n";
    }
    
    // 4. SQLクエリの詳細テスト
    echo "\n=== 4. SQLクエリの詳細テスト ===\n";
    $sql = "
        SELECT b.building_id,
               b.uid,
               b.title,
               b.titleEn,
               b.slug,
               b.lat,
               b.lng,
               b.location,
               b.locationEn_from_datasheetChunkEn as locationEn,
               b.completionYears,
               b.buildingTypes,
               b.buildingTypesEn,
               b.prefectures,
               b.prefecturesEn,
               b.has_photo,
               b.thumbnailUrl,
               b.youtubeUrl,
               b.created_at,
               b.updated_at,
               0 as likes,
               GROUP_CONCAT(
                   DISTINCT ia.name_ja 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectJa,
               GROUP_CONCAT(
                   DISTINCT ia.name_en 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ' / '
               ) AS architectEn,
               GROUP_CONCAT(
                   DISTINCT ba.architect_id 
                   ORDER BY ba.architect_order 
                   SEPARATOR ','
               ) AS architectIds,
               GROUP_CONCAT(
                   DISTINCT ia.slug 
                   ORDER BY ba.architect_order, ac.order_index 
                   SEPARATOR ','
               ) AS architectSlugs
        FROM buildings_table_3 b
        LEFT JOIN building_architects ba ON b.building_id = ba.building_id
        LEFT JOIN architect_compositions_2 ac ON ba.architect_id = ac.architect_id
        LEFT JOIN individual_architects_3 ia ON ac.individual_architect_id = ia.individual_architect_id
        WHERE b.slug = ?
        GROUP BY b.building_id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$slug]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ 完全なSQLクエリで建築物が見つかりました:\n";
        echo "  ID: " . $result['building_id'] . "\n";
        echo "  タイトル: " . $result['title'] . "\n";
        echo "  スラッグ: " . $result['slug'] . "\n";
        echo "  建築家: " . ($result['architectJa'] ?? 'なし') . "\n";
    } else {
        echo "❌ 完全なSQLクエリで建築物が見つかりませんでした\n";
    }
    
} catch (Exception $e) {
    echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== テスト完了 ===\n";
?>
