<?php
/**
 * 建築家情報取得のデバッグテスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 建築家情報取得デバッグテスト ===\n";

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
    require_once 'src/Services/ArchitectService.php';
    
    $slug = 'mori-building';
    $lang = 'ja';
    
    echo "検索対象スラッグ: $slug\n";
    echo "言語: $lang\n\n";
    
    // 1. 直接データベースで検索
    echo "=== 1. 直接データベース検索 ===\n";
    $stmt = $pdo->prepare('SELECT individual_architect_id, name_ja, name_en, slug FROM individual_architects_3 WHERE slug = ?');
    $stmt->execute([$slug]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ データベースで建築家が見つかりました:\n";
        echo "  ID: " . $result['individual_architect_id'] . "\n";
        echo "  日本語名: " . $result['name_ja'] . "\n";
        echo "  英語名: " . $result['name_en'] . "\n";
        echo "  スラッグ: " . $result['slug'] . "\n";
    } else {
        echo "❌ データベースで建築家が見つかりませんでした\n";
        exit;
    }
    
    // 2. ArchitectServiceクラスで検索
    echo "\n=== 2. ArchitectServiceクラスで検索 ===\n";
    $architectService = new ArchitectService();
    $architect = $architectService->getBySlug($slug, $lang);
    
    if ($architect) {
        echo "✅ ArchitectServiceで建築家が見つかりました:\n";
        echo "  ID: " . $architect['id'] . "\n";
        echo "  name: " . ($architect['name'] ?? 'NULL') . "\n";
        echo "  nameJa: " . ($architect['nameJa'] ?? 'NULL') . "\n";
        echo "  nameEn: " . ($architect['nameEn'] ?? 'NULL') . "\n";
        echo "  name_ja: " . ($architect['name_ja'] ?? 'NULL') . "\n";
        echo "  name_en: " . ($architect['name_en'] ?? 'NULL') . "\n";
        echo "  スラッグ: " . $architect['slug'] . "\n";
    } else {
        echo "❌ ArchitectServiceで建築家が見つかりませんでした\n";
    }
    
    // 3. 既存の関数で検索
    echo "\n=== 3. 既存の関数で検索 ===\n";
    $architect2 = getArchitectBySlug($slug, $lang);
    
    if ($architect2) {
        echo "✅ 既存の関数で建築家が見つかりました:\n";
        echo "  ID: " . $architect2['id'] . "\n";
        echo "  name: " . ($architect2['name'] ?? 'NULL') . "\n";
        echo "  nameJa: " . ($architect2['nameJa'] ?? 'NULL') . "\n";
        echo "  nameEn: " . ($architect2['nameEn'] ?? 'NULL') . "\n";
        echo "  name_ja: " . ($architect2['name_ja'] ?? 'NULL') . "\n";
        echo "  name_en: " . ($architect2['name_en'] ?? 'NULL') . "\n";
        echo "  スラッグ: " . $architect2['slug'] . "\n";
    } else {
        echo "❌ 既存の関数で建築家が見つかりませんでした\n";
    }
    
    // 4. 建築家検索の完全なテスト
    echo "\n=== 4. 建築家検索の完全なテスト ===\n";
    $searchResult = searchBuildingsByArchitectSlug($slug, 1, $lang, 10);
    
    if ($searchResult && isset($searchResult['architectInfo'])) {
        $architectInfo = $searchResult['architectInfo'];
        echo "✅ 建築家検索で建築家情報が取得されました:\n";
        echo "  ID: " . ($architectInfo['id'] ?? 'NULL') . "\n";
        echo "  name: " . ($architectInfo['name'] ?? 'NULL') . "\n";
        echo "  nameJa: " . ($architectInfo['nameJa'] ?? 'NULL') . "\n";
        echo "  nameEn: " . ($architectInfo['nameEn'] ?? 'NULL') . "\n";
        echo "  name_ja: " . ($architectInfo['name_ja'] ?? 'NULL') . "\n";
        echo "  name_en: " . ($architectInfo['name_en'] ?? 'NULL') . "\n";
        echo "  スラッグ: " . ($architectInfo['slug'] ?? 'NULL') . "\n";
    } else {
        echo "❌ 建築家検索で建築家情報が取得されませんでした\n";
    }
    
} catch (Exception $e) {
    echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== テスト完了 ===\n";
?>
