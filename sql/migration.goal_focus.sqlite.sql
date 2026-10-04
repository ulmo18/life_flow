ALTER TABLE goals ADD COLUMN is_focused INTEGER NOT NULL DEFAULT 0 CHECK (is_focused IN (0, 1));
ALTER TABLE goals ADD COLUMN focused_at TEXT NULL;
CREATE INDEX IF NOT EXISTS idx_goals_user_focus ON goals(user_id, is_focused, focused_at);
