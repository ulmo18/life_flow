PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;

CREATE TABLE daily_plan_items_new (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  daily_plan_id INTEGER NOT NULL,
  source_plan_block_id INTEGER NULL,
  source_plan_template_id INTEGER NULL,
  goal_id INTEGER NULL,
  title TEXT NOT NULL,
  importance TEXT NOT NULL DEFAULT 'D',
  start_index INTEGER NULL,
  end_index INTEGER NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (daily_plan_id) REFERENCES daily_plans(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (source_plan_block_id) REFERENCES plan_blocks(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (source_plan_template_id) REFERENCES plan_templates(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (
    (start_index IS NULL AND end_index IS NULL)
    OR (start_index IS NOT NULL AND end_index IS NOT NULL
        AND start_index >= 0 AND end_index <= 144 AND start_index < end_index)
  )
);

INSERT INTO daily_plan_items_new (
  id, daily_plan_id, source_plan_block_id, source_plan_template_id, goal_id,
  title, importance, start_index, end_index, sort_order,
  deleted_at, created_at, updated_at
)
SELECT
  id, daily_plan_id, source_plan_block_id, source_plan_template_id, goal_id,
  title, importance, start_index, end_index, sort_order,
  deleted_at, created_at, updated_at
FROM daily_plan_items;

DROP TABLE daily_plan_items;
ALTER TABLE daily_plan_items_new RENAME TO daily_plan_items;
CREATE INDEX IF NOT EXISTS idx_daily_plan_items_plan_order ON daily_plan_items(daily_plan_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_daily_plan_items_goal ON daily_plan_items(goal_id);
CREATE INDEX IF NOT EXISTS idx_daily_plan_items_source_template ON daily_plan_items(source_plan_template_id);

COMMIT;
PRAGMA foreign_keys = ON;
