-- Apply once to existing databases after backing up. Existing blocks start without a manually selected outcome.
ALTER TABLE daily_plan_blocks ADD COLUMN manual_status VARCHAR(20) NOT NULL DEFAULT 'pending';
