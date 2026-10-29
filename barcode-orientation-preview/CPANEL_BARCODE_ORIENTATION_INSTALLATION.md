# QPOS barcode orientation and live preview — cPanel File Manager

This is a **minimal patch** for an existing QPOS site that already has the browser label screen at `/labels/browser-print`. It is not a full application. There is **no database migration** and **no Terminal or SSH step**.

## 1. Find the live site

In cPanel, open **File Manager**. The QPOS folder contains `artisan`, `app`, and `resources`. On this account that is usually:

`/home/mtcqpos/public_html`

Confirm `resources/views/labels/browser_print.blade.php` is there before you upload anything.

## 2. Back up the files you will replace

Download these files and keep them on your computer:

- `app/Utils/BrowserLabelLayout.php`
- `app/Http/Controllers/LabelsController.php`
- `resources/views/labels/browser_print.blade.php`
- `resources/views/business/partials/settings_bartender.blade.php`
- `lang/en/lang_v1.php`
- `routes/web.php`

`resources/views/labels/partials/browser_sticker.blade.php` is new.

Also export the database from **phpMyAdmin** (Export → Quick → SQL) and store it with the file backups. This patch does not change tables. Do not import that file unless you are rolling back.

## 3. Upload the ZIP away from the live site

1. Go to the home directory, not inside `public_html`.
2. Upload `QPOS-Barcode-Orientation-Live-Preview-Fix.zip`.
3. Select it and click **Extract**.
4. Confirm the extracted folder contains `app/Utils/BrowserLabelLayout.php` and `resources/views/labels/partials/browser_sticker.blade.php`.

Do not extract the ZIP directly over `public_html`.

## 4. Copy only these paths

| Extracted file | Live destination |
| --- | --- |
| `app/Utils/BrowserLabelLayout.php` | `public_html/app/Utils/BrowserLabelLayout.php` |
| `app/Http/Controllers/LabelsController.php` | `public_html/app/Http/Controllers/LabelsController.php` |
| `resources/views/labels/browser_print.blade.php` | `public_html/resources/views/labels/browser_print.blade.php` |
| `resources/views/labels/partials/browser_sticker.blade.php` | `public_html/resources/views/labels/partials/browser_sticker.blade.php` |
| `resources/views/business/partials/settings_bartender.blade.php` | `public_html/resources/views/business/partials/settings_bartender.blade.php` |
| `lang/en/lang_v1.php` | `public_html/lang/en/lang_v1.php` |
| `routes/web.php` | `public_html/routes/web.php` |

Create `resources/views/labels/partials` if it is missing. Do not replace `.env`, `vendor`, `storage`, or the database.

## 5. Refresh the page

1. Hard-refresh the label page (`Ctrl+F5`).
2. If the live preview is missing, delete the compiled `.php` files in `storage/framework/views` and leave `.gitignore`. QPOS rebuilds them on the next page load.
3. If the old screen is still shown after a minute, restart PHP for the domain from cPanel **MultiPHP Manager**. Do not upload a public script that runs Artisan.

## 6. Check the preview, then print one sticker

1. Log in and open **Print Labels → Browser print** (`/labels/browser-print`).
2. The **Live label preview** is at the top of the settings. It is one sticker at the real proportions.
3. Set **Content rotation** to **0° — upright**. The shop name must sit at the top of the preview and the price at the bottom.
4. Width **38**, height **25**, stock **Continuous roll**.
5. Click **Save layout**.
6. The batch under the form uses the same design. Several stickers there show gaps and alignment.
7. Print **one** sticker. In Chrome or Edge: margins **None**, scale **100%**, headers and footers **off**. Do not switch the paper to A4.

The previous **90° — printer turns the page** choice swapped the page and turned the artwork again. On this printer those two turns printed the label upside down. That option is gone. **0°** is upright. **180°** is upside down, and the preview shows it before you print. Use 180° only if a test sticker at 0° comes out upside down.

A browser preview does not prove the physical gap sensor. Confirm with one sticker before a batch.

## 7. Roll back

Put the backed-up files back in the same paths. Delete `resources/views/labels/partials/browser_sticker.blade.php` if you are returning to a copy that does not have it. Hard-refresh. If needed, clear `storage/framework/views` again. Import the SQL backup only if you must undo settings saved after the export. A full import also restores sales and stock to the backup time.
