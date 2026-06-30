ALTER TABLE `user`
  ADD COLUMN `terms_agreed_at` DATETIME NULL AFTER `last_login_at`,
  ADD COLUMN `privacy_agreed_at` DATETIME NULL AFTER `terms_agreed_at`;
