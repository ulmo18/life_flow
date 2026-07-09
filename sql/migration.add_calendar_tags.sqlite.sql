CREATE TABLE IF NOT EXISTS calendar_tags (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  color_hex TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 1,
  deleted_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_calendar_tags_sort ON calendar_tags(sort_order);
CREATE INDEX IF NOT EXISTS idx_calendar_tags_deleted_at ON calendar_tags(deleted_at);

INSERT OR IGNORE INTO calendar_tags (slug, name, color_hex, sort_order) VALUES
  ('sleep', '수면', '#8EA4A2', 1),
  ('meal', '식사', '#D6A04F', 2),
  ('commute-prep', '출퇴근 준비', '#B98C6B', 3),
  ('health', '건강', '#7E9D72', 4),
  ('exercise', '운동', '#6F9A8D', 5),
  ('hospital', '병원', '#C77F7A', 6),
  ('work', '업무', '#C85F5A', 7),
  ('study', '공부', '#6F83B7', 8),
  ('chore', '집안일', '#9B8A67', 9),
  ('hobby', '취미', '#B77490', 10),
  ('meditation', '명상', '#8D9B8F', 11),
  ('rest', '휴식', '#A99A86', 12),
  ('movement', '이동', '#7896A6', 13),
  ('relationship', '관계', '#D07C5D', 14),
  ('etc', '기타', '#7D7466', 15);

ALTER TABLE calendar_events ADD COLUMN calendar_tag_id INTEGER NULL REFERENCES calendar_tags(id) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE INDEX IF NOT EXISTS idx_calendar_events_tag_id ON calendar_events(calendar_tag_id);
