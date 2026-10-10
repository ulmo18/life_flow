CREATE TABLE IF NOT EXISTS wellness_entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  calendar_event_id INTEGER NULL,
  entry_date TEXT NOT NULL,
  entry_type TEXT NOT NULL,
  occurred_time TEXT NULL,
  start_time TEXT NULL,
  end_time TEXT NULL,
  detail TEXT NULL,
  note TEXT NULL,
  rating INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (calendar_event_id) REFERENCES calendar_events(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (entry_type IN ('sleep', 'meal', 'snack', 'exercise', 'rest')),
  CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)
);

CREATE INDEX IF NOT EXISTS idx_wellness_user_date ON wellness_entries(user_id, entry_date, entry_type);
CREATE UNIQUE INDEX IF NOT EXISTS uq_wellness_calendar_event ON wellness_entries(calendar_event_id);
