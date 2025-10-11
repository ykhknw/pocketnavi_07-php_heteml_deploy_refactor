-- =====================================================
-- 古いテーブルの削除スクリプト（シンプル版）
-- 実行前に必ずバックアップを取ってください
-- =====================================================

-- 削除対象テーブル（現在使用されていない古いバージョン）
DROP TABLE IF EXISTS architect_compositions;
DROP TABLE IF EXISTS architect_websites_3;
DROP TABLE IF EXISTS buildings_table_2;
DROP TABLE IF EXISTS individual_architects;
DROP TABLE IF EXISTS individual_architects_2;

