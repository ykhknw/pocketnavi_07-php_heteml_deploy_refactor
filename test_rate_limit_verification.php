<?php
/**
 * レート制限値の動作確認テスト
 * 制限値が正しく適用されているかを確認
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>レート制限値の動作確認テスト</h1>\n";

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Security/RateLimiter.php';

echo "<h2>1. 設定値の確認</h2>\n";

$rateLimiter = new RateLimiter();
$testIP = '192.168.1.200';

// 設定ファイルの確認
$configFile = __DIR__ . '/config/rate_limit_config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    echo "✅ 設定ファイル読み込み成功\n";
    echo "検索API制限: {$config['api']['search']['limit']}回/{$config['api']['search']['window']}秒\n";
    echo "一般API制限: {$config['api']['general']['limit']}回/{$config['api']['general']['window']}秒\n";
} else {
    echo "❌ 設定ファイルが見つかりません\n";
}

echo "<h2>2. 制限値の詳細テスト</h2>\n";

// 検索API制限の詳細テスト
echo "<h3>検索API制限の詳細テスト</h3>\n";
echo "制限値: 30回/60秒\n";
echo "テスト開始: " . date('Y-m-d H:i:s') . "\n";

$blockedAt = null;
for ($i = 1; $i <= 35; $i++) {
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

if ($blockedAt) {
    echo "✅ 制限が正しく動作しました（{$blockedAt}回目で制限発動）\n";
} else {
    echo "⚠️ 制限が発動しませんでした（設定を確認してください）\n";
}

echo "<h2>3. レート制限情報の詳細確認</h2>\n";

$info = $rateLimiter->getRateLimitInfo('search', $testIP);
if ($info) {
    echo "現在の使用量: {$info['current']}\n";
    echo "制限値: {$info['limit']}\n";
    echo "残り: {$info['remaining']}\n";
    echo "リセット時間: " . date('Y-m-d H:i:s', $info['reset_time']) . "\n";
    echo "ブロック状態: " . ($info['blocked'] ? 'Yes' : 'No') . "\n";
    
    if ($info['current'] >= $info['limit']) {
        echo "✅ 制限値に達しています\n";
    } else {
        echo "⚠️ 制限値に達していません（現在: {$info['current']}, 制限: {$info['limit']}）\n";
    }
}

echo "<h2>4. バースト制限のテスト</h2>\n";

$testIP2 = '192.168.1.201';
echo "バースト制限テスト（10回/10秒）\n";

$burstBlockedAt = null;
for ($i = 1; $i <= 15; $i++) {
    $allowed = $rateLimiter->checkLimit('search', $testIP2);
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

echo "<h2>5. 統計情報の詳細確認</h2>\n";

$stats = $rateLimiter->getStats();
echo "Redis利用可能: " . ($stats['redis_available'] ? 'Yes' : 'No') . "\n";
echo "フォールバック中: " . ($stats['fallback_active'] ? 'Yes' : 'No') . "\n";
echo "設定読み込み済み: " . ($stats['config_loaded'] ? 'Yes' : 'No') . "\n";
echo "アクティブブロック: {$stats['active_blocks']}\n";
echo "総リクエスト数: {$stats['total_requests']}\n";

echo "<h2>テスト完了</h2>\n";
echo "<p>レート制限値の動作確認テストが完了しました。</p>\n";

if ($blockedAt) {
    echo "<p>✅ レート制限は正常に動作しています。</p>\n";
} else {
    echo "<p>⚠️ レート制限の設定を確認してください。</p>\n";
}
?>
