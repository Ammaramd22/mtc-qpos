# QPOS invoice layout designer

Install this on the live cPanel account without Terminal or SSH. This is a **minimal file patch**, not a full application. No database change is required.

Appearance and colour / black & white are stored in the existing `invoice_layouts.common_settings` JSON field. Layouts that have never saved the new fields keep their current look.

## 1. Find the live QPOS folder

1. Log in to cPanel.
2. Open **File Manager**.
3. Open the directory that contains `artisan`, `app`, `routes`, `resources`, and `public`. That directory is the QPOS root.
4. Confirm you can see `app/Http/Controllers/InvoiceLayoutController.php`.

Do not upload the zip into `public` only. The files must land in the QPOS root so `app`, `routes`, `resources`, `lang`, and `public` line up with the folders already there.

## 2. Back up the files you will replace

In File Manager, select these existing files and click **Compress**. Download that backup before you replace anything.

- `app/Http/Controllers/InvoiceLayoutController.php`
- `app/Http/Controllers/SellPosController.php`
- `app/Utils/TransactionUtil.php`
- `app/Utils/InvoicePrintAppearance.php` (present only if the earlier preview patch was installed)
- `app/Utils/InvoiceLayoutPreview.php` (same)
- `app/Utils/PreviewReceiptDetails.php` (same)
- `routes/web.php`
- `resources/views/invoice_layout/edit.blade.php`
- `resources/views/invoice_layout/create.blade.php`
- `resources/views/sale_pos/receipts/classic_a4.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm_mobile.blade.php`
- `lang/en/invoice.php`
- `public/css/invoice-layout-preview.css` (if it exists)
- `public/js/invoice-layout-preview.js` (if it exists)

`resources/views/invoice_layout/partials/designer_fields.blade.php` is new.

## 3. Database

No phpMyAdmin change is required. Do not run a migration. Do not edit invoice or sales rows.

Optional safety copy: in phpMyAdmin, export the `invoice_layouts` table as SQL. You do not need to import anything afterwards.

## 4. Upload the zip

1. In File Manager, go to the QPOS root.
2. Click **Upload**.
3. Upload `QPOS-Complete-Invoice-Layout-Designer-cPanel.zip`.
4. The zip should sit next to `artisan`.

## 5. Extract

1. Right-click the zip.
2. Click **Extract**.
3. Extract into the QPOS root, not into a new subfolder.
4. If cPanel asks whether to overwrite files, choose **Overwrite**.

## 6. Check the paths

After extract, these paths must exist:

- `app/Utils/InvoicePrintAppearance.php`
- `resources/views/invoice_layout/partials/designer_fields.blade.php`
- `resources/views/invoice_layout/partials/live_preview.blade.php`
- `public/js/invoice-layout-preview.js`
- `public/css/invoice-layout-preview.css`

Open `routes/web.php` and confirm it contains `invoice-layouts/live-preview`.

If `bootstrap/cache/routes-v7.php` or `bootstrap/cache/routes.php` exists, delete that cache file in File Manager. Do not delete `bootstrap/cache/services.php` or `packages.php`.

Delete the zip from the server after the files are in place. Do not leave the zip inside `public`.

## 7. What not to replace

Do not upload or replace `.env`, `storage/`, `vendor/`, the database, or any file that is not in this patch.

## 8. Refresh caches

Laravel recompiles a Blade file when the uploaded file is newer than the cached view. A normal reload is enough for the PHP and Blade changes.

Hard-refresh the layout page (Ctrl+F5) after upload. The preview controls are part of the editor page. Colour should turn blue when clicked, and the zoom label should change when − or + is clicked.

If the host has **Restart PHP** or **Flush OPcache** in cPanel, use that once if the page still looks like the old editor. Do not upload a public PHP file that runs Artisan.

## 9. Open the layout editor

1. Log in as a user who can edit invoice settings.
2. Open **Invoice Settings** and edit the layout (`/invoice-layouts/1/edit`).
3. The sample invoice should show the business heading, items, totals, and footer, not only the first few lines.
4. **Invoice appearance** is with the design fields: font, sizes, colours, spacing, and presets.

## 10. Test the designer

The preview uses sample lines. It does not create a sale.

1. Change the invoice heading. The preview heading should change without a full page reload.
2. Change the design between Classic 80mm and Classic A4. The paper shape should change.
3. Switch the sample between unpaid, paid, and quotation.
4. Tick **Use these colours**, pick Classic teal, and confirm the preview colours change. Switch to **Black & White** and confirm the preview goes monochrome.
5. Set a heading size, then clear it. Blank means the template size.
6. Click the footer in the preview. The page should scroll to the footer field.
7. Click **Expand preview**, then **Show settings**.
8. Click **Save layout**. Reopen the layout. The choices should still be selected.
9. Click **Restore appearance defaults**, then save if you want those defaults stored.

## 11. Test printing

1. Print a real invoice from the POS or the invoice screen with **Use these colours** off. It should match the previous design.
2. Turn custom colours on, save, and print again. Headings and table bars should use the colours you saved.
3. Set **Black & White**, save, and print. Text should be dark and coloured bars light gray. Amounts must be the real sale amounts. The editor preview is sample data only.

## 12. Roll back

1. Delete `resources/views/invoice_layout/partials/designer_fields.blade.php` and the other new preview files if you are returning to a copy from before any preview patch.
2. Upload the backup copies of the replaced files into the same paths.
3. Hard-refresh the browser.
4. No database rollback is required. A saved `designer` value is ignored by the old code.
