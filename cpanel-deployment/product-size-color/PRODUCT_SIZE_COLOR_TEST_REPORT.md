# Product Size and Color test report

Date: 30 October 2026

The live cPanel database was not changed. The local `.env` points at MySQL on `127.0.0.1:3306`. That server refused the connection, and `vsoftpos.test` does not resolve on this machine, so the QPOS screens could not be opened in a browser.

The Laravel migration, saves, updates, validation, import mapping, and form markup were tested on an in-memory SQLite database using the same migration class and the same controller methods. SQLite does not apply MySQL `AFTER column` placement, so the final column order was not observed on MySQL.

Result of that run: **46 checks passed, 0 failed.**

## Files modified

- `app/Exports/ProductsExport.php`
- `app/Http/Controllers/ImportProductsController.php`
- `app/Http/Controllers/ProductController.php`
- `database/migrations/2026_10_30_120000_add_size_and_color_to_products_table.php` (new)
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
- `resources/views/product/partials/size_color_fields.blade.php` (new)

`App\Product` was not edited. It uses `$guarded = ['id']`, which already allows `size` and `color` to be saved. That guard list was checked in the test.

## Database

| Field | Type in the migration and SQL | Nullable |
|---|---|---|
| `size` | `string` / `VARCHAR(191)` | YES |
| `color` | `string` / `VARCHAR(191)` | YES |

Checked on SQLite:

- Running the migration once adds both columns.
- Running it a second time does not fail and does not duplicate the columns.
- A product inserted before the migration is still readable afterward, with `size` and `color` NULL, and its name and SKU unchanged.
- `down()` removes only those columns and leaves the product row in place.
- The columns can be added again after `down()`.

The phpMyAdmin script was reviewed for `DROP TABLE`, `DELETE FROM products`, and `TRUNCATE`. None of those statements are present. On the live cPanel account, the first script failed with `#1044 Access denied` for `information_schema`. It was replaced with direct `ALTER TABLE` statements that do not read `information_schema`. Those statements were not executed from this machine.

## Create and update

Checked through Eloquent on the temporary database and through `ProductController` helpers:

| Case | Result |
|---|---|
| Size `XL` and color `Navy Blue` | Saved |
| Size `42` only | Saved, color NULL |
| Color `Dark Grey` only | Saved, size NULL |
| Both empty / omitted | Saved as NULL |
| Text size `Medium` | Saved |
| `50mm` and `1L` | Saved |
| 191-character size and color | Saved |
| 192-character size | Rejected, with the existing status message |
| Empty values | Validation passes |
| Update size only | Color, name, and SKU stay the same |
| Update color only | Size stays the same |
| Update both | Both new values saved |
| Clear size | Size becomes NULL, color remains |
| Clear color | Color becomes NULL |
| Load a product whose size and color are NULL | Loads |
| Request that does not include the fields | Does not overwrite them |
| Columns absent | Size and color are left out of the save so create/update can still run |
| Blank text | Stored as NULL, surrounding spaces trimmed |

Quick-add over-length input returns a JSON error payload (`success` = 0) instead of a redirect.

## Import, export, and screens

Checked without uploading a file through the browser:

- A simple import header that includes `Size` and `Color` maps those values to import slots 40 and 41. Tyre Size and Note stay on their existing slots.
- A `SIZE` header is not treated as `TYRE SIZE`.
- An older simple row with no Size or Color headers still expands, with those slots empty.
- A full export-style row with `SIZE` and `COLOR` at the end is copied into slots 40 and 41, and the Size text is removed from the positional tyre-note slot so it is not also saved as the tyre note.
- A full row with no Size or Color headers keeps its existing cells.
- The export class appends `SIZE` and `COLOR`.
- The Add / Edit partial renders `name="size"` and `name="color"`, placeholders `Enter product size` and `Enter product color`, `maxlength="191"`, and no `required` attribute.
- Edit values `38` and `Black` are filled into the inputs.
- Duplicate values `XXL` and `Red` are filled into the inputs.
- Create, edit, and quick-add templates include the partial.
- The product list header and DataTables column list include Size and Color when the database columns exist.
- The view modal prints Size and Color.
- `public/js/product.js` keeps both fields optional with a 191-character maximum on the main product form.

## Backward compatibility

On the temporary database, an existing product row survived the migration with its name and SKU unchanged and with NULL size and color. Creates that omit the new fields store NULL. Updates that do not send the fields do not clear them. Bulk edit was not modified, so it still writes only category, subcategory, brand, tax, locations, and variation prices.

POS search code was not modified. The product **list** search was extended so a list search can match size and color. That does not change the POS suggestion query.

## Tests that were not performed

These were not run, so they are not marked as passed:

- The SQL file against MySQL or MariaDB, including `VARCHAR(191)` and `AFTER name` / `AFTER size` on a real server
- phpMyAdmin, cPanel File Manager, or the live site
- A logged-in browser pass of Add Product, edit, list, delete, import upload, or export download
- Desktop, tablet, and mobile layout
- Dark theme and light theme in the running application
- POS sale, payment, purchase selection, stock adjustment, barcode label print, variation edit, or reports
- Product deletion
- Opening the stock-report tab on the products page

The stock-report tab was left unchanged on purpose. Barcode labels were left unchanged on purpose. Size and Color are one pair of values for the product, including a variable product. They are not stored separately on each variation.

## Known limitations

- Labels are defined in `lang/en/product.php`. The Add Product screen in the supplied screenshot is English. Another locale will not have these two labels until that language file is updated.
- The old full `.xls` import template was not edited. Import still accepts it. Size and Color are read when a header cell is `Size` or `Color`.
- If the PHP files are uploaded before the SQL runs, product save still works, but Size and Color are ignored until the columns exist. Run the SQL first.
- Listing columns stay hidden until `products.size` and `products.color` exist, so the product table does not request missing columns.
- No public PHP migration script is included.
