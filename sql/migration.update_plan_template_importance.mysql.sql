-- Add importance metadata for plan templates.

ALTER TABLE `plan_templates`
  ADD COLUMN `importance` CHAR(1) NOT NULL DEFAULT 'D' AFTER `title`;

UPDATE `plan_templates`
SET `importance` = 'D'
WHERE `importance` IS NULL OR `importance` = '';
