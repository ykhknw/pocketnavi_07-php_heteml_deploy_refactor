<?php
/**
 * PocketNavi PHP版 - 完全リファクタリング版
 * 元のindex.phpのデザインと機能を完全に維持しながら、コードの品質を向上
 */

// ============================================================================
// 初期化と設定
// ============================================================================

// エラーレポートの設定
error_reporting(E_ALL);
ini_set('display_errors', 0); // 本番環境では0に設定

// 必要なファイルの読み込み
require_once 'config/database_unified.php';
require_once 'src/Utils/Config.php';
require_once 'src/Views/includes/functions.php';
require_once 'src/Utils/InputValidator.php';
require_once 'src/Utils/SecurityHelper.php';
require_once 'src/Utils/SecurityHeaders.php';
require_once 'src/Utils/SEOHelper.php';

// セキュリティヘッダーを設定
SecurityHeaders::setHeadersByEnvironment();

// ============================================================================
// リファクタリングされたメイン処理クラス
// ============================================================================

class PocketNaviApplication {
    
    private $lang;
    private $searchParams;
    private $searchResult;
    private $seoData;
    private $structuredData;
    private $popularSearches;
    private $debugInfo;
    private $debugMode;
    
    public function __construct() {
        $this->debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
        $this->initializeSearchParameters();
        $this->performSearch();
        $this->generateSEOData();
        $this->getPopularSearches();
        $this->getDebugInfo();
    }
    
    /**
     * 検索パラメータの初期化
     */
    private function initializeSearchParameters() {
        $this->lang = InputValidator::validateLanguage($_GET['lang'] ?? 'ja');
        
        $this->searchParams = [
            'query' => InputValidator::validateSearchQuery($_GET['q'] ?? ''),
            'page' => InputValidator::validatePage($_GET['page'] ?? 1),
            'hasPhotos' => InputValidator::validateBoolean($_GET['photos'] ?? false),
            'hasVideos' => InputValidator::validateBoolean($_GET['videos'] ?? false),
            'userLat' => InputValidator::validateCoordinates($_GET['lat'] ?? null, $_GET['lng'] ?? null)[0],
            'userLng' => InputValidator::validateCoordinates($_GET['lat'] ?? null, $_GET['lng'] ?? null)[1],
            'radiusKm' => InputValidator::validateInteger($_GET['radius'] ?? 5, 1, 100) ?? 5,
            'buildingSlug' => InputValidator::validateSlug($_GET['building_slug'] ?? ''),
            'prefectures' => InputValidator::validatePrefecture($_GET['prefectures'] ?? ''),
            'architectsSlug' => InputValidator::validateSlug($_GET['architects_slug'] ?? ''),
            'completionYears' => InputValidator::validateString($_GET['completionYears'] ?? '', 50)
        ];
        
        if ($this->debugMode) {
            $this->logDebugInfo();
        }
    }
    
    /**
     * デバッグ情報のログ出力
     */
    private function logDebugInfo() {
        error_log("Debug - hasPhotos: " . ($this->searchParams['hasPhotos'] ? 'true' : 'false') . " (raw: " . ($_GET['photos'] ?? 'not set') . ")");
        error_log("Debug - hasVideos: " . ($this->searchParams['hasVideos'] ? 'true' : 'false') . " (raw: " . ($_GET['videos'] ?? 'not set') . ")");
        error_log("Debug - query: " . ($this->searchParams['query'] ?: 'empty'));
        error_log("Debug - buildingSlug: " . ($this->searchParams['buildingSlug'] ?: 'empty'));
        error_log("Debug - architectsSlug: " . ($this->searchParams['architectsSlug'] ?: 'empty'));
        error_log("Debug - GET params: " . print_r($_GET, true));
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
            $this->logBuildingPageView($currentBuilding);
            
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
        $searchResult = searchBuildingsByArchitectSlug(
            $this->searchParams['architectsSlug'], 
            $this->searchParams['page'], 
            $this->lang, 
            $limit, 
            $this->searchParams['completionYears'], 
            $this->searchParams['prefectures'], 
            $this->searchParams['query']
        );
        
        if ($this->debugMode) {
            error_log("Debug - architectsSlug: " . $this->searchParams['architectsSlug']);
            error_log("Debug - searchResult: " . print_r($searchResult, true));
            error_log("Debug - architectInfo: " . print_r($searchResult['architectInfo'] ?? null, true));
        }
        
        return $searchResult;
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
        // 都道府県ページ閲覧ログを記録
        if (!empty($this->searchParams['prefectures']) && empty($this->searchParams['query']) && empty($this->searchParams['completionYears'])) {
            $this->logPrefecturePageView();
        }
        
        if ($this->debugMode) {
            $originalResult = searchBuildings($this->searchParams['query'], $this->searchParams['page'], $this->searchParams['hasPhotos'], $this->searchParams['hasVideos'], $this->lang, $limit);
            error_log("Original searchBuildings result - total: " . $originalResult['total'] . ", buildings count: " . count($originalResult['buildings']));
        }
        
        $searchResult = searchBuildingsWithMultipleConditions(
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
        
        error_log("Search parameters - query: '{$this->searchParams['query']}', prefectures: '{$this->searchParams['prefectures']}', completionYears: '{$this->searchParams['completionYears']}', hasPhotos: " . ($this->searchParams['hasPhotos'] ? 'true' : 'false') . ", hasVideos: " . ($this->searchParams['hasVideos'] ? 'true' : 'false'));
        error_log("Search result - total: " . $searchResult['total'] . ", buildings count: " . count($searchResult['buildings']));
        
        return $searchResult;
    }
    
    /**
     * 建築物ページビューログの記録
     */
    private function logBuildingPageView($currentBuilding) {
        try {
            require_once __DIR__ . '/src/Services/SearchLogService.php';
            $searchLogService = new SearchLogService();
            $searchLogService->logPageView('building', $this->searchParams['buildingSlug'], $currentBuilding['title'] ?? $currentBuilding['titleEn'] ?? $this->searchParams['buildingSlug'], [
                'building_id' => $currentBuilding['building_id'] ?? null,
                'lang' => $this->lang
            ]);
        } catch (Exception $e) {
            error_log("Building page view log error: " . $e->getMessage());
        }
    }
    
    /**
     * 都道府県ページビューログの記録
     */
    private function logPrefecturePageView() {
        try {
            require_once __DIR__ . '/src/Services/SearchLogService.php';
            $searchLogService = new SearchLogService();
            
            $prefectureTranslations = $this->getPrefectureTranslations();
            $prefectureName = $this->searchParams['prefectures'];
            if ($this->lang === 'ja' && isset($prefectureTranslations[$this->searchParams['prefectures']])) {
                $prefectureName = $prefectureTranslations[$this->searchParams['prefectures']];
            }
            
            $searchLogService->logPageView('prefecture', $this->searchParams['prefectures'], $prefectureName, [
                'lang' => $this->lang,
                'hasPhotos' => $this->searchParams['hasPhotos'],
                'hasVideos' => $this->searchParams['hasVideos'],
                'prefecture_ja' => $prefectureTranslations[$this->searchParams['prefectures']] ?? $this->searchParams['prefectures'],
                'prefecture_en' => $this->searchParams['prefectures']
            ]);
        } catch (Exception $e) {
            error_log("Prefecture page view log error: " . $e->getMessage());
        }
    }
    
    /**
     * 都道府県翻訳データの取得
     */
    private function getPrefectureTranslations() {
        return [
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
            'Saitama' => '埼玉県',
            'Chiba' => '千葉県',
            'Tokyo' => '東京都',
            'Kanagawa' => '神奈川県',
            'Niigata' => '新潟県',
            'Toyama' => '富山県',
            'Ishikawa' => '石川県',
            'Fukui' => '福井県',
            'Yamanashi' => '山梨県',
            'Nagano' => '長野県',
            'Gifu' => '岐阜県',
            'Shizuoka' => '静岡県',
            'Aichi' => '愛知県',
            'Mie' => '三重県',
            'Shiga' => '滋賀県',
            'Kyoto' => '京都府',
            'Osaka' => '大阪府',
            'Hyogo' => '兵庫県',
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
            'Fukuoka' => '福岡県',
            'Saga' => '佐賀県',
            'Nagasaki' => '長崎県',
            'Kumamoto' => '熊本県',
            'Oita' => '大分県',
            'Miyazaki' => '宮崎県',
            'Kagoshima' => '鹿児島県',
            'Okinawa' => '沖縄県'
        ];
    }
    
    /**
     * SEOデータの生成
     */
    private function generateSEOData() {
        $this->seoData = [];
        $this->structuredData = [];
        $pageType = 'home';
        
        if ($this->searchParams['buildingSlug'] && isset($this->searchResult['currentBuilding'])) {
            $pageType = 'building';
            $this->seoData = SEOHelper::generateMetaTags('building', $this->searchResult['currentBuilding'], $this->lang);
            $this->structuredData = SEOHelper::generateStructuredData('building', $this->searchResult['currentBuilding'], $this->lang);
        } elseif ($this->searchParams['architectsSlug'] && isset($this->searchResult['architectInfo'])) {
            $pageType = 'architect';
            $architectInfo = $this->searchResult['architectInfo'];
            $architectInfo['building_count'] = count($this->searchResult['buildings']);
            $this->seoData = SEOHelper::generateMetaTags('architect', $architectInfo, $this->lang);
            $this->structuredData = SEOHelper::generateStructuredData('architect', $architectInfo, $this->lang);
        }
        
        // デフォルト値の設定
        if (empty($this->seoData)) {
            $this->seoData = SEOHelper::generateMetaTags('home', [], $this->lang);
            $this->structuredData = SEOHelper::generateStructuredData('home', [], $this->lang);
        }
    }
    
    /**
     * 人気検索の取得
     */
    private function getPopularSearches() {
        $this->popularSearches = getPopularSearches($this->lang);
    }
    
    /**
     * デバッグ情報の取得
     */
    private function getDebugInfo() {
        if ($this->debugMode) {
            $this->debugInfo = debugDatabase();
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
        
        $seoData = $this->seoData;
        $structuredData = $this->structuredData;
        $popularSearches = $this->popularSearches;
        $debugInfo = $this->debugInfo;
        $lang = $this->lang;
        
        // 元のindex.phpのHTML部分をそのまま使用
        include 'src/Views/includes/refactored_index_view.php';
    }
}

// ============================================================================
// アプリケーションの実行
// ============================================================================

try {
    $app = new PocketNaviApplication();
    $app->run();
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    
    // エラーが発生した場合は元のindex.phpにフォールバック
    require_once 'index.php';
}
?>
