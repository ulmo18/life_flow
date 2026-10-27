-- Apply once to existing installations; the application also upgrades SQLite preferences.
ALTER TABLE user_preferences ADD COLUMN calendar_retrospect_prompt_date TEXT NULL;
