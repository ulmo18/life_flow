ALTER TABLE `calendar_events`
  ADD COLUMN `schedule_type` VARCHAR(20) NOT NULL DEFAULT 'timed' AFTER `title`,
  MODIFY COLUMN `start_index` SMALLINT UNSIGNED NULL,
  MODIFY COLUMN `end_index` SMALLINT UNSIGNED NULL;

ALTER TABLE `calendar_events`
  DROP CHECK `chk_calendar_events_index_range`;

ALTER TABLE `calendar_events`
  ADD CONSTRAINT `chk_calendar_events_index_range`
  CHECK (
    (`schedule_type` = 'timed' AND `start_index` >= 0 AND `end_index` <= 144 AND `start_index` < `end_index`)
    OR (`schedule_type` = 'unscheduled' AND `start_index` IS NULL AND `end_index` IS NULL)
  );
