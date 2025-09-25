-- 人気検索ビューの作成
-- 過去5日間の人気検索を集計

CREATE OR REPLACE VIEW `popular_searches_view` AS
SELECT 
    `query`,
    `search_type`,
    COUNT(*) as `search_count`,
    COUNT(DISTINCT `user_session_id`) as `unique_sessions`,
    MAX(`searched_at`) as `last_searched`,
    MAX(JSON_EXTRACT(`filters`, '$.pageType')) as `page_type`,
    MAX(JSON_EXTRACT(`filters`, '$.identifier')) as `identifier`,
    MAX(JSON_EXTRACT(`filters`, '$.title')) as `title`,
    MAX(`filters`) as `filters`
FROM `global_search_history` 
WHERE `searched_at` >= DATE_SUB(NOW(), INTERVAL 5 DAY)
    AND `search_type` IS NOT NULL 
    AND `search_type` != ''
GROUP BY `query`, `search_type`
HAVING COUNT(*) >= 1
ORDER BY `search_count` DESC, `last_searched` DESC;
