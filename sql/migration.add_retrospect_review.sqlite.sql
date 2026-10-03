ALTER TABLE retrospect_report_plan_items ADD COLUMN goal_title_snapshot TEXT NULL;
ALTER TABLE retrospect_report_routine_items ADD COLUMN goal_title_snapshot TEXT NULL;

CREATE TABLE IF NOT EXISTS retrospect_report_memo_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  report_id INTEGER NOT NULL,
  note_id INTEGER NULL,
  content_snapshot TEXT NOT NULL,
  created_at_snapshot TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES retrospect_reports(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_retrospect_memo_items_report ON retrospect_report_memo_items(report_id, sort_order);
