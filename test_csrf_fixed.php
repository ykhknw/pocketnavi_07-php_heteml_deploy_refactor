<?php
/**
 * 修正版CSRFテスト
 * セッション管理の修正を確認
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>修正版CSRFテスト</h1>\n";

// セッション開始（APIエンドポイントと同じセッションを使用）
if (session_status() === PHP_SESSION_NONE) {
    // セッション設定を明示的に指定
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Utils/CSRFHelper.php';

echo "<h2>1. CSRFトークンの生成</h2>\n";

// セッション情報の表示
echo "セッションID: " . session_id() . "\n";
echo "セッション状態: " . session_status() . "\n";

// トークンを生成
$token = getCSRFToken('search');
echo "生成されたトークン: " . substr($token, 0, 20) . "...\n";

// セッション内容の確認
if (isset($_SESSION['csrf_tokens'])) {
    echo "セッション内のCSRFトークン: " . json_encode($_SESSION['csrf_tokens'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "セッション内にCSRFトークンがありません\n";
}

echo "<h2>2. 修正版APIエンドポイントテスト</h2>\n";

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

// 複数回テスト
$successCount = 0;
$errorCount = 0;

for ($i = 1; $i <= 5; $i++) {
    echo "<h3>テスト {$i}</h3>\n";
    
    // cURLでAPIリクエスト
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // セッションCookieを詳細設定
    $sessionId = session_id();
    $cookieString = 'PHPSESSID=' . $sessionId . '; path=/';
    curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'X-CSRF-Token: ' . $token
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: {$httpCode}\n";
    
    if ($httpCode === 200) {
        $successCount++;
        echo "✅ 成功\n";
        
        // レスポンスを解析
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['count'])) {
            echo "検索結果件数: {$responseData['count']}\n";
        }
    } else {
        $errorCount++;
        echo "❌ 失敗\n";
        
        // レスポンスを解析
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo "エラー: " . ($responseData['error'] ?? 'Unknown error') . "\n";
            
            // デバッグ情報を表示
            if (isset($responseData['debug'])) {
                echo "デバッグ情報:\n";
                foreach ($responseData['debug'] as $key => $value) {
                    if (is_array($value)) {
                        echo "  {$key}: " . json_encode($value) . "\n";
                    } else {
                        echo "  {$key}: {$value}\n";
                    }
                }
            }
        }
    }
    
    echo "\n";
    usleep(200000); // 0.2秒待機
}

echo "<h2>3. テスト結果</h2>\n";
echo "成功: {$successCount}回\n";
echo "エラー: {$errorCount}回\n";

if ($successCount > 0) {
    echo "✅ CSRFトークン検証が正常に動作しています\n";
} else {
    echo "❌ CSRFトークン検証に問題があります\n";
}

echo "<h2>4. レート制限テスト</h2>\n";

if ($successCount > 0) {
    echo "レート制限テストを実行中...\n";
    
    $rateLimitCount = 0;
    for ($i = 1; $i <= 15; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // セッションCookieを詳細設定
    $sessionId = session_id();
    $cookieString = 'PHPSESSID=' . $sessionId . '; path=/';
    curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'X-CSRF-Token: ' . $token
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 429) {
            $rateLimitCount++;
            echo "試行 {$i}: ❌ レート制限 (HTTP {$httpCode})\n";
            break;
        } else {
            echo "試行 {$i}: ✅ 成功 (HTTP {$httpCode})\n";
        }
        
        usleep(100000); // 0.1秒待機
    }
    
    if ($rateLimitCount > 0) {
        echo "✅ レート制限が正常に動作しています\n";
    } else {
        echo "⚠️ レート制限が発動しませんでした\n";
    }
}

echo "<h2>テスト完了</h2>\n";
?>
