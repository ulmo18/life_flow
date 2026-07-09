CREATE TABLE IF NOT EXISTS `routines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(60) NOT NULL,
  `start_date` DATE NOT NULL,
  `duration_days` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  `reminder_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `reminder_time` TIME NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_routines_user_deleted` (`user_id`, `deleted_at`),
  KEY `idx_routines_user_date` (`user_id`, `start_date`),
  CONSTRAINT `fk_routines_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `chk_routines_duration`
    CHECK (`duration_days` >= 7 AND `duration_days` <= 60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `routine_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `routine_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `log_date` DATE NOT NULL,
  `is_done` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_routine_logs_routine_date` (`routine_id`, `log_date`),
  KEY `idx_routine_logs_user_date` (`user_id`, `log_date`),
  KEY `idx_routine_logs_done` (`is_done`),
  CONSTRAINT `fk_routine_logs_routine`
    FOREIGN KEY (`routine_id`) REFERENCES `routines` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_routine_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
