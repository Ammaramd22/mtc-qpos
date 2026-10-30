# QPOS Print Labels — search and Size/Color update

This is a **minimal patch** for an existing live QPOS installation. It is not a full application. There is **no database change** and **no Terminal or SSH step**.

The package changes Print Labels search and the browser label “Show on label” options. Sales, stock, prices, invoices, and product records are not changed.

## 1. Find the live QPOS folder

In cPanel, open **File Manager**.

The live site is the folder that contains `artisan`, `app`, and `resources`. On this hosting account that is usually:

`/home/mtcqpos/public_html`

Confirm you can see `app/Http/Controllers/LabelsController.php` and `resources/views/labels/show.blade.php` before you upload anything. If those files are in a subfolder, that subfolder is the destination. Do not upload into `public` alone.

## 2. Back up the files you will replace

In File Manager, download a copy of each of these files and keep them on your computer:

- `app/Http/Controllers/LabelsController.php`
- `app/Utils/BrowserLabelLayout.php`
- `routes/web.php`
- `public/js/labels.js`
- `resources/views/labels/show.blade.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/labels/partials/browser_sticker.blade.php`
- `resources/views/labels/partials/show_table_rows.blade.php`
- `resources/views/business/partials/settings_bartender.blade.php`
- `lang/en/lang_v1.php`

## 3. Back up the database

Open **phpMyAdmin** from cPanel.

1. Select the QPOS database.
2. Click **Export**.
3. Choose **Quick** and **SQL**.
4. Click **Go** and store the `.sql` file with the file backups.

This update does not change tables, columns, or product rows. The backup is only so you can restore `business.common_settings` if a saved label layout needs to be undone. Do not import that file unless you intend to roll the whole database back. A full import replaces sales and stock made after the export.

## 4. Upload and extract away from the live site

1. In File Manager, go to a temporary folder such as `/home/mtcqpos` (the home directory), **not** inside `public_html`.
2. Upload `QPOS-Label-Search-Size-Color-Update-cPanel.zip`.
3. Select the ZIP and click **Extract**.
4. Open the extracted folder and confirm these paths exist inside it:

```
app/Http/Controllers/LabelsController.php
app/Utils/BrowserLabelLayout.php
routes/web.php
public/js/labels.js
resources/views/labels/show.blade.php
resources/views/labels/browser_print.blade.php
resources/views/labels/partials/browser_sticker.blade.php
resources/views/labels/partials/show_table_rows.blade.php
resources/views/business/partials/settings_bartender.blade.php
lang/en/lang_v1.php
```

Do not extract the ZIP directly over `public_html`. Copy the files one path at a time so nothing else is overwritten.

## 5. Copy each file to the matching live path

From the extracted folder, copy each file onto the same path under the live QPOS folder. Overwrite only these files:

| Extracted file | Live destination |
| --- | --- |
| `app/Http/Controllers/LabelsController.php` | `public_html/app/Http/Controllers/LabelsController.php` |
| `app/Utils/BrowserLabelLayout.php` | `public_html/app/Utils/BrowserLabelLayout.php` |
| `routes/web.php` | `public_html/routes/web.php` |
| `public/js/labels.js` | `public_html/public/js/labels.js` |
| `resources/views/labels/show.blade.php` | `public_html/resources/views/labels/show.blade.php` |
| `resources/views/labels/browser_print.blade.php` | `public_html/resources/views/labels/browser_print.blade.php` |
| `resources/views/labels/partials/browser_sticker.blade.php` | `public_html/resources/views/labels/partials/browser_sticker.blade.php` |
| `resources/views/labels/partials/show_table_rows.blade.php` | `public_html/resources/views/labels/partials/show_table_rows.blade.php` |
| `resources/views/business/partials/settings_bartender.blade.php` | `public_html/resources/views/business/partials/settings_bartender.blade.php` |
| `lang/en/lang_v1.php` | `public_html/lang/en/lang_v1.php` |

Do not replace `.env`, `vendor`, `storage`, or the database.

## 6. Database

No phpMyAdmin change is required. Do not run a SQL file. Do not create a PHP file to run Artisan. Size and Color already live on `products.size` and `products.color`. This update only reads them.

The new show/hide settings are saved later into the existing `business.common_settings` column when an authorized user clicks **Save layout**, **Save as profile**, or saves Business Settings.

## 7. Route cache, compiled views, and the browser

1. In File Manager, look in `bootstrap/cache`. If you see `routes-v7.php` or `routes.php`, delete **only** that route-cache file. Leave `services.php` and `packages.php`. If those route-cache files are not there, do nothing. QPOS reads `routes/web.php` directly when they are absent.
2. Hard-refresh Print Labels (`Ctrl+F5`). The page loads `labels.js` with a new `labelsearch1` query so the old script is not reused.
3. If the print screen still has no Size and Color checkboxes, open `storage/framework/views` and delete the compiled `.php` files in that folder only. Leave the `.gitignore` file. QPOS recreates the compiled views on the next page load.
4. If PHP still serves old code after several minutes, use cPanel **MultiPHP Manager** or **Select PHP Version** and restart PHP for the domain. Do not upload a public script that runs Artisan or clears caches.

## 8. Test product suggestions

1. Log in to QPOS.
2. Open **Products → Print Labels** (`/labels/show`).
3. Type one letter. Nothing should be added to the list.
4. Type at least two letters of a product name. A dropdown should appear under the search field. The list below stays unchanged.
5. Type a SKU or barcode the same way. The matching product should appear as a suggestion, not as a new row.

## 9. Test explicit selection

1. Click one suggestion. That product is added, and the search field clears.
2. Search again and add a different product. Both rows stay, each with its own label quantity.
3. Use the down arrow to highlight a suggestion and press Enter. That product is added.
4. Press Escape. The dropdown closes and nothing is added.
5. Select a product that is already in the list. Its **No. of labels** increases by 1. A second row is not created.
6. Change a quantity, then select that same product again. The quantity goes up by 1 from the number you typed.
7. Use the trash button. The row is removed.

## 10. Test Size and Color

1. Open **Print Labels (Browser)** for a product that has Size, one that has Color, one that has both, and one that has neither.
2. In **Show on label**, tick **Size**. Size appears on the live preview and on the batch stickers for products that have a size. Products with an empty size do not gain a blank line.
3. Untick **Size**. Size disappears. Color, if ticked, stays.
4. Tick and untick **Color** on its own, then with Size.
5. A product with neither value shows no Size or Color line in any of those states.

## 11. Test the live preview

The dashed preview and the batch stickers use the same sticker template. Changing Size or Color updates both immediately. Width, height, rotation, barcode size, fonts, padding, and presets still apply.

Click **Save layout**, close the print window, and open browser print again. Size and Color should return as you saved them.

Save a named profile with Size on and Color off. Save another with both off. Switch between them. **Restore defaults** turns Size and Color off, which matches older layouts that did not have these switches.

## 12. Test printing

A physical printer was not part of this package test. On the shop computer:

1. Print one sticker with Size on, one with Color on, one with both, and one with neither.
2. Compare each sticker with the dashed preview.
3. Use the existing 50 × 25 mm layout and one other size you already use.
4. Chrome or Edge print dialog: margins **None**, scale **100%**, headers and footers **off**.

Do not print a large batch until one sticker matches the preview.

## 13. Roll back

1. Put the ten backed-up files back in the same paths.
2. If you deleted `bootstrap/cache/routes-v7.php` or `routes.php`, leave it deleted. QPOS will use `routes/web.php` from the restored copy.
3. Hard-refresh the browser.
4. If compiled views still look new, delete the `.php` files in `storage/framework/views` again.
5. Restore the database export only if you must undo settings saved after the backup. Importing the full SQL file also restores sales and stock to the backup time. If you only need the old label settings, restore the `common_settings` value of that business row in phpMyAdmin and leave every other table alone.

No public cleanup script is part of this update. Do not add one.
