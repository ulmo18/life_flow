SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `calendar_tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(40) NOT NULL,
  `name` VARCHAR(40) NOT NULL,
  `color_hex` CHAR(7) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_calendar_tags_slug` (`slug`),
  KEY `idx_calendar_tags_sort` (`sort_order`),
  KEY `idx_calendar_tags_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `calendar_tags` (`slug`, `name`, `color_hex`, `sort_order`)
VALUES
  ('sleep', '수면', '#8EA4A2', 1),
  ('meal', '식사', '#D6A04F', 2),
  ('commute-prep', '출퇴근 준비', '#B98C6B', 3),
  ('health', '건강', '#7E9D72', 4),
  ('exercise', '운동', '#6F9A8D', 5),
  ('hospital', '병원', '#C77F7A', 6),
  ('work', '업무', '#C85F5A', 7),
  ('study', '공부', '#6F83B7', 8),
  ('chore', '집안일', '#9B8A67', 9),
  ('hobby', '취미', '#B77490', 10),
  ('meditation', '명상', '#8D9B8F', 11),
  ('rest', '휴식', '#A99A86', 12),
  ('movement', '이동', '#7896A6', 13),
  ('relationship', '관계', '#D07C5D', 14),
  ('etc', '기타', '#7D7466', 15)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `color_hex` = VALUES(`color_hex`),
  `sort_order` = VALUES(`sort_order`),
  `deleted_at` = NULL;

SET @column_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'calendar_events'
    AND COLUMN_NAME = 'calendar_tag_id'
);

SET @alter_calendar_events := IF(
  @column_exists = 0,
  'ALTER TABLE `calendar_events` ADD COLUMN `calendar_tag_id` BIGINT UNSIGNED NULL AFTER `plan_template_id`',
  'SELECT 1'
);

PREPARE alter_calendar_events_stmt FROM @alter_calendar_events;
EXECUTE alter_calendar_events_stmt;
DEALLOCATE PREPARE alter_calendar_events_stmt;

SET @index_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'calendar_events'
    AND INDEX_NAME = 'idx_calendar_events_tag_id'
);

SET @add_calendar_events_tag_index := IF(
  @index_exists = 0,
  'ALTER TABLE `calendar_events` ADD KEY `idx_calendar_events_tag_id` (`calendar_tag_id`)',
  'SELECT 1'
);

PREPARE add_calendar_events_tag_index_stmt FROM @add_calendar_events_tag_index;
EXECUTE add_calendar_events_tag_index_stmt;
DEALLOCATE PREPARE add_calendar_events_tag_index_stmt;

SET @fk_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'calendar_events'
    AND CONSTRAINT_NAME = 'fk_calendar_events_tag'
);

SET @add_calendar_events_tag_fk := IF(
  @fk_exists = 0,
  'ALTER TABLE `calendar_events` ADD CONSTRAINT `fk_calendar_events_tag` FOREIGN KEY (`calendar_tag_id`) REFERENCES `calendar_tags` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);

PREPARE add_calendar_events_tag_fk_stmt FROM @add_calendar_events_tag_fk;
EXECUTE add_calendar_events_tag_fk_stmt;
DEALLOCATE PREPARE add_calendar_events_tag_fk_stmt;
