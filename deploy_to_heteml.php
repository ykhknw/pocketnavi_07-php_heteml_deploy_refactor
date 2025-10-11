<?php
/**
 * HETEML移行用ファイルリスト生成スクリプト
 */

echo "=== HETEML移行用ファイルリスト ===\n\n";

// 移行が必要なファイル・ディレクトリ
$requiredFiles = [
    // アプリケーションコア
    'index.php',
    'index_production.php',
    'router.php',
    'about.php',
    'contact.php',
    'sitemap.php',
    'generate-sitemap.php',
    'robots.txt',
    'sitemap.xml',
    
    // 設定ファイル
    'config/',
    
    // ソースコード
    'src/',
    
    // 静的ファイル
    'assets/',
    'pictures/',
    'screen_shots_3_webp/',
    
    // API
    'api/',
    
    // 管理画面
    'admin/',
    
    // ルート設定
    'routes/',
];

// 移行が不要なファイル・ディレクトリ
$excludedFiles = [
    // テストファイル
    'test_*.php',
    'test-*.php',
    
    // 開発用スクリプト
    'scripts/',
    
    // ドキュメント
    'docs/',
    
    // データベーススクリプト
    'database/',
    
    // 一時ファイル
    'check_*.php',
    'simple_test.php',
    'error_test.php',
    'db_test.php',
    'deploy_to_heteml.php',
    
    // キャッシュ（本番環境で再生成）
    'cache/',
    
    // ログ（本番環境で再生成）
    'logs/',
];

echo "📁 移行が必要なファイル・ディレクトリ:\n";
foreach ($requiredFiles as $file) {
    echo "   ✅ {$file}\n";
}

echo "\n❌ 移行が不要なファイル・ディレクトリ:\n";
foreach ($excludedFiles as $file) {
    echo "   ❌ {$file}\n";
}

echo "\n📋 HETEML移行手順:\n";
echo "1. 必要なファイルのみをアップロード\n";
echo "2. 本番環境設定の適用\n";
echo "3. データベースの設定\n";
echo "4. 権限の設定\n";
echo "5. 動作確認\n";

echo "\n🔧 本番環境での設定:\n";
echo "- index_production.php を index.php にリネーム\n";
echo "- config/env.production を .env にコピー\n";
echo "- データベース接続情報の設定\n";
echo "- ログ・キャッシュディレクトリの権限設定\n";

echo "\n=== HETEML移行準備完了 ===\n";
?>
