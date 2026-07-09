-- LifeFlow SQLite schema (SQLite 3.x compatible)
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS user (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  nickname TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'user',
  is_active INTEGER NOT NULL DEFAULT 1,
  last_login_at TEXT NULL,
  terms_agreed_at TEXT NULL,
  privacy_agreed_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_user_role ON user(role);
CREATE INDEX IF NOT EXISTS idx_user_is_active ON user(is_active);

CREATE TABLE IF NOT EXISTS user_device (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  device_token TEXT NOT NULL UNIQUE,
  device_type TEXT NOT NULL,
  notification_enabled INTEGER NOT NULL DEFAULT 1,
  last_seen_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_device_user_id ON user_device(user_id);
CREATE INDEX IF NOT EXISTS idx_user_device_type ON user_device(device_type);
CREATE INDEX IF NOT EXISTS idx_user_device_notification_enabled ON user_device(notification_enabled);

CREATE TABLE IF NOT EXISTS user_preferences (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  theme TEXT NOT NULL DEFAULT 'light',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (theme IN ('light', 'dark'))
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token_hash TEXT NOT NULL UNIQUE,
  expires_at TEXT NOT NULL,
  used_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user_id ON password_reset_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_expires_at ON password_reset_tokens(expires_at);

CREATE TABLE IF NOT EXISTS login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  email TEXT NOT NULL,
  ip_address TEXT NOT NULL,
  user_agent TEXT NULL,
  is_success INTEGER NOT NULL DEFAULT 0,
  attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_login_attempts_user_id ON login_attempts(user_id);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email_attempted_at ON login_attempts(email, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_attempted_at ON login_attempts(ip_address, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_is_success ON login_attempts(is_success);

CREATE TABLE IF NOT EXISTS remember_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  selector TEXT NOT NULL UNIQUE,
  token_hash TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_remember_tokens_user_id ON remember_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_remember_tokens_expires_at ON remember_tokens(expires_at);

CREATE TABLE IF NOT EXISTS social_accounts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  provider TEXT NOT NULL,
  provider_user_id TEXT NOT NULL,
  email TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE(provider, provider_user_id)
);

CREATE INDEX IF NOT EXISTS idx_social_accounts_user_id ON social_accounts(user_id);
CREATE INDEX IF NOT EXISTS idx_social_accounts_email ON social_accounts(email);

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

CREATE TABLE IF NOT EXISTS plan_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  goal_id INTEGER NULL,
  title TEXT NOT NULL,
  importance TEXT NOT NULL DEFAULT 'D',
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_plan_templates_user_id ON plan_templates(user_id);
CREATE INDEX IF NOT EXISTS idx_plan_templates_goal_id ON plan_templates(goal_id);
CREATE INDEX IF NOT EXISTS idx_plan_templates_deleted_at ON plan_templates(deleted_at);

CREATE TABLE IF NOT EXISTS plan_groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  source_plan_group_id INTEGER NULL,
  version_no INTEGER NOT NULL DEFAULT 1,
  name TEXT NOT NULL,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (source_plan_group_id) REFERENCES plan_groups(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_plan_groups_source_id ON plan_groups(source_plan_group_id);
CREATE INDEX IF NOT EXISTS idx_plan_groups_user_deleted ON plan_groups(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_plan_groups_user_updated ON plan_groups(user_id, updated_at);

CREATE TABLE IF NOT EXISTS plan_blocks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  plan_group_id INTEGER NOT NULL,
  plan_template_id INTEGER NOT NULL,
  start_index INTEGER NOT NULL,
  end_index INTEGER NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (plan_group_id) REFERENCES plan_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (plan_template_id) REFERENCES plan_templates(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK (start_index >= 0 AND end_index <= 144 AND start_index < end_index)
);

CREATE INDEX IF NOT EXISTS idx_plan_blocks_group_order ON plan_blocks(plan_group_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_plan_blocks_template_id ON plan_blocks(plan_template_id);

CREATE TABLE IF NOT EXISTS calendar_date_meta (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  calendar_date TEXT NOT NULL,
  locale_code TEXT NOT NULL DEFAULT 'KR',
  date_type TEXT NOT NULL DEFAULT 'weekday',
  holiday_name TEXT NULL,
  is_holiday INTEGER NOT NULL DEFAULT 0,
  is_substitute_holiday INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(locale_code, calendar_date)
);

CREATE INDEX IF NOT EXISTS idx_calendar_date_meta_date ON calendar_date_meta(calendar_date);
CREATE INDEX IF NOT EXISTS idx_calendar_date_meta_type ON calendar_date_meta(date_type);

CREATE TABLE IF NOT EXISTS calendar_days (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  calendar_date TEXT NOT NULL,
  plan_group_id INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (plan_group_id) REFERENCES plan_groups(id) ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE(user_id, calendar_date)
);

CREATE INDEX IF NOT EXISTS idx_calendar_days_date ON calendar_days(calendar_date);
CREATE INDEX IF NOT EXISTS idx_calendar_days_plan_group_id ON calendar_days(plan_group_id);

CREATE TABLE IF NOT EXISTS calendar_tag_palettes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  color_hex TEXT NOT NULL UNIQUE,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_calendar_tag_palettes_sort ON calendar_tag_palettes(sort_order);

INSERT OR IGNORE INTO calendar_tag_palettes (slug, color_hex, sort_order) VALUES
  ('moss', '#8EA4A2', 1),
  ('grain', '#D6A04F', 2),
  ('clay', '#B98C6B', 3),
  ('leaf', '#7E9D72', 4),
  ('sage', '#6F9A8D', 5),
  ('rosewood', '#C77F7A', 6),
  ('sunset', '#C85F5A', 7),
  ('denim', '#6F83B7', 8),
  ('olive', '#9B8A67', 9),
  ('berry', '#B77490', 10),
  ('lichen', '#8D9B8F', 11),
  ('linen', '#A99A86', 12),
  ('bluegray', '#7896A6', 13),
  ('terra', '#D07C5D', 14),
  ('soil', '#7D7466', 15);

CREATE TABLE IF NOT EXISTS calendar_tags (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  palette_id INTEGER NULL,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  color_hex TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  is_system INTEGER NOT NULL DEFAULT 0,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (palette_id) REFERENCES calendar_tag_palettes(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_calendar_tags_user ON calendar_tags(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_calendar_tags_palette_id ON calendar_tags(palette_id);
CREATE INDEX IF NOT EXISTS idx_calendar_tags_sort ON calendar_tags(sort_order);
CREATE INDEX IF NOT EXISTS idx_calendar_tags_deleted_at ON calendar_tags(deleted_at);

INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'fixed', '고정', color_hex, 1, 1 FROM calendar_tag_palettes WHERE slug = 'moss';
INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'health', '건강', color_hex, 2, 1 FROM calendar_tag_palettes WHERE slug = 'leaf';
INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'work', '업무', color_hex, 3, 1 FROM calendar_tag_palettes WHERE slug = 'sunset';
INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'rest', '휴식', color_hex, 4, 1 FROM calendar_tag_palettes WHERE slug = 'linen';

CREATE TABLE IF NOT EXISTS calendar_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  calendar_day_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  start_index INTEGER NOT NULL,
  end_index INTEGER NOT NULL,
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
  CHECK (start_index >= 0 AND end_index <= 144 AND start_index < end_index)
);

CREATE INDEX IF NOT EXISTS idx_calendar_events_user_day ON calendar_events(user_id, calendar_day_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_calendar_events_day_time ON calendar_events(calendar_day_id, start_index, end_index);
CREATE INDEX IF NOT EXISTS idx_calendar_events_plan_template_id ON calendar_events(plan_template_id);
CREATE INDEX IF NOT EXISTS idx_calendar_events_tag_id ON calendar_events(calendar_tag_id);

CREATE TABLE IF NOT EXISTS routines (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  goal_id INTEGER NULL,
  name TEXT NOT NULL,
  start_date TEXT NOT NULL,
  duration_days INTEGER NOT NULL DEFAULT 60,
  reminder_enabled INTEGER NOT NULL DEFAULT 0,
  reminder_time TEXT NULL,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (duration_days >= 7 AND duration_days <= 60)
);

CREATE INDEX IF NOT EXISTS idx_routines_user_deleted ON routines(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_routines_user_date ON routines(user_id, start_date);
CREATE INDEX IF NOT EXISTS idx_routines_goal_id ON routines(goal_id);

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
