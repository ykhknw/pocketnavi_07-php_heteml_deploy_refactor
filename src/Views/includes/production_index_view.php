<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($seoData['title'] ?? 'PocketNavi - 建築物検索'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoData['description'] ?? '建築物を検索できるサイト'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoData['keywords'] ?? '建築物,検索,建築家'); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/landmark.svg" type="image/svg+xml">

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-9FY04VHM17"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-9FY04VHM17');
</script>

</head>
<body>
    <!-- Header -->
    <?php include 'src/Views/includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Search Form -->
                <?php include 'src/Views/includes/search_form.php'; ?>
                
                <!-- Current Search Context Display -->
                <?php if ($architectsSlug && isset($architectInfo) && $architectInfo): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h4 mb-2">
                                <i data-lucide="circle-user-round" class="me-2" style="width: 20px; height: 20px;"></i>
                                <?php echo $lang === 'ja' ? '建築家' : 'Architect'; ?>: 
                                <span class="text-primary"><?php echo htmlspecialchars($lang === 'ja' ? ($architectInfo['nameJa'] ?? $architectInfo['nameEn'] ?? '') : ($architectInfo['nameEn'] ?? $architectInfo['nameJa'] ?? '')); ?></span>
                            </h2>
                            <?php if ($lang === 'ja' && !empty($architectInfo['nameEn']) && $architectInfo['nameJa'] !== $architectInfo['nameEn']): ?>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($architectInfo['nameEn']); ?>
                                </p>
                            <?php elseif ($lang === 'en' && !empty($architectInfo['nameJa']) && $architectInfo['nameJa'] !== $architectInfo['nameEn']): ?>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($architectInfo['nameJa']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($architectInfo['individual_website'])): ?>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-end align-items-center">
                                    <a href="<?php echo htmlspecialchars($architectInfo['individual_website']); ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-secondary btn-sm"
                                       title="<?php echo $lang === 'ja' ? '関連サイトを見る' : 'Visit Related Site'; ?>">
                                        <i data-lucide="square-mouse-pointer" style="width: 16px; height: 16px;"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($buildingSlug && $currentBuilding): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="h4 mb-2">
                                <i data-lucide="building" class="me-2" style="width: 20px; height: 20px;"></i>
                                <?php echo $lang === 'ja' ? '建築物' : 'Building'; ?>: 
                                <span class="text-primary"><?php echo htmlspecialchars($lang === 'ja' ? ($currentBuilding['title'] ?? '') : ($currentBuilding['titleEn'] ?? $currentBuilding['title'] ?? '')); ?></span>
                            </h2>
                            <?php if ($lang === 'ja' && !empty($currentBuilding['titleEn']) && $currentBuilding['title'] !== $currentBuilding['titleEn']): ?>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($currentBuilding['titleEn']); ?>
                                </p>
                            <?php elseif ($lang === 'en' && !empty($currentBuilding['title']) && $currentBuilding['title'] !== $currentBuilding['titleEn']): ?>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($currentBuilding['title']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Image Search Links -->
                            <div class="mt-3">
                                <p class="mb-2">
                                    <i data-lucide="search" class="me-1" style="width: 16px; height: 16px;"></i>
                                    <?php echo $lang === 'ja' ? '画像検索で見る' : 'View in Image Search'; ?>:
                                </p>
                                <div class="d-flex gap-3 flex-wrap">
                                    <?php 
                                    $buildingName = $currentBuilding['title'] ?? '';
                                    $encodedName = urlencode($buildingName);
                                    ?>
                                    <a href="https://www.google.com/search?q=<?php echo $encodedName; ?>&tbm=isch" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i data-lucide="external-link" class="me-1" style="width: 14px; height: 14px;"></i>
                                        <?php echo $lang === 'ja' ? 'Google画像検索' : 'Google Image Search'; ?>
                                    </a>
                                    <a href="https://www.bing.com/images/search?q=<?php echo $encodedName; ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i data-lucide="external-link" class="me-1" style="width: 14px; height: 14px;"></i>
                                        <?php echo $lang === 'ja' ? 'Microsoft Bing画像検索' : 'Microsoft Bing Image Search'; ?>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Video Links -->
                            <?php if (!empty($currentBuilding['youtubeUrl'])): ?>
                                <div class="mt-3">
                                    <p class="mb-2">
                                        <i data-lucide="video" class="me-1" style="width: 16px; height: 16px;"></i>
                                        <?php echo $lang === 'ja' ? '動画で見る' : 'View in Video'; ?>:
                                    </p>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <a href="<?php echo htmlspecialchars($currentBuilding['youtubeUrl']); ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-danger btn-sm">
                                            <i data-lucide="youtube" class="me-1" style="width: 14px; height: 14px;"></i>
                                            <?php echo $lang === 'ja' ? 'Youtubeで見る' : 'Watch on YouTube'; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Search Results Header -->
                <?php if ($hasPhotos || $hasVideos || $completionYears || $prefectures || $query || $architectsSlug): ?>
                    <div class="alert alert-light mb-3">
                        <h6 class="mb-2">
                            <i data-lucide="filter" class="me-2" style="width: 16px; height: 16px;"></i>
                            <?php echo $lang === 'ja' ? 'フィルター適用済み' : 'Filters Applied'; ?>
                        </h6>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php if ($architectsSlug): ?>
                                <span class="architect-badge filter-badge">
                                    <i data-lucide="circle-user-round" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php 
                                    $architectName = '';
                                    if (isset($architectInfo) && $architectInfo) {
                                        $architectName = $lang === 'ja' ? 
                                            ($architectInfo['name_ja'] ?? $architectInfo['name_en'] ?? '') : 
                                            ($architectInfo['name_en'] ?? $architectInfo['name_ja'] ?? '');
                                    }
                                    if (empty($architectName)) {
                                        $architectName = str_replace('-', ' ', $architectsSlug);
                                        $architectName = ucwords($architectName);
                                    }
                                    echo htmlspecialchars($architectName); 
                                    ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['architects_slug' => null])); ?>" 
                                       class="filter-remove-btn ms-2" 
                                       title="<?php echo $lang === 'ja' ? 'フィルターを解除' : 'Remove filter'; ?>">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($hasPhotos): ?>
                                <span class="architect-badge filter-badge">
                                    <i data-lucide="image" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo $lang === 'ja' ? '写真あり' : 'With Photos'; ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['photos' => null])); ?>" 
                                       class="filter-remove-btn ms-2" 
                                       title="<?php echo $lang === 'ja' ? 'フィルターを解除' : 'Remove filter'; ?>">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($hasVideos): ?>
                                <span class="architect-badge filter-badge">
                                    <i data-lucide="youtube" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo $lang === 'ja' ? '動画あり' : 'With Videos'; ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['videos' => null])); ?>" 
                                       class="filter-remove-btn ms-2" 
                                       title="<?php echo $lang === 'ja' ? 'フィルターを解除' : 'Remove filter'; ?>">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($completionYears): ?>
                                <span class="architect-badge filter-badge">
                                    <i data-lucide="calendar" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo htmlspecialchars($completionYears); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['completionYears' => null])); ?>" 
                                       class="filter-remove-btn ms-2" 
                                       title="<?php echo $lang === 'ja' ? 'フィルターを解除' : 'Remove filter'; ?>">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($prefectures): ?>
                                <span class="architect-badge filter-badge">
                                    <i data-lucide="map-pin" class="me-1" style="width: 12px; height: 12px;"></i>
                                    <?php echo htmlspecialchars($prefectures); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['prefectures' => null])); ?>" 
                                       class="filter-remove-btn ms-2" 
                                       title="<?php echo $lang === 'ja' ? 'フィルターを解除' : 'Remove filter'; ?>">
                                        <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($query): ?>
                                <?php 
                                $keywords = preg_split('/[\s　]+/u', trim($query));
                                $keywords = array_filter($keywords, function($keyword) {
                                    return !empty(trim($keyword));
                                });
                                ?>
                                <?php foreach ($keywords as $index => $keyword): ?>
                                    <span class="architect-badge filter-badge">
                                        <i data-lucide="search" class="me-1" style="width: 12px; height: 12px;"></i>
                                        <?php echo htmlspecialchars($keyword); ?>
                                        <a href="?<?php 
                                            $remainingKeywords = $keywords;
                                            unset($remainingKeywords[$index]);
                                            $newQuery = implode(' ', $remainingKeywords);
                                            echo http_build_query(array_merge($_GET, ['q' => $newQuery ?: null])); 
                                        ?>" 
                                           class="filter-remove-btn ms-2" 
                                           title="<?php echo $lang === 'ja' ? 'このキーワードを削除' : 'Remove this keyword'; ?>">
                                            <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                        </a>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Building Cards -->
                <div class="row" id="building-cards">
                    <?php if (empty($buildings)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <?php echo $lang === 'ja' ? '建築物が見つかりませんでした。' : 'No buildings found.'; ?>
                                <?php if ($query): ?>
                                    <br><small>検索キーワード: "<?php echo htmlspecialchars($query); ?>"</small>
                                <?php endif; ?>
                                <?php if ($hasPhotos): ?>
                                    <br><small>写真フィルター: 有効</small>
                                <?php endif; ?>
                                <?php if ($hasVideos): ?>
                                    <br><small>動画フィルター: 有効</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($buildings as $index => $building): ?>
                            <div class="col-12 mb-4">
                                <?php 
                                $globalIndex = ($currentPage - 1) * $limit + $index + 1;
                                ?>
                                <?php include 'src/Views/includes/building_card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <?php include 'src/Views/includes/pagination.php'; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <?php include 'src/Views/includes/sidebar.php'; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'src/Views/includes/footer.php'; ?>
    
    <!-- Photo Carousel Modal -->
    <?php include 'src/Views/includes/photo_carousel_modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
    <!-- Popular Searches JS -->
    <script src="/assets/js/popular-searches.js"></script>
    
    <script>
        // ページ情報をJavaScriptに渡す
        window.pageInfo = {
            currentPage: <?php echo $currentPage; ?>,
            limit: <?php echo $limit; ?>
        };
        
        // 建築物データをJavaScriptに渡す
        window.buildingsData = <?php echo json_encode($buildings); ?>;
        
        // Lucideアイコンの初期化とマップの初期化
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
            
            if (typeof L === 'undefined') {
                console.error('Leaflet library not loaded');
                return;
            }
            
            if (typeof initMap === 'function') {
                let center = [35.6762, 139.6503]; // デフォルト（東京）
                if (window.buildingsData && window.buildingsData.length > 0) {
                    const firstBuilding = window.buildingsData[0];
                    if (firstBuilding.lat && firstBuilding.lng) {
                        center = [parseFloat(firstBuilding.lat), parseFloat(firstBuilding.lng)];
                    }
                }
                
                initMap(center, window.buildingsData || []);
            } else {
                console.error('initMap function not found');
            }
        });
    </script>
</body>
</html>
