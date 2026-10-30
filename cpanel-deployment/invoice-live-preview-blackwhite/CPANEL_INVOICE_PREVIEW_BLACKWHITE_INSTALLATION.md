# QPOS invoice live preview and black & white printing

Install this on the live cPanel account without Terminal or SSH. This is a **minimal file patch**, not a full application. No database change is required.

The setting is stored in the existing `invoice_layouts.common_settings` JSON field as `print_appearance` (`colour` or `black_white`). Layouts that do not have the key yet keep printing in colour.

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
- `routes/web.php`
- `resources/views/invoice_layout/edit.blade.php`
- `resources/views/invoice_layout/create.blade.php`
- `resources/views/sale_pos/receipts/classic_a4.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm.blade.php`
- `resources/views/sale_pos/receipts/classic_80mm_mobile.blade.php`
- `lang/en/invoice.php`

These files are new and do not need a backup:

- `app/Utils/InvoicePrintAppearance.php`
- `app/Utils/InvoiceLayoutPreview.php`
- `app/Utils/PreviewReceiptDetails.php`
- `resources/views/invoice_layout/partials/live_preview.blade.php`
- `resources/views/invoice_layout/partials/print_appearance_field.blade.php`
- `resources/views/invoice_layout/partials/preview_document.blade.php`
- `public/css/invoice-layout-preview.css`
- `public/js/invoice-layout-preview.js`

## 3. Database

No phpMyAdmin change is required. Do not run a migration. Do not edit invoice or sales rows.

Existing layouts stay on **Colour** until someone opens the layout, chooses **Black & White**, and clicks **Update**.

Optional safety copy, only if you want one: in phpMyAdmin, export the `invoice_layouts` table as SQL. You do not need to import anything afterwards.

## 4. Upload the zip

1. In File Manager, go to the QPOS root (the folder from step 1).
2. Click **Upload**.
3. Upload `QPOS-Invoice-Live-Preview-BlackWhite-Update-cPanel.zip`.
4. After the upload finishes, go back to the QPOS root. The zip should sit next to `artisan`.

## 5. Extract

1. Right-click the zip.
2. Click **Extract**.
3. Extract into the QPOS root, not into a new subfolder.
4. If cPanel asks whether to overwrite files, choose **Overwrite**.

## 6. Check the paths

After extract, these paths must exist inside the QPOS root:

- `app/Utils/InvoicePrintAppearance.php`
- `app/Utils/InvoiceLayoutPreview.php`
- `app/Utils/PreviewReceiptDetails.php`
- `public/css/invoice-layout-preview.css`
- `public/js/invoice-layout-preview.js`
- `resources/views/invoice_layout/partials/live_preview.blade.php`

Open `routes/web.php` in the editor and confirm it contains:

`invoice-layouts/live-preview`

Delete the zip from the server after the files are in place. Do not leave the zip inside `public`.

## 7. What not to replace

Do not upload or replace:

- `.env`
- `storage/`
- `vendor/`
- the database
- any file that is not in this patch

## 8. Refresh caches

Laravel recompiles a Blade file when the uploaded file is newer than the cached view. A normal reload is enough for the PHP and Blade changes.

The preview stylesheet and script are loaded with a version query (`?v=20261030b`), so the browser fetches them on the next visit to the layout page.

If `bootstrap/cache/routes-v7.php` or `bootstrap/cache/routes.php` is present, delete that cache file in File Manager. The preview route is then read from `routes/web.php`. Do not delete `bootstrap/cache/services.php` or `packages.php`.

If the edit page still looks like the old form:

1. Hard-refresh the browser (Ctrl+F5).
2. If the host has **Restart PHP** or **Flush OPcache** in cPanel (MultiPHP Manager, Select PHP Version, or a cache tool), use that once.
3. Do not upload a public PHP file that runs Artisan.

## 9. Open the layout editor

1. Log in to QPOS as a user who can edit invoice settings.
2. Open **Invoice Settings** and edit the layout (the address looks like `/invoice-layouts/1/edit`).
3. You should see **Live invoice preview** beside the form on a wide screen, and above the form on a phone. The sample invoice should already be visible under the zoom buttons. A black empty area means the browser is still using the previous script: hard-refresh (Ctrl+F5).
4. You should see **Invoice print appearance** with **Colour** and **Black & White**.

## 10. Test the live preview

The preview uses sample lines. It does not create a sale.

1. Change the invoice heading. The preview heading should change without a full page reload.
2. Change the design (for example Classic A4 and Classic 80mm). The paper shape should change.
3. Switch the sample document between unpaid, paid, and quotation. Paid sample should not show a due date. Quotation should use the quotation heading.
4. Turn **Show due date** on and off for an unpaid sample.
5. Choose **Black & White**, then **Colour**. The sample should switch with the setting.
6. Click **Fit**, then zoom in and out. That only scales the preview on screen. It does not change the printed size.
7. Click **Update**. Reopen the layout. The appearance you saved should still be selected.

## 11. Test printing

1. Leave one layout on **Colour** and print a real invoice from the POS or the invoice screen. Colours should match the previous design.
2. Set a layout to **Black & White**, save, and print another invoice with that layout. Headings and totals should be black or dark gray. Coloured bars should be light gray with dark text.
3. Use the browser print preview for both an 80mm receipt and an A4 invoice if those designs are in use.
4. Compare that print preview with the live preview. Amounts must be the real sale amounts on the printed invoice. The editor preview is sample data only.

## 12. Roll back

1. Delete the new files listed in step 2 as “new”.
2. Upload the backup copies of the replaced files into the same paths.
3. Hard-refresh the browser.
4. No database rollback is required. A saved `print_appearance` value is ignored by the old code.

If a layout was saved as Black & White and you later reinstall this patch, that choice is still in `common_settings` and will apply again.
