SET @has_end_month = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cia_ferias_leave_period_history'
    AND COLUMN_NAME = 'leave_period_end_month'
);
SET @sql = IF(
  @has_end_month = 0,
  'ALTER TABLE cia_ferias_leave_period_history ADD COLUMN leave_period_end_month INT NULL AFTER leave_period_start_day',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_end_day = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cia_ferias_leave_period_history'
    AND COLUMN_NAME = 'leave_period_end_day'
);
SET @sql = IF(
  @has_end_day = 0,
  'ALTER TABLE cia_ferias_leave_period_history ADD COLUMN leave_period_end_day INT NULL AFTER leave_period_end_month',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE cia_ferias_leave_period_history
SET
  leave_period_end_month = MONTH(
    DATE_SUB(
      DATE_ADD(
        STR_TO_DATE(
          CONCAT(YEAR(created_at), '-', leave_period_start_month, '-', leave_period_start_day),
          '%Y-%c-%e'
        ),
        INTERVAL 1 YEAR
      ),
      INTERVAL 1 DAY
    )
  ),
  leave_period_end_day = DAY(
    DATE_SUB(
      DATE_ADD(
        STR_TO_DATE(
          CONCAT(YEAR(created_at), '-', leave_period_start_month, '-', leave_period_start_day),
          '%Y-%c-%e'
        ),
        INTERVAL 1 YEAR
      ),
      INTERVAL 1 DAY
    )
  )
WHERE leave_period_end_month IS NULL OR leave_period_end_day IS NULL;
