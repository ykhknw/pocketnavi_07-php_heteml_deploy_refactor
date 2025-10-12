<?php
/**
 * PocketNavi PHP版 - 本番環境対応リファクタリング版
 * 本番環境の既存ファイル構造に合わせて最適化
 */

// エラーレポートの設定（本番環境用）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 本番環境の既存ファイルを読み込み
// データベース設定ファイルの読み込み（複数候補を試す）
$dbConfigFiles = [
    'config/database.php',
    'config/database_unified.php', 
    'config/db.php',
    'config/config.php',
    'database.php',
    'db.php'
];

$dbConfigLoaded = false;
foreach ($dbConfigFiles as $configFile) {
    if (file_exists($configFile)) {
        require_once $configFile;
        $dbConfigLoaded = true;
        error_log("Database config loaded from: " . $configFile);
        break;
    }
}

if (!$dbConfigLoaded) {
    error_log("No database config file found. Tried: " . implode(', ', $dbConfigFiles));
    die("データベース設定ファイルが見つかりません。");
}

require_once 'src/Views/includes/functions.php';

// セキュリティヘッダーの設定（簡易版）
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// ============================================================================
// 本番環境対応リファクタリング版メイン処理クラス
// ============================================================================

class PocketNaviProductionApp {
    
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
     * 検索パラメータの初期化（本番環境対応）
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
     * 簡易バリデーション関数
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
        
        if ($this->searchParams['buildingSlug']) {
            $this->searchResult = $this->searchByBuildingSlug($limit);
        } elseif ($this->searchParams['architectsSlug']) {
            $this->searchResult = $this->searchByArchitectSlug($limit);
        } elseif ($this->searchParams['userLat'] !== null && $this->searchParams['userLng'] !== null) {
            $this->searchResult = $this->searchByLocation($limit);
        } else {
            $this->searchResult = $this->searchWithMultipleConditions($limit);
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
        $this->popularSearches = getPopularSearches($this->lang);
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
        
        // 簡易SEOデータ
        $seoData = [
            'title' => 'PocketNavi - 建築物検索',
            'description' => '建築物を検索できるサイト',
            'keywords' => '建築物,検索,建築家'
        ];
        
        $structuredData = [];
        
        // 元のindex.phpのHTML部分をそのまま使用
        include 'src/Views/includes/production_index_view.php';
    }
}

// ============================================================================
// アプリケーションの実行
// ============================================================================

try {
    $app = new PocketNaviProductionApp();
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
</body>
</html>';
    }
}
?>
