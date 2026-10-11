SET @execution_energy_notice_check_exists = (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'execution_energy_notices'
    AND constraint_name = 'chk_execution_energy_notices_level'
    AND constraint_type = 'CHECK'
);

SET @execution_energy_notice_drop_sql = IF(
  @execution_energy_notice_check_exists = 0,
  'SELECT 1',
  IF(
    VERSION() LIKE '%MariaDB%',
    'ALTER TABLE `execution_energy_notices` DROP CONSTRAINT `chk_execution_energy_notices_level`',
    'ALTER TABLE `execution_energy_notices` DROP CHECK `chk_execution_energy_notices_level`'
  )
);

PREPARE execution_energy_notice_drop FROM @execution_energy_notice_drop_sql;
EXECUTE execution_energy_notice_drop;
DEALLOCATE PREPARE execution_energy_notice_drop;

ALTER TABLE `execution_energy_notices`
  ADD CONSTRAINT `chk_execution_energy_notices_level`
  CHECK (`notice_level` IN (0, 25, 50));
