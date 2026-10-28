# QPOS cPanel troubleshooting

Fix the smallest thing that matches the log. Do not reinstall, do not run `migrate:fresh`, and do not delete the database to clear an error.

Logs, in this order:

1. `storage/logs/laravel.log` in the QPOS folder (File Manager). This is the application log.
2. cPanel → **Metrics** → **Errors**, if that menu exists. This is the web server log. The name and path vary by host.
3. The text printed by `php artisan` if Terminal is available.

When you copy a log line to anyone, remove database passwords, `APP_KEY`, mail passwords, and API keys first.

`APP_DEBUG=false` is required on the live site. You may set `APP_DEBUG=true` for one request to see a stack trace, then set it back to `false` and run `php artisan optimize:clear`. Do not leave it on.

---

## Upload rejected: disk quota

**What you see:** File Manager upload of `QPOS-cPanel-Production.zip` reaches 100%, then a red line says the file exceeds the account's remaining disk usage quota. The blue "Maximum file size" line can still say 1.95 GB.

**Cause:** the account does not have enough free disk space for a file of about 78 MB. This is not a PHP or Laravel error.

**Fix, without Terminal:**

1. cPanel home → **Disk Usage**. See which folders are large.
2. File Manager → delete old ZIPs, backups, and unused folders under `/home/mtcqpos` and `/home/mtcqpos/public_html`. Delete a partial copy of `QPOS-cPanel-Production.zip` if it is there.
3. Confirm at least about 300 MB is free (78 MB ZIP + about 220 MB extracted). Delete the ZIP after extract.
4. If the quota is smaller than that, ask the host to increase it. Do not split the ZIP. The pieces still have to fit, and the extracted site is larger than the ZIP.

## No Terminal

You can still install. Use these instead of `php artisan`:

| Task | Where |
|------|--------|
| Create `.env` | File Manager → Copy `.env.cpanel.example` → rename the copy to `.env` → Edit |
| New empty database | Do not create `.env` first. Open `https://YOURDOMAIN.com/install` and finish the wizard. It writes `.env`, creates `APP_KEY`, and builds the tables |
| Existing database, pending migrations only | Edit `MIGRATE_SECRET` in `public/cpanel_run_migrations.php`, open the URL once, then delete that file |
| Clear cached views and config | File Manager → delete the files inside `bootstrap/cache` (keep `.gitignore`), `storage/framework/views`, and `storage/framework/cache/data` |
| Writable folders | File Manager → Permissions → `775` on `storage`, `bootstrap/cache`, and `public/uploads` |
| Cron | cPanel → **Advanced** → **Cron Jobs**. Paste the command there. You do not open Terminal |
| Storage symlink | Skip it. Product files are stored in `public/uploads` |
| `route:cache`, `config:cache`, `key:generate` | Do not run these by hand. The installer generates the key. Leave route cache unset |

`/install` still drops every table. Use it only when phpMyAdmin shows the database has no QPOS tables.

## HTTP 500

**What you see:** a blank page or "Internal Server Error".

**Look at:** the newest lines in `storage/logs/laravel.log`, then the web server error log.

| Log clue | Smallest fix |
|----------|----------------|
| `syntax error` or `PHP Parse error` | The domain is on PHP older than 8.1, or a file was uploaded partially. Select PHP 8.1 or 8.2 and re-upload the damaged file. |
| `Failed opening required '.../vendor/autoload.php'` | `vendor/` did not extract. Re-extract the ZIP. Document root and `public/index.php` must stay one level below the folder that contains `vendor`. |
| `No application encryption key` | `APP_KEY` is empty. On a new shop run `php artisan key:generate` once. On an existing shop paste the original key. |
| `Permission denied` on `storage` or `bootstrap/cache` | Step "Permission denied" below. |
| `SQLSTATE` | Step "Database connection" or "Missing tables" below. Do not drop the database. |
| `Allowed memory size exhausted` | MultiPHP INI Editor → `memory_limit = 512M` for that report or PDF, then retry. |
| The log file is empty and the server log says `.htaccess: Invalid command 'Header'` | The host has no `mod_headers`. Rename is the wrong fix. Ask the host to enable headers, or temporarily comment the `<IfModule mod_headers.c>` block. The `<IfModule>` wrapper normally prevents this; if it still happens, `.htaccess` is not being parsed as Apache expects. |
| The server log says `php_value` not allowed | PHP is PHP-FPM. Remove is unnecessary if the directives are inside `mod_php` IfModules. Set the limits in MultiPHP INI Editor instead. |

---

## HTTP 403

**What you see:** Forbidden on the home page.

**Causes that match this package:**

- Document root is `qpos/public` but `public/.htaccess` is missing (hidden files were not extracted). Re-extract with hidden files shown.
- The account has no index file and directory listing is off (`Options -Indexes` in both `.htaccess` files). `public/index.php` must be present.
- You requested `/.env`, `/vendor`, or `/storage` on purpose. **403 is the correct result.** Do not "fix" that by loosening permissions.

**Home page 403 while `/index.php` works:** `mod_rewrite` is off or `AllowOverride` is None. Ask the host to allow rewrite for this domain. Opening `/index.php` confirms the code is there.

---

## HTTP 404

**What you see:** cPanel's default 404, or Laravel's "page not found".

**cPanel 404 on `/login`:** the document root is not `qpos/public`, and the root `.htaccess` rewrite is not running. Point the domain at `/home/USERNAME/qpos/public`, or place the project root (the folder that contains both `.htaccess` and `public/`) in `public_html`.

**Laravel 404 on `/login`:** routes did not load. Confirm `routes/web.php` and `vendor/` exist. Do not run `route:cache`. If `bootstrap/cache/routes-v7.php` exists, delete it. It should not be in this ZIP.

**404 on `/install` after you created `.env`:** that is intentional. `InstallController::isInstalled()` returns 404 when `.env` exists. It is not a missing file. Do not delete `.env` on a database that already has shops.

**404 on CSS:** see "Missing CSS".

**`/public/login` redirects or 404s:** `APP_URL` must not end in `/public`. The root `.htaccess` redirects `/public/...` to a clean URL when the document root is the project folder. Preferred setup never uses that prefix.

---

## PHP version incompatibility

**What you see:** parse errors, or a flood of `Deprecated` messages, or a white screen only on this domain.

**Check:** cPanel → Select PHP Version or MultiPHP Manager → the version **for this domain**. The account default can differ from the domain.

**Use:** PHP 8.1 or 8.2.

**Do not use:** PHP 7.x, PHP 8.0 (Symfony packages in `vendor` require `>= 8.1`), PHP 8.4, or PHP 8.5. This tree was booted on PHP 8.5 only as a local test and printed deprecation warnings from Monolog and Laravel 9. That is not a production target.

**Artisan uses a different PHP than the website:** Terminal `php -v` can be another version. Call the same binary the domain uses, often `/opt/cpanel/ea-php82/root/usr/bin/php`. The folder name is host-specific.

---

## Missing PHP extensions

**What you see:** `Class "..." not found` is usually Composer, not an extension. An extension error looks like `undefined function mysqli_connect`, `could not find driver`, or the installer page showing a red cross.

**Installer red rows** (`/install` before `.env` exists) name the missing piece: PHP version, `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `fileinfo`, `json`, or a folder that is not writable.

**`could not find driver` in `laravel.log`:** enable `pdo_mysql`.

**`Call to undefined function mysqli_connect` on the install form:** enable `mysqli`. Artisan does not use it; the install form does.

**PDF or Excel errors mentioning `iconv` or `mbstring`:** enable those extensions.

**Where:** Select PHP Version → Extensions, or MultiPHP INI Editor's extension list. Save, then reload the site. No code change is required.

---

## Composer / autoload

**What you see:** `require(.../vendor/autoload.php): Failed to open stream`, or `Class "App\..." not found` after a partial upload.

**Fix:**

1. Confirm the path in the error is the same folder as `artisan`.
2. Re-extract the ZIP so `vendor/autoload.php` is restored.
3. Only if `vendor/` is gone and Terminal has Composer:

```bash
cd /home/USERNAME/qpos
composer install --no-dev --optimize-autoloader
```

Do not run `composer update`. Do not delete `vendor` first "to be clean".

`Class "Barryvdh\Debugbar\..." not found` after someone ran `composer install --no-dev` while an old `bootstrap/cache/packages.php` still mentions Debugbar: delete `bootstrap/cache/packages.php` and `bootstrap/cache/services.php`, then reload. This ZIP keeps the vendor tree that was installed with the application, and `config/debugbar.php` sets `'enabled' => false`.

---

## Wrong document root

**Symptom:** the file list of `public/` is shown, or the browser downloads `index.php`, or URLs need `/public`.

**Correct root:** `/home/USERNAME/qpos/public`

**Check:** that folder contains `index.php` and `.htaccess`, and the parent contains `artisan` and `vendor`.

**Wrong roots:**

- `/home/USERNAME/qpos` (exposes the application unless the root `.htaccess` is active)
- `/home/USERNAME/qpos/public/public`
- `/home/USERNAME/public_html/qpos/public` while the domain still points at `public_html`

Change the domain document root. Do not edit `public/index.php` paths. They are already `../vendor` and `../bootstrap`.

---

## `public/index.php` paths

This package's `public/index.php` expects:

```text
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

If a host tells you to move `index.php` into `public_html` and leave the rest of the project elsewhere, you would have to change those two lines. Do not do that with this package. Move the document root, or move the whole project, so the relative paths stay true.

A 500 that says the autoload path failed is a layout problem, not a missing Composer package, when `vendor` exists beside `public`.

---

## Database connection failures

**What you see in `laravel.log`:** `SQLSTATE[HY000] [2002]`, `SQLSTATE[HY000] [1045] Access denied`, or `Unknown database`.

**Check `.env` only:**

- `DB_DATABASE` and `DB_USERNAME` are the full cPanel names.
- `DB_PASSWORD` has no quotes unless the password itself contains spaces. If you add quotes, they must be double quotes and the password must not contain an unescaped `"`.
- `DB_HOST=localhost` and `DB_PORT=3306`.
- No space before or after `=`.
- The MySQL user is assigned to that database with ALL PRIVILEGES (cPanel → MySQL Databases).

**After any edit:**

```bash
php artisan optimize:clear
```

A cached config file ignores your new password until you do this. Delete `bootstrap/cache/config.php` if you cannot run Artisan.

**1045 after a password reset:** cPanel changed the password. Update `.env` to the new password. The old one is not stored in QPOS.

**2002 Connection refused:** `DB_HOST` is wrong for this host, or MySQL is down. Try `localhost`. Do not point `DB_HOST` at a public IP unless the host explicitly allows remote MySQL.

This package was not able to connect to a database during packaging. A connection error on the server is new information from that server, not a broken ZIP.

---

## Missing tables or migration errors

**`Base table or view not found`:** the database is empty or only partly migrated. If it has no shop data, `php artisan migrate --force` creates the missing tables. If some tables exist and contain data, run `php artisan migrate:status` and stop if you do not understand the Pending list.

**`Table already exists`:** a migration is pending in code but the table was created earlier without a `migrations` row, or a patch SQL file was imported twice. Do not drop the table. Compare the table in phpMyAdmin with the migration file before doing anything else.

**Duplicate column** after importing `database/cpanel_tyre_size.sql` or another `database/cpanel_*.sql` file: that column is already there. Ignore that one statement. Do not import the file again.

**`/install` on a database that already has products:** stop. `migrate:fresh` drops all tables. Restore the phpMyAdmin backup if the wizard was finished.

**Nothing was backed up and fresh already ran:** the data is not in a QPOS recycle bin. Only a host backup or a phpMyAdmin export made earlier can restore it.

The locked script `public/cpanel_run_migrations.php` runs `migrate --force` (pending only). It does not drop tables. It still changes schema. Backup first. Delete the file after `SUCCESS`. A `LOCKED` response means `MIGRATE_SECRET` is still the placeholder.

---

## Permission denied

**What you see:** `file_put_contents(.../storage/...): Permission denied` or `The stream or file ".../laravel.log" could not be opened`.

**Fix only these trees:** `storage`, `bootstrap/cache`, `public/uploads`.

File Manager → Permissions → `775` for folders. If PHP still cannot write, the host runs PHP as a different user. `777` on those three trees is the shared-hosting fallback. Do not apply `777` to `.env` or `vendor`.

Also confirm the folders exist. This package includes `storage/framework/sessions`, `storage/framework/views`, `storage/framework/cache/data`, `storage/logs`, `storage/app/pdf`, and `public/uploads/temp`.

---

## Missing CSS, JavaScript, images, or fonts

**What you see:** unstyled login page, or 404 for `/css/app.css` or `/js/app.js`.

**Check:**

1. Document root is `public`, so `/css/app.css` maps to `public/css/app.css`.
2. `public/css/app.css`, `public/js/app.js`, `public/js/pos.js`, and `public/mix-manifest.json` exist. They are in the ZIP. A 404 means the document root is wrong or the extract skipped `public`.
3. `APP_URL` is `https://YOURDOMAIN.com` with no `/public` and no trailing path.
4. Hard-refresh the browser.

**Product images 404:** the database row points at a file under `public/uploads/img`. A new empty install has no old photos. A moved shop must copy `public/uploads` from the old server. The ZIP contains the uploads that were on the machine that built it. It does not contain another server's photos.

**Fonts:** `public/fonts` and the paths in `public/mix-manifest.json` must be present. Re-extract if `woff` files are missing. Do not run `npm` on the server.

---

## Session and CSRF (419 Page Expired)

**What you see:** login returns 419, or the form says Page Expired.

**Causes in this project:**

- `storage/framework/sessions` is not writable, so the CSRF token is never stored.
- `APP_URL` is `http://` while the browser is on `https://`, or the opposite.
- `SESSION_SECURE_COOKIE=true` while you are still testing on `http://`. Set it `true` only after HTTPS works. Set it `false` only for a short HTTP test, then turn it back.
- `SESSION_DOMAIN` is set to a different host. Leave it empty unless you know you need it.
- The browser cached an old login page. `public/.htaccess` and the root `.htaccess` send `Cache-Control: no-store` on PHP responses when `mod_headers` is available. Clear the browser cache once.

`config/session.php` uses `same_site` = `lax` regardless of `SESSION_SAME_SITE` in `.env`.

**Fix:** correct `.env`, chmod the session folder, `php artisan optimize:clear`, try a private browser window.

---

## Login redirect loop

**What you see:** `/login` and `/home` swap forever, or you return to login immediately after a correct password.

**Check:**

1. Session folder writable (above). A login that cannot store a session always returns to the form.
2. `SESSION_SECURE_COOKIE` matches the real scheme.
3. `APP_URL` matches the domain you typed, including `https://`.
4. The clock on the server is not wildly wrong. Session cookies use that clock. cPanel does not always let you set the server clock; `APP_TIMEZONE` does not fix a wrong system clock.
5. You are not blocking cookies in the browser.

**Demo tiles:** `SHOW_DEMO_SHOPS=true` shows extra buttons. It does not create a loop. Leave it `false`.

**`APP_DEBUG` must stay false** while you test the loop. A 500 hidden as a redirect is visible in `laravel.log`.

---

## Storage link and upload failures

**Upload succeeds but the picture is missing:** check `public/uploads/img` and the matching subfolder. Permissions `775`. The default disk is `public/uploads`, not `storage/app`.

**`Unable to create the symbolic link` or `symlink(): Permission denied`:** skip `storage:link`. Product uploads still work. Only `/storage/...` URLs break.

**PDF invoice fails and the log mentions `tempDir` or `not writable`:** `public/uploads/temp` must exist and be writable. Ledger PDFs also use `storage/app/pdf`.

**`The file failed to upload`:** `upload_max_filesize` and `post_max_size` are still 2M. Set both to `64M` in MultiPHP INI Editor. `post_max_size` must be at least as large as `upload_max_filesize`.

---

## Cache configuration problems

**Symptom:** you changed `.env` and the site still uses the old database or the old URL.

**Cause:** `bootstrap/cache/config.php` exists.

**Fix:**

```bash
cd /home/USERNAME/qpos
php artisan optimize:clear
```

No Terminal: delete `bootstrap/cache/config.php`, `bootstrap/cache/routes-v7.php` if present, and the files inside `storage/framework/cache/data` and `storage/framework/views`. Leave the folders.

Then, when Terminal exists and `.env` is final:

```bash
php artisan config:cache
php artisan view:cache
```

Never `php artisan route:cache`. The local test failed with: another route is already named `logout`.

**`CACHE_DRIVER=redis` or `memcached`:** this package's template uses `file`. If `.env` was changed to redis and redis is not installed, set it back to `file`.

---

## HTTPS and mixed content

**What you see:** the padlock is missing, or the console says a script was loaded over HTTP.

**Fix:**

- `APP_URL=https://YOURDOMAIN.com`
- Certificate is active for that exact host (`www` and the bare domain are different certificates on some hosts).
- After changing `APP_URL`, clear the config cache.
- Hard-refresh.

QPOS asset URLs are generated from the current request and `APP_URL`. A cached config with `http://` produces mixed content on an HTTPS page.

**Redirect loop between http and https:** a host-level "force HTTPS" rule plus an application rule can loop. QPOS does not add its own HTTPS redirect in `.htaccess`. If the loop starts after you enable the host's force-HTTPS option, disable that option and use one redirect only. Keep `SESSION_SECURE_COOKIE=true` once HTTPS is stable.

---

## Email delivery failures

**What you see:** quotations or reminders never arrive. `laravel.log` shows `Connection could not be established with host mail.YOURDOMAIN.com` or authentication failed.

**Check:**

- The mailbox exists in cPanel Email Accounts.
- `MAIL_USERNAME` is the full address.
- `MAIL_PASSWORD` is the mailbox password, not the cPanel password and not the database password.
- Port `465` with `ssl`, or port `587` with `tls`, matching the host.
- `MAIL_FROM_ADDRESS` is an address on this domain. Many hosts reject other from-addresses.

**Payment reminders** (`pos:autoSendPaymentReminder`) also need cron and `APP_ENV=live`. A mail failure there does not stop POS.

**No mail configured:** leave the password empty and do not use features that send mail. Do not switch `MAIL_MAILER` to `log` on production unless you are deliberately writing messages into `laravel.log` for a test. Switch it back afterward.

---

## Scheduled commands not running

**Check all of these:**

1. `APP_ENV=live`. Any other value, including `production`, skips the schedule in `app/Console/Kernel.php`.
2. Cron exists and runs **every minute**, not once a day.
3. The command uses the PHP 8.1/8.2 binary and the full path to `artisan`:

```text
/opt/cpanel/ea-php82/root/usr/bin/php /home/USERNAME/qpos/artisan schedule:run >> /home/USERNAME/qpos/storage/logs/cron.log 2>&1
```

4. `storage/logs` is writable so the cron log can be created.
5. The server timezone plus `APP_TIMEZONE` explains the clock. A job set to 08:00 runs at 08:00 in `APP_TIMEZONE`, and only after a cron tick.

**`backup:run` fails** with `mysqldump` not found: expected on many shared hosts. Use cPanel backups. Do not uncomment the Windows path in `config/database.php`.

**`pos:dummyBusiness` appears in the log:** `APP_ENV` is `demo`. Set it back to `live` immediately. That command is for a demo server.

---

## Receipt and barcode printing on a remote host

**Browser receipt works** when the sale screen shows a preview and the browser print dialog opens. The printer must be installed on the cashier's computer. cPanel has no setting for that USB device.

**PDF download works** when Sales → the invoice → download returns a PDF. If it 500s, read `laravel.log` and confirm `public/uploads/temp` is writable and `gd`, `mbstring`, and `iconv` are on.

**"The receipt printer is unavailable"** or a sale completes with no paper, while the location is set to printer type `printer`: the browser tried `ws://127.0.0.1:6441` (`public/js/printer.js`) and nothing on the cashier PC accepted it. The hosting account cannot start that service. On the cashier PC, run the local QPOS print bridge that listens on port 6441, and keep the browser allowed to connect to localhost. Until that program is running, set the location receipt printer type to browser so `pos.js` prints the HTML receipt instead.

**Windows or Linux printer path in QPOS settings** is a path on the cashier's computer, sent through that same local socket. It is not a path on the cPanel server. A network printer IP is also used by the local bridge, not by PHP on the host.

**Barcode labels:** Business Settings saves `enable_bartender_export` as `0`. Use **Print labels** and the browser print page. BarTender installed on a shop PC is not connected by uploading this ZIP. Label size is stored in business settings (`browser_label_layout`) and printed by the browser.

**Labels cut off:** that is the browser margin and the label size in Business Settings (millimetres). Change the paper size in the print dialog to match `label_w` and `label_h`. The server is not clipping a physical printer.

---

## After a fix

1. `APP_DEBUG=false`
2. `php artisan optimize:clear` if `.env` changed
3. Retry the one action that failed
4. Delete `public/cpanel_run_migrations.php` if it was used
5. Confirm `https://YOURDOMAIN.com/.env` does not download
