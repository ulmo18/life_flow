CREATE TABLE IF NOT EXISTS saved_locations (
  user_id INTEGER NOT NULL,
  entity_type VARCHAR(16) NOT NULL,
  entity_id INTEGER NOT NULL,
  place_name VARCHAR(120) NOT NULL,
  address VARCHAR(300) NOT NULL,
  latitude DOUBLE NOT NULL,
  longitude DOUBLE NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, entity_type, entity_id)
);
