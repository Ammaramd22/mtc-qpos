-- QPOS Size Management and multiple colours
-- Run once in phpMyAdmin against the existing QPOS database.
-- Safe to run again: existing tables are left in place, and products that
-- already have size or colour links are not inserted a second time.
-- Does not delete products, sales, stock, or payments.

-- 1. Keep a text copy of size and colour on products (list, POS, labels).
SET @db := DATABASE();

SET @sql := (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'size') = 0,
        'ALTER TABLE `products` ADD COLUMN `size` TEXT NULL',
        'ALTER TABLE `products` MODIFY `size` TEXT NULL'
    )
);
PREPARE qpos_size_stmt FROM @sql;
EXECUTE qpos_size_stmt;
DEALLOCATE PREPARE qpos_size_stmt;

SET @sql := (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'color') = 0,
        'ALTER TABLE `products` ADD COLUMN `color` TEXT NULL',
        'ALTER TABLE `products` MODIFY `color` TEXT NULL'
    )
);
PREPARE qpos_color_stmt FROM @sql;
EXECUTE qpos_color_stmt;
DEALLOCATE PREPARE qpos_color_stmt;

-- 2. Size catalog. One row per business + size name.
CREATE TABLE IF NOT EXISTS `sizes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `is_catalog` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `sizes_business_name_unique` (`business_id`, `name`),
    CONSTRAINT `sizes_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. A product can have many sizes. A size can be used by many products.
CREATE TABLE IF NOT EXISTS `product_size` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `size_id` INT UNSIGNED NOT NULL,
    `position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_size_unique` (`product_id`, `size_id`),
    KEY `product_size_size_id_index` (`size_id`),
    CONSTRAINT `product_size_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_size_size_id_foreign` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Colours typed on each product. Duplicate colours on the same product are blocked.
CREATE TABLE IF NOT EXISTS `product_colors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `name_key` VARCHAR(150) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_colors_unique` (`product_id`, `name_key`),
    KEY `product_colors_business_name_index` (`business_id`, `name_key`),
    CONSTRAINT `product_colors_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_colors_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Optional cashier choice on a sale line. Does not affect quantity, price, or stock.
SET @sql := (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'transaction_sell_lines' AND COLUMN_NAME = 'line_size') = 0,
        'ALTER TABLE `transaction_sell_lines` ADD COLUMN `line_size` VARCHAR(191) NULL',
        'SELECT 1'
    )
);
PREPARE qpos_line_size_stmt FROM @sql;
EXECUTE qpos_line_size_stmt;
DEALLOCATE PREPARE qpos_line_size_stmt;

SET @sql := (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'transaction_sell_lines' AND COLUMN_NAME = 'line_color') = 0,
        'ALTER TABLE `transaction_sell_lines` ADD COLUMN `line_color` VARCHAR(191) NULL',
        'SELECT 1'
    )
);
PREPARE qpos_line_color_stmt FROM @sql;
EXECUTE qpos_line_color_stmt;
DEALLOCATE PREPARE qpos_line_color_stmt;

-- 6. Copy existing product size text into the catalog.
--    "S, M, L" becomes three sizes. A blank size is skipped.
--    Products that already have links are skipped.
INSERT IGNORE INTO `sizes` (`business_id`, `name`, `created_by`, `created_at`, `updated_at`)
SELECT DISTINCT
    p.business_id,
    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.size, ',', n.n), ',', -1)) AS size_name,
    NULLIF(p.created_by, 0),
    NOW(),
    NOW()
FROM `products` p
JOIN (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
    UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
    UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
) n
    ON p.size IS NOT NULL
   AND TRIM(p.size) <> ''
   AND n.n <= 1 + (LENGTH(p.size) - LENGTH(REPLACE(p.size, ',', '')))
WHERE TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.size, ',', n.n), ',', -1)) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM `sizes` s
      WHERE s.business_id = p.business_id
        AND LOWER(s.name) = LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.size, ',', n.n), ',', -1)))
  );

INSERT IGNORE INTO `product_size` (`product_id`, `size_id`, `position`, `created_at`, `updated_at`)
SELECT
    p.id,
    s.id,
    n.n - 1,
    NOW(),
    NOW()
FROM `products` p
JOIN (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
    UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
    UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
) n
    ON p.size IS NOT NULL
   AND TRIM(p.size) <> ''
   AND n.n <= 1 + (LENGTH(p.size) - LENGTH(REPLACE(p.size, ',', '')))
JOIN `sizes` s
    ON s.business_id = p.business_id
   AND LOWER(s.name) = LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.size, ',', n.n), ',', -1)))
WHERE NOT EXISTS (
    SELECT 1 FROM `product_size` ps WHERE ps.product_id = p.id
);

-- 7. Copy existing colour text into product_colors.
INSERT IGNORE INTO `product_colors` (`business_id`, `product_id`, `name`, `name_key`, `created_at`, `updated_at`)
SELECT
    p.business_id,
    p.id,
    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.color, ',', n.n), ',', -1)),
    LOWER(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.color, ',', n.n), ',', -1))),
    NOW(),
    NOW()
FROM `products` p
JOIN (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
    UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
    UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
) n
    ON p.color IS NOT NULL
   AND TRIM(p.color) <> ''
   AND n.n <= 1 + (LENGTH(p.color) - LENGTH(REPLACE(p.color, ',', '')))
WHERE TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.color, ',', n.n), ',', -1)) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM `product_colors` pc WHERE pc.product_id = p.id
  );

-- 8. Refresh the text columns from the linked rows so list, POS and labels stay in sync.
SET SESSION group_concat_max_len = 4096;

UPDATE `products` p
SET p.size = (
    SELECT GROUP_CONCAT(s.name ORDER BY ps.position SEPARATOR ', ')
    FROM `product_size` ps
    JOIN `sizes` s ON s.id = ps.size_id
    WHERE ps.product_id = p.id
)
WHERE EXISTS (SELECT 1 FROM `product_size` ps WHERE ps.product_id = p.id);

UPDATE `products` p
SET p.color = (
    SELECT GROUP_CONCAT(pc.name ORDER BY pc.id SEPARATOR ', ')
    FROM `product_colors` pc
    WHERE pc.product_id = p.id
)
WHERE EXISTS (SELECT 1 FROM `product_colors` pc WHERE pc.product_id = p.id);

-- 8. Size dropdown flag. Existing rows stay at 0 so old product size text
-- is not offered as a new choice. Safe to run again.
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
