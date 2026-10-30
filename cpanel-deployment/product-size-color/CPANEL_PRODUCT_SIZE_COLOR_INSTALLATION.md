# QPOS product Size and Color — cPanel installation

This update adds two optional product fields, **Size** and **Color**, to an existing QPOS installation. It does not replace the application. No Terminal or SSH access is required.

Apply the database change **before** uploading the PHP files. The form can appear before the columns exist, and values entered in that window are not stored.

## Files in this folder

| File | Purpose |
|---|---|
| `QPOS-Product-Size-Color-Update-cPanel.zip` | Changed application files only |
| `PRODUCT_SIZE_COLOR_DATABASE_SQL.sql` | phpMyAdmin script that adds the columns |
| `CPANEL_PRODUCT_SIZE_COLOR_INSTALLATION.md` | This guide |
| `PRODUCT_SIZE_COLOR_TEST_REPORT.md` | What was tested |
| `PRODUCT_SIZE_COLOR_CHANGELOG.md` | What changed |

The same SQL file is also inside the ZIP at `database/sql/PRODUCT_SIZE_COLOR_DATABASE_SQL.sql`.

## 1. Find the live QPOS application folder

1. Log in to cPanel.
2. Open **File Manager**.
3. Find the QPOS application root. It is the folder that contains all of these together:
   - `artisan`
   - `app`
   - `resources`
   - `public`
   - `database`
4. The website address `mtc.qpos.avenueque.io` may point at the `public` folder, or at a `public_html` folder that holds the contents of `public`. The ZIP must be extracted in the **application root**, the folder that contains `artisan`, not inside `public` only.
5. Confirm the folder by opening `app/Http/Controllers/ProductController.php`. If that file is there, you are in the right place.

Do not upload this package into a different site or into a backup folder.

## 2. Back up the files that will be replaced

In File Manager, copy each of these files to a folder named `qpos-size-color-backup` **before** extracting the ZIP. Keep the same folder structure inside the backup.

- `app/Exports/ProductsExport.php`
- `app/Http/Controllers/ImportProductsController.php`
- `app/Http/Controllers/ProductController.php`
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

These two files are new. They do not need a backup:

- `database/migrations/2026_10_30_120000_add_size_and_color_to_products_table.php`
- `resources/views/product/partials/size_color_fields.blade.php`

## 3. Back up the MySQL database

1. Open **phpMyAdmin** from cPanel.
2. Select the QPOS database in the left column. The name is the one in the live `.env` file (`DB_DATABASE`). Do not open `.env` in a public folder, and do not send it to anyone.
3. Click **Export**.
4. Choose **Quick** and format **SQL**.
5. Click **Go** and store the downloaded `.sql` file with the file backup.

Do not import that backup unless you need to restore the whole database.

## 4. Apply the database change

1. In phpMyAdmin, keep the QPOS database selected.
2. Open the **SQL** tab.
3. Open `PRODUCT_SIZE_COLOR_DATABASE_SQL.sql` from this folder in a text editor.
4. Paste the whole script into the SQL box.
5. Click **Go**.

The script adds two nullable columns:

| Column | Type | Null | Default |
|---|---|---|---|
| `size` | `VARCHAR(191)` | YES | NULL |
| `color` | `VARCHAR(191)` | YES | NULL |

`size` is placed after `name`. `color` is placed after `size`. Existing product rows are not updated. Their new values stay NULL.

This host cannot read `information_schema` (`#1044 Access denied`). Do not use a script that queries `information_schema` or uses `PREPARE`. The SQL file uses direct `ALTER TABLE` statements only.

If a statement says `Duplicate column name`, that column is already there. Leave it and run the other statement.

### Migration record

The SQL file does not insert a migrations row. That insert is optional on a server with no Terminal, and the migration file checks whether the columns exist, so a later `php artisan migrate` will not create duplicate columns.

There is no public PHP address for running migrations. Do not add one.

## 5. Confirm the columns

In phpMyAdmin, open the `products` table, then **Structure**.

You should see:

- `size`, type `varchar(191)`, Null = Yes
- `color`, type `varchar(191)`, Null = Yes

Or run:

```sql
SHOW COLUMNS FROM `products` LIKE 'size';
SHOW COLUMNS FROM `products` LIKE 'color';
```

Expect one row each, with `Null` = `YES` and `Type` = `varchar(191)`.

## 6. Upload the ZIP

1. In File Manager, open the QPOS application root from step 1.
2. Click **Upload**.
3. Upload `QPOS-Product-Size-Color-Update-cPanel.zip`.
4. Do not upload `.env`. This package does not contain it.

## 7. Extract the ZIP

1. Right-click the ZIP and choose **Extract**.
2. Extract into the application root, the folder that contains `artisan`.
3. If cPanel asks to overwrite, allow overwrite for the files listed in step 2 only.
4. The ZIP paths already match the application. For example, `app/Http/Controllers/ProductController.php` in the ZIP must land on the existing `app/Http/Controllers/ProductController.php`.
5. After extraction, confirm these new files exist:
   - `resources/views/product/partials/size_color_fields.blade.php`
   - `database/migrations/2026_10_30_120000_add_size_and_color_to_products_table.php`
6. Delete the ZIP from the server after a successful extract so it is not left in a public folder. If you uploaded it inside `public` or `public_html` by mistake, delete it and extract again in the application root.

## 8. If the new fields do not appear

Replacing the Blade files is usually enough. Laravel rebuilds a view when the file on disk is newer than the compiled copy.

If the Add Product page still has no Size or Color field:

1. In File Manager, open `storage/framework/views`.
2. Delete the compiled view files inside that folder. Do not delete the `views` folder itself.
3. Do not delete `storage/framework/sessions` or `storage/logs`.
4. Reload Add Product.

If the page still runs the old PHP after the controller file was replaced, use cPanel **MultiPHP Manager** or the PHP restart option for the domain, if one is shown. That reloads cached PHP without SSH. Do not add a public PHP file to clear caches.

## 9. Check Add Product

1. Log in to QPOS.
2. Open **Products → Add Product**.
3. Confirm two optional text fields:
   - **Size**, placeholder `Enter product size`
   - **Color**, placeholder `Enter product color`
4. They sit in the same three-column form, after Unit and before Brand.
5. Neither field shows a required asterisk. Saving with both empty must still work.

## 10. Create a test product

Create one product you can delete afterward:

- Item Name: `Size Color Test`
- Size: `XL`
- Color: `Navy Blue`
- Fill the unit, prices, and any other fields the form already requires.

Save it. The product should appear in the product list with Size `XL` and Color `Navy Blue`.

Then create a second product with Size and Color left blank. That save must succeed, and the list should show empty Size and Color cells.

## 11. Edit an existing product

1. Edit a product that was created before this update.
2. Size and Color should be empty.
3. Enter a size, save, and reopen the edit page. The size should still be there. Name, barcode, prices, and stock should be unchanged.
4. Clear the size, save, and reopen. The field should be empty again.
5. Open **View** for the test product and confirm Size and Color are shown.

## 12. Check the database

In phpMyAdmin:

```sql
SELECT id, name, sku, size, color
FROM products
WHERE name = 'Size Color Test';
```

`size` should be `XL` and `color` should be `Navy Blue`.

```sql
SELECT id, name, size, color
FROM products
WHERE size IS NULL AND color IS NULL
LIMIT 5;
```

Older products should still be listed. Their `id` and `sku` stay the same.

Delete the test products from **Products** when you are finished, using the normal delete action.

## 13. Check the product list and POS

1. On **Products**, confirm the Size and Color columns. Empty values should be blank, not an error.
2. Use the list search and the Size / Color filters.
3. Open POS and add a normal product to a sale, then cancel or complete it the way you usually test a sale. Size and Color are not part of the sale calculation.
4. Open a purchase screen only far enough to see that product search still returns products. Do not post a purchase unless you mean to.

## 14. Roll back the files

If the new pages misbehave:

1. Restore the backed-up files from `qpos-size-color-backup` over the same paths.
2. Delete the two new files if you want the previous templates back:
   - `resources/views/product/partials/size_color_fields.blade.php`
   - `database/migrations/2026_10_30_120000_add_size_and_color_to_products_table.php`
3. Leave the `size` and `color` columns in `products`. They are nullable, and the previous code does not read them. Leaving them keeps any values already saved and avoids a destructive change.
4. Reload the site. If PHP still shows the new page, use the cPanel PHP restart from step 8.

Only drop the columns if you must remove them. That deletes saved sizes and colors and does not delete products:

```sql
ALTER TABLE `products` DROP COLUMN `color`;
ALTER TABLE `products` DROP COLUMN `size`;
DELETE FROM `migrations`
WHERE `migration` = '2026_10_30_120000_add_size_and_color_to_products_table';
```

Run the `DELETE` only if that migration row was inserted. Restore the full database backup from step 3 if a product row itself was damaged. This update does not include a statement that updates or deletes product rows.

## What this update does not use

- No Terminal
- No SSH
- No `php artisan migrate` on the server
- No public migration URL
