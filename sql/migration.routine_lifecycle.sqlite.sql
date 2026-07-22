PRAGMA foreign_keys = OFF;

CREATE TABLE routines_lifecycle_new (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  goal_id INTEGER NULL,
  name TEXT NOT NULL,
  start_date TEXT NOT NULL,
  duration_days INTEGER NOT NULL DEFAULT 60,
  status TEXT NOT NULL DEFAULT 'active',
  ended_at TEXT NULL,
  reminder_enabled INTEGER NOT NULL DEFAULT 0,
  reminder_time TEXT NULL,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (duration_days >= 1 AND duration_days <= 365),
  CHECK (status IN ('active', 'completed', 'stopped'))
);

INSERT INTO routines_lifecycle_new (
  id, user_id, goal_id, name, start_date, duration_days,
  status, ended_at, reminder_enabled, reminder_time, deleted_at, created_at, updated_at
)
SELECT
  id, user_id, goal_id, name, start_date, duration_days,
  'active', NULL, reminder_enabled, reminder_time, deleted_at, created_at, updated_at
FROM routines;

DROP TABLE routines;
ALTER TABLE routines_lifecycle_new RENAME TO routines;

CREATE INDEX IF NOT EXISTS idx_routines_user_deleted ON routines(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_routines_user_date ON routines(user_id, start_date);
CREATE INDEX IF NOT EXISTS idx_routines_goal_id ON routines(goal_id);
CREATE INDEX IF NOT EXISTS idx_routines_user_status ON routines(user_id, status);

PRAGMA foreign_keys = ON;
