ALTER TABLE `wellness_entries`
  ADD COLUMN `calendar_event_id` BIGINT UNSIGNED NULL AFTER `user_id`,
  ADD UNIQUE KEY `uq_wellness_calendar_event` (`calendar_event_id`),
  ADD CONSTRAINT `fk_wellness_calendar_event`
    FOREIGN KEY (`calendar_event_id`) REFERENCES `calendar_events` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;
