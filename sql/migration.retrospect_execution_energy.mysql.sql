ALTER TABLE `retrospect_report_actual_items`
  ADD COLUMN `energy_group_name_snapshot` VARCHAR(60) NULL AFTER `plan_importance_snapshot`,
  ADD COLUMN `energy_effect_type_snapshot` VARCHAR(20) NULL AFTER `energy_group_name_snapshot`,
  ADD COLUMN `energy_value_snapshot` SMALLINT UNSIGNED NULL AFTER `energy_effect_type_snapshot`;
