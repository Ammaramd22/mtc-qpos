-- QPOS POS Pay fix — add columns that newer code expects (safe / idempotent)
-- Run in phpMyAdmin on the live DB.

SET @db := DATABASE();

-- transactions.offline_uuid (common cause of Pay failure after recent POS updates)
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `transactions` ADD COLUMN `offline_uuid` VARCHAR(80) NULL AFTER `invoice_token`, ADD UNIQUE KEY `transactions_business_offline_uuid_unique` (`business_id`, `offline_uuid`)',
    'SELECT ''offline_uuid already exists'' AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'offline_uuid'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- transaction_payments.credit_note_id
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `transaction_payments` ADD COLUMN `credit_note_id` INT UNSIGNED NULL AFTER `account_id`',
    'SELECT ''credit_note_id already exists'' AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'transaction_payments' AND COLUMN_NAME = 'credit_note_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- transactions.credit_note_status
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `transactions` ADD COLUMN `credit_note_status` ENUM(''not_used'',''partially_used'',''used'') NOT NULL DEFAULT ''not_used'' AFTER `payment_status`',
    'SELECT ''credit_note_status already exists'' AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'credit_note_status'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- products.tyre_size (if missing)
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `products` ADD COLUMN `tyre_size` VARCHAR(50) NULL AFTER `name`, ADD INDEX `products_tyre_size_index` (`tyre_size`)',
    'SELECT ''tyre_size already exists'' AS info'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'tyre_size'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'DONE — offline_uuid / credit_note columns check complete' AS status;
