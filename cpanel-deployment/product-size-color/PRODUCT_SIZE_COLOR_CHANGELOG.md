# Product Size and Color changelog

Optional product-level **Size** and **Color** fields for the existing Add Product, edit, list, view, quick add, import, and export flows.

Size and Color are stored on `products`. They are not variation rows, and they do not change barcode, stock, price, purchase, or sale calculations.

## Database

New migration: `database/migrations/2026_10_30_120000_add_size_and_color_to_products_table.php`

| Column | Type | Nullable | Default |
|---|---|---|---|
| `products.size` | `VARCHAR(191)` | YES | NULL |
| `products.color` | `VARCHAR(191)` | YES | NULL |

The migration adds each column only when it is missing. It does not rebuild `products`. `down()` drops only these two columns.

phpMyAdmin equivalent: `PRODUCT_SIZE_COLOR_DATABASE_SQL.sql`.

## Application

- Add Product, Edit Product, quick add, and product duplicate show the fields. Both are optional. Empty values are stored as NULL.
- Values longer than 191 characters are rejected. Shorter text, including numbers such as `42`, `50mm`, and `1L`, is accepted.
- The product list can show and filter Size and Color after the columns exist. The list search also matches those values. POS product search was not changed.
- The product view modal shows Size and Color.
- Product export adds `SIZE` and `COLOR` at the end of the existing columns.
- Simple product import reads `Size` and `Color` when those headers are present. Files without those headers still import. A `Size` header is not treated as `Tyre Size`.
- Bulk edit still updates category, brand, tax, location, and prices only, so it leaves Size and Color as they are.
- `App\Product` already uses `$guarded = ['id']`, so the new columns can be saved without changing the model.

## Files

- `app/Exports/ProductsExport.php`
- `app/Http/Controllers/ImportProductsController.php`
- `app/Http/Controllers/ProductController.php`
- `database/migrations/2026_10_30_120000_add_size_and_color_to_products_table.php`
- `lang/en/product.php`
- `public/files/import_products_simple_sample.csv`
- `public/files/import_products_simple_template.csv`
- `public/js/product.js`
- `resources/views/product/create.blade.php`
- `resources/views/product/edit.blade.php`
- `resources/views/product/index.blade.php`
- `resources/views/product/view-modal.blade.php`
- `resources/views/product/partials/product_list.blade.php`
- `resources/views/product/partials/quick_add_product.blade.php`
- `resources/views/product/partials/size_color_fields.blade.php`

## Not changed

POS totals, payments, stock movements, purchases, barcode generation, variation structure, pricing, and reports were not modified.
