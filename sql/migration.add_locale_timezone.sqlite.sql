ALTER TABLE user_preferences ADD COLUMN locale TEXT NOT NULL DEFAULT 'ko';
ALTER TABLE user_preferences ADD COLUMN timezone TEXT NOT NULL DEFAULT 'Asia/Seoul';
