# Database update guide

Use phpMyAdmin. Do not use Terminal or SSH. Take a database backup before you start.

This update adds tables and columns. It does not delete products, sales, stock, or payments.

## Before you start

1. In cPanel, open **phpMyAdmin**.
2. Select the QPOS database (the same database named in the live `.env` file).
3. Open the **Export** tab and download a backup.

## Apply the update

1. Open the **SQL** tab.
2. Open `database/cpanel_size_color_management.sql` from this package in a text editor.
3. Paste the full script into the SQL box.
4. Click **Go**.

The script is safe to run a second time. Tables that already exist are skipped. Products that already have size or colour links are not inserted again.

## What the script creates

| Object | Purpose |
| --- | --- |
| `sizes` | Size names for one business, such as S, M, L, XL, 32, Free Size |
| `product_size` | Links a product to one or more sizes |
| `product_colors` | Colours typed on a product. The same colour cannot be stored twice on one product |
| `transaction_sell_lines.line_size` | Size chosen on a sale line, if the cashier picks one |
| `transaction_sell_lines.line_color` | Colour chosen on a sale line, if the cashier picks one |

`products.size` and `products.color` stay in place. They hold a comma-separated copy used by the product list, POS, and labels. Existing text such as `M` or `Black` is copied into the new tables. Products with a blank size or colour are left blank.

## Check the result

Run this in the SQL tab:

```sql
SHOW TABLES LIKE 'sizes';
SHOW TABLES LIKE 'product_size';
SHOW TABLES LIKE 'product_colors';
SHOW COLUMNS FROM transaction_sell_lines LIKE 'line_size';
SHOW COLUMNS FROM transaction_sell_lines LIKE 'line_color';
```

Each query should return a row.

## If phpMyAdmin reports an error

- **Table already exists**: the script uses `CREATE TABLE IF NOT EXISTS`. If you still see this, you pasted only part of the file. Paste the whole file again.
- **Duplicate column**: the script checks `line_size` and `line_color` before adding them. Run the remaining statements after the one that failed.
- **Foreign key error**: confirm you selected the QPOS database, and that the `business` and `products` tables are present.

Do not drop `products`, `transaction_sell_lines`, or any sales table.

## Laravel migration

The same change is in:

`database/migrations/2026_10_31_180000_create_sizes_and_product_colors.php`

On this server there is no Terminal, so use the SQL file above. Do not run both a partial SQL script and a manual table create. The SQL file is the install path for cPanel.
