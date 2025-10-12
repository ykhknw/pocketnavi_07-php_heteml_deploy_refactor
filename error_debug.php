<?php
/**
 * エラーデバッグスクリプト
 * システムエラーの詳細を確認
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 エラーデバッグ</h1>";

// 1. 基本的なPHP環境確認
echo "<h2>📋 PHP環境確認</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";

// 2. ファイル存在確認
echo "<h2>📋 ファイル存在確認</h2>";
$requiredFiles = [
    'index_production.php',
    'config/database_unified.php',
    'src/Utils/EnvironmentLoader.php',
    'src/Utils/ProductionConfig.php',
    'src/Utils/ProductionErrorHandler.php',
    '.env'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color: green;'>✅ $file: 存在</p>";
    } else {
        echo "<p style='color: red;'>❌ $file: 存在しない</p>";
    }
}

// 3. EnvironmentLoaderのテスト
echo "<h2>📋 EnvironmentLoaderのテスト</h2>";

try {
    require_once __DIR__ . '/src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    if (is_array($config) && !empty($config)) {
        echo "<p style='color: green;'>✅ EnvironmentLoader: 正常に読み込み</p>";
        echo "<p>読み込まれた設定数: " . count($config) . "</p>";
        
        // 主要な設定を表示
        $importantKeys = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'APP_ENV', 'APP_DEBUG'];
        foreach ($importantKeys as $key) {
            if (isset($config[$key])) {
                echo "<p>$key: " . htmlspecialchars($config[$key]) . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ EnvironmentLoader: 設定が読み込めません</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ EnvironmentLoader: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// 4. ProductionConfigのテスト
echo "<h2>📋 ProductionConfigのテスト</h2>";

try {
    require_once __DIR__ . '/src/Utils/ProductionConfig.php';
    $productionConfig = ProductionConfig::getInstance();
    echo "<p style='color: green;'>✅ ProductionConfig: 正常に読み込み</p>";
    
    $dbConfig = $productionConfig->getDatabaseConfig();
    echo "<p>データベース設定:</p>";
    echo "<pre>" . print_r($dbConfig, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ProductionConfig: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// 5. database_unified.phpのテスト
echo "<h2>📋 database_unified.phpのテスト</h2>";

try {
    require_once __DIR__ . '/config/database_unified.php';
    echo "<p style='color: green;'>✅ database_unified.php: 正常に読み込み</p>";
    
    $dbConfig = getDatabaseConfig();
    echo "<p>データベース設定:</p>";
    echo "<pre>" . print_r($dbConfig, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ database_unified.php: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

// 6. データベース接続のテスト
echo "<h2>📋 データベース接続のテスト</h2>";

try {
    $config = getDatabaseConfig();
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    echo "<p style='color: green;'>✅ データベース接続: 成功</p>";
    
    // テーブルの存在確認
    $tables = ['buildings_table_3', 'individual_architects_3', 'architect_compositions_2'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt->bindValue(':table_name', $table);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ テーブル $table: 存在</p>";
        } else {
            echo "<p style='color: red;'>❌ テーブル $table: 存在しない</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ データベース接続: 失敗 - " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 7. エラーログの確認
echo "<h2>📋 エラーログの確認</h2>";

$logPath = __DIR__ . '/logs';
if (is_dir($logPath)) {
    echo "<p style='color: green;'>✅ logsディレクトリ: 存在</p>";
    
    $logFiles = glob($logPath . '/*.log');
    if (!empty($logFiles)) {
        echo "<p>ログファイル:</p>";
        foreach ($logFiles as $logFile) {
            $size = filesize($logFile);
            $modified = date('Y-m-d H:i:s', filemtime($logFile));
            echo "<p>- " . basename($logFile) . " ($size bytes, 更新: $modified)</p>";
            
            // 最新のエラーログを表示
            if (strpos(basename($logFile), 'error') !== false || strpos(basename($logFile), 'php_errors') !== false) {
                $content = file_get_contents($logFile);
                $lines = explode("\n", $content);
                $recentLines = array_slice($lines, -10); // 最後の10行
                echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 200px; overflow-y: scroll;'>";
                foreach ($recentLines as $line) {
                    if (!empty(trim($line))) {
                        echo htmlspecialchars($line) . "\n";
                    }
                }
                echo "</pre>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ ログファイルが見つかりません</p>";
    }
} else {
    echo "<p style='color: red;'>❌ logsディレクトリ: 存在しない</p>";
}

// 8. 簡単なindex_production.phpのテスト
echo "<h2>📋 簡単なindex_production.phpのテスト</h2>";

try {
    // 基本的な設定のみでテスト
    require_once __DIR__ . '/src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    // データベース定数の定義
    define('DB_HOST', $config['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $config['DB_NAME'] ?? '_shinkenchiku_02');
    define('DB_USERNAME', $config['DB_USERNAME'] ?? 'root');
    define('DB_PASS', $config['DB_PASSWORD'] ?? '');
    
    // アプリケーション設定の適用
    define('APP_NAME', $config['APP_NAME'] ?? 'PocketNavi');
    define('APP_ENV', $config['APP_ENV'] ?? 'local');
    define('APP_DEBUG', $config['APP_DEBUG'] ?? 'true');
    
    echo "<p style='color: green;'>✅ 基本的な設定: 成功</p>";
    echo "<p>APP_NAME: " . APP_NAME . "</p>";
    echo "<p>APP_ENV: " . APP_ENV . "</p>";
    echo "<p>APP_DEBUG: " . APP_DEBUG . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 基本的な設定: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

echo "<h2>🎯 エラーデバッグ完了</h2>";
echo "<p><a href='index_local.php'>← ローカル環境用ページにアクセス</a></p>";
echo "<p><a href='index_production.php'>← 本番環境用ページにアクセス</a></p>";
?>
