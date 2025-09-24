-- has_photoカラムをbuildings_table_3に追加
-- 写真がある建築物を優先表示するためのフラグ

ALTER TABLE buildings_table_3 
ADD COLUMN has_photo TINYINT(1) DEFAULT 0 COMMENT '写真の有無（1: あり, 0: なし）';

-- 既存データに対してhas_photoを設定
-- thumbnailUrlが存在する場合は1、そうでなければ0
UPDATE buildings_table_3 
SET has_photo = CASE 
    WHEN thumbnailUrl IS NOT NULL AND thumbnailUrl != '' THEN 1 
    ELSE 0 
END;

-- インデックスを追加（検索パフォーマンス向上）
CREATE INDEX idx_has_photo ON buildings_table_3(has_photo);
CREATE INDEX idx_has_photo_building_id ON buildings_table_3(has_photo, building_id);
