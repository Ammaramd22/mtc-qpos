# QPOS POS list view — final table fix — cPanel installation

This package replaces the POS List View on `/pos/create` with a full-width table of horizontal product rows. Grid View stays the original product cards.

No Terminal or SSH access is required. No database change is required. Do not import a SQL file. Do not replace the live `.env` file. Do not change product records, stock, prices, sales, or payment calculations. Do not deploy by uploading this ZIP into the live application root.

## Files in this folder

| File | Purpose |
|---|---|
| `QPOS-POS-List-View-Final-Fix-cPanel.zip` | The updated application files |
| `CPANEL_INSTALLATION_GUIDE.md` | This guide |
| `POS_LIST_VIEW_TEST_REPORT.md` | What was tested and what was not |
| `POS_LIST_VIEW_CHANGELOG.md` | What changed and why |

## Files inside the ZIP

Extracting the ZIP must put each file at the path below, relative to the QPOS application root (the folder that contains `artisan`).

| ZIP path | Replace this live file |
|---|---|
| `app/Http/Controllers/SellPosController.php` | `app/Http/Controllers/SellPosController.php` |
| `resources/views/sale_pos/partials/product_list.blade.php` | `resources/views/sale_pos/partials/product_list.blade.php` |
| `resources/views/sale_pos/partials/product_list_rows.blade.php` | `resources/views/sale_pos/partials/product_list_rows.blade.php` |
| `resources/views/sale_pos/partials/touch_main.blade.php` | `resources/views/sale_pos/partials/touch_main.blade.php` |
| `public/js/pos-touch.js` | `public/js/pos-touch.js` |
| `public/js/pos.js` | `public/js/pos.js` |
| `public/css/pos-touch.css` | `public/css/pos-touch.css` |

If `product_list_rows.blade.php` is not on the live site yet, copy it into `resources/views/sale_pos/partials/`. Do not rename it. If a previous list-view package already placed that file, overwrite it with this copy.

If the website address points at `public_html`, that folder is usually the Laravel `public` folder. In that case:

- `public/js/pos-touch.js` in the ZIP goes to `public_html/js/pos-touch.js`
- `public/js/pos.js` goes to `public_html/js/pos.js`
- `public/css/pos-touch.css` goes to `public_html/css/pos-touch.css`
- `app/Http/Controllers/SellPosController.php` and the Blade files go in the application folder **above** `public_html`

## 1. Back up the live files

In cPanel File Manager, create a folder named `qpos-list-view-final-backup`. Copy these files into it and keep the same folder structure:

- `app/Http/Controllers/SellPosController.php`
- `resources/views/sale_pos/partials/product_list.blade.php`
- `resources/views/sale_pos/partials/product_list_rows.blade.php` (only if it already exists)
- `resources/views/sale_pos/partials/touch_main.blade.php`
- `public/js/pos-touch.js`
- `public/js/pos.js`
- `public/css/pos-touch.css`

If `public` is `public_html`, back up the JavaScript and CSS from `public_html`, and the PHP and Blade files from the application root.

Leave this backup in place until Grid view and List view have both been checked on a register.

## 2. Upload the ZIP

1. In File Manager, open a temporary folder such as `qpos-list-view-final-fix`. Create it next to the application if it does not exist. Do not upload the ZIP into `public` or `public_html`.
2. Click **Upload** and choose `QPOS-POS-List-View-Final-Fix-cPanel.zip`.
3. After the upload finishes, go back to the folder that contains the ZIP.

## 3. Extract into the temporary folder

1. Right-click the ZIP.
2. Click **Extract**.
3. Extract it inside `qpos-list-view-final-fix`, not into the live application root.
4. Open the extracted folder and confirm these paths exist:
   - `app/Http/Controllers/SellPosController.php`
   - `resources/views/sale_pos/partials/product_list.blade.php`
   - `resources/views/sale_pos/partials/product_list_rows.blade.php`
   - `resources/views/sale_pos/partials/touch_main.blade.php`
   - `public/js/pos-touch.js`
   - `public/js/pos.js`
   - `public/css/pos-touch.css`

## 4. Replace only those files

Copy each extracted file over the live file at the destination in the table above. When File Manager asks to overwrite, confirm overwrite for these files only.

`product_list_rows.blade.php` belongs beside `product_list.blade.php`.

Do not copy any other file. Do not upload `.env`.

## 5. Refresh cached pages and styles

The POS page loads CSS and JavaScript with a version number from `config/constants.php` (`asset_version`). The updated `touch_main.blade.php` loads `pos-touch.css` with an extra `&lv=5` so the list styles are not stuck on the previous file. Increasing `asset_version` is still required, because `pos.js` and `pos-touch.js` use that number.

1. Open `config/constants.php` in File Manager → **Edit**. This file is in the application root, not in `public`.
2. Find the line `'asset_version' =>`.
3. Increase that number by 1. Save the file.
4. Do not replace the whole `config/constants.php` file. Other lines in the live file must stay as they are.

If `bootstrap/cache/config.php` exists, delete that one file only. Do not delete the `bootstrap/cache` folder.

Laravel may still be serving an old compiled copy of the product list. If List View still shows narrow cards after the steps above:

1. Open `storage/framework/views/`.
2. Delete the compiled `.php` files in that folder only. Do not delete the folder, and do not delete files outside it.
3. Reload `/pos/create`. Laravel rebuilds those compiled views.

## 6. Check the register

1. Open `/pos/create`.
2. Hard refresh the browser with Ctrl+F5.
3. Confirm **Grid** still shows the original product cards.
4. Click **List**. The product area must become one full-width table. The headings Image, Product Name, SKU / Barcode, Size, Color, Stock, Price, and Action must sit on a single line across the product panel. Each product must be one horizontal row under those headings, with a small thumbnail. Size and Color cells stay in the row and stay blank when that product has no size or color.
5. On a phone, the row keeps the thumbnail, name, stock, price, and Add. SKU, Size, and Color are hidden so the row does not overflow.
6. Click a row once and confirm the product is added to the cart once. Click **Add** on another row and confirm that also adds once.
7. Search by name and by SKU. The list must stay in table form. Clear the search.
8. Choose a category, then return to All Categories.
9. Switch Grid → List → Grid. The cart lines must stay. The page must not reload.
10. Scan a barcode the way the register normally does, and confirm the same product is added.

## 7. Rollback

Copy the backed-up files back over the live files, including `public/js/pos.js`. If `product_list_rows.blade.php` did not exist before this install, delete it. If it did exist, restore the backup copy. Set `asset_version` back to the previous number if you changed it. Delete the compiled files in `storage/framework/views/` again so the previous Blade files are recompiled. Hard refresh `/pos/create`.
