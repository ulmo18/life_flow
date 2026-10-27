-- Apply once to existing installations; retain a backup before migration.
ALTER TABLE user_preferences ADD COLUMN calendar_retrospect_prompt_date DATE NULL;
