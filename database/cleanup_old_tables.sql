-- =====================================================
-- 古いテーブルの削除スクリプト
-- 実行前に必ずバックアップを取ってください
-- =====================================================

-- 削除対象テーブル:
-- - architect_compositions (古いバージョン)
-- - architect_websites_3 (古いバージョン)  
-- - buildings_table_2 (古いバージョン)
-- - individual_architects (古いバージョン)
-- - individual_architects_2 (古いバージョン)

-- 注意: これらのテーブルは現在のアプリケーションでは使用されていません
-- 新しいバージョンのテーブルが使用されています:
-- - architect_compositions_2
-- - architect_websites_4
-- - buildings_table_3
-- - individual_architects_3

-- =====================================================
-- 削除実行
-- =====================================================

-- 1. architect_compositions テーブルの削除
-- (architect_compositions_2 が使用中)
DROP TABLE IF EXISTS architect_compositions;

-- 2. architect_websites_3 テーブルの削除
-- (architect_websites_4 が使用中)
DROP TABLE IF EXISTS architect_websites_3;

-- 3. buildings_table_2 テーブルの削除
-- (buildings_table_3 が使用中)
DROP TABLE IF EXISTS buildings_table_2;

-- 4. individual_architects テーブルの削除
-- (individual_architects_3 が使用中)
DROP TABLE IF EXISTS individual_architects;

-- 5. individual_architects_2 テーブルの削除
-- (individual_architects_3 が使用中)
DROP TABLE IF EXISTS individual_architects_2;

-- =====================================================
-- 削除後の確認クエリ
-- =====================================================

-- 削除されたテーブルが存在しないことを確認
SELECT 'architect_compositions' as table_name, 
       CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'DELETED' END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'architect_compositions'

UNION ALL

SELECT 'architect_websites_3' as table_name, 
       CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'DELETED' END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'architect_websites_3'

UNION ALL

SELECT 'buildings_table_2' as table_name, 
       CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'DELETED' END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'buildings_table_2'

UNION ALL

SELECT 'individual_architects' as table_name, 
       CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'DELETED' END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'individual_architects'

UNION ALL

SELECT 'individual_architects_2' as table_name, 
       CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'DELETED' END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'individual_architects_2';

-- =====================================================
-- 現在使用中のテーブルの確認
-- =====================================================

SELECT 'Current Tables Status' as info;

SELECT table_name, table_rows
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
  AND table_name IN (
    'buildings_table_3',
    'individual_architects_3', 
    'architect_compositions_2',
    'architect_websites_4',
    'building_architects',
    'global_search_history'
  )
ORDER BY table_name;

