-- Add Plan feature tables (MySQL 8.x / MariaDB compatible)

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
