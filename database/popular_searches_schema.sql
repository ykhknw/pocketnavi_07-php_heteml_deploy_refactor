-- 人気検索機能用テーブル作成
-- global_search_history テーブル

CREATE TABLE IF NOT EXISTS `global_search_history` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `query` TEXT NOT NULL,
  `search_type` VARCHAR(20) NOT NULL CHECK (search_type IN ('text', 'architect', 'prefecture', 'building')),
  `user_id` BIGINT NULL,
  `user_session_id` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `filters` JSON NULL,
  `searched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_query` (`query`(100)),
  INDEX `idx_search_type` (`search_type`),
  INDEX `idx_searched_at` (`searched_at`),
  INDEX `idx_user_session` (`user_session_id`),
  INDEX `idx_ip_address` (`ip_address`),
  INDEX `idx_query_type_date` (`query`(50), `search_type`, `searched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- サンプルデータの挿入
INSERT INTO `global_search_history` (`id`, `query`, `search_type`, `user_id`, `user_session_id`, `ip_address`, `filters`, `searched_at`, `created_at`) VALUES
(77, '茨木市文化・子育て複合施設おにクル', 'building', NULL, 'search_68d103a99ffeb1.83193259', '::1', '{\"pageType\":\"building\",\"identifier\":\"ibaraki-city-culture-and-child-rearing-support-complex-onikuru\",\"title\":\"\\u8328\\u6728\\u5e02\\u6587\\u5316\\u30fb\\u5b50\\u80b2\\u3066\\u8907\\u5408\\u65bd\\u8a2d\\u304a\\u306b\\u30af\\u30eb\",\"building_id\":10490,\"lang\":\"ja\"}', '2025-09-22 08:09:21', '2025-09-22 08:09:21'),
(71, '竹中工務店', 'architect', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"pageType\":\"architect\",\"identifier\":\"takenaka-corporation\",\"title\":\"\\u7af9\\u4e2d\\u5de5\\u52d9\\u5e97\",\"architect_id\":9,\"lang\":\"ja\"}', '2025-09-22 07:28:34', '2025-09-22 07:28:34'),
(76, '竹中工務店', 'architect', NULL, 'search_68d103a99ffeb1.83193259', '::1', '{\"pageType\":\"architect\",\"identifier\":\"takenaka-corporation\",\"title\":\"\\u7af9\\u4e2d\\u5de5\\u52d9\\u5e97\",\"architect_id\":9,\"lang\":\"ja\"}', '2025-09-22 08:07:53', '2025-09-22 08:07:53'),
(79, '竹中工務店', 'architect', NULL, 'search_68d1083fbecc55.67369528', '::1', '{\"hasPhotos\":false,\"hasVideos\":false,\"lang\":\"ja\",\"completionYears\":[],\"prefectures\":[],\"buildingTypes\":[],\"architect_id\":9,\"architect_slug\":\"takenaka-corporation\",\"architect_name_ja\":\"\\u7af9\\u4e2d\\u5de5\\u52d9\\u5e97\",\"architect_name_en\":\"TAKENAKA CORPORATION\"}', '2025-09-22 08:26:47', '2025-09-22 08:26:47'),
(80, '竹中工務店', 'architect', NULL, 'search_68d1083fbecc55.67369528', '::1', '{\"pageType\":\"architect\",\"identifier\":\"takenaka-corporation\",\"title\":\"\\u7af9\\u4e2d\\u5de5\\u52d9\\u5e97\",\"architect_id\":9,\"lang\":\"ja\"}', '2025-09-22 08:26:52', '2025-09-22 08:26:52'),
(81, '竹中工務店', 'architect', NULL, 'search_68d1083fbecc55.67369528', '::1', '{\"pageType\":\"architect\",\"identifier\":\"takenaka-corporation\",\"title\":\"\\u7af9\\u4e2d\\u5de5\\u52d9\\u5e97\",\"architect_id\":9,\"lang\":\"ja\"}', '2025-09-22 08:37:38', '2025-09-22 08:37:38'),
(63, '東京都', 'prefecture', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"hasPhotos\":false,\"hasVideos\":false,\"lang\":\"ja\",\"completionYears\":[],\"prefectures\":[],\"buildingTypes\":[],\"prefecture_ja\":\"\\u6771\\u4eac\\u90fd\",\"prefecture_en\":\"Tokyo\"}', '2025-09-22 07:08:17', '2025-09-22 07:08:17'),
(68, '東京都', 'prefecture', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"hasPhotos\":false,\"hasVideos\":false,\"lang\":\"ja\",\"completionYears\":[],\"prefectures\":[],\"buildingTypes\":[],\"prefecture_ja\":\"\\u6771\\u4eac\\u90fd\",\"prefecture_en\":\"Tokyo\"}', '2025-09-22 07:17:03', '2025-09-22 07:17:03'),
(74, '東京都', 'prefecture', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"pageType\":\"prefecture\",\"identifier\":\"Tokyo\",\"title\":\"\\u6771\\u4eac\\u90fd\",\"lang\":\"ja\",\"hasPhotos\":false,\"hasVideos\":false,\"prefecture_ja\":\"\\u6771\\u4eac\\u90fd\",\"prefecture_en\":\"Tokyo\"}', '2025-09-22 07:38:42', '2025-09-22 07:38:42'),
(66, '東京スカイツリー', 'building', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"pageType\":\"building\",\"identifier\":\"tokyo-skytree\",\"title\":\"\\u6771\\u4eac\\u30b9\\u30ab\\u30a4\\u30c4\\u30ea\\u30fc\",\"building_id\":6928,\"lang\":\"ja\"}', '2025-09-22 07:09:48', '2025-09-22 07:09:48'),
(64, '日建設計', 'architect', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"pageType\":\"architect\",\"identifier\":\"nikken-sekkei-1\",\"title\":\"\\u65e5\\u5efa\\u8a2d\\u8a08\",\"architect_id\":17,\"lang\":\"ja\"}', '2025-09-22 07:08:28', '2025-09-22 07:08:28'),
(70, '愛知県', 'prefecture', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"pageType\":\"prefecture\",\"identifier\":\"Aichi\",\"title\":\"\\u611b\\u77e5\\u770c\",\"lang\":\"ja\",\"hasPhotos\":false,\"hasVideos\":false,\"prefecture_ja\":\"\\u611b\\u77e5\\u770c\",\"prefecture_en\":\"Aichi\"}', '2025-09-22 07:28:17', '2025-09-22 07:28:17'),
(75, '愛知県', 'prefecture', NULL, 'search_68d103a99ffeb1.83193259', '::1', '{\"pageType\":\"prefecture\",\"identifier\":\"Aichi\",\"title\":\"\\u611b\\u77e5\\u770c\",\"lang\":\"ja\",\"hasPhotos\":false,\"hasVideos\":false,\"prefecture_ja\":\"\\u611b\\u77e5\\u770c\",\"prefecture_en\":\"Aichi\"}', '2025-09-22 08:07:05', '2025-09-22 08:07:05'),
(78, '愛知県', 'prefecture', NULL, 'search_68d1083fbecc55.67369528', '::1', '{\"pageType\":\"prefecture\",\"identifier\":\"Aichi\",\"title\":\"\\u611b\\u77e5\\u770c\",\"lang\":\"ja\",\"hasPhotos\":false,\"hasVideos\":false,\"prefecture_ja\":\"\\u611b\\u77e5\\u770c\",\"prefecture_en\":\"Aichi\"}', '2025-09-22 08:26:39', '2025-09-22 08:26:39'),
(72, '大阪府', 'prefecture', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"pageType\":\"prefecture\",\"identifier\":\"Osaka\",\"title\":\"\\u5927\\u962a\\u5e9c\",\"lang\":\"ja\",\"hasPhotos\":false,\"hasVideos\":false,\"prefecture_ja\":\"\\u5927\\u962a\\u5e9c\",\"prefecture_en\":\"Osaka\"}', '2025-09-22 07:32:54', '2025-09-22 07:32:54'),
(73, '大阪大学箕面キャンパス 外国学研究講義棟', 'building', NULL, 'search_68d0e682a0bbb5.17663544', '::1', '{\"pageType\":\"building\",\"identifier\":\"research-and-education-hub-minoh-campus-osaka-university\",\"title\":\"\\u5927\\u962a\\u5927\\u5b66\\u7b95\\u9762\\u30ad\\u30e3\\u30f3\\u30d1\\u30b9 \\u5916\\u56fd\\u5b66\\u7814\\u7a76\\u8b1b\\u7fa9\\u68df\",\"building_id\":10452,\"lang\":\"ja\"}', '2025-09-22 07:32:59', '2025-09-22 07:32:59');