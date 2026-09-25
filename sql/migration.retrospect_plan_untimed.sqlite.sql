PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;

CREATE TABLE retrospect_report_plan_items_new (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  report_id INTEGER NOT NULL,
  plan_group_id INTEGER NULL,
  plan_block_id INTEGER NULL,
  plan_template_id INTEGER NULL,
  title_snapshot TEXT NOT NULL,
  start_index INTEGER NULL,
  end_index INTEGER NULL,
  importance_snapshot TEXT NOT NULL DEFAULT 'D',
  is_linked INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES retrospect_reports(id) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO retrospect_report_plan_items_new (
  id, report_id, plan_group_id, plan_block_id, plan_template_id,
  title_snapshot, start_index, end_index, importance_snapshot,
  is_linked, sort_order, created_at
)
SELECT
  id, report_id, plan_group_id, plan_block_id, plan_template_id,
  title_snapshot, start_index, end_index, importance_snapshot,
  is_linked, sort_order, created_at
FROM retrospect_report_plan_items;

DROP TABLE retrospect_report_plan_items;
ALTER TABLE retrospect_report_plan_items_new RENAME TO retrospect_report_plan_items;
CREATE INDEX IF NOT EXISTS idx_retrospect_plan_items_report ON retrospect_report_plan_items(report_id, sort_order);

COMMIT;
PRAGMA foreign_keys = ON;
