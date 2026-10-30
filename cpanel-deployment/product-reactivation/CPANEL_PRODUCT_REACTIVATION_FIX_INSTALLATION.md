# QPOS product reactivation white-screen fix — cPanel installation

This package is a **minimal patch**. It is not a full QPOS application. It replaces three files that reactivate an inactive product from Products → List Products.

No Terminal or SSH access is required. No database change is required. Do not import a SQL file. Do not run a migration. Do not replace the live `.env` file.

## Files in this folder

| File | Purpose |
|---|---|
| `QPOS-Product-Reactivation-White-Screen-Fix-cPanel.zip` | The three updated application files |
| `CPANEL_PRODUCT_REACTIVATION_FIX_INSTALLATION.md` | This guide |
| `PRODUCT_REACTIVATION_TEST_REPORT.md` | What was tested and what was not |
| `PRODUCT_REACTIVATION_CHANGELOG.md` | What changed and why |

## Files inside the ZIP

Extracting the ZIP must put each file at the path below, relative to the QPOS application root (the folder that contains `artisan`).

| ZIP path | Replace this live file |
|---|---|
| `app/Http/Controllers/ProductController.php` | `app/Http/Controllers/ProductController.php` |
| `resources/views/product/index.blade.php` | `resources/views/product/index.blade.php` |
| `lang/en/lang_v1.php` | `lang/en/lang_v1.php` |

None of these files belong in `public` or `public_html`. If the website address points at `public_html`, that folder is the Laravel `public` folder. The three files live in the application folder **above** `public_html`:

- `app/Http/Controllers/ProductController.php`
- `resources/views/product/index.blade.php`
- `lang/en/lang_v1.php`

## 1. Find the live QPOS folder

1. Log in to cPanel.
2. Open **File Manager**.
3. Find the application root. It contains `artisan`, `app`, `resources`, `lang`, and `public` together.
4. Confirm you are in the right place by opening `app/Http/Controllers/ProductController.php` and checking that it contains `function activate`.
5. The products address is `https://mtc.qpos.avenque.io/products`.

## 2. Back up the three files

In File Manager, create a folder named `qpos-product-reactivation-backup` outside `public` / `public_html`. Copy these files into it and keep the same folder structure:

- `app/Http/Controllers/ProductController.php`
- `resources/views/product/index.blade.php`
- `lang/en/lang_v1.php`

Leave this backup in place until reactivation has been checked.

## 3. Upload the ZIP

1. In File Manager, open a temporary folder such as `qpos-product-reactivation-update`. Create it next to the application if it does not exist. Do not upload the ZIP into `public` or `public_html`.
2. Click **Upload** and choose `QPOS-Product-Reactivation-White-Screen-Fix-cPanel.zip`.
3. After the upload finishes, go back to the folder that contains the ZIP.

## 4. Extract into a temporary folder

1. Right-click the ZIP.
2. Click **Extract**.
3. Extract it inside `qpos-product-reactivation-update`, not into the live application root.
4. Open the extracted folder and confirm these paths exist:
   - `app/Http/Controllers/ProductController.php`
   - `resources/views/product/index.blade.php`
   - `lang/en/lang_v1.php`

## 5. Replace only those files

Copy each extracted file over the live file at the destination in the table above. When File Manager asks to overwrite, confirm overwrite for these three files only.

Do not copy any other file from the ZIP. Do not upload `.env`. Do not delete products, stock, prices, sales, or purchases.

## 6. Refresh the browser

1. Open Products → List Products.
2. Hard-refresh the page (Ctrl+F5, or empty the browser cache for this site).

The Reactivate script is part of the product list page, so a normal refresh after the Blade file is replaced is enough. No Artisan command is required. Laravel recompiles the Blade file when the uploaded file is newer than the compiled view.

If the address bar still goes to `/products/activate/{id}` and the page is still blank after a hard refresh, the old controller is still being served from PHP’s cache. In cPanel, open **Select PHP Version** or **MultiPHP Manager** and restart PHP from that screen. Do not upload a public PHP file to clear caches.

## 7. Test product reactivation

1. Sign in as a user who can edit products.
2. Open Products → List Products.
3. Find an inactive product (it shows an Inactive badge, and Actions includes Reactivate). The screenshot example was product id 21, named Cap.
4. Click Actions → Reactivate.
5. The address bar must stay on `/products`. It must not stay on `/products/activate/21`.
6. A green notification must say **Product reactivated successfully**.
7. The Inactive badge and the Reactivate item must disappear for that product.
8. View, Edit, Delete, and the other Actions items must still be there.
9. Refresh the list. The product must still be active, and no second copy of the product may appear.

Opening `/products/activate/{id}` directly in the address bar must also leave the white page. It must return to the product list and show the same notification.

## 8. Check the database status

No SQL has to be run for the fix to work. To confirm the row in phpMyAdmin:

1. Open **phpMyAdmin**.
2. Select the QPOS database.
3. Open the `products` table, or run:

```sql
SELECT id, name, sku, is_inactive
FROM products
WHERE id = 21;
```

Replace `21` with the product you reactivated.

`is_inactive` must be `0`. The same `id`, name, SKU, prices, and stock row must still be there. Do not run an `UPDATE`, `DELETE`, or `DROP`.

## 9. Roll back

If reactivation misbehaves, copy the three files from `qpos-product-reactivation-backup` back over the live files, then hard-refresh the product list.

No database rollback is required, because this patch does not change the table structure. A product that was successfully reactivated stays active (`is_inactive = 0`) until someone deactivates it again with **Deactivate Selected**.
