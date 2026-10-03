CREATE TABLE IF NOT EXISTS execution_energy_profiles (
  user_id INTEGER PRIMARY KEY,
  base_capacity INTEGER NOT NULL DEFAULT 500,
  effective_from TEXT NOT NULL,
  body_state_note TEXT NULL,
  mood_state_note TEXT NULL,
  stress_state_note TEXT NULL,
  injury_note TEXT NULL,
  injury_until TEXT NULL,
  inbody_note TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (base_capacity >= 5 AND base_capacity <= 1000 AND base_capacity % 5 = 0)
);

CREATE TABLE IF NOT EXISTS execution_energy_groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  effect_type TEXT NOT NULL,
  energy_value INTEGER NOT NULL,
  is_default INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (effect_type IN ('consume', 'recover')),
  CHECK (energy_value >= 5 AND energy_value <= 1000 AND energy_value % 5 = 0)
);
CREATE INDEX IF NOT EXISTS idx_execution_energy_groups_user ON execution_energy_groups(user_id, is_active);

CREATE TABLE IF NOT EXISTS execution_energy_days (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  energy_date TEXT NOT NULL,
  base_capacity_snapshot INTEGER NOT NULL,
  sleep_state INTEGER NULL,
  fatigue_state INTEGER NULL,
  mood_state INTEGER NULL,
  stress_state INTEGER NULL,
  burden_feedback TEXT NULL,
  morning_adjustment INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, energy_date),
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (burden_feedback IS NULL OR burden_feedback IN ('lower', 'similar', 'higher'))
);

CREATE TABLE IF NOT EXISTS execution_energy_event_settings (
  calendar_event_id INTEGER PRIMARY KEY,
  user_id INTEGER NOT NULL,
  group_id_snapshot INTEGER NULL,
  group_name_snapshot TEXT NULL,
  effect_type TEXT NOT NULL,
  energy_value INTEGER NOT NULL,
  is_override INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (calendar_event_id) REFERENCES calendar_events(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (effect_type IN ('consume', 'recover')),
  CHECK (energy_value >= 5 AND energy_value <= 1000 AND energy_value % 5 = 0)
);
CREATE INDEX IF NOT EXISTS idx_execution_energy_event_settings_user ON execution_energy_event_settings(user_id);

CREATE TABLE IF NOT EXISTS execution_energy_adjustments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  energy_date TEXT NOT NULL,
  time_index INTEGER NOT NULL DEFAULT 144,
  target_capacity INTEGER NOT NULL,
  reason TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (time_index >= 0 AND time_index <= 144),
  CHECK (target_capacity >= 0 AND target_capacity <= 1000 AND target_capacity % 5 = 0)
);
CREATE INDEX IF NOT EXISTS idx_execution_energy_adjustments_user_date ON execution_energy_adjustments(user_id, energy_date, time_index);

CREATE TABLE IF NOT EXISTS execution_energy_notices (
  user_id INTEGER NOT NULL,
  energy_date TEXT NOT NULL,
  notice_level INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, energy_date, notice_level),
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
);
