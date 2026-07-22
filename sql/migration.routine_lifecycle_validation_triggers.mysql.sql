DROP TRIGGER IF EXISTS `trg_routines_validate_insert`;
DROP TRIGGER IF EXISTS `trg_routines_validate_update`;

DELIMITER //

CREATE TRIGGER `trg_routines_validate_insert`
BEFORE INSERT ON `routines`
FOR EACH ROW
BEGIN
  IF NEW.`duration_days` < 1 OR NEW.`duration_days` > 365 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'duration_days must be between 1 and 365';
  END IF;

  IF NEW.`status` NOT IN ('active', 'completed', 'stopped') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'invalid routine status';
  END IF;
END//

CREATE TRIGGER `trg_routines_validate_update`
BEFORE UPDATE ON `routines`
FOR EACH ROW
BEGIN
  IF NEW.`duration_days` < 1 OR NEW.`duration_days` > 365 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'duration_days must be between 1 and 365';
  END IF;

  IF NEW.`status` NOT IN ('active', 'completed', 'stopped') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'invalid routine status';
  END IF;
END//

DELIMITER ;
