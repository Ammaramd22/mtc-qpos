-- QPOS centralized Color Management (same pattern as Sizes)
-- Run once in phpMyAdmin against the existing QPOS database.
-- Safe to run again.
-- Does not delete products, sales, stock, payments, sizes, or existing colour text.
--
-- Prerequisite: sizes / product_size / product_colors from cpanel_size_color_management.sql
-- should already exist on live. This script adds the colours catalog only.
--
-- Migrated colour rows stay is_catalog = 0 (not offered on new products until
-- added again from Color Management, which keeps the same row and sets is_catalog = 1).

SET @db := DATABASE();

-- 1. Colour catalog. One row per business + colour name.
CREATE TABLE IF NOT EXISTS `colors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `is_catalog` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `colors_business_name_unique` (`business_id`, `name`),
    CONSTRAINT `colors_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. A product can have many colours. A colour can be used by many products.
CREATE TABLE IF NOT EXISTS `product_color` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `color_id` INT UNSIGNED NOT NULL,
    `position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_color_unique` (`product_id`, `color_id`),
    KEY `product_color_color_id_index` (`color_id`),
    CONSTRAINT `product_color_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_color_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Copy distinct colour tags from product_colors into the catalog (is_catalog = 0).
INSERT INTO `colors` (`business_id`, `name`, `is_catalog`, `created_by`, `created_at`, `updated_at`)
SELECT t.`business_id`, t.`name`, 0, NULL, NOW(), NOW()
FROM (
    SELECT `business_id`,
           SUBSTRING(TRIM(`name`), 1, 150) AS `name`,
           LOWER(TRIM(`name`)) AS `name_key`
    FROM `product_colors`
    WHERE TRIM(`name`) <> ''
) t
WHERE NOT EXISTS (
    SELECT 1 FROM `colors` c
    WHERE c.`business_id` = t.`business_id`
      AND LOWER(c.`name`) = t.`name_key`
)
GROUP BY t.`business_id`, t.`name_key`, t.`name`;

-- 4. Link products to catalog colours from product_colors (skip if already linked).
INSERT INTO `product_color` (`product_id`, `color_id`, `position`, `created_at`, `updated_at`)
SELECT pc.`product_id`,
       c.`id`,
       COALESCE((
           SELECT COUNT(*) FROM `product_color` x WHERE x.`product_id` = pc.`product_id`
       ), 0) + COALESCE((
           SELECT COUNT(*) FROM `product_colors` earlier
           WHERE earlier.`product_id` = pc.`product_id` AND earlier.`id` < pc.`id`
       ), 0),
       NOW(),
       NOW()
FROM `product_colors` pc
INNER JOIN `colors` c
    ON c.`business_id` = pc.`business_id`
   AND LOWER(c.`name`) = LOWER(TRIM(pc.`name`))
WHERE TRIM(pc.`name`) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM `product_color` link
      WHERE link.`product_id` = pc.`product_id`
        AND link.`color_id` = c.`id`
  );

-- 5. For products that only have products.color text (no product_color links yet),
--    split up to 12 comma-separated values into the catalog.
DROP TEMPORARY TABLE IF EXISTS `qpos_color_split`;
CREATE TEMPORARY TABLE `qpos_color_split` (
    `product_id` INT UNSIGNED NOT NULL,
    `business_id` INT UNSIGNED NOT NULL,
    `created_by` INT UNSIGNED NULL,
    `name` VARCHAR(150) NOT NULL,
    `position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY (`product_id`),
    KEY (`business_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `qpos_color_split` (`product_id`, `business_id`, `created_by`, `name`, `position`)
SELECT p.`id`,
       p.`business_id`,
       p.`created_by`,
       SUBSTRING(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.`color`, ',', numbers.n), ',', -1)), 1, 150),
       numbers.n - 1
FROM `products` p
INNER JOIN (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
    UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
) numbers
  ON CHAR_LENGTH(p.`color`) - CHAR_LENGTH(REPLACE(p.`color`, ',', '')) >= numbers.n - 1
WHERE p.`color` IS NOT NULL
  AND TRIM(p.`color`) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM `product_color` link WHERE link.`product_id` = p.`id`
  )
  AND TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.`color`, ',', numbers.n), ',', -1)) <> '';

INSERT INTO `colors` (`business_id`, `name`, `is_catalog`, `created_by`, `created_at`, `updated_at`)
SELECT s.`business_id`, s.`name`, 0, MAX(s.`created_by`), NOW(), NOW()
FROM `qpos_color_split` s
WHERE NOT EXISTS (
    SELECT 1 FROM `colors` c
    WHERE c.`business_id` = s.`business_id`
      AND LOWER(c.`name`) = LOWER(s.`name`)
)
GROUP BY s.`business_id`, LOWER(s.`name`), s.`name`;

INSERT INTO `product_color` (`product_id`, `color_id`, `position`, `created_at`, `updated_at`)
SELECT s.`product_id`,
       c.`id`,
       MIN(s.`position`),
       NOW(),
       NOW()
FROM `qpos_color_split` s
INNER JOIN `colors` c
    ON c.`business_id` = s.`business_id`
   AND LOWER(c.`name`) = LOWER(s.`name`)
WHERE NOT EXISTS (
    SELECT 1 FROM `product_color` link
    WHERE link.`product_id` = s.`product_id`
      AND link.`color_id` = c.`id`
)
GROUP BY s.`product_id`, c.`id`;

DROP TEMPORARY TABLE IF EXISTS `qpos_color_split`;
