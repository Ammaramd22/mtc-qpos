# Update Print Labels search on cPanel

No Terminal and no SSH. Use cPanel File Manager only.

Do not replace `.env`. Do not upload `vendor` or `node_modules`. Do not delete products, sales, stock, sizes, colors, or payments.

This package does not include a database change. Do not import a SQL file for this update.

## 1. Backup

In File Manager, open the live site folder (the folder that contains `app` and `resources`).

1. Select `app`, `resources`, `lang`, and `public` (or `js` if your site serves `js` from the site root).
2. Compress those folders to a backup zip, or download copies of the files listed in section 3 before you overwrite them.

## 2. Upload and extract

The zip is flat. Paths match the live app. Extract it into the folder that already contains `app`, not into a new subfolder.

1. Upload `QPOS-PRINT-LABEL-SEARCH-FIX-cPanel.zip` into that folder.
2. Select the zip → **Extract**.
3. When asked, overwrite the existing files.

Confirm these files exist after extract:

- `app/Http/Controllers/LabelsController.php`
- `app/Utils/LabelProductSearch.php`
- `app/Utils/BrowserLabelLayout.php`
- `resources/views/labels/show.blade.php`
- `lang/en/lang_v1.php`
- `public/js/labels.js`
- `public/js/lang/en.js`

This package also includes `js/labels.js` and `js/lang/en.js`. Those copies are for a site whose document root is the application folder and whose browser loads `/js/labels.js` from that root. If your site already uses `public/js`, both copies are the same file and the extra `js` folder is harmless.

If extract reports Permission denied, set the target folder to `755` and the file to `644`, then upload that single file into the matching folder.

## 3. Clear compiled views

Open `storage/framework/views` and delete the files inside it. Keep the folder.

Do not delete `.env`.

Do not delete `storage/framework/sessions` or `storage/logs`.

## 4. Browser

Hard-refresh the Print Labels page (Ctrl+F5).

The page script address should contain `labelsearch2`. If the old page is still showing, clear the browser cache for the site and refresh again.

## 5. Checks

1. Open **Products → Print Labels**.
2. Search a full product name, then a few letters of that name.
3. Search a SKU and a barcode.
4. Search a size that is assigned to a product, such as `54`. The product should appear. A different size such as `154` should not appear only because it contains those digits.
5. Search a color assigned to a product, such as `White`.
6. Click a suggestion. The product is added only after that click.
7. If the product has several sizes or colors, pick the combination, set the number of labels, and open **Print Labels (Browser)**.
8. The preview should show the product, the chosen size, and the chosen color. Print one test label.

See `TEST_REPORT.md` for the checks already run in development, and `DATABASE_UPDATE_GUIDE.md` for the optional read-only queries.
