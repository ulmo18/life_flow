-- Run once before enabling explicit completion. The marker prevents later user choices being overwritten.
CREATE TABLE IF NOT EXISTS plan_block_completion_migration (id INTEGER NOT NULL PRIMARY KEY);
START TRANSACTION;
UPDATE daily_plan_blocks dpb SET dpb.manual_status = 'completed'
WHERE NOT EXISTS (SELECT 1 FROM plan_block_completion_migration WHERE id = 1)
  AND EXISTS (SELECT 1 FROM calendar_events ce WHERE ce.daily_plan_block_id = dpb.id AND ce.deleted_at IS NULL);
INSERT INTO plan_block_completion_migration (id) SELECT 1 WHERE NOT EXISTS (SELECT 1 FROM plan_block_completion_migration WHERE id = 1);
COMMIT;
