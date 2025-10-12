<?php
/**
 * ローカル環境デバッグスクリプト
 * ローカル環境の問題を診断・修正
 */

echo "<h1>🔍 ローカル環境デバッグ</h1>";

// 1. PHP環境確認
echo "<h2>📋 PHP環境確認</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";

// 2. 必要な拡張機能確認
echo "<h2>📋 必要な拡張機能確認</h2>";
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ $ext: 利用可能</p>";
    } else {
        echo "<p style='color: red;'>❌ $ext: 利用不可</p>";
    }
}

// 3. ファイル存在確認
echo "<h2>📋 ファイル存在確認</h2>";
$requiredFiles = [
    'index_production.php',
    'config/.env',
    'src/Utils/EnvironmentLoader.php',
    'src/Utils/ConfigManager.php',
    'src/Utils/ProductionConfig.php',
    'src/Utils/ProductionErrorHandler.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color: green;'>✅ $file: 存在</p>";
    } else {
        echo "<p style='color: red;'>❌ $file: 存在しない</p>";
    }
}

// 4. 環境設定確認
echo "<h2>📋 環境設定確認</h2>";

// .envファイルの確認
$envPath = __DIR__ . '/config/.env';
if (file_exists($envPath)) {
    echo "<p style='color: green;'>✅ .env: 存在</p>";
    
    $envContent = file_get_contents($envPath);
    $lines = explode("\n", $envContent);
    echo "<p>最初の10行:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 150px; overflow-y: scroll;'>";
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ .env: 存在しない</p>";
}

// 5. EnvironmentLoaderのテスト
echo "<h2>📋 EnvironmentLoaderのテスト</h2>";

try {
    require_once __DIR__ . '/src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    echo "<p style='color: green;'>✅ EnvironmentLoader: 正常に読み込み</p>";
    echo "<p>読み込まれた設定:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 150px; overflow-y: scroll;'>";
    foreach ($config as $key => $value) {
        if (strpos($key, 'PASSWORD') !== false) {
            echo "$key: 設定済み\n";
        } else {
            echo "$key: $value\n";
        }
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ EnvironmentLoader: エラー - " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 6. データベース接続テスト
echo "<h2>📋 データベース接続テスト</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();
    echo "<p style='color: green;'>✅ データベース接続: 成功</p>";
    
    // テーブルの存在確認
    $tables = ['buildings_table_3', 'individual_architects_3', 'architect_compositions_2'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
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
            echo "<p>- " . basename($logFile) . " (" . filesize($logFile) . " bytes)</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ ログファイルが見つかりません</p>";
    }
} else {
    echo "<p style='color: red;'>❌ logsディレクトリ: 存在しない</p>";
}

// 8. ローカル環境用の設定修正
echo "<h2>🔧 ローカル環境用の設定修正</h2>";

// ローカル環境用の.envファイルを作成
$localEnvContent = '# ローカル環境設定
DB_HOST=localhost
DB_NAME=_shinkenchiku_02
DB_USERNAME=root
DB_PASSWORD=

APP_NAME=PocketNavi
APP_ENV=local
APP_DEBUG=true

APP_KEY=0a53961ea1609c394e8178c61b64c58491d0b59629ec310c60f9ac8b75eb8d4a
SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# ログ設定
LOG_LEVEL=debug
LOG_FILE=logs/application.log

# キャッシュ設定
CACHE_ENABLED=true
CACHE_TTL=300

# セキュリティ設定
CSRF_ENABLED=true
RATE_LIMIT_ENABLED=false

# 多言語設定
DEFAULT_LANGUAGE=ja
SUPPORTED_LANGUAGES=ja,en';

$localEnvPath = __DIR__ . '/config/.env.local';
if (file_put_contents($localEnvPath, $localEnvContent)) {
    echo "<p style='color: green;'>✅ ローカル環境用の.env.localを作成しました</p>";
} else {
    echo "<p style='color: red;'>❌ ローカル環境用の.env.localの作成に失敗しました</p>";
}

// 9. ローカル環境用のindex.phpを作成
echo "<h2>🔧 ローカル環境用のindex.phpの作成</h2>";

$localIndexContent = '<?php
/**
 * ローカル環境用のメインエントリーポイント
 */

// エラー表示を有効にする（ローカル環境のみ）
error_reporting(E_ALL);
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);

// メモリ制限を増やす
ini_set(\'memory_limit\', \'512M\');

// 実行時間制限を増やす
set_time_limit(300);

// アプリケーションの開始
try {
    // 環境設定の読み込み
    require_once __DIR__ . \'/src/Utils/EnvironmentLoader.php\';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    // データベース接続の確認
    require_once __DIR__ . \'/config/database.php\';
    $pdo = getDatabaseConnection();
    
    // 基本的な検索機能のテスト
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM buildings_table_3");
    $stmt->execute();
    $result = $stmt->fetch();
    $buildingCount = $result[\'count\'];
    
    echo "<h1>🎉 ローカル環境テスト成功</h1>";
    echo "<p>データベース接続: 成功</p>";
    echo "<p>建物データ数: " . number_format($buildingCount) . "件</p>";
    echo "<p>環境: " . ($config[\'APP_ENV\'] ?? \'unknown\') . "</p>";
    echo "<p>デバッグ: " . ($config[\'APP_DEBUG\'] ?? \'unknown\') . "</p>";
    
    // 検索フォームの表示
    echo "<h2>🔍 検索フォームテスト</h2>";
    
    // 変数の初期化
    $query = $_GET[\'q\'] ?? \'\';
    $prefectures = $_GET[\'prefectures\'] ?? \'\';
    $completionYears = $_GET[\'completionYears\'] ?? \'\';
    $buildingTypes = $_GET[\'buildingTypes\'] ?? \'\';
    $hasPhotos = isset($_GET[\'photos\']);
    $hasVideos = isset($_GET[\'videos\']);
    $lang = $_GET[\'lang\'] ?? \'ja\';
    
    // 翻訳関数の読み込み
    require_once __DIR__ . \'/src/Utils/Translation.php\';
    
    // 検索フォームの表示
    include __DIR__ . \'/src/Views/includes/search_form.php\';
    
    // 検索結果の表示
    if (!empty($query) || !empty($prefectures) || !empty($completionYears) || !empty($buildingTypes) || $hasPhotos || $hasVideos) {
        echo "<h2>🔍 検索結果</h2>";
        
        // 簡単な検索クエリ
        $sql = "SELECT * FROM buildings_table_3 WHERE 1=1";
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }
        
        if (!empty($prefectures)) {
            $sql .= " AND location LIKE ?";
            $params[] = "%$prefectures%";
        }
        
        if (!empty($completionYears)) {
            $sql .= " AND completionYear = ?";
            $params[] = $completionYears;
        }
        
        $sql .= " LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $buildings = $stmt->fetchAll();
        
        echo "<p>検索結果: " . count($buildings) . "件</p>";
        
        foreach ($buildings as $building) {
            echo "<div style=\'border: 1px solid #ccc; padding: 10px; margin: 10px 0;\'>";
            echo "<h3>" . htmlspecialchars($building[\'title\'] ?? \'\') . "</h3>";
            echo "<p>場所: " . htmlspecialchars($building[\'location\'] ?? \'\') . "</p>";
            echo "<p>完成年: " . htmlspecialchars($building[\'completionYear\'] ?? \'\') . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<h1>❌ エラーが発生しました</h1>";
    echo "<p style=\'color: red;\'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>';

$localIndexPath = __DIR__ . '/index_local.php';
if (file_put_contents($localIndexPath, $localIndexContent)) {
    echo "<p style='color: green;'>✅ ローカル環境用のindex_local.phpを作成しました</p>";
} else {
    echo "<p style='color: red;'>❌ ローカル環境用のindex_local.phpの作成に失敗しました</p>";
}

echo "<h2>🎯 ローカル環境デバッグ完了</h2>";
echo "<p><a href='index_local.php'>← ローカル環境用ページにアクセス</a></p>";
echo "<p><a href='index_production.php'>← 本番環境用ページにアクセス</a></p>";
?>
