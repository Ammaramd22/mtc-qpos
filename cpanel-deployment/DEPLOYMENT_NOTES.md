# QPOS deployment notes

These notes describe the QPOS tree that was inspected on this machine on 28 October 2026. They are not a generic Laravel checklist. Nothing in this package was uploaded to the live domain, and the cPanel database was not opened.

## Application

| Item | Verified value |
|------|----------------|
| Framework | Laravel `9.52.4` (`composer.lock`, `php artisan --version`) |
| PHP constraint in `composer.json` | `^8.0` |
| PHP actually required by locked Symfony components | `>= 8.1` (`vendor/symfony/http-kernel/composer.json`) |
| Installer requirement | PHP 8.1 or newer (`public/install/index.php` and `InstallController::checkServer`) |
| Recommended production PHP | **8.1 or 8.2** |
| Entry point | `public/index.php` loads `../vendor/autoload.php` and `../bootstrap/app.php` |
| Front controller | `public/.htaccess` rewrites non-files to `index.php` |
| Alternate front controller | Root `.htaccess` rewrites the site into `public/` when the document root cannot be changed |
| Routes | `routes/web.php` (includes `routes/install_r.php`), `routes/api.php`, `routes/console.php` |
| Views | Blade under `resources/views` |
| Frontend | Prebuilt files in `public/css`, `public/js`, `public/fonts`, `public/img`. `public/mix-manifest.json` is present. Node is not required on the server. `node_modules` is not in this project. |
| Modules folder | `Modules/` exists and is in the Composer PSR-4 map. It has no module packages in this tree. |
| Languages | `lang/ar`, `ce`, `de`, `en`, `es`, `fr`, `he`, `hi`, `id`, `lo`, `nl`, `ps`, `pt`, `ro`, `sq`, `tr`, `vi` |
| Migrations on disk | 331 files in `database/migrations` |
| Default filesystem disk | `local`, rooted at `public/uploads` (`config/filesystems.php`) |
| Public disk | `storage/app/public`, exposed only after `php artisan storage:link` creates `public/storage` |
| mPDF temp for invoices | `public/uploads/temp` (`SellPosController`) |
| mPDF temp for some ledgers | `storage/app/pdf` (`config/constants.php`) |
| Queue | `QUEUE_CONNECTION=sync`. No queue worker is required for normal POS use. |
| Cache | `CACHE_DRIVER=file` → `storage/framework/cache/data` |
| Session | `SESSION_DRIVER=file` → `storage/framework/sessions` |
| Logs | `storage/logs/laravel.log` |
| App version string | `config/author.php` → `7.0` (used by the old update screen) |

## PHP version

Select **PHP 8.1 or 8.2** in cPanel.

This package was booted locally with PHP **8.5.9**. Artisan started, but PHP printed deprecation warnings from Monolog and Laravel 9. That is not a supported production choice. Do not select PHP 8.4 or 8.5 for this application. PHP 8.0 is below the locked Symfony requirement. PHP 8.3 was not executed here.

## PHP extensions

The installer refuses to continue unless these are loaded:

`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `curl`, `zip`, `gd`

`public/install/index.php` also requires:

`bcmath`, `fileinfo`, `json`

Laravel's MySQL connection requires:

`pdo_mysql`

The installer connection test uses `mysqli_connect`, so the **web installer also needs `mysqli`**. Artisan migrate uses PDO and does not need `mysqli`.

Enable these as well when the host offers them. mPDF, Excel, phone parsing, and image uploads are more reliable with them:

`intl`, `iconv`, `sodium`, `exif`, `zlib`

On the PHP 8.5 CLI used for the local checks, these were present: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `iconv`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `zip`, `zlib`.

These were **absent** on that CLI: `mysqli`, `intl`, `sodium`, `exif`. Absence on this Windows CLI does not mean the cPanel account lacks them. Confirm them in the cPanel PHP selector.

## PHP limits

Set these in cPanel **MultiPHP INI Editor** (or the host's PHP settings). `public/.htaccess` tries to set `max_input_vars`, `post_max_size`, and `upload_max_filesize` only when `mod_php`, `mod_php7`, or `mod_php8` is loaded. Most current cPanel accounts use PHP-FPM or LSAPI, so the `.htaccess` values are ignored and the INI editor is the place that works.

```text
max_input_vars = 10000
post_max_size = 64M
upload_max_filesize = 64M
memory_limit = 256M
max_execution_time = 120
```

Business Settings is a large form. `max_input_vars` below 10000 can save the early tabs and silently drop later tabs.

## Environment values that matter

Create `.env` on the server from `.env.cpanel.example`. Do not upload the development `.env`.

| Setting | Production value | Why |
|---------|------------------|-----|
| `APP_ENV` | `live` | `app/Console/Kernel.php` schedules jobs only when `config('app.env')` is `live`. `production` boots Laravel but skips those jobs. |
| `APP_DEBUG` | `false` | `config/app.php` defaults debug to `true` when the variable is missing. The live site must set it explicitly. |
| `APP_URL` | `https://YOURDOMAIN.com` | No `/public` suffix. Used for URLs, installer, and secure-cookie detection. |
| `APP_KEY` | generate once, then keep | New empty database: `php artisan key:generate` or let `/install` generate it. Existing data: reuse the key from the system that encrypted that data. |
| `APP_TIMEZONE` | shop timezone | Omitted value falls back to `Europe/London` in `config/app.php`. |
| `APP_LOCALE` | `en` | Fallback locale is also `en`. Other locales exist under `lang/`. |
| `SHOW_DEMO_SHOPS` | `false` | `true` shows demo login tiles. |
| `ALLOW_REGISTRATION` | `false` | `true` leaves `/business/register` open. |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | `localhost` | Usual when MySQL is on the same cPanel account. Some hosts require `127.0.0.1`. |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | full cPanel name | Often `cpaneluser_dbname`, not the short name you typed. |
| `DB_USERNAME` | full cPanel user | Often `cpaneluser_dbuser`. |
| `DB_PASSWORD` | that user's password | |
| `CACHE_DRIVER` | `file` | Do not select redis or memcached unless that service is actually provisioned. |
| `SESSION_DRIVER` | `file` | Needs `storage/framework/sessions` writable. |
| `SESSION_SECURE_COOKIE` | `true` | Required on HTTPS. If this is `true` while you are still on `http://`, login cookies are dropped and the site looks like a redirect loop. |
| `SESSION_SAME_SITE` | ignored | `config/session.php` hardcodes `same_site` to `lax`. |
| `QUEUE_CONNECTION` | `sync` | Jobs run inside the web request. Do not add a worker unless you later change this. |
| `FILESYSTEM_DISK` | `local` | Product and business files go to `public/uploads`. |
| `LOG_LEVEL` | `error` | |

Mail is required only if you send quotations, notifications, or the scheduled payment reminder. Leave the password blank until the mailbox exists. Use the mailbox created in cPanel **Email Accounts**. Typical cPanel SMTP is `mail.YOURDOMAIN.com`, port `465`, encryption `ssl`. Some hosts use port `587` and `tls`. That is host-specific.

Optional integrations present in config, not required for a shop to sell:

- MyReload (`config/myreload.php`) — each business stores its own key in settings
- Gemini (`GEMINI_API_KEY`)
- PayPal, Stripe, Paystack, Razorpay, Pesapal, MyFatoorah
- Twilio, Pusher, AWS S3, Dropbox
- WooCommerce client library

Leave those credentials empty until that feature is switched on.

## Directory structure to use

Preferred, when the host lets you change the domain document root:

```text
/home/USERNAME/qpos/                 application root (not public)
  app/ bootstrap/ config/ database/
  lang/ public/ resources/ routes/
  storage/ vendor/
  artisan
  .env                               created on the server, not in the ZIP
  .htaccess                          used only by the alternate layout
  public/                            DOCUMENT ROOT
    index.php
    .htaccess
    css/ js/ fonts/ img/ uploads/
```

In cPanel this control is usually **Domains → the domain → Document Root**, or the Document Root field on **Addon Domains** / **Subdomains**. The path must end in `/qpos/public`.

Some accounts cannot change the document root and can only serve `public_html`. In that case extract this project so the root `.htaccess` sits directly inside `public_html` next to `app/` and `public/`. Do not copy only the `public` folder into `public_html` and leave `vendor` somewhere else unless you also edit `public/index.php`, which this package does not require. Do not nest a second `qpos` folder inside `qpos`.

The root `.htaccess` forbids direct requests for `app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `storage`, `vendor`, `lang`, `tests`, and `Modules`, and forbids downloading `.env`, Composer files, `artisan`, SQL, logs, and shell scripts. That protection depends on Apache `AllowOverride` and `mod_rewrite`. Prefer the document-root method so those files are not under the web root at all.

## Writable directories

The PHP user must be able to write:

- `storage/` (including `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, `storage/logs`, `storage/app/public`, `storage/app/pdf`)
- `bootstrap/cache/`
- `public/uploads/` (including `public/uploads/temp`, `business_logos`, `documents`, `img`, `invoice_logos`)

`775` on directories and `664` on files is the usual shared-hosting choice when the cPanel user and the PHP user are in the same group. If uploads still fail, the host may require `755`/`644` or, less desirably, `777` on those directories only. Do not chmod `vendor` or `.env` to `777`.

`public/storage` is a symlink created by `php artisan storage:link`. Core product images do not depend on it; they are stored under `public/uploads`. Create the link anyway when Terminal is available. If the host disables `symlink()`, the POS still runs and product images in `public/uploads` still load.

## Database

The database already exists. Do not drop it, recreate it, or run a seeder against it unless you have confirmed it is empty and you intend to load sample data.

There is **no full QPOS schema dump** in this project. Schema comes from `database/migrations`.

`database/cpanel_*.sql` files are small one-off patches (shop mode, tyre size, trade-ins, and similar). Do not import them until you have looked at the table in phpMyAdmin and confirmed the column or permission is missing. A duplicate-column error means that change is already present.

### What the installer really does

`InstallController::runArtisanCommands()` runs:

```text
php artisan migrate:fresh --force
php artisan db:seed --force
```

`migrate:fresh` drops all tables. `DatabaseSeeder` then calls `BarcodesTableSeeder`, `PermissionsTableSeeder`, `CurrenciesTableSeeder`, and `SampleProductsSeeder`. Other shop seeders (`SeedTyreShop`, `SeedMobileShop`, and the rest) are separate commands and must not be run on a database that already has real sales.

`/install` aborts with 404 once `.env` exists (`InstallController::isInstalled`). `public/install/index.php` is a pre-Laravel check and exits early when `.env` exists. Deleting `.env` to "unlock" the installer on a database that already has data will destroy that data if you finish the wizard.

`/install/update` is a different action. It compares `config/author.php` version `7.0` with a `db_version` system row and, only when the app version is greater, runs `migrate --force` (not `migrate:fresh`), `module:publish`, and `passport:install --force`. Do not open it unless you know this database came from an older QPOS of the same lineage and you have a backup.

### Safe decision

1. Back up with cPanel **Backup Wizard** or phpMyAdmin **Export**.
2. In phpMyAdmin, open the existing database and look for `migrations`, `business`, `users`, `products`, `transactions`.
3. If those tables are absent, the database is empty enough for `/install` or for `php artisan migrate --force` followed by creating the business in the installer. Still take the backup first because `migrate:fresh` drops whatever is there.
4. If those tables exist, configure `.env` only. Run `php artisan migrate:status`. Apply `php artisan migrate --force` only for rows marked Pending, after the backup. Never run `migrate:fresh`, `db:seed`, or `db:wipe`.

Local `php artisan migrate:status` was attempted and **failed**. MySQL on this computer refused the connection. The production database was not inspected, so this package cannot tell you whether migrations are pending.

## Scheduler

When `APP_ENV=live`, `app/Console/Kernel.php` schedules:

| Time | Command |
|------|---------|
| 01:00 daily | `backup:clean` |
| 01:30 daily | `backup:run` |
| 02:00 daily | `pos:generateRecurringExpense` |
| 08:00 daily | `pos:autoSendPaymentReminder` |
| 23:30 daily | `pos:generateSubscriptionInvoices` |
| 23:45 daily | `pos:updateRewardPoints` |

Cron, once a minute, from the application root:

```text
* * * * * /usr/local/bin/php /home/USERNAME/qpos/artisan schedule:run >> /dev/null 2>&1
```

Replace the PHP binary with the 8.1 or 8.2 binary shown by the host. On EasyApache that is often:

```text
/opt/cpanel/ea-php82/root/usr/bin/php
```

`backup:run` uses Spatie Backup and expects `mysqldump`. `config/database.php` contains a **commented** Windows Laragon path. Leave that line commented. On many shared hosts `mysqldump` is missing, so `backup:run` fails even when cron is correct. Use cPanel backups for the database. The other scheduled commands are ordinary Artisan commands and do not need `mysqldump`.

`APP_ENV=demo` would schedule `pos:dummyBusiness`, which the command file describes as deleting business data and creating a dummy business. Never set `APP_ENV=demo` on this server.

## Commands that are safe after `.env` is correct

```text
cd /home/USERNAME/qpos
php artisan key:generate
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan migrate:status
```

`key:generate` only on a new install, before users exist. `config:cache` after `.env` is final. If you edit `.env` later, run `php artisan optimize:clear` or the new values are ignored.

## Commands that must not be run on an existing shop

```text
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
php artisan db:seed
php artisan route:cache
php artisan pos:dummyBusiness
```

`php artisan route:cache` was executed locally and **failed**:

```text
Unable to prepare route [logout] for serialization. Another route has already been assigned name [logout].
```

`routes/web.php` and `routes/api.php` also contain closures. The route cache was cleared afterward (`php artisan route:clear`). Do not run `route:cache` on the server. `config:cache` and `view:cache` are the ones to use.

Shop seed commands such as `pos:seedTyreShop` and `pos:seedMobileShop` insert demo businesses. Do not run them against production.

## Printing

Uploading QPOS to cPanel does not attach the server to a printer on the cashier's PC.

- **Browser receipt.** `SellPosController` renders a Blade receipt (`sale_pos.receipts.*`) and `public/js/pos.js` prints it with the browser. The cashier chooses the printer in the browser dialog. This works against a remote host.
- **Server PDF.** Invoice download uses mPDF with `tempDir` set to `public/uploads/temp`. The PDF is generated on the server and downloaded. The cashier still prints it locally.
- **Direct printer.** If the location's `receipt_printer_type` is `printer`, the browser opens `ws://127.0.0.1:6441` (`public/js/printer.js`) and sends the receipt to software on **that computer**. The cPanel server never opens the USB port. Windows, Linux, and network printer records in the `printers` table are instructions for that local bridge, not for the host.
- **Barcode labels.** Business Settings forces `enable_bartender_export` to `0`. The supported label path is browser print (`LabelsController::browserPrint` → `labels.browser_print`). BarTender on a Windows PC is not started by this host.
- **Offline package.** `offline-package/` is a local Windows/Laragon kit. It is not part of the cPanel ZIP and is not required for the hosted site.

## No-Terminal package

`public/cpanel_setup.php` is the browser installer for accounts with no Terminal. It writes `.env`, generates `APP_KEY`, creates folders, clears file caches, and can run `migrate --force`. It does not call `migrate:fresh`. On an empty database it can also load `BarcodesTableSeeder`, `PermissionsTableSeeder`, and `CurrenciesTableSeeder`, then turns public registration on so `/business/register` can create the first shop. Delete the file after login.

The ZIP extracts into `public_html` with `artisan` next to the host's `cgi-bin` and `php.ini`. The root `.htaccess` sends visitors into `public/`.

Large mPDF fonts that are not used for Latin or Arabic receipts (including `Sun-ExtA.ttf` and `Sun-ExtB.ttf`) are omitted from this ZIP to reduce disk use. `DejaVuSans.ttf` remains. A receipt that needs a missing CJK font can fail to render that script. The fonts are still in the development tree.

## What was left out of the ZIP

- `.env` and any local database password or `APP_KEY`
- `offline-package/`
- one-off public scripts (`qpos_patch_v4.php`, `deploy.php`, `diagnose.php`, `fix.php`, `cpanel_seed_shop_modes.php`, and the other patch/diagnose files). Several contained default keys or old host names
- older `CPANEL_*.txt` / `CPANEL_*.md` notes that describe those one-off uploads
- `storage/logs/*.log`, cached views, session files, and file-cache data
- `.git`, editor folders, and tests

`public/cpanel_run_migrations.php` is included and locked. `public/install/` is included because a genuinely empty database needs it.

`vendor/` is included, including development packages that are already installed locally. `config/debugbar.php` has `'enabled' => false`. Do not set `APP_DEBUG=true` to compensate. Composer on the server is unnecessary while `vendor/autoload.php` is present.

## Tests actually run

| Check | Result |
|-------|--------|
| `vendor/autoload.php` loads | Passed |
| `php artisan --version` | Passed: Laravel Framework 9.52.4 |
| Application bootstrap, route and view lookup | Passed: 753 routes, `login` route present, views `auth.login`, `sale_pos.create`, `sale_pos.receipts.classic`, `labels.browser_print` present |
| Compiled assets | Passed: `public/css/app.css`, `public/js/app.js`, `public/js/pos.js`, `public/js/printer.js`, `public/mix-manifest.json` |
| `php artisan route:cache` | Failed, as expected, duplicate `logout` name. Cache cleared. |
| `php artisan migrate:status` | Failed: local MySQL refused the connection. No migration was executed. |
| Production database | Not contacted |
| Live domain, login, POS sale, upload, or print | Not executed |
| ZIP open and layout | Passed. PHP ZipArchive opened `QPOS-cPanel-Production.zip` (about 78 MB, 22,617 entries). Archive root contains `artisan`, `public/index.php`, `public/cpanel_setup.php`, and `vendor/autoload.php`. No `.env`. No `Sun-ExtA.ttf`. 331 migration files. |

## Known limits

- Shared hosting may disable `symlink()`, `exec`, `proc_open`, or `mysqldump`. POS pages and `public/uploads` still work. Spatie `backup:run` and some Passport/install shell steps may not.
- `passport:install` is not part of go-live for browser POS. It is only invoked by the version-update screen.
- Payment gateways and MyReload need outbound HTTPS from the host. A shop that only takes cash does not need them.
- The cashier's browser must be allowed to open a print dialog. A locked-down kiosk browser can block that even when the server is fine.
