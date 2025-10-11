<?php
/**
 * PocketNavi PHP版 - 新しいMVCアーキテクチャ
 */

// ルートの定義
require_once 'routes/web.php';

// ルーティングの実行
try {
    Router::dispatch();
} catch (Exception $e) {
    error_log("Routing error: " . $e->getMessage());
    http_response_code(500);
    echo "Internal Server Error";
}
?>
