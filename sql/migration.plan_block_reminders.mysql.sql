ALTER TABLE daily_plan_blocks
  ADD COLUMN reminder_enabled TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN reminder_minutes_before SMALLINT UNSIGNED NOT NULL DEFAULT 5;
