<?php
/**
 * 検索結果件数取得API
 * フィルター変更時の動的件数更新用
 */

// CORS設定
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// OPTIONSリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // リクエストボディの取得
    $input = file_get_contents('php://input');
    $searchParams = json_decode($input, true);
    
    if (!$searchParams) {
        throw new Exception('Invalid JSON input');
    }
    
    // 必要なファイルの読み込み
    require_once __DIR__ . '/../src/Utils/DatabaseConnection.php';
    require_once __DIR__ . '/../src/Services/BuildingService.php';
    require_once __DIR__ . '/../src/Views/includes/functions.php';
    
    // データベース接続
    $db = DatabaseConnection::getInstance();
    
    // BuildingServiceの初期化
    $buildingService = new BuildingService($db);
    
    // 検索パラメータの正規化
    $query = $searchParams['q'] ?? '';
    $prefectures = $searchParams['prefectures'] ?? '';
    $completionYears = $searchParams['completionYears'] ?? '';
    $hasPhotos = isset($searchParams['hasPhotos']) && $searchParams['hasPhotos'] !== '';
    $hasVideos = isset($searchParams['hasVideos']) && $searchParams['hasVideos'] !== '';
    $userLat = $searchParams['userLat'] ?? null;
    $userLng = $searchParams['userLng'] ?? null;
    $radiusKm = $searchParams['radiusKm'] ?? 10;
    $lang = $searchParams['lang'] ?? 'ja';
    
    // 配列の正規化
    if (is_string($prefectures) && !empty($prefectures)) {
        $prefectures = [$prefectures];
    }
    if (is_string($completionYears) && !empty($completionYears)) {
        $completionYears = [$completionYears];
    }
    
    // 検索実行（件数のみ取得）
    $searchResult = $buildingService->searchBuildingsWithMultipleConditions(
        $query,
        $prefectures,
        $completionYears,
        $hasPhotos,
        $hasVideos,
        $userLat,
        $userLng,
        $radiusKm,
        1, // ページ番号
        $lang,
        1  // 1件のみ取得（件数確認用）
    );
    
    // 結果の返却
    $response = [
        'success' => true,
        'count' => $searchResult['total'],
        'query' => $query,
        'prefectures' => $prefectures,
        'completionYears' => $completionYears,
        'hasPhotos' => $hasPhotos,
        'hasVideos' => $hasVideos
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // エラーログの記録
    error_log("Search count API error: " . $e->getMessage());
    
    // エラーレスポンス
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
