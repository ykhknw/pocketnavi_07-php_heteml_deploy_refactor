<?php

/**
 * 統一設定管理クラス
 * 環境変数と設定ファイルを統合管理
 */
class ConfigManager {
    
    private static $instance = null;
    private $config = [];
    private $loaded = false;
    
    private function __construct() {
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 設定の読み込み
     */
    private function loadConfig() {
        if ($this->loaded) {
            return;
        }
        
        // 環境変数から設定を読み込み
        $this->config = [
            'APP_NAME' => $_ENV['APP_NAME'] ?? 'PocketNavi',
            'APP_ENV' => $_ENV['APP_ENV'] ?? 'development',
            'APP_DEBUG' => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'APP_URL' => $_ENV['APP_URL'] ?? 'http://localhost',
            'APP_TIMEZONE' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Tokyo',
            'APP_LOCALE' => $_ENV['APP_LOCALE'] ?? 'ja',
            'APP_FALLBACK_LOCALE' => $_ENV['APP_FALLBACK_LOCALE'] ?? 'ja',
            
            // データベース設定
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
            'DB_NAME' => $_ENV['DB_NAME'] ?? '_shinkenchiku_02',
            'DB_USERNAME' => $_ENV['DB_USERNAME'] ?? 'root',
            'DB_PASSWORD' => $_ENV['DB_PASSWORD'] ?? '',
            'DB_PORT' => $_ENV['DB_PORT'] ?? '3306',
            'DB_CHARSET' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            
            // セッション設定
            'SESSION_LIFETIME' => $_ENV['SESSION_LIFETIME'] ?? '7200',
            'SESSION_SECURE' => filter_var($_ENV['SESSION_SECURE'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
            'SESSION_HTTP_ONLY' => filter_var($_ENV['SESSION_HTTP_ONLY'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'SESSION_SAME_SITE' => $_ENV['SESSION_SAME_SITE'] ?? 'lax',
            
            // パフォーマンス設定
            'MAX_EXECUTION_TIME' => $_ENV['MAX_EXECUTION_TIME'] ?? '30',
            'MEMORY_LIMIT' => $_ENV['MEMORY_LIMIT'] ?? '512M',
            'UPLOAD_MAX_FILESIZE' => $_ENV['UPLOAD_MAX_FILESIZE'] ?? '10M',
            'POST_MAX_SIZE' => $_ENV['POST_MAX_SIZE'] ?? '10M',
            
            // ログ設定
            'LOG_LEVEL' => $_ENV['LOG_LEVEL'] ?? 'info',
            'LOG_FILE' => $_ENV['LOG_FILE'] ?? 'logs/application.log',
            
            // セキュリティ設定
            'APP_KEY' => $_ENV['APP_KEY'] ?? 'default-secret-key-change-in-production',
            'CSRF_PROTECTION' => filter_var($_ENV['CSRF_PROTECTION'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'RATE_LIMITING' => filter_var($_ENV['RATE_LIMITING'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'SECURITY_HEADERS' => filter_var($_ENV['SECURITY_HEADERS'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        ];
        
        $this->loaded = true;
    }
    
    /**
     * 設定値の取得
     */
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * 設定値の設定
     */
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * 設定値の存在確認
     */
    public function has($key) {
        return array_key_exists($key, $this->config);
    }
    
    /**
     * 全設定の取得
     */
    public function all() {
        return $this->config;
    }
    
    /**
     * 設定情報の取得
     */
    public function getInfo() {
        return [
            'loaded' => $this->loaded,
            'config_count' => count($this->config),
            'environment' => $this->get('APP_ENV'),
            'debug_mode' => $this->get('APP_DEBUG'),
            'database' => $this->get('DB_NAME'),
            'timezone' => $this->get('APP_TIMEZONE'),
            'locale' => $this->get('APP_LOCALE')
        ];
    }
    
    /**
     * 環境が本番かどうか
     */
    public function isProduction() {
        return $this->get('APP_ENV') === 'production';
    }
    
    /**
     * デバッグモードかどうか
     */
    public function isDebug() {
        return $this->get('APP_DEBUG') === true;
    }
    
    /**
     * データベース設定の取得
     */
    public function getDatabaseConfig() {
        return [
            'host' => $this->get('DB_HOST'),
            'dbname' => $this->get('DB_NAME'),
            'username' => $this->get('DB_USERNAME'),
            'password' => $this->get('DB_PASSWORD'),
            'port' => $this->get('DB_PORT'),
            'charset' => $this->get('DB_CHARSET'),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    }
    
    /**
     * セキュリティ設定の取得
     */
    public function getSecurityConfig() {
        return [
            'csrf_protection' => $this->get('CSRF_PROTECTION'),
            'rate_limiting' => $this->get('RATE_LIMITING'),
            'security_headers' => $this->get('SECURITY_HEADERS'),
            'session_lifetime' => $this->get('SESSION_LIFETIME'),
            'session_secure' => $this->get('SESSION_SECURE'),
            'session_http_only' => $this->get('SESSION_HTTP_ONLY'),
            'session_same_site' => $this->get('SESSION_SAME_SITE'),
            'app_key' => $this->get('APP_KEY')
        ];
    }
    
    /**
     * パフォーマンス設定の取得
     */
    public function getPerformanceConfig() {
        return [
            'max_execution_time' => $this->get('MAX_EXECUTION_TIME'),
            'memory_limit' => $this->get('MEMORY_LIMIT'),
            'upload_max_filesize' => $this->get('UPLOAD_MAX_FILESIZE'),
            'post_max_size' => $this->get('POST_MAX_SIZE')
        ];
    }
    
    /**
     * 設定のリロード
     */
    public function reload() {
        $this->loaded = false;
        $this->config = [];
        $this->loadConfig();
    }
}