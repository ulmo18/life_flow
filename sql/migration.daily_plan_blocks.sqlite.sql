-- SQLite compatibility migration. Application bootstrap runs this only when
-- daily_plan_blocks is missing; the backfill statements are safe to rerun.

CREATE TABLE IF NOT EXISTS daily_plan_blocks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  daily_plan_item_id INTEGER NOT NULL,
  start_index INTEGER NOT NULL,
  end_index INTEGER NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (daily_plan_item_id) REFERENCES daily_plan_items(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (start_index >= 0 AND end_index <= 144 AND start_index < end_index)
);

ALTER TABLE calendar_events ADD COLUMN daily_plan_block_id INTEGER NULL;
CREATE INDEX IF NOT EXISTS idx_daily_plan_blocks_item_order ON daily_plan_blocks(daily_plan_item_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_calendar_events_daily_plan_block_id ON calendar_events(daily_plan_block_id);

INSERT INTO daily_plan_blocks (daily_plan_item_id, start_index, end_index, sort_order, created_at, updated_at)
SELECT dpi.id, dpi.start_index, dpi.end_index, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM daily_plan_items dpi
WHERE dpi.deleted_at IS NULL
  AND dpi.start_index IS NOT NULL
  AND dpi.end_index IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM daily_plan_blocks dpb WHERE dpb.daily_plan_item_id = dpi.id
  );

UPDATE calendar_events
SET daily_plan_block_id = (
  SELECT dpb.id
  FROM daily_plan_blocks dpb
  WHERE dpb.daily_plan_item_id = calendar_events.daily_plan_item_id
  ORDER BY dpb.sort_order ASC, dpb.id ASC
  LIMIT 1
)
WHERE daily_plan_item_id IS NOT NULL
  AND daily_plan_block_id IS NULL;
