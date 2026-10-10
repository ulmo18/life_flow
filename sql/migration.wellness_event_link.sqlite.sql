ALTER TABLE wellness_entries ADD COLUMN calendar_event_id INTEGER NULL REFERENCES calendar_events(id) ON DELETE SET NULL ON UPDATE CASCADE;
CREATE UNIQUE INDEX IF NOT EXISTS uq_wellness_calendar_event ON wellness_entries(calendar_event_id);
