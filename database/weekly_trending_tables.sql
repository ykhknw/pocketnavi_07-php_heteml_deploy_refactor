-- 週間トレンド検索用テーブル

-- 週間トレンド検索結果を保存するテーブル
CREATE TABLE IF NOT EXISTS weekly_trending_searches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(255) NOT NULL,
    search_type VARCHAR(50) NOT NULL,
    total_searches INT NOT NULL,
    unique_users INT NOT NULL,
    last_searched DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_query_type (query, search_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- キャッシュ管理用テーブル
CREATE TABLE IF NOT EXISTS trending_cache (
    cache_key VARCHAR(100) PRIMARY KEY,
    last_update DATETIME NOT NULL,
    data JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
