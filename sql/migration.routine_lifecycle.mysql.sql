ALTER TABLE `routines`
  ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `duration_days`,
  ADD COLUMN `ended_at` DATE NULL AFTER `status`,
  ADD KEY `idx_routines_user_status` (`user_id`, `status`);

-- CHECK support and DROP CHECK syntax vary across hosted MySQL/MariaDB versions.
-- Runtime validation remains in RoutineService. Apply the companion trigger
-- migration when the production database cannot enforce CHECK constraints.
