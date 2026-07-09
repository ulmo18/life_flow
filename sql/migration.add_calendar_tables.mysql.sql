SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `calendar_date_meta` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `calendar_date` DATE NOT NULL,
  `locale_code` VARCHAR(8) NOT NULL DEFAULT 'KR',
  `date_type` VARCHAR(30) NOT NULL DEFAULT 'weekday',
  `holiday_name` VARCHAR(100) NULL,
  `is_holiday` TINYINT(1) NOT NULL DEFAULT 0,
  `is_substitute_holiday` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_calendar_date_meta_locale_date` (`locale_code`, `calendar_date`),
  KEY `idx_calendar_date_meta_date` (`calendar_date`),
  KEY `idx_calendar_date_meta_type` (`date_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_days` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `calendar_date` DATE NOT NULL,
  `plan_group_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_calendar_days_user_date` (`user_id`, `calendar_date`),
  KEY `idx_calendar_days_date` (`calendar_date`),
  KEY `idx_calendar_days_plan_group_id` (`plan_group_id`),
  CONSTRAINT `fk_calendar_days_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_calendar_days_plan_group`
    FOREIGN KEY (`plan_group_id`) REFERENCES `plan_groups` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `calendar_day_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(80) NOT NULL,
  `start_index` SMALLINT UNSIGNED NOT NULL,
  `end_index` SMALLINT UNSIGNED NOT NULL,
  `plan_template_id` BIGINT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_calendar_events_user_day` (`user_id`, `calendar_day_id`, `deleted_at`),
  KEY `idx_calendar_events_day_time` (`calendar_day_id`, `start_index`, `end_index`),
  KEY `idx_calendar_events_plan_template_id` (`plan_template_id`),
  CONSTRAINT `fk_calendar_events_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_calendar_events_day`
    FOREIGN KEY (`calendar_day_id`) REFERENCES `calendar_days` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_calendar_events_plan_template`
    FOREIGN KEY (`plan_template_id`) REFERENCES `plan_templates` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `chk_calendar_events_index_range`
    CHECK (`start_index` >= 0 AND `end_index` <= 144 AND `start_index` < `end_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
