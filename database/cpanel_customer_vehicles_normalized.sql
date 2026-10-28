-- QPOS: customer vehicle registration normalization + soft deletes
-- Safe to re-run. Reuses existing customer_vehicles (Vehicle Modification + Tyre Shop).
-- Normalized values are backfilled by the Laravel migration or on next vehicle save.

SET @db := DATABASE();

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_vehicles' AND COLUMN_NAME = 'registration_normalized'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `customer_vehicles` ADD COLUMN `registration_normalized` VARCHAR(40) NULL AFTER `registration_number`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_vehicles' AND INDEX_NAME = 'cv_biz_reg_norm_idx'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE `customer_vehicles` ADD INDEX `cv_biz_reg_norm_idx` (`business_id`, `registration_normalized`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_vehicles' AND COLUMN_NAME = 'deleted_at'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `customer_vehicles` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
