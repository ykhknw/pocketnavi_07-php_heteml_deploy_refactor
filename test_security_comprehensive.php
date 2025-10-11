<?php
/**
 * Phase 5.3: 包括的セキュリティテスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Phase 5.3: 包括的セキュリティテスト ===\n\n";

$securityResults = [
    'headers' => false,
    'csrf' => false,
    'authentication' => false,
    'input_validation' => false,
    'sql_injection' => false,
    'xss_protection' => false,
    'session_security' => false,
    'log_monitoring' => false
];

$totalTests = 0;
$passedTests = 0;

// 1. セキュリティヘッダーテスト
echo "1. セキュリティヘッダーテスト\n";
try {
    require_once 'src/Security/SecurityManager.php';
    $securityManager = SecurityManager::getInstance();
    
    // セキュリティシステムの初期化テスト
    $securityManager->initialize();
    
    // ヘッダーの確認
    $headers = headers_list();
    $requiredHeaders = [
        'X-Content-Type-Options',
        'X-Frame-Options', 
        'X-XSS-Protection',
        'Referrer-Policy',
        'Content-Security-Policy'
    ];
    
    $foundHeaders = 0;
    foreach ($requiredHeaders as $header) {
        foreach ($headers as $h) {
            if (strpos($h, $header) !== false) {
                $foundHeaders++;
                break;
            }
        }
    }
    
    if ($foundHeaders >= 3) {
        echo "   ✅ セキュリティヘッダー設定成功（{$foundHeaders}/5個）\n";
        $securityResults['headers'] = true;
        $passedTests++;
    } else {
        echo "   ❌ セキュリティヘッダー設定不十分（{$foundHeaders}/5個）\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ セキュリティヘッダーテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 2. CSRF保護テスト
echo "2. CSRF保護テスト\n";
try {
    // CSRFトークン生成テスト
    $token1 = $securityManager->generateCsrfToken();
    $token2 = $securityManager->generateCsrfToken();
    
    if ($token1 && $token2 && $token1 !== $token2) {
        echo "   ✅ CSRFトークン生成成功（一意性確認済み）\n";
        
        // CSRFトークン検証テスト
        $isValid = $securityManager->validateCsrfToken($token1);
        if ($isValid) {
            echo "   ✅ CSRFトークン検証成功\n";
            $securityResults['csrf'] = true;
            $passedTests++;
        } else {
            echo "   ❌ CSRFトークン検証失敗\n";
        }
    } else {
        echo "   ❌ CSRFトークン生成失敗\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ CSRF保護テストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 3. 認証システムテスト
echo "3. 認証システムテスト\n";
try {
    require_once 'src/Security/AuthenticationManager.php';
    $authManager = AuthenticationManager::getInstance();
    
    // パスワードハッシュ化テスト
    $password = 'test_password_123';
    $hashedPassword = $authManager->hashPassword($password);
    
    if ($hashedPassword && $hashedPassword !== $password) {
        echo "   ✅ パスワードハッシュ化成功\n";
        
        // パスワード検証テスト
        $isValid = $authManager->verifyPassword($password, $hashedPassword);
        if ($isValid) {
            echo "   ✅ パスワード検証成功\n";
            
            // セッション管理テスト
            $sessionId = $authManager->createSession('test_user');
            if ($sessionId) {
                echo "   ✅ セッション作成成功\n";
                $securityResults['authentication'] = true;
                $passedTests++;
            } else {
                echo "   ❌ セッション作成失敗\n";
            }
        } else {
            echo "   ❌ パスワード検証失敗\n";
        }
    } else {
        echo "   ❌ パスワードハッシュ化失敗\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ 認証システムテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 4. 入力検証テスト
echo "4. 入力検証テスト\n";
try {
    require_once 'src/Utils/InputValidator.php';
    $validator = new InputValidator();
    
    // SQLインジェクション対策テスト
    $maliciousInputs = [
        "'; DROP TABLE users; --",
        "' OR '1'='1",
        "admin'--",
        "1' UNION SELECT * FROM users--"
    ];
    
    $sqlInjectionBlocked = 0;
    foreach ($maliciousInputs as $input) {
        $sanitized = $validator->sanitizeInput($input);
        if ($sanitized !== $input && strpos($sanitized, 'DROP') === false && strpos($sanitized, 'UNION') === false) {
            $sqlInjectionBlocked++;
        }
    }
    
    if ($sqlInjectionBlocked >= 3) {
        echo "   ✅ SQLインジェクション対策成功（{$sqlInjectionBlocked}/4件ブロック）\n";
        $securityResults['sql_injection'] = true;
    } else {
        echo "   ❌ SQLインジェクション対策不十分（{$sqlInjectionBlocked}/4件ブロック）\n";
    }
    
    // XSS対策テスト
    $xssInputs = [
        "<script>alert('XSS')</script>",
        "<img src=x onerror=alert('XSS')>",
        "javascript:alert('XSS')",
        "<iframe src='javascript:alert(\"XSS\")'></iframe>"
    ];
    
    $xssBlocked = 0;
    foreach ($xssInputs as $input) {
        $sanitized = $validator->sanitizeInput($input);
        if ($sanitized !== $input && strpos($sanitized, '<script>') === false && strpos($sanitized, 'javascript:') === false) {
            $xssBlocked++;
        }
    }
    
    if ($xssBlocked >= 3) {
        echo "   ✅ XSS対策成功（{$xssBlocked}/4件ブロック）\n";
        $securityResults['xss_protection'] = true;
    } else {
        echo "   ❌ XSS対策不十分（{$xssBlocked}/4件ブロック）\n";
    }
    
    if ($securityResults['sql_injection'] && $securityResults['xss_protection']) {
        $securityResults['input_validation'] = true;
        $passedTests++;
    }
    
} catch (Exception $e) {
    echo "   ❌ 入力検証テストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 5. セッションセキュリティテスト
echo "5. セッションセキュリティテスト\n";
try {
    // セッション設定の確認
    $sessionSettings = [
        'session.cookie_httponly' => ini_get('session.cookie_httponly'),
        'session.cookie_secure' => ini_get('session.cookie_secure'),
        'session.use_strict_mode' => ini_get('session.use_strict_mode'),
        'session.cookie_samesite' => ini_get('session.cookie_samesite')
    ];
    
    $secureSettings = 0;
    foreach ($sessionSettings as $setting => $value) {
        if ($value) {
            $secureSettings++;
        }
    }
    
    if ($secureSettings >= 2) {
        echo "   ✅ セッションセキュリティ設定良好（{$secureSettings}/4項目）\n";
        $securityResults['session_security'] = true;
        $passedTests++;
    } else {
        echo "   ⚠️ セッションセキュリティ設定改善の余地あり（{$secureSettings}/4項目）\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ セッションセキュリティテストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 6. ログ監視テスト
echo "6. ログ監視テスト\n";
try {
    require_once 'src/Security/LogMonitor.php';
    $logMonitor = LogMonitor::getInstance();
    
    // セキュリティイベントの記録テスト
    $securityManager->logSecurityEvent('SECURITY_TEST', 'セキュリティテスト実行');
    $securityManager->logSecurityEvent('LOGIN_ATTEMPT', 'ログイン試行テスト');
    $securityManager->logSecurityEvent('SUSPICIOUS_ACTIVITY', '疑わしい活動の検出テスト');
    
    echo "   ✅ セキュリティイベント記録成功\n";
    
    // ログ分析テスト
    $analysis = $logMonitor->analyzeSecurityEvents(3600);
    if ($analysis && isset($analysis['total_events'])) {
        echo "   ✅ ログ分析成功（総イベント数: {$analysis['total_events']}）\n";
        echo "   📋 リスクレベル: {$analysis['risk_level']}\n";
        
        $securityResults['log_monitoring'] = true;
        $passedTests++;
    } else {
        echo "   ❌ ログ分析失敗\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ ログ監視テストエラー: " . $e->getMessage() . "\n";
}
$totalTests++;

echo "\n";

// 7. セキュリティテスト結果サマリー
echo "7. セキュリティテスト結果サマリー\n";
echo "   📊 総テスト数: {$totalTests}\n";
echo "   ✅ 成功: {$passedTests}\n";
echo "   ❌ 失敗: " . ($totalTests - $passedTests) . "\n";
echo "   📈 成功率: " . round(($passedTests / $totalTests) * 100, 1) . "%\n";

echo "\n";

// 8. 詳細結果
echo "8. 詳細セキュリティテスト結果\n";
$testNames = [
    'headers' => 'セキュリティヘッダー',
    'csrf' => 'CSRF保護',
    'authentication' => '認証システム',
    'input_validation' => '入力検証',
    'sql_injection' => 'SQLインジェクション対策',
    'xss_protection' => 'XSS対策',
    'session_security' => 'セッションセキュリティ',
    'log_monitoring' => 'ログ監視'
];

foreach ($securityResults as $test => $result) {
    $status = $result ? '✅ 成功' : '❌ 失敗';
    echo "   {$status} {$testNames[$test]}\n";
}

echo "\n";

// 9. セキュリティ評価
echo "9. セキュリティ評価\n";
if ($passedTests === $totalTests) {
    echo "   🏆 すべてのセキュリティテストが成功しました！\n";
    echo "   🛡️ アプリケーションは高いセキュリティレベルを維持しています。\n";
    echo "   📋 次のステップ: Phase 5.4 本番環境への移行準備\n";
} elseif ($passedTests >= $totalTests * 0.8) {
    echo "   ✅ セキュリティレベルは良好です。\n";
    echo "   ⚠️ 一部の項目で改善の余地があります。\n";
    echo "   📋 失敗したテストを修正してから次のステップに進むことを推奨します。\n";
} else {
    echo "   ⚠️ セキュリティレベルに改善が必要です。\n";
    echo "   🔧 失敗したテストを修正してから次のステップに進んでください。\n";
}

echo "\n";

// 10. 推奨事項
echo "10. セキュリティ推奨事項\n";
if (!$securityResults['headers']) {
    echo "   🔧 セキュリティヘッダーの設定を確認してください\n";
}
if (!$securityResults['csrf']) {
    echo "   🔧 CSRF保護の実装を確認してください\n";
}
if (!$securityResults['authentication']) {
    echo "   🔧 認証システムの実装を確認してください\n";
}
if (!$securityResults['input_validation']) {
    echo "   🔧 入力検証システムの実装を確認してください\n";
}
if (!$securityResults['session_security']) {
    echo "   🔧 セッションセキュリティ設定を確認してください\n";
}
if (!$securityResults['log_monitoring']) {
    echo "   🔧 ログ監視システムの実装を確認してください\n";
}

echo "\n";

echo "=== Phase 5.3: セキュリティテスト完了 ===\n";
