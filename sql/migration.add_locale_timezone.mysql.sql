ALTER TABLE `user_preferences`
  ADD COLUMN `locale` VARCHAR(10) NOT NULL DEFAULT 'ko' AFTER `theme`,
  ADD COLUMN `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Seoul' AFTER `locale`;
