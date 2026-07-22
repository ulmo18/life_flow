CREATE TABLE IF NOT EXISTS `retrospect_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `auto_publish_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `auto_publish_time` TIME NOT NULL DEFAULT '22:00:00',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_retrospect_settings_user` (`user_id`),
  CONSTRAINT `fk_retrospect_settings_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retrospect_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `report_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `today_review` TEXT NULL,
  `today_thoughts` TEXT NULL,
  `tomorrow_plan` TEXT NULL,
  `plan_total_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `plan_linked_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `plan_unlinked_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `plan_achievement_rate` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `routine_total_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `routine_done_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `routine_achievement_rate` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `linked_actual_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `linked_actual_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `submitted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_retrospect_reports_user_date` (`user_id`, `report_date`),
  KEY `idx_retrospect_reports_user_status` (`user_id`, `status`),
  KEY `idx_retrospect_reports_date` (`report_date`),
  CONSTRAINT `fk_retrospect_reports_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `chk_retrospect_reports_status`
    CHECK (`status` IN ('draft', 'submitted'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retrospect_report_plan_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `plan_group_id` BIGINT UNSIGNED NULL,
  `plan_block_id` BIGINT UNSIGNED NULL,
  `plan_template_id` BIGINT UNSIGNED NULL,
  `title_snapshot` VARCHAR(80) NOT NULL,
  `start_index` SMALLINT UNSIGNED NOT NULL,
  `end_index` SMALLINT UNSIGNED NOT NULL,
  `importance_snapshot` CHAR(1) NOT NULL DEFAULT 'D',
  `is_linked` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retrospect_plan_items_report` (`report_id`, `sort_order`),
  CONSTRAINT `fk_retrospect_plan_items_report`
    FOREIGN KEY (`report_id`) REFERENCES `retrospect_reports` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retrospect_report_actual_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `calendar_day_id` BIGINT UNSIGNED NULL,
  `calendar_event_id` BIGINT UNSIGNED NULL,
  `title_snapshot` VARCHAR(80) NOT NULL,
  `memo_snapshot` TEXT NULL,
  `start_index` SMALLINT UNSIGNED NOT NULL,
  `end_index` SMALLINT UNSIGNED NOT NULL,
  `tag_name_snapshot` VARCHAR(40) NULL,
  `tag_color_snapshot` CHAR(7) NULL,
  `plan_template_id_snapshot` BIGINT UNSIGNED NULL,
  `plan_importance_snapshot` CHAR(1) NULL,
  `is_linked` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retrospect_actual_items_report_time` (`report_id`, `start_index`, `end_index`),
  KEY `idx_retrospect_actual_items_report_tag` (`report_id`, `tag_name_snapshot`),
  CONSTRAINT `fk_retrospect_actual_items_report`
    FOREIGN KEY (`report_id`) REFERENCES `retrospect_reports` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retrospect_report_routine_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `routine_id` BIGINT UNSIGNED NULL,
  `routine_name_snapshot` VARCHAR(60) NOT NULL,
  `state_snapshot` VARCHAR(10) NOT NULL DEFAULT 'blank',
  `was_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retrospect_routine_items_report` (`report_id`, `sort_order`),
  CONSTRAINT `fk_retrospect_routine_items_report`
    FOREIGN KEY (`report_id`) REFERENCES `retrospect_reports` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `chk_retrospect_routine_items_state`
    CHECK (`state_snapshot` IN ('blank', 'O', 'X'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
