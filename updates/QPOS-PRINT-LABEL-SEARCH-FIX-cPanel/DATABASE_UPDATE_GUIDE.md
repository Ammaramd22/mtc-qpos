# Database update guide — Print Labels search

No database change is required.

Do not run a migration. Do not import a SQL file. Do not edit or delete product, stock, size, color, sale, or payment rows.

The search fix uses the tables that Size and Color management already created:

- `products` (`name`, `sku`, `size`, `color`, `is_inactive`)
- `variations` (`sub_sku`, the barcode)
- `product_size` and `sizes`
- `product_color` and `colors` when the color catalog is installed
- `product_colors` when only the older color tags exist

Missing size or color rows do not remove a product from search.

## Optional read-only check

In phpMyAdmin, select the live QPOS database, open the **SQL** tab, and run these one at a time. They only read data.

```sql
SELECT COUNT(*) AS list_products
FROM products
WHERE type <> 'modifier';
```

```sql
SELECT COUNT(*) AS inactive_products
FROM products
WHERE type <> 'modifier' AND is_inactive = 1;
```

```sql
SELECT sizes.name AS size_name, COUNT(*) AS products
FROM product_size
INNER JOIN sizes ON sizes.id = product_size.size_id
GROUP BY sizes.name
ORDER BY sizes.name;
```

```sql
SELECT colors.name AS color_name, COUNT(*) AS products
FROM product_color
INNER JOIN colors ON colors.id = product_color.color_id
GROUP BY colors.name
ORDER BY colors.name;
```

If `product_color` or `colors` does not exist yet, skip the last query. Install the earlier Color Management package before expecting color-catalog search. Product color text on `products.color` is still searched.

Take a phpMyAdmin **Export** backup before any future database work. This update does not need one in order to run, but a backup is still sensible before overwriting application files.
