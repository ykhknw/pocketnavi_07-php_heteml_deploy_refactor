<?php

/**
 * 統一されたデータベース設定ファイル
 * 環境変数を使用して設定を管理
 */

// エラーハンドリングの初期化
require_once __DIR__ . '/../src/Utils/ErrorHandlerInitializer.php';
ErrorHandlerInitializer::initialize();

// 環境変数の読み込み
require_once __DIR__ . '/../src/Utils/EnvironmentLoader.php';
EnvironmentLoader::load();

// 統一されたデータベース接続クラスの読み込み
require_once __DIR__ . '/../src/Utils/DatabaseConnection.php';
require_once __DIR__ . '/../src/Utils/DatabaseHelper.php';

/**
 * データベース設定の取得
 * @return array
 */
function getDatabaseConfig() {
    return [
        'host' => EnvironmentLoader::get('DB_HOST', 'localhost'),
        'dbname' => EnvironmentLoader::get('DB_NAME', '_shinkenchiku_02'),
        'username' => EnvironmentLoader::get('DB_USERNAME', 'root'),
        'password' => EnvironmentLoader::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    ];
}

// 初期化時にデータベース接続をテスト（開発環境のみ）
if (EnvironmentLoader::get('APP_ENV', 'production') === 'development') {
    // 開発環境でのみ接続テストを実行
    if (function_exists('testDatabaseConnection') && !testDatabaseConnection()) {
        error_log("Warning: Database connection test failed during initialization");
    }
}