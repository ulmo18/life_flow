-- One-time migration for independent Calendar block snapshots.
-- Existing blocks retain the values visible at migration time.
ALTER TABLE daily_plan_blocks ADD COLUMN title_snapshot TEXT NULL;
ALTER TABLE daily_plan_blocks ADD COLUMN importance_snapshot TEXT NULL;
ALTER TABLE daily_plan_blocks ADD COLUMN goal_id_snapshot INTEGER NULL;

UPDATE daily_plan_blocks
SET title_snapshot = (SELECT title FROM daily_plan_items WHERE id = daily_plan_item_id),
    importance_snapshot = (SELECT importance FROM daily_plan_items WHERE id = daily_plan_item_id),
    goal_id_snapshot = (SELECT goal_id FROM daily_plan_items WHERE id = daily_plan_item_id);

ALTER TABLE daily_plan_items ADD COLUMN source_block_template_id INTEGER NULL;
