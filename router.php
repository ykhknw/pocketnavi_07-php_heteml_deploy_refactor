<?php
// PHP開発サーバー用のルーターファイル

// リクエストされたパスを取得
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// クエリパラメータを取得
$queryString = parse_url($requestUri, PHP_URL_QUERY);

// パスの書き換えルール
$rewriteRules = [
    // 建築家ページ: /architects/slug -> /index.php?architects_slug=slug
    '/^\/architects\/([^\/]+)\/?$/' => 'index.php?architects_slug=$1',
    
    // 建築物ページ: /buildings/slug -> /index.php?building_slug=slug
    '/^\/buildings\/([^\/]+)\/?$/' => 'index.php?building_slug=$1',
    
    // メインページ: / -> /index.php
    '/^\/$/' => 'index.php'
];

// パスにマッチするルールを探す
foreach ($rewriteRules as $pattern => $replacement) {
    if (preg_match($pattern, $path, $matches)) {
        // マッチした場合、置換を実行
        $newPath = preg_replace($pattern, $replacement, $path);
        
        // クエリパラメータがある場合は追加
        if ($queryString) {
            $newPath .= '&' . $queryString;
        }
        
        // 新しいパスでファイルを読み込み
        if (file_exists($newPath)) {
            include $newPath;
            return true;
        }
    }
}

// マッチしない場合は、元のファイルを探す
if (file_exists($path)) {
    return false; // 通常のファイル処理に任せる
}

// 404エラー
http_response_code(404);
echo "404 Not Found";
return true;
?>
