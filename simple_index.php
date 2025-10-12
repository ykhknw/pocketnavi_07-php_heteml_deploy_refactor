<?php
/**
 * 簡単なメインページ
 * 複雑なルーティングシステムを使わずに直接表示
 */

// エラー表示を有効にする（ローカル環境）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// メモリ制限を増やす
ini_set('memory_limit', '512M');

// 実行時間制限を増やす
set_time_limit(300);

try {
    // 環境設定の読み込み
    require_once __DIR__ . '/src/Utils/EnvironmentLoader.php';
    $envLoader = new EnvironmentLoader();
    $config = $envLoader->load();
    
    // データベース接続
    require_once __DIR__ . '/config/database_unified.php';
    $pdo = getDatabaseConnection();
    
    // 翻訳関数の読み込み
    require_once __DIR__ . '/src/Utils/Translation.php';
    
    // 検索パラメータの取得
    $query = $_GET['q'] ?? '';
    $prefectures = $_GET['prefectures'] ?? '';
    $completionYears = $_GET['completionYears'] ?? '';
    $buildingTypes = $_GET['buildingTypes'] ?? '';
    $hasPhotos = isset($_GET['photos']);
    $hasVideos = isset($_GET['videos']);
    $page = (int)($_GET['page'] ?? 1);
    $lang = $_GET['lang'] ?? 'ja';
    $limit = 10;
    
    // 基本的な検索クエリ
    $sql = "SELECT * FROM buildings_table_3 WHERE 1=1";
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    
    if (!empty($prefectures)) {
        $sql .= " AND location LIKE ?";
        $params[] = "%$prefectures%";
    }
    
    if (!empty($completionYears)) {
        $sql .= " AND completionYears = ?";
        $params[] = $completionYears;
    }
    
    if (!empty($buildingTypes)) {
        $sql .= " AND buildingTypes LIKE ?";
        $params[] = "%$buildingTypes%";
    }
    
    if ($hasPhotos) {
        $sql .= " AND has_photo IS NOT NULL AND has_photo != '' AND has_photo != '0'";
    }
    
    if ($hasVideos) {
        $sql .= " AND has_video IS NOT NULL AND has_video != '' AND has_video != '0'";
    }
    
    // 総件数の取得
    $countSql = str_replace("SELECT *", "SELECT COUNT(*) as count", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = $stmt->fetch()['count'];
    
    // ページネーション
    $offset = ($page - 1) * $limit;
    $sql .= " ORDER BY completionYears DESC LIMIT $limit OFFSET $offset";
    
    // 検索実行
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $buildings = $stmt->fetchAll();
    
    // 人気検索の取得（テーブルが存在しない場合はスキップ）
    $popularSearches = [];
    // popular_searchesテーブルは存在しないため、スキップ
    
    // HTMLの出力
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $lang; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PocketNavi - 建築検索システム</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
                background-color: #f5f5f5; 
            }
            .container { 
                max-width: 1200px; 
                margin: 0 auto; 
                background: white; 
                padding: 20px; 
                border-radius: 8px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            }
            .search-form { 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 5px; 
                margin-bottom: 20px; 
            }
            .form-group { 
                margin-bottom: 15px; 
            }
            label { 
                display: block; 
                margin-bottom: 5px; 
                font-weight: bold; 
            }
            input, select { 
                width: 100%; 
                padding: 8px; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
                box-sizing: border-box;
            }
            button { 
                background: #007bff; 
                color: white; 
                padding: 10px 20px; 
                border: none; 
                border-radius: 4px; 
                cursor: pointer; 
                margin-right: 10px;
            }
            button:hover { 
                background: #0056b3; 
            }
            .building-card { 
                border: 1px solid #ddd; 
                padding: 15px; 
                margin: 10px 0; 
                border-radius: 5px; 
                background: white;
            }
            .building-card h3 { 
                margin-top: 0; 
                color: #2c3e50; 
            }
            .building-info { 
                color: #7f8c8d; 
                margin-bottom: 10px; 
            }
            .pagination { 
                text-align: center; 
                margin: 20px 0; 
            }
            .pagination a { 
                display: inline-block; 
                padding: 8px 12px; 
                margin: 0 4px; 
                text-decoration: none; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
                color: #007bff; 
            }
            .pagination a:hover { 
                background: #007bff; 
                color: white; 
            }
            .pagination .current { 
                background: #007bff; 
                color: white; 
            }
            .popular-searches { 
                background: #e9ecef; 
                padding: 15px; 
                border-radius: 5px; 
                margin-bottom: 20px; 
            }
            .popular-searches h3 { 
                margin-top: 0; 
            }
            .popular-searches a { 
                display: inline-block; 
                margin: 5px 10px 5px 0; 
                padding: 5px 10px; 
                background: white; 
                border: 1px solid #ddd; 
                border-radius: 3px; 
                text-decoration: none; 
                color: #007bff; 
            }
            .popular-searches a:hover { 
                background: #007bff; 
                color: white; 
            }
            .stats { 
                background: #d4edda; 
                padding: 10px; 
                border-radius: 5px; 
                margin-bottom: 20px; 
                color: #155724; 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🏗️ PocketNavi - 建築検索システム</h1>
            
            <div class="stats">
                <strong>データベース接続成功！</strong> 建物データ: <?php echo number_format($totalCount); ?>件
            </div>
            
            <div class="search-form">
                <h2>🔍 建物検索</h2>
                <form method="GET">
                    <div class="form-group">
                        <label for="q"><?php echo t('search', $lang); ?></label>
                        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="<?php echo t('search_placeholder', $lang); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="prefectures"><?php echo t('prefecture', $lang); ?></label>
                        <input type="text" id="prefectures" name="prefectures" value="<?php echo htmlspecialchars($prefectures); ?>" placeholder="<?php echo t('prefecture', $lang); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="completionYears"><?php echo t('completion_year', $lang); ?></label>
                        <input type="number" id="completionYears" name="completionYears" value="<?php echo htmlspecialchars($completionYears); ?>" placeholder="<?php echo t('completion_year', $lang); ?>" min="1800" max="2030">
                    </div>
                    
                    <div class="form-group">
                        <label for="buildingTypes"><?php echo t('building_type', $lang); ?></label>
                        <input type="text" id="buildingTypes" name="buildingTypes" value="<?php echo htmlspecialchars($buildingTypes); ?>" placeholder="<?php echo t('building_type', $lang); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="photos" value="1" <?php echo $hasPhotos ? 'checked' : ''; ?>>
                            <?php echo t('has_photos', $lang); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="videos" value="1" <?php echo $hasVideos ? 'checked' : ''; ?>>
                            <?php echo t('has_videos', $lang); ?>
                        </label>
                    </div>
                    
                    <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
                    
                    <button type="submit"><?php echo t('search_button', $lang); ?></button>
                    <button type="button" onclick="clearForm()"><?php echo t('clear_button', $lang); ?></button>
                </form>
            </div>
            
            <?php if (!empty($popularSearches)): ?>
            <div class="popular-searches">
                <h3>🔥 人気検索</h3>
                <?php foreach ($popularSearches as $search): ?>
                    <a href="?q=<?php echo urlencode($search['search_term']); ?>&lang=<?php echo $lang; ?>">
                        <?php echo htmlspecialchars($search['search_term']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($query) || !empty($prefectures) || !empty($completionYears) || !empty($buildingTypes) || $hasPhotos || $hasVideos): ?>
            <h2>🔍 検索結果 (<?php echo count($buildings); ?>件 / 合計<?php echo number_format($totalCount); ?>件)</h2>
            
            <?php if (!empty($buildings)): ?>
                <?php foreach ($buildings as $building): ?>
                <div class="building-card">
                    <h3><?php echo htmlspecialchars($building['title'] ?? ''); ?></h3>
                    <div class="building-info">
                        <p><strong>場所:</strong> <?php echo htmlspecialchars($building['location'] ?? ''); ?></p>
                        <p><strong>完成年:</strong> <?php echo htmlspecialchars($building['completionYears'] ?? ''); ?></p>
                        <p><strong>建築タイプ:</strong> <?php echo htmlspecialchars($building['buildingTypes'] ?? ''); ?></p>
                        <?php if (!empty($building['description'])): ?>
                            <p><strong>説明:</strong> <?php echo htmlspecialchars(substr($building['description'], 0, 200)); ?><?php echo strlen($building['description']) > 200 ? '...' : ''; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($totalCount > $limit): ?>
                <div class="pagination">
                    <?php
                    $totalPages = ceil($totalCount / $limit);
                    $startPage = max(1, $page - 5);
                    $endPage = min($totalPages, $page + 5);
                    
                    if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">前へ</a>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">次へ</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p>検索結果が見つかりませんでした。</p>
            <?php endif; ?>
            <?php else: ?>
            <h2>🏗️ 建物データベース</h2>
            <p>上記の検索フォームを使用して建物を検索してください。</p>
            <p>現在 <?php echo number_format($totalCount); ?> 件の建物データが登録されています。</p>
            <?php endif; ?>
        </div>
        
        <script>
        function clearForm() {
            document.querySelector('form').reset();
        }
        </script>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    echo '<!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>エラー - PocketNavi</title>
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
            <h1>エラーが発生しました</h1>
            <p>エラー: ' . htmlspecialchars($e->getMessage()) . '</p>
            <p>ファイル: ' . htmlspecialchars($e->getFile()) . '</p>
            <p>行: ' . $e->getLine() . '</p>
            <a href="/" class="back-link">トップページに戻る</a>
        </div>
    </body>
    </html>';
}
?>
