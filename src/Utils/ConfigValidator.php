<?php

/**
 * 設定値検証クラス
 */
class ConfigValidator {
    private static $rules = [
        // アプリケーション設定
        'app.name' => 'required|string|min:1|max:100',
        'app.env' => 'required|string|in:local,development,staging,production',
        'app.debug' => 'boolean',
        'app.url' => 'required|url',
        'app.timezone' => 'required|string|timezone',
        'app.locale' => 'required|string|in:ja,en',
        'app.fallback_locale' => 'required|string|in:ja,en',
        
        // データベース設定
        'database.connections.mysql.host' => 'required|string|min:1',
        'database.connections.mysql.port' => 'required|integer|min:1|max:65535',
        'database.connections.mysql.database' => 'required|string|min:1',
        'database.connections.mysql.username' => 'required|string|min:1',
        'database.connections.mysql.password' => 'string',
        'database.connections.mysql.charset' => 'required|string|in:utf8,utf8mb4',
        
        // ログ設定
        'logging.default_level' => 'required|string|in:debug,info,warning,error,critical,DEBUG,INFO,WARNING,ERROR,CRITICAL',
        
        // セッション設定
        'app.session.lifetime' => 'required|integer|min:1|max:1440',
        'app.session.secure' => 'boolean',
        'app.session.http_only' => 'boolean',
        'app.session.same_site' => 'required|string|in:lax,strict,none',
        
        // パフォーマンス設定
        'app.performance.max_execution_time' => 'required|integer|min:1|max:300',
        'app.performance.memory_limit' => 'required|string|memory_limit',
        'app.performance.upload_max_filesize' => 'required|string|file_size',
        'app.performance.post_max_size' => 'required|string|file_size',
    ];
    
    /**
     * 設定値を検証
     */
    public static function validate($config) {
        $errors = [];
        
        foreach (self::$rules as $key => $rule) {
            $value = self::getNestedValue($config, $key);
            $errors = array_merge($errors, self::validateValue($key, $value, $rule));
        }
        
        if (!empty($errors)) {
            throw new InvalidConfigurationException('設定値の検証に失敗しました: ' . implode(', ', $errors));
        }
        
        return true;
    }
    
    /**
     * ネストされた配列から値を取得
     */
    private static function getNestedValue($array, $key) {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * 値を検証
     */
    private static function validateValue($key, $value, $rule) {
        $errors = [];
        $rules = explode('|', $rule);
        
        foreach ($rules as $r) {
            $error = self::applyRule($key, $value, $r);
            if ($error) {
                $errors[] = $error;
            }
        }
        
        return $errors;
    }
    
    /**
     * ルールを適用
     */
    private static function applyRule($key, $value, $rule) {
        if (strpos($rule, ':') !== false) {
            list($ruleName, $ruleValue) = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $ruleValue = null;
        }
        
        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '') {
                    return "{$key} は必須です";
                }
                break;
                
            case 'string':
                if ($value !== null && !is_string($value)) {
                    return "{$key} は文字列である必要があります";
                }
                break;
                
            case 'integer':
                if ($value !== null && !is_int($value)) {
                    return "{$key} は整数である必要があります";
                }
                break;
                
            case 'boolean':
                if ($value !== null && !is_bool($value)) {
                    return "{$key} は真偽値である必要があります";
                }
                break;
                
            case 'min':
                if ($value !== null) {
                    if (is_string($value) && strlen($value) < (int)$ruleValue) {
                        return "{$key} は {$ruleValue} 文字以上である必要があります";
                    } elseif (is_numeric($value) && $value < (int)$ruleValue) {
                        return "{$key} は {$ruleValue} 以上である必要があります";
                    }
                }
                break;
                
            case 'max':
                if ($value !== null) {
                    if (is_string($value) && strlen($value) > (int)$ruleValue) {
                        return "{$key} は {$ruleValue} 文字以下である必要があります";
                    } elseif (is_numeric($value) && $value > (int)$ruleValue) {
                        return "{$key} は {$ruleValue} 以下である必要があります";
                    }
                }
                break;
                
            case 'in':
                if ($value !== null) {
                    $allowedValues = explode(',', $ruleValue);
                    if (!in_array($value, $allowedValues)) {
                        return "{$key} は " . implode(', ', $allowedValues) . " のいずれかである必要があります";
                    }
                }
                break;
                
            case 'url':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$key} は有効なURLである必要があります";
                }
                break;
                
            case 'timezone':
                if ($value !== null && !in_array($value, timezone_identifiers_list())) {
                    return "{$key} は有効なタイムゾーンである必要があります";
                }
                break;
                
            case 'memory_limit':
                if ($value !== null && !self::isValidMemoryLimit($value)) {
                    return "{$key} は有効なメモリ制限値である必要があります";
                }
                break;
                
            case 'file_size':
                if ($value !== null && !self::isValidFileSize($value)) {
                    return "{$key} は有効なファイルサイズ値である必要があります";
                }
                break;
        }
        
        return null;
    }
    
    /**
     * メモリ制限値が有効かチェック
     */
    private static function isValidMemoryLimit($value) {
        if (!is_string($value)) return false;
        
        $pattern = '/^(\d+)([KMG]?)$/i';
        return preg_match($pattern, $value);
    }
    
    /**
     * ファイルサイズ値が有効かチェック
     */
    private static function isValidFileSize($value) {
        if (!is_string($value)) return false;
        
        $pattern = '/^(\d+)([KMG]?)$/i';
        return preg_match($pattern, $value);
    }
}

/**
 * 設定例外クラス
 */
class InvalidConfigurationException extends Exception {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
