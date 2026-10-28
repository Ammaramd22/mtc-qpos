-- QPOS — Tyre Size column (NO TERMINAL)
-- cPanel → phpMyAdmin → select your database → SQL → paste → Go
--
-- If you get "Duplicate column name 'tyre_size'", the column is already there — skip to step 2.

-- STEP 1: add column + index
ALTER TABLE `products`
  ADD COLUMN `tyre_size` VARCHAR(50) NULL DEFAULT NULL AFTER `name`;

ALTER TABLE `products`
  ADD INDEX `products_tyre_size_index` (`tyre_size`);

-- STEP 2: mark Laravel migration as done (skip if this row already exists)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT
  '2026_08_21_120000_add_tyre_size_to_products_table',
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` AS m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1
  FROM `migrations` AS m2
  WHERE m2.`migration` = '2026_08_21_120000_add_tyre_size_to_products_table'
);
