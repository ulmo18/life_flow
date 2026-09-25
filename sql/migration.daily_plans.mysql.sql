CREATE TABLE IF NOT EXISTS `daily_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `calendar_day_id` BIGINT UNSIGNED NOT NULL,
  `source_plan_group_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(80) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_plans_calendar_day` (`calendar_day_id`),
  KEY `idx_daily_plans_user` (`user_id`),
  KEY `idx_daily_plans_source_group` (`source_plan_group_id`),
  CONSTRAINT `fk_daily_plans_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_plans_day` FOREIGN KEY (`calendar_day_id`) REFERENCES `calendar_days` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_plans_source_group` FOREIGN KEY (`source_plan_group_id`) REFERENCES `plan_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_plan_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `daily_plan_id` BIGINT UNSIGNED NOT NULL,
  `source_plan_block_id` BIGINT UNSIGNED NULL,
  `source_plan_template_id` BIGINT UNSIGNED NULL,
  `goal_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(80) NOT NULL,
  `importance` CHAR(1) NOT NULL DEFAULT 'D',
  `start_index` SMALLINT UNSIGNED NULL,
  `end_index` SMALLINT UNSIGNED NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_daily_plan_items_plan_order` (`daily_plan_id`, `sort_order`),
  KEY `idx_daily_plan_items_goal` (`goal_id`),
  KEY `idx_daily_plan_items_source_template` (`source_plan_template_id`),
  CONSTRAINT `fk_daily_plan_items_plan` FOREIGN KEY (`daily_plan_id`) REFERENCES `daily_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_plan_items_source_block` FOREIGN KEY (`source_plan_block_id`) REFERENCES `plan_blocks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_plan_items_source_template` FOREIGN KEY (`source_plan_template_id`) REFERENCES `plan_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_plan_items_goal` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_daily_plan_items_index_range` CHECK (
    (`start_index` IS NULL AND `end_index` IS NULL)
    OR (`start_index` IS NOT NULL AND `end_index` IS NOT NULL
        AND `start_index` >= 0 AND `end_index` <= 144 AND `start_index` < `end_index`)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `calendar_events`
  ADD COLUMN `daily_plan_item_id` BIGINT UNSIGNED NULL AFTER `plan_template_id`,
  ADD KEY `idx_calendar_events_daily_plan_item_id` (`daily_plan_item_id`),
  ADD CONSTRAINT `fk_calendar_events_daily_plan_item`
    FOREIGN KEY (`daily_plan_item_id`) REFERENCES `daily_plan_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO `daily_plans` (`user_id`, `calendar_day_id`, `source_plan_group_id`, `name`, `created_at`, `updated_at`)
SELECT cd.user_id, cd.id, cd.plan_group_id, COALESCE(pg.name, CONCAT('[', DATE_FORMAT(cd.calendar_date, '%Y-%m-%d'), ']의 계획')), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM `calendar_days` cd
LEFT JOIN `plan_groups` pg ON pg.id = cd.plan_group_id
WHERE cd.plan_group_id IS NOT NULL
ON DUPLICATE KEY UPDATE `source_plan_group_id` = VALUES(`source_plan_group_id`);

INSERT INTO `daily_plan_items` (
  `daily_plan_id`, `source_plan_block_id`, `source_plan_template_id`, `goal_id`,
  `title`, `importance`, `start_index`, `end_index`, `sort_order`, `created_at`, `updated_at`
)
SELECT dp.id, pb.id, pt.id, pt.goal_id, pt.title, pt.importance,
       pb.start_index, pb.end_index, pb.sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM `daily_plans` dp
INNER JOIN `plan_blocks` pb ON pb.plan_group_id = dp.source_plan_group_id
INNER JOIN `plan_templates` pt ON pt.id = pb.plan_template_id
WHERE NOT EXISTS (
  SELECT 1 FROM `daily_plan_items` dpi WHERE dpi.daily_plan_id = dp.id
);

UPDATE `calendar_events` ce
INNER JOIN `calendar_days` cd ON cd.id = ce.calendar_day_id
INNER JOIN `daily_plans` dp ON dp.calendar_day_id = cd.id
INNER JOIN `daily_plan_items` dpi
  ON dpi.daily_plan_id = dp.id AND dpi.source_plan_template_id = ce.plan_template_id
SET ce.daily_plan_item_id = dpi.id
WHERE ce.plan_template_id IS NOT NULL AND ce.daily_plan_item_id IS NULL;
