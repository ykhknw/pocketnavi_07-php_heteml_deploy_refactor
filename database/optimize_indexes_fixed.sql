-- Phase 4.1.1: データベースインデックス最適化（修正版）
-- パフォーマンス向上のためのインデックス追加

-- 1. 座標検索用の複合インデックス（最優先）
-- 現在の座標検索で全テーブルスキャンが発生している問題を解決
CREATE INDEX idx_buildings_coords_photo ON buildings_table_3(lat, lng, has_photo, building_id);

-- 2. 検索用の複合インデックス（JSON型カラムは除外）
-- 完成年、写真有無での検索を最適化
CREATE INDEX idx_buildings_search_optimized ON buildings_table_3(completionYears, has_photo, building_id);

-- 3. 建築家検索用のインデックス
-- 建築家ページでの建築物一覧表示を最適化
CREATE INDEX idx_buildings_architect_search ON buildings_table_3(has_photo, building_id, lat, lng);

-- 4. タイトル検索用のインデックス
-- 全文検索の代替として部分一致検索を最適化
CREATE INDEX idx_buildings_title_search ON buildings_table_3(title(100), has_photo);

-- 5. 英語タイトル検索用のインデックス
CREATE INDEX idx_buildings_title_en_search ON buildings_table_3(titleEn(100), has_photo);

-- 6. 場所検索用のインデックス
CREATE INDEX idx_buildings_location_search ON buildings_table_3(location(100), has_photo);

-- 7. 完成年範囲検索用のインデックス
CREATE INDEX idx_buildings_year_range ON buildings_table_3(completionYears, has_photo, building_id);

-- 8. 写真有無での並び替え最適化
CREATE INDEX idx_buildings_photo_priority ON buildings_table_3(has_photo DESC, building_id DESC);

-- JSON型カラム用のインデックス（長さ指定）
-- 9. 都道府県検索用のインデックス（JSON型）
CREATE INDEX idx_buildings_prefecture_json ON buildings_table_3((CAST(prefectures AS CHAR(100))), has_photo);

-- 10. 建築タイプ検索用のインデックス（JSON型）
CREATE INDEX idx_buildings_type_json ON buildings_table_3((CAST(buildingTypes AS CHAR(100))), has_photo);

-- インデックス作成後の統計情報更新
ANALYZE TABLE buildings_table_3;
ANALYZE TABLE individual_architects_3;
ANALYZE TABLE building_architects;
ANALYZE TABLE architect_compositions_2;
