CREATE TABLE IF NOT EXISTS `retrospect_report_milestone_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `item_type` VARCHAR(20) NOT NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `title_snapshot` VARCHAR(120) NOT NULL,
  `feedback_snapshot` VARCHAR(500) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retrospect_milestones_report` (`report_id`, `sort_order`),
  CONSTRAINT `fk_retrospect_milestones_report`
    FOREIGN KEY (`report_id`) REFERENCES `retrospect_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_retrospect_milestones_type` CHECK (`item_type` IN ('goal', 'routine'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
