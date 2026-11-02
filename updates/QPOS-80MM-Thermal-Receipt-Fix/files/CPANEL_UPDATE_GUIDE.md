# Update 80mm thermal receipt printing on cPanel

No Terminal and no SSH. Use cPanel File Manager only.

Do not replace `.env`. Do not upload `vendor` or `node_modules`. Do not delete sales, stock, customers, or invoice layouts.

This update does not need a database change. Do not run SQL in phpMyAdmin for this fix.

## 1. Back up

1. In cPanel File Manager, compress the current application folder and download that archive.
2. In phpMyAdmin, export the database.

## 2. Upload

Extract `QPOS-80MM-Thermal-Receipt-Fix-cPanel.zip` into the Laravel project root (the folder that already contains `app/`, `resources/`, `public/`, and `config/`).

Overwrite only these paths when prompted:

- `app/Utils/InvoicePrintAppearance.php`
- `config/constants.php` (only needed to bump `asset_version` to `921` for CSS/JS cache bust — if you customized this file on the server, edit that one line instead of overwriting the whole file)
- `public/js/functions.js`
- `public/css/print-preview.css`
- `public/css/pos-theme.css`
- `resources/views/sale_pos/receipts/classic_80mm.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm_mobile.blade.php`

Leave every other file as it is, including `.env`.

`CHANGELOG.md`, `CPANEL_UPDATE_GUIDE.md`, and `TEST_REPORT.md` are instructions. They do not need to stay on the server.

## 3. Clear compiled views

Open `storage/framework/views` and delete the files inside that folder. Keep the folder.

If a cache clearer URL exists on the site (for example `/cpanel_clear_cache.php`), you may open it once after upload. Do not delete `.env`.

## 4. Browser refresh

Cashiers who already have POS open should press Ctrl+F5 (or clear cache) so the browser loads:

- `functions.js` with the new asset version
- `print-preview.css` / `pos-theme.css` with the new asset version

Confirm Invoice Layouts → the location’s POS layout still uses **Classic 80mm** (or Classic 80mm Mobile) if that is the intended thermal design.

## 5. Database

Skip phpMyAdmin. This fix does not add or change tables.

## 6. Check printing

1. Complete a cash sale on POS and open the print preview.
2. Confirm labels (address, Invoice No, Subtotal, customer) are solid black, not grey.
3. Confirm Total is shown once, then the Payments box lists Cash (or card) with the amount — no faded duplicate Paid line under Total.
4. Print to the 80mm thermal printer. Text should be readable; nothing should overlap.
5. Reprint an older invoice with a long product name and with size/colour.
6. Repeat once in Light Theme and once in Dark Theme — the paper print must stay black on white either way.
7. Open Invoice Layout edit and confirm the live preview for Classic 80mm still matches the print design.
