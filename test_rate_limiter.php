<?php
/**
 * レート制限機能のテストスクリプト
 * レート制限の動作確認とテストを行う
 */

// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 必要なファイルを読み込み
require_once __DIR__ . '/src/Security/RateLimiter.php';
require_once __DIR__ . '/src/Security/LoginRateLimiter.php';

// エラーハンドリング
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

echo "<h1>レート制限機能テスト</h1>\n";

// テスト用IPアドレス
$testIP = '192.168.1.100';
$testUser = 'testuser';

echo "<h2>1. 基本レート制限テスト</h2>\n";

$rateLimiter = new RateLimiter();

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
    
    usleep(100000); // 0.1秒待機
}

echo "<h3>一般API制限テスト</h3>\n";
$rateLimiter2 = new RateLimiter();
for ($i = 1; $i <= 65; $i++) {
    $allowed = $rateLimiter2->checkLimit('general', $testIP);
    $status = $allowed ? '✅ 許可' : '❌ 拒否';
    echo "試行 {$i}: {$status}\n";
    
    if (!$allowed) {
        break;
    }
    
    usleep(100000); // 0.1秒待機
}

echo "<h2>2. ログイン制限テスト</h2>\n";

$loginRateLimiter = new LoginRateLimiter();

// ログイン試行のテスト
echo "<h3>ログイン試行テスト</h3>\n";
try {
    for ($i = 1; $i <= 7; $i++) {
        try {
            $result = $loginRateLimiter->checkLoginAttempt($testIP, $testUser);
            $status = $result['allowed'] ? '✅ 許可' : '❌ 拒否';
            echo "試行 {$i}: {$status} - {$result['message']}\n";
            
            if (!$result['allowed']) {
                echo "理由: {$result['reason']}\n";
                if (isset($result['retry_after'])) {
                    echo "再試行可能時間: " . date('Y-m-d H:i:s', $result['retry_after']) . "\n";
                }
                break;
            }
            
            // 失敗したログイン試行を記録
            $loginRateLimiter->recordLoginAttempt($testIP, $testUser, false);
            
        } catch (Exception $e) {
            echo "試行 {$i}: ❌ エラー - " . $e->getMessage() . "\n";
            error_log("Login attempt test error: " . $e->getMessage());
            break;
        }
        
        usleep(100000); // 0.1秒待機
    }
} catch (Exception $e) {
    echo "ログイン制限テストでエラーが発生しました: " . $e->getMessage() . "\n";
    error_log("Login rate limiter test error: " . $e->getMessage());
}

echo "<h2>3. 統計情報テスト</h2>\n";

// レート制限統計
$stats = $rateLimiter->getStats();
echo "<h3>レート制限統計</h3>\n";
echo "Redis利用可能: " . ($stats['redis_available'] ? 'Yes' : 'No') . "\n";
echo "フォールバック中: " . ($stats['fallback_active'] ? 'Yes' : 'No') . "\n";
echo "設定読み込み済み: " . ($stats['config_loaded'] ? 'Yes' : 'No') . "\n";
echo "アクティブブロック: {$stats['active_blocks']}\n";
echo "総リクエスト数: {$stats['total_requests']}\n";

// ログイン統計
try {
    $loginStats = $loginRateLimiter->getLoginStats(1);
    echo "<h3>ログイン統計（過去1時間）</h3>\n";
    echo "総試行回数: {$loginStats['total_attempts']}\n";
    echo "失敗回数: {$loginStats['failed_attempts']}\n";
    echo "成功回数: {$loginStats['successful_attempts']}\n";
    echo "ブロックIP数: {$loginStats['blocked_ips']}\n";
    echo "ブロックユーザー数: {$loginStats['blocked_users']}\n";
} catch (Exception $e) {
    echo "<h3>ログイン統計（過去1時間）</h3>\n";
    echo "統計取得エラー: " . $e->getMessage() . "\n";
    error_log("Login stats error: " . $e->getMessage());
}

if (!empty($loginStats['top_attack_ips'])) {
    echo "<h4>攻撃元IP（上位5件）</h4>\n";
    $count = 0;
    foreach ($loginStats['top_attack_ips'] as $ip => $attempts) {
        echo "{$ip}: {$attempts}回\n";
        $count++;
        if ($count >= 5) break;
    }
}

if (!empty($loginStats['top_attack_users'])) {
    echo "<h4>攻撃対象ユーザー（上位5件）</h4>\n";
    $count = 0;
    foreach ($loginStats['top_attack_users'] as $user => $attempts) {
        echo "{$user}: {$attempts}回\n";
        $count++;
        if ($count >= 5) break;
    }
}

echo "<h2>4. レート制限情報テスト</h2>\n";

// レート制限情報の取得
$info = $rateLimiter->getRateLimitInfo('search', $testIP);
if ($info) {
    echo "<h3>検索API制限情報</h3>\n";
    echo "現在の使用量: {$info['current']}\n";
    echo "制限値: {$info['limit']}\n";
    echo "残り: {$info['remaining']}\n";
    echo "リセット時間: " . date('Y-m-d H:i:s', $info['reset_time']) . "\n";
    echo "ブロック状態: " . ($info['blocked'] ? 'Yes' : 'No') . "\n";
    if ($info['block_until']) {
        echo "ブロック終了時間: " . date('Y-m-d H:i:s', $info['block_until']) . "\n";
    }
}

echo "<h2>5. ブロック解除テスト</h2>\n";

// ブロックの解除テスト
$rateLimiter->unblock('search', $testIP);
$rateLimiter->unblock('general', $testIP);
$loginRateLimiter->unblockIP($testIP);
$loginRateLimiter->unblockUsername($testUser);

echo "ブロック解除完了\n";

// 解除後のテスト
$allowed = $rateLimiter->checkLimit('search', $testIP);
echo "解除後の検索API: " . ($allowed ? '✅ 許可' : '❌ 拒否') . "\n";

$result = $loginRateLimiter->checkLoginAttempt($testIP, $testUser);
echo "解除後のログイン: " . ($result['allowed'] ? '✅ 許可' : '❌ 拒否') . "\n";

echo "<h2>テスト完了</h2>\n";
echo "<p>レート制限機能のテストが完了しました。</p>\n";
echo "<p><a href='admin/rate_limit_management.php'>管理画面</a>で詳細を確認できます。</p>\n";
?>
