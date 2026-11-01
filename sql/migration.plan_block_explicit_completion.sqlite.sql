-- Run once before enabling explicit completion. The marker prevents later user choices being overwritten.
BEGIN;
CREATE TABLE IF NOT EXISTS plan_block_completion_migration (id INTEGER PRIMARY KEY);
UPDATE daily_plan_blocks SET manual_status = 'completed'
WHERE NOT EXISTS (SELECT 1 FROM plan_block_completion_migration WHERE id = 1)
  AND EXISTS (SELECT 1 FROM calendar_events ce WHERE ce.daily_plan_block_id = daily_plan_blocks.id AND ce.deleted_at IS NULL);
INSERT INTO plan_block_completion_migration (id) SELECT 1 WHERE NOT EXISTS (SELECT 1 FROM plan_block_completion_migration WHERE id = 1);
COMMIT;
