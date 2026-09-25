CREATE TABLE IF NOT EXISTS plan_block_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  goal_id INTEGER NULL,
  title TEXT NOT NULL,
  duration_index INTEGER NOT NULL,
  importance TEXT NOT NULL DEFAULT 'D',
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (duration_index BETWEEN 1 AND 143),
  CHECK (importance IN ('A', 'B', 'C', 'D'))
);

CREATE INDEX IF NOT EXISTS idx_plan_block_templates_user_deleted
  ON plan_block_templates(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_plan_block_templates_goal_id
  ON plan_block_templates(goal_id);
