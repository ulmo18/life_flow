-- One-time migration for independent Calendar block snapshots.
-- Back up the database before applying. Existing blocks retain their current values.
ALTER TABLE daily_plan_blocks
    ADD COLUMN title_snapshot VARCHAR(80) NULL,
    ADD COLUMN importance_snapshot CHAR(1) NULL,
    ADD COLUMN goal_id_snapshot BIGINT UNSIGNED NULL;

UPDATE daily_plan_blocks AS block
INNER JOIN daily_plan_items AS item ON item.id = block.daily_plan_item_id
SET block.title_snapshot = item.title,
    block.importance_snapshot = item.importance,
    block.goal_id_snapshot = item.goal_id;

ALTER TABLE daily_plan_items
    ADD COLUMN source_block_template_id BIGINT UNSIGNED NULL;
