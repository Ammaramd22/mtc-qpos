# QPOS barcode label update — cPanel File Manager

This is a **minimal patch** for an existing live QPOS installation. It is not a full application. There is **no database migration** and **no Terminal or SSH step**.

The package changes only browser barcode-label printing and the Business Settings fields that store that layout. Sales, receipts, KOT/BOT, stock, and products are untouched.

## 1. Find the live QPOS folder

In cPanel, open **File Manager**.

The live site is the folder that contains `artisan`, `app`, and `resources`. On this hosting account that is usually:

`/home/mtcqpos/public_html`

Confirm you can see `app/Http/Controllers/LabelsController.php` and `resources/views/labels/browser_print.blade.php` before you upload anything. If those files are in a subfolder, that subfolder is the destination. Do not upload into `public` alone.

## 2. Back up the files you will replace

In File Manager, download a copy of each of these files and keep them on your computer:

- `app/Http/Controllers/LabelsController.php`
- `app/Http/Controllers/BusinessController.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/business/partials/settings_bartender.blade.php`
- `lang/en/lang_v1.php`

`app/Utils/BrowserLabelLayout.php` is new. There is nothing to back up for that file.

## 3. Back up the database

Open **phpMyAdmin** from cPanel.

1. Select the QPOS database.
2. Click **Export**.
3. Choose **Quick** and **SQL**.
4. Click **Go** and store the `.sql` file with the file backups.

This update does not change tables. The backup is so you can restore the `business` row if a saved label layout needs to be undone. Do not import that file unless you intend to roll back. A full import replaces sales and stock made after the export.

## 4. Upload and extract away from the live site

1. In File Manager, go to a temporary folder such as `/home/mtcqpos` (the home directory), **not** inside `public_html`.
2. Upload `QPOS-Advanced-Barcode-Label-Fix-cPanel.zip`.
3. Select the ZIP and click **Extract**.
4. Open the extracted folder and confirm these paths exist inside it:

```
app/Utils/BrowserLabelLayout.php
app/Http/Controllers/LabelsController.php
app/Http/Controllers/BusinessController.php
resources/views/labels/browser_print.blade.php
resources/views/business/partials/settings_bartender.blade.php
lang/en/lang_v1.php
```

Do not extract the ZIP directly over `public_html`. Copy the files one path at a time so nothing else is overwritten.

## 5. Copy each file to the matching live path

From the extracted folder, copy each file onto the same path under the live QPOS folder. Overwrite only these files:

| Extracted file | Live destination |
| --- | --- |
| `app/Utils/BrowserLabelLayout.php` | `public_html/app/Utils/BrowserLabelLayout.php` |
| `app/Http/Controllers/LabelsController.php` | `public_html/app/Http/Controllers/LabelsController.php` |
| `app/Http/Controllers/BusinessController.php` | `public_html/app/Http/Controllers/BusinessController.php` |
| `resources/views/labels/browser_print.blade.php` | `public_html/resources/views/labels/browser_print.blade.php` |
| `resources/views/business/partials/settings_bartender.blade.php` | `public_html/resources/views/business/partials/settings_bartender.blade.php` |
| `lang/en/lang_v1.php` | `public_html/lang/en/lang_v1.php` |

Create `app/Utils` if File Manager says the folder is missing. Do not replace `.env`, `vendor`, `storage`, or the database.

## 6. Database

No migration is required. Do not create a PHP file to run Artisan. Do not click anything that imports a SQL file. New settings are saved later into the existing `business.common_settings` column when an authorized user clicks **Save layout** or saves Business Settings.

## 7. Clear a stale compiled view if the old label screen is still shown

Laravel rebuilds a Blade file when the uploaded file is newer than the compiled copy. After the upload:

1. In the browser, hard-refresh the label page (`Ctrl+F5`).
2. If the print screen still has no Stock, offset, or profile controls, open File Manager at `storage/framework/views` and delete the compiled `.php` files in that folder only. Leave the `.gitignore` file. QPOS recreates the compiled views on the next page load.
3. If PHP still serves old code after several minutes, use cPanel **MultiPHP Manager** or **Select PHP Version** and restart PHP for the domain. Do not upload a public script that runs Artisan or clears caches.

## 8. Open the label screen and set the sticker size

1. Log in to QPOS.
2. Open **Print Labels**, choose a product, and use **Browser print**.
3. On the print screen set:

- **Width** = sticker width across the roll. The current stock default is **38 mm**.
- **Height** = the sticker face along the feed, **not** including the gap. The current stock default is **25 mm**.
- **Print rotation** = **0° — one sticker per page**.
- **Stock** = **Continuous roll**.

4. Click **Save layout**.

If an older save still shows **90°**, change it to **0°** and save again. Leaving 90° makes the page 38 mm long, and the gap between stickers cuts through the name and price.

Chrome or Edge print dialog, every time:

- Margins: **None**
- Scale: **100%** (not “Fit to page”)
- Headers and footers: **off**
- Paper size: leave the custom size from the page. Do not switch it to A4.

## 9. Preview, test print, and calibrate

1. Check the dashed preview. The shop name, product, barcode, SKU, and price should sit inside one dashed rectangle.
2. Click **Print** and print **one** sticker.
3. If the whole design is shifted but still on one sticker, change **Shift left/right** or **Shift up/down** by 0.5 mm and print one sticker again. Offsets only move the design. They do not change the page size.
4. If the gap still cuts through the design, the height is longer than the sticker face or rotation is not 0°. Set rotation to 0° and set Height to the measured sticker face.
5. If the text is sideways, the printer is turning the page. Try **90°**, save, and print one sticker. Use **270°** only if 90° is sideways the other way.
6. When one sticker is correct, print a short batch (two or three). Each sticker should match the first. Then print the real batch.

A browser preview does not prove the physical printer is aligned. Confirm with the single test sticker before a large batch.

## 10. Check that a barcode scans

Scan the test sticker with the shop scanner. The value must match the product SKU already stored in QPOS. This package does not change stored barcodes.

Formats offered are Code 128, Code 39, EAN-13, EAN-8, UPC-A, and UPC-E. **Use the product barcode type** keeps the product’s own type and falls back to Code 128 when the SKU cannot be encoded in the chosen type (for example a short code with EAN-13).

## 11. Business Settings

**Settings → Business Settings → Barcode / label** stores the same layout for the business: size, stock, rotation, offsets, columns, gaps, padding, font, alignment, barcode format, and which fields to show. Save Business Settings after changing them. Named profiles are created on the print screen, not in this form. Profiles stay on that business only.

## 12. Roll back

1. Put the six backed-up files back in the same paths. Delete `app/Utils/BrowserLabelLayout.php` if you are returning to a copy that does not have that class.
2. Hard-refresh the browser.
3. If compiled views still look new, delete the `.php` files in `storage/framework/views` again.
4. Restore the database export only if you must undo settings saved after the backup. Importing the full SQL file also restores sales and stock to the backup time. If you only need the old label settings, restore the `common_settings` value of that business row in phpMyAdmin and leave every other table alone.

No public cleanup script is part of this update. Do not add one.
