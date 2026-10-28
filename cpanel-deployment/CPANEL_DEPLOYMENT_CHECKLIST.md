# QPOS cPanel deployment checklist

Use this before upload and again after the site is on the server. Items marked "verified while building the package" were checked on the packaging machine. Items marked "on the server" were not run against the live host.

## Pre-deployment (packaging machine)

- [x] Laravel 9.52.4 boots (`php artisan --version` and a bootstrap script).
- [x] `vendor/autoload.php` loads.
- [x] 753 routes register, including `login`.
- [x] Views exist: `auth.login`, `sale_pos.create`, `sale_pos.receipts.classic`, `labels.browser_print`.
- [x] `public/index.php` points at `../vendor/autoload.php` and `../bootstrap/app.php`.
- [x] `public/.htaccess` and the root `.htaccess` exist.
- [x] Compiled assets exist: `public/css/app.css`, `public/js/app.js`, `public/js/pos.js`, `public/js/printer.js`, `public/mix-manifest.json`.
- [x] 331 migration files are in `database/migrations`.
- [x] `.env.cpanel.example` has placeholders only. `APP_DEBUG=false`, `APP_ENV=live`.
- [x] Development `.env` is not part of the package plan (`APP_DEBUG=true` and a local URL stay on the build machine).
- [x] `php artisan route:cache` fails on duplicate route name `logout`. It must not be used.
- [x] `public/cpanel_run_migrations.php` refuses the default secret.
- [x] Local MySQL was **not** running. `php artisan migrate:status` failed with connection refused. No migration was applied. The production database was not inspected.
- [x] `QPOS-cPanel-Production.zip` opens (about 78 MB, 22,617 entries). Files sit at the archive root, including `artisan`, `public/cpanel_setup.php`, and `vendor/autoload.php`. `.env` is absent. 331 migrations are included. Large unused mPDF fonts were left out.

## Before you click Extract on the server

- [ ] You have a cPanel or phpMyAdmin export of the existing database, stored off the server.
- [ ] You know whether that database already contains `business`, `users`, `products`, or `transactions`.
- [ ] If those tables exist, you will not open `/install`.
- [ ] PHP for the domain is 8.1 or 8.2.
- [ ] Extensions from the installation guide are enabled, including `pdo_mysql` and `mysqli`.
- [ ] `max_input_vars` is 10000.
- [ ] HTTPS certificate is active, or you will keep `SESSION_SECURE_COOKIE` off until it is.
- [ ] File Manager is set to show hidden files.

## Install

- [ ] ZIP extracted to `/home/USERNAME/qpos/` with `artisan` and `public/index.php` one folder apart. No `qpos/qpos` nesting.
- [ ] `vendor/autoload.php` is present on the server.
- [ ] Document root is `/home/USERNAME/qpos/public`, or the alternate `public_html` layout from the installation guide is in place.
- [ ] `.env` created on the server from `.env.cpanel.example`.
- [ ] `APP_URL` is `https://YOURDOMAIN.com` with no `/public`.
- [ ] `APP_DEBUG=false` and `APP_ENV=live`.
- [ ] `SHOW_DEMO_SHOPS=false` and `ALLOW_REGISTRATION=false`.
- [ ] `DB_*` uses the full cPanel database name and username.
- [ ] `APP_KEY` generated once for an empty database, or copied for an existing database. Not regenerated afterward.
- [ ] `storage`, `bootstrap/cache`, and `public/uploads` are writable.
- [ ] `php artisan storage:link` succeeded, or you recorded that symlinks are disabled and product images use `public/uploads`.
- [ ] Schema decision recorded: installer (empty database only) **or** `migrate:status` plus pending `migrate --force` **or** no migration because the schema is already current.
- [ ] `php artisan route:cache` was not run.
- [ ] `config:cache` / `view:cache` ran only after `.env` was final, or compiled caches were left for Laravel to rebuild.
- [ ] Cron added only if subscriptions, recurring expenses, reward points, payment reminders, or backup commands are required.
- [ ] Mail filled in only if the shop sends mail.
- [ ] `public/cpanel_run_migrations.php` deleted if it was unlocked and used.

## Security after install

- [ ] `https://YOURDOMAIN.com/.env` does not download (403 or 404).
- [ ] `https://YOURDOMAIN.com/vendor/autoload.php` does not download.
- [ ] `https://YOURDOMAIN.com/storage/logs/laravel.log` does not download.
- [ ] `APP_DEBUG` is false.
- [ ] Demo login tiles are not on the login page.
- [ ] Public business registration is off unless you chose to allow it.

## Functional test (on the server, after install)

Do these in a browser. They were not run while building the ZIP.

- [ ] Login and logout.
- [ ] Dashboard loads for that user.
- [ ] A user without permission is refused on a screen they should not open.
- [ ] Create a product and find it in the product list.
- [ ] Stock quantity changes after a purchase and after a sale.
- [ ] Create a purchase.
- [ ] Create a POS sale.
- [ ] Cash payment completes. Card, cheque, bank transfer, and other are tested only if the shop uses them.
- [ ] Receipt preview or browser print shows the right total.
- [ ] PDF download works if the shop uses PDF invoices.
- [ ] Sell return adjusts stock.
- [ ] An expense can be saved and listed.
- [ ] A report the shop uses opens.
- [ ] A product image or logo uploads and is visible from `public/uploads`.
- [ ] A document download works if you uploaded one.
- [ ] The business shop mode matches the shop (normal retail, tyre, mobile, vehicle modification, spare parts, or hardware). Extra fields for other modes are not required.
- [ ] `storage/logs/laravel.log` has no new fatal error after the tests.

## Printing (cashier computer, not cPanel)

- [ ] Browser receipt print dialog opens on the cashier PC.
- [ ] If the location uses printer type `printer`, the local program on port 6441 is running on that PC. The cPanel server was not expected to see the printer.
- [ ] Label printing uses the browser label page. BarTender was not required for this deployment.

## Sign-off

| | |
|--|--|
| Database backup file name and date | |
| Document root used | |
| PHP version selected | |
| Schema action taken (none / installer / pending migrate) | |
| `APP_KEY` newly generated or reused | |
| Tester | |
| Date | |
