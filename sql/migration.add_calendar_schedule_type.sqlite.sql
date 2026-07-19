PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;

ALTER TABLE calendar_events RENAME TO calendar_events_legacy;

CREATE TABLE calendar_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  calendar_day_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  schedule_type TEXT NOT NULL DEFAULT 'timed',
  start_index INTEGER NULL,
  end_index INTEGER NULL,
  plan_template_id INTEGER NULL,
  calendar_tag_id INTEGER NULL,
  memo TEXT NULL,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (calendar_day_id) REFERENCES calendar_days(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (plan_template_id) REFERENCES plan_templates(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (calendar_tag_id) REFERENCES calendar_tags(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (
    (schedule_type = 'timed' AND start_index >= 0 AND end_index <= 144 AND start_index < end_index)
    OR (schedule_type = 'unscheduled' AND start_index IS NULL AND end_index IS NULL)
  )
);

INSERT INTO calendar_events (
  id, user_id, calendar_day_id, title, schedule_type, start_index, end_index,
  plan_template_id, calendar_tag_id, memo, deleted_at, created_at, updated_at
)
SELECT
  id, user_id, calendar_day_id, title, 'timed', start_index, end_index,
  plan_template_id, calendar_tag_id, memo, deleted_at, created_at, updated_at
FROM calendar_events_legacy;

DROP TABLE calendar_events_legacy;

CREATE INDEX IF NOT EXISTS idx_calendar_events_user_day ON calendar_events(user_id, calendar_day_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_calendar_events_day_time ON calendar_events(calendar_day_id, start_index, end_index);
CREATE INDEX IF NOT EXISTS idx_calendar_events_plan_template_id ON calendar_events(plan_template_id);
CREATE INDEX IF NOT EXISTS idx_calendar_events_tag_id ON calendar_events(calendar_tag_id);

COMMIT;
PRAGMA foreign_keys = ON;
