ALTER TABLE `retrospect_report_plan_items`
  ADD COLUMN `goal_title_snapshot` VARCHAR(120) NULL AFTER `importance_snapshot`;

ALTER TABLE `retrospect_report_routine_items`
  ADD COLUMN `goal_title_snapshot` VARCHAR(120) NULL AFTER `routine_name_snapshot`;

CREATE TABLE IF NOT EXISTS `retrospect_report_memo_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `note_id` BIGINT UNSIGNED NULL,
  `content_snapshot` TEXT NOT NULL,
  `created_at_snapshot` DATETIME NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retrospect_memo_items_report` (`report_id`, `sort_order`),
  CONSTRAINT `fk_retrospect_memo_items_report`
    FOREIGN KEY (`report_id`) REFERENCES `retrospect_reports` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
