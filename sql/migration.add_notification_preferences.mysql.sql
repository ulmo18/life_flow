ALTER TABLE `user_preferences`
  ADD COLUMN `notification_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `retrospect_morning_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `retrospect_morning_time` TIME NOT NULL DEFAULT '07:00:00',
  ADD COLUMN `retrospect_evening_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `retrospect_evening_time` TIME NOT NULL DEFAULT '20:00:00',
  ADD COLUMN `routine_reminder_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `routine_reminder_time` TIME NOT NULL DEFAULT '14:00:00',
  ADD COLUMN `calendar_plan_reminder_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `goal_deadline_reminder_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `goal_deadline_time` TIME NOT NULL DEFAULT '12:00:00',
  ADD COLUMN `goal_deadline_day_before_enabled` TINYINT(1) NOT NULL DEFAULT 1;
