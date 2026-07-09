CREATE TABLE IF NOT EXISTS goals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  parent_goal_id INTEGER NULL,
  goal_type TEXT NOT NULL,
  title TEXT NOT NULL,
  behavior_when TEXT NULL,
  behavior_where TEXT NULL,
  behavior_how TEXT NULL,
  period_start_date TEXT NULL,
  period_end_date TEXT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  sort_order INTEGER NOT NULL DEFAULT 1,
  completed_at TEXT NULL,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (parent_goal_id) REFERENCES goals(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (goal_type IN ('bucket', 'yearly', 'half_year', 'quarterly', 'monthly')),
  CHECK (status IN ('active', 'completed', 'paused', 'archived')),
  CHECK (period_start_date IS NULL OR period_end_date IS NULL OR period_start_date <= period_end_date)
);

CREATE INDEX IF NOT EXISTS idx_goals_user_deleted ON goals(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_goals_parent_goal_id ON goals(parent_goal_id);
CREATE INDEX IF NOT EXISTS idx_goals_type_status ON goals(goal_type, status);

ALTER TABLE routines ADD COLUMN goal_id INTEGER NULL;
CREATE INDEX IF NOT EXISTS idx_routines_goal_id ON routines(goal_id);
