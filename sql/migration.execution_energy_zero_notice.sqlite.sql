PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;

CREATE TABLE execution_energy_notices_new (
  user_id INTEGER NOT NULL,
  energy_date TEXT NOT NULL,
  notice_level INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(user_id, energy_date, notice_level),
  FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CHECK (notice_level IN (0, 25, 50))
);

INSERT INTO execution_energy_notices_new (user_id, energy_date, notice_level, created_at)
SELECT user_id, energy_date, notice_level, created_at
FROM execution_energy_notices;

DROP TABLE execution_energy_notices;
ALTER TABLE execution_energy_notices_new RENAME TO execution_energy_notices;

COMMIT;
PRAGMA foreign_keys = ON;
