<?php
/**
 * 本番環境レート制限テスト
 * 実際のAPIエンドポイントでの動作確認
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>本番環境レート制限テスト</h1>\n";

echo "<h2>1. APIエンドポイントのテスト</h2>\n";

// CSRFトークンを取得
require_once __DIR__ . '/src/Utils/CSRFHelper.php';
$csrfToken = getCSRFToken('search');

if (!$csrfToken) {
    echo "❌ CSRFトークンの取得に失敗しました\n";
    exit;
}

echo "✅ CSRFトークン取得成功: " . substr($csrfToken, 0, 20) . "...\n";

// APIエンドポイントのURL
$apiUrl = 'http://localhost/api/search-count.php';

echo "<h3>レート制限テスト（50回リクエスト）</h3>\n";

$successCount = 0;
$rateLimitCount = 0;
$errorCount = 0;

for ($i = 1; $i <= 50; $i++) {
    // POSTデータの準備
    $postData = [
        'csrf_token' => $csrfToken,
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
    
    // cURLでAPIリクエスト
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'X-CSRF-Token: ' . $csrfToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $successCount++;
        echo "試行 {$i}: ✅ 成功 (HTTP {$httpCode})\n";
    } elseif ($httpCode === 429) {
        $rateLimitCount++;
        echo "試行 {$i}: ❌ レート制限 (HTTP {$httpCode})\n";
        
        // レスポンスからRetry-Afterを取得
        $responseData = json_decode($response, true);
        if (isset($responseData['retry_after'])) {
            echo "  再試行可能時間: {$responseData['retry_after']}秒後\n";
        }
        break; // レート制限に達したら終了
    } else {
        $errorCount++;
        echo "試行 {$i}: ❌ エラー (HTTP {$httpCode})\n";
        if ($response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['error'])) {
                echo "  エラー: {$responseData['error']}\n";
            }
        }
    }
    
    usleep(100000); // 0.1秒待機
}

echo "<h2>2. テスト結果</h2>\n";
echo "成功: {$successCount}回\n";
echo "レート制限: {$rateLimitCount}回\n";
echo "エラー: {$errorCount}回\n";

if ($rateLimitCount > 0) {
    echo "✅ レート制限が正常に動作しています\n";
} else {
    echo "⚠️ レート制限が発動しませんでした\n";
}

echo "<h2>3. レート制限管理画面の確認</h2>\n";
echo "<p><a href='admin/rate_limit_management.php' target='_blank'>レート制限管理画面</a>で詳細を確認できます。</p>\n";

echo "<h2>テスト完了</h2>\n";
?>
