<?php
/**
 * レート制限デバッグテスト
 * 制限が発動しない原因を特定
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>レート制限デバッグテスト</h1>\n";

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Security/RateLimiter.php';

echo "<h2>1. デバッグ情報の確認</h2>\n";

$rateLimiter = new RateLimiter();
$testIP = '192.168.1.300';

// 初期状態のデバッグ情報
$debug = $rateLimiter->getDebugInfo('search', $testIP);
echo "<h3>初期状態</h3>\n";
echo "設定: " . json_encode($debug['config'], JSON_PRETTY_PRINT) . "\n";
echo "Redis利用可能: " . ($debug['redis_available'] ? 'Yes' : 'No') . "\n";
echo "現在のカウント: {$debug['current_count']}\n";
echo "フォールバックストレージ: " . json_encode($debug['fallback_storage'], JSON_PRETTY_PRINT) . "\n";

echo "<h2>2. 制限テスト（詳細ログ付き）</h2>\n";

$limit = $debug['config']['limit'] ?? 30;
$window = $debug['config']['window'] ?? 60;

echo "制限値: {$limit}回/{$window}秒\n";
echo "テスト開始: " . date('Y-m-d H:i:s') . "\n\n";

$blockedAt = null;
for ($i = 1; $i <= $limit + 5; $i++) {
    echo "試行 {$i}:\n";
    
    // 制限チェック前の状態
    $debugBefore = $rateLimiter->getDebugInfo('search', $testIP);
    echo "  チェック前 - カウント: {$debugBefore['current_count']}\n";
    
    // 制限チェック
    $allowed = $rateLimiter->checkLimit('search', $testIP);
    $status = $allowed ? '✅ 許可' : '❌ 拒否';
    echo "  結果: {$status}\n";
    
    // 制限チェック後の状態
    $debugAfter = $rateLimiter->getDebugInfo('search', $testIP);
    echo "  チェック後 - カウント: {$debugAfter['current_count']}\n";
    echo "  フォールバックストレージ: " . count($debugAfter['fallback_storage']) . " エントリ\n";
    
    if (!$allowed && !$blockedAt) {
        $blockedAt = $i;
        echo "  ← 制限発動！\n";
        break;
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

echo "<h2>3. 最終状態の確認</h2>\n";

$finalDebug = $rateLimiter->getDebugInfo('search', $testIP);
echo "最終カウント: {$finalDebug['current_count']}\n";
echo "制限値: {$limit}\n";
echo "フォールバックストレージ: " . json_encode($finalDebug['fallback_storage'], JSON_PRETTY_PRINT) . "\n";

if ($blockedAt) {
    echo "✅ 制限が正しく動作しました（{$blockedAt}回目で制限発動）\n";
} else {
    echo "❌ 制限が発動しませんでした\n";
    echo "原因の可能性:\n";
    echo "- フォールバックストレージの実装に問題がある\n";
    echo "- 設定値が正しく適用されていない\n";
    echo "- カウントの増加処理に問題がある\n";
}

echo "<h2>4. 手動カウントテスト</h2>\n";

$testIP2 = '192.168.1.301';
$key = "rate_limit:search:{$testIP2}";

echo "手動でカウントを増加してテスト\n";
for ($i = 1; $i <= 5; $i++) {
    $rateLimiter->incrementCountPublic($key, 60);
    $count = $rateLimiter->getCurrentCountPublic($key, 60);
    echo "手動増加 {$i}: カウント = {$count}\n";
}

echo "<h2>テスト完了</h2>\n";
?>
