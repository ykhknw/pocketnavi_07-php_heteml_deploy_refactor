<?php
/**
 * PocketNavi PHP版 - リファクタリングされたエントリーポイント
 * クリーンなMVCアーキテクチャとルーティングシステムを使用
 */

// エラーレポートの設定（開発環境用）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// 基本的な設定ファイルの読み込み
try {
    require_once __DIR__ . '/config/database_unified.php';
    require_once __DIR__ . '/src/Views/includes/functions.php';
} catch (Exception $e) {
    error_log("Basic config loading error: " . $e->getMessage());
    die("設定ファイルの読み込みに失敗しました: " . $e->getMessage());
}

// エラーハンドラーの初期化（オプション）
try {
    if (file_exists(__DIR__ . '/src/Utils/ErrorHandlerInitializer.php')) {
        require_once __DIR__ . '/src/Utils/ErrorHandlerInitializer.php';
        ErrorHandlerInitializer::initialize();
    }
} catch (Exception $e) {
    error_log("Error handler initialization failed: " . $e->getMessage());
    // エラーハンドラーの初期化に失敗しても続行
}

// 新しいルーティングシステムのテスト
try {
    // まず基本的な機能をテスト
    if (function_exists('searchBuildings')) {
        // 基本的な検索機能が利用可能
        $searchParams = [
            'query' => $_GET['q'] ?? '',
            'page' => (int)($_GET['page'] ?? 1),
            'hasPhotos' => isset($_GET['photos']),
            'hasVideos' => isset($_GET['videos']),
            'lang' => $_GET['lang'] ?? 'ja',
            'buildingSlug' => $_GET['building_slug'] ?? '',
            'architectsSlug' => $_GET['architects_slug'] ?? '',
            'prefectures' => $_GET['prefectures'] ?? '',
            'completionYears' => $_GET['completionYears'] ?? ''
        ];
        
        // 検索の実行
        $searchResult = performSearch($searchParams);
        
        // 人気検索の取得
        $popularSearches = getPopularSearches($searchParams['lang']);
        
        // ビューの表示
        displayRefactoredView($searchResult, $searchParams, $popularSearches);
        
    } else {
        // フォールバック: 元のindex.phpを使用
        require_once __DIR__ . '/index.php';
    }
    
} catch (Exception $e) {
    error_log("Refactored system error: " . $e->getMessage());
    
    // エラーが発生した場合は元のindex.phpにフォールバック
    try {
        require_once __DIR__ . '/index.php';
    } catch (Exception $fallbackError) {
        // フォールバックも失敗した場合
        http_response_code(500);
        echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>システムエラー - PocketNavi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .error-icon { font-size: 48px; color: #e74c3c; margin-bottom: 20px; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        p { color: #7f8c8d; line-height: 1.6; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
        .back-link:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">⚠️</div>
        <h1>システムエラーが発生しました</h1>
        <p>申し訳ございませんが、システムに一時的な問題が発生しています。</p>
        <p>しばらく時間をおいてから再度お試しください。</p>
        <a href="/" class="back-link">トップページに戻る</a>
    </div>
</body>
</html>';
    }
}

/**
 * 検索の実行
 */
function performSearch($params) {
    $limit = 10;
    
    if ($params['buildingSlug']) {
        // 建築物スラッグ検索
        $building = getBuildingBySlug($params['buildingSlug'], $params['lang']);
        if ($building) {
            return [
                'buildings' => [$building],
                'total' => 1,
                'totalPages' => 1,
                'currentPage' => 1,
                'currentBuilding' => $building
            ];
        }
        return [
            'buildings' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => 1
        ];
    } elseif ($params['architectsSlug']) {
        // 建築家スラッグ検索
        return searchBuildingsByArchitectSlug(
            $params['architectsSlug'], 
            $params['page'], 
            $params['lang'], 
            $limit, 
            $params['completionYears'], 
            $params['prefectures'], 
            $params['query']
        );
    } else {
        // 通常の検索
        return searchBuildingsWithMultipleConditions(
            $params['query'], 
            $params['completionYears'], 
            $params['prefectures'], 
            '', 
            $params['hasPhotos'], 
            $params['hasVideos'], 
            $params['page'], 
            $params['lang'], 
            $limit
        );
    }
}

/**
 * リファクタリングされたビューの表示
 */
function displayRefactoredView($searchResult, $searchParams, $popularSearches) {
    // 基本的なHTMLの出力
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $searchParams['lang']; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PocketNavi - リファクタリング版</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .refactored-badge {
                background: linear-gradient(45deg, #28a745, #20c997);
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 0.8em;
                margin-left: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h4>🚀 PocketNavi リファクタリング版</h4>
                        <p>新しいアーキテクチャで動作しています。</p>
                        <span class="refactored-badge">REFACTORED</span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- 検索フォーム -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">建築物検索</h5>
                            <form method="GET">
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" name="q" class="form-control" placeholder="キーワード" value="<?php echo htmlspecialchars($searchParams['query']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="prefectures" class="form-control" placeholder="都道府県" value="<?php echo htmlspecialchars($searchParams['prefectures']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">検索</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 検索結果 -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                検索結果 
                                <span class="badge bg-primary"><?php echo $searchResult['total']; ?>件</span>
                            </h5>
                            
                            <?php if (!empty($searchResult['buildings'])): ?>
                                <?php foreach ($searchResult['buildings'] as $building): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($building['title'] ?? ''); ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    場所: <?php echo htmlspecialchars($building['location'] ?? ''); ?><br>
                                                    完成年: <?php echo htmlspecialchars($building['completionYears'] ?? ''); ?>
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">検索結果が見つかりませんでした。</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- 人気検索 -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">人気検索</h5>
                            <?php if (!empty($popularSearches)): ?>
                                <?php foreach ($popularSearches as $search): ?>
                                    <a href="?q=<?php echo urlencode($search['query']); ?>" class="btn btn-outline-secondary btn-sm me-2 mb-2">
                                        <?php echo htmlspecialchars($search['query']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
