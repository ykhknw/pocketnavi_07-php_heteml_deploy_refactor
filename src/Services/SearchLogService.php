<?php

// require_once __DIR__ . '/../Utils/Database.php'; // getDB()はconfig/database.phpで定義済み

/**
 * 検索ログ記録サービス
 */
class SearchLogService {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
        if ($this->db === null) {
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * 検索ログを記録
     */
    public function logSearch($query, $searchType = 'text', $filters = [], $userId = null) {
        // 重複防止チェック
        if ($this->isDuplicateSearch($query, $searchType)) {
            return false;
        }
        
        $sessionId = $this->getSessionId();
        $ipAddress = $this->getClientIpAddress();
        
        // 検索タイプに応じて追加情報を取得
        $additionalData = $this->getAdditionalSearchData($query, $searchType);
        $filters = array_merge($filters, $additionalData);
        
        $sql = "
            INSERT INTO global_search_history 
            (query, search_type, user_id, user_session_id, ip_address, filters) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $query,
                $searchType,
                $userId,
                $sessionId,
                $ipAddress,
                json_encode($filters)
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Search log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 検索タイプに応じて追加データを取得
     */
    private function getAdditionalSearchData($query, $searchType) {
        $additionalData = [];
        
        try {
            switch ($searchType) {
                case 'architect':
                    $architectData = $this->getArchitectDataByQuery($query);
                    if ($architectData) {
                        $additionalData = [
                            'architect_id' => $architectData['individual_architect_id'],
                            'architect_slug' => $architectData['slug'],
                            'architect_name_ja' => $architectData['name_ja'],
                            'architect_name_en' => $architectData['name_en']
                        ];
                    }
                    break;
                    
                case 'prefecture':
                    $prefectureData = $this->getPrefectureDataByQuery($query);
                    if ($prefectureData) {
                        $additionalData = [
                            'prefecture_ja' => $prefectureData['prefectures'],
                            'prefecture_en' => $prefectureData['prefecturesEn']
                        ];
                    }
                    break;
                    
                case 'building':
                    $buildingData = $this->getBuildingDataByQuery($query);
                    if ($buildingData) {
                        $additionalData = [
                            'building_id' => $buildingData['building_id'],
                            'building_slug' => $buildingData['slug'],
                            'building_title_ja' => $buildingData['title'],
                            'building_title_en' => $buildingData['titleEn']
                        ];
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log("Get additional search data error: " . $e->getMessage());
        }
        
        return $additionalData;
    }
    
    /**
     * 建築家クエリから建築家データを取得
     */
    private function getArchitectDataByQuery($query) {
        $sql = "
            SELECT individual_architect_id, name_ja, name_en, slug
            FROM individual_architects_3
            WHERE name_ja = ? OR name_en = ? OR slug = ?
            LIMIT 1
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$query, $query, $query]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get architect data by query error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 都道府県クエリから都道府県データを取得
     */
    private function getPrefectureDataByQuery($query) {
        // 都道府県名の英語→日本語変換配列
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
        
        // 日本語名から英語名を逆引き
        $englishToJapanese = array_flip($prefectureTranslations);
        
        // クエリが日本語名か英語名かを判定
        $prefectureEn = $query;
        $prefectureJa = $query;
        
        if (isset($prefectureTranslations[$query])) {
            // 英語名の場合
            $prefectureJa = $prefectureTranslations[$query];
        } elseif (isset($englishToJapanese[$query])) {
            // 日本語名の場合
            $prefectureEn = $englishToJapanese[$query];
        }
        
        return [
            'prefectures' => $prefectureJa,
            'prefecturesEn' => $prefectureEn
        ];
    }
    
    /**
     * 建築物クエリから建築物データを取得
     */
    private function getBuildingDataByQuery($query) {
        $sql = "
            SELECT building_id, title, titleEn, slug
            FROM buildings_table_3
            WHERE title = ? OR titleEn = ? OR slug = ?
            LIMIT 1
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$query, $query, $query]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get building data by query error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ページ閲覧ログを記録（建築家、建築物、都道府県ページ）
     */
    public function logPageView($pageType, $identifier, $title = '', $additionalData = []) {
        // 重複防止チェック（同一セッションで5分以内の同一ページ閲覧を制限）
        if ($this->isDuplicatePageView($pageType, $identifier)) {
            return false;
        }
        
        $sessionId = $this->getSessionId();
        $ipAddress = $this->getClientIpAddress();
        
        // ページタイプに応じて検索タイプを決定
        $searchType = $this->getSearchTypeFromPageType($pageType);
        
        // クエリ文字列を生成
        $query = $this->generateQueryFromPageType($pageType, $identifier, $title);
        
        // フィルター情報を構築
        $filters = array_merge([
            'pageType' => $pageType,
            'identifier' => $identifier,
            'title' => $title
        ], $additionalData);
        
        $sql = "
            INSERT INTO global_search_history 
            (query, search_type, user_id, user_session_id, ip_address, filters) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $query,
                $searchType,
                null, // ページ閲覧はユーザーIDなし
                $sessionId,
                $ipAddress,
                json_encode($filters)
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Page view log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ページ閲覧の重複チェック
     */
    private function isDuplicatePageView($pageType, $identifier) {
        $sessionId = $this->getSessionId();
        $ipAddress = $this->getClientIpAddress();
        
        $sql = "
            SELECT COUNT(*) as count 
            FROM global_search_history 
            WHERE JSON_EXTRACT(filters, '$.pageType') = ? 
            AND JSON_EXTRACT(filters, '$.identifier') = ?
            AND (user_session_id = ? OR ip_address = ?)
            AND searched_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pageType, $identifier, $sessionId, $ipAddress]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Duplicate page view check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ページタイプから検索タイプを取得
     */
    private function getSearchTypeFromPageType($pageType) {
        switch ($pageType) {
            case 'architect':
                return 'architect';
            case 'building':
                return 'building';
            case 'prefecture':
                return 'prefecture';
            default:
                return 'text';
        }
    }
    
    /**
     * ページタイプからクエリ文字列を生成
     */
    private function generateQueryFromPageType($pageType, $identifier, $title) {
        switch ($pageType) {
            case 'architect':
                return $title ?: $identifier;
            case 'building':
                return $title ?: $identifier;
            case 'prefecture':
                return $title ?: $identifier;
            default:
                return $identifier;
        }
    }
    
    /**
     * 重複検索チェック（5分以内の同一検索を防止）
     */
    private function isDuplicateSearch($query, $searchType) {
        $sessionId = $this->getSessionId();
        $ipAddress = $this->getClientIpAddress();
        
        $sql = "
            SELECT COUNT(*) as count 
            FROM global_search_history 
            WHERE query = ? 
            AND search_type = ? 
            AND (user_session_id = ? OR ip_address = ?)
            AND searched_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$query, $searchType, $sessionId, $ipAddress]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Duplicate check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 人気検索ワードを取得（モーダル用）
     */
    public function getPopularSearchesForModal($page = 1, $limit = 20, $searchQuery = '', $searchType = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClauses = ["searched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"];
        $params = [];
        
        // 検索クエリフィルタ
        if (!empty($searchQuery)) {
            $whereClauses[] = "query LIKE ?";
            $params[] = '%' . $searchQuery . '%';
        }
        
        // 検索タイプフィルタ
        if (!empty($searchType)) {
            $whereClauses[] = "search_type = ?";
            $params[] = $searchType;
        }
        
        $whereClause = implode(' AND ', $whereClauses);
        
        $sql = "
            SELECT 
                query,
                search_type,
                COUNT(*) as total_searches,
                COUNT(DISTINCT COALESCE(user_id, user_session_id, ip_address)) as unique_users,
                MAX(searched_at) as last_searched,
                MAX(JSON_EXTRACT(filters, '$.pageType')) as page_type,
                MAX(JSON_EXTRACT(filters, '$.identifier')) as identifier,
                MAX(JSON_EXTRACT(filters, '$.title')) as title,
                MAX(filters) as filters
            FROM global_search_history
            WHERE {$whereClause}
            GROUP BY query, search_type
            HAVING COUNT(*) >= 2
            ORDER BY 
                total_searches DESC, 
                unique_users DESC,
                last_searched DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $searches = $stmt->fetchAll();
            
            // リンク情報を追加
            foreach ($searches as &$search) {
                $search['link'] = $this->generateLinkFromSearchData($search);
            }
            
            // 総件数を取得
            $countSql = "
                SELECT COUNT(*) as total
                FROM (
                    SELECT query, search_type
                    FROM global_search_history
                    WHERE {$whereClause}
                    GROUP BY query, search_type
                    HAVING COUNT(*) >= 2
                ) as subquery
            ";
            
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2)); // LIMIT, OFFSETを除く
            $totalCount = $countStmt->fetch()['total'];
            
            return [
                'searches' => $searches,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($totalCount / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Get popular searches error: " . $e->getMessage());
            return [
                'searches' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => 0
            ];
        }
    }
    
    /**
     * 検索データからリンクを生成
     */
    private function generateLinkFromSearchData($searchData) {
        $pageType = $searchData['page_type'];
        $identifier = $searchData['identifier'];
        $searchType = $searchData['search_type'];
        $query = $searchData['query'];
        
        // ページタイプが設定されている場合（ページ閲覧ログ）
        if ($pageType && $pageType !== 'null') {
            switch ($pageType) {
                case 'architect':
                    return "/architects/{$identifier}/";
                case 'building':
                    return "/buildings/{$identifier}/";
                case 'prefecture':
                    return "/index.php?prefectures=" . urlencode($identifier);
            }
        }
        
        // 検索タイプに基づいてリンクを生成（検索ログ）
        switch ($searchType) {
            case 'architect':
                // 建築家検索の場合、filtersからslugを取得
                $filters = json_decode($searchData['filters'] ?? '{}', true);
                
                // ページ閲覧ログの場合（pageTypeが設定されている）
                if (isset($filters['pageType']) && $filters['pageType'] === 'architect') {
                    return "/architects/{$filters['identifier']}/";
                }
                
                // 検索ログの場合
                if (isset($filters['architect_slug'])) {
                    return "/architects/{$filters['architect_slug']}/";
                }
                
                // filtersにarchitect_slugがない場合、クエリから直接検索
                $architectData = $this->getArchitectDataByQuery($query);
                if ($architectData && isset($architectData['slug'])) {
                    return "/architects/{$architectData['slug']}/";
                }
                
                return "/index.php?q=" . urlencode($query) . "&type=architect";
                
            case 'prefecture':
                // 都道府県検索の場合、filtersからprefecturesEnを取得
                $filters = json_decode($searchData['filters'] ?? '{}', true);
                
                // ページ閲覧ログの場合（pageTypeが設定されている）
                if (isset($filters['pageType']) && $filters['pageType'] === 'prefecture') {
                    return "/index.php?prefectures=" . urlencode($filters['identifier'] ?? $query);
                }
                
                // 検索ログの場合
                if (isset($filters['prefecture_en'])) {
                    return "/index.php?prefectures=" . urlencode($filters['prefecture_en']);
                }
                
                // filtersにprefecture_enがない場合、日本語名から英語名に変換
                $prefectureEn = $this->convertJapaneseToEnglishPrefecture($query);
                return "/index.php?prefectures=" . urlencode($prefectureEn);
                
            case 'building':
                // 建築物検索の場合、filtersからslugを取得
                $filters = json_decode($searchData['filters'] ?? '{}', true);
                
                // ページ閲覧ログの場合
                if (isset($filters['pageType']) && $filters['pageType'] === 'building') {
                    return "/buildings/{$filters['identifier']}/";
                }
                
                // 検索ログの場合
                if (isset($filters['building_slug'])) {
                    return "/buildings/{$filters['building_slug']}/";
                }
                return "/index.php?q=" . urlencode($query) . "&type=building";
                
            default:
                return "/index.php?q=" . urlencode($query);
        }
    }
    
    /**
     * サイドバー用の人気検索ワードを取得（上位20件）
     */
    public function getPopularSearchesForSidebar($limit = 20) {
        $sql = "
            SELECT 
                query,
                search_type,
                COUNT(*) as total_searches,
                MAX(JSON_EXTRACT(filters, '$.pageType')) as page_type,
                MAX(JSON_EXTRACT(filters, '$.identifier')) as identifier,
                MAX(JSON_EXTRACT(filters, '$.title')) as title,
                MAX(filters) as filters
            FROM global_search_history
            WHERE searched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND search_type IS NOT NULL
            AND search_type != ''
            GROUP BY query, search_type
            HAVING COUNT(*) >= 1
            ORDER BY 
                total_searches DESC, 
                COUNT(DISTINCT COALESCE(user_id, user_session_id, ip_address)) DESC,
                MAX(searched_at) DESC
            LIMIT ?
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            $searches = $stmt->fetchAll();
            
            // リンク情報を追加
            foreach ($searches as &$search) {
                $search['link'] = $this->generateLinkFromSearchData($search);
                // countフィールドを追加（サイドバー表示用）
                $search['count'] = $search['total_searches'];
            }
            
            return $searches;
            
        } catch (Exception $e) {
            error_log("Get sidebar popular searches error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 日本語都道府県名から英語名に変換
     */
    private function convertJapaneseToEnglishPrefecture($japaneseName) {
        // 都道府県名の日本語→英語変換配列
        $japaneseToEnglish = [
            '北海道' => 'Hokkaido',
            '青森県' => 'Aomori',
            '岩手県' => 'Iwate',
            '宮城県' => 'Miyagi',
            '秋田県' => 'Akita',
            '山形県' => 'Yamagata',
            '福島県' => 'Fukushima',
            '茨城県' => 'Ibaraki',
            '栃木県' => 'Tochigi',
            '群馬県' => 'Gunma',
            '埼玉県' => 'Saitama',
            '千葉県' => 'Chiba',
            '東京都' => 'Tokyo',
            '神奈川県' => 'Kanagawa',
            '新潟県' => 'Niigata',
            '富山県' => 'Toyama',
            '石川県' => 'Ishikawa',
            '福井県' => 'Fukui',
            '山梨県' => 'Yamanashi',
            '長野県' => 'Nagano',
            '岐阜県' => 'Gifu',
            '静岡県' => 'Shizuoka',
            '愛知県' => 'Aichi',
            '三重県' => 'Mie',
            '滋賀県' => 'Shiga',
            '京都府' => 'Kyoto',
            '大阪府' => 'Osaka',
            '兵庫県' => 'Hyogo',
            '奈良県' => 'Nara',
            '和歌山県' => 'Wakayama',
            '鳥取県' => 'Tottori',
            '島根県' => 'Shimane',
            '岡山県' => 'Okayama',
            '広島県' => 'Hiroshima',
            '山口県' => 'Yamaguchi',
            '徳島県' => 'Tokushima',
            '香川県' => 'Kagawa',
            '愛媛県' => 'Ehime',
            '高知県' => 'Kochi',
            '福岡県' => 'Fukuoka',
            '佐賀県' => 'Saga',
            '長崎県' => 'Nagasaki',
            '熊本県' => 'Kumamoto',
            '大分県' => 'Oita',
            '宮崎県' => 'Miyazaki',
            '鹿児島県' => 'Kagoshima',
            '沖縄県' => 'Okinawa'
        ];
        
        return $japaneseToEnglish[$japaneseName] ?? $japaneseName;
    }
    
    /**
     * セッションIDを取得または生成
     */
    private function getSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['search_session_id'])) {
            $_SESSION['search_session_id'] = uniqid('search_', true);
        }
        
        return $_SESSION['search_session_id'];
    }
    
    /**
     * クライアントのIPアドレスを取得
     */
    private function getClientIpAddress() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // プロキシ経由の場合は最初のIPを使用
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}