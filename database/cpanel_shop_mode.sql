-- QPOS — Shop Mode / industry columns + workshop tables (NO TERMINAL)
-- cPanel → phpMyAdmin → select your QPOS database → SQL → paste this whole file → Go
--
-- Safe to run more than once. Does NOT delete products, sales, stock, or customers.

-- business.shop_mode
SET @db = DATABASE();
SET @exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'business' AND COLUMN_NAME = 'shop_mode'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `business` ADD COLUMN `shop_mode` VARCHAR(50) NULL DEFAULT NULL AFTER `enabled_modules`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- products industry columns
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='part_number');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `part_number` VARCHAR(80) NULL DEFAULT NULL, ADD INDEX `products_part_number_index` (`part_number`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='oem_number');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `oem_number` VARCHAR(80) NULL DEFAULT NULL, ADD INDEX `products_oem_number_index` (`oem_number`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='compatible_make');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `compatible_make` VARCHAR(80) NULL DEFAULT NULL, ADD INDEX `products_compatible_make_index` (`compatible_make`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='compatible_model');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `compatible_model` VARCHAR(80) NULL DEFAULT NULL, ADD INDEX `products_compatible_model_index` (`compatible_model`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='year_from');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `year_from` SMALLINT UNSIGNED NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='year_to');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `year_to` SMALLINT UNSIGNED NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='engine_type');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `engine_type` VARCHAR(80) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='hw_size');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `hw_size` VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='hw_length');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `hw_length` VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='hw_width');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `hw_width` VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='hw_thickness');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `hw_thickness` VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='hw_material');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `hw_material` VARCHAR(80) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='hw_grade');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `hw_grade` VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='hw_colour');
SET @sql = IF(@exists=0, 'ALTER TABLE `products` ADD COLUMN `hw_colour` VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `customer_vehicles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `registration_number` VARCHAR(40) NULL DEFAULT NULL,
  `make` VARCHAR(80) NULL DEFAULT NULL,
  `model` VARCHAR(80) NULL DEFAULT NULL,
  `year` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `vehicle_type` VARCHAR(40) NULL DEFAULT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_vehicles_registration_number_index` (`registration_number`),
  KEY `customer_vehicles_business_id_contact_id_index` (`business_id`, `contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `workshop_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NULL DEFAULT NULL,
  `vehicle_id` INT UNSIGNED NULL DEFAULT NULL,
  `location_id` INT UNSIGNED NULL DEFAULT NULL,
  `reference_no` VARCHAR(30) NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `description` TEXT NULL,
  `notes` TEXT NULL,
  `estimated_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  `sell_transaction_id` INT UNSIGNED NULL DEFAULT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `delivered_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workshop_jobs_reference_no_index` (`reference_no`),
  KEY `workshop_jobs_status_index` (`status`),
  KEY `workshop_jobs_payment_status_index` (`payment_status`),
  KEY `workshop_jobs_business_id_status_index` (`business_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `workshop_job_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workshop_job_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL DEFAULT NULL,
  `variation_id` INT UNSIGNED NULL DEFAULT NULL,
  `item_type` VARCHAR(20) NOT NULL DEFAULT 'part',
  `name` VARCHAR(191) NULL DEFAULT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workshop_job_items_workshop_job_id_index` (`workshop_job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT
  '2026_09_03_140000_add_shop_mode_and_industry_fields',
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` AS m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1
  FROM `migrations` AS m2
  WHERE m2.`migration` = '2026_09_03_140000_add_shop_mode_and_industry_fields'
);
