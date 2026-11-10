ALTER TABLE daily_plan_items ADD COLUMN source_carryover_item_id INTEGER NULL REFERENCES daily_plan_items(id) ON DELETE SET NULL;
CREATE UNIQUE INDEX uq_daily_plan_items_carryover ON daily_plan_items(daily_plan_id, source_carryover_item_id);
