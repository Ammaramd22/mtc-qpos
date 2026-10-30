# cPanel installation

Install this update with cPanel File Manager only. Do not use Terminal or SSH. Do not replace the live site’s `.env`, `vendor`, or `storage` folders.

## Before you upload

1. In File Manager, open the QPOS application.
2. Download a backup of each file listed below before you overwrite it.
3. Confirm which folder is the website root. On most accounts that folder is `public_html`. The Laravel application (the folder that contains `app`, `resources`, and `vendor`) may be `public_html` itself, or it may sit one level above `public_html`.

## Where each file goes

Upload these into the website root (the folder that already contains `css` and `js`):

- `public/css/qpos-light.css` → `css/qpos-light.css`
- `public/css/pos-touch.css` → `css/pos-touch.css`

Upload these into the Laravel application folder (the folder that already contains `resources/views`):

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/restaurant.blade.php`
- `resources/views/labels/show.blade.php`
- `resources/views/product/view-modal.blade.php`
- `resources/views/contact/partials/ledger_screen_styles.blade.php`
- `resources/views/cheque/index.blade.php`
- `resources/views/cheque/report.blade.php`
- `resources/views/sale_pos/partials/pos_form.blade.php`

Keep the same folders. For example, `ledger_screen_styles.blade.php` must stay inside `resources/views/contact/partials/`.

Do not upload `CHANGELOG.md`, `LIGHT_THEME_FIX_REPORT.md`, `CPANEL_INSTALLATION_GUIDE.md`, or `TEST_REPORT.md` into the application. Those files are for you to read.

## After you upload

1. In the browser, open QPOS and hard-refresh (Ctrl+F5).
2. View page source and confirm the stylesheet address contains `qpos-light.css?v=` and ends with `-lt2`.
3. Turn on Light Theme and open Print Labels. The product name must be dark charcoal on a white row.
4. Turn on Dark Theme. The same product name must be light text on a dark row.
5. Open one products table, one sales table, and one contact ledger, in both themes.

Blade templates recompile when the uploaded file is newer than the cached view. You do not need to run a command. If a page still shows the old layout, delete only the files inside `storage/framework/views` from File Manager, then reload. Do not delete `storage/logs` or uploaded business files.

## If the document root is public_html and the app is above it

- CSS files go in `public_html/css/`.
- Blade files go in the parent application’s `resources/views/...`.
- Do not place `resources` inside `public_html` unless the whole Laravel project already lives there.
