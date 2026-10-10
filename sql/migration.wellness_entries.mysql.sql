CREATE TABLE IF NOT EXISTS `wellness_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `calendar_event_id` BIGINT UNSIGNED NULL,
  `entry_date` DATE NOT NULL,
  `entry_type` VARCHAR(20) NOT NULL,
  `occurred_time` TIME NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `detail` VARCHAR(160) NULL,
  `note` TEXT NULL,
  `rating` TINYINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wellness_user_date` (`user_id`, `entry_date`, `entry_type`),
  UNIQUE KEY `uq_wellness_calendar_event` (`calendar_event_id`),
  CONSTRAINT `fk_wellness_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wellness_calendar_event`
    FOREIGN KEY (`calendar_event_id`) REFERENCES `calendar_events` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_wellness_type`
    CHECK (`entry_type` IN ('sleep', 'meal', 'snack', 'exercise', 'rest')),
  CONSTRAINT `chk_wellness_rating`
    CHECK (`rating` IS NULL OR (`rating` BETWEEN 1 AND 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
