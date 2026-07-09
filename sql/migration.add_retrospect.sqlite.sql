CREATE TABLE IF NOT EXISTS retrospect_settings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  auto_publish_enabled INTEGER NOT NULL DEFAULT 0,
  auto_publish_time TEXT NOT NULL DEFAULT '22:00',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE(user_id)
);

CREATE TABLE IF NOT EXISTS retrospect_reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  report_date TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'draft',
  today_review TEXT NULL,
  today_thoughts TEXT NULL,
  tomorrow_plan TEXT NULL,
  plan_total_count INTEGER NOT NULL DEFAULT 0,
  plan_linked_count INTEGER NOT NULL DEFAULT 0,
  plan_unlinked_count INTEGER NOT NULL DEFAULT 0,
  plan_achievement_rate INTEGER NOT NULL DEFAULT 0,
  routine_total_count INTEGER NOT NULL DEFAULT 0,
  routine_done_count INTEGER NOT NULL DEFAULT 0,
  routine_achievement_rate INTEGER NOT NULL DEFAULT 0,
  linked_actual_minutes INTEGER NOT NULL DEFAULT 0,
  linked_actual_count INTEGER NOT NULL DEFAULT 0,
  submitted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE(user_id, report_date),
  CHECK (status IN ('draft', 'submitted'))
);

CREATE INDEX IF NOT EXISTS idx_retrospect_reports_user_status ON retrospect_reports(user_id, status);
CREATE INDEX IF NOT EXISTS idx_retrospect_reports_date ON retrospect_reports(report_date);

CREATE TABLE IF NOT EXISTS retrospect_report_plan_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  report_id INTEGER NOT NULL,
  plan_group_id INTEGER NULL,
  plan_block_id INTEGER NULL,
  plan_template_id INTEGER NULL,
  title_snapshot TEXT NOT NULL,
  start_index INTEGER NOT NULL,
  end_index INTEGER NOT NULL,
  importance_snapshot TEXT NOT NULL DEFAULT 'D',
  is_linked INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES retrospect_reports(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_retrospect_plan_items_report ON retrospect_report_plan_items(report_id, sort_order);

CREATE TABLE IF NOT EXISTS retrospect_report_actual_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  report_id INTEGER NOT NULL,
  calendar_day_id INTEGER NULL,
  calendar_event_id INTEGER NULL,
  title_snapshot TEXT NOT NULL,
  start_index INTEGER NOT NULL,
  end_index INTEGER NOT NULL,
  tag_name_snapshot TEXT NULL,
  tag_color_snapshot TEXT NULL,
  plan_template_id_snapshot INTEGER NULL,
  plan_importance_snapshot TEXT NULL,
  is_linked INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES retrospect_reports(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_retrospect_actual_items_report_time ON retrospect_report_actual_items(report_id, start_index, end_index);
CREATE INDEX IF NOT EXISTS idx_retrospect_actual_items_report_tag ON retrospect_report_actual_items(report_id, tag_name_snapshot);

CREATE TABLE IF NOT EXISTS retrospect_report_routine_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  report_id INTEGER NOT NULL,
  routine_id INTEGER NULL,
  routine_name_snapshot TEXT NOT NULL,
  state_snapshot TEXT NOT NULL DEFAULT 'blank',
  was_active INTEGER NOT NULL DEFAULT 1,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES retrospect_reports(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (state_snapshot IN ('blank', 'O', 'X'))
);

CREATE INDEX IF NOT EXISTS idx_retrospect_routine_items_report ON retrospect_report_routine_items(report_id, sort_order);
