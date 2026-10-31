# Update POS product loading on cPanel

No Terminal and no SSH. Use cPanel File Manager only.

Do not replace `.env`. Do not upload `vendor` or `node_modules`. Do not delete products, sales, stock, sizes, colors, or payments.

This update does not need a database change. Do not run SQL in phpMyAdmin for this fix.

## 1. Back up

1. In cPanel File Manager, compress the current application folder and download that archive.
2. In phpMyAdmin, export the database.

## 2. If the whole site says HTTP 500

Copying the `public` folder onto `public_html` replaced `index.php` with a file that looks for `vendor` above `public_html`. Extract the zip in step 3. That puts the correct `index.php` back.

## 3. Upload

Extract this zip in `/home/mtcqpos/public_html`.

`index.php` in the zip belongs in `public_html`. It loads `vendor` and `bootstrap` from `public_html`. The copy that came out of the `public` folder looks one level too high and that is why the site returns HTTP 500. Extracting this zip replaces that file.

Extracting also writes these files in place:

- `js/pos.js`
- `app/Http/Controllers/SellPosController.php`
- `resources/views/sale_pos/create.blade.php`
- `resources/views/sale_pos/partials/product_list.blade.php`
- `resources/views/sale_pos/partials/product_list_rows.blade.php`

`CHANGELOG.md`, `CPANEL_UPDATE_GUIDE.md`, and `TEST_REPORT.md` are instructions. They do not need to stay on the server.

## 4. Clear compiled views

Open `/home/mtcqpos/public_html/storage/framework/views` and delete the files inside that folder. Keep the folder.

Then open POS and press Ctrl+F5 so the browser loads the new script. The page source should mention `pos.js` with `-posload1`.

If the site has a cached config or routes folder under `bootstrap/cache`, leave `packages.php` and `services.php`. Do not delete `.env`.

## 5. Database

Skip phpMyAdmin. This fix does not add or change tables.

## 6. Check POS

1. Sign in and open POS (`/pos/create`).
2. Confirm product cards appear with All Categories selected. Do not refresh first.
3. Select a category, then return to All Categories.
4. Search by product name, SKU, and barcode. Clear the search and confirm the products return.
5. If the grid says it could not load products, press Retry once. The products should appear without reloading the whole page.
6. Add a product to the cart and confirm the price and stock look the same as before.
7. Repeat in light theme, dark theme, and a narrow browser window.

Cashiers who already have POS open should refresh once after the upload so the browser picks up the new script.
