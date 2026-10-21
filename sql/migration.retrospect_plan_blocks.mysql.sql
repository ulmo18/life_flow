ALTER TABLE `retrospect_report_actual_items`
  ADD COLUMN `daily_plan_block_id_snapshot` BIGINT UNSIGNED NULL AFTER `plan_template_id_snapshot`;
