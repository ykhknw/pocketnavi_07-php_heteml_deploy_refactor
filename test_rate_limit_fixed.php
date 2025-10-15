<?php
/**
 * 修正版レート制限テスト
 * フォールバックストレージの修正を確認
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>修正版レート制限テスト</h1>\n";

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Security/RateLimiter.php';

echo "<h2>1. 修正版レート制限テスト</h2>\n";

$rateLimiter = new RateLimiter();
$testIP = '192.168.1.400';

// 設定の確認
$debug = $rateLimiter->getDebugInfo('search', $testIP);
$limit = $debug['config']['limit'] ?? 30;
$window = $debug['config']['window'] ?? 60;

echo "制限値: {$limit}回/{$window}秒\n";
echo "テスト開始: " . date('Y-m-d H:i:s') . "\n\n";

$blockedAt = null;
for ($i = 1; $i <= $limit + 5; $i++) {
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
    
    usleep(50000); // 0.05秒待機
}

echo "<h2>2. 最終状態の確認</h2>\n";

$finalDebug = $rateLimiter->getDebugInfo('search', $testIP);
echo "最終カウント: {$finalDebug['current_count']}\n";
echo "制限値: {$limit}\n";
echo "フォールバックストレージ: " . count($finalDebug['fallback_storage']) . " エントリ\n";

if ($blockedAt) {
    echo "✅ 制限が正しく動作しました（{$blockedAt}回目で制限発動）\n";
} else {
    echo "❌ 制限が発動しませんでした\n";
}

echo "<h2>3. 手動カウントテスト（修正版）</h2>\n";

$testIP2 = '192.168.1.401';
$key = "rate_limit:search:{$testIP2}";

echo "手動でカウントを増加してテスト\n";
for ($i = 1; $i <= 5; $i++) {
    $rateLimiter->incrementCountPublic($key, 60);
    $count = $rateLimiter->getCurrentCountPublic($key, 60);
    echo "手動増加 {$i}: カウント = {$count}\n";
    usleep(100000); // 0.1秒待機
}

echo "<h2>4. バースト制限テスト</h2>\n";

$testIP3 = '192.168.1.402';
echo "バースト制限テスト（10回/10秒）\n";

$burstBlockedAt = null;
for ($i = 1; $i <= 15; $i++) {
    $allowed = $rateLimiter->checkLimit('search', $testIP3);
    $status = $allowed ? '✅ 許可' : '❌ 拒否';
    echo "試行 {$i}: {$status}";
    
    if (!$allowed && !$burstBlockedAt) {
        $burstBlockedAt = $i;
        echo " ← バースト制限発動！";
    }
    echo "\n";
    
    if (!$allowed) {
        break;
    }
    
    usleep(50000); // 0.05秒待機
}

if ($burstBlockedAt) {
    echo "✅ バースト制限が正しく動作しました（{$burstBlockedAt}回目で制限発動）\n";
} else {
    echo "⚠️ バースト制限が発動しませんでした\n";
}

echo "<h2>テスト完了</h2>\n";

if ($blockedAt) {
    echo "<p>✅ レート制限は正常に動作しています。</p>\n";
} else {
    echo "<p>❌ レート制限の修正が必要です。</p>\n";
}
?>
