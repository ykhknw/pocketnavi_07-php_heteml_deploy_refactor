<?php
/**
 * ローカル環境用のメインエントリーポイント
 */

// エラー表示を有効にする（ローカル環境のみ）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// メモリ制限を増やす
ini_set('memory_limit', '512M');

// 実行時間制限を増やす
set_time_limit(300);

// アプリケーションの開始
try {
    // 環境設定の読み込み
    require_once __DIR__ . '/src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    // データベース接続の確認
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();
    
    // 基本的な検索機能のテスト
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM buildings_table_3");
    $stmt->execute();
    $result = $stmt->fetch();
    $buildingCount = $result['count'];
    
    echo "<h1>🎉 ローカル環境テスト成功</h1>";
    echo "<p>データベース接続: 成功</p>";
    echo "<p>建物データ数: " . number_format($buildingCount) . "件</p>";
    echo "<p>環境: " . ($config['APP_ENV'] ?? 'unknown') . "</p>";
    echo "<p>デバッグ: " . ($config['APP_DEBUG'] ?? 'unknown') . "</p>";
    
    // 検索フォームの表示
    echo "<h2>🔍 検索フォームテスト</h2>";
    
    // 変数の初期化
    $query = $_GET['q'] ?? '';
    $prefectures = $_GET['prefectures'] ?? '';
    $completionYears = $_GET['completionYears'] ?? '';
    $buildingTypes = $_GET['buildingTypes'] ?? '';
    $hasPhotos = isset($_GET['photos']);
    $hasVideos = isset($_GET['videos']);
    $lang = $_GET['lang'] ?? 'ja';
    
    // 翻訳関数の読み込み
    require_once __DIR__ . '/src/Utils/Translation.php';
    
    // 検索フォームの表示
    include __DIR__ . '/src/Views/includes/search_form.php';
    
    // 検索結果の表示
    if (!empty($query) || !empty($prefectures) || !empty($completionYears) || !empty($buildingTypes) || $hasPhotos || $hasVideos) {
        echo "<h2>🔍 検索結果</h2>";
        
        // 簡単な検索クエリ
        $sql = "SELECT * FROM buildings_table_3 WHERE 1=1";
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }
        
        if (!empty($prefectures)) {
            $sql .= " AND location LIKE ?";
            $params[] = "%$prefectures%";
        }
        
        if (!empty($completionYears)) {
            $sql .= " AND completionYear = ?";
            $params[] = $completionYears;
        }
        
        $sql .= " LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $buildings = $stmt->fetchAll();
        
        echo "<p>検索結果: " . count($buildings) . "件</p>";
        
        foreach ($buildings as $building) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h3>" . htmlspecialchars($building['title'] ?? '') . "</h3>";
            echo "<p>場所: " . htmlspecialchars($building['location'] ?? '') . "</p>";
            echo "<p>完成年: " . htmlspecialchars($building['completionYear'] ?? '') . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<h1>❌ エラーが発生しました</h1>";
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>