CREATE TABLE IF NOT EXISTS routines (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  start_date TEXT NOT NULL,
  duration_days INTEGER NOT NULL DEFAULT 60,
  reminder_enabled INTEGER NOT NULL DEFAULT 0,
  reminder_time TEXT NULL,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (duration_days >= 7 AND duration_days <= 60)
);

CREATE INDEX IF NOT EXISTS idx_routines_user_deleted ON routines(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_routines_user_date ON routines(user_id, start_date);

CREATE TABLE IF NOT EXISTS routine_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  routine_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  log_date TEXT NOT NULL,
  is_done INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE(routine_id, log_date)
);

CREATE INDEX IF NOT EXISTS idx_routine_logs_user_date ON routine_logs(user_id, log_date);
CREATE INDEX IF NOT EXISTS idx_routine_logs_done ON routine_logs(is_done);
