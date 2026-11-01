-- Apply once to existing databases; normal SQLite startup also adds the column if missing.
ALTER TABLE daily_plan_blocks ADD COLUMN manual_status TEXT NOT NULL DEFAULT 'pending';
