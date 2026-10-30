# QPOS Light Theme — complete contrast fix — cPanel installation

This package makes Light Theme text, backgrounds, tables, forms, dropdowns, and popups readable. Dark Theme stays the default and is not restyled.

No Terminal or SSH access is required. No database change is required. Do not import a SQL file. Do not replace the live `.env` file. Do not change product records, stock, prices, sales, or payment calculations.

## Files in this folder

| File | Purpose |
|---|---|
| `QPOS-Light-Theme-Complete-Fix-cPanel.zip` | The updated application files |
| `CPANEL_INSTALLATION_GUIDE.md` | This guide |
| `LIGHT_THEME_AUDIT_REPORT.md` | What was wrong and where |
| `LIGHT_THEME_TEST_REPORT.md` | What was tested and what was not |
| `CHANGELOG.md` | What changed and why |

## Files inside the ZIP

Extracting the ZIP must put each application file at the path below, relative to the QPOS application root (the folder that contains `artisan`).

| ZIP path | Replace this live file |
|---|---|
| `public/css/qpos-light.css` | `public/css/qpos-light.css` |
| `public/css/app.css` | `public/css/app.css` |
| `public/js/labels.js` | `public/js/labels.js` |
| `resources/views/layouts/app.blade.php` | `resources/views/layouts/app.blade.php` |
| `resources/views/layouts/restaurant.blade.php` | `resources/views/layouts/restaurant.blade.php` |
| `resources/views/layouts/partials/css.blade.php` | `resources/views/layouts/partials/css.blade.php` |
| `resources/views/labels/show.blade.php` | `resources/views/labels/show.blade.php` |
| `resources/views/labels/browser_print.blade.php` | `resources/views/labels/browser_print.blade.php` |

The four Markdown files in the ZIP are documentation only. Do not copy them into the live application.

If the website address points at `public_html`, that folder is usually the Laravel `public` folder. In that case:

- `public/css/qpos-light.css` in the ZIP goes to `public_html/css/qpos-light.css`
- `public/css/app.css` goes to `public_html/css/app.css`
- `public/js/labels.js` goes to `public_html/js/labels.js`
- Every `resources/views/...` file goes in the application folder **above** `public_html`, under `resources/views/...`

## 1. Back up the live files

In cPanel File Manager, download a copy of each live file in the table above before replacing it. Keep that backup outside the application folder.

## 2. Upload the ZIP

1. In File Manager, create a temporary folder such as `qpos-light-theme-fix` next to the application. Do not upload the ZIP into `public` or `public_html`.
2. Upload `QPOS-Light-Theme-Complete-Fix-cPanel.zip` into that temporary folder.
3. Select the ZIP and choose Extract.
4. Confirm the extract created `public/` and `resources/` inside the temporary folder.

## 3. Copy the application files

Copy each extracted file over the matching live file. Overwrite when File Manager asks. Do not merge folders blindly if the temporary folder also contains the Markdown guides; copy only the eight paths in the table.

After the copy:

- `public/css/qpos-light.css` on the server must be the new file (it is larger than the previous light stylesheet).
- The layout files must reference `qpos-light.css?v=...-lt1`. That query string is what makes browsers load the new stylesheet without a terminal cache clear.

## 4. Do not run commands

Do not open Terminal. Do not run `php artisan`. Laravel recompiles a Blade file on the next page view when the uploaded file is newer. Do not delete `storage/framework/views` unless a page still shows the old Print Labels markup after a hard refresh.

Do not replace `.env`, `storage/`, `vendor/`, or `node_modules/`.

## 5. Confirm on the live site

1. Sign in and open any admin page.
2. Use the header theme control to switch to Light. The choice is stored in this browser for the signed-in user.
3. Open Products → Print Labels. Add a product. The product name must be dark charcoal on a white row. The table header must be dark grey on a light header, not white on white.
4. Switch back to Dark. The same product name must return to light text on the dark row.
5. Refresh the page. The theme you selected must still be active.
6. Open the browser label print window. The sticker artwork stays black ink on white paper. In Light Theme the setup toolbar is light; in Dark Theme it stays dark. Printing still hides the toolbar.

If Light Theme still looks like the old page, hard-refresh once (Ctrl+F5). The stylesheet address must end in `-lt1`.
