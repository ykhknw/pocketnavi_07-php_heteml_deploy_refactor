<?php
/**
 * Web環境でのBuildingService.php確認
 */

// デバッグ情報を出力
echo "=== Web環境でのBuildingService.php確認 ===\n";
echo "現在時刻: " . date('Y-m-d H:i:s') . "\n";
echo "BuildingService.phpのパス: " . __DIR__ . "/src/Services/BuildingService.php\n";
echo "ファイルの最終更新時刻: " . date('Y-m-d H:i:s', filemtime(__DIR__ . "/src/Services/BuildingService.php")) . "\n";

// BuildingService.phpの内容を確認（executeArchitectSearchメソッド部分）
$filePath = __DIR__ . "/src/Services/BuildingService.php";
$content = file_get_contents($filePath);

// executeArchitectSearchメソッドの内容を抽出
if (preg_match('/private function executeArchitectSearch\([^}]+\}/s', $content, $matches)) {
    echo "\n=== executeArchitectSearchメソッドの内容 ===\n";
    echo $matches[0] . "\n";
} else {
    echo "\n=== executeArchitectSearchメソッドが見つかりません ===\n";
}

// WHERE句の部分を確認
if (strpos($content, 'WHERE {$whereSql}') !== false) {
    echo "\n✅ 修正済み: WHERE {\$whereSql} が見つかりました\n";
} else {
    echo "\n❌ 未修正: WHERE {\$whereSql} が見つかりません\n";
}

if (strpos($content, 'WHERE ia.slug = ?') !== false) {
    echo "❌ 問題: WHERE ia.slug = ? がまだ残っています\n";
} else {
    echo "✅ 修正済み: WHERE ia.slug = ? は削除されました\n";
}

// BuildingServiceのインスタンス化テスト
echo "\n=== BuildingServiceインスタンス化テスト ===\n";
try {
    require_once __DIR__ . '/src/Services/BuildingService.php';
    $buildingService = new BuildingService();
    echo "✅ BuildingServiceのインスタンス化成功\n";
    
    // リフレクションでメソッドの存在確認
    $reflection = new ReflectionClass($buildingService);
    if ($reflection->hasMethod('executeArchitectSearch')) {
        echo "✅ executeArchitectSearchメソッドが存在します\n";
    } else {
        echo "❌ executeArchitectSearchメソッドが存在しません\n";
    }
    
} catch (Exception $e) {
    echo "❌ BuildingServiceのインスタンス化失敗: " . $e->getMessage() . "\n";
}

echo "\n=== 確認完了 ===\n";
?>
