<?php
/**
 * 本番環境デバッグスクリプト
 * エラーの原因を特定するための診断ツール
 */

// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 本番環境デバッグ診断</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// 1. PHP環境確認
echo "<h2>1. PHP環境確認</h2>";
echo "<p class='success'>✅ PHP Version: " . phpversion() . "</p>";
echo "<p class='success'>✅ Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p class='success'>✅ Max Execution Time: " . ini_get('max_execution_time') . "</p>";

// 2. 必要な拡張機能確認
echo "<h2>2. 必要な拡張機能確認</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ $ext: 利用可能</p>";
    } else {
        echo "<p class='error'>❌ $ext: 利用不可</p>";
    }
}

// 3. ファイル存在確認
echo "<h2>3. ファイル存在確認</h2>";
$required_files = [
    'index_production.php',
    'config/env.heteml',
    'src/Utils/EnvironmentLoader.php',
    'src/Utils/ConfigManager.php',
    'src/Utils/ProductionConfig.php',
    'src/Utils/ProductionErrorHandler.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $file: 存在</p>";
    } else {
        echo "<p class='error'>❌ $file: 存在しない</p>";
    }
}

// 4. ディレクトリ権限確認
echo "<h2>4. ディレクトリ権限確認</h2>";
$directories = ['logs', 'cache', 'config'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p class='success'>✅ $dir: 書き込み可能</p>";
        } else {
            echo "<p class='error'>❌ $dir: 書き込み不可</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ $dir: ディレクトリが存在しない</p>";
    }
}

// 5. 環境設定読み込みテスト
echo "<h2>5. 環境設定読み込みテスト</h2>";
try {
    if (file_exists('config/env.heteml')) {
        echo "<p class='success'>✅ env.heteml: 存在</p>";
        
        // 環境設定を読み込み
        if (file_exists('src/Utils/EnvironmentLoader.php')) {
            require_once 'src/Utils/EnvironmentLoader.php';
            $env = new EnvironmentLoader();
            echo "<p class='success'>✅ EnvironmentLoader: 読み込み成功</p>";
        } else {
            echo "<p class='error'>❌ EnvironmentLoader: ファイルが見つからない</p>";
        }
    } else {
        echo "<p class='error'>❌ env.heteml: 存在しない</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ 環境設定読み込みエラー: " . $e->getMessage() . "</p>";
}

// 6. データベース接続テスト
echo "<h2>6. データベース接続テスト</h2>";
try {
    // 環境設定からデータベース情報を取得
    if (file_exists('config/env.heteml')) {
        $env_content = file_get_contents('config/env.heteml');
        $lines = explode("\n", $env_content);
        $db_config = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                list($key, $value) = explode('=', $line, 2);
                $db_config[trim($key)] = trim($value);
            }
        }
        
        if (isset($db_config['DB_HOST']) && isset($db_config['DB_NAME']) && 
            isset($db_config['DB_USERNAME']) && isset($db_config['DB_PASSWORD'])) {
            
            $dsn = "mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_config['DB_USERNAME'], $db_config['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            echo "<p class='success'>✅ データベース接続: 成功</p>";
            echo "<p class='success'>✅ ホスト: {$db_config['DB_HOST']}</p>";
            echo "<p class='success'>✅ データベース: {$db_config['DB_NAME']}</p>";
            
            // テーブル存在確認
            $tables = ['buildings_table_3', 'individual_architects_3', 'architect_compositions_2'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "<p class='success'>✅ テーブル $table: 存在</p>";
                } else {
                    echo "<p class='error'>❌ テーブル $table: 存在しない</p>";
                }
            }
            
        } else {
            echo "<p class='error'>❌ データベース設定が不完全</p>";
        }
    } else {
        echo "<p class='error'>❌ env.heteml が見つからない</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ データベース接続エラー: " . $e->getMessage() . "</p>";
}

// 7. メモリ使用量確認
echo "<h2>7. メモリ使用量確認</h2>";
echo "<p class='success'>✅ 現在のメモリ使用量: " . number_format(memory_get_usage(true) / 1024 / 1024, 2) . " MB</p>";
echo "<p class='success'>✅ ピークメモリ使用量: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB</p>";

echo "<h2>🎯 診断完了</h2>";
echo "<p>上記の結果を確認して、❌ マークの項目を修正してください。</p>";
echo "<p><a href='index_production.php'>← メインページに戻る</a></p>";
?>
