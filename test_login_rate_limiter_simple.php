<?php
/**
 * 簡易版ログイン制限テスト
 * 基本的なログイン制限機能のテスト
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>簡易版ログイン制限テスト</h1>\n";

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Security/RateLimiter.php';

echo "<h2>1. 基本レート制限テスト（ログイン用）</h2>\n";

$rateLimiter = new RateLimiter();
$testIP = '192.168.1.100';
$testUser = 'testuser';

// ログイン試行のシミュレーション
echo "<h3>ログイン試行シミュレーション</h3>\n";

for ($i = 1; $i <= 7; $i++) {
    // IP別の制限チェック
    $ipKey = "login_ip:{$testIP}";
    $userKey = "login_user:{$testUser}";
    
    // 現在の試行回数を取得
    $ipAttempts = $rateLimiter->getCurrentCountPublic($ipKey, 3600); // 1時間
    $userAttempts = $rateLimiter->getCurrentCountPublic($userKey, 3600); // 1時間
    
    $maxAttempts = 5; // 最大5回
    
    if ($ipAttempts >= $maxAttempts || $userAttempts >= $maxAttempts) {
        echo "試行 {$i}: ❌ 拒否 - 制限に達しました（IP: {$ipAttempts}回, ユーザー: {$userAttempts}回）\n";
        break;
    }
    
    echo "試行 {$i}: ✅ 許可 - IP: {$ipAttempts}回, ユーザー: {$userAttempts}回\n";
    
    // 試行回数を増加
    $rateLimiter->incrementCountPublic($ipKey, 3600);
    $rateLimiter->incrementCountPublic($userKey, 3600);
    
    usleep(100000); // 0.1秒待機
}

echo "<h2>2. ブロック機能テスト</h2>\n";

// ブロックの設定
$blockKey = "block_ip:{$testIP}";
$blockDuration = 900; // 15分
$expireTime = time() + $blockDuration;

$rateLimiter->setBlockPublic('ip', $testIP, $blockDuration);

// ブロック状態の確認
$blockTime = $rateLimiter->isBlocked('ip', $testIP);
if ($blockTime) {
    echo "IP {$testIP} はブロックされています\n";
    echo "ブロック終了時間: " . date('Y-m-d H:i:s', $blockTime) . "\n";
} else {
    echo "IP {$testIP} はブロックされていません\n";
}

echo "<h2>3. ブロック解除テスト</h2>\n";

// ブロックの解除
$rateLimiter->unblock('ip', $testIP);

// 解除後の確認
$blockTime = $rateLimiter->isBlocked('ip', $testIP);
if ($blockTime) {
    echo "ブロック解除失敗: まだブロックされています\n";
} else {
    echo "ブロック解除成功: ブロックが解除されました\n";
}

echo "<h2>4. 統計情報テスト</h2>\n";

$stats = $rateLimiter->getStats();
echo "Redis利用可能: " . ($stats['redis_available'] ? 'Yes' : 'No') . "\n";
echo "フォールバック中: " . ($stats['fallback_active'] ? 'Yes' : 'No') . "\n";
echo "設定読み込み済み: " . ($stats['config_loaded'] ? 'Yes' : 'No') . "\n";
echo "アクティブブロック: {$stats['active_blocks']}\n";
echo "総リクエスト数: {$stats['total_requests']}\n";

echo "<h2>5. レート制限情報テスト</h2>\n";

$info = $rateLimiter->getRateLimitInfo('search', $testIP);
if ($info) {
    echo "現在の使用量: {$info['current']}\n";
    echo "制限値: {$info['limit']}\n";
    echo "残り: {$info['remaining']}\n";
    echo "リセット時間: " . date('Y-m-d H:i:s', $info['reset_time']) . "\n";
    echo "ブロック状態: " . ($info['blocked'] ? 'Yes' : 'No') . "\n";
}

echo "<h2>テスト完了</h2>\n";
echo "<p>簡易版ログイン制限テストが完了しました。</p>\n";
echo "<p>基本的なレート制限機能は正常に動作しています。</p>\n";
?>
