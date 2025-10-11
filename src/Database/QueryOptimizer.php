<?php

/**
 * データベースクエリ最適化システム
 * クエリの分析、最適化、キャッシュを提供
 */
class QueryOptimizer {
    
    private static $instance = null;
    private $cache;
    private $queryStats = [];
    private $slowQueries = [];
    
    private function __construct() {
        $this->cache = CacheManager::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 最適化されたクエリを実行
     */
    public function execute($sql, $params = [], $cacheTtl = 300) {
        $startTime = microtime(true);
        
        // クエリの正規化
        $normalizedSql = $this->normalizeQuery($sql);
        $cacheKey = $this->generateCacheKey($normalizedSql, $params);
        
        // キャッシュから取得を試行
        if ($cacheTtl > 0) {
            $cachedResult = $this->cache->get($cacheKey);
            if ($cachedResult !== null) {
                $this->recordQueryStats($normalizedSql, microtime(true) - $startTime, true);
                return $cachedResult;
            }
        }
        
        // データベースから実行
        try {
            $db = getDB();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            $executionTime = microtime(true) - $startTime;
            
            // クエリ統計の記録
            $this->recordQueryStats($normalizedSql, $executionTime, false);
            
            // 遅いクエリの記録
            if ($executionTime > 0.1) { // 100ms以上
                $this->recordSlowQuery($normalizedSql, $executionTime, $params);
            }
            
            // キャッシュに保存
            if ($cacheTtl > 0) {
                $this->cache->set($cacheKey, $result, $cacheTtl);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Query execution error: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * クエリの最適化提案を取得
     */
    public function getOptimizationSuggestions($sql) {
        $suggestions = [];
        
        // SELECT * の検出
        if (preg_match('/SELECT\s+\*\s+FROM/i', $sql)) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'SELECT * の使用を避け、必要なカラムのみを指定してください',
                'impact' => 'medium'
            ];
        }
        
        // インデックスが推奨される条件の検出
        if (preg_match('/WHERE\s+(\w+)\s*=\s*\?/i', $sql, $matches)) {
            $column = $matches[1];
            $suggestions[] = [
                'type' => 'info',
                'message' => "カラム '{$column}' にインデックスが設定されていることを確認してください",
                'impact' => 'high'
            ];
        }
        
        // LIKE クエリの最適化提案
        if (preg_match('/WHERE\s+(\w+)\s+LIKE\s+[\'"]%[^\'"]*[\'"]/i', $sql, $matches)) {
            $column = $matches[1];
            $suggestions[] = [
                'type' => 'warning',
                'message' => "カラム '{$column}' の前方一致検索はインデックスが効きません。全文検索の使用を検討してください",
                'impact' => 'medium'
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * クエリ統計を取得
     */
    public function getQueryStats() {
        return [
            'total_queries' => count($this->queryStats),
            'slow_queries' => count($this->slowQueries),
            'average_time' => $this->calculateAverageTime(),
            'most_frequent' => $this->getMostFrequentQueries(),
            'slowest_queries' => $this->getSlowestQueries()
        ];
    }
    
    /**
     * クエリを正規化
     */
    private function normalizeQuery($sql) {
        // 空白を正規化
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        
        // パラメータを正規化
        $sql = preg_replace('/\?/', '?', $sql);
        
        // 文字列リテラルを正規化
        $sql = preg_replace('/[\'"][^\'"]*[\'"]/', '?', $sql);
        
        return $sql;
    }
    
    /**
     * キャッシュキーを生成
     */
    private function generateCacheKey($sql, $params) {
        $key = 'query_' . md5($sql . serialize($params));
        return $key;
    }
    
    /**
     * クエリ統計を記録
     */
    private function recordQueryStats($sql, $executionTime, $fromCache) {
        $key = md5($sql);
        
        if (!isset($this->queryStats[$key])) {
            $this->queryStats[$key] = [
                'sql' => $sql,
                'count' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'cache_hits' => 0,
                'last_executed' => time()
            ];
        }
        
        $this->queryStats[$key]['count']++;
        $this->queryStats[$key]['total_time'] += $executionTime;
        $this->queryStats[$key]['avg_time'] = $this->queryStats[$key]['total_time'] / $this->queryStats[$key]['count'];
        $this->queryStats[$key]['last_executed'] = time();
        
        if ($fromCache) {
            $this->queryStats[$key]['cache_hits']++;
        }
    }
    
    /**
     * 遅いクエリを記録
     */
    private function recordSlowQuery($sql, $executionTime, $params) {
        $this->slowQueries[] = [
            'sql' => $sql,
            'execution_time' => $executionTime,
            'params' => $params,
            'timestamp' => time()
        ];
        
        // 最新の100件のみ保持
        if (count($this->slowQueries) > 100) {
            array_shift($this->slowQueries);
        }
    }
    
    /**
     * 平均実行時間を計算
     */
    private function calculateAverageTime() {
        if (empty($this->queryStats)) {
            return 0;
        }
        
        $totalTime = 0;
        $totalCount = 0;
        
        foreach ($this->queryStats as $stats) {
            $totalTime += $stats['total_time'];
            $totalCount += $stats['count'];
        }
        
        return $totalCount > 0 ? $totalTime / $totalCount : 0;
    }
    
    /**
     * 最も頻繁に実行されるクエリを取得
     */
    private function getMostFrequentQueries() {
        $queries = $this->queryStats;
        usort($queries, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return array_slice($queries, 0, 5);
    }
    
    /**
     * 最も遅いクエリを取得
     */
    private function getSlowestQueries() {
        $queries = $this->queryStats;
        usort($queries, function($a, $b) {
            return $b['avg_time'] - $a['avg_time'];
        });
        
        return array_slice($queries, 0, 5);
    }
}
