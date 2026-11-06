# Database update guide — Color catalog

Use phpMyAdmin. Do not use Terminal or SSH. Take a database backup before you start.

This update adds tables and migrates existing colour names. It does not delete products, sales, stock, or payments.

## Before you start

1. In cPanel, open **phpMyAdmin**.
2. Select the QPOS database (the same database named in the live `.env` file).
3. Open the **Export** tab and download a backup.
4. Confirm earlier Size/Color tables exist:

```sql
SHOW TABLES LIKE 'sizes';
SHOW TABLES LIKE 'product_size';
SHOW TABLES LIKE 'product_colors';
SHOW COLUMNS FROM products LIKE 'color';
```

If `sizes` / `product_size` / `product_colors` are missing, run `database/cpanel_size_color_management.sql` from the earlier Size Management package first, then continue here.

## Apply the update

1. Open the **SQL** tab.
2. Open `database/cpanel_color_catalog.sql` from this package in a text editor.
3. Paste the full script into the SQL box.
4. Click **Go**.

The script is safe to run a second time. Existing catalog links are not duplicated.

## What the script creates

| Object | Purpose |
| --- | --- |
| `colors` | Colour names for one business (Red, White, Black, …) with `is_catalog` |
| `product_color` | Links a product to one or more catalogue colours |

`products.color` stays in place as a comma-separated display copy for list, POS, labels, import, and export.

`product_colors` (older per-product tags) is left in place. Existing tag names are copied into `colors` / `product_color`. The application keeps writing both after this update.

Migrated rows use `is_catalog = 0`. They remain on existing products but are not offered as new choices until added again from Color Management (which sets `is_catalog = 1` on the same row).

## Check the result

```sql
SHOW TABLES LIKE 'colors';
SHOW TABLES LIKE 'product_color';
SELECT COUNT(*) AS color_rows FROM colors;
SELECT COUNT(*) AS product_color_links FROM product_color;
```

Each `SHOW TABLES` query should return a row. Counts may be zero if no products had colours yet.

## If phpMyAdmin reports an error

- **Table already exists**: the script uses `CREATE TABLE IF NOT EXISTS`. Paste the whole file again only if a later statement failed.
- **Foreign key error**: confirm you selected the QPOS database, and that `business` and `products` exist.
- **Unknown table product_colors**: run the earlier Size/Color management SQL first, then re-run this script.

Do not drop `products`, `product_colors`, `sizes`, `product_size`, or any sales table.

## Laravel migration (optional)

The same change is in:

`database/migrations/2026_11_05_120000_create_colors_catalog.php`

On cPanel without Terminal, prefer the SQL script above. Do not run `php artisan migrate` unless you already have a supported CLI workflow.
