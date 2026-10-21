-- Apply once to an existing MySQL/MariaDB database after the daily-plan migration.
-- The INSERT and UPDATE backfill statements are safe to run again.

CREATE TABLE IF NOT EXISTS `daily_plan_blocks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `daily_plan_item_id` BIGINT UNSIGNED NOT NULL,
  `start_index` SMALLINT UNSIGNED NOT NULL,
  `end_index` SMALLINT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_daily_plan_blocks_item_order` (`daily_plan_item_id`, `sort_order`),
  CONSTRAINT `fk_daily_plan_blocks_item`
    FOREIGN KEY (`daily_plan_item_id`) REFERENCES `daily_plan_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_daily_plan_blocks_index_range`
    CHECK (`start_index` >= 0 AND `end_index` <= 144 AND `start_index` < `end_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `calendar_events`
  ADD COLUMN `daily_plan_block_id` BIGINT UNSIGNED NULL AFTER `daily_plan_item_id`,
  ADD KEY `idx_calendar_events_daily_plan_block_id` (`daily_plan_block_id`),
  ADD CONSTRAINT `fk_calendar_events_daily_plan_block`
    FOREIGN KEY (`daily_plan_block_id`) REFERENCES `daily_plan_blocks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO `daily_plan_blocks` (
  `daily_plan_item_id`, `start_index`, `end_index`, `sort_order`, `created_at`, `updated_at`
)
SELECT dpi.id, dpi.start_index, dpi.end_index, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM `daily_plan_items` dpi
WHERE dpi.deleted_at IS NULL
  AND dpi.start_index IS NOT NULL
  AND dpi.end_index IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `daily_plan_blocks` dpb
    WHERE dpb.daily_plan_item_id = dpi.id
  );

UPDATE `calendar_events` ce
INNER JOIN `daily_plan_blocks` dpb ON dpb.daily_plan_item_id = ce.daily_plan_item_id
SET ce.daily_plan_block_id = dpb.id
WHERE ce.daily_plan_item_id IS NOT NULL
  AND ce.daily_plan_block_id IS NULL
  AND dpb.sort_order = 1;
