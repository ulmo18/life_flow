-- LifeFlow initial schema (MySQL 8.x / MariaDB 10.x compatible)
-- Charset/collation chosen for multilingual support.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `user` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nickname` VARCHAR(50) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'user',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `terms_agreed_at` DATETIME NULL,
  `privacy_agreed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  KEY `idx_user_role` (`role`),
  KEY `idx_user_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_device` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `device_token` VARCHAR(255) NOT NULL,
  `device_type` VARCHAR(20) NOT NULL,
  `notification_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `last_seen_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_device_token` (`device_token`),
  KEY `idx_user_device_user_id` (`user_id`),
  KEY `idx_user_device_type` (`device_type`),
  KEY `idx_user_device_notification_enabled` (`notification_enabled`),
  CONSTRAINT `fk_user_device_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `theme` VARCHAR(20) NOT NULL DEFAULT 'light',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_preferences_user` (`user_id`),
  CONSTRAINT `fk_user_preferences_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `chk_user_preferences_theme`
    CHECK (`theme` IN ('light', 'dark'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_tokens_token_hash` (`token_hash`),
  KEY `idx_password_reset_tokens_user_id` (`user_id`),
  KEY `idx_password_reset_tokens_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_reset_tokens_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `email` VARCHAR(191) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `is_success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_user_id` (`user_id`),
  KEY `idx_login_attempts_email_attempted_at` (`email`, `attempted_at`),
  KEY `idx_login_attempts_ip_attempted_at` (`ip_address`, `attempted_at`),
  KEY `idx_login_attempts_is_success` (`is_success`),
  CONSTRAINT `fk_login_attempts_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `selector` CHAR(18) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_remember_tokens_selector` (`selector`),
  KEY `idx_remember_tokens_user_id` (`user_id`),
  KEY `idx_remember_tokens_expires_at` (`expires_at`),
  CONSTRAINT `fk_remember_tokens_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(20) NOT NULL,
  `provider_user_id` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_social_accounts_provider_user` (`provider`, `provider_user_id`),
  KEY `idx_social_accounts_user_id` (`user_id`),
  KEY `idx_social_accounts_email` (`email`),
  CONSTRAINT `fk_social_accounts_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `goals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `parent_goal_id` BIGINT UNSIGNED NULL,
  `goal_type` VARCHAR(20) NOT NULL,
  `title` VARCHAR(80) NOT NULL,
  `behavior_when` VARCHAR(120) NULL,
  `behavior_where` VARCHAR(120) NULL,
  `behavior_how` VARCHAR(300) NULL,
  `period_start_date` DATE NULL,
  `period_end_date` DATE NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `completed_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_goals_user_deleted` (`user_id`, `deleted_at`),
  KEY `idx_goals_parent_goal_id` (`parent_goal_id`),
  KEY `idx_goals_type_status` (`goal_type`, `status`),
  CONSTRAINT `fk_goals_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_goals_parent`
    FOREIGN KEY (`parent_goal_id`) REFERENCES `goals` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `chk_goals_type`
    CHECK (`goal_type` IN ('bucket', 'yearly', 'half_year', 'quarterly', 'monthly')),
  CONSTRAINT `chk_goals_status`
    CHECK (`status` IN ('active', 'completed', 'paused', 'archived')),
  CONSTRAINT `chk_goals_period`
    CHECK (`period_start_date` IS NULL OR `period_end_date` IS NULL OR `period_start_date` <= `period_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plan_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `goal_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(80) NOT NULL,
  `importance` CHAR(1) NOT NULL DEFAULT 'D',
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plan_templates_user_id` (`user_id`),
  KEY `idx_plan_templates_goal_id` (`goal_id`),
  KEY `idx_plan_templates_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_plan_templates_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plan_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `source_plan_group_id` BIGINT UNSIGNED NULL,
  `version_no` INT UNSIGNED NOT NULL DEFAULT 1,
  `name` VARCHAR(80) NOT NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plan_groups_source_id` (`source_plan_group_id`),
  KEY `idx_plan_groups_user_deleted` (`user_id`, `deleted_at`),
  KEY `idx_plan_groups_user_updated` (`user_id`, `updated_at`),
  CONSTRAINT `fk_plan_groups_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_plan_groups_source`
    FOREIGN KEY (`source_plan_group_id`) REFERENCES `plan_groups` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plan_blocks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_group_id` BIGINT UNSIGNED NOT NULL,
  `plan_template_id` BIGINT UNSIGNED NOT NULL,
  `start_index` SMALLINT UNSIGNED NOT NULL,
  `end_index` SMALLINT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plan_blocks_group_order` (`plan_group_id`, `sort_order`),
  KEY `idx_plan_blocks_template_id` (`plan_template_id`),
  CONSTRAINT `fk_plan_blocks_group`
    FOREIGN KEY (`plan_group_id`) REFERENCES `plan_groups` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_plan_blocks_template`
    FOREIGN KEY (`plan_template_id`) REFERENCES `plan_templates` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `chk_plan_blocks_index_range`
    CHECK (`start_index` >= 0 AND `end_index` <= 144 AND `start_index` < `end_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `calendar_tag_palettes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(40) NOT NULL,
  `color_hex` CHAR(7) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_calendar_tag_palettes_slug` (`slug`),
  UNIQUE KEY `uq_calendar_tag_palettes_color` (`color_hex`),
  KEY `idx_calendar_tag_palettes_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `calendar_tag_palettes` (`slug`, `color_hex`, `sort_order`)
VALUES
  ('moss', '#8EA4A2', 1),
  ('grain', '#D6A04F', 2),
  ('clay', '#B98C6B', 3),
  ('leaf', '#7E9D72', 4),
  ('sage', '#6F9A8D', 5),
  ('rosewood', '#C77F7A', 6),
  ('sunset', '#C85F5A', 7),
  ('denim', '#6F83B7', 8),
  ('olive', '#9B8A67', 9),
  ('berry', '#B77490', 10),
  ('lichen', '#8D9B8F', 11),
  ('linen', '#A99A86', 12),
  ('bluegray', '#7896A6', 13),
  ('terra', '#D07C5D', 14),
  ('soil', '#7D7466', 15)
ON DUPLICATE KEY UPDATE
  `color_hex` = VALUES(`color_hex`),
  `sort_order` = VALUES(`sort_order`);

CREATE TABLE IF NOT EXISTS `calendar_tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `palette_id` BIGINT UNSIGNED NULL,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(40) NOT NULL,
  `color_hex` CHAR(7) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_calendar_tags_slug` (`slug`),
  KEY `idx_calendar_tags_user` (`user_id`, `deleted_at`),
  KEY `idx_calendar_tags_palette_id` (`palette_id`),
  KEY `idx_calendar_tags_sort` (`sort_order`),
  KEY `idx_calendar_tags_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_calendar_tags_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_calendar_tags_palette`
    FOREIGN KEY (`palette_id`) REFERENCES `calendar_tag_palettes` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `calendar_tags` (`user_id`, `palette_id`, `slug`, `name`, `color_hex`, `sort_order`, `is_system`)
SELECT NULL, p.id, 'fixed', '고정', p.color_hex, 1, 1
FROM `calendar_tag_palettes` p
WHERE p.slug = 'moss'
UNION ALL
SELECT NULL, p.id, 'health', '건강', p.color_hex, 2, 1
FROM `calendar_tag_palettes` p
WHERE p.slug = 'leaf'
UNION ALL
SELECT NULL, p.id, 'work', '업무', p.color_hex, 3, 1
FROM `calendar_tag_palettes` p
WHERE p.slug = 'sunset'
UNION ALL
SELECT NULL, p.id, 'rest', '휴식', p.color_hex, 4, 1
FROM `calendar_tag_palettes` p
WHERE p.slug = 'linen'
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `palette_id` = VALUES(`palette_id`),
  `color_hex` = VALUES(`color_hex`),
  `sort_order` = VALUES(`sort_order`),
  `is_system` = 1,
  `deleted_at` = NULL;

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `calendar_day_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(80) NOT NULL,
  `start_index` SMALLINT UNSIGNED NOT NULL,
  `end_index` SMALLINT UNSIGNED NOT NULL,
  `plan_template_id` BIGINT UNSIGNED NULL,
  `calendar_tag_id` BIGINT UNSIGNED NULL,
  `memo` TEXT NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_calendar_events_user_day` (`user_id`, `calendar_day_id`, `deleted_at`),
  KEY `idx_calendar_events_day_time` (`calendar_day_id`, `start_index`, `end_index`),
  KEY `idx_calendar_events_plan_template_id` (`plan_template_id`),
  KEY `idx_calendar_events_tag_id` (`calendar_tag_id`),
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
  CONSTRAINT `fk_calendar_events_tag`
    FOREIGN KEY (`calendar_tag_id`) REFERENCES `calendar_tags` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `chk_calendar_events_index_range`
    CHECK (`start_index` >= 0 AND `end_index` <= 144 AND `start_index` < `end_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `routines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `goal_id` BIGINT UNSIGNED NULL,
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
  KEY `idx_routines_goal_id` (`goal_id`),
  CONSTRAINT `fk_routines_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_routines_goal`
    FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`)
    ON DELETE SET NULL
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
