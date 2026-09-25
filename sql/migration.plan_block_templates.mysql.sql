CREATE TABLE IF NOT EXISTS `plan_block_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `goal_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(80) NOT NULL,
  `duration_index` SMALLINT UNSIGNED NOT NULL,
  `importance` CHAR(1) NOT NULL DEFAULT 'D',
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plan_block_templates_user_deleted` (`user_id`, `deleted_at`),
  KEY `idx_plan_block_templates_goal_id` (`goal_id`),
  CONSTRAINT `fk_plan_block_templates_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_plan_block_templates_goal`
    FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_plan_block_templates_duration`
    CHECK (`duration_index` BETWEEN 1 AND 143),
  CONSTRAINT `chk_plan_block_templates_importance`
    CHECK (`importance` IN ('A', 'B', 'C', 'D'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
