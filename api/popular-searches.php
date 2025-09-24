<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONSリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 必要なファイルを読み込み
//require_once __DIR__ . '/../../src/Utils/Database.php';
require_once __DIR__ . 'src/Services/SearchLogService.php';

try {
    // パラメータの取得
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
    $searchType = ''; // 常にすべてのタイプを表示
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'ja';
    
    // パラメータの検証
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 20;
    
    // 検索ログサービスを使用してデータを取得
    $searchLogService = new SearchLogService();
    $result = $searchLogService->getPopularSearchesForModal($page, $limit, $searchQuery, $searchType);
    
    // レスポンスの準備
    $response = [
        'success' => true,
        'data' => [
            'searches' => $result['searches'],
            'pagination' => [
                'current_page' => $result['page'],
                'per_page' => $result['limit'],
                'total' => $result['total'],
                'total_pages' => $result['totalPages'],
                'has_next' => $result['page'] < $result['totalPages'],
                'has_prev' => $result['page'] > 1
            ]
        ],
        'lang' => $lang
    ];
    
    // 検索結果をHTMLに変換
    $html = '';
    if (!empty($result['searches'])) {
        $html .= '<div class="list-group list-group-flush">';
        
        foreach ($result['searches'] as $search) {
            $searchTypeLabel = '';
            $pageTypeLabel = '';
            
            // 検索タイプのラベル
            switch ($search['search_type']) {
                case 'text':
                    $searchTypeLabel = $lang === 'ja' ? 'テキスト' : 'Text';
                    break;
                case 'architect':
                    $searchTypeLabel = $lang === 'ja' ? '建築家' : 'Architect';
                    break;
                case 'prefecture':
                    $searchTypeLabel = $lang === 'ja' ? '都道府県' : 'Prefecture';
                    break;
                case 'building':
                    $searchTypeLabel = $lang === 'ja' ? '建築物' : 'Building';
                    break;
            }
            
            // ページタイプのラベル
            if (isset($search['page_type']) && $search['page_type']) {
                switch ($search['page_type']) {
                    case 'architect':
                        $pageTypeLabel = $lang === 'ja' ? '建築家ページ' : 'Architect Page';
                        break;
                    case 'building':
                        $pageTypeLabel = $lang === 'ja' ? '建築物ページ' : 'Building Page';
                        break;
                    case 'prefecture':
                        $pageTypeLabel = $lang === 'ja' ? '都道府県ページ' : 'Prefecture Page';
                        break;
                }
            }
            
            // リンクを生成
            $link = $search['link'] ?? '/index.php?q=' . urlencode($search['query']);
            if (strpos($link, '?') !== false) {
                $link .= '&lang=' . $lang;
            } else {
                $link .= '?lang=' . $lang;
            }
            
            $html .= sprintf(
                '<a href="%s" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">',
                htmlspecialchars($link)
            );
            $html .= '<div class="d-flex flex-column">';
            $html .= sprintf('<span class="fw-medium">%s</span>', htmlspecialchars($search['query']));
            if ($pageTypeLabel) {
                $html .= sprintf('<small class="text-muted">%s</small>', $pageTypeLabel);
            } else {
                $html .= sprintf('<small class="text-muted">%s</small>', $searchTypeLabel);
            }
            $html .= '</div>';
            $html .= '<div class="d-flex flex-column align-items-end">';
            $html .= sprintf('<span class="badge bg-primary rounded-pill">%d</span>', $search['total_searches']);
            $html .= sprintf('<small class="text-muted">%d %s</small>', 
                $search['unique_users'], 
                $lang === 'ja' ? 'ユーザー' : 'users'
            );
            $html .= '</div>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        // ページネーション
        if ($result['totalPages'] > 1) {
            $html .= '<nav aria-label="Popular searches pagination" class="mt-3">';
            $html .= '<ul class="pagination justify-content-center">';
            
            // 前のページ
            if ($result['page'] > 1) {
                $html .= sprintf(
                    '<li class="page-item"><a class="page-link" href="#" onclick="loadPopularSearchesPage(%d)">%s</a></li>',
                    $result['page'] - 1,
                    $lang === 'ja' ? '前へ' : 'Previous'
                );
            }
            
            // ページ番号
            $startPage = max(1, $result['page'] - 2);
            $endPage = min($result['totalPages'], $result['page'] + 2);
            
            if ($startPage > 1) {
                $html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadPopularSearchesPage(1)">1</a></li>';
                if ($startPage > 2) {
                    $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            for ($i = $startPage; $i <= $endPage; $i++) {
                $activeClass = $i === $result['page'] ? ' active' : '';
                $html .= sprintf(
                    '<li class="page-item%s"><a class="page-link" href="#" onclick="loadPopularSearchesPage(%d)">%d</a></li>',
                    $activeClass,
                    $i,
                    $i
                );
            }
            
            if ($endPage < $result['totalPages']) {
                if ($endPage < $result['totalPages'] - 1) {
                    $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                $html .= sprintf(
                    '<li class="page-item"><a class="page-link" href="#" onclick="loadPopularSearchesPage(%d)">%d</a></li>',
                    $result['totalPages'],
                    $result['totalPages']
                );
            }
            
            // 次のページ
            if ($result['page'] < $result['totalPages']) {
                $html .= sprintf(
                    '<li class="page-item"><a class="page-link" href="#" onclick="loadPopularSearchesPage(%d)">%s</a></li>',
                    $result['page'] + 1,
                    $lang === 'ja' ? '次へ' : 'Next'
                );
            }
            
            $html .= '</ul>';
            $html .= '</nav>';
        }
        
    } else {
        $html = sprintf(
            '<div class="text-center py-4"><p class="text-muted">%s</p></div>',
            $lang === 'ja' ? '該当する検索ワードが見つかりませんでした。' : 'No search terms found.'
        );
    }
    
    $response['data']['html'] = $html;
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Popular searches API error: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'error' => [
            'message' => $lang === 'ja' ? 'データの取得に失敗しました。' : 'Failed to fetch data.',
            'code' => 'FETCH_ERROR'
        ],
        'lang' => $lang
    ];
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
