-- Add version metadata for versioned Plan edits.

ALTER TABLE `plan_groups`
  ADD COLUMN IF NOT EXISTS `source_plan_group_id` BIGINT UNSIGNED NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `version_no` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `source_plan_group_id`;

CREATE INDEX IF NOT EXISTS `idx_plan_groups_source_id` ON `plan_groups` (`source_plan_group_id`);
