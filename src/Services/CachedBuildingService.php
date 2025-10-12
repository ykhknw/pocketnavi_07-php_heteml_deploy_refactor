<?php
/**
 * キャッシュ機能付き建築物検索サービス
 * 既存のBuildingServiceを拡張してキャッシュ機能を追加
 */

require_once __DIR__ . '/../Cache/SearchResultCache.php';
require_once __DIR__ . '/BuildingService.php';

class CachedBuildingService extends BuildingService {
    private $cache;
    private $cacheEnabled;
    
    public function __construct($cacheEnabled = true, $cacheTTL = 3600) {
        parent::__construct();
        
        $this->cacheEnabled = $cacheEnabled;
        $this->cache = new SearchResultCache('cache/search', $cacheTTL, $cacheEnabled);
    }
    
    /**
     * キャッシュ機能付き検索
     */
    public function search($query, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
        $params = [
            'query' => $query,
            'page' => $page,
            'hasPhotos' => $hasPhotos,
            'hasVideos' => $hasVideos,
            'lang' => $lang,
            'limit' => $limit
        ];
        
        // キャッシュが有効な場合のみキャッシュから取得を試行
        if ($this->cacheEnabled) {
            $cached = $this->cache->get($params);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // キャッシュがない場合は既存の検索を実行
        $result = parent::search($query, $page, $hasPhotos, $hasVideos, $lang, $limit);
        
        // 結果をキャッシュに保存（キャッシュが無効でも_cache_infoを追加）
        $this->cache->set($params, $result);
        
        return $result;
    }
    
    /**
     * キャッシュ機能付き複数条件検索
     */
    public function searchWithMultipleConditions($query, $completionYears, $prefectures, $buildingTypes, $hasPhotos, $hasVideos, $page = 1, $lang = 'ja', $limit = 10) {
        $params = [
            'query' => $query,
            'completionYears' => $completionYears,
            'prefectures' => $prefectures,
            'buildingTypes' => $buildingTypes,
            'hasPhotos' => $hasPhotos,
            'hasVideos' => $hasVideos,
            'page' => $page,
            'lang' => $lang,
            'limit' => $limit
        ];
        
        // キャッシュが有効な場合のみキャッシュから取得を試行
        if ($this->cacheEnabled) {
            $cached = $this->cache->get($params);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // キャッシュがない場合は既存の検索を実行
        $result = parent::searchWithMultipleConditions($query, $completionYears, $prefectures, $buildingTypes, $hasPhotos, $hasVideos, $page, $lang, $limit);
        
        // 結果をキャッシュに保存（キャッシュが無効でも_cache_infoを追加）
        $this->cache->set($params, $result);
        
        return $result;
    }
    
    /**
     * キャッシュ機能付き位置情報検索
     */
    public function searchByLocation($userLat, $userLng, $radiusKm = 5, $page = 1, $hasPhotos = false, $hasVideos = false, $lang = 'ja', $limit = 10) {
        $params = [
            'userLat' => $userLat,
            'userLng' => $userLng,
            'radiusKm' => $radiusKm,
            'page' => $page,
            'hasPhotos' => $hasPhotos,
            'hasVideos' => $hasVideos,
            'lang' => $lang,
            'limit' => $limit
        ];
        
        // キャッシュが有効な場合のみキャッシュから取得を試行
        if ($this->cacheEnabled) {
            $cached = $this->cache->get($params);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // キャッシュがない場合は既存の検索を実行
        $result = parent::searchByLocation($userLat, $userLng, $radiusKm, $page, $hasPhotos, $hasVideos, $lang, $limit);
        
        // 結果をキャッシュに保存（キャッシュが無効でも_cache_infoを追加）
        $this->cache->set($params, $result);
        
        return $result;
    }
    
    /**
     * キャッシュ機能付き建築家検索
     */
    public function searchByArchitectSlug($architectSlug, $page = 1, $lang = 'ja', $limit = 10) {
        $params = [
            'architectSlug' => $architectSlug,
            'page' => $page,
            'lang' => $lang,
            'limit' => $limit
        ];
        
        // キャッシュが有効な場合のみキャッシュから取得を試行
        if ($this->cacheEnabled) {
            $cached = $this->cache->get($params);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // キャッシュがない場合は既存の検索を実行
        $result = parent::searchByArchitectSlug($architectSlug, $page, $lang, $limit);
        
        // 結果をキャッシュに保存（キャッシュが無効でも_cache_infoを追加）
        $this->cache->set($params, $result);
        
        return $result;
    }
    
    /**
     * キャッシュ機能付きスラッグ検索
     */
    public function getBySlug($slug, $lang = 'ja') {
        $params = [
            'buildingSlug' => $slug,
            'lang' => $lang
        ];
        
        // キャッシュが有効な場合のみキャッシュから取得を試行
        if ($this->cacheEnabled) {
            $cached = $this->cache->get($params);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // キャッシュがない場合は既存の検索を実行
        $result = parent::getBySlug($slug, $lang);
        
        // 結果をキャッシュに保存（キャッシュが無効でも_cache_infoを追加）
        $this->cache->set($params, $result);
        
        return $result;
    }
    
    /**
     * キャッシュの有効/無効切り替え
     */
    public function setCacheEnabled($enabled) {
        $this->cacheEnabled = $enabled;
        $this->cache->setEnabled($enabled);
    }
    
    /**
     * キャッシュが有効かどうか
     */
    public function isCacheEnabled() {
        return $this->cacheEnabled;
    }
    
    /**
     * キャッシュの統計情報を取得
     */
    public function getCacheStats() {
        return $this->cache->getStats();
    }
    
    /**
     * キャッシュのクリア
     */
    public function clearCache() {
        return $this->cache->clear();
    }
    
    /**
     * 特定のパラメータのキャッシュを削除
     */
    public function deleteCache($params) {
        return $this->cache->delete($params);
    }
}
