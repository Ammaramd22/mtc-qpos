# QPOS POS product list view — cPanel installation

This package is a **minimal patch**. It is not a full QPOS application. It adds a Grid / List switch on the POS product panel at `/pos/create`.

No Terminal or SSH access is required. No database change is required. Do not import a SQL file. Do not replace the live `.env` file. Do not change product records, stock, prices, or sales.

## Files in this folder

| File | Purpose |
|---|---|
| `QPOS-POS-Product-List-View-Update-cPanel.zip` | The five updated application files |
| `CPANEL_INSTALLATION_GUIDE.md` | This guide |
| `POS_PRODUCT_LIST_VIEW_TEST_REPORT.md` | What was tested and what was not |
| `POS_PRODUCT_LIST_VIEW_CHANGELOG.md` | What changed and why |

## Files inside the ZIP

Extracting the ZIP must put each file at the path below, relative to the QPOS application root (the folder that contains `artisan`).

| ZIP path | Replace this live file |
|---|---|
| `app/Http/Controllers/SellPosController.php` | `app/Http/Controllers/SellPosController.php` |
| `resources/views/sale_pos/partials/product_list.blade.php` | `resources/views/sale_pos/partials/product_list.blade.php` |
| `resources/views/sale_pos/partials/touch_main.blade.php` | `resources/views/sale_pos/partials/touch_main.blade.php` |
| `public/js/pos-touch.js` | `public/js/pos-touch.js` |
| `public/css/pos-touch.css` | `public/css/pos-touch.css` |

If the website address points at `public_html`, that folder is usually the Laravel `public` folder. In that case:

- `public/js/pos-touch.js` in the ZIP goes to `public_html/js/pos-touch.js`
- `public/css/pos-touch.css` goes to `public_html/css/pos-touch.css`
- `app/Http/Controllers/SellPosController.php` and the two Blade files go in the application folder **above** `public_html`

## 1. Back up the five live files

In cPanel File Manager, create a folder named `qpos-pos-list-view-backup`. Copy these files into it and keep the same folder structure:

- `app/Http/Controllers/SellPosController.php`
- `resources/views/sale_pos/partials/product_list.blade.php`
- `resources/views/sale_pos/partials/touch_main.blade.php`
- `public/js/pos-touch.js`
- `public/css/pos-touch.css`

If `public` is `public_html`, back up the JavaScript and CSS from `public_html`, and the PHP and Blade files from the application root.

Leave this backup in place until Grid view and List view have both been checked on a register.

## 2. Upload the ZIP

1. In File Manager, open a temporary folder such as `qpos-pos-list-view-update`. Create it next to the application if it does not exist. Do not upload the ZIP into `public` or `public_html`.
2. Click **Upload** and choose `QPOS-POS-Product-List-View-Update-cPanel.zip`.
3. After the upload finishes, go back to the folder that contains the ZIP.

## 3. Extract into the temporary folder

1. Right-click the ZIP.
2. Click **Extract**.
3. Extract it inside `qpos-pos-list-view-update`, not into the live application root.
4. Open the extracted folder and confirm these paths exist:
   - `app/Http/Controllers/SellPosController.php`
   - `resources/views/sale_pos/partials/product_list.blade.php`
   - `resources/views/sale_pos/partials/touch_main.blade.php`
   - `public/js/pos-touch.js`
   - `public/css/pos-touch.css`

## 4. Replace only those files

Copy each extracted file over the live file at the destination in the table above. When File Manager asks to overwrite, confirm overwrite for these five files only.

Do not copy any other file. Do not upload `.env`.

## 5. Refresh browser assets

The POS page loads `pos-touch.js` and `pos-touch.css` with a version number from `config/constants.php` (`asset_version`).

1. Open `config/constants.php` in File Manager → **Edit**. This file is in the application root, not in `public`.
2. Find the line `'asset_version' =>`.
3. Increase that number by 1. For example, `919` becomes `920`. Save the file.
4. Do not replace the whole `config/constants.php` file. Other lines in the live file must stay as they are.

If `bootstrap/cache/config.php` exists, Laravel is using a cached copy of that number and will ignore the edit until the cache file is removed.

1. Open `bootstrap/cache/`.
2. If `config.php` is there, delete **only** `config.php`.
3. Leave `.env`, `bootstrap/cache/packages.php`, and `bootstrap/cache/services.php` in place.

There is no safe one-click cache button in File Manager. Do not upload a PHP file that clears caches or runs commands.

The product list is a Blade view. If the POS still shows only the old cards after a hard refresh:

1. Open `storage/framework/views/`.
2. Delete the compiled `.php` files in that folder. Leave the folder itself.
3. Laravel rebuilds them on the next page load.

## 6. Test Grid view

1. On a register, open `https://mtc.qpos.avenque.io/pos/create`.
2. Hard refresh: **Ctrl+F5** in Chrome or Edge.
3. In the product toolbar, **Grid** should be available next to the search box.
4. Product cards should look the same as before: image, name, SKU, stock, and price.
5. Click one card. It should enter the cart the same way it did before this update.
6. Search by name and by SKU. Change category, then return to **All Categories**. The cards should follow the search and the category.

## 7. Test List view

1. Click **List**. The page must not reload. The same products should appear as compact rows.
2. Each row should show a small image, the product name, SKU, stock, selling price, and **Add**.
3. Size and color appear when the product has them. A product with no size and no color should not leave an empty size or color column.
4. Category is shown under the name while **All Categories** is selected. It is hidden after a single category is selected, because every row would repeat that category.
5. Click a row once. The cart should gain that product once, not twice.
6. Click **Add** on another row. That should also add the product once.
7. Search, clear the search, and change category. The list should update with the same products the grid would show.
8. Click **Grid**. The cards should return. The cart lines already added must still be there.

The cashier's last choice is stored in that browser. The next visit to POS opens in the same view.

## 8. Confirm the rest of the sale

On the same bill, check only that existing behaviour is unchanged:

- Customer selection
- Quantity on the cart line
- Discount and shipping
- MRP / price group, if you use it
- Pay, and a test receipt if you normally print one

Do not change stock or prices from this screen while testing, other than a normal test sale you can void or refund by your usual process.

## 9. Roll back

1. Copy the five files from `qpos-pos-list-view-backup` back to their original locations and overwrite.
2. If you changed `asset_version`, set it back to the previous number.
3. If you deleted compiled views, delete the `.php` files in `storage/framework/views/` again.
4. Hard refresh the POS page (Ctrl+F5).

No database restore is required for this patch.
