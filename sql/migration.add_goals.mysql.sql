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

ALTER TABLE `routines`
  ADD COLUMN `goal_id` BIGINT UNSIGNED NULL AFTER `user_id`,
  ADD KEY `idx_routines_goal_id` (`goal_id`),
  ADD CONSTRAINT `fk_routines_goal`
    FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
