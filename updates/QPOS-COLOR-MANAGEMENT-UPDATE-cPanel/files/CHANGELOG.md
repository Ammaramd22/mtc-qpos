# QPOS Color Management update

## Added

- Products sidebar includes a **Colors** section next to **Sizes**.
- Color Management supports add, edit, delete, product-usage counts, and blocks delete when a colour is still assigned to products.
- Add Product and Edit Product use the same searchable multi-select workflow as Size (Select2 tags + blue + quick-add modal).
- Only colours created in Color Management appear as new suggestions (`is_catalog = 1`). Typing free text no longer creates an unregistered colour on the product.
- Multiple colours per product, duplicate prevention, removable chips via Select2, and quick-add without leaving the product form.
- Database tables `colors` and `product_color` (catalog + pivot), matching the Size architecture.

## Preserved

- Existing `products.color` comma-separated text stays in place for product list, POS grid/list, search, labels, import, and export.
- Existing `product_colors` tag rows are migrated into the catalog and kept mirrored on save.
- Migrated colour names stay on existing products. They are not auto-offered on new products until added (or promoted) from Color Management — same behaviour as Size Management.
- No changes to payments, discounts, stock quantities, invoices, or customer balances.

## Files

- `app/Color.php`
- `app/Http/Controllers/ColorController.php`
- `app/Http/Controllers/ProductController.php` (colour id validation)
- `app/Http/Middleware/AdminSidebarMenu.php`
- `app/Product.php`
- `app/Utils/ProductSizeColor.php`
- `resources/views/color/*`
- `resources/views/product/partials/size_color_fields.blade.php`
- `public/js/product-size-color.js`
- `routes/web.php`
- `lang/en/color.php`, `lang/en/product.php`
- `database/cpanel_color_catalog.sql`
- `database/migrations/2026_11_05_120000_create_colors_catalog.php`
