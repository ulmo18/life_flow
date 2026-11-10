ALTER TABLE daily_plan_blocks ADD COLUMN reminder_enabled INTEGER NOT NULL DEFAULT 0;
ALTER TABLE daily_plan_blocks ADD COLUMN reminder_minutes_before INTEGER NOT NULL DEFAULT 5;
