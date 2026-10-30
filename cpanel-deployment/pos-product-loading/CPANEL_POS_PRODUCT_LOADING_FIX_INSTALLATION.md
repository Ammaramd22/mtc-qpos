# QPOS POS product loading fix — cPanel installation

This package is a **minimal patch**. It is not a full QPOS application. It replaces five files that load and draw the product grid on `/pos/create`.

No Terminal or SSH access is required. No database change is required. Do not import a SQL file. Do not replace the live `.env` file.

## Files in this folder

| File | Purpose |
|---|---|
| `QPOS-POS-Product-Loading-Fix-cPanel.zip` | The five updated application files |
| `CPANEL_POS_PRODUCT_LOADING_FIX_INSTALLATION.md` | This guide |
| `POS_PRODUCT_LOADING_TEST_REPORT.md` | What was tested and what was not |
| `POS_PRODUCT_LOADING_CHANGELOG.md` | What changed and why |

## Files inside the ZIP

Extracting the ZIP must put each file at the path below, relative to the QPOS application root (the folder that contains `artisan`).

| ZIP path | Replace this live file |
|---|---|
| `public/js/pos.js` | `public/js/pos.js` |
| `public/js/pos-live-search.js` | `public/js/pos-live-search.js` |
| `public/js/pos-touch.js` | `public/js/pos-touch.js` |
| `public/css/pos-touch.css` | `public/css/pos-touch.css` |
| `resources/views/sale_pos/partials/product_list.blade.php` | `resources/views/sale_pos/partials/product_list.blade.php` |

If the website address points at `public_html`, that folder is usually the Laravel `public` folder. In that case:

- `public/js/pos.js` in the ZIP goes to `public_html/js/pos.js`
- `public/js/pos-live-search.js` goes to `public_html/js/pos-live-search.js`
- `public/js/pos-touch.js` goes to `public_html/js/pos-touch.js`
- `public/css/pos-touch.css` goes to `public_html/css/pos-touch.css`
- `resources/views/sale_pos/partials/product_list.blade.php` goes in the application folder **above** `public_html`, at `resources/views/sale_pos/partials/product_list.blade.php`

## 1. Find the live QPOS folder

1. Log in to cPanel.
2. Open **File Manager**.
3. Find the application root. It contains `artisan`, `app`, `resources`, `public`, and `config` together.
4. Confirm you are in the right place by opening `resources/views/sale_pos/partials/product_list.blade.php`.
5. The POS address is `https://mtc.qpos.avenque.io/pos/create`. The domain may point at the `public` folder, or at `public_html`. The Blade file is not inside `public`.

## 2. Back up the five files

In File Manager, create a folder named `qpos-product-loading-backup`. Copy these files into it and keep the same folder structure:

- `public/js/pos.js`
- `public/js/pos-live-search.js`
- `public/js/pos-touch.js`
- `public/css/pos-touch.css`
- `resources/views/sale_pos/partials/product_list.blade.php`

If `public` is `public_html`, back up the matching files under `public_html` and the Blade file under the application root.

Leave this backup in place until the POS grid has been checked.

## 3. Upload the ZIP

1. In File Manager, open a temporary folder such as `qpos-product-loading-update`. Create it next to the application if it does not exist. Do not upload the ZIP into `public` or `public_html`.
2. Click **Upload** and choose `QPOS-POS-Product-Loading-Fix-cPanel.zip`.
3. After the upload finishes, go back to the folder that contains the ZIP.

## 4. Extract into a temporary folder

1. Right-click the ZIP.
2. Click **Extract**.
3. Extract it inside `qpos-product-loading-update`, not into the live application root.
4. Open the extracted folder and confirm these paths exist:
   - `public/js/pos.js`
   - `public/js/pos-live-search.js`
   - `public/js/pos-touch.js`
   - `public/css/pos-touch.css`
   - `resources/views/sale_pos/partials/product_list.blade.php`

## 5. Replace only those files

Copy each extracted file over the live file at the destination in the table above. When File Manager asks to overwrite, confirm overwrite for these five files only.

Do not copy any other file from the ZIP. Do not upload `.env`.

## 6. Make browsers load the new JavaScript

The POS page loads `pos.js` with a version number from `config/constants.php` (`asset_version`).

1. Open `config/constants.php` in File Manager → **Edit**. This file is in the application root, not in `public`.
2. Find the line `'asset_version' =>`.
3. Increase that number by 1. For example, `919` becomes `920`. Save the file.
4. Do not replace the whole `config/constants.php` file. Other lines in the live file must stay as they are.

If `bootstrap/cache/config.php` exists, Laravel is using a cached copy of that number and will ignore the edit until the cache file is removed.

1. Open `bootstrap/cache/`.
2. If `config.php` is there, delete **only** `config.php`.
3. Leave `.env`, `bootstrap/cache/packages.php`, and `bootstrap/cache/services.php` in place.

There is no safe one-click cache button in File Manager. Do not upload a PHP file that clears caches or runs commands.

If the product grid still behaves the old way after a hard refresh:

1. Open `storage/framework/views/`.
2. Delete the compiled `.php` files in that folder. Leave the folder itself.
3. Laravel rebuilds them on the next page load.

## 7. Open the POS page

1. On each register, open `https://mtc.qpos.avenque.io/pos/create`.
2. Hard refresh: **Ctrl+F5** in Chrome or Edge.
3. The product panel should show **Loading products...** and then the product cards.
4. "No Products to display" should appear only when the selected category really has no products.

## 8. Test the first load

1. Open POS in the current tab.
2. Refresh the page.
3. Leave POS, then open it again.
4. Open POS in a second tab.

Each time, products that exist for the location should appear without a second refresh.

## 9. Test categories

1. Click **All Categories**. The full permitted product list should return.
2. Click **attar**, **Cap**, **counter**, **MISWAK**, and **PERFUME**.
3. Click between those categories quickly.
4. Return to **All Categories**.

The cards on screen should match the highlighted category. A category that contains products should not stay on "No Products to display".

## 10. Test search and barcode

1. Type part of a product name in the product search box. The grid should narrow to matches.
2. Clear the box. The current category's products should return.
3. Search by a known SKU.
4. Scan a barcode, or type the barcode and press Enter. The existing scan behaviour should add the item or open the existing quantity step. The grid fix does not replace that flow.

## 11. Test Retry

1. Select a category that has products.
2. If "Could not load products." appears, click **Retry**. The same category should load again without refreshing the whole page.
3. On a category that really has no products, "No Products to display" and **Retry** are expected. Retry runs the same category request again.

## 12. Roll back

1. Copy the five files from `qpos-product-loading-backup` back to their original locations and overwrite.
2. If you changed `asset_version`, set it back to the previous number.
3. Hard refresh the POS page (Ctrl+F5).

No database restore is required for this patch.
