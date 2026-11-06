# Database update guide

Use phpMyAdmin. Do not use Terminal or SSH. Take a database backup before you start.

## Color Management catalog (current update)

1. Back up the database.
2. Confirm Size/Color base tables exist (`sizes`, `product_size`, `product_colors`). If not, run `database/cpanel_size_color_management.sql` first.
3. Open the **SQL** tab and run the full contents of `database/cpanel_color_catalog.sql`.
4. Verify:

```sql
SHOW TABLES LIKE 'colors';
SHOW TABLES LIKE 'product_color';
```

Full steps, migration notes, and troubleshooting: see `updates/QPOS-COLOR-MANAGEMENT-UPDATE-cPanel/DATABASE_UPDATE_GUIDE.md`.

The script is safe to run a second time. It does not delete products, sales, stock, or payments. Existing colour names are copied into the catalog with `is_catalog = 0` until promoted from Color Management.

## Earlier Size Management tables

If you still need the original Size + free-text colour tables:

1. Open the **SQL** tab.
2. Paste `database/cpanel_size_color_management.sql`.
3. Optionally run `database/cpanel_size_dropdown_fix.sql` so Add Product only lists Size Management entries.

| Object | Purpose |
| --- | --- |
| `sizes` | Size names for one business |
| `product_size` | Links a product to one or more sizes |
| `product_colors` | Older per-product colour tags (kept and mirrored) |
| `colors` | Colour catalog (this update) |
| `product_color` | Links a product to one or more catalogue colours |
| `transaction_sell_lines.line_size` / `line_color` | Optional cashier line choice |

`products.size` and `products.color` stay as comma-separated display copies.

## Laravel migrations (optional)

- `database/migrations/2026_10_31_180000_create_sizes_and_product_colors.php`
- `database/migrations/2026_11_05_120000_create_colors_catalog.php`

On cPanel without Terminal, prefer the SQL scripts above.
