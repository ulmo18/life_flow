SET NAMES utf8mb4;

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

SET @column_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_tags' AND COLUMN_NAME = 'user_id'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `calendar_tags` ADD COLUMN `user_id` BIGINT UNSIGNED NULL AFTER `id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_tags' AND COLUMN_NAME = 'palette_id'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `calendar_tags` ADD COLUMN `palette_id` BIGINT UNSIGNED NULL AFTER `user_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_tags' AND COLUMN_NAME = 'is_system'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `calendar_tags` ADD COLUMN `is_system` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sort_order`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_events' AND COLUMN_NAME = 'memo'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `calendar_events` ADD COLUMN `memo` TEXT NULL AFTER `calendar_tag_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_tags' AND INDEX_NAME = 'idx_calendar_tags_user'
);
SET @sql := IF(@index_exists = 0, 'ALTER TABLE `calendar_tags` ADD KEY `idx_calendar_tags_user` (`user_id`, `deleted_at`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_tags' AND INDEX_NAME = 'idx_calendar_tags_palette_id'
);
SET @sql := IF(@index_exists = 0, 'ALTER TABLE `calendar_tags` ADD KEY `idx_calendar_tags_palette_id` (`palette_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_tags' AND CONSTRAINT_NAME = 'fk_calendar_tags_user'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE `calendar_tags` ADD CONSTRAINT `fk_calendar_tags_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calendar_tags' AND CONSTRAINT_NAME = 'fk_calendar_tags_palette'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE `calendar_tags` ADD CONSTRAINT `fk_calendar_tags_palette` FOREIGN KEY (`palette_id`) REFERENCES `calendar_tag_palettes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE calendar_tags ct
INNER JOIN calendar_tag_palettes p ON p.color_hex = ct.color_hex
SET ct.palette_id = p.id
WHERE ct.palette_id IS NULL;

UPDATE calendar_tags
SET deleted_at = COALESCE(deleted_at, CURRENT_TIMESTAMP),
    is_system = 0
WHERE slug NOT IN ('fixed', 'health', 'work', 'rest');

INSERT INTO `calendar_tags` (`user_id`, `palette_id`, `slug`, `name`, `color_hex`, `sort_order`, `is_system`)
SELECT NULL, p.id, 'fixed', '고정', p.color_hex, 1, 1 FROM calendar_tag_palettes p WHERE p.slug = 'moss'
ON DUPLICATE KEY UPDATE name = VALUES(name), palette_id = VALUES(palette_id), color_hex = VALUES(color_hex), sort_order = VALUES(sort_order), is_system = 1, deleted_at = NULL;

INSERT INTO `calendar_tags` (`user_id`, `palette_id`, `slug`, `name`, `color_hex`, `sort_order`, `is_system`)
SELECT NULL, p.id, 'health', '건강', p.color_hex, 2, 1 FROM calendar_tag_palettes p WHERE p.slug = 'leaf'
ON DUPLICATE KEY UPDATE name = VALUES(name), palette_id = VALUES(palette_id), color_hex = VALUES(color_hex), sort_order = VALUES(sort_order), is_system = 1, deleted_at = NULL;

INSERT INTO `calendar_tags` (`user_id`, `palette_id`, `slug`, `name`, `color_hex`, `sort_order`, `is_system`)
SELECT NULL, p.id, 'work', '업무', p.color_hex, 3, 1 FROM calendar_tag_palettes p WHERE p.slug = 'sunset'
ON DUPLICATE KEY UPDATE name = VALUES(name), palette_id = VALUES(palette_id), color_hex = VALUES(color_hex), sort_order = VALUES(sort_order), is_system = 1, deleted_at = NULL;

INSERT INTO `calendar_tags` (`user_id`, `palette_id`, `slug`, `name`, `color_hex`, `sort_order`, `is_system`)
SELECT NULL, p.id, 'rest', '휴식', p.color_hex, 4, 1 FROM calendar_tag_palettes p WHERE p.slug = 'linen'
ON DUPLICATE KEY UPDATE name = VALUES(name), palette_id = VALUES(palette_id), color_hex = VALUES(color_hex), sort_order = VALUES(sort_order), is_system = 1, deleted_at = NULL;
