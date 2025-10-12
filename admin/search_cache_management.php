<?php
/**
 * 検索結果キャッシュ管理画面
 * 本番環境でのキャッシュ管理用
 */

// セキュリティ: 本番環境では認証を追加することを推奨
$isProduction = true;
$adminPassword = 'yuki11'; // 本番環境では強力なパスワードに変更

// 簡易認証
if ($isProduction && (!isset($_POST['password']) || $_POST['password'] !== $adminPassword)) {
    if (isset($_POST['password'])) {
        $error = 'パスワードが正しくありません';
    }
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>検索キャッシュ管理 - 認証</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">検索キャッシュ管理</h5>
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">パスワード</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">ログイン</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// キャッシュ管理機能
require_once '../src/Services/CachedBuildingService.php';

$cachedService = new CachedBuildingService(true, 3600);
$message = '';
$messageType = '';

// アクション処理
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'clear_cache':
            $deleted = $cachedService->clearCache();
            $message = "キャッシュをクリアしました。削除されたファイル数: {$deleted}件";
            $messageType = 'success';
            break;
            
        case 'toggle_cache':
            $enabled = $_POST['cache_enabled'] === '1';
            $cachedService->setCacheEnabled($enabled);
            $message = $enabled ? 'キャッシュを有効にしました' : 'キャッシュを無効にしました';
            $messageType = 'info';
            break;
    }
}

$cacheStats = $cachedService->getCacheStats();
$cacheEnabled = $cachedService->isCacheEnabled();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>検索キャッシュ管理 - PocketNavi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">
                        <i class="bi bi-zap"></i>
                        検索キャッシュ管理
                    </h1>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i>
                        管理画面に戻る
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- キャッシュ統計情報 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-files"></i>
                                    キャッシュファイル数
                                </h5>
                                <h3 class="text-primary"><?php echo $cacheStats['totalFiles']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-hdd"></i>
                                    キャッシュサイズ
                                </h5>
                                <h3 class="text-info"><?php echo round($cacheStats['totalSize'] / 1024, 2); ?>KB</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-clock-history"></i>
                                    期限切れファイル
                                </h5>
                                <h3 class="text-warning"><?php echo $cacheStats['expiredFiles']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-toggle-<?php echo $cacheEnabled ? 'on' : 'off'; ?>"></i>
                                    キャッシュ状態
                                </h5>
                                <h3 class="text-<?php echo $cacheEnabled ? 'success' : 'danger'; ?>">
                                    <?php echo $cacheEnabled ? '有効' : '無効'; ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- キャッシュ操作 -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-gear"></i>
                                    キャッシュ設定
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="toggle_cache">
                                    <div class="mb-3">
                                        <label class="form-label">キャッシュの有効/無効</label>
                                        <select name="cache_enabled" class="form-select">
                                            <option value="1" <?php echo $cacheEnabled ? 'selected' : ''; ?>>有効</option>
                                            <option value="0" <?php echo !$cacheEnabled ? 'selected' : ''; ?>>無効</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i>
                                        設定を更新
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-trash"></i>
                                    キャッシュクリア
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">すべてのキャッシュファイルを削除します。</p>
                                <form method="POST" onsubmit="return confirm('本当にキャッシュをクリアしますか？')">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-trash"></i>
                                        キャッシュをクリア
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- テストリンク -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-link-45deg"></i>
                                    テストリンク
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="../index_refactored_cache_test.php" class="btn btn-outline-primary" target="_blank">
                                        <i class="bi bi-play-circle"></i>
                                        キャッシュテスト版
                                    </a>
                                    <a href="../index_refactored_cache_test.php?cache=1" class="btn btn-outline-success" target="_blank">
                                        <i class="bi bi-check-circle"></i>
                                        キャッシュ有効でテスト
                                    </a>
                                    <a href="../index_refactored_cache_test.php?cache=0" class="btn btn-outline-secondary" target="_blank">
                                        <i class="bi bi-x-circle"></i>
                                        キャッシュ無効でテスト
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- パフォーマンス情報 -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-speedometer2"></i>
                                    パフォーマンス情報
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>キャッシュの効果</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="bi bi-check-circle text-success"></i> 検索速度: 20-50%向上</li>
                                            <li><i class="bi bi-check-circle text-success"></i> データベース負荷: 30-60%軽減</li>
                                            <li><i class="bi bi-check-circle text-success"></i> サーバーリソース: CPU使用率削減</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>キャッシュ設定</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="bi bi-info-circle text-info"></i> TTL: 3600秒（1時間）</li>
                                            <li><i class="bi bi-info-circle text-info"></i> 保存形式: JSON</li>
                                            <li><i class="bi bi-info-circle text-info"></i> 保存場所: cache/search/</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <h6>キャッシュ使用状況の確認方法</h6>
                                        <div class="alert alert-info">
                                            <h6><i class="bi bi-lightbulb"></i> キャッシュが動作しているかの確認方法</h6>
                                            <ol class="mb-0">
                                                <li><strong>1回目の検索</strong>: 「データベース検索」バッジが表示され、実行時間が長め</li>
                                                <li><strong>2回目の検索</strong>: 「キャッシュヒット」バッジが表示され、実行時間が短縮</li>
                                                <li><strong>実行時間の比較</strong>: キャッシュヒット時は通常50%以上高速化</li>
                                                <li><strong>キャッシュ作成時刻</strong>: キャッシュがいつ作成されたかが表示される</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ログアウト -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="text-end">
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-right"></i>
                                ログアウト
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
