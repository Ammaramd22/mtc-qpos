# QPOS barcode label fix — cPanel File Manager

This is a **small patch** for the browser barcode labels. It is not a full copy of QPOS. It does not change the database. You do not need cPanel Terminal or SSH.

The live site in this project is the folder that already contains `artisan` and `app`. On the Marjan Thobe Center account that folder is usually:

`/home/mtcqpos/public_html`

If your File Manager opens somewhere else, use the folder that contains **all** of these:

- `artisan`
- `app`
- `resources/views/labels/browser_print.blade.php`

Do not upload this ZIP into `public` only, and do not extract it on top of the live site until you have copied the current files out.

## 1. Find the QPOS folder

1. Log in to cPanel.
2. Open **Files → File Manager**.
3. Open `public_html`. If you do not see `artisan` there, go up one level and open the folder that does contain `artisan`.
4. Confirm this file exists:

   `resources/views/labels/browser_print.blade.php`

That is the label page this update replaces.

## 2. Back up the five files you will replace

Still in File Manager, select each file below, click **Copy**, and copy it into a new folder such as `label-fix-backup` in your home directory (not inside `public_html` if you can avoid it). If Copy is not available, select the file, click **Download**, and keep the download on your computer.

| File in the QPOS folder | Why it is in the backup |
| --- | --- |
| `resources/views/labels/browser_print.blade.php` | Label layout and print rules |
| `resources/views/business/partials/settings_bartender.blade.php` | Business Settings label form |
| `app/Http/Controllers/LabelsController.php` | Label page and saved layout |
| `app/Http/Controllers/BusinessController.php` | Saving Business Settings |
| `lang/en/lang_v1.php` | On-screen wording for the rotation control |

Rename the downloaded copies so you can tell them apart, for example `browser_print.blade.php.bak`.

## 3. Database backup (optional)

This fix does not change tables, columns, or product data. A database export is only a safety copy.

1. cPanel → **phpMyAdmin**.
2. Click the QPOS database (the name is in the site’s `.env` as `DB_DATABASE`).
3. Click **Export → Quick → SQL → Go**.
4. Save the `.sql` file with the file backup. Do not import it unless you are restoring the whole database.

## 4. Upload the ZIP

1. In File Manager, open your **home** directory (the folder above `public_html`), not the live QPOS folder.
2. Click **+ Folder** and create `qpos-label-fix-upload`.
3. Open that folder.
4. Click **Upload** and choose `QPOS-Barcode-Label-Fix-cPanel.zip`.
5. Wait until the upload reaches 100%. If cPanel rejects the file, the account disk quota is full. Delete old ZIPs from this folder and try again. Do not delete the live site to free space.

## 5. Extract into the temporary folder

1. Go back to `qpos-label-fix-upload`.
2. Select the ZIP.
3. Click **Extract**.
4. Set the extract path to the same folder, `qpos-label-fix-upload`.
5. Click **Extract File(s)**.

You should then see:

- `CPANEL_BARCODE_LABEL_FIX_INSTALLATION.md`
- `app/Http/Controllers/LabelsController.php`
- `app/Http/Controllers/BusinessController.php`
- `lang/en/lang_v1.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/business/partials/settings_bartender.blade.php`

If Extract offers “overwrite”, that only affects this temporary folder. It must not point at `public_html`.

## 6. Where each file goes

Copy from the temporary folder into the live QPOS folder. The path after the QPOS folder name must match exactly.

| From `qpos-label-fix-upload` | To the live QPOS folder |
| --- | --- |
| `resources/views/labels/browser_print.blade.php` | `resources/views/labels/browser_print.blade.php` |
| `resources/views/business/partials/settings_bartender.blade.php` | `resources/views/business/partials/settings_bartender.blade.php` |
| `app/Http/Controllers/LabelsController.php` | `app/Http/Controllers/LabelsController.php` |
| `app/Http/Controllers/BusinessController.php` | `app/Http/Controllers/BusinessController.php` |
| `lang/en/lang_v1.php` | `lang/en/lang_v1.php` |

In File Manager: open the file in the temporary folder, **Copy**, set the destination to the matching live folder, and choose **Overwrite** only for that one file.

Do not copy `vendor`, `.env`, or any other folder from this package. They are not in the ZIP.

## 7. Confirm the new files are in place

Open the live file `resources/views/labels/browser_print.blade.php` with **Edit** or **View**. Near the top of the sticker styles you should see the words `label-inner`. The **Print rotation** control is in that same file.

Check the **Last Modified** time. It should be the time you copied the file, not an older date.

File permissions can stay as they are (usually `644` for these PHP and Blade files). You do not need to change them. The web server must still be able to write `storage/framework/views`, which it already does on the live site. Laravel rebuilds the label page from the new Blade file the next time someone opens it, because the new file is newer than the old compiled copy. You do not run a command for that.

There is no cache-clear URL in this update. Do not add one.

If the label page still looks like the old page after a hard refresh, the host may be keeping an old copy of the PHP files. In cPanel, open **MultiPHP Manager** or **Select PHP Version** and use **Restart** if that button is there. Not every host shows it. Waiting a minute and loading the label page again is enough on most accounts.

## 8. Refresh the browser

On the shop PC:

1. Close any label print tab that was already open.
2. In QPOS, open **Print Labels** again.
3. If the toolbar does not show **Print rotation**, press `Ctrl+F5` on that page.

The preview on screen stays upright. **Print rotation** defaults to **90° — fix sideways labels**. That is the setting for the roll in the photo. Click **Save layout** once so every device uses it.

## 9. Roll back

If labels are worse after the update:

1. Copy the five backup files from step 2 back to the same paths.
2. Overwrite the new files.
3. Close the label tab and open Print Labels again with `Ctrl+F5`.

Do not import the SQL backup unless the database itself was damaged. This update does not write to the database until someone clicks **Save layout** or saves Business Settings. That save only stores label width, height, fonts, and rotation. It does not change products, stock, or barcode numbers.

## 10. Print a test label

1. Add one product and set the quantity to 2.
2. Click **Print Labels (Browser)**.
3. Check the preview: business name, product name, barcode, SKU, cost code, and price sit inside the dashed sticker. The price is not cut off.
4. Click **Print**.
5. In the browser print dialog:
   - **Margins:** None
   - **Scale:** 100 (not “Fit to page”)
   - **Headers and footers:** Off
   - **Background graphics:** On, if the dialog has that tick
   - Leave the paper size that the page already selected. With rotation **90°** that size is the sticker height by the sticker width (25 mm × 38 mm when the sticker is 38 mm × 25 mm). Do not switch the paper to A4 or Letter.
6. Print two stickers.

What you want on the roll:

- Text reads left to right across the sticker.
- Barcode bars run from top to bottom, so a scanner can read across the sticker.
- The name, barcode, SKU, and price are on the same sticker, not across the gap.
- The second sticker matches the first.

If the text is still sideways, change **Print rotation** to **270°**, click **Save layout**, and print one sticker. If it is sideways the other way, or upright but split across two stickers, set **Print rotation** to **0°**, save, and in the print dialog set orientation to **Landscape** and the paper size to the sticker width and height.

Width is across the roll. Height is the sticker face along the feed. Do not add the gap between stickers to the height. The printer’s gap sensor should use that gap. The size already saved for this shop is 38 mm × 25 mm unless someone changed it in Business Settings or on the label page.

No scanner test is included in this package. After a sticker looks right, scan it at the POS to confirm the SKU.

## What this update does not do

- It does not change receipts, kitchen tickets, sales, stock, or product records.
- It does not upgrade Laravel or PHP.
- It does not need a database migration.
