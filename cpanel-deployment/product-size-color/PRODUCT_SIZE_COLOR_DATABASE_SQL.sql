-- QPOS optional product Size and Color
-- For cPanel phpMyAdmin. Select the QPOS database first (mtcqpos_db).
-- This script does not read information_schema. cPanel denies that access.
-- It only adds two nullable columns. It does not change existing product rows.
--
-- Run these two statements. If you see "Duplicate column name", that column
-- is already present. Skip that statement and run the other one.

ALTER TABLE `products` ADD COLUMN `size` VARCHAR(191) NULL DEFAULT NULL AFTER `name`;
ALTER TABLE `products` ADD COLUMN `color` VARCHAR(191) NULL DEFAULT NULL AFTER `size`;

-- Confirm. Expect one row for size and one row for color, Null = YES.
SHOW COLUMNS FROM `products` LIKE 'size';
SHOW COLUMNS FROM `products` LIKE 'color';

-- Optional. Records the Laravel migration so a later artisan migrate will not add the columns again.
-- Skip this if it errors. The two columns above are what the application needs.
-- INSERT INTO `migrations` (`migration`, `batch`)
-- SELECT '2026_10_30_120000_add_size_and_color_to_products_table', COALESCE(MAX(`batch`), 0)
-- FROM `migrations`
-- WHERE NOT EXISTS (
--     SELECT 1 FROM `migrations` AS existing
--     WHERE existing.`migration` = '2026_10_30_120000_add_size_and_color_to_products_table'
-- );
