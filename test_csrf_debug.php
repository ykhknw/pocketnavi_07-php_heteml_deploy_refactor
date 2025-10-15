<?php
/**
 * CSRFトークンデバッグテスト
 * トークン検証の問題を特定
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>CSRFトークンデバッグテスト</h1>\n";

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Utils/CSRFHelper.php';

echo "<h2>1. CSRFトークンの生成と検証テスト</h2>\n";

// トークンを生成
$token = getCSRFToken('search');
echo "生成されたトークン: " . substr($token, 0, 20) . "...\n";

// 直接検証
$isValid = validateCSRFToken($token, 'search');
echo "直接検証結果: " . ($isValid ? '✅ 有効' : '❌ 無効') . "\n";

echo "<h2>2. AJAX検証の詳細テスト</h2>\n";

// セッション情報の確認
echo "セッションID: " . session_id() . "\n";
echo "セッション開始: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "\n";

// セッション内容の確認
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "セッション内容: " . json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n";
}

echo "<h2>3. 手動POSTデータテスト</h2>\n";

// 手動でPOSTデータを設定
$_POST['csrf_token'] = $token;
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "POSTデータ設定: csrf_token = " . substr($token, 0, 20) . "...\n";

// POST検証
$postValid = validatePostCSRFToken('search');
echo "POST検証結果: " . ($postValid ? '✅ 有効' : '❌ 無効') . "\n";

echo "<h2>4. ヘッダー検証テスト</h2>\n";

// ヘッダーを設定
$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
echo "ヘッダー設定: X-CSRF-Token = " . substr($token, 0, 20) . "...\n";

// AJAX検証
$ajaxValid = validateAjaxCSRFToken('search');
echo "AJAX検証結果: " . ($ajaxValid ? '✅ 有効' : '❌ 無効') . "\n";

echo "<h2>5. 実際のAPIエンドポイントテスト</h2>\n";

// 実際のAPIエンドポイントのURL
$apiUrl = 'http://localhost/api/search-count.php';

// POSTデータの準備
$postData = [
    'csrf_token' => $token,
    'query' => 'test',
    'prefectures' => '',
    'architectsSlug' => '',
    'completionYears' => '',
    'hasPhotos' => false,
    'hasVideos' => false,
    'userLat' => null,
    'userLng' => null,
    'radiusKm' => 5
];

echo "API URL: {$apiUrl}\n";
echo "POSTデータ: " . json_encode($postData, JSON_PRETTY_PRINT) . "\n";

// cURLでAPIリクエスト
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'X-CSRF-Token: ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
echo "Response: " . substr($response, 0, 200) . "...\n";

if ($httpCode === 200) {
    echo "✅ APIリクエスト成功\n";
} else {
    echo "❌ APIリクエスト失敗\n";
    
    // レスポンスを解析
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['error'])) {
        echo "エラー: {$responseData['error']}\n";
    }
}

echo "<h2>テスト完了</h2>\n";
?>
