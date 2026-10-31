-- QPOS size dropdown fix
-- Run once in phpMyAdmin against the existing QPOS database.
-- Safe to run again.
-- Adds a flag so Add Product lists only sizes saved in Size Management.
-- Existing size rows, product links, stock, sales, and payments are not deleted.
-- Rows already in `sizes` (including old product size text such as 30 or 105)
-- stay in the table with is_catalog = 0. They are not offered on new products.
-- To show one of those names, add it again from Size Management. The same row is kept.

SET @db := DATABASE();

SET @sql := (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sizes' AND COLUMN_NAME = 'is_catalog') = 0,
        'ALTER TABLE `sizes` ADD COLUMN `is_catalog` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `name`',
        'SELECT 1'
    )
);
PREPARE qpos_size_catalog_stmt FROM @sql;
EXECUTE qpos_size_catalog_stmt;
DEALLOCATE PREPARE qpos_size_catalog_stmt;
