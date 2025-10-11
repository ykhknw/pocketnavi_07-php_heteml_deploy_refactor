<?php

/**
 * 環境変数読み込みユーティリティ
 * .envファイルから環境変数を読み込み、getenv()でアクセス可能にする
 */
class EnvironmentLoader {
    private static $loaded = false;
    private static $envFile = null;
    
    /**
     * 環境変数の読み込み
     * @param string $envFile .envファイルのパス
     */
    public static function load($envFile = null) {
        if (self::$loaded) {
            return;
        }
        
        // デフォルトの.envファイルパスを設定
        if ($envFile === null) {
            $envFile = self::findEnvFile();
        }
        
        if ($envFile && file_exists($envFile)) {
            self::$envFile = $envFile;
            self::parseEnvFile($envFile);
        }
        
        self::$loaded = true;
    }
    
    /**
     * .envファイルを検索
     * @return string|null
     */
    private static function findEnvFile() {
        $possiblePaths = [
            __DIR__ . '/../../.env',
            __DIR__ . '/../../config/.env',
            getcwd() . '/.env',
            getcwd() . '/config/.env'
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * .envファイルを解析して環境変数を設定
     * @param string $envFile
     */
    private static function parseEnvFile($envFile) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // コメント行をスキップ
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // キー=値の形式を解析
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // クォートを除去
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // 環境変数が設定されていない場合のみ設定
                if (getenv($key) === false) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
    
    /**
     * 環境変数の取得（デフォルト値付き）
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
    
    /**
     * 環境変数の取得（必須）
     * @param string $key
     * @return string
     * @throws Exception
     */
    public static function getRequired($key) {
        $value = getenv($key);
        if ($value === false) {
            throw new Exception("Required environment variable '$key' is not set");
        }
        return $value;
    }
    
    /**
     * 環境変数の設定
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
    
    /**
     * 環境変数の存在確認
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        return getenv($key) !== false;
    }
    
    /**
     * 読み込まれた.envファイルのパスを取得
     * @return string|null
     */
    public static function getEnvFile() {
        return self::$envFile;
    }
    
    /**
     * 環境変数の一覧を取得（デバッグ用）
     * @return array
     */
    public static function getAll() {
        return $_ENV;
    }
}
