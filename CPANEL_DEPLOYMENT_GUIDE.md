# QPOS — Exact cPanel Deployment Guide

This guide matches the **actual QPOS project files** in this package.  
Do not point the domain at the Laravel project root without the included root `.htaccess`, and prefer pointing the document root at `public/`.

---

## Recommended folder architecture

### Preferred (safest): document root = `public`

```text
/home/USERNAME/
  qpos/                          ← Laravel project (NOT public)
    app/
    bootstrap/
    config/
    database/
    Modules/
    resources/
    routes/
    storage/
    vendor/
    artisan
    .env                         ← create on server (not in ZIP)
    public/                      ← web document root points HERE
      index.php
      .htaccess
      css/ js/ uploads/ ...
```

In cPanel → **Domains** (or Subdomains / Addon Domains) → set **Document Root** to:

```text
/home/USERNAME/qpos/public
```

This keeps `.env`, `app/`, `vendor/`, `storage/`, etc. outside the web root.

### Alternate (if you cannot change document root)

Extract the whole project into `public_html/` so that:

```text
/home/USERNAME/public_html/
  app/
  ...
  public/
  .htaccess          ← root .htaccess already rewrites to /public
  .env
```

The project root `.htaccess` rewrites all traffic into `public/` and blocks direct access to `.env` / `artisan` / composer files. Prefer the first option when cPanel allows changing the document root.

**Never** leave `.env`, `vendor/`, or `storage/logs` directly browsable without rewrite protection.

---

## Differences vs `CPANEL_UPLOAD.txt` / `.env.cpanel.example`

| Item | Existing file says | What this project actually needs |
|------|--------------------|----------------------------------|
| Extract location | “into public_html” | Prefer `~/qpos` + document root `~/qpos/public` |
| `APP_ENV` | `.env.cpanel.example` uses `production` | Scheduler in `app/Console/Kernel.php` only runs when `APP_ENV=live`. Use **`live`** on production if you want backups / recurring invoices / reward points / payment reminders / recurring expenses. |
| `route:cache` | Recommended | **Skip** — `routes/web.php` and `routes/api.php` contain closure routes; `php artisan route:cache` is unsafe/fails with closures. Use `config:cache` and `view:cache` only. |
| Seed Mobile Shop | Optional | Only if you want the Mobile Shop demo. Tyre House can use installer / business register / tyre seeder instead. |
| PHP | Not stated clearly | Install checker (`public/install/index.php`) requires **PHP 8.1+**. Locked Symfony packages also require **≥ 8.1**. |
| Uploads | Not emphasized | Default disk is **`public/uploads`** (`config/filesystems.php`), not only `storage/app`. Keep `public/uploads` writable. |
| Migrations | Always `migrate --force` | Prefer **web installer** for a fresh DB, or import an SQL dump if moving an existing shop. Do not migrate blindly over a filled database. |

---

## Project requirements checklist

```text
Laravel:                 9.52.x (composer.lock)
PHP Version:             8.1 or 8.2 (recommended). Minimum from install UI: 8.1
MySQL Version:           MySQL 5.7+ / MariaDB 10.3+ (cPanel default is fine)
Composer:                Required only if vendor/ is missing (this ZIP includes vendor/)
Node/npm on server:      NOT required (assets already built in public/)
Document root:           .../qpos/public
Storage writable:        storage/ , bootstrap/cache/ , public/uploads/
Cron:                    Recommended when APP_ENV=live (see Cron section)
Queue worker:            NOT required (QUEUE_CONNECTION=sync)
```

### Required PHP extensions

Enable in cPanel → **Software** → **Select PHP Version** → **Extensions**:

| Extension | Why |
|-----------|-----|
| `bcmath` | Laravel / barcode / math |
| `ctype` | Laravel |
| `curl` | Guzzle, SMS, MyReload, Gemini, gateways |
| `dom` | DomPDF / XML |
| `fileinfo` | Uploads / Flysystem |
| `gd` | Images, barcodes, DomPDF |
| `json` | Laravel / APIs |
| `mbstring` | Laravel / Excel / mPDF |
| `openssl` | HTTPS, encryption, Passport |
| `pdo` | Database |
| `pdo_mysql` | MySQL |
| `tokenizer` | Blade |
| `xml` | Excel / XML |
| `zip` | Excel, backups, module zip upload |

Strongly recommended if available: `intl`, `iconv`, `sodium`, `exif`.

### PHP settings (MultiPHP INI Editor)

```text
max_input_vars = 10000
post_max_size = 64M
upload_max_filesize = 64M
memory_limit = 256M (or 512M if reports/PDF fail)
max_execution_time = 120
```

(`public/.htaccess` already tries to set some of these when `mod_php` allows it; many cPanel hosts use PHP-FPM — set them in MultiPHP INI.)

---

## Database initialization — which method to use

This package contains:

- **325 migrations** under `database/migrations/`
- **Seeders** (permissions, demo shops) under `database/seeders/`
- **Web installer** routes: `/install` → `public/install/` → `/install-start`
- **No full application SQL dump** for a complete empty schema (only a tiny helper SQL under `public/exports/`)

### Path A — Fresh install (recommended for new cPanel site)

1. Create empty MySQL database + user in cPanel.  
2. Upload/extract files, set document root, create `.env` **or** leave `.env` missing and use the installer.  
3. Open `https://YOURDOMAIN.com/install`  
4. Complete the installer (writes `.env`, runs migrations, creates first business/admin).

### Path B — Manual `.env` + Artisan migrate (SSH/Terminal)

1. Create empty DB.  
2. Create `.env` from `.env.cpanel.example` (with corrections below).  
3. `php artisan key:generate`  
4. `php artisan migrate --force`  
5. Create admin: register at `/business/register` (if `ALLOW_REGISTRATION=true`) **or** run a seeder you intentionally choose.

### Path C — Moving an existing live/local shop

1. Export SQL from the old server (phpMyAdmin).  
2. Import into the new cPanel database.  
3. Do **not** run `migrate --force` unless you know pending migrations only.  
4. Copy `public/uploads/` from the old server.  
5. Set `.env` DB_* and `APP_URL` to the new host.  
6. Prefer **keeping the same `APP_KEY`** from the old `.env` (see APP_KEY section).

---

## `.env` configuration

**Do not upload your local `.env`.** It contains local secrets and `APP_DEBUG=true`.

On the server, copy `.env.cpanel.example` to `.env`, then set:

### Must change

```env
APP_NAME="QPOS"
APP_ENV=live
APP_KEY=                    # generate with artisan OR paste from old production
APP_DEBUG=false
APP_URL=https://YOURDOMAIN.com

SHOW_DEMO_SHOPS=false
ALLOW_REGISTRATION=false    # set true only if you want public business signup

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_db_name_exactly
DB_USERNAME=cpanel_db_user_exactly
DB_PASSWORD=your_db_password
```

### Keep as in template unless you know you need otherwise

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### Optional (only if you use them)

```env
ADMINISTRATOR_USERNAMES=softwareowner
MAIL_*                      # SMTP for email
GEMINI_API_KEY=             # AI assistant
MYRELOAD_*                  # platform defaults; shops can set their own keys in UI
AWS_* / PUSHER_*            # only if using S3/Pusher
```

**Important:** `APP_URL` must be the public HTTPS URL **without** `/public` at the end.

**Important:** Use `APP_ENV=live` (not `production`) if you want the Laravel scheduler jobs defined in `app/Console/Kernel.php` to run.

---

## APP_KEY

| Situation | What to do |
|-----------|------------|
| Brand-new install | Leave empty, then run `php artisan key:generate` **once** |
| Moving existing database | Copy the **same** `APP_KEY` from the old `.env` |
| After users already exist | Do **not** change `APP_KEY` — encrypted values / cookies / some tokens can break |

Never commit or share production `APP_KEY`.

---

## Composer

This ZIP **includes `vendor/`** (~217 MB) so the site can run without Composer on the server.

### Option A — cPanel has Terminal/SSH + Composer

Only needed if `vendor/` is missing or autoload is broken:

```bash
cd ~/qpos
composer install --no-dev --optimize-autoloader
```

Do **not** run `composer update`.

### Option B — No Composer on server

Upload this ZIP as-is (with `vendor/`). Skip Composer.

---

## Storage, uploads, permissions

### Where files live in this project

| Content | Location |
|---------|----------|
| Product / business / document uploads (default disk) | `public/uploads/` |
| Laravel `public` disk | `storage/app/public` (linked as `public/storage`) |
| mPDF temp | `storage/app/pdf` |
| Sessions/cache/views | `storage/framework/...` |
| Logs | `storage/logs/laravel.log` |
| Spatie backups | per `config/backup.php` (uses configured disks) |

### Commands (SSH/Terminal), from project root

```bash
cd ~/qpos
php artisan storage:link
```

Creates `public/storage` → `storage/app/public`.  
Still keep **`public/uploads` writable** — that is the main upload path.

### Permissions (cPanel-safe)

```bash
# typical shared hosting
find storage bootstrap/cache public/uploads -type d -exec chmod 775 {} \;
find storage bootstrap/cache public/uploads -type f -exec chmod 664 {} \;
```

Owner should be your cPanel user. Prefer **775/664**, not 777, unless your host requires 777 for PHP to write.

---

## Cache commands (safe for this project)

```bash
cd ~/qpos
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
# DO NOT run: php artisan route:cache
```

If settings look “stuck” after editing `.env`:

```bash
php artisan optimize:clear
```

---

## Cron (required for scheduled features when `APP_ENV=live`)

`app/Console/Kernel.php` schedules (only if `APP_ENV === 'live'`):

- `backup:clean` 01:00  
- `backup:run` 01:30  
- `pos:generateSubscriptionInvoices` 23:30  
- `pos:updateRewardPoints` 23:45  
- `pos:autoSendPaymentReminder` 08:00  
- `pos:generateRecurringExpense` 02:00  

### cPanel Cron Jobs

```text
* * * * * cd /home/USERNAME/qpos && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Replace `USERNAME` and the `php` path with the path shown in cPanel → Select PHP Version / Terminal (`which php`).

If you set `APP_ENV=production`, these scheduled commands will **not** run (by design of Kernel.php).

---

## Queue workers

**No queue worker is required.**  
Defaults: `QUEUE_CONNECTION=sync`. No `app/Jobs` workers are required for normal POS operation.

---

## Exact cPanel installation sequence

### 1. Create database

cPanel → **MySQL® Databases** → Create Database → note full name (often `user_dbname`).

### 2. Create database user

Same page → Create User → strong password → note full username (`user_dbuser`).

### 3. Assign user to database

Add user to database → privileges → **ALL PRIVILEGES** → Make Changes.

### 4. Upload ZIP

Upload `QPOS-cPanel-Deployment.zip` to `/home/USERNAME/` (File Manager or FTP).  
Prefer extract to `/home/USERNAME/qpos/` (not inside random nested folders).

### 5. Extract ZIP

Extract so you have `/home/USERNAME/qpos/artisan` and `/home/USERNAME/qpos/public/index.php`.  
Delete the ZIP afterward to save space.

### 6. Document root

Set domain document root to `/home/USERNAME/qpos/public`.

### 7. PHP version

Select **PHP 8.1** or **8.2**.

### 8. Extensions

Enable the extension list above.

### 9. Configure `.env`

Either:

- Use installer (Path A), or  
- Copy `.env.cpanel.example` → `.env` and edit DB + URL + `APP_ENV=live` + `APP_DEBUG=false` + `SHOW_DEMO_SHOPS=false`.

### 10. APP_KEY

Fresh: `php artisan key:generate`  
Migrating data: paste old key.

### 11. Composer

Skip if `vendor/` is present (it is in this ZIP).

### 12. Storage link

```bash
php artisan storage:link
```

### 13. Permissions

`775` dirs / `664` files on `storage`, `bootstrap/cache`, `public/uploads`.

### 14. Initialize database

- Fresh: open `/install` and finish wizard, **or** `migrate --force` + create admin.  
- Existing shop: import SQL + copy uploads; do not blindly migrate.

### 15. Cache

```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

### 16. Cron

Add `schedule:run` every minute if you want live scheduled tasks.

### 17. Open domain

`https://YOURDOMAIN.com` → should redirect to **login**.

### 18. Login

Login page at `/login`.  
Use the admin created by installer/register/seeder.  
Change any demo passwords immediately.

---

## Troubleshooting

### 500 Internal Server Error

1. PHP 8.1+ selected?  
2. Extensions enabled?  
3. `.env` exists and readable?  
4. `APP_KEY` set?  
5. `storage/` and `bootstrap/cache` writable?  
6. `vendor/autoload.php` present?  
7. Read `storage/logs/laravel.log`.  
8. Temporarily set `APP_DEBUG=true` only to diagnose, then set back to `false`.

### 404

- Document root must be `.../public`.  
- `public/.htaccess` present; Apache `mod_rewrite` on.  
- If using alternate root install, root `.htaccess` must be present.

### Database connection error

- Use **full** cPanel DB name/user (with prefix).  
- `DB_HOST=localhost` (usual on cPanel).  
- User has ALL PRIVILEGES on that database.  
- No extra spaces in `.env` values.

### Class not found

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload
```

### Images / uploads missing

- Ensure `public/uploads` exists and is writable.  
- Run `php artisan storage:link` for `/storage` URLs.  
- If migrating, copy old `public/uploads`.

### APP_KEY missing

```bash
php artisan key:generate
php artisan config:clear
```

### CSS/JS not loading

- Document root is `public`.  
- `APP_URL` matches real HTTPS domain (no `/public`).  
- `public/mix-manifest.json` and `public/css`, `public/js` uploaded.  
- Hard-refresh browser; check mixed content if HTTP vs HTTPS mismatch.

### Permission denied

Fix ownership to cPanel user; use 775/664 as above.

### Blank page

Check `storage/logs/laravel.log`. Ensure `storage/framework/views` and `sessions` exist and are writable.

### Installer loop / “already installed”

`.env` already exists → installer exits. Delete `.env` only on a **new** empty install (never on a live shop).

### 419 Page Expired on login

Session/`APP_URL`/HTTPS cookie mismatch. Confirm `APP_URL` uses `https://`, `SESSION_SECURE_COOKIE=true`, and clear caches. Root/public `.htaccess` already disable caching for PHP responses.

---

## Verification checklist

```text
[ ] Domain opens (HTTPS)
[ ] Login page loads
[ ] Login works
[ ] Dashboard (/home) loads
[ ] Database reads/writes (create a test contact or product)
[ ] Products work
[ ] POS opens and can add a line
[ ] Sales save
[ ] Purchases save
[ ] Images load from public/uploads
[ ] Invoice print/PDF works
[ ] File upload works
[ ] Reports open
[ ] Cash register open/close works
[ ] storage/logs has no repeated fatal errors
```

---

## Production safety notes from this workspace

| Finding | Action on server |
|---------|------------------|
| Local `.env` has `APP_DEBUG=true`, local URL/DB | **Do not upload** local `.env` |
| No full SQL dump in repo | Use installer, migrate, or your own export |
| `vendor/` present | Included in ZIP |
| Closure routes | Skip `route:cache` |
| Scheduler needs `APP_ENV=live` | Set `live` for cron jobs to matter |
| Default uploads path = `public/uploads` | Keep writable; migrate uploads with DB if moving shops |
