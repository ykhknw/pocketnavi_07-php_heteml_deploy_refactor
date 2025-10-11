<?php
/**
 * セキュリティシステムのデバッグテスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== セキュリティシステムデバッグテスト ===\n\n";

// 1. ファイル存在確認
echo "1. ファイル存在確認\n";
$files = [
    'src/Security/SecurityManager.php',
    'src/Security/LogMonitor.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ {$file}\n";
    } else {
        echo "   ❌ {$file} - ファイルが見つかりません\n";
    }
}

echo "\n";

// 2. クラス読み込みテスト
echo "2. クラス読み込みテスト\n";
try {
    require_once 'src/Security/SecurityManager.php';
    echo "   ✅ SecurityManager クラス読み込み成功\n";
} catch (Exception $e) {
    echo "   ❌ SecurityManager エラー: " . $e->getMessage() . "\n";
}

try {
    require_once 'src/Security/LogMonitor.php';
    echo "   ✅ LogMonitor クラス読み込み成功\n";
} catch (Exception $e) {
    echo "   ❌ LogMonitor エラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. インスタンス作成テスト
echo "3. インスタンス作成テスト\n";
try {
    $securityManager = SecurityManager::getInstance();
    echo "   ✅ SecurityManager インスタンス作成成功\n";
} catch (Exception $e) {
    echo "   ❌ SecurityManager インスタンス作成エラー: " . $e->getMessage() . "\n";
}

try {
    $logMonitor = LogMonitor::getInstance();
    echo "   ✅ LogMonitor インスタンス作成成功\n";
} catch (Exception $e) {
    echo "   ❌ LogMonitor インスタンス作成エラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. 個別機能テスト
echo "4. 個別機能テスト\n";

// CSRFトークンテスト
try {
    if (isset($securityManager)) {
        $csrfToken = $securityManager->generateCsrfToken();
        if ($csrfToken) {
            echo "   ✅ CSRFトークン生成成功: " . substr($csrfToken, 0, 16) . "...\n";
        } else {
            echo "   ❌ CSRFトークン生成失敗\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ CSRFトークン生成エラー: " . $e->getMessage() . "\n";
}

// セキュリティログテスト
try {
    if (isset($securityManager)) {
        $securityManager->logSecurityEvent('DEBUG_TEST', 'デバッグテスト実行');
        echo "   ✅ セキュリティログ記録成功\n";
    }
} catch (Exception $e) {
    echo "   ❌ セキュリティログ記録エラー: " . $e->getMessage() . "\n";
}

// ログ監視テスト
try {
    if (isset($logMonitor)) {
        $analysis = $logMonitor->analyzeSecurityEvents(3600);
        echo "   ✅ ログ監視システム正常動作\n";
        echo "   📋 総イベント数: " . $analysis['total_events'] . "\n";
        echo "   📋 リスクレベル: " . $analysis['risk_level'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ ログ監視システムエラー: " . $e->getMessage() . "\n";
}

echo "\n";

echo "=== デバッグテスト完了 ===\n";

