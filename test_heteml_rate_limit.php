<?php
/**
 * HETEML本番環境レート制限テスト
 * レンタルサーバーでの動作確認
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>HETEML本番環境レート制限テスト</h1>\n";

echo "<h2>1. 環境情報</h2>\n";
echo "サーバー: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "現在時刻: " . date('Y-m-d H:i:s') . "\n";

echo "<h2>2. レート制限システムの基本テスト</h2>\n";

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Security/RateLimiter.php';

$rateLimiter = new RateLimiter();
$testIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

echo "テストIP: {$testIP}\n";

// 設定の確認
$debug = $rateLimiter->getDebugInfo('search', $testIP);
$limit = $debug['config']['limit'] ?? 30;
$window = $debug['config']['window'] ?? 60;

echo "制限値: {$limit}回/{$window}秒\n";
echo "Redis利用可能: " . ($debug['redis_available'] ? 'Yes' : 'No') . "\n";

echo "<h3>レート制限テスト（15回リクエスト）</h3>\n";

$blockedAt = null;
for ($i = 1; $i <= 15; $i++) {
    $allowed = $rateLimiter->checkLimit('search', $testIP);
    $status = $allowed ? '✅ 許可' : '❌ 拒否';
    echo "試行 {$i}: {$status}";
    
    if (!$allowed && !$blockedAt) {
        $blockedAt = $i;
        echo " ← 制限発動！";
    }
    echo "\n";
    
    if (!$allowed) {
        $blockTime = $rateLimiter->isBlocked('search', $testIP);
        if ($blockTime) {
            echo "ブロック時間: " . date('Y-m-d H:i:s', $blockTime) . "\n";
        }
        break;
    }
    
    usleep(100000); // 0.1秒待機
}

echo "<h2>3. APIエンドポイントのテスト</h2>\n";

// CSRFトークンを取得
require_once __DIR__ . '/src/Utils/CSRFHelper.php';
$csrfToken = getCSRFToken('search');

if (!$csrfToken) {
    echo "❌ CSRFトークンの取得に失敗しました\n";
} else {
    echo "✅ CSRFトークン取得成功\n";
    
    // APIエンドポイントのURL（本番環境用）
    $apiUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api/search-count.php';
    
    echo "API URL: {$apiUrl}\n";
    
    // 簡単なAPIテスト（3回のみ）
    echo "<h3>APIテスト（3回リクエスト）</h3>\n";
    
    for ($i = 1; $i <= 3; $i++) {
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
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'X-CSRF-Token: ' . $csrfToken
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // HETEML用
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "API試行 {$i}: ✅ 成功 (HTTP {$httpCode})\n";
        } elseif ($httpCode === 429) {
            echo "API試行 {$i}: ❌ レート制限 (HTTP {$httpCode})\n";
            break;
        } else {
            echo "API試行 {$i}: ❌ エラー (HTTP {$httpCode})\n";
        }
        
        usleep(200000); // 0.2秒待機
    }
}

echo "<h2>4. テスト結果</h2>\n";

if ($blockedAt) {
    echo "✅ レート制限が正常に動作しています（{$blockedAt}回目で制限発動）\n";
} else {
    echo "⚠️ レート制限が発動しませんでした（15回以内）\n";
}

echo "<h2>5. 管理画面へのリンク</h2>\n";
echo "<p><a href='admin/rate_limit_management.php' target='_blank'>レート制限管理画面</a></p>\n";

echo "<h2>テスト完了</h2>\n";
echo "<p>本番環境でのレート制限システムの動作確認が完了しました。</p>\n";
?>
