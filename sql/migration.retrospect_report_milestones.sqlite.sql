CREATE TABLE IF NOT EXISTS retrospect_report_milestone_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  report_id INTEGER NOT NULL,
  item_type TEXT NOT NULL,
  source_id INTEGER NULL,
  title_snapshot TEXT NOT NULL,
  feedback_snapshot TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES retrospect_reports(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (item_type IN ('goal', 'routine'))
);

CREATE INDEX IF NOT EXISTS idx_retrospect_milestones_report ON retrospect_report_milestone_items(report_id, sort_order);
