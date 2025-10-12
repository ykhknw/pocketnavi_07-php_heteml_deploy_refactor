<?php
/**
 * PocketNavi PHP版 - 修正版リファクタリング
 * データベース接続とエラーハンドリングの問題を修正
 */

// エラーレポートの設定（本番環境用）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ============================================================================
// 環境変数と.envファイルの読み込み
// ============================================================================

// .envファイルの読み込み（簡易版）
function loadEnvFile($filePath = '.env') {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // コメント行をスキップ
        }
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // クォートを削除
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
    return true;
}

// .envファイルの読み込みを試行
$envLoaded = loadEnvFile('.env') || loadEnvFile('../.env') || loadEnvFile('../../.env');

if ($envLoaded) {
    error_log("Environment variables loaded from .env file");
} else {
    error_log("No .env file found, using system environment variables");
}

// ============================================================================
// データベース接続の設定（修正版）
// ============================================================================

// 環境変数からデータベース設定を取得
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? 'pocketnavi',
    'username' => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '',
    'charset' => $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?? 'utf8' // utf8mb4からutf8に変更
];

// データベース接続関数（修正版）
function getDB() {
    global $dbConfig;
    
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // 文字セットをutf8に変更し、より安全な接続設定
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ];
            
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
            error_log("Database connected successfully to: {$dbConfig['host']}:{$dbConfig['port']}/{$dbConfig['database']}");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("データベース接続に失敗しました: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// ============================================================================
// 簡易エラーハンドリング（ErrorHandlerの代わり）
// ============================================================================

class SimpleErrorHandler {
    public static function log($message, $level = 'error', $context = []) {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] [$level] $message";
        if (!empty($context)) {
            $logMessage .= " Context: " . json_encode($context);
        }
        error_log($logMessage);
    }
}

// 既存のfunctions.phpを読み込み
try {
    require_once 'src/Views/includes/functions.php';
} catch (Exception $e) {
    error_log("Functions.php loading error: " . $e->getMessage());
    // functions.phpが読み込めない場合は、基本的な検索関数を定義
    function getPopularSearches($lang = 'ja') {
        return [];
    }
    
    function searchBuildingsWithMultipleConditions($query, $completionYears, $prefectures, $buildingTypes, $hasPhotos, $hasVideos, $page, $lang, $limit) {
        return [
            'buildings' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => $page
        ];
    }
    
    function getBuildingBySlug($slug, $lang) {
        return null;
    }
    
    function searchBuildingsByArchitectSlug($slug, $page, $lang, $limit, $completionYears, $prefectures, $query) {
        return [
            'buildings' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => $page,
            'architectInfo' => null
        ];
    }
    
    function searchBuildingsByLocation($lat, $lng, $radius, $page, $hasPhotos, $hasVideos, $lang, $limit) {
        return [
            'buildings' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => $page
        ];
    }
}

// セキュリティヘッダーの設定
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// ============================================================================
// 修正版リファクタリングメイン処理クラス
// ============================================================================

class PocketNaviFixedApp {
    
    private $lang;
    private $searchParams;
    private $searchResult;
    private $popularSearches;
    private $debugMode;
    
    public function __construct() {
        $this->debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
        $this->initializeSearchParameters();
        $this->performSearch();
        $this->getPopularSearches();
    }
    
    /**
     * 検索パラメータの初期化
     */
    private function initializeSearchParameters() {
        $this->lang = $this->validateLanguage($_GET['lang'] ?? 'ja');
        
        $this->searchParams = [
            'query' => $this->validateSearchQuery($_GET['q'] ?? ''),
            'page' => $this->validatePage($_GET['page'] ?? 1),
            'hasPhotos' => isset($_GET['photos']) && $_GET['photos'] !== '',
            'hasVideos' => isset($_GET['videos']) && $_GET['videos'] !== '',
            'userLat' => $this->validateFloat($_GET['lat'] ?? null),
            'userLng' => $this->validateFloat($_GET['lng'] ?? null),
            'radiusKm' => $this->validateInteger($_GET['radius'] ?? 5, 1, 100),
            'buildingSlug' => $this->validateSlug($_GET['building_slug'] ?? ''),
            'prefectures' => $this->validateString($_GET['prefectures'] ?? '', 50),
            'architectsSlug' => $this->validateSlug($_GET['architects_slug'] ?? ''),
            'completionYears' => $this->validateString($_GET['completionYears'] ?? '', 50)
        ];
    }
    
    /**
     * バリデーション関数
     */
    private function validateLanguage($lang) {
        return in_array($lang, ['ja', 'en']) ? $lang : 'ja';
    }
    
    private function validateSearchQuery($query) {
        return htmlspecialchars(trim($query), ENT_QUOTES, 'UTF-8');
    }
    
    private function validatePage($page) {
        $page = (int)$page;
        return $page > 0 ? $page : 1;
    }
    
    private function validateFloat($value) {
        return $value !== null ? (float)$value : null;
    }
    
    private function validateInteger($value, $min = null, $max = null) {
        $value = (int)$value;
        if ($min !== null && $value < $min) return $min;
        if ($max !== null && $value > $max) return $max;
        return $value;
    }
    
    private function validateSlug($slug) {
        return preg_match('/^[a-zA-Z0-9\-_]+$/', $slug) ? $slug : '';
    }
    
    private function validateString($string, $maxLength = 255) {
        $string = htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
        return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
    }
    
    /**
     * 検索の実行
     */
    private function performSearch() {
        $limit = 10;
        
        try {
            if ($this->searchParams['buildingSlug']) {
                $this->searchResult = $this->searchByBuildingSlug($limit);
            } elseif ($this->searchParams['architectsSlug']) {
                $this->searchResult = $this->searchByArchitectSlug($limit);
            } elseif ($this->searchParams['userLat'] !== null && $this->searchParams['userLng'] !== null) {
                $this->searchResult = $this->searchByLocation($limit);
            } else {
                $this->searchResult = $this->searchWithMultipleConditions($limit);
            }
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            $this->searchResult = [
                'buildings' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => 1
            ];
        }
    }
    
    /**
     * 建築物スラッグによる検索
     */
    private function searchByBuildingSlug($limit) {
        $currentBuilding = getBuildingBySlug($this->searchParams['buildingSlug'], $this->lang);
        
        if ($currentBuilding) {
            return [
                'buildings' => [$currentBuilding],
                'total' => 1,
                'totalPages' => 1,
                'currentPage' => 1,
                'currentBuilding' => $currentBuilding
            ];
        }
        
        return [
            'buildings' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => 1,
            'currentBuilding' => null
        ];
    }
    
    /**
     * 建築家スラッグによる検索
     */
    private function searchByArchitectSlug($limit) {
        return searchBuildingsByArchitectSlug(
            $this->searchParams['architectsSlug'], 
            $this->searchParams['page'], 
            $this->lang, 
            $limit, 
            $this->searchParams['completionYears'], 
            $this->searchParams['prefectures'], 
            $this->searchParams['query']
        );
    }
    
    /**
     * 位置情報による検索
     */
    private function searchByLocation($limit) {
        return searchBuildingsByLocation(
            $this->searchParams['userLat'], 
            $this->searchParams['userLng'], 
            $this->searchParams['radiusKm'], 
            $this->searchParams['page'], 
            $this->searchParams['hasPhotos'], 
            $this->searchParams['hasVideos'], 
            $this->lang, 
            $limit
        );
    }
    
    /**
     * 複数条件による検索
     */
    private function searchWithMultipleConditions($limit) {
        return searchBuildingsWithMultipleConditions(
            $this->searchParams['query'], 
            $this->searchParams['completionYears'], 
            $this->searchParams['prefectures'], 
            '', 
            $this->searchParams['hasPhotos'], 
            $this->searchParams['hasVideos'], 
            $this->searchParams['page'], 
            $this->lang, 
            $limit
        );
    }
    
    /**
     * 人気検索の取得
     */
    private function getPopularSearches() {
        try {
            $this->popularSearches = getPopularSearches($this->lang);
        } catch (Exception $e) {
            error_log("Popular searches error: " . $e->getMessage());
            $this->popularSearches = [];
        }
    }
    
    /**
     * アプリケーションの実行
     */
    public function run() {
        // 変数をビューで使用できるように設定
        $buildings = $this->searchResult['buildings'];
        $totalBuildings = $this->searchResult['total'];
        $totalPages = $this->searchResult['totalPages'];
        $currentPage = $this->searchResult['currentPage'];
        $currentBuilding = $this->searchResult['currentBuilding'] ?? null;
        $architectInfo = $this->searchResult['architectInfo'] ?? null;
        
        // 元のindex.phpと同じ変数名を使用
        $query = $this->searchParams['query'];
        $page = $this->searchParams['page'];
        $hasPhotos = $this->searchParams['hasPhotos'];
        $hasVideos = $this->searchParams['hasVideos'];
        $userLat = $this->searchParams['userLat'];
        $userLng = $this->searchParams['userLng'];
        $radiusKm = $this->searchParams['radiusKm'];
        $buildingSlug = $this->searchParams['buildingSlug'];
        $prefectures = $this->searchParams['prefectures'];
        $architectsSlug = $this->searchParams['architectsSlug'];
        $completionYears = $this->searchParams['completionYears'];
        $limit = 10;
        
        $popularSearches = $this->popularSearches;
        $lang = $this->lang;
        
        // 環境変数からSEOデータを取得
        $seoData = [
            'title' => $_ENV['APP_TITLE'] ?? getenv('APP_TITLE') ?? 'PocketNavi - 建築物検索',
            'description' => $_ENV['APP_DESCRIPTION'] ?? getenv('APP_DESCRIPTION') ?? '建築物を検索できるサイト',
            'keywords' => $_ENV['APP_KEYWORDS'] ?? getenv('APP_KEYWORDS') ?? '建築物,検索,建築家'
        ];
        
        $structuredData = [];
        
        // ビューファイルの読み込み
        include 'src/Views/includes/production_index_view.php';
    }
}

// ============================================================================
// アプリケーションの実行
// ============================================================================

try {
    $app = new PocketNaviFixedApp();
    $app->run();
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    
    // エラーが発生した場合は元のindex.phpにフォールバック
    if (file_exists('index.php')) {
        require_once 'index.php';
    } else {
        // 最終的なフォールバック
        http_response_code(500);
        echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>システムエラー - PocketNavi</title>
</head>
<body>
    <h1>システムエラーが発生しました</h1>
    <p>申し訳ございませんが、システムに一時的な問題が発生しています。</p>
    <p>しばらく時間をおいてから再度お試しください。</p>
    <p>エラー詳細: ' . htmlspecialchars($e->getMessage()) . '</p>
</body>
</html>';
    }
}
?>
