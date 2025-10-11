<?php

/**
 * 統一アプリケーション設定
 * 全アプリケーション設定の中央管理
 */

// 設定管理クラスの読み込み
require_once __DIR__ . '/../src/Utils/ConfigManager.php';

// グローバル設定関数
function config($key = null, $default = null) {
    $configManager = ConfigManager::getInstance();
    
    if ($key === null) {
        return $configManager->all();
    }
    
    return $configManager->get($key, $default);
}

// 設定値の設定
function config_set($key, $value) {
    $configManager = ConfigManager::getInstance();
    $configManager->set($key, $value);
}

// 設定値の存在確認
function config_has($key) {
    $configManager = ConfigManager::getInstance();
    return $configManager->has($key);
}

// 環境判定関数
function is_production() {
    $configManager = ConfigManager::getInstance();
    return $configManager->isProduction();
}

function is_development() {
    $configManager = ConfigManager::getInstance();
    return !$configManager->isProduction();
}

function is_debug() {
    $configManager = ConfigManager::getInstance();
    return $configManager->isDebug();
}

// データベース設定の取得
function database_config() {
    $configManager = ConfigManager::getInstance();
    return $configManager->getDatabaseConfig();
}

// セキュリティ設定の取得
function security_config() {
    $configManager = ConfigManager::getInstance();
    return $configManager->getSecurityConfig();
}

// パフォーマンス設定の取得
function performance_config() {
    $configManager = ConfigManager::getInstance();
    return $configManager->getPerformanceConfig();
}

// 設定情報の取得
function config_info() {
    $configManager = ConfigManager::getInstance();
    return $configManager->getInfo();
}

// 設定のリロード
function config_reload() {
    $configManager = ConfigManager::getInstance();
    $configManager->reload();
}

// 設定値の検証
function config_validate($config = null) {
    require_once __DIR__ . '/../src/Utils/ConfigValidator.php';
    
    if ($config === null) {
        $configManager = ConfigManager::getInstance();
        $config = $configManager->all();
    }
    
    return ConfigValidator::validate($config);
}

// アプリケーション設定の初期化
$configManager = ConfigManager::getInstance();

// 定数の定義
if (!defined('APP_NAME')) {
    define('APP_NAME', $configManager->get('APP_NAME', 'PocketNavi'));
}

if (!defined('APP_ENV')) {
    define('APP_ENV', $configManager->get('APP_ENV', 'development'));
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', $configManager->get('APP_DEBUG', true));
}

if (!defined('APP_URL')) {
    define('APP_URL', $configManager->get('APP_URL', 'http://localhost'));
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', $configManager->get('APP_TIMEZONE', 'Asia/Tokyo'));
}

if (!defined('APP_LOCALE')) {
    define('APP_LOCALE', $configManager->get('APP_LOCALE', 'ja'));
}

// データベース定数の定義
if (!defined('DB_HOST')) {
    define('DB_HOST', $configManager->get('DB_HOST', 'localhost'));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', $configManager->get('DB_NAME', '_shinkenchiku_02'));
}

if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', $configManager->get('DB_USERNAME', 'root'));
}

if (!defined('DB_PASS')) {
    define('DB_PASS', $configManager->get('DB_PASSWORD', ''));
}

if (!defined('DB_PORT')) {
    define('DB_PORT', $configManager->get('DB_PORT', '3306'));
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', $configManager->get('DB_CHARSET', 'utf8mb4'));
}

// セッション設定
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', $configManager->get('SESSION_LIFETIME', '7200'));
}

if (!defined('SESSION_SECURE')) {
    define('SESSION_SECURE', $configManager->get('SESSION_SECURE', false));
}

if (!defined('SESSION_HTTP_ONLY')) {
    define('SESSION_HTTP_ONLY', $configManager->get('SESSION_HTTP_ONLY', true));
}

if (!defined('SESSION_SAME_SITE')) {
    define('SESSION_SAME_SITE', $configManager->get('SESSION_SAME_SITE', 'lax'));
}

// パフォーマンス設定
if (!defined('MAX_EXECUTION_TIME')) {
    define('MAX_EXECUTION_TIME', $configManager->get('MAX_EXECUTION_TIME', '30'));
}

if (!defined('MEMORY_LIMIT')) {
    define('MEMORY_LIMIT', $configManager->get('MEMORY_LIMIT', '512M'));
}

if (!defined('UPLOAD_MAX_FILESIZE')) {
    define('UPLOAD_MAX_FILESIZE', $configManager->get('UPLOAD_MAX_FILESIZE', '10M'));
}

if (!defined('POST_MAX_SIZE')) {
    define('POST_MAX_SIZE', $configManager->get('POST_MAX_SIZE', '10M'));
}

// ログ設定
if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', $configManager->get('LOG_LEVEL', 'info'));
}

if (!defined('LOG_FILE')) {
    define('LOG_FILE', $configManager->get('LOG_FILE', 'logs/application.log'));
}

// セキュリティ設定
if (!defined('APP_KEY')) {
    define('APP_KEY', $configManager->get('APP_KEY', 'default-secret-key-change-in-production'));
}

if (!defined('CSRF_PROTECTION')) {
    define('CSRF_PROTECTION', $configManager->get('CSRF_PROTECTION', true));
}

if (!defined('RATE_LIMITING')) {
    define('RATE_LIMITING', $configManager->get('RATE_LIMITING', true));
}

if (!defined('SECURITY_HEADERS')) {
    define('SECURITY_HEADERS', $configManager->get('SECURITY_HEADERS', true));
}