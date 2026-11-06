# Update Color Management on cPanel

No Terminal and no SSH. Use cPanel File Manager and phpMyAdmin only.

Do not replace `.env`. Do not upload `vendor` or `node_modules`. Do not delete products, sales, stock, sizes, colors, or payments.

## 1. Back up

1. In cPanel File Manager, compress the current application folder and download that archive.
2. In phpMyAdmin, export the database.

## 2. Database first

1. Open phpMyAdmin and select the live QPOS database.
2. Open the **SQL** tab.
3. Paste the full contents of `database/cpanel_color_catalog.sql` from this package.
4. Click **Go**.

See `DATABASE_UPDATE_GUIDE.md` for checks and troubleshooting.

The script is safe to run a second time. It creates `colors` and `product_color`, then copies existing colour names from `product_colors` and `products.color` without deleting sales or stock.

## 3. Upload application files

Extract this zip so the paths under `files/` land on the live app root (the folder that already contains `app`, `resources`, `public` / `public_html` as on your host).

Typical layout on this host:

- Application PHP / Blade / lang / routes → `/home/mtcqpos/public_html/` (same places as today)
- Public JS → `/home/mtcqpos/public_html/js/product-size-color.js` (or `public/js/` if your install keeps a separate `public` folder)

Overwrite only the files included in this package. Leave `.env` untouched.

Files included:

- `app/Color.php` (new)
- `app/Http/Controllers/ColorController.php` (new)
- `app/Http/Controllers/ProductController.php`
- `app/Http/Middleware/AdminSidebarMenu.php`
- `app/Product.php`
- `app/Utils/ProductSizeColor.php`
- `lang/en/color.php` (new)
- `lang/en/product.php`
- `resources/views/color/manage.blade.php` (new)
- `resources/views/color/create.blade.php` (new)
- `resources/views/color/edit.blade.php` (new)
- `resources/views/product/partials/size_color_fields.blade.php`
- `resources/views/layouts/partials/javascripts.blade.php`
- `public/js/product-size-color.js` → copy into the live public JS folder
- `routes/web.php`
- `database/migrations/2026_11_05_120000_create_colors_catalog.php` (optional on cPanel; SQL above is enough)

## 4. Clear compiled views

Open `storage/framework/views` and delete the files inside that folder. Keep the folder.

If `bootstrap/cache` has cached config or routes, leave `packages.php` and `services.php`. Do not delete `.env`.

## 5. Browser refresh

Hard-refresh (Ctrl+F5) after sign-in so `product-size-color.js?v=...-colorcatalog1` loads.

## 6. Smoke checks

1. Sidebar → Products → **Colors** opens and lists colours.
2. Add colours such as Red, White, Black, Blue.
3. Add Product → Color field is a searchable multi-select with a blue **+** (same as Size).
4. Typing `Bl` suggests Black and Blue only from Color Management.
5. Select multiple colours, remove one chip, save the product.
6. Edit the product and confirm colours are still selected.
7. Product list / POS still show the colour text column.
8. Print Labels with Color enabled still previews and prints assigned colours.
9. Try deleting a colour that is assigned to a product — it must be blocked.
