ALTER TABLE `goals`
  ADD COLUMN `is_focused` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN `focused_at` DATETIME NULL AFTER `is_focused`,
  ADD KEY `idx_goals_user_focus` (`user_id`, `is_focused`, `focused_at`);
