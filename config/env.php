<?php
// 環境変数設定ファイル（ヘテムル対応版）

// 環境変数のデフォルト値を定義
$envDefaults = [
    // アプリケーション設定
    'APP_NAME' => 'PocketNavi',
    'APP_ENV' => 'local',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'http://localhost',
    'APP_TIMEZONE' => 'Asia/Tokyo',
    'APP_LOCALE' => 'ja',
    'APP_FALLBACK_LOCALE' => 'en',
    
    // データベース設定
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_DATABASE' => '_shinkenchiku_db',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    
    // ログ設定
    'LOG_LEVEL' => 'debug',
    'LOG_CHANNEL' => 'file',
    
    // キャッシュ設定
    'CACHE_DRIVER' => 'file',
    'SESSION_DRIVER' => 'file',
    
    // セキュリティ設定
    'SESSION_LIFETIME' => '120',
    'SESSION_SECURE' => 'false',
    'SESSION_HTTP_ONLY' => 'true',
    'SESSION_SAME_SITE' => 'lax',
    
    // メール設定（将来の拡張用）
    'MAIL_MAILER' => 'smtp',
    'MAIL_HOST' => 'localhost',
    'MAIL_PORT' => '587',
    'MAIL_USERNAME' => '',
    'MAIL_PASSWORD' => '',
    'MAIL_ENCRYPTION' => 'null',
    'MAIL_FROM_ADDRESS' => 'noreply@pocketnavi.com',
    'MAIL_FROM_NAME' => 'PocketNavi',
    
    // 外部API設定（将来の拡張用）
    'GOOGLE_MAPS_API_KEY' => '',
    'YOUTUBE_API_KEY' => '',
    
    // パフォーマンス設定
    'MAX_EXECUTION_TIME' => '30',
    'MEMORY_LIMIT' => '256M',
    'UPLOAD_MAX_FILESIZE' => '10M',
    'POST_MAX_SIZE' => '10M'
];

// 環境変数を設定（存在しない場合はデフォルト値を使用）
foreach ($envDefaults as $key => $defaultValue) {
    if (!getenv($key)) {
        putenv("$key=$defaultValue");
    }
}

// ヘテムル環境の検出と設定
function isHETEML() {
    return isset($_SERVER['HTTP_HOST']) && 
           (strpos($_SERVER['HTTP_HOST'], 'heteml.net') !== false || 
            strpos($_SERVER['HTTP_HOST'], 'heteml.com') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'heteml.jp') !== false);
}

// ヘテムル環境の場合はデータベース設定を変更
if (isHETEML()) {
    putenv('DB_DATABASE=_shinkenchiku_02');
    putenv('APP_ENV=production');
    putenv('APP_DEBUG=false');
    putenv('LOG_LEVEL=info');
}
