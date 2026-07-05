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
