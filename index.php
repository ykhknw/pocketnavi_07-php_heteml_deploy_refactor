<?php
/**
 * PocketNavi PHP版 - 安全版リファクタリング
 * クラス重複エラーを回避し、既存システムとの互換性を保つ
 */

// エラーレポートの設定（本番環境用）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 本番環境でのログ設定
$isProduction = true; // 本番環境では true に設定
define('DEBUG_MODE', false); // 本番環境では false に設定

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
            
            // DB_NAMEをDB_DATABASEとしても設定（互換性のため）
            if ($name === 'DB_NAME' && !array_key_exists('DB_DATABASE', $_ENV)) {
                $_ENV['DB_DATABASE'] = $value;
                putenv("DB_DATABASE=$value");
            }
        }
    }
    return true;
}

// .envファイルの読み込みを試行
$envLoaded = loadEnvFile('.env') || loadEnvFile('../.env') || loadEnvFile('../../.env');

if ($envLoaded) {
    if (!$isProduction) {
        error_log("Environment variables loaded from .env file");
        // デバッグ: 読み込まれた環境変数を確認
        error_log("Debug - Loaded DB_DATABASE from .env: " . ($_ENV['DB_DATABASE'] ?? 'not_found'));
        error_log("Debug - Loaded DB_NAME from .env: " . ($_ENV['DB_NAME'] ?? 'not_found'));
        
        // デバッグ: .envファイルの内容を確認
        if (file_exists('.env')) {
            $envContent = file_get_contents('.env');
            error_log("Debug - .env file content (first 200 chars): " . substr($envContent, 0, 200));
        }
    }
} else {
    error_log("No .env file found, using system environment variables");
}

// ============================================================================
// データベース接続の設定
// ============================================================================

// 環境変数からデータベース設定を取得（修正版）
$dbConfig = [
    'host' => !empty($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : (!empty(getenv('DB_HOST')) ? getenv('DB_HOST') : 'localhost'),
    'port' => !empty($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : (!empty(getenv('DB_PORT')) ? getenv('DB_PORT') : '3306'),
    'database' => !empty($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : (!empty(getenv('DB_DATABASE')) ? getenv('DB_DATABASE') : '_shinkenchiku_02'),
    'username' => !empty($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : (!empty(getenv('DB_USERNAME')) ? getenv('DB_USERNAME') : 'root'),
    'password' => !empty($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : (!empty(getenv('DB_PASSWORD')) ? getenv('DB_PASSWORD') : ''),
    'charset' => !empty($_ENV['DB_CHARSET']) ? $_ENV['DB_CHARSET'] : (!empty(getenv('DB_CHARSET')) ? getenv('DB_CHARSET') : 'utf8')
];

// デバッグ: 環境変数の値をログ出力（開発環境のみ）
if (!$isProduction) {
    error_log("Debug - DB_HOST: " . ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'not_set'));
    error_log("Debug - DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? 'not_set'));
    error_log("Debug - DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'not_set'));
    error_log("Debug - Final dbConfig: " . json_encode($dbConfig));
}

// データベース接続関数（既存のfunctions.phpより先に定義）
function getDB() {
    global $dbConfig;
    
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ];
            
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
            if (!$isProduction) {
                error_log("Database connected successfully to: {$dbConfig['host']}:{$dbConfig['port']}/{$dbConfig['database']}");
            }
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("データベース接続に失敗しました: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// データベース接続を事前に確立し、グローバル変数として保持
try {
    $GLOBALS['pocketnavi_db_connection'] = getDB();
    if (!$isProduction) {
        error_log("Pre-connection test successful with database: " . $dbConfig['database']);
    }
} catch (Exception $e) {
    error_log("Pre-connection test failed: " . $e->getMessage());
}

// ============================================================================
// 既存システムとの互換性を保つための設定
// ============================================================================

// セキュリティヘッダーの設定
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// 既存のfunctions.phpを読み込み
$functionsLoaded = false;
try {
    require_once 'src/Views/includes/functions.php';
    $functionsLoaded = true;
    if (!$isProduction) {
        error_log("Functions.php loaded successfully");
    }
    
    // functions.php読み込み後、確実に正しいデータベース接続を使用
    if (isset($GLOBALS['pocketnavi_db_connection'])) {
        // グローバル接続を再設定
        $GLOBALS['pocketnavi_db_connection'] = getDB();
        if (!$isProduction) {
            error_log("Database connection re-established after functions.php load");
        }
    }
    
    // データベース接続の最終確認
    try {
        $finalTest = getDB();
        $dbName = $finalTest->query("SELECT DATABASE()")->fetchColumn();
        if (!$isProduction) {
            error_log("Final database connection test - Current database: " . $dbName);
        }
    } catch (Exception $e) {
        error_log("Final database connection test failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log("Functions.php loading failed: " . $e->getMessage());
}

// functions.phpが読み込めない場合のフォールバック関数
if (!$functionsLoaded) {
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

// ============================================================================
// 安全版リファクタリングメイン処理クラス
// ============================================================================

class PocketNaviSafeApp {
    
    private $lang;
    private $searchParams;
    private $searchResult;
    private $popularSearches;
    private $debugMode;
    private $cacheEnabled;
    private $cachedBuildingService;
    
    public function __construct() {
        $this->debugMode = isset($_GET['debug']) && ($_GET['debug'] === '1' || $_GET['debug'] === 'true');
        $this->cacheEnabled = isset($_GET['cache']) ? $_GET['cache'] === '1' : true; // デフォルトでキャッシュ有効
        
        // デバッグモードの確認（ログ出力）
        if ($this->debugMode) {
            error_log("Debug mode activated via URL parameter: " . ($_GET['debug'] ?? 'not_set'));
        }
        
        // キャッシュ機能付きサービスを初期化
        try {
            require_once 'src/Services/CachedBuildingService.php';
            $this->cachedBuildingService = new CachedBuildingService($this->cacheEnabled, 3600);
            
            // デバッグ情報を追加
            if ($this->debugMode) {
                error_log("CachedBuildingService initialized successfully. Cache enabled: " . ($this->cacheEnabled ? 'true' : 'false'));
            }
        } catch (Exception $e) {
            error_log("CachedBuildingService initialization failed: " . $e->getMessage());
            if ($this->debugMode) {
                error_log("Debug - Cache service initialization error: " . $e->getTraceAsString());
            }
            $this->cachedBuildingService = null;
        }
        
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
        
        // パフォーマンス測定開始
        $startTime = microtime(true);
        
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
        
        // パフォーマンス測定終了
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2); // ミリ秒
        
        // 実行時間をキャッシュ情報に追加
        if (isset($this->searchResult['_cache_info'])) {
            $this->searchResult['_cache_info']['execution_time_ms'] = $executionTime;
        } else {
            $this->searchResult['_cache_info'] = [
                'hit' => false,
                'reason' => 'no_cache_info',
                'execution_time_ms' => $executionTime,
                'created' => time(),
                'expires' => time()
            ];
        }
    }
    
    /**
     * 建築物スラッグによる検索
     */
    private function searchByBuildingSlug($limit) {
        if ($this->cachedBuildingService) {
            $currentBuilding = $this->cachedBuildingService->getBySlug($this->searchParams['buildingSlug'], $this->lang);
        } else {
            // フォールバック: 既存の関数を使用
            $currentBuilding = getBuildingBySlug($this->searchParams['buildingSlug'], $this->lang);
        }
        
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
        if ($this->cachedBuildingService) {
            return $this->cachedBuildingService->searchByArchitectSlug(
                $this->searchParams['architectsSlug'], 
                $this->searchParams['page'], 
                $this->lang, 
                $limit
            );
        } else {
            // フォールバック: 既存の関数を使用
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
    }
    
    /**
     * 位置情報による検索
     */
    private function searchByLocation($limit) {
        if ($this->cachedBuildingService) {
            return $this->cachedBuildingService->searchByLocation(
                $this->searchParams['userLat'], 
                $this->searchParams['userLng'], 
                $this->searchParams['radiusKm'], 
                $this->searchParams['page'], 
                $this->searchParams['hasPhotos'], 
                $this->searchParams['hasVideos'], 
                $this->lang, 
                $limit
            );
        } else {
            // フォールバック: 既存の関数を使用
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
    }
    
    /**
     * 複数条件による検索
     */
    private function searchWithMultipleConditions($limit) {
        if ($this->cachedBuildingService) {
            return $this->cachedBuildingService->searchWithMultipleConditions(
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
        } else {
            // フォールバック: 既存の関数を使用
            $result = searchBuildingsWithMultipleConditions(
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
            
            // フォールバック時のキャッシュ情報を追加
            if (is_array($result)) {
                $result['_cache_info'] = [
                    'hit' => false,
                    'reason' => 'cache_service_unavailable',
                    'created' => time(),
                    'expires' => time()
                ];
            }
            
            return $result;
        }
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
     * キャッシュ統計情報の取得
     */
    public function getCacheStats() {
        if ($this->cachedBuildingService) {
            return $this->cachedBuildingService->getCacheStats();
        }
        return null;
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
        
        // キャッシュ統計情報
        $cacheStats = $this->getCacheStats();
        
        // ビューファイルの読み込み
        $viewFile = 'src/Views/includes/production_index_view.php';
        if (file_exists($viewFile) && !$this->debugMode) {
            // デバッグモードでない場合のみ既存のビューファイルを使用
            include $viewFile;
        } else {
            // デバッグモードまたはビューファイルが存在しない場合はフォールバックビューを使用
            $this->renderFallbackView($buildings, $totalBuildings, $totalPages, $currentPage, $currentBuilding, $architectInfo, $query, $page, $hasPhotos, $hasVideos, $userLat, $userLng, $radiusKm, $buildingSlug, $prefectures, $architectsSlug, $completionYears, $limit, $popularSearches, $lang, $seoData, $structuredData, $cacheStats);
        }
    }
    
    /**
     * 都道府県の表示名を取得
     */
    private function getPrefectureDisplayName($prefectures, $lang) {
        // 都道府県の英語名から日本語名への変換マップ
        $prefectureMap = [
            'Aichi' => '愛知県',
            'Tokyo' => '東京都',
            'Osaka' => '大阪府',
            'Kyoto' => '京都府',
            'Kanagawa' => '神奈川県',
            'Saitama' => '埼玉県',
            'Chiba' => '千葉県',
            'Hyogo' => '兵庫県',
            'Fukuoka' => '福岡県',
            'Hokkaido' => '北海道',
            'Aomori' => '青森県',
            'Iwate' => '岩手県',
            'Miyagi' => '宮城県',
            'Akita' => '秋田県',
            'Yamagata' => '山形県',
            'Fukushima' => '福島県',
            'Ibaraki' => '茨城県',
            'Tochigi' => '栃木県',
            'Gunma' => '群馬県',
            'Niigata' => '新潟県',
            'Toyama' => '富山県',
            'Ishikawa' => '石川県',
            'Fukui' => '福井県',
            'Yamanashi' => '山梨県',
            'Nagano' => '長野県',
            'Gifu' => '岐阜県',
            'Shizuoka' => '静岡県',
            'Mie' => '三重県',
            'Shiga' => '滋賀県',
            'Nara' => '奈良県',
            'Wakayama' => '和歌山県',
            'Tottori' => '鳥取県',
            'Shimane' => '島根県',
            'Okayama' => '岡山県',
            'Hiroshima' => '広島県',
            'Yamaguchi' => '山口県',
            'Tokushima' => '徳島県',
            'Kagawa' => '香川県',
            'Ehime' => '愛媛県',
            'Kochi' => '高知県',
            'Saga' => '佐賀県',
            'Nagasaki' => '長崎県',
            'Kumamoto' => '熊本県',
            'Oita' => '大分県',
            'Miyazaki' => '宮崎県',
            'Kagoshima' => '鹿児島県',
            'Okinawa' => '沖縄県'
        ];
        
        // 言語に応じて表示名を返す
        if ($lang === 'ja') {
            return $prefectureMap[$prefectures] ?? $prefectures;
        } else {
            return $prefectures;
        }
    }
    
    /**
     * フォールバックビューのレンダリング
     */
    private function renderFallbackView($buildings, $totalBuildings, $totalPages, $currentPage, $currentBuilding, $architectInfo, $query, $page, $hasPhotos, $hasVideos, $userLat, $userLng, $radiusKm, $buildingSlug, $prefectures, $architectsSlug, $completionYears, $limit, $popularSearches, $lang, $seoData, $structuredData, $cacheStats) {
        ?>
        <!DOCTYPE html>
        <html lang="<?php echo $lang; ?>">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($seoData['title']); ?></title>
            <meta name="description" content="<?php echo htmlspecialchars($seoData['description']); ?>">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://unpkg.com/lucide@latest"></script>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <link rel="stylesheet" href="/assets/css/style.css">
            <link rel="icon" href="/assets/images/landmark.svg" type="image/svg+xml">
        </head>
        <body>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <?php if ($this->debugMode): ?>
                            <div class="alert alert-info">
                                <h4>🚀 PocketNavi リファクタリング版</h4>
                                <p>新しいアーキテクチャで動作しています。</p>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-success">REFACTORED</span>
                                    <?php if ($this->cacheEnabled): ?>
                                        <span class="badge bg-primary">キャッシュ有効</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">キャッシュ無効</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($cacheStats): ?>
                                    <div class="mt-2">
                                        <small>
                                            キャッシュ統計: 
                                            ファイル数: <?php echo $cacheStats['totalFiles']; ?>件, 
                                            サイズ: <?php echo round($cacheStats['totalSize'] / 1024, 2); ?>KB
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <small class="text-muted">
                                        デバッグ情報:<br>
                                        - キャッシュサービス: <?php echo $this->cachedBuildingService ? '利用可能' : '利用不可'; ?><br>
                                        - キャッシュ有効: <?php echo $this->cacheEnabled ? 'true' : 'false'; ?><br>
                                        - 検索パラメータ: <?php echo htmlspecialchars(json_encode($this->searchParams)); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- 検索フォーム -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">建築物検索</h5>
                                <form method="GET">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" name="q" class="form-control" placeholder="キーワード" value="<?php echo htmlspecialchars($query); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="prefectures" class="form-control" placeholder="都道府県" value="<?php echo htmlspecialchars($prefectures); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary w-100">検索</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- フィルター適用済み -->
                        <?php if ($architectsSlug && $architectInfo): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">フィルター適用済み</h6>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($architectInfo['name'] ?? $architectInfo['name_ja'] ?? $architectInfo['name_en'] ?? $architectsSlug); ?>
                                            <a href="?" class="text-white text-decoration-none ms-1">×</a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($prefectures): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">フィルター適用済み</h6>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-primary">
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo htmlspecialchars($this->getPrefectureDisplayName($prefectures, $lang)); ?>
                                            <a href="?" class="text-white text-decoration-none ms-1">×</a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 検索結果 -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    検索結果 
                                    <span class="badge bg-primary"><?php echo $totalBuildings; ?>件</span>
                                    
                                    <?php if ($this->debugMode && isset($this->searchResult['_cache_info'])): ?>
                                        <?php $cacheInfo = $this->searchResult['_cache_info']; ?>
                                        <?php if ($cacheInfo['hit']): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="bi bi-lightning-charge"></i>
                                                キャッシュヒット
                                            </span>
                                            <small class="text-muted d-block mt-1">
                                                キャッシュ作成: <?php echo date('H:i:s', $cacheInfo['created']); ?> 
                                                (<?php echo round($cacheInfo['age'] / 60, 1); ?>分前)
                                                <?php if (isset($cacheInfo['execution_time_ms'])): ?>
                                                    | 実行時間: <?php echo $cacheInfo['execution_time_ms']; ?>ms
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-warning ms-2">
                                                <i class="bi bi-database"></i>
                                                データベース検索
                                            </span>
                                            <small class="text-muted d-block mt-1">
                                                理由: <?php 
                                                    switch($cacheInfo['reason']) {
                                                        case 'cache_miss': echo 'キャッシュなし'; break;
                                                        case 'cache_disabled': echo 'キャッシュ無効'; break;
                                                        case 'cache_service_unavailable': echo 'キャッシュサービス利用不可'; break;
                                                        case 'no_cache_info': echo 'キャッシュ情報なし'; break;
                                                        default: echo $cacheInfo['reason']; break;
                                                    }
                                                ?>
                                                <?php if (isset($cacheInfo['execution_time_ms'])): ?>
                                                    | 実行時間: <?php echo $cacheInfo['execution_time_ms']; ?>ms
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </h5>
                                
                                <?php if (!empty($buildings)): ?>
                                    <?php foreach ($buildings as $index => $building): ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($building['title'] ?? ''); ?></h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        場所: <?php echo htmlspecialchars($building['location'] ?? ''); ?><br>
                                                        完成年: <?php echo htmlspecialchars($building['completionYears'] ?? ''); ?>
                                                    </small>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">検索結果が見つかりませんでした。</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- 人気検索 -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">人気検索</h5>
                                <?php if (!empty($popularSearches)): ?>
                                    <?php foreach ($popularSearches as $search): ?>
                                        <a href="?q=<?php echo urlencode($search['query']); ?>" class="btn btn-outline-secondary btn-sm me-2 mb-2">
                                            <?php echo htmlspecialchars($search['query']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    lucide.createIcons();
                });
            </script>
        </body>
        </html>
        <?php
    }
}

// ============================================================================
// アプリケーションの実行
// ============================================================================

try {
    $app = new PocketNaviSafeApp();
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
