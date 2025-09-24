<?php

// 必要なファイルを読み込み
//require_once __DIR__ . '/../Utils/Database.php';

/**
 * 建築物検索サービス
 */
class BuildingService {
    private $db;
    private $buildings_table = 'buildings_table_3';
    private $building_architects_table = 'building_architects';
    private $architect_compositions_table = 'architect_compositions_2';
    private $individual_architects_table = 'individual_architects_3';
    
    public function __construct() {
        $this->db = getDB();
        if ($this->db === null) {
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * 建築物を検索する
     */
    public function search($query, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
        // キーワードを分割（全角・半角スペースで分割）
        $keywords = $this->parseKeywords($query);
        
        // WHERE句の構築
        $whereClauses = [];
        $params = [];
        
        // キーワード検索条件の追加
        $this->addKeywordConditions($whereClauses, $params, $keywords);
        
        // メディアフィルターの追加
        $this->addMediaFilters($whereClauses, $hasPhotos, $hasVideos);
        
        return $this->executeSearch($whereClauses, $params, $page, $lang, $limit);
    }
    
    /**
     * 複数条件での建築物検索
     */
    public function searchWithMultipleConditions($query, $completionYears, $prefectures, $buildingTypes, $hasPhotos, $hasVideos, $page = 1, $lang = 'ja', $limit = 10) {
        // 検索ログを記録
        if (!empty($query)) {
            try {
                require_once __DIR__ . '/SearchLogService.php';
                $searchLogService = new SearchLogService();
                
                // 検索語が建築物名かどうかを判定
                $searchType = $this->determineSearchType($query);
                
                $searchLogService->logSearch($query, $searchType, [
                    'hasPhotos' => $hasPhotos,
                    'hasVideos' => $hasVideos,
                    'lang' => $lang,
                    'completionYears' => $completionYears,
                    'prefectures' => $prefectures,
                    'buildingTypes' => $buildingTypes
                ]);
            } catch (Exception $e) {
                error_log("Search log error: " . $e->getMessage());
            }
        }
        
        // WHERE句の構築
        $whereClauses = [];
        $params = [];
        
        // キーワード検索条件の追加
        $keywords = $this->parseKeywords($query);
        $this->addKeywordConditions($whereClauses, $params, $keywords);
        
        // 完成年条件の追加
        $this->addCompletionYearConditions($whereClauses, $params, $completionYears);
        
        // 都道府県条件の追加
        $this->addPrefectureConditions($whereClauses, $params, $prefectures);
        
        // 建築種別条件の追加
        $this->addBuildingTypeConditions($whereClauses, $params, $buildingTypes);
        
        // メディアフィルターの追加
        $this->addMediaFilters($whereClauses, $hasPhotos, $hasVideos);
        
        return $this->executeSearch($whereClauses, $params, $page, $lang, $limit);
    }
    
    /**
     * 検索語のタイプを判定
     */
    private function determineSearchType($query) {
        try {
            // 建築物名として検索してみる
            $sql = "
                SELECT COUNT(*) as count
                FROM {$this->buildings_table}
                WHERE title = ? OR titleEn = ? OR slug = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$query, $query, $query]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return 'building';
            }
            
            // 建築家名として検索してみる
            $sql = "
                SELECT COUNT(*) as count
                FROM {$this->individual_architects_table}
                WHERE name_ja = ? OR name_en = ? OR slug = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$query, $query, $query]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return 'architect';
            }
            
            // 都道府県名として検索してみる
            $sql = "
                SELECT COUNT(*) as count
                FROM {$this->buildings_table}
                WHERE prefectures = ? OR prefecturesEn = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$query, $query]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return 'prefecture';
            }
            
            // どれにも該当しない場合はテキスト検索
            return 'text';
            
        } catch (Exception $e) {
            error_log("Determine search type error: " . $e->getMessage());
            return 'text';
        }
    }
    
    /**
     * 位置情報による建築物検索
     */
    public function searchByLocation($userLat, $userLng, $radiusKm = 5, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
        // WHERE句の構築
        $whereClauses = [];
        $params = [];
        
        // 位置情報条件の追加
        $this->addLocationConditions($whereClauses, $params, $userLat, $userLng, $radiusKm);
        
        // メディアフィルターの追加
        $this->addMediaFilters($whereClauses, $hasPhotos, $hasVideos);
        
        return $this->executeLocationSearch($whereClauses, $params, $userLat, $userLng, $page, $lang, $limit);
    }
    
    /**
     * 建築家による建築物検索
     */
    public function searchByArchitectSlug($architectSlug, $page = 1, $lang = 'ja', $limit = 10) {
        // 建築家検索の場合は、特定の建築家でフィルタリングされた建築物を取得し、
        // 各建築物の建築家情報は、その建築物に紐付くすべての建築家を取得する
        return $this->executeArchitectSearch($architectSlug, $page, $lang, $limit);
    }
    
    /**
     * スラッグで建築物を取得
     */
    public function getBySlug($slug, $lang = 'ja') {
        $sql = "
            SELECT b.building_id,
                   b.uid,
                   b.title,
                   b.titleEn,
                   b.slug,
                   b.lat,
                   b.lng,
                   b.location,
                   b.locationEn_from_datasheetChunkEn as locationEn,
                   b.completionYears,
                   b.buildingTypes,
                   b.buildingTypesEn,
                   b.prefectures,
                   b.prefecturesEn,
                   b.has_photo,
                   b.thumbnailUrl,
                   b.youtubeUrl,
                   b.created_at,
                   b.updated_at,
                   0 as likes,
                   GROUP_CONCAT(
                       DISTINCT ia.name_ja 
                       ORDER BY ba.architect_order, ac.order_index 
                       SEPARATOR ' / '
                   ) AS architectJa,
                   GROUP_CONCAT(
                       DISTINCT ia.name_en 
                       ORDER BY ba.architect_order, ac.order_index 
                       SEPARATOR ' / '
                   ) AS architectEn,
                   GROUP_CONCAT(
                       DISTINCT ba.architect_id 
                       ORDER BY ba.architect_order 
                       SEPARATOR ','
                   ) AS architectIds,
                   GROUP_CONCAT(
                       DISTINCT ia.slug 
                       ORDER BY ba.architect_order, ac.order_index 
                       SEPARATOR ','
                   ) AS architectSlugs
            FROM {$this->buildings_table} b
            LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
            LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
            LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
            WHERE b.slug = ?
            GROUP BY b.building_id
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            
            if ($row) {
                return transformBuildingData($row, $lang);
            }
            
            return null;
            
        } catch (Exception $e) {
            ErrorHandler::logError("Get building by slug error", "getBySlug", $e);
            return null;
        }
    }
    
    // プライベートメソッド群
    
    /**
     * 共通の検索実行ロジック
     */
    private function executeSearch($whereClauses, $params, $page, $lang, $limit) {
        $offset = ($page - 1) * $limit;
        
        // WHERE句の構築
        $whereSql = $this->buildWhereClause($whereClauses);
        
        // カウントクエリ
        $countSql = $this->buildCountQuery($whereSql);
        
        // データ取得クエリ
        $sql = $this->buildSearchQuery($whereSql, $limit, $offset);
        
        try {
            // カウント実行
            $total = $this->executeCountQuery($countSql, $params);
            
            // データ取得実行
            $rows = $this->executeSearchQuery($sql, $params);
            
            // データ変換
            $buildings = $this->transformBuildingData($rows, $lang);
            
            $totalPages = ceil($total / $limit);
            
            return [
                'buildings' => $buildings,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            ErrorHandler::logError("Search error", "executeSearch", $e);
            return ErrorHandler::getEmptySearchResult($page);
        }
    }
    
    /**
     * 建築家検索の専用実行メソッド
     */
    private function executeArchitectSearch($architectSlug, $page, $lang, $limit) {
        $offset = ($page - 1) * $limit;
        
        // カウントクエリ（特定の建築家でフィルタリング）
        $countSql = "
            SELECT COUNT(DISTINCT b.building_id) as total
            FROM {$this->buildings_table} b
            LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
            LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
            LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
            WHERE ia.slug = ?
        ";
        
        // データ取得クエリ（特定の建築家でフィルタリングされた建築物を取得し、
        // 各建築物の建築家情報は、その建築物に紐付くすべての建築家を取得）
        $sql = "
            SELECT b.building_id,
                   b.uid,
                   b.title,
                   b.titleEn,
                   b.slug,
                   b.lat,
                   b.lng,
                   b.location,
                   b.locationEn_from_datasheetChunkEn as locationEn,
                   b.completionYears,
                   b.buildingTypes,
                   b.buildingTypesEn,
                   b.prefectures,
                   b.prefecturesEn,
                   b.has_photo,
                   b.thumbnailUrl,
                   b.youtubeUrl,
                   b.created_at,
                   b.updated_at,
                   0 as likes,
                   GROUP_CONCAT(
                       DISTINCT ia2.name_ja 
                       ORDER BY ba2.architect_order, ac2.order_index 
                       SEPARATOR ' / '
                   ) AS architectJa,
                   GROUP_CONCAT(
                       DISTINCT ia2.name_en 
                       ORDER BY ba2.architect_order, ac2.order_index 
                       SEPARATOR ' / '
                   ) AS architectEn,
                   GROUP_CONCAT(
                       DISTINCT ba2.architect_id 
                       ORDER BY ba2.architect_order 
                       SEPARATOR ','
                   ) AS architectIds,
                   GROUP_CONCAT(
                       DISTINCT ia2.slug 
                       ORDER BY ba2.architect_order, ac2.order_index 
                       SEPARATOR ','
                   ) AS architectSlugs
            FROM {$this->buildings_table} b
            LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
            LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
            LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
            LEFT JOIN {$this->building_architects_table} ba2 ON b.building_id = ba2.building_id
            LEFT JOIN {$this->architect_compositions_table} ac2 ON ba2.architect_id = ac2.architect_id
            LEFT JOIN {$this->individual_architects_table} ia2 ON ac2.individual_architect_id = ia2.individual_architect_id
            WHERE ia.slug = ?
            GROUP BY b.building_id, b.uid, b.title, b.titleEn, b.slug, b.lat, b.lng, b.location, b.locationEn_from_datasheetChunkEn, b.completionYears, b.buildingTypes, b.buildingTypesEn, b.prefectures, b.prefecturesEn, b.has_photo, b.thumbnailUrl, b.youtubeUrl, b.created_at, b.updated_at
            ORDER BY b.has_photo DESC, b.building_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        try {
            // カウント実行
            $total = $this->executeCountQuery($countSql, [$architectSlug]);
            
            // データ取得実行
            $rows = $this->executeSearchQuery($sql, [$architectSlug]);
            
            // データ変換
            $buildings = $this->transformBuildingData($rows, $lang);
            
            $totalPages = ceil($total / $limit);
            
            return [
                'buildings' => $buildings,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            ErrorHandler::logError("Architect search error", "executeArchitectSearch", $e);
            return ErrorHandler::getEmptySearchResult($page);
        }
    }
    
    /**
     * 位置情報検索の専用実行メソッド
     */
    private function executeLocationSearch($whereClauses, $params, $userLat, $userLng, $page, $lang, $limit) {
        $offset = ($page - 1) * $limit;
        
        // WHERE句の構築
        $whereSql = $this->buildWhereClause($whereClauses);
        
        // カウントクエリ
        $countSql = $this->buildCountQuery($whereSql);
        
        // データ取得クエリ（距離順でソート）
        $sql = $this->buildLocationSearchQuery($whereSql, $limit, $offset);
        
        // パラメータの順序を修正（SELECT句用 + WHERE句用）
        $locationParams = [$userLat, $userLng, $userLat]; // SELECT句用
        $allParams = array_merge($locationParams, $params); // WHERE句用
        
        try {
            // カウント実行
            $total = $this->executeCountQuery($countSql, $params);
            
            // データ取得実行
            $rows = $this->executeSearchQuery($sql, $allParams);
            
            // データ変換
            $buildings = $this->transformBuildingData($rows, $lang);
            
            $totalPages = ceil($total / $limit);
            
            return [
                'buildings' => $buildings,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ];
            
        } catch (Exception $e) {
            ErrorHandler::logError("Location search error", "executeLocationSearch", $e);
            return ErrorHandler::getEmptySearchResult($page);
        }
    }
    
    /**
     * キーワードを分割
     */
    private function parseKeywords($query) {
        if (empty($query)) {
            return [];
        }
        
        $temp = str_replace('　', ' ', $query);
        return array_filter(explode(' ', trim($temp)));
    }
    
    /**
     * キーワード検索条件を追加
     */
    private function addKeywordConditions(&$whereClauses, &$params, $keywords) {
        if (empty($keywords)) {
            return;
        }
        
        $keywordConditions = [];
        foreach ($keywords as $keyword) {
            $escapedKeyword = '%' . $keyword . '%';
            $fieldConditions = [
                "b.title LIKE ?",
                "b.titleEn LIKE ?",
                "b.buildingTypes LIKE ?",
                "b.buildingTypesEn LIKE ?",
                "b.location LIKE ?",
                "b.locationEn_from_datasheetChunkEn LIKE ?",
                "ia.name_ja LIKE ?",
                "ia.name_en LIKE ?"
            ];
            $keywordConditions[] = '(' . implode(' OR ', $fieldConditions) . ')';
            
            // パラメータを8回追加（各フィールド用）
            for ($i = 0; $i < 8; $i++) {
                $params[] = $escapedKeyword;
            }
        }
        
        if (!empty($keywordConditions)) {
            $whereClauses[] = '(' . implode(' AND ', $keywordConditions) . ')';
        }
    }
    
    /**
     * 完成年条件を追加
     */
    private function addCompletionYearConditions(&$whereClauses, &$params, $completionYears) {
        if (empty($completionYears)) {
            return;
        }
        
        $yearConditions = [];
        foreach ($completionYears as $year) {
            $yearConditions[] = "b.completionYears LIKE ?";
            $params[] = '%' . $year . '%';
        }
        
        if (!empty($yearConditions)) {
            $whereClauses[] = '(' . implode(' OR ', $yearConditions) . ')';
        }
    }
    
    /**
     * 都道府県条件を追加
     */
    private function addPrefectureConditions(&$whereClauses, &$params, $prefectures) {
        if (empty($prefectures)) {
            return;
        }
        
        // 都道府県の英語名→日本語名マッピング
        $prefectureTranslations = [
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
        
        $prefectureConditions = [];
        foreach ($prefectures as $prefecture) {
            // 英語名の場合は日本語名に変換
            $japaneseName = isset($prefectureTranslations[$prefecture]) ? $prefectureTranslations[$prefecture] : $prefecture;
            
            // 日本語名で検索
            $prefectureConditions[] = "b.prefectures LIKE ?";
            $params[] = '%' . $japaneseName . '%';
        }
        
        if (!empty($prefectureConditions)) {
            $whereClauses[] = '(' . implode(' OR ', $prefectureConditions) . ')';
        }
    }
    
    /**
     * 建築種別条件を追加
     */
    private function addBuildingTypeConditions(&$whereClauses, &$params, $buildingTypes) {
        if (empty($buildingTypes)) {
            return;
        }
        
        $typeConditions = [];
        foreach ($buildingTypes as $type) {
            $typeConditions[] = "b.buildingTypes LIKE ?";
            $params[] = '%' . $type . '%';
        }
        
        if (!empty($typeConditions)) {
            $whereClauses[] = '(' . implode(' OR ', $typeConditions) . ')';
        }
    }
    
    /**
     * メディアフィルターを追加
     */
    private function addMediaFilters(&$whereClauses, $hasPhotos, $hasVideos) {
        if ($hasPhotos) {
            $whereClauses[] = "b.has_photo IS NOT NULL AND b.has_photo != ''";
        }
        
        if ($hasVideos) {
            $whereClauses[] = "b.youtubeUrl IS NOT NULL AND b.youtubeUrl != ''";
        }
    }
    
    /**
     * 位置情報条件を追加
     */
    private function addLocationConditions(&$whereClauses, &$params, $userLat, $userLng, $radiusKm) {
        $whereClauses[] = "b.lat IS NOT NULL AND b.lng IS NOT NULL";
        $whereClauses[] = "(
            6371 * acos(
                cos(radians(?)) * cos(radians(b.lat)) * 
                cos(radians(b.lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(b.lat))
            )
        ) <= ?";
        
        $params[] = $userLat;
        $params[] = $userLng;
        $params[] = $userLat;
        $params[] = $radiusKm;
    }
    
    /**
     * 建築家条件を追加
     */
    private function addArchitectConditions(&$whereClauses, &$params, $architectSlug) {
        $whereClauses[] = "ia.slug = ?";
        $params[] = $architectSlug;
    }
    
    /**
     * WHERE句を構築
     */
    private function buildWhereClause($whereClauses) {
        if (empty($whereClauses)) {
            return '';
        }
        return 'WHERE ' . implode(' AND ', $whereClauses);
    }
    
    /**
     * カウントクエリを構築
     */
    private function buildCountQuery($whereSql) {
        // WHERE句に建築家関連の条件が含まれているかチェック
        if (strpos($whereSql, 'ia.') !== false) {
            // 建築家検索用（JOINあり）
            return "
                SELECT COUNT(DISTINCT b.building_id) as total
                FROM {$this->buildings_table} b
                LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
                LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
                LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
                $whereSql
            ";
        } else {
            // 通常検索用（JOINあり、建築家情報も含める）
            return "
                SELECT COUNT(DISTINCT b.building_id) as total
                FROM {$this->buildings_table} b
                LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
                LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
                LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
                $whereSql
            ";
        }
    }
    
    /**
     * 検索クエリを構築
     */
    private function buildSearchQuery($whereSql, $limit, $offset) {
        // WHERE句に建築家関連の条件が含まれているかチェック
        if (strpos($whereSql, 'ia.') !== false) {
            // 建築家検索用（JOINあり）
            return "
                SELECT b.building_id,
                       b.uid,
                       b.title,
                       b.titleEn,
                       b.slug,
                       b.lat,
                       b.lng,
                       b.location,
                       b.locationEn_from_datasheetChunkEn as locationEn,
                       b.completionYears,
                       b.buildingTypes,
                       b.buildingTypesEn,
                       b.prefectures,
                       b.prefecturesEn,
                       b.has_photo,
                       b.thumbnailUrl,
                       b.youtubeUrl,
                       b.created_at,
                       b.updated_at,
                       0 as likes,
                       GROUP_CONCAT(
                           DISTINCT ia.name_ja 
                           ORDER BY ba.architect_order, ac.order_index 
                           SEPARATOR ' / '
                       ) AS architectJa,
                       GROUP_CONCAT(
                           DISTINCT ia.name_en 
                           ORDER BY ba.architect_order, ac.order_index 
                           SEPARATOR ' / '
                       ) AS architectEn,
                       GROUP_CONCAT(
                           DISTINCT ba.architect_id 
                           ORDER BY ba.architect_order 
                           SEPARATOR ','
                       ) AS architectIds,
                       GROUP_CONCAT(
                           DISTINCT ia.slug 
                           ORDER BY ba.architect_order, ac.order_index 
                           SEPARATOR ','
                       ) AS architectSlugs
                FROM {$this->buildings_table} b
                LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
                LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
                LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
                $whereSql
                GROUP BY b.building_id, b.uid, b.title, b.titleEn, b.slug, b.lat, b.lng, b.location, b.locationEn_from_datasheetChunkEn, b.completionYears, b.buildingTypes, b.buildingTypesEn, b.prefectures, b.prefecturesEn, b.has_photo, b.thumbnailUrl, b.youtubeUrl, b.created_at, b.updated_at
                ORDER BY b.has_photo DESC, b.building_id DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
        } else {
            // 通常検索用（JOINあり、建築家情報も含める）
            return "
                SELECT b.building_id,
                       b.uid,
                       b.title,
                       b.titleEn,
                       b.slug,
                       b.lat,
                       b.lng,
                       b.location,
                       b.locationEn_from_datasheetChunkEn as locationEn,
                       b.completionYears,
                       b.buildingTypes,
                       b.buildingTypesEn,
                       b.prefectures,
                       b.prefecturesEn,
                       b.has_photo,
                       b.thumbnailUrl,
                       b.youtubeUrl,
                       b.created_at,
                       b.updated_at,
                       0 as likes,
                       GROUP_CONCAT(
                           DISTINCT ia.name_ja 
                           ORDER BY ba.architect_order, ac.order_index 
                           SEPARATOR ' / '
                       ) AS architectJa,
                       GROUP_CONCAT(
                           DISTINCT ia.name_en 
                           ORDER BY ba.architect_order, ac.order_index 
                           SEPARATOR ' / '
                       ) AS architectEn,
                       GROUP_CONCAT(
                           DISTINCT ba.architect_id 
                           ORDER BY ba.architect_order 
                           SEPARATOR ','
                       ) AS architectIds,
                       GROUP_CONCAT(
                           DISTINCT ia.slug 
                           ORDER BY ba.architect_order, ac.order_index 
                           SEPARATOR ','
                       ) AS architectSlugs
                FROM {$this->buildings_table} b
                LEFT JOIN {$this->building_architects_table} ba ON b.building_id = ba.building_id
                LEFT JOIN {$this->architect_compositions_table} ac ON ba.architect_id = ac.architect_id
                LEFT JOIN {$this->individual_architects_table} ia ON ac.individual_architect_id = ia.individual_architect_id
                $whereSql
                GROUP BY b.building_id, b.uid, b.title, b.titleEn, b.slug, b.lat, b.lng, b.location, b.locationEn_from_datasheetChunkEn, b.completionYears, b.buildingTypes, b.buildingTypesEn, b.prefectures, b.prefecturesEn, b.has_photo, b.thumbnailUrl, b.youtubeUrl, b.created_at, b.updated_at
                ORDER BY b.has_photo DESC, b.building_id DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
        }
    }
    
    /**
     * 位置情報検索クエリを構築
     */
    private function buildLocationSearchQuery($whereSql, $limit, $offset) {
        return "
            SELECT b.building_id,
                   b.uid,
                   b.title,
                   b.titleEn,
                   b.slug,
                   b.lat,
                   b.lng,
                   b.location,
                   b.locationEn_from_datasheetChunkEn as locationEn,
                   b.completionYears,
                   b.buildingTypes,
                   b.buildingTypesEn,
                   b.prefectures,
                   b.prefecturesEn,
                   b.has_photo,
                   b.thumbnailUrl,
                   b.youtubeUrl,
                   b.created_at,
                   b.updated_at,
                   0 as likes,
                   (
                       6371 * acos(
                           cos(radians(?)) * cos(radians(b.lat)) * 
                           cos(radians(b.lng) - radians(?)) + 
                           sin(radians(?)) * sin(radians(b.lat))
                       )
                   ) AS distance,
                   '' as architectJa,
                   '' as architectEn,
                   '' as architectIds,
                   '' as architectSlugs
            FROM {$this->buildings_table} b
            WHERE b.lat IS NOT NULL AND b.lng IS NOT NULL AND (
            6371 * acos(
                cos(radians(?)) * cos(radians(b.lat)) * 
                cos(radians(b.lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(b.lat))
            )
        ) <= ?
            ORDER BY distance ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
    }
    
    /**
     * カウントクエリを実行
     */
    private function executeCountQuery($sql, $params) {
        if ($this->db === null) {
            throw new Exception("Database connection is null");
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        if ($result) {
            if (isset($result['total'])) {
                return $result['total'];
            } elseif (isset($result[0])) {
                return $result[0];
            } else {
                return 0;
            }
        }
        return 0;
    }
    
    /**
     * 検索クエリを実行
     */
    private function executeSearchQuery($sql, $params) {
        if ($this->db === null) {
            throw new Exception("Database connection is null");
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("SQL execution failed: " . print_r($errorInfo, true));
            }
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            ErrorHandler::logError("executeSearchQuery error", "executeSearchQuery", $e);
            throw $e;
        }
    }
    
    /**
     * 建築物データを変換
     */
    private function transformBuildingData($rows, $lang) {
        if (empty($rows)) {
            return [];
        }
        
        // 複数行の場合
        if (is_array($rows) && isset($rows[0])) {
            $buildings = [];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $buildings[] = $this->transformSingleBuildingData($row, $lang);
                }
            }
            return $buildings;
        } 
        
        // 単一行の場合
        if (is_array($rows)) {
            return [$this->transformSingleBuildingData($rows, $lang)];
        }
        
        return [];
    }
    
    /**
     * 単一建築物データを変換
     */
    private function transformSingleBuildingData($row, $lang) {
        // 建築家情報の処理
        $architects = [];
        if (!empty($row['architectJa']) && $row['architectJa'] !== '') {
            $namesJa = explode(' / ', $row['architectJa']);
            $namesEn = !empty($row['architectEn']) && $row['architectEn'] !== '' ? explode(' / ', $row['architectEn']) : [];
            $architectIds = !empty($row['architectIds']) && $row['architectIds'] !== '' ? explode(',', $row['architectIds']) : [];
            $architectSlugs = !empty($row['architectSlugs']) && $row['architectSlugs'] !== '' ? explode(',', $row['architectSlugs']) : [];
            
            for ($i = 0; $i < count($namesJa); $i++) {
                $architects[] = [
                    'architect_id' => isset($architectIds[$i]) ? intval($architectIds[$i]) : 0,
                    'architectJa' => trim($namesJa[$i]),
                    'architectEn' => isset($namesEn[$i]) ? trim($namesEn[$i]) : trim($namesJa[$i]),
                    'slug' => isset($architectSlugs[$i]) ? trim($architectSlugs[$i]) : ''
                ];
            }
        }
        
        // 建物用途の配列変換
        $buildingTypes = !empty($row['buildingTypes']) ? 
            array_filter(explode('/', $row['buildingTypes']), function($type) {
                return !empty(trim($type));
            }) : [];
        
        $buildingTypesEn = !empty($row['buildingTypesEn']) ? 
            array_filter(explode('/', $row['buildingTypesEn']), function($type) {
                return !empty(trim($type));
            }) : [];
        
        // サムネイルURLの生成
        $thumbnailUrl = '';
        if (!empty($row['has_photo'])) {
            $uid = $row['uid'] ?? '';
            $photo = $row['has_photo'];
            $thumbnailUrl = "https://kenchikuka.com/pictures/{$uid}/{$photo}";
        }
        
        return [
            'building_id' => $row['building_id'] ?? 0,
            'uid' => $row['uid'] ?? '',
            'title' => $lang === 'ja' ? ($row['title'] ?? '') : ($row['titleEn'] ?? ''),
            'titleEn' => $row['titleEn'] ?? '',
            'slug' => $row['slug'] ?? '',
            'lat' => $row['lat'] ?? 0,
            'lng' => $row['lng'] ?? 0,
            'location' => $lang === 'ja' ? ($row['location'] ?? '') : ($row['locationEn'] ?? ''),
            'locationEn' => $row['locationEn'] ?? '',
            'completionYears' => $row['completionYears'] ?? '',
            'buildingTypes' => $buildingTypes,
            'buildingTypesEn' => $buildingTypesEn,
            'prefectures' => $lang === 'ja' ? ($row['prefectures'] ?? '') : ($row['prefecturesEn'] ?? ''),
            'prefecturesEn' => $row['prefecturesEn'] ?? '',
            'has_photo' => $row['has_photo'] ?? '',
            'thumbnailUrl' => $thumbnailUrl,
            'youtubeUrl' => $row['youtubeUrl'] ?? '',
            'architects' => $architects,
            'likes' => $row['likes'] ?? 0,
            'distance' => isset($row['distance']) && $row['distance'] !== '' ? round($row['distance'], 2) : null,
            'created_at' => $row['created_at'] ?? '',
            'updated_at' => $row['updated_at'] ?? ''
        ];
    }
}
