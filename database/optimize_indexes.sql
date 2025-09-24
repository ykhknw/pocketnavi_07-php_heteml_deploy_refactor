-- Phase 4.1.1: データベースインデックス最適化
-- パフォーマンス向上のためのインデックス追加

-- 1. 座標検索用の複合インデックス（最優先）
-- 現在の座標検索で全テーブルスキャンが発生している問題を解決
CREATE INDEX idx_buildings_coords_photo ON buildings_table_3(lat, lng, has_photo, building_id);

-- 2. 検索用の複合インデックス
-- 都道府県、建築タイプ、完成年での検索を最適化
CREATE INDEX idx_buildings_search_optimized ON buildings_table_3(prefectures, buildingTypes, completionYears, has_photo);

-- 3. 建築家検索用のインデックス
-- 建築家ページでの建築物一覧表示を最適化
CREATE INDEX idx_buildings_architect_search ON buildings_table_3(has_photo, building_id, lat, lng);

-- 4. タイトル検索用のインデックス
-- 全文検索の代替として部分一致検索を最適化
CREATE INDEX idx_buildings_title_search ON buildings_table_3(title, has_photo);

-- 5. 英語タイトル検索用のインデックス
CREATE INDEX idx_buildings_title_en_search ON buildings_table_3(titleEn, has_photo);

-- 6. 場所検索用のインデックス
CREATE INDEX idx_buildings_location_search ON buildings_table_3(location, has_photo);

-- 7. 完成年範囲検索用のインデックス
CREATE INDEX idx_buildings_year_range ON buildings_table_3(completionYears, has_photo, building_id);

-- 8. 建築タイプ検索用のインデックス
CREATE INDEX idx_buildings_type_search ON buildings_table_3(buildingTypes, has_photo, lat, lng);

-- 9. 都道府県別検索用のインデックス
CREATE INDEX idx_buildings_prefecture_search ON buildings_table_3(prefectures, has_photo, lat, lng);

-- 10. 写真有無での並び替え最適化
CREATE INDEX idx_buildings_photo_priority ON buildings_table_3(has_photo DESC, building_id DESC);

-- 既存のインデックスで不要なものを特定（後で削除検討）
-- 現在のインデックス一覧を確認してから決定

-- インデックス作成後の統計情報更新
ANALYZE TABLE buildings_table_3;
ANALYZE TABLE individual_architects_3;
ANALYZE TABLE building_architects;
ANALYZE TABLE architect_compositions_2;
