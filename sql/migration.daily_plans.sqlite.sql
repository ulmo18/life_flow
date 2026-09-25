CREATE TABLE IF NOT EXISTS daily_plans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  calendar_day_id INTEGER NOT NULL UNIQUE,
  source_plan_group_id INTEGER NULL,
  name TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (calendar_day_id) REFERENCES calendar_days(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (source_plan_group_id) REFERENCES plan_groups(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS daily_plan_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  daily_plan_id INTEGER NOT NULL,
  source_plan_block_id INTEGER NULL,
  source_plan_template_id INTEGER NULL,
  goal_id INTEGER NULL,
  title TEXT NOT NULL,
  importance TEXT NOT NULL DEFAULT 'D',
  start_index INTEGER NULL,
  end_index INTEGER NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (daily_plan_id) REFERENCES daily_plans(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (source_plan_block_id) REFERENCES plan_blocks(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (source_plan_template_id) REFERENCES plan_templates(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (
    (start_index IS NULL AND end_index IS NULL)
    OR (start_index IS NOT NULL AND end_index IS NOT NULL
        AND start_index >= 0 AND end_index <= 144 AND start_index < end_index)
  )
);

CREATE INDEX IF NOT EXISTS idx_daily_plans_user ON daily_plans(user_id);
CREATE INDEX IF NOT EXISTS idx_daily_plans_source_group ON daily_plans(source_plan_group_id);
CREATE INDEX IF NOT EXISTS idx_daily_plan_items_plan_order ON daily_plan_items(daily_plan_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_daily_plan_items_goal ON daily_plan_items(goal_id);
CREATE INDEX IF NOT EXISTS idx_daily_plan_items_source_template ON daily_plan_items(source_plan_template_id);
CREATE INDEX IF NOT EXISTS idx_calendar_events_daily_plan_item_id ON calendar_events(daily_plan_item_id);

INSERT OR IGNORE INTO daily_plans (user_id, calendar_day_id, source_plan_group_id, name, created_at, updated_at)
SELECT cd.user_id, cd.id, cd.plan_group_id, COALESCE(pg.name, '[' || cd.calendar_date || ']의 계획'), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM calendar_days cd
LEFT JOIN plan_groups pg ON pg.id = cd.plan_group_id
WHERE cd.plan_group_id IS NOT NULL;

INSERT INTO daily_plan_items (
  daily_plan_id, source_plan_block_id, source_plan_template_id, goal_id,
  title, importance, start_index, end_index, sort_order, created_at, updated_at
)
SELECT dp.id, pb.id, pt.id, pt.goal_id, pt.title, pt.importance,
       pb.start_index, pb.end_index, pb.sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM daily_plans dp
JOIN plan_blocks pb ON pb.plan_group_id = dp.source_plan_group_id
JOIN plan_templates pt ON pt.id = pb.plan_template_id
WHERE NOT EXISTS (SELECT 1 FROM daily_plan_items dpi WHERE dpi.daily_plan_id = dp.id);

UPDATE calendar_events
SET daily_plan_item_id = (
  SELECT dpi.id
  FROM daily_plan_items dpi
  JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
  WHERE dp.calendar_day_id = calendar_events.calendar_day_id
    AND dpi.source_plan_template_id = calendar_events.plan_template_id
  LIMIT 1
)
WHERE plan_template_id IS NOT NULL AND daily_plan_item_id IS NULL;
