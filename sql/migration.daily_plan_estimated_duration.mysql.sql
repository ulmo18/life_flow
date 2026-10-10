ALTER TABLE `daily_plan_items`
  ADD COLUMN `estimated_duration_minutes` SMALLINT UNSIGNED NULL AFTER `end_index`;
