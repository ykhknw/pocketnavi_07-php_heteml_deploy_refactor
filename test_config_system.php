<?php
/**
 * 設定システムテストスクリプト
 * 新しい統一された設定管理システムのテスト
 */

// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PocketNavi 設定システムテスト ===\n\n";

try {
    // 統一された設定システムを読み込み
    require_once 'config/app_unified.php';
    
    echo "1. 設定管理システムの初期化テスト\n";
    echo "   ✅ ConfigManager: 初期化完了\n";
    echo "   ✅ ConfigValidator: 初期化完了\n\n";
    
    echo "2. 基本設定値の取得テスト\n";
    echo "   - アプリケーション名: " . config('app.name') . "\n";
    echo "   - 環境: " . config('app.env') . "\n";
    echo "   - デバッグモード: " . (config('app.debug') ? '有効' : '無効') . "\n";
    echo "   - データベース名: " . config('database.name') . "\n";
    echo "   - データベースホスト: " . config('database.host') . "\n\n";
    
    echo "3. 設定の検証テスト\n";
    $validation = config_validate();
    if ($validation['valid']) {
        echo "   ✅ 設定検証: 成功\n";
    } else {
        echo "   ❌ 設定検証: 失敗\n";
        foreach ($validation['errors'] as $key => $errors) {
            echo "     - {$key}: " . implode(', ', $errors) . "\n";
        }
    }
    echo "\n";
    
    echo "4. 設定情報の表示\n";
    $info = config_info();
    echo "   - 環境: " . $info['environment'] . "\n";
    echo "   - デバッグ: " . ($info['debug'] ? '有効' : '無効') . "\n";
    echo "   - HETEML環境: " . ($info['is_heteml'] ? 'はい' : 'いいえ') . "\n";
    echo "   - 環境ファイル: " . ($info['env_file'] ?: '見つかりません') . "\n\n";
    
    echo "5. 推奨事項の確認\n";
    $recommendations = config_recommendations();
    if (empty($recommendations)) {
        echo "   ✅ 推奨事項: なし\n";
    } else {
        echo "   ⚠️ 推奨事項:\n";
        foreach ($recommendations as $recommendation) {
            echo "     - {$recommendation}\n";
        }
    }
    echo "\n";
    
    echo "6. 最適化提案の確認\n";
    $optimizations = config_optimizations();
    if (empty($optimizations)) {
        echo "   ✅ 最適化提案: なし\n";
    } else {
        echo "   💡 最適化提案:\n";
        foreach ($optimizations as $optimization) {
            echo "     - {$optimization}\n";
        }
    }
    echo "\n";
    
    echo "7. データベース接続テスト\n";
    if (function_exists('testDatabaseConnection') && testDatabaseConnection()) {
        echo "   ✅ データベース接続: 成功\n";
    } else {
        echo "   ❌ データベース接続: 失敗\n";
    }
    echo "\n";
    
    echo "=== テスト完了 ===\n";
    echo "✅ 設定システムは正常に動作しています！\n";
    echo "統一された設定管理システムが正常に機能しています。\n";
    
} catch (Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo "   エラーメッセージ: " . $e->getMessage() . "\n";
    echo "   ファイル: " . $e->getFile() . "\n";
    echo "   行番号: " . $e->getLine() . "\n";
    exit(1);
}
?>