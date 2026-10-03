-- Apply only when the base Execution Energy migration was deployed before burden_feedback was added.
ALTER TABLE `execution_energy_days`
  ADD COLUMN `burden_feedback` VARCHAR(12) NULL AFTER `stress_state`;

ALTER TABLE `execution_energy_days`
  ADD CONSTRAINT `chk_execution_energy_days_feedback`
  CHECK (`burden_feedback` IS NULL OR `burden_feedback` IN ('lower', 'similar', 'higher'));
