# QPOS cPanel installation guide

This guide installs the existing QPOS Laravel 9 application. It does not rebuild it. Menu names differ between hosts. Where a screen is not the same on every cPanel account, the step says so.

Placeholders used throughout:

- `USERNAME` — the cPanel account name
- `YOURDOMAIN.com` — the domain already pointed at this account
- `cpaneluser_database` and `cpaneluser_dbuser` — the full MySQL names shown by cPanel, including the account prefix

Do not invent shorter names. cPanel almost always prefixes them.

This account has **no Terminal**. Do not wait for SSH. Do not open `/install` (it drops every table). Use `cpanel_setup.php` in the browser instead.

For `/home/mtcqpos/public_html` (File Manager already inside that folder):

1. Upload `QPOS-cPanel-Production.zip` into this folder and extract here. Tick **Overwrite existing files**.
2. `index.php` and `artisan` must sit in this same list, next to `cgi-bin`. There is no `public` folder and no `qpos` folder in the ZIP.
3. If a `public` folder is still there from the old extract, delete it. That folder is what put `/public/` in the address.
4. Keep the existing `.env`, `cgi-bin`, and `php.ini`. The ZIP does not replace `.env`.
5. In `.env`, set `APP_URL=https://mtc-qpos.avenque.io` with no `/public`.
6. Open `https://mtc-qpos.avenque.io/login`. Old `/public/login` redirects to `/login`.

The longer steps below explain the same work in more detail. Where they mention `php artisan`, skip that command and use the setup page.

## If the upload says the disk quota is full

cPanel → File Manager → Upload can show two different limits:

- **Maximum file size allowed** (on this account, 1.95 GB). This ZIP is about 78 MB, so that limit is not the failure.
- **Remaining disk usage quota.** The red message `The file size exceeds your account's remaining disk usage quota` means the account does not have 78 MB free. cPanel refused the file before it was stored.

Free space, then upload again. The username on this account is `mtcqpos`.

1. cPanel home → **Disk Usage** (or the disk meter in the right sidebar). Note the quota and what is still free.
2. File Manager → `/home/mtcqpos/public_html` and `/home/mtcqpos`. Delete old ZIPs, old backups, and unused sites. Also delete a partial `QPOS-cPanel-Production.zip` if the failed upload left one.
3. You need about **300 MB free** before you start: about 78 MB for the ZIP plus about 220 MB for the extracted application. After a successful extract, delete the ZIP. The live site then occupies about 220 MB.
4. If Disk Usage shows the quota itself is too small, ask the host (hostique.co.uk for this account) to raise the disk quota for `mtcqpos`. A smaller ZIP does not fix a full account, because extracting QPOS still needs the space above.

Uploading into `public_html` uses the same quota as uploading into the home directory.

## Before you start

You need:

- The file `QPOS-cPanel-Production.zip`
- PHP **8.1 or 8.2** (not 8.0, and not 8.4 or 8.5)
- The MySQL database that already exists on this account
- A backup of that database before any install or migration step

The ZIP does not contain `.env`. You create that on the server.

---

## 1. Check hosting requirements and PHP

**Where:** cPanel → **Software** → **Select PHP Version** (CloudLinux) or **Software** → **MultiPHP Manager** (EasyApache).

**What to set:** PHP 8.1 or 8.2 for `YOURDOMAIN.com`. Apply the version to this domain only if the account hosts other sites.

**Extensions to enable** on that same PHP version:

Required: `bcmath`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `zip`, `json`, `iconv`

`mysqli` is required by the QPOS web installer (`mysqli_connect` in `app/Http/Controllers/Install/InstallController.php`). Laravel itself uses `pdo_mysql`.

**INI values:** cPanel → **Software** → **MultiPHP INI Editor** → select the domain, or Editor Mode:

```text
max_input_vars = 10000
post_max_size = 64M
upload_max_filesize = 64M
memory_limit = 256M
max_execution_time = 120
```

`public/.htaccess` contains the same upload and `max_input_vars` values inside `<IfModule mod_php*>`. On PHP-FPM those lines do nothing. The INI editor is the setting that applies.

**Expected result:** the domain's PHP version shows 8.1 or 8.2, and the extensions above are checked.

**If this screen does not exist:** the host has locked PHP. Open a ticket and ask for PHP 8.1 or 8.2 with the extensions listed above. Do not continue on PHP 7.

**Terminal check,** only if cPanel → **Advanced** → **Terminal** exists:

```bash
php -v
php -m
```

The `php` in Terminal must be 8.1 or 8.2. If it prints 8.0 or older, cron and Artisan must call the versioned binary instead, for example `/opt/cpanel/ea-php82/root/usr/bin/php`. The exact folder is host-specific. MultiPHP Manager sometimes shows it.

---

## 2. Confirm the domain is on this account

**Where:** cPanel → **Domains**, or **Domains** → **Addon Domains** / **Subdomains** on older themes.

**Check:** `YOURDOMAIN.com` is listed on this account, and its DNS points at this server. cPanel → **Zone Editor** can show the A record, but only if DNS is hosted here. If DNS is at a registrar, check it there. This guide cannot see your DNS.

**Expected result:** the domain is listed, and a browser request to it reaches this account (even if it currently shows a default page or a 403).

**Common error:** the domain opens a different host. Fix DNS before uploading. Uploading QPOS to the wrong account will not move the domain.

---

## 3. Enable SSL

**Where:** cPanel → **SSL/TLS Status**, or **Security** → **Let's Encrypt SSL** / **AutoSSL**. The label is provider-specific. Not every host shows all three.

**What to do:** run AutoSSL, or issue a certificate for `YOURDOMAIN.com` and `www` if you use it. Wait until the certificate is active before setting `SESSION_SECURE_COOKIE=true` and forcing HTTPS.

**Expected result:** `https://YOURDOMAIN.com` loads without a browser certificate warning.

**If SSL is still pending:** you may configure files first, but do not force HTTPS redirects and do not set `SESSION_SECURE_COOKIE=true` until HTTPS works. A secure cookie on plain HTTP makes login look like a loop.

---

## 4. Upload the ZIP

**Where:** cPanel → **Files** → **File Manager**.

**Directory:** `/home/USERNAME/public_html` (the folder that already contains `cgi-bin`).

Enable **Settings** → **Show Hidden Files** before you extract. `.htaccess` and `.env` are hidden files. If hidden files stay invisible, you will think the extract failed.

**Upload:** `QPOS-cPanel-Production.zip`.

If File Manager rejects the upload because of size, use FTP or SFTP into `/home/USERNAME/` and upload the same ZIP. The size limit is set by the host, not by QPOS.

**Expected result:** the ZIP is visible in `public_html`, in the same list as `cgi-bin`.

---

## 5. Extract

**Where:** File Manager → select the ZIP → **Extract**.

**Extract to:** `/home/USERNAME/public_html`

Tick **Overwrite existing files**. The ZIP has no wrapper folder. After extract you must have:

```text
/home/USERNAME/public_html/index.php
/home/USERNAME/public_html/.htaccess
/home/USERNAME/public_html/artisan
/home/USERNAME/public_html/vendor/autoload.php
/home/USERNAME/public_html/css/app.css
```

**Expected result:** `index.php` is in the same list as `cgi-bin`. There is no new `qpos` folder. Delete a leftover `public` folder from the old extract. The ZIP does not contain `.env`, so the saved database password stays.

**If `vendor/autoload.php` is missing:** the upload was incomplete. Upload the ZIP again. Do not run `composer update`.

Delete the ZIP from the server after a successful extract if you need the disk space. Keep a copy on your computer.

---

## 6. Document root

Leave the document root on `/home/USERNAME/public_html`. Do not point it at a `public` or `qpos` folder.

**Expected result:** `https://YOURDOMAIN.com/login` opens the QPOS login page. The address bar does not contain `/public`. An old `/public/login` address redirects to `/login`.

In `.env`, set `APP_URL=https://YOURDOMAIN.com` with no `/public` at the end.

**Check:** open `https://YOURDOMAIN.com/.env`. You must get **403** or **404**, never the file contents. Details are in step 21.

---

## 7. Create `.env`

**Where:** File Manager, inside `/home/USERNAME/qpos` (or `public_html` if you used the alternate).

1. Copy `.env.cpanel.example` to `.env`. File Manager → Copy, or Terminal: `cp .env.cpanel.example .env`
2. **Edit** `.env`. Replace placeholders. Do not paste a development `.env`.

Values that must be set now:

```env
APP_NAME="QPOS"
APP_TITLE="QPOS"
APP_ENV=live
APP_KEY=
APP_DEBUG=false
APP_URL=https://YOURDOMAIN.com
SHOW_DEMO_SHOPS=false
ALLOW_REGISTRATION=false
APP_TIMEZONE=Europe/London
APP_LOCALE=en
```

Change `APP_TIMEZONE` if the shop is not in the UK. Example for Sri Lanka: `Asia/Colombo`. The code default, when this line is missing, is `Europe/London` (`config/app.php`).

Leave `APP_KEY` empty until step 10 if this is a new empty database. If you are connecting a database that already contains QPOS data from another server, paste the existing `APP_KEY` from that server's `.env` and do not generate a new one.

`APP_ENV` must be `live` if you want the scheduled jobs in `app/Console/Kernel.php`. `production` is the usual Laravel word, and it will **not** run those jobs in this project.

**Expected result:** `.env` is in the same folder as `artisan`. It is not inside `public/`.

---

## 8. Connect the existing MySQL database

Do not create a second database if the one you were given is the database QPOS should use. Do not click **Delete Database**.

**Where:** cPanel → **Databases** → **MySQL® Databases**.

**Confirm:**

1. The database is in **Current Databases**. Copy the full name, prefix included.
2. The user is in **Current Users**. Copy the full username.
3. Under **Privileged Users** / **Add User To Database**, that user is assigned to that database with **ALL PRIVILEGES**.

Put those values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_database
DB_USERNAME=cpaneluser_dbuser
DB_PASSWORD=the_password_for_that_user
```

`DB_HOST=localhost` is correct for MySQL on the same cPanel account. Use `127.0.0.1` only if the host's documentation says localhost sockets fail.

**Expected result:** the user shows ALL PRIVILEGES on that one database, and `.env` uses the full names with no spaces around `=`.

**Common error:** using the short name you typed (`qpos`) instead of `cpaneluser_qpos`. The connection then fails with "Unknown database" or "Access denied".

---

## 9. Verify dependencies and extensions

**Files:** `vendor/autoload.php` must exist. This ZIP includes `vendor`, so Composer is not required.

**PHP:** repeat the extension list from step 1 if you have not.

**Optional Terminal check** from the application root (`/home/USERNAME/qpos`):

```bash
php artisan --version
```

**Expected result:** `Laravel Framework 9.52.4` (or the same 9.52 line). Deprecation notices mean the selected PHP is too new. Switch back to 8.1 or 8.2.

**If Terminal does not exist:** continue. The web installer and the locked migration script are the no-terminal tools. Do not upload a new Composer `vendor` from an unknown source over this one.

**If `artisan` says the autoload file is missing:** re-extract the ZIP. Only if the host has Composer and `vendor/` was actually lost:

```bash
cd /home/USERNAME/qpos
composer install --no-dev --optimize-autoloader
```

Do not run `composer update`.

---

## 10. Permissions, `APP_KEY`, and writable folders

**Where:** File Manager → select each folder → **Permissions**. Or Terminal.

Folders that must be writable by PHP:

```text
storage
bootstrap/cache
public/uploads
```

Including:

```text
storage/framework/cache/data
storage/framework/sessions
storage/framework/views
storage/logs
storage/app/public
storage/app/pdf
public/uploads/temp
public/uploads/business_logos
public/uploads/documents
public/uploads/img
public/uploads/invoice_logos
```

**Permissions to try first:** directories `775`, files `664`. Owner should stay the cPanel user. Use `777` only on those writable folders if the host's PHP user cannot write with `775`, and change them back if the host allows a better value later.

**Terminal equivalent:**

```bash
cd /home/USERNAME/qpos
find storage bootstrap/cache public/uploads -type d -exec chmod 775 {} \;
find storage bootstrap/cache public/uploads -type f -exec chmod 664 {} \;
```

**APP_KEY**

- Empty database, Terminal available:

  ```bash
  cd /home/USERNAME/qpos
  php artisan key:generate
  ```

  This writes one `base64:...` value into `.env`. Run it once.

- Empty database, no Terminal: leave `APP_KEY` empty and let `/install` generate it in the next database step. Do not do both in a way that replaces a key after users exist.

- Database that already has QPOS users or encrypted settings: paste the old key. Do not run `key:generate`.

**Expected result:** `.env` contains `APP_KEY=base64:` followed by a long value, and a new file can be created in `storage/logs`.

---

## 11. Storage link

Product photos, logos, and documents in this application are stored in `public/uploads`, which is already inside the document root. They do not need a symlink.

`php artisan storage:link` creates `public/storage` → `storage/app/public` for the Laravel `public` disk. The installer does **not** create this link (`storage:link` is commented out in `InstallController`).

**Terminal:**

```bash
cd /home/USERNAME/qpos
php artisan storage:link
```

**Expected result:** `public/storage` exists and points at `storage/app/public`.

**If the command says the link already exists:** leave it.

**If Terminal is missing or the host disables symlinks:** skip this step. Confirm later that a product image uploaded to `public/uploads/img` opens in the browser. Only features that request `/storage/...` need the link.

---

## 12. Database schema — look first, then decide

**Where:** cPanel → **Databases** → **phpMyAdmin** → click the database name in the left column.

### 12a. See what is already there

Look for these tables: `migrations`, `business`, `users`, `products`, `transactions`.

**If the table list is empty** (no tables, or only tables you know are not QPOS): this database can be initialized. Take a backup anyway (phpMyAdmin → **Export** → Quick → SQL → Go). Then use either the installer or Artisan, not both.

**If those tables exist and contain shop data:** this is an existing shop.

- Do not open `/install`.
- Do not import a second full dump on top of it.
- Do not run `migrate:fresh`, `db:seed`, or any `pos:seed...` command.
- Export a backup first.
- Skip to "Existing database" below.

### 12b. Empty database — web installer (no Terminal)

1. Confirm `.env` does **not** exist yet, or delete it only because you just created an empty one and there is no shop data. The installer stops when `.env` exists.
2. Open `https://YOURDOMAIN.com/install`.
3. `public/install/index.php` checks PHP 8.1+, extensions, and write access to `storage`, `bootstrap/cache`, and `public/uploads`. Fix any red row and refresh.
4. Continue to `https://YOURDOMAIN.com/install-start` and the details form.
5. Enter the full database name, user, password, host `localhost`, port `3306`.
6. Submit. The controller writes `.env`, sets `APP_ENV=live`, `APP_DEBUG=false`, and `APP_URL` from the current URL.

**What it will do:** `migrate:fresh` drops every table, then `db:seed` loads barcodes, permissions, currencies, and sample products. That is safe only because you confirmed the database was empty.

7. Finish on the success page. Log in with the administrator the installer created and change the password.
8. Set `SHOW_DEMO_SHOPS=false` and `ALLOW_REGISTRATION=false` in `.env` if the written file still has them true. The current `.env.example` shipped in this package already sets both to false. The installer copies that file and then overwrites the database and app keys it knows about.

**Expected result:** login page loads, you can sign in, and phpMyAdmin shows `business`, `users`, `products`, and `migrations`.

### 12c. Empty database — Terminal instead of the installer

Use this when `.env` is already filled in, including `APP_KEY`.

```bash
cd /home/USERNAME/qpos
php artisan migrate --force
```

This creates tables and does not seed sample products. It does not create the first business user. After it succeeds, either register a business at `/business/register` (only if you temporarily set `ALLOW_REGISTRATION=true`) or use the installer path above on a database you are still willing to wipe.

Do not run `php artisan db:seed` unless you want sample products and you have confirmed the seeders. `DatabaseSeeder` calls `SampleProductsSeeder`.

### 12d. Existing database with QPOS tables

1. Backup (Export in phpMyAdmin, or cPanel **Backup**).
2. `.env` uses this database and the **same** `APP_KEY` as the system that created the data.
3. Terminal:

   ```bash
   cd /home/USERNAME/qpos
   php artisan migrate:status
   ```

4. Read the list. "Ran" means that migration is already recorded. "Pending" means it is not.
5. Only if you accept the pending changes:

   ```bash
   php artisan migrate --force
   ```

   That applies pending files only. It does not drop tables. A pending migration can still alter a table. If the command errors, stop. Restore the backup if it changed anything. Read `storage/logs/laravel.log`.

**No Terminal:** edit `public/cpanel_run_migrations.php`. Replace `CHANGE_ME_BEFORE_USE` with a private random string of at least 16 characters. Back up the database. Open:

```text
https://YOURDOMAIN.com/cpanel_run_migrations.php?key=YOUR_LONG_SECRET
```

Wait for `SUCCESS`. Then delete `public/cpanel_run_migrations.php` in File Manager. The script refuses the default secret, so the URL does nothing until you edit it. It runs `migrate --force`, not `migrate:fresh`.

**Do not** run the SQL files in `database/cpanel_*.sql` "just in case". Open one only when an error says a specific column is missing, and only after you have looked at that table's structure.

**Expected result for an existing shop:** the same businesses and products are still present, and `storage/logs/laravel.log` has no new SQL exception.

---

## 13. Clear and rebuild caches

Do this after `.env` is final.

**Terminal:**

```bash
cd /home/USERNAME/qpos
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

Do **not** run `php artisan route:cache`. It fails on this project (duplicate route name `logout`, plus closure routes).

**No Terminal:** in File Manager, delete the **files inside** these folders and leave the folders:

```text
bootstrap/cache/*.php
storage/framework/views/*
storage/framework/cache/data/*
```

Keep `.gitignore` files. Laravel rebuilds views and cache on the next request. Avoid `config:cache` until you can run Artisan; a stale `bootstrap/cache/config.php` from another computer would point at the wrong database. This ZIP does not include a compiled config cache.

**Expected result:** the next page load creates new files in `storage/framework/views`.

---

## 14. Scheduler

QPOS does not start its own background process. cPanel cron must call `schedule:run` every minute, and `APP_ENV` must be `live`, or these commands never start:

- `backup:clean` at 01:00
- `backup:run` at 01:30
- `pos:generateRecurringExpense` at 02:00
- `pos:autoSendPaymentReminder` at 08:00
- `pos:generateSubscriptionInvoices` at 23:30
- `pos:updateRewardPoints` at 23:45

Times follow `APP_TIMEZONE`.

`backup:run` often fails on shared hosting because `mysqldump` is not available. A failed backup job does not stop sales. Use cPanel **Backup** for the database. Leave the commented Laragon path in `config/database.php` commented.

If you do not use subscriptions, recurring expenses, reward points, or payment reminder email, the site still sells without cron. Add cron if you use any of those.

---

## 15. Cron job

**Where:** cPanel → **Advanced** → **Cron Jobs**. This menu is sometimes hidden by the host. If it is missing, ask the host to add a cron job. There is no safe always-on substitute inside QPOS.

**Schedule:** every minute. In the common form, set Minute, Hour, Day, Month, and Weekday each to `*`.

**Command** (change the user and the PHP path):

```text
/opt/cpanel/ea-php82/root/usr/bin/php /home/USERNAME/qpos/artisan schedule:run >> /dev/null 2>&1
```

If `php -v` in Terminal already prints 8.1 or 8.2, this shorter command is enough:

```text
/usr/local/bin/php /home/USERNAME/qpos/artisan schedule:run >> /dev/null 2>&1
```

**Expected result:** after a minute, nothing visible is required. A successful run is quiet. To see errors once, change the end to `>> /home/USERNAME/qpos/storage/logs/cron.log 2>&1`, reload a minute later, read the log, then point it back at `/dev/null`.

Do not add `queue:work`. `QUEUE_CONNECTION=sync`.

---

## 16. Email and other services

**Email, only if the shop sends mail**

1. cPanel → **Email** → **Email Accounts** → create `noreply@YOURDOMAIN.com` (the address is your choice).
2. Put the mailbox username and password into `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.YOURDOMAIN.com
MAIL_PORT=465
MAIL_USERNAME=noreply@YOURDOMAIN.com
MAIL_PASSWORD=the_mailbox_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="noreply@YOURDOMAIN.com"
MAIL_FROM_NAME="${APP_NAME}"
```

3. If 465 is rejected, try port `587` and `MAIL_ENCRYPTION=tls`. The host's email documentation wins.
4. Run `php artisan optimize:clear` after editing `.env`.

**Leave blank when unused:** `GEMINI_API_KEY`, AWS, Pusher, and `MYRELOAD_API_KEY`. MyReload, card gateways, and SMS are configured per business inside QPOS after login. Cash sales do not need them.

**Expected result:** a test quotation or password mail leaves the outbox, or you have deliberately left mail empty because the shop will not send mail.

---

## 17. Test login

1. Open `https://YOURDOMAIN.com`. The `/` route redirects to the login route.
2. The address must not contain `/public`.
3. Sign in with the business admin.
4. Open the dashboard (`/home`).
5. Sign out and sign in again.
6. Wrong password must stay on the login form, not a 500 page.

**Expected result:** session persists across one more click. `APP_DEBUG` is still `false` (errors are generic, not stack traces).

**419 Page Expired:** see the troubleshooting guide. Usual causes are `APP_URL` on http while the browser is on https, `SESSION_SECURE_COOKIE` mismatch, or `storage/framework/sessions` not writable.

---

## 18. Test products, stock, purchases, POS, payments, receipts

Use one low-value test item. Do this on the live database only because you are creating real rows. Delete the test documents afterward if the shop does not want them.

1. **Products** → add a product with a price and an opening stock, or receive stock through a purchase.
2. **Purchases** → add a purchase for that product. Status completed. Confirm stock increased.
3. **POS** (`/pos` or the POS menu) → add the product → pay.
4. Pay once with **Cash**. If the shop uses them, repeat a small sale or a split payment with **Card**, **Cheque**, **Bank Transfer**, and **Other**. Those types are defined in `app/Utils/Util.php`. **Credit note** is for returns, not a normal tender.
5. Complete the sale. The receipt preview or print dialog must show the invoice number and the total.
6. Open the sale from the sales list and print or download the PDF again.

**Expected result:** the sale is listed, stock decreased, and the receipt HTML or PDF shows the same total.

Browser print uses the cashier's computer. Direct "printer" mode needs the local bridge in the printing section of `DEPLOYMENT_NOTES.md`. A missing local bridge is not a failed cPanel install if browser print works.

---

## 19. Test returns, expenses, reports, uploads, and the shop mode

1. **Sell return** against the test sale. Stock should move back.
2. **Expenses** → add a small expense and confirm it appears in the expense list.
3. Open **Reports** that the shop actually uses (profit/loss, stock, sales). A heavy report that times out needs a higher `max_execution_time` or `memory_limit`, not a code change.
4. Upload a product image or a business logo. The file should land in `public/uploads/img` or `public/uploads/business_logos` and the page should show it.
5. Download a purchase or sale document link if you attached one (`public/uploads/documents`).
6. Shop mode is stored on the business, not in this ZIP. In Business Settings, confirm the mode the shop should use. The code supports normal retail, tyre shop, mobile shop, vehicle modification, vehicle spare parts, and hardware shop (`app/Utils/ShopModeUtil.php`). Test the extra fields only for the mode that is turned on. Do not run a shop-mode seeder to "enable" it.

**Expected result:** each screen you opened returns data or an empty state, not HTTP 500.

---

## 20. Read the log

**Where:** File Manager → `/home/USERNAME/qpos/storage/logs/laravel.log`.

Also, if the host offers it: cPanel → **Metrics** → **Errors**. That is the Apache/PHP error log. Some hosts move or hide it.

**Expected result:** no repeating `SQLSTATE`, `Permission denied`, or `Class not found` after the tests above.

If you turn `APP_DEBUG=true` to see one error, set it back to `false` and clear config cache before anyone else uses the site.

---

## 21. Confirm private files are not public

In a browser, these must **not** show source code, passwords, or a directory listing:

```text
https://YOURDOMAIN.com/.env
https://YOURDOMAIN.com/../.env
https://YOURDOMAIN.com/vendor/autoload.php
https://YOURDOMAIN.com/storage/logs/laravel.log
https://YOURDOMAIN.com/composer.json
https://YOURDOMAIN.com/database/migrations
```

**Expected result:** 403 or 404.

If `.env` downloads, the document root is the project folder and the root `.htaccess` is not being applied. Move the document root to `qpos/public` or ask the host to allow `.htaccess`.

After a successful empty-database install you may delete `public/cpanel_run_migrations.php` if you did not need it. You may also remove `public/install/index.php` later so the server-check page is gone. Do not delete `public/index.php`.

---

## Order of operations (short)

1. PHP 8.1 or 8.2 and extensions.
2. Domain and HTTPS.
3. Upload and extract to `/home/USERNAME/qpos`.
4. Document root `/home/USERNAME/qpos/public`.
5. `.env` from `.env.cpanel.example`, `APP_DEBUG=false`, `APP_ENV=live`, `APP_URL` without `/public`.
6. Existing database name, user, and ALL PRIVILEGES.
7. Writable `storage`, `bootstrap/cache`, `public/uploads`.
8. Backup the database.
9. Inspect tables. Installer only if empty. `migrate --force` only for pending migrations on a backup.
10. `storage:link` if Terminal exists.
11. `optimize:clear`, `config:cache`, `view:cache`. Never `route:cache`.
12. Cron only if you need the scheduled commands.
13. Log in and run the shop tests.
14. Confirm `.env` is not downloadable.
