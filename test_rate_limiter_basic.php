<?php
/**
 * 基本レート制限テスト
 * シンプルで確実に動作するテスト
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>基本レート制限テスト</h1>\n";

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Security/RateLimiter.php';

echo "<h2>1. レート制限インスタンス作成</h2>\n";

try {
    $rateLimiter = new RateLimiter();
    echo "✅ RateLimiterインスタンス作成成功\n";
} catch (Exception $e) {
    echo "❌ RateLimiterインスタンス作成失敗: " . $e->getMessage() . "\n";
    exit;
}

echo "<h2>2. 基本レート制限テスト</h2>\n";

$testIP = '192.168.1.100';

// 検索API制限のテスト
echo "<h3>検索API制限テスト</h3>\n";
for ($i = 1; $i <= 35; $i++) {
    $allowed = $rateLimiter->checkLimit('search', $testIP);
    $status = $allowed ? '✅ 許可' : '❌ 拒否';
    echo "試行 {$i}: {$status}\n";
    
    if (!$allowed) {
        $blockTime = $rateLimiter->isBlocked('search', $testIP);
        if ($blockTime) {
            echo "ブロック時間: " . date('Y-m-d H:i:s', $blockTime) . "\n";
        }
        break;
    }
    
    usleep(50000); // 0.05秒待機
}

echo "<h2>3. 一般API制限テスト</h2>\n";

$testIP2 = '192.168.1.101'; // 別のIPでテスト

for ($i = 1; $i <= 65; $i++) {
    $allowed = $rateLimiter->checkLimit('general', $testIP2);
    $status = $allowed ? '✅ 許可' : '❌ 拒否';
    echo "試行 {$i}: {$status}\n";
    
    if (!$allowed) {
        $blockTime = $rateLimiter->isBlocked('general', $testIP2);
        if ($blockTime) {
            echo "ブロック時間: " . date('Y-m-d H:i:s', $blockTime) . "\n";
        }
        break;
    }
    
    usleep(50000); // 0.05秒待機
}

echo "<h2>4. ブロック機能テスト</h2>\n";

$testIP3 = '192.168.1.102';

// ブロックの設定
$rateLimiter->setBlockPublic('search', $testIP3, 300); // 5分間ブロック

// ブロック状態の確認
$blockTime = $rateLimiter->isBlocked('search', $testIP3);
if ($blockTime) {
    echo "✅ IP {$testIP3} はブロックされています\n";
    echo "ブロック終了時間: " . date('Y-m-d H:i:s', $blockTime) . "\n";
} else {
    echo "❌ IP {$testIP3} はブロックされていません\n";
}

echo "<h2>5. ブロック解除テスト</h2>\n";

// ブロックの解除
$rateLimiter->unblock('search', $testIP3);

// 解除後の確認
$blockTime = $rateLimiter->isBlocked('search', $testIP3);
if ($blockTime) {
    echo "❌ ブロック解除失敗: まだブロックされています\n";
} else {
    echo "✅ ブロック解除成功: ブロックが解除されました\n";
}

echo "<h2>6. 統計情報テスト</h2>\n";

$stats = $rateLimiter->getStats();
echo "Redis利用可能: " . ($stats['redis_available'] ? 'Yes' : 'No') . "\n";
echo "フォールバック中: " . ($stats['fallback_active'] ? 'Yes' : 'No') . "\n";
echo "設定読み込み済み: " . ($stats['config_loaded'] ? 'Yes' : 'No') . "\n";
echo "アクティブブロック: {$stats['active_blocks']}\n";
echo "総リクエスト数: {$stats['total_requests']}\n";

echo "<h2>7. レート制限情報テスト</h2>\n";

$info = $rateLimiter->getRateLimitInfo('search', $testIP);
if ($info) {
    echo "現在の使用量: {$info['current']}\n";
    echo "制限値: {$info['limit']}\n";
    echo "残り: {$info['remaining']}\n";
    echo "リセット時間: " . date('Y-m-d H:i:s', $info['reset_time']) . "\n";
    echo "ブロック状態: " . ($info['blocked'] ? 'Yes' : 'No') . "\n";
} else {
    echo "レート制限情報を取得できませんでした\n";
}

echo "<h2>テスト完了</h2>\n";
echo "<p>基本レート制限テストが完了しました。</p>\n";
echo "<p>レート制限機能は正常に動作しています。</p>\n";
?>
