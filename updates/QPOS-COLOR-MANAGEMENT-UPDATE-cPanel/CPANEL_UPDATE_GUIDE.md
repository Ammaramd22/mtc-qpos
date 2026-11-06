# Update Color Management on cPanel

No Terminal and no SSH. Use cPanel File Manager and phpMyAdmin only.

Do not replace `.env`. Do not upload `vendor` or `node_modules`. Do not delete products, sales, stock, sizes, colors, or payments.

## Why the last extract failed

The previous zip put application files under a folder named `files/`. Your site already has `/home/mtcqpos/public_html/files`, so cPanel tried to write into that folder and hit **Permission denied**.

This zip is flat. Paths match the live app (`app/`, `resources/`, `js/`, `routes/`, `database/`). Extract directly into `public_html`.

## 1. Clean up the failed extract

In File Manager open `/home/mtcqpos/public_html`:

1. Delete the zip extract leftovers if present under `files/` that were added today (`files/app`, `files/CHANGELOG.md`, etc.). Do **not** delete your real `app` folder.
2. You may delete `QPOS-COLOR-MANAGEMENT-UPDATE-cPanel.zip` after you upload the new one, or overwrite it.

## 2. Database first

1. phpMyAdmin → select the live QPOS database → **Export** backup.
2. **SQL** tab → paste all of `database/cpanel_color_catalog.sql` from this package → **Go**.

See `DATABASE_UPDATE_GUIDE.md`.

## 3. Upload and extract

1. Upload the new `QPOS-COLOR-MANAGEMENT-UPDATE-cPanel.zip` into `/home/mtcqpos/public_html`.
2. Select the zip → **Extract** → extract into `/home/mtcqpos/public_html` (overwrite when asked).
3. Confirm these paths exist after extract (examples):
   - `app/Color.php`
   - `app/Http/Controllers/ColorController.php`
   - `resources/views/color/manage.blade.php`
   - `js/product-size-color.js`
   - `routes/web.php`

If any path still fails with Permission denied, open that folder (for example `app/Http/Controllers`), set permissions to **0755** for folders and **0644** for files, then extract again — or upload that one file with **Upload** into the correct folder.

## 4. Clear compiled views

Open `storage/framework/views` and delete the files inside. Keep the folder.

Do not delete `.env`.

## 5. Browser

Hard-refresh (Ctrl+F5). Check Products → **Colors**, then Add Product colour select + blue +.

## 6. Smoke checks

1. Colors page opens.
2. Add Red, White, Black, Blue.
3. Add Product → Color is searchable multi-select with +.
4. Save product with multiple colours; edit and confirm they remain.
5. Print Labels with Color enabled still works.
