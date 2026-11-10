ALTER TABLE daily_plan_items
  ADD COLUMN source_carryover_item_id BIGINT UNSIGNED NULL,
  ADD UNIQUE KEY uq_daily_plan_items_carryover (daily_plan_id, source_carryover_item_id),
  ADD CONSTRAINT fk_daily_plan_items_carryover FOREIGN KEY (source_carryover_item_id) REFERENCES daily_plan_items(id) ON DELETE SET NULL;
