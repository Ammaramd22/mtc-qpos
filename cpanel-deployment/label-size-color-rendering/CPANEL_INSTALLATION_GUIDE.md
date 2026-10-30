# QPOS — Size and Color on printed labels

This is a **minimal patch** for an existing live QPOS installation. It is not a full application. There is **no database change** and **no Terminal or SSH step**.

The package makes product Size and Color appear on the browser label preview and on the printed sticker when those Show on label boxes are ticked. Sales, stock, prices, invoices, product create, and product edit are not changed.

## 1. Find the live QPOS folder

In cPanel, open **File Manager**.

The live site is the folder that contains `artisan`, `app`, and `resources`. On this hosting account that is usually:

`/home/mtcqpos/public_html`

Confirm you can see `app/Http/Controllers/LabelsController.php` and `resources/views/labels/browser_print.blade.php` before you upload anything. If those files are in a subfolder, that subfolder is the destination. Do not upload into `public` alone.

## 2. Back up the files you will replace

In File Manager, download a copy of each of these files and keep them on your computer:

- `app/Http/Controllers/LabelsController.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/labels/partials/browser_sticker.blade.php`

## 3. Back up the database

Open **phpMyAdmin** from cPanel.

1. Select the QPOS database.
2. Click **Export**.
3. Choose **Quick** and **SQL**.
4. Click **Go** and store the `.sql` file with the file backups.

This update does not change tables, columns, or product rows. The backup is only so you can restore `business.common_settings` if a saved label layout needs to be undone. Do not import that file unless you intend to roll the whole database back. A full import replaces sales and stock made after the export.

## 4. Upload and extract away from the live site

1. In File Manager, go to a temporary folder such as `/home/mtcqpos` (the home directory), **not** inside `public_html`.
2. Upload `QPOS-Label-Size-Color-Rendering-Fix-cPanel.zip`.
3. Select the ZIP and click **Extract**.
4. Open the extracted folder and confirm these paths exist inside it:

```
app/Http/Controllers/LabelsController.php
resources/views/labels/browser_print.blade.php
resources/views/labels/partials/browser_sticker.blade.php
```

Do not extract the ZIP directly over `public_html`. Copy the files one path at a time so nothing else is overwritten.

## 5. Copy each file to the matching live path

From the extracted folder, copy each file onto the same path under the live QPOS folder. Overwrite only these files:

| Extracted file | Live destination |
| --- | --- |
| `app/Http/Controllers/LabelsController.php` | `public_html/app/Http/Controllers/LabelsController.php` |
| `resources/views/labels/browser_print.blade.php` | `public_html/resources/views/labels/browser_print.blade.php` |
| `resources/views/labels/partials/browser_sticker.blade.php` | `public_html/resources/views/labels/partials/browser_sticker.blade.php` |

Do not replace `.env`, `vendor`, `storage`, or the database.

## 6. Database

No phpMyAdmin change is required. Do not run a SQL file. Do not create a PHP file to run Artisan.

Size and Color are read from the columns that already exist:

- `products.size`
- `products.color`

If those are empty, the label uses the selected variation when its template is Size or Color (or Colour). A dummy single-product variation named `DUMMY` is not treated as a size or a color. Hardware `hw_size` and `hw_colour` are used only when the dedicated value is still empty.

## 7. Compiled views and the browser

This patch does not change `routes/web.php`. Leave `bootstrap/cache` alone unless a route-cache file is already a problem from an earlier update.

1. Open `storage/framework/views` and delete the compiled `.php` files in that folder only. Leave the `.gitignore` file. QPOS recreates the compiled views on the next page load.
2. Hard-refresh the browser print page (`Ctrl+F5`).
3. If PHP still serves the old label page after several minutes, use cPanel **MultiPHP Manager** or **Select PHP Version** and restart PHP for the domain. Do not upload a public script that runs Artisan or clears caches.

## 8. Test the live preview

1. Log in to QPOS.
2. Open **Products → Print Labels** and print a product that has both Size and Color. Use the existing **50 × 25 mm** sticker.
3. In **Show on label**, tick **Size** and **Color**. The dashed preview should show `Size: …` and `Color: …` under the item name, above the SKU.
4. Untick **Size**. Only Color remains. Untick **Color** as well. The line disappears and does not leave a gap.
5. Tick **Size** only, then **Color** only. The preview changes immediately, without clicking Apply.
6. Repeat with a product that has only Size, only Color, and neither. An empty value does not print a blank `Size:` or `Color:` line.
7. Print two quantities of one product and one other product. Each sticker keeps its own values.
8. Change width, height, and rotation. The values stay on the sticker and do not cover the name, barcode, SKU, cost code, or price.

## 9. Test printing

A physical printer was not part of this package test. On the shop computer:

1. Click **Print** with Size and Color both ticked, then with only one ticked, then with both unticked.
2. Compare the print dialog preview with the dashed preview. They use the same sticker markup. The toolbar is hidden when printing; the batch stickers are what print.
3. Chrome or Edge print dialog: margins **None**, scale **100%**, headers and footers **off**. For the 50 × 25 mm roll, orientation **Landscape**.

Do not print a large batch until one sticker matches the preview.

## 10. Test saved settings

1. Tick Size and Color, click **Save layout**, close the window, and open browser print again. Both boxes should still be ticked, and the values should still be on the sticker.
2. Save a profile named for example `Size only` with Size ticked and Color unticked. Save another with both unticked.
3. Switch profiles. The preview follows the profile.
4. Click **Restore defaults**. Size and Color turn off. Older profiles that were saved before these boxes existed also leave them off and keep the sticker width they already had.

## 11. Roll back

1. Put the three backed-up files back in the same paths.
2. Delete the compiled `.php` files in `storage/framework/views` again. Leave `.gitignore`.
3. Hard-refresh the browser.
4. Restore the database export only if you must undo settings saved after the backup. Importing the full SQL file also restores sales and stock to the backup time. If you only need the old label settings, restore the `common_settings` value of that business row in phpMyAdmin and leave every other table alone.

No public cleanup script is part of this update. Do not add one.
