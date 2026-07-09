CREATE TABLE IF NOT EXISTS calendar_tag_palettes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  color_hex TEXT NOT NULL UNIQUE,
  sort_order INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_calendar_tag_palettes_sort ON calendar_tag_palettes(sort_order);

INSERT OR IGNORE INTO calendar_tag_palettes (slug, color_hex, sort_order) VALUES
  ('moss', '#8EA4A2', 1),
  ('grain', '#D6A04F', 2),
  ('clay', '#B98C6B', 3),
  ('leaf', '#7E9D72', 4),
  ('sage', '#6F9A8D', 5),
  ('rosewood', '#C77F7A', 6),
  ('sunset', '#C85F5A', 7),
  ('denim', '#6F83B7', 8),
  ('olive', '#9B8A67', 9),
  ('berry', '#B77490', 10),
  ('lichen', '#8D9B8F', 11),
  ('linen', '#A99A86', 12),
  ('bluegray', '#7896A6', 13),
  ('terra', '#D07C5D', 14),
  ('soil', '#7D7466', 15);

ALTER TABLE calendar_tags ADD COLUMN user_id INTEGER NULL REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE calendar_tags ADD COLUMN palette_id INTEGER NULL REFERENCES calendar_tag_palettes(id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE calendar_tags ADD COLUMN is_system INTEGER NOT NULL DEFAULT 0;
ALTER TABLE calendar_events ADD COLUMN memo TEXT NULL;

CREATE INDEX IF NOT EXISTS idx_calendar_tags_user ON calendar_tags(user_id, deleted_at);
CREATE INDEX IF NOT EXISTS idx_calendar_tags_palette_id ON calendar_tags(palette_id);

UPDATE calendar_tags
SET deleted_at = COALESCE(deleted_at, CURRENT_TIMESTAMP),
    is_system = 0
WHERE slug NOT IN ('fixed', 'health', 'work', 'rest');

INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'fixed', '고정', color_hex, 1, 1 FROM calendar_tag_palettes WHERE slug = 'moss';
INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'health', '건강', color_hex, 2, 1 FROM calendar_tag_palettes WHERE slug = 'leaf';
INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'work', '업무', color_hex, 3, 1 FROM calendar_tag_palettes WHERE slug = 'sunset';
INSERT OR IGNORE INTO calendar_tags (user_id, palette_id, slug, name, color_hex, sort_order, is_system)
SELECT NULL, id, 'rest', '휴식', color_hex, 4, 1 FROM calendar_tag_palettes WHERE slug = 'linen';
